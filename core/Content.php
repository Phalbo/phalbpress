<?php
/**
 * Contenuti traducibili. Due "famiglie" con la stessa logica:
 *  - entry: pagine, news, storie, partner, paesi, blocchi della home, categorie… (pp_entries + pp_entry_tr)
 *  - doc:   documenti scaricabili (pp_docs + pp_doc_tr + pp_doc_files)
 * Ogni traduzione ricorda l'impronta (src_hash) del testo originale da cui è stata fatta:
 * se l'originale cambia, la traduzione risulta "da aggiornare".
 * Ogni lingua ha il suo slug (pp_entry_tr.slug), creato dal titolo tradotto.
 */
final class Content
{
    public const KINDS = [
        'entry' => ['base' => 'pp_entries', 'tr' => 'pp_entry_tr', 'fk' => 'entry_id'],
        'doc'   => ['base' => 'pp_docs',    'tr' => 'pp_doc_tr',   'fk' => 'doc_id'],
    ];

    /** true in anteprima: si vedono anche bozze e contenuti programmati. */
    public static bool $preview = false;

    public static function now(): string { return date('Y-m-d H:i:s'); }

    public static function meta(array $row): array
    {
        $m = json_decode((string)($row['meta'] ?? ''), true);
        return is_array($m) ? $m : [];
    }

    /** Condizione SQL "visibile al pubblico": pubblicato e dentro la finestra di programmazione. */
    public static function live(string $alias = ''): string
    {
        if (self::$preview) return '1=1';
        $a = $alias ? $alias . '.' : '';
        $now = self::now();
        return "{$a}status = 'published' AND ({$a}publish_at = '' OR {$a}publish_at <= '$now') AND ({$a}unpublish_at = '' OR {$a}unpublish_at > '$now')";
    }

    /* ---------- lettura ---------- */

    public static function tr(string $kind, int $id, string $lang): ?array
    {
        $k = self::KINDS[$kind];
        return DB::one("SELECT * FROM {$k['tr']} WHERE {$k['fk']} = ? AND lang = ?", [$id, $lang]);
    }

    public static function masterHash(string $kind, int $id): string
    {
        $m = self::tr($kind, $id, I18n::$master);
        return $m ? self::hashOf($m) : '';
    }

    /** Impronta del testo originale. Testo alternativo e campi extra contano solo se compilati (le impronte vecchie restano valide). */
    public static function hashOf(array $f): string
    {
        $parts = [$f['title'] ?? '', $f['excerpt'] ?? '', $f['body'] ?? ''];
        if (trim((string)($f['image_alt'] ?? '')) !== '') $parts[] = $f['image_alt'];
        $x = is_array($f['extra'] ?? null) ? $f['extra'] : (json_decode((string)($f['extra'] ?? ''), true) ?: []);
        $x = array_filter($x, fn($v) => trim((string)$v) !== '');
        if ($x) { ksort($x); $parts[] = json_encode($x, JSON_UNESCAPED_UNICODE); }
        return I18n::hash($parts);
    }

    /** Slug di un contenuto in una lingua: quello della lingua, altrimenti quello originale. */
    public static function slugFor(int $id, string $lang): string
    {
        static $cache = [];
        $k = $id . ':' . $lang;
        if (isset($cache[$k])) return $cache[$k];
        $s = (string)DB::val("SELECT slug FROM pp_entry_tr WHERE entry_id = ? AND lang = ? AND slug <> ''", [$id, $lang]);
        if ($s === '') $s = (string)DB::val("SELECT slug FROM pp_entry_tr WHERE entry_id = ? AND lang = ? AND slug <> ''", [$id, I18n::$master]);
        if ($s === '') $s = (string)DB::val('SELECT slug FROM pp_entries WHERE id = ?', [$id]);
        return $cache[$k] = $s;
    }

    /** Unisce riga base + traduzione (con ripiego sull'originale). Aggiunge _lang, _fallback, x (campi extra), slug localizzato. */
    public static function localize(string $kind, array $row, string $lang): array
    {
        $tr = self::tr($kind, (int)$row['id'], $lang);
        $fallback = false;
        $okAuto = PP::$config['publish_machine_translations'] ?? true;
        if (!$tr || trim((string)$tr['title']) === '' || (!$okAuto && $tr['auto'])) {
            $tr = self::tr($kind, (int)$row['id'], I18n::$master) ?: ['title' => '', 'excerpt' => '', 'body' => ''];
            $fallback = $lang !== I18n::$master;
        }
        $row['title'] = (string)$tr['title'];
        $row['excerpt'] = (string)($tr['excerpt'] ?? '');
        $row['body'] = (string)($tr['body'] ?? '');
        $row['image_alt'] = (string)($tr['image_alt'] ?? '');
        $row['_lang'] = $fallback ? I18n::$master : $lang;
        $row['_fallback'] = $fallback;
        if ($kind === 'entry') {
            $row['meta_arr'] = self::meta($row);
            $row['x'] = json_decode((string)($tr['extra'] ?? ''), true) ?: [];
            $row['base_slug'] = $row['slug'];
            $row['slug'] = self::slugFor((int)$row['id'], $lang);
        }
        return $row;
    }

    /** Cerca per slug nella lingua; accetta anche lo slug originale o quello base (vecchi link). */
    public static function entry(string $type, string $slug, string $lang, bool $drafts = false): ?array
    {
        $live = $drafts ? '1=1' : self::live('e');
        $row = DB::one("SELECT e.* FROM pp_entries e JOIN pp_entry_tr t ON t.entry_id = e.id WHERE e.type = ? AND t.lang = ? AND t.slug = ? AND $live", [$type, $lang, $slug])
            ?: DB::one("SELECT e.* FROM pp_entries e JOIN pp_entry_tr t ON t.entry_id = e.id WHERE e.type = ? AND t.lang = ? AND t.slug = ? AND $live", [$type, I18n::$master, $slug])
            ?: DB::one("SELECT * FROM pp_entries e WHERE e.type = ? AND e.slug = ? AND $live", [$type, $slug]);
        return $row ? self::localize('entry', $row, $lang) : null;
    }

    public static function byId(int $id, string $lang, bool $drafts = false): ?array
    {
        $row = DB::one('SELECT * FROM pp_entries e WHERE id = ? AND ' . ($drafts ? '1=1' : self::live('e')), [$id]);
        return $row ? self::localize('entry', $row, $lang) : null;
    }

    public static function entries(string $type, string $lang, int $limit = 0, string $order = 'menu_order ASC, pub_date DESC, id ASC'): array
    {
        $sql = "SELECT * FROM pp_entries WHERE type = ? AND " . self::live() . " ORDER BY $order" . ($limit ? ' LIMIT ' . (int)$limit : '');
        return array_map(fn($r) => self::localize('entry', $r, $lang), DB::all($sql, [$type]));
    }

    public static function menu(string $lang): array
    {
        $rows = DB::all("SELECT * FROM pp_entries WHERE type = 'page' AND in_menu = 1 AND " . self::live() . " ORDER BY menu_order ASC, id ASC");
        return array_map(fn($r) => self::localize('entry', $r, $lang), $rows);
    }

    public static function docs(string $lang, ?string $category = null): array
    {
        $sql = "SELECT * FROM pp_docs WHERE status = 'published'" . ($category ? ' AND category = ?' : '') . ' ORDER BY sort ASC, pub_date DESC, id DESC';
        $rows = DB::all($sql, $category ? [$category] : []);
        return array_map(fn($r) => self::localizeDoc($r, $lang), $rows);
    }

    public static function doc(int $id, string $lang, bool $drafts = false): ?array
    {
        $row = DB::one('SELECT * FROM pp_docs WHERE id = ?' . ($drafts ? '' : " AND status = 'published'"), [$id]);
        return $row ? self::localizeDoc($row, $lang) : null;
    }

    public static function localizeDoc(array $row, string $lang): array
    {
        $row = self::localize('doc', $row, $lang);
        $own = DB::val('SELECT file FROM pp_doc_files WHERE doc_id = ? AND lang = ?', [$row['id'], $lang]);
        $row['file_active'] = $own ?: $row['file'];
        $row['file_is_translated'] = (bool)$own;
        $abs = PP_ROOT . '/' . $row['file_active'];
        $row['file_ext'] = strtoupper(pathinfo((string)$row['file_active'], PATHINFO_EXTENSION));
        $row['file_size'] = ($row['file_active'] && is_file($abs)) ? filesize($abs) : 0;
        $row['langs_available'] = array_column(DB::all('SELECT lang FROM pp_doc_files WHERE doc_id = ?', [$row['id']]), 'lang');
        return $row;
    }

    /* ---------- scrittura ---------- */

    /** Slug libero per tipo e lingua. */
    public static function uniqueSlug(string $type, string $lang, string $slug, int $exceptId): string
    {
        $slug = Media::slug($slug) ?: 'pagina';
        $base = $slug; $i = 1;
        $reserved = array_merge(I18n::codes(), [PP::adminSlug(), 'download', 'core', 'site', 'uploads', 'data', 'pp-admin', 'pp-assets', 'pp-themes', 'form']);
        while (in_array($slug, $reserved, true) || DB::val('SELECT 1 FROM pp_entry_tr t JOIN pp_entries e ON e.id = t.entry_id WHERE e.type = ? AND t.lang = ? AND t.slug = ? AND e.id <> ?', [$type, $lang, $slug, $exceptId])) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }

    /** Salva il testo in una lingua. Le traduzioni salvate a mano vengono allineate all'originale attuale. */
    public static function saveText(string $kind, int $id, string $lang, array $f, bool $auto = false): void
    {
        $k = self::KINDS[$kind];
        $old = self::tr($kind, $id, $lang);
        $title = trim((string)($f['title'] ?? ''));
        $excerpt = trim((string)($f['excerpt'] ?? ''));
        $body = Sanitizer::html($f['body'] ?? '');
        $data = [$k['fk'] => $id, 'lang' => $lang, 'title' => $title, 'excerpt' => $excerpt, 'body' => $body];
        if ($kind === 'entry') {
            $data['image_alt'] = mb_substr(trim(strip_tags((string)($f['image_alt'] ?? ''))), 0, 300);
            $extra = array_map(fn($v) => is_string($v) ? trim(Sanitizer::inline($v)) : $v, (array)($f['extra'] ?? []));
            $data['extra'] = json_encode(array_filter($extra, fn($v) => $v !== '' && $v !== null), JSON_UNESCAPED_UNICODE);
            $type = (string)DB::val('SELECT type FROM pp_entries WHERE id = ?', [$id]);
            $want = trim((string)($f['slug'] ?? ''));
            $data['slug'] = self::uniqueSlug($type, $lang, $want !== '' ? $want : (($old['slug'] ?? '') ?: $title), $id);
        }
        $hash = $lang === I18n::$master ? self::hashOf($data) : self::masterHash($kind, $id);
        DB::upsert($k['tr'], $data + ['src_hash' => $hash, 'auto' => $auto ? 1 : 0, 'updated_at' => self::now()], [$k['fk'], 'lang']);
        DB::update($k['base'], ['updated_at' => self::now()], 'id = ?', [$id]);
        // lo slug base segue quello della lingua originale (tranne la home)
        if ($kind === 'entry' && $lang === I18n::$master) DB::run("UPDATE pp_entries SET slug = ? WHERE id = ? AND slug <> 'home'", [$data['slug'], $id]);
        if ($old) self::revision($kind, $id, $lang, $old);
        if ($kind === 'entry' && $old && $old['slug'] !== '' && $old['slug'] !== $data['slug']) Redirects::slugChanged($id, $lang, $old['slug'], $data['slug']);
    }

    /* ---------- revisioni ---------- */

    public static function revision(string $kind, int $id, string $lang, array $snapshot): void
    {
        $last = DB::val('SELECT data FROM pp_revisions WHERE kind = ? AND ref_id = ? AND lang = ? ORDER BY id DESC LIMIT 1', [$kind, $id, $lang]);
        $json = json_encode($snapshot, JSON_UNESCAPED_UNICODE);
        if ($last === $json) return;
        DB::insert('pp_revisions', ['kind' => $kind, 'ref_id' => $id, 'lang' => $lang, 'user_email' => Auth::user()['email'] ?? '', 'at' => self::now(), 'data' => $json]);
        $keep = (int)(PP::$config['revisions_keep'] ?? 30);
        $ids = array_column(DB::all('SELECT id FROM pp_revisions WHERE kind = ? AND ref_id = ? AND lang = ? ORDER BY id DESC', [$kind, $id, $lang]), 'id');
        foreach (array_slice($ids, $keep) as $old) DB::run('DELETE FROM pp_revisions WHERE id = ?', [$old]);
    }

    public static function revisions(string $kind, int $id, string $lang): array
    {
        return DB::all('SELECT id, user_email, at FROM pp_revisions WHERE kind = ? AND ref_id = ? AND lang = ? ORDER BY id DESC', [$kind, $id, $lang]);
    }

    public static function restore(int $revId): ?array
    {
        $r = DB::one('SELECT * FROM pp_revisions WHERE id = ?', [$revId]);
        if (!$r) return null;
        $d = json_decode((string)$r['data'], true) ?: [];
        $d['extra'] = json_decode((string)($d['extra'] ?? ''), true) ?: [];
        self::saveText($r['kind'], (int)$r['ref_id'], $r['lang'], $d, (bool)($d['auto'] ?? false));
        return $r;
    }

    /* ---------- duplica ---------- */

    public static function duplicate(int $id): int
    {
        $row = DB::one('SELECT * FROM pp_entries WHERE id = ?', [$id]);
        if (!$row) throw new RuntimeException('Contenuto non trovato');
        unset($row['id']);
        $row['slug'] = Media::slug($row['slug'] . '-copia-' . substr(bin2hex(random_bytes(2)), 0, 4));
        $row['status'] = 'draft';
        $row['in_menu'] = 0;
        $row['created_at'] = $row['updated_at'] = self::now();
        $row['author_id'] = (int)(Auth::user()['id'] ?? 0);
        $new = DB::insert('pp_entries', $row);
        foreach (DB::all('SELECT * FROM pp_entry_tr WHERE entry_id = ?', [$id]) as $t) {
            $t['entry_id'] = $new;
            $t['title'] = $t['title'] . ($t['lang'] === I18n::$master ? ' (copia)' : '');
            $t['slug'] = self::uniqueSlug($row['type'], $t['lang'], $t['slug'] . '-copia', $new);
            DB::insert('pp_entry_tr', $t);
        }
        // l'originale è cambiato (" (copia)"): riallinea le impronte delle traduzioni copiate
        $h = self::masterHash('entry', $new);
        DB::run('UPDATE pp_entry_tr SET src_hash = ? WHERE entry_id = ? AND lang <> ?', [$h, $new, I18n::$master]);
        return $new;
    }

    /** Stato di tutte le lingue per un contenuto. */
    public static function statuses(string $kind, int $id): array
    {
        $k = self::KINDS[$kind];
        $hash = self::masterHash($kind, $id);
        $rows = [];
        foreach (DB::all("SELECT * FROM {$k['tr']} WHERE {$k['fk']} = ?", [$id]) as $r) $rows[$r['lang']] = $r;
        $out = [];
        foreach (I18n::codes() as $l) $out[$l] = I18n::status($l, $rows[$l] ?? null, $hash);
        return $out;
    }

    public static function deleteItem(string $kind, int $id): void
    {
        $k = self::KINDS[$kind];
        DB::run("DELETE FROM {$k['tr']} WHERE {$k['fk']} = ?", [$id]);
        if ($kind === 'doc') DB::run('DELETE FROM pp_doc_files WHERE doc_id = ?', [$id]);
        DB::run("DELETE FROM {$k['base']} WHERE id = ?", [$id]);
        DB::run('DELETE FROM pp_revisions WHERE kind = ? AND ref_id = ?', [$kind, $id]);
    }

    /* ---------- testi di interfaccia ---------- */

    public static function stringMasterHash(string $key): string
    {
        $v = DB::val('SELECT value FROM pp_strings WHERE skey = ? AND lang = ?', [$key, I18n::$master]);
        return I18n::hash([(string)$v]);
    }

    public static function saveString(string $key, string $lang, string $value, bool $auto = false): void
    {
        $def = I18n::defaults()[$key] ?? [];
        $value = ($def['html'] ?? false) === 'block' ? Sanitizer::html($value) : (!empty($def['html']) ? Sanitizer::inline($value) : trim(strip_tags($value)));
        $hash = $lang === I18n::$master ? I18n::hash([$value]) : self::stringMasterHash($key);
        DB::upsert('pp_strings', [
            'skey' => $key, 'lang' => $lang, 'value' => $value, 'src_hash' => $hash,
            'auto' => $auto ? 1 : 0, 'updated_at' => self::now(),
        ], ['skey', 'lang']);
        I18n::flush();
    }

    public static function stringStatus(string $key, string $lang): string
    {
        $row = DB::one('SELECT * FROM pp_strings WHERE skey = ? AND lang = ?', [$key, $lang]);
        return I18n::status($lang, $row, self::stringMasterHash($key));
    }

    /** Elenco di tutto ciò che va tradotto/aggiornato, per la coda di traduzione. */
    public static function pending(?string $onlyLang = null, bool $includeAuto = false): array
    {
        $langs = $onlyLang ? [$onlyLang] : I18n::others();
        $want = $includeAuto ? ['missing', 'outdated', 'auto'] : ['missing', 'outdated'];
        $jobs = [];
        foreach (DB::all('SELECT id, type FROM pp_entries') as $e) {
            if (!self::tr('entry', (int)$e['id'], I18n::$master)) continue;
            $st = self::statuses('entry', (int)$e['id']);
            foreach ($langs as $l) if (in_array($st[$l], $want, true)) $jobs[] = ['kind' => 'entry', 'id' => (int)$e['id'], 'lang' => $l, 'label' => $e['type'] . ' #' . $e['id']];
        }
        foreach (DB::all('SELECT id, code FROM pp_docs') as $d) {
            if (!self::tr('doc', (int)$d['id'], I18n::$master)) continue;
            $st = self::statuses('doc', (int)$d['id']);
            foreach ($langs as $l) if (in_array($st[$l], $want, true)) $jobs[] = ['kind' => 'doc', 'id' => (int)$d['id'], 'lang' => $l, 'label' => 'documento ' . ($d['code'] ?: '#' . $d['id'])];
        }
        foreach (array_keys(I18n::defaults()) as $key) {
            if (trim(I18n::t($key, I18n::$master)) === '') continue;
            foreach ($langs as $l) {
                if (in_array(self::stringStatus($key, $l), $want, true)) $jobs[] = ['kind' => 'string', 'id' => $key, 'lang' => $l, 'label' => 'testo ' . $key];
            }
        }
        return $jobs;
    }
}

/**
 * Redirect 301/302. Quelli automatici nascono quando cambia lo slug di un contenuto pubblicato.
 */
final class Redirects
{
    /** Parametri dell'indirizzo che distinguono un redirect (indirizzi dei vecchi siti WordPress: ?lang=en, ?p=12, ?page_id=34). */
    public const QUERY_KEYS = ['lang', 'p', 'page_id', 'post_type'];

    /** "lang=en&utm_source=x&p=3" → "lang=en&p=3" (solo i parametri che contano, in ordine) */
    public static function query(string $q): string
    {
        parse_str($q, $a);
        $a = array_intersect_key(array_filter($a, 'is_scalar'), array_flip(self::QUERY_KEYS));
        ksort($a);
        return http_build_query($a);
    }

    public static function find(string $path, string $query = ''): ?array
    {
        $p = '/' . trim($path, '/');
        $base = $p === '/' ? '/' : $p . '/';
        $q = self::query($query);
        try {
            if ($q !== '' && ($r = DB::one('SELECT * FROM pp_redirects WHERE from_path = ?', [$base . '?' . $q]))) return $r;
            if ($base === '/') return null;
            return DB::one('SELECT * FROM pp_redirects WHERE from_path = ?', [$base]) ?: DB::one('SELECT * FROM pp_redirects WHERE from_path = ?', [$p]);
        }
        catch (Throwable $e) { return null; }
    }

    public static function add(string $from, string $to, int $code = 301, bool $auto = false): void
    {
        $q = self::query((string)parse_url($from, PHP_URL_QUERY));
        $from = '/' . trim((string)(parse_url($from, PHP_URL_PATH) ?: ''), '/');
        $from = ($from === '/' ? '/' : $from . '/') . ($q !== '' ? '?' . $q : '');
        if (preg_match('~^https?://~', $to) === 0) $to = '/' . ltrim($to, '/');
        if ($from === $to || $from === '/') return;
        DB::run('DELETE FROM pp_redirects WHERE from_path = ?', [$to]);   // evita catene e anelli
        DB::upsert('pp_redirects', ['from_path' => $from, 'to_path' => $to, 'code' => in_array($code, [301, 302], true) ? $code : 301, 'auto' => $auto ? 1 : 0, 'created_at' => Content::now()], ['from_path']);
        DB::run('UPDATE pp_redirects SET to_path = ? WHERE to_path = ?', [$to, $from]);
    }

    /** Lo slug è cambiato: il vecchio indirizzo porta al nuovo (solo se il contenuto ha una pagina pubblica). */
    public static function slugChanged(int $id, string $lang, string $old, string $new): void
    {
        $e = DB::one('SELECT type, status FROM pp_entries WHERE id = ?', [$id]);
        if (!$e || $e['status'] !== 'published') return;
        $prefix = PP::typePrefix($e['type'], $lang);
        if ($prefix === null) return;
        self::add('/' . $lang . '/' . $prefix . $old . '/', '/' . $lang . '/' . $prefix . $new . '/', 301, true);
    }
}
