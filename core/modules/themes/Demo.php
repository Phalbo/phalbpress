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
    private static function fill(array $seed): void
    {
        $now = Content::now();
        $pick = fn(array $tr, string $l) => $tr[$l] ?? $tr['en'] ?? reset($tr);
        foreach ($seed['entries'] as $e) {
            $id = DB::insert('pp_entries', [
                'type' => $e['type'], 'slug' => $e['slug'], 'template' => $e['template'] ?? 'default',
                'menu_order' => $e['menu_order'] ?? 0, 'in_menu' => $e['in_menu'] ?? 0, 'status' => 'published',
                'pub_date' => $e['pub_date'] ?? '', 'image' => self::media((string)($e['image'] ?? '')),
                'meta' => json_encode(array_map(fn($v) => is_string($v) ? self::media($v) : $v, $e['meta'] ?? []), JSON_UNESCAPED_UNICODE),
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

    /** I link interni della pagina restano nell'anteprima demo, così si può girare per il sito finto. */
    public static function links(string $html, string $query): string
    {
        $base = rtrim((string)parse_url(PP::url(''), PHP_URL_PATH), '/');
        $langs = implode('|', array_map(fn($l) => preg_quote($l, '~'), I18n::codes()));
        return preg_replace_callback('~href="' . preg_quote($base, '~') . '(/(?:' . $langs . ')/[^"#?]*)"~', function ($m) use ($query) {
            return 'href="' . e(Admin::u('preview') . '?' . $query . '&path=' . rawurlencode(html_entity_decode($m[1]))) . '"';
        }, $html);
    }
}
