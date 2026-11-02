<?php
/**
 * Contenuti di partenza di un sito: li usano l'installazione (install.php) e "Azzera il sito" (Backup e modelli).
 * - seed(): pagine, fasce e documenti di site/seed.php (immagini da site/seed-media/)
 * - strings(): testi di interfaccia nelle lingue del sito
 * - start(): una delle partenze (seed | demo | empty) + testi + sito nascosto ai motori di ricerca
 */
final class Starter
{
    /** Immagini del seed: da site/seed-media/ a uploads/media/ (una volta). */
    public static function media(string $rel): string
    {
        if ($rel === '' || !str_starts_with($rel, 'seed-media/')) return $rel;
        $src = PP_SITE . '/' . $rel;
        if (!is_file($src)) return '';
        $dst = 'uploads/media/' . date('Y/m') . '/' . basename($rel);
        if (!is_dir(dirname(PP_ROOT . '/' . $dst))) mkdir(dirname(PP_ROOT . '/' . $dst), 0775, true);
        copy($src, PP_ROOT . '/' . $dst);
        $small = preg_replace('~(\.[a-z0-9]+)$~', '-960$1', $src);
        if (is_file($small)) copy($small, preg_replace('~(\.[a-z0-9]+)$~', '-960$1', PP_ROOT . '/' . $dst));
        return $dst;
    }

    public static function hasSeed(): bool
    {
        return is_file(PP_SITE . '/seed.php');
    }

    public static function seed(): void
    {
        $now = Content::now();
        $seed = self::hasSeed() ? require PP_SITE . '/seed.php' : ['entries' => [], 'docs' => []];
        foreach ($seed['entries'] ?? [] as $e) {
            if (DB::val('SELECT id FROM pp_entries WHERE type = ? AND slug = ?', [$e['type'], $e['slug']])) continue;
            $id = DB::insert('pp_entries', [
                'type' => $e['type'], 'slug' => $e['slug'], 'template' => $e['template'] ?? 'default',
                'menu_order' => $e['menu_order'] ?? 0, 'in_menu' => $e['in_menu'] ?? 0, 'status' => $e['status'] ?? 'published',
                'pub_date' => $e['pub_date'] ?? '', 'image' => self::media($e['image'] ?? ''),
                'meta' => json_encode(array_map(fn($v) => is_string($v) && str_contains($v, 'seed-media/') ? implode("\n", array_map([self::class, 'media'], explode("\n", $v))) : $v, $e['meta'] ?? []), JSON_UNESCAPED_UNICODE),
                'created_at' => $now, 'updated_at' => $now,
            ]);
            foreach (array_unique(array_merge([I18n::$master], array_keys($e['tr']))) as $l) {
                if (!isset($e['tr'][$l])) continue;
                // nell'originale lo slug è quello del seed; nelle altre lingue nasce dal titolo tradotto (o da 'slug' nel seed)
                Content::saveText('entry', $id, $l, $e['tr'][$l] + ['excerpt' => '', 'body' => '', 'slug' => $l === I18n::$master && $e['slug'] !== 'home' ? $e['slug'] : ''], in_array($l, $e['auto'] ?? [], true));
            }
        }
        foreach ($seed['docs'] ?? [] as $d) {
            if (DB::val('SELECT id FROM pp_docs WHERE code = ?', [$d['code']])) continue;
            $id = DB::insert('pp_docs', [
                'code' => $d['code'], 'category' => $d['category'], 'wp' => $d['wp'] ?? '', 'pub_date' => $d['pub_date'] ?? '',
                'file' => '', 'status' => 'published', 'sort' => $d['sort'] ?? 0, 'created_at' => $now, 'updated_at' => $now,
            ]);
            Content::saveText('doc', $id, I18n::$master, $d['tr'][I18n::$master] + ['excerpt' => '', 'body' => '']);
            foreach ($d['tr'] as $l => $f) if ($l !== I18n::$master) Content::saveText('doc', $id, $l, $f + ['excerpt' => '', 'body' => '']);
        }
    }

    /** Testi di interfaccia: prima l'originale, poi le lingue presenti nei valori iniziali. */
    public static function strings(): void
    {
        $machine = ['sl', 'sr', 'sq', 'es']; // valori iniziali scritti senza revisione madrelingua
        foreach (I18n::defaults() as $k => $d) {
            $m = $d['text'][I18n::$master] ?? '';
            // lingua originale che il testo non ha (es. spagnolo): si parte dall'inglese, segnato da rivedere
            $fromEn = $m === '' && ($d['text']['en'] ?? '') !== '';
            if ($fromEn) $m = $d['text']['en'];
            if ($m !== '' && !DB::val('SELECT 1 FROM pp_strings WHERE skey = ? AND lang = ?', [$k, I18n::$master])) Content::saveString($k, I18n::$master, $m, $fromEn);
            foreach ($d['text'] as $l => $v) {
                if ($l === I18n::$master || !I18n::valid($l)) continue;
                if (DB::val('SELECT 1 FROM pp_strings WHERE skey = ? AND lang = ?', [$k, $l])) continue;
                Content::saveString($k, $l, $v, in_array($l, $machine, true));
            }
            // i numeri ("150") valgono per tutte le lingue: non serve tradurli
            if (preg_match('~^\d+$~', $m)) foreach (I18n::others() as $l) {
                if (!DB::val('SELECT 1 FROM pp_strings WHERE skey = ? AND lang = ?', [$k, $l])) Content::saveString($k, $l, $m);
            }
        }
    }

    /** Partenza: 'seed' (contenuti del sito), 'demo' (esempio), altro = vuoto. Il sito nasce nascosto ai motori di ricerca. */
    public static function start(string $start): void
    {
        if ($start === 'demo' && class_exists('Demo')) Demo::install();
        elseif ($start === 'seed') self::seed();
        self::strings();
        // un sito nuovo nasce nascosto ai motori di ricerca: si apre da SEO quando è pronto
        Settings::set('search_hidden', 1);
    }
}
