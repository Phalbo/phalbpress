<?php
/**
 * Anteprima dei temi con i contenuti dimostrativi (pp-assets/demo): news, storie, partner, documenti e foto
 * di un progetto inventato, per vedere un tema "vero" anche su un sito che ha ancora pochi contenuti o poche foto.
 * Il sito non viene toccato: i contenuti stanno in un database SQLite a parte (data/demo-preview.sqlite),
 * creato al primo uso e rifatto quando cambiano i contenuti dimostrativi o le lingue del sito.
 */
final class Demo
{
    public const FILE = PP_ROOT . '/data/demo-preview.sqlite';

    /** Per il resto di questa richiesta il sito legge i contenuti dimostrativi invece dei suoi. */
    public static function use(): void
    {
        $seed = PP_ROOT . '/pp-assets/demo/seed.php';
        if (!is_file($seed)) return;
        $stamp = md5_file($seed) . '|' . implode(',', I18n::codes()) . '|' . I18n::$master . '|' . PP::VERSION;
        $fresh = is_file(self::FILE) && is_file(self::FILE . '.stamp') && trim((string)file_get_contents(self::FILE . '.stamp')) === $stamp;
        if (!$fresh) {
            foreach ([self::FILE, self::FILE . '-wal', self::FILE . '-shm'] as $f) if (is_file($f)) unlink($f);
        }
        DB::reconnect(['driver' => 'sqlite', 'file' => self::FILE]);
        Settings::reset();
        I18n::flush();
        if (!$fresh) {
            DB::migrate();
            self::fill(require $seed);
            file_put_contents(self::FILE . '.stamp', $stamp);
        }
    }

    private static function media(string $v): string
    {
        return implode("\n", array_map(fn($p) => str_starts_with($p, 'seed-media/') ? 'pp-assets/demo/media/' . substr($p, 11) : $p, explode("\n", $v)));
    }

    /** Ogni lingua del sito riceve il testo dimostrativo nella sua lingua, se c'è, altrimenti quello inglese. */
    private static function fill(array $seed, ?callable $map = null): void
    {
        $now = Content::now();
        $map ??= fn(string $v) => self::media($v);
        $pick = fn(array $tr, string $l) => $tr[$l] ?? $tr['en'] ?? reset($tr);
        foreach ($seed['entries'] as $e) {
            $id = DB::insert('pp_entries', [
                'type' => $e['type'], 'slug' => $e['slug'], 'template' => $e['template'] ?? 'default',
                'menu_order' => $e['menu_order'] ?? 0, 'in_menu' => $e['in_menu'] ?? 0, 'status' => 'published',
                'pub_date' => $e['pub_date'] ?? '', 'image' => $map((string)($e['image'] ?? '')),
                'meta' => json_encode(array_map(fn($v) => is_string($v) ? $map($v) : $v, $e['meta'] ?? []), JSON_UNESCAPED_UNICODE),
                'created_at' => $now, 'updated_at' => $now,
            ]);
            foreach (array_merge([I18n::$master], I18n::others()) as $l) {
                $f = $pick($e['tr'], $l);
                $slug = $l === I18n::$master && $e['slug'] !== 'home' && empty($f['slug']) ? $e['slug'] : (string)($f['slug'] ?? '');
                Content::saveText('entry', $id, $l, ['excerpt' => '', 'body' => ''] + $f + ['slug' => $slug]);
            }
        }
        foreach ($seed['docs'] as $d) {
            $id = DB::insert('pp_docs', [
                'code' => $d['code'], 'category' => $d['category'], 'wp' => $d['wp'] ?? '', 'pub_date' => $d['pub_date'] ?? '',
                'file' => '', 'status' => 'published', 'sort' => $d['sort'] ?? 0, 'created_at' => $now, 'updated_at' => $now,
            ]);
            foreach (array_merge([I18n::$master], I18n::others()) as $l) Content::saveText('doc', $id, $l, ['excerpt' => '', 'body' => ''] + $pick($d['tr'], $l));
        }
    }

    /**
     * Installazione con i contenuti di esempio nel sito vero: gli stessi testi dell'anteprima demo,
     * con le foto copiate in uploads/media/ come segnaposto (una per contenuto) da sostituire poi una alla volta.
     */
    public static function install(): void
    {
        $src = PP_ROOT . '/pp-assets/demo/media/';
        $dir = 'uploads/media/' . date('Y/m');
        if (!is_dir(PP_ROOT . '/' . $dir)) mkdir(PP_ROOT . '/' . $dir, 0775, true);
        $copy = function (string $v) use ($src, $dir): string {
            return implode("\n", array_map(function ($p) use ($src, $dir) {
                if (!str_starts_with($p, 'seed-media/')) return $p;
                $name = substr($p, 11);
                $prefix = str_starts_with($name, 'partner-') ? '' : 'segnaposto-';
                $rel = $dir . '/' . $prefix . $name;
                if (!is_file($src . $name)) return '';
                if (!is_file(PP_ROOT . '/' . $rel)) {
                    copy($src . $name, PP_ROOT . '/' . $rel);
                    $small = preg_replace('~(\.webp)$~', '-960$1', $name);
                    if ($small !== $name && is_file($src . $small)) copy($src . $small, PP_ROOT . '/' . preg_replace('~(\.webp)$~', '-960$1', $rel));
                }
                return $rel;
            }, explode("\n", $v)));
        };
        self::fill(require PP_ROOT . '/pp-assets/demo/seed.php', $copy);
    }

    /** I link interni della pagina restano nell'anteprima demo, così si può girare per il sito finto. */
    public static function links(string $html, string $query): string
    {
        $base = rtrim((string)parse_url(PP::url(''), PHP_URL_PATH), '/');
        $langs = implode('|', array_map(fn($l) => preg_quote($l, '~'), I18n::codes()));
        return preg_replace_callback('~href="' . preg_quote($base, '~') . '(/(?:' . $langs . ')/[^"#?]*)"~', function ($m) use ($query) {
            return 'href="' . e(Admin::u('preview') . '?' . $query . '&path=' . rawurlencode(html_entity_decode($m[1]))) . '"';
        }, $html);
    }

    /**
     * Foto segnaposto nel sito vero: ogni posto senza foto (apertura, news, storie, galleria) riceve una foto di esempio
     * con le misure stampate sopra, copiata in uploads/media/ come file a sé ("segnaposto-…"), così si sostituisce una alla volta
     * da Immagini e file → Sostituisci. Le foto già messe non si toccano mai. Restituisce quante foto ha messo.
     */
    public static function placeholders(): int
    {
        $src = PP_ROOT . '/pp-assets/demo/media/';
        $dir = 'uploads/media/' . date('Y/m');
        if (!is_dir(PP_ROOT . '/' . $dir)) mkdir(PP_ROOT . '/' . $dir, 0775, true);
        $copy = function (string $from, string $name) use ($src, $dir): string {
            $base = $dir . '/segnaposto-' . Media::slug($name);
            $rel = $base . '.webp';
            for ($i = 2; is_file(PP_ROOT . '/' . $rel); $i++) $rel = $base . '-' . $i . '.webp';
            copy($src . $from . '.webp', PP_ROOT . '/' . $rel);
            if (is_file($src . $from . '-960.webp')) copy($src . $from . '-960.webp', PP_ROOT . '/' . substr($rel, 0, -5) . '-960.webp');
            return $rel;
        };
        $n = 0;
        $count = ['news' => 0, 'story' => 0];
        foreach (DB::all("SELECT id, type, slug, template, image, meta FROM pp_entries WHERE type IN ('block', 'news', 'story') ORDER BY type, menu_order, pub_date DESC, id") as $e) {
            $m = json_decode((string)$e['meta'], true) ?: [];
            $set = [];
            if ($e['type'] === 'block' && $e['template'] === 'hero' && $e['image'] === '') {
                $set['image'] = $copy('hero-1', 'apertura-' . $e['slug']); $n++;
            } elseif ($e['type'] === 'block' && $e['template'] === 'gallery' && trim((string)($m['images'] ?? '')) === '') {
                $m['images'] = implode("\n", array_map(fn($i) => $copy('galleria-' . $i, 'galleria-' . $i), range(1, 6)));
                $set['meta'] = json_encode($m, JSON_UNESCAPED_UNICODE); $n += 6;
            } elseif ($e['type'] === 'news' && $e['image'] === '') {
                $set['image'] = $copy('news-' . ($count['news']++ % 6 + 1), 'news-' . $e['slug']); $n++;
            } elseif ($e['type'] === 'story' && $e['image'] === '' && empty($m['anonymous'])) {
                $set['image'] = $copy('storia-' . ($count['story']++ % 3 + 1), 'storia-' . $e['slug']); $n++;
            }
            if ($set) DB::update('pp_entries', $set + ['updated_at' => Content::now()], 'id = ?', [(int)$e['id']]);
        }
        return $n;
    }
}
