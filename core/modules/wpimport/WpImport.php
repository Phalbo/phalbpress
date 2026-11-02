<?php
/**
 * Importazione di un sito WordPress: contenuti, traduzioni (WPML), categorie, foto e file, pagine madri, menu, SEO (Yoast), redirect.
 *
 * Si lavora a passi che riprendono da dove si erano fermati (i server condivisi interrompono le richieste lunghe):
 *   scan → media → terms → entries → links → menu → redirects → done
 * Ogni cosa importata è segnata in pp_wp_map (tipo, chiave di WordPress → riferimento nuovo): rilanciando si aggiorna invece di duplicare.
 *
 * Configurazione del sito (tutto facoltativo), config['wp_import']:
 *   'types'      => ['post' => 'news', 'page' => 'page', 'portfolio' => 'story']   tipo di WordPress → tipo di PhalbPress
 *   'taxonomies' => ['category' => ['type' => 'category', 'field' => 'category']]   termini → contenuti, collegati con un campo
 *   'drafts'     => true      importa anche le bozze (restano bozze)
 *   'skip'       => [id o slug di WordPress da non importare]; con 'slug' => 'home' | chiave di sezione (es. 'stories') | '/percorso/' | 'category:slug'
 *                   il vecchio indirizzo porta lì (pagine che erano solo griglie automatiche, la vecchia home…)
 *   'download'   => true      i file che non sono nello zip si scaricano dal vecchio sito, piano
 *   'menu'       => 'slug'    menu di WordPress da usare come menu principale (di base: il più lungo nella lingua principale)
 * Il sito può ritoccare ogni contenuto col filtro 'wp_import_entry' ($data, $item, $lang).
 */
final class WpImport
{
    public const STEPS = ['scan', 'media', 'terms', 'entries', 'links', 'menu', 'redirects', 'done'];
    private const LABELS = ['scan' => 'Lettura dei file', 'media' => 'Foto e file', 'terms' => 'Categorie', 'entries' => 'Contenuti',
        'links' => 'Pagine madri, link e SEO', 'menu' => 'Menu', 'redirects' => 'Redirect', 'done' => 'Finito'];

    private static ?array $model = null;
    private static array $mediaIndex = [];

    public static function label(string $step): string { return self::LABELS[$step] ?? $step; }

    public static function cfg(): array
    {
        return array_replace([
            'types' => ['post' => 'news', 'page' => 'page'],
            'taxonomies' => ['category' => ['type' => 'category', 'field' => 'category']],
            'drafts' => true, 'skip' => [], 'download' => true, 'menu' => '',
        ], (array)(PP::$config['wp_import'] ?? []));
    }

    /* ---------- stato ---------- */

    private static function stateFile(): string { return WpSource::dir() . '/state.json'; }

    public static function state(): array
    {
        $s = is_file(self::stateFile()) ? json_decode((string)file_get_contents(self::stateFile()), true) : null;
        return is_array($s) ? $s : ['step' => 'scan', 'cursor' => 0, 'counts' => [], 'notes' => [], 'started' => ''];
    }

    private static function save(array $s): void { file_put_contents(self::stateFile(), json_encode($s, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); }

    public static function reset(): void { @unlink(self::stateFile()); @unlink(WpSource::modelFile()); WpSource::cleanTmp(); }

    private static function model(): array
    {
        if (self::$model === null) {
            self::$model = WpSource::model() ?? WpSource::scan();
            foreach (self::$model['media'] as $m) if ($m['file'] !== '' && $m['alt'] !== '') self::$mediaIndex[$m['file']] = $m['alt'];
        }
        return self::$model;
    }

    /* ---------- tabella dei collegamenti ---------- */

    public static function migrate(): void
    {
        $vc = fn($n) => DB::driver() === 'sqlite' ? 'TEXT' : "VARCHAR($n)";
        $tail = DB::driver() === 'sqlite' ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        DB::pdo()->exec("CREATE TABLE IF NOT EXISTS pp_wp_map (kind {$vc(20)} NOT NULL, wp_key {$vc(190)} NOT NULL, ref {$vc(255)} NOT NULL DEFAULT '', PRIMARY KEY (kind, wp_key))$tail");
    }

    private static function map(string $kind, string $key): ?string
    {
        $v = DB::val('SELECT ref FROM pp_wp_map WHERE kind = ? AND wp_key = ?', [$kind, $key]);
        return $v === null || $v === false ? null : (string)$v;
    }

    private static function setMap(string $kind, string $key, string $ref): void
    {
        DB::upsert('pp_wp_map', ['kind' => $kind, 'wp_key' => $key, 'ref' => $ref], ['kind', 'wp_key']);
    }

    /* ---------- foto già importate (anche dopo "Azzera il sito") ----------
       Il collegamento file di WordPress → file nuovo sta nel database e anche in data/wp-media-map.json, accanto agli upload:
       se il database riparte da zero ma le foto restano, l'importazione le ritrova invece di rifarle. */

    private static ?array $kept = null;

    public static function mediaMapFile(): string { return PP_ROOT . '/data/wp-media-map.json'; }

    private static function kept(string $orig): string
    {
        if (self::$kept === null) self::$kept = is_file(self::mediaMapFile()) ? (json_decode((string)file_get_contents(self::mediaMapFile()), true) ?: []) : [];
        $rel = (string)(self::$kept[$orig] ?? '');
        return $rel !== '' && str_starts_with($rel, 'uploads/') && !str_contains($rel, '..') && is_file(PP_ROOT . '/' . $rel) ? $rel : '';
    }

    /** Scrive la mappa delle foto (quelle del database più quelle già nel file, se il file c'è ancora). */
    public static function saveMediaMap(): void
    {
        self::migrate();
        $map = is_file(self::mediaMapFile()) ? (json_decode((string)file_get_contents(self::mediaMapFile()), true) ?: []) : [];
        foreach (DB::all("SELECT wp_key, ref FROM pp_wp_map WHERE kind = 'media' AND ref <> ''") as $r) $map[$r['wp_key']] = $r['ref'];
        $map = array_filter($map, fn($rel) => is_string($rel) && is_file(PP_ROOT . '/' . $rel));
        if (!$map) { @unlink(self::mediaMapFile()); return; }
        ksort($map);
        file_put_contents(self::mediaMapFile(), json_encode($map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        self::$kept = $map;
    }

    /* ---------- gruppi di traduzioni ---------- */

    /** Lingua di un contenuto di WordPress (WPML) o la lingua principale del sito. */
    private static function langOf(int $id, string $kind = 'posts'): string
    {
        return self::model()['icl'][$kind][$id][1] ?? I18n::$master;
    }

    /**
     * Contenuti da importare, raggruppati per traduzione: ['g123' => ['type' => pp type, 'items' => [lang => item]], …]
     * e quelli lasciati fuori, col motivo.
     */
    public static function groups(): array
    {
        $m = self::model(); $c = self::cfg();
        $groups = []; $out = [];
        $skip = self::skipList();
        $ok = $c['drafts'] ? ['publish', 'draft', 'pending', 'private', 'future'] : ['publish', 'future'];
        foreach ($m['items'] as $it) {
            $type = $c['types'][$it['type']] ?? null;
            if ($type === null) continue;
            $why = '';
            if (!in_array($it['status'], $ok, true)) $why = 'stato ' . $it['status'];
            elseif (in_array((string)$it['id'], $skip, true) || in_array($it['slug'], $skip, true)) $why = 'escluso nel config';
            $lang = self::langOf($it['id']);
            if (!$why && !I18n::valid($lang)) $why = 'lingua ' . $lang . ' non presente nel sito';
            if ($why) { $out[] = ['id' => $it['id'], 'type' => $it['type'], 'title' => $it['title'], 'why' => $why]; continue; }
            $key = isset($m['icl']['posts'][$it['id']]) ? 'g' . $m['icl']['posts'][$it['id']][0] : 'p' . $it['id'];
            $groups[$key]['type'] = $type;
            // due contenuti nella stessa lingua e traduzione (capita con WPML): vince il pubblicato
            $prev = $groups[$key]['items'][$lang] ?? null;
            if (!$prev || ($prev['status'] !== 'publish' && $it['status'] === 'publish')) $groups[$key]['items'][$lang] = $it;
        }
        return ['groups' => $groups, 'skipped' => $out];
    }

    /** id o slug da saltare (le chiavi testuali di 'skip' hanno anche una destinazione per il redirect). */
    private static function skipList(): array
    {
        $out = [];
        foreach ((array)self::cfg()['skip'] as $k => $v) $out[] = is_int($k) ? (string)$v : (string)$k;
        return $out;
    }

    /** Il contenuto di riferimento del gruppo: quello nella lingua principale, altrimenti il primo. */
    private static function master(array $g): array
    {
        return $g['items'][I18n::$master] ?? reset($g['items']);
    }

    /* ---------- foto e file ---------- */

    /** Percorso dentro uploads/ del vecchio sito per un id o un indirizzo, nella versione originale (senza -300x200). */
    private static function wpFile(string $ref): string
    {
        $m = self::model();
        $ref = trim($ref);
        if ($ref === '') return '';
        if (ctype_digit($ref)) return (string)($m['media'][(int)$ref]['file'] ?? '');
        return WpSource::uploadPath($ref);
    }

    /** Importa (se serve) un file del vecchio sito e restituisce [percorso nuovo, testo alternativo]. Con $dry non scrive niente. */
    public static function media(string $ref, string $alt = '', bool $dry = false): array
    {
        $file = self::wpFile($ref);
        if ($file === '') return ['', ''];
        $orig = preg_replace('~-\d+x\d+(\.[A-Za-z0-9]+)$~', '$1', $file);
        $alt = trim($alt) !== '' && !preg_match('~^(alt|image|img|foto|dsc|img_)\d*$~i', trim($alt)) ? trim($alt) : (self::$mediaIndex[$orig] ?? self::$mediaIndex[$file] ?? '');
        if ($dry) return ['uploads/media/' . $orig, $alt];
        $done = self::map('media', $orig);
        if ($done !== null) return [$done, $alt];
        if (($have = self::kept($orig)) !== '') { self::setMap('media', $orig, $have); return [$have, $alt]; }
        $c = self::cfg();
        $base = self::model()['base'];
        $tmp = '';
        foreach (array_unique([$orig, $file, preg_replace('~(\.[A-Za-z0-9]+)$~', '-scaled$1', $orig)]) as $try) {
            $tmp = WpSource::fetch($try, $base, (bool)$c['download']);
            if ($tmp !== '') break;
        }
        if ($tmp === '') { self::note('missing', $orig); self::setMap('media', $orig, ''); return ['', '']; }
        try {
            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            $path = Media::upload(['name' => basename($orig), 'tmp_name' => $tmp, 'size' => (int)filesize($tmp), 'error' => UPLOAD_ERR_OK],
                'media', array_merge(Media::IMAGES, Media::FILES, ['svg']));
            if ($ext === 'svg' && !str_ends_with($path, '.svg')) $path = '';
        } catch (Throwable $e) {
            self::note('media_error', $orig . ': ' . $e->getMessage());
            $path = '';
        } finally {
            if (is_file($tmp)) @unlink($tmp);
        }
        self::setMap('media', $orig, $path);
        self::$counts['media'] = (self::$counts['media'] ?? 0) + ($path !== '' ? 1 : 0);
        return [$path, $alt];
    }

    /** File che servono (foto in evidenza e file nel testo), senza duplicati. */
    private static function neededMedia(array $groups): array
    {
        $need = [];
        foreach ($groups as $g) foreach ($g['items'] as $it) {
            if (!empty($it['meta']['_thumbnail_id'])) $need[] = (string)$it['meta']['_thumbnail_id'];
            WpContent::html($it['content'], function ($ref) use (&$need) { $need[] = (string)$ref; return ['', '']; });
        }
        $files = [];
        foreach ($need as $r) { $f = self::wpFile($r); if ($f !== '') $files[preg_replace('~-\d+x\d+(\.[A-Za-z0-9]+)$~', '$1', $f)] = $r; }
        return $files;
    }

    /* ---------- resoconto e note ---------- */

    private static array $counts = [];
    private static array $notes = [];

    private static function note(string $kind, string $msg): void
    {
        if (count(self::$notes[$kind] ?? []) < 200) self::$notes[$kind][] = $msg;
    }

    /** Prova a secco: cosa verrebbe importato, senza scrivere niente. */
    public static function plan(): array
    {
        $m = self::model(); $c = self::cfg();
        ['groups' => $groups, 'skipped' => $skipped] = self::groups();
        $byType = []; $noMaster = [];
        foreach ($groups as $k => $g) {
            $mi = self::master($g);
            $row = &$byType[$g['type']];
            $row['total'] = ($row['total'] ?? 0) + 1;
            $row['published'] = ($row['published'] ?? 0) + (isset($g['items'][I18n::$master]) && $mi['status'] === 'publish' ? 1 : 0);
            foreach (array_keys($g['items']) as $l) $row['langs'][$l] = ($row['langs'][$l] ?? 0) + 1;
            if (!isset($g['items'][I18n::$master])) $noMaster[] = $mi['title'] . ' (' . $mi['type'] . ', ' . implode('/', array_keys($g['items'])) . ')';
            unset($row);
        }
        WpContent::$unknown = []; WpContent::$dropped = [];
        $files = self::neededMedia($groups);
        $inZip = 0; $missing = [];
        $zip = WpSource::files()['zip'] ?? '';
        $index = [];
        if ($zip && class_exists('ZipArchive')) {
            $z = new ZipArchive();
            if ($z->open($zip) === true) {
                for ($i = 0; $i < $z->numFiles; $i++) { $n = (string)$z->getNameIndex($i); $p = strpos($n, 'uploads/'); if ($p !== false) $index[substr($n, $p + 8)] = true; }
                $z->close();
            }
        }
        foreach (array_keys($files) as $f) {
            if (isset($index[$f]) || isset($index[preg_replace('~(\.[A-Za-z0-9]+)$~', '-scaled$1', $f)])) $inZip++;
            else $missing[] = $f;
        }
        $terms = [];
        foreach ($m['terms'] as $t) if (isset($c['taxonomies'][$t['tax']])) $terms[$t['tax']] = ($terms[$t['tax']] ?? 0) + 1;
        $wpTypes = [];
        foreach ($m['items'] as $it) $wpTypes[$it['type']] = ($wpTypes[$it['type']] ?? 0) + 1;
        arsort($wpTypes);
        return [
            'files' => $m['files'] ?? [], 'base' => $m['base'], 'wp_types' => $wpTypes, 'map' => $c['types'],
            'by_type' => $byType, 'no_master' => $noMaster, 'skipped' => $skipped, 'terms' => $terms,
            'media_needed' => count($files), 'media_in_zip' => $inZip, 'media_missing' => $missing,
            'wpml' => count($m['icl']['posts']) > 0, 'redirection' => count($m['redirection']),
            'unknown' => WpContent::$unknown, 'dropped' => WpContent::$dropped,
        ];
    }

    /* ---------- esecuzione a passi ---------- */

    /** Lavora per al massimo $seconds secondi; restituisce lo stato. Si richiama finché step = done. */
    public static function run(int $seconds = 20): array
    {
        @set_time_limit(max(60, $seconds * 3));
        self::migrate();
        $s = self::state();
        if ($s['started'] === '') $s['started'] = Content::now();
        self::$counts = $s['counts'] ?? [];
        self::$notes = $s['notes'] ?? [];
        $end = microtime(true) + $seconds;
        while ($s['step'] !== 'done' && microtime(true) < $end) {
            $more = match ($s['step']) {
                'scan' => self::stepScan($s),
                'media' => self::stepMedia($s, $end),
                'terms' => self::stepTerms($s),
                'entries' => self::stepEntries($s, $end),
                'links' => self::stepLinks($s, $end),
                'menu' => self::stepMenu($s),
                'redirects' => self::stepRedirects($s),
                default => false,
            };
            if (!$more) {
                $i = array_search($s['step'], self::STEPS, true);
                $s['step'] = self::STEPS[$i + 1] ?? 'done';
                $s['cursor'] = 0;
            }
            $s['counts'] = self::$counts; $s['notes'] = self::$notes;
            self::save($s);
        }
        if (($s['step'] ?? '') !== 'scan') self::saveMediaMap();
        if ($s['step'] === 'done' && empty($s['finished'])) {
            $s['finished'] = Content::now();
            self::save($s);
            WpSource::cleanTmp();
            if (class_exists('Auth')) Auth::log('wp import', (string)(self::$counts['entries'] ?? 0));
            try { Hooks::fire('saved', 'import'); } catch (Throwable $e) { self::note('static', $e->getMessage()); }
        }
        return $s;
    }

    private static function stepScan(array &$s): bool
    {
        self::$model = null;
        WpSource::scan();
        self::model();
        return false;
    }

    private static function stepMedia(array &$s, float $end): bool
    {
        $files = array_values(self::neededMedia(self::groups()['groups']));
        $s['total'] = count($files);
        for ($i = (int)$s['cursor']; $i < count($files); $i++) {
            if (microtime(true) > $end) { $s['cursor'] = $i; return true; }
            self::media($files[$i]);
        }
        return false;
    }

    /** Termini (categorie) → contenuti del tipo scelto, con le traduzioni di WPML. */
    private static function stepTerms(array &$s): bool
    {
        $m = self::model(); $c = self::cfg();
        $groups = []; $termIds = [];
        foreach ($m['terms'] as $t) {
            if (!isset($c['taxonomies'][$t['tax']])) continue;
            $icl = $m['icl']['terms'][$t['id']] ?? null;
            $lang = $icl[1] ?? I18n::$master;
            if (!I18n::valid($lang)) continue;
            $groups[$icl ? 'g' . $icl[0] : 't' . $t['id']][$lang] = $t;
        }
        foreach ($groups as $key => $byLang) {
            $mt = $byLang[I18n::$master] ?? reset($byLang);
            $type = $c['taxonomies'][$mt['tax']]['type'];
            $id = (int)(self::map('termgroup', $key) ?? 0);
            if (!$id || !DB::val('SELECT 1 FROM pp_entries WHERE id = ?', [$id])) {
                $id = DB::insert('pp_entries', ['type' => $type, 'slug' => 'wp-term-' . $mt['id'], 'status' => 'published', 'pub_date' => date('Y-m-d'),
                    'meta' => json_encode(['wp_term' => $mt['id']]), 'created_at' => Content::now(), 'updated_at' => Content::now()]);
                self::$counts['terms'] = (self::$counts['terms'] ?? 0) + 1;
            }
            $texts = $byLang + [I18n::$master => $mt];
            foreach ($texts as $l => $t) Content::saveText('entry', $id, $l, ['title' => $t['name'], 'slug' => $t['slug']]);
            self::setMap('termgroup', $key, (string)$id);
            foreach ($byLang as $t) self::setMap('term', (string)$t['id'], (string)$id);
            $termIds[$id] = $mt;
        }
        // categorie madri (gerarchia di WordPress) e ritocchi del sito (es. la disposizione dell'elenco per categoria)
        foreach ($termIds ?? [] as $id => $mt) {
            $row = DB::one('SELECT meta FROM pp_entries WHERE id = ?', [$id]);
            $meta = Content::meta($row ?: []);
            if ($mt['parent'] !== '') {
                foreach ($m['terms'] as $p) if ($p['tax'] === $mt['tax'] && ($p['slug'] === $mt['parent'] || (string)$p['id'] === $mt['parent'])) {
                    $pid = (int)(self::map('term', (string)$p['id']) ?? 0);
                    if ($pid && $pid !== $id) $meta['parent'] = $pid;
                }
            }
            $meta = Hooks::filter('wp_import_term', $meta, $mt);
            // il sito può dare l'ordine delle categorie (meta "_order"): va nel campo Ordine
            $row = ['meta' => ''];
            if (isset($meta['_order'])) { $row['menu_order'] = (int)$meta['_order']; unset($meta['_order']); }
            $row['meta'] = json_encode($meta, JSON_UNESCAPED_UNICODE);
            DB::update('pp_entries', $row, 'id = ?', [$id]);
        }
        return false;
    }

    private static function stepEntries(array &$s, float $end): bool
    {
        $groups = self::groups()['groups'];
        $keys = array_keys($groups);
        $s['total'] = count($keys);
        for ($i = (int)$s['cursor']; $i < count($keys); $i++) {
            if (microtime(true) > $end) { $s['cursor'] = $i; return true; }
            try { self::entry($keys[$i], $groups[$keys[$i]]); }
            catch (Throwable $e) { self::note('entry_error', $keys[$i] . ': ' . $e->getMessage()); }
        }
        return false;
    }

    /** Un gruppo di traduzioni → un contenuto con i testi nelle sue lingue. */
    private static function entry(string $key, array $g): void
    {
        $c = self::cfg();
        $mi = self::master($g);
        $hasMaster = isset($g['items'][I18n::$master]);
        $mediaFn = fn($ref, $alt) => self::media((string)$ref, (string)$alt);
        $texts = [];
        $base = ['type' => $g['type'], 'meta' => [], 'image' => '', 'skip' => false];
        foreach ($g['items'] as $lang => $it) {
            $thumb = (string)($it['meta']['_thumbnail_id'] ?? '');
            [$img, $alt] = $thumb !== '' ? self::media($thumb) : ['', ''];
            $data = [
                'type' => $g['type'], 'title' => $it['title'], 'excerpt' => trim(strip_tags(html_entity_decode($it['excerpt'], ENT_QUOTES, 'UTF-8'))),
                'body' => WpContent::html($it['content'], $mediaFn), 'slug' => $it['slug'], 'image' => $img, 'image_alt' => $alt,
                'meta' => [], 'extra' => [], 'skip' => false,
            ];
            // categorie → campo del contenuto
            foreach ($it['terms'] as [$tax, $slug]) {
                $tc = $c['taxonomies'][$tax] ?? null;
                if (!$tc) continue;
                foreach (self::model()['terms'] as $t) {
                    if ($t['tax'] !== $tax || $t['slug'] !== $slug) continue;
                    $ref = (int)(self::map('term', (string)$t['id']) ?? 0);
                    if ($ref) { $data['meta'][$tc['field']] ??= $ref; $data['meta']['categories'][] = $ref; }
                }
            }
            $data = Hooks::filter('wp_import_entry', $data, $it, $lang);
            if (!empty($data['skip'])) continue;
            $texts[$lang] = $data;
            if ($it === $mi || $base['image'] === '') {
                $base['type'] = $data['type'];
                if ($data['image'] !== '') $base['image'] = $data['image'];
                $base['meta'] = $data['meta'] + $base['meta'];
            }
        }
        if (!$texts) return;
        if (!isset($texts[I18n::$master])) { $texts[I18n::$master] = reset($texts); $hasMaster = false; }
        $status = $hasMaster && $mi['status'] === 'publish' ? 'published' : 'draft';
        $meta = $base['meta'] + ['wp_id' => $mi['id']];
        foreach ($meta as $k => $v) if (is_array($v)) $meta[$k] = array_values(array_unique($v));
        $id = (int)(self::map('entry', $key) ?? 0);
        $row = ['type' => $base['type'], 'status' => $status, 'pub_date' => substr($mi['date'], 0, 10) ?: date('Y-m-d'), 'menu_order' => $mi['order'],
            'image' => $base['image'], 'updated_at' => Content::now()];
        if ($id && ($old = DB::one('SELECT meta FROM pp_entries WHERE id = ?', [$id]))) {
            DB::update('pp_entries', $row + ['meta' => json_encode($meta + Content::meta($old), JSON_UNESCAPED_UNICODE)], 'id = ?', [$id]);
        } else {
            $id = DB::insert('pp_entries', $row + ['slug' => 'wp-' . $mi['id'], 'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE), 'created_at' => Content::now()]);
            self::$counts['entries'] = (self::$counts['entries'] ?? 0) + 1;
        }
        foreach ($texts as $lang => $t) {
            Content::saveText('entry', $id, $lang, ['title' => $t['title'], 'excerpt' => $t['excerpt'], 'body' => $t['body'],
                'slug' => $t['slug'], 'image_alt' => $t['image_alt'], 'extra' => $t['extra']]);
        }
        if (!$hasMaster) self::note('no_master', $mi['title'] . ' → bozza');
        self::setMap('entry', $key, (string)$id);
        foreach ($g['items'] as $it) self::setMap('post', (string)$it['id'], (string)$id);
    }

    /* ---------- pagine madri, link interni, SEO ---------- */

    /** Indirizzo pubblico vecchio di un contenuto di WordPress: [percorso, query] ('' se non si sa). */
    private static function oldPath(array $it): array
    {
        $u = parse_url($it['link']);
        $q = Redirects::query((string)($u['query'] ?? ''));
        return [(string)($u['path'] ?? '/'), $q];
    }

    private static function stepLinks(array &$s, float $end): bool
    {
        $groups = self::groups()['groups'];
        $keys = array_keys($groups);
        $s['total'] = count($keys);
        $seoRef = ['page' => 'page', 'news' => 'news-item', 'story' => 'stories-item', 'event' => 'events-item'];
        for ($i = (int)$s['cursor']; $i < count($keys); $i++) {
            if (microtime(true) > $end) { $s['cursor'] = $i; return true; }
            $g = $groups[$keys[$i]];
            $id = (int)(self::map('entry', $keys[$i]) ?? 0);
            $row = $id ? DB::one('SELECT * FROM pp_entries WHERE id = ?', [$id]) : null;
            if (!$row) continue;
            // pagina madre: quella del contenuto di WordPress (anche dalla traduzione, WPML non sempre la copia)
            $parent = 0;
            foreach ([self::master($g), ...array_values($g['items'])] as $it) {
                if ($it['parent']) { $parent = (int)(self::map('post', (string)$it['parent']) ?? 0); if ($parent) break; }
            }
            $meta = Content::meta($row);
            if ($parent && $parent !== $id) $meta['parent'] = $parent;
            DB::update('pp_entries', ['meta' => json_encode($meta, JSON_UNESCAPED_UNICODE)], 'id = ?', [$id]);
            foreach ($g['items'] as $lang => $it) {
                $tr = Content::tr('entry', $id, $lang);
                if ($tr && trim((string)$tr['body']) !== '') {
                    $body = self::links((string)$tr['body'], $lang);
                    if ($body !== $tr['body']) DB::update('pp_entry_tr', ['body' => $body], 'entry_id = ? AND lang = ?', [$id, $lang]);
                }
                // SEO di Yoast
                $ref = $seoRef[$row['type']] ?? null;
                $title = self::yoast((string)($it['meta']['_yoast_wpseo_title'] ?? ''), $it['title']);
                $desc = self::yoast((string)($it['meta']['_yoast_wpseo_metadesc'] ?? ''), $it['title']);
                if ($ref && ($title !== '' || $desc !== '')) {
                    DB::upsert('pp_seo', ['ref' => $ref . ':' . $id, 'lang' => $lang, 'title' => mb_substr($title, 0, 255), 'description' => mb_substr($desc, 0, 400), 'updated_at' => Content::now()], ['ref', 'lang']);
                    self::$counts['seo'] = (self::$counts['seo'] ?? 0) + 1;
                }
            }
        }
        return false;
    }

    /** Variabili di Yoast (%%title%% %%sep%% %%sitename%%…) sostituite o tolte. */
    private static function yoast(string $s, string $title): string
    {
        $s = mb_scrub($s, 'UTF-8');
        if (trim($s) === '') return '';
        $site = class_exists('Identity') ? Identity::name() : (string)(PP::$config['site_name'] ?? '');
        $s = strtr($s, ['%%title%%' => $title, '%%sitename%%' => $site, '%%sep%%' => '·', '%%page%%' => '', '%%primary_category%%' => '', '%%excerpt%%' => '']);
        $s = preg_replace('~%%[a-z_]+%%~', '', $s);
        return trim((string)preg_replace('~\s+~u', ' ', trim($s, " ·-|")));
    }

    /** Indirizzo nuovo per un indirizzo del vecchio sito (o '' se non è un contenuto importato). */
    public static function newUrl(string $url, string $lang = ''): string
    {
        static $byPath = null, $skipTo = null;
        $m = self::model();
        if ($byPath === null) {
            $byPath = []; $skipTo = [];
            // contenuti saltati con una destinazione (es. la vecchia pagina "news" → l'archivio delle news): anche i link nei testi portano lì
            foreach ((array)self::cfg()['skip'] as $k => $target) if (!is_int($k)) $skipTo[(string)$k] = (string)$target;
            foreach ($m['items'] as $it) {
                if (!self::map('post', (string)$it['id']) && !isset($skipTo[$it['slug']]) && !isset($skipTo[(string)$it['id']])) continue;
                [$p, $q] = self::oldPath($it);
                $byPath[rtrim($p, '/') . '/' . ($q !== '' ? '?' . $q : '')] = $it['id'];
            }
        }
        $u = parse_url(html_entity_decode($url, ENT_QUOTES, 'UTF-8'));
        if ($u === false) return '';
        $host = parse_url($m['base'], PHP_URL_HOST);
        if (!empty($u['host']) && preg_replace('~^www\.~', '', $u['host']) !== preg_replace('~^www\.~', '', (string)$host)) return '';
        $path = (string)($u['path'] ?? '/');
        if (str_contains($path, '/wp-content/') || str_contains($path, '/wp-admin')) return '';
        parse_str((string)($u['query'] ?? ''), $qa);
        $wpId = (int)($qa['page_id'] ?? $qa['p'] ?? 0);
        if (!$wpId) {
            $q = Redirects::query((string)($u['query'] ?? ''));
            $wpId = $byPath[rtrim($path, '/') . '/' . ($q !== '' ? '?' . $q : '')] ?? $byPath[rtrim($path, '/') . '/'] ?? 0;
        }
        if (!$wpId) return '';
        $entry = (int)(self::map('post', (string)$wpId) ?? 0);
        $l = I18n::valid((string)($qa['lang'] ?? '')) ? (string)$qa['lang'] : ($lang ?: self::langOf($wpId));
        if (!$entry && isset($m['items'][$wpId])) {
            $t = $skipTo[$m['items'][$wpId]['slug']] ?? $skipTo[(string)$wpId] ?? '';
            return $t !== '' ? self::target($t, $l) : '';
        }
        $e = $entry ? DB::one('SELECT * FROM pp_entries WHERE id = ?', [$entry]) : null;
        if (!$e) return '';
        $href = PP::entryUrl(Content::localize('entry', $e, $l), $l);
        return $href !== '' ? $href . (!empty($u['fragment']) ? '#' . $u['fragment'] : '') : '';
    }

    private static function links(string $html, string $lang): string
    {
        $base = preg_quote(preg_replace('~^https?://(www\.)?~', '', self::model()['base']), '~');
        return preg_replace_callback('~(<a\b[^>]*\shref=")((?:https?://(?:www\.)?' . $base . ')?/[^"]*|\?[^"]*)(")~i', function ($m) use ($lang) {
            $new = self::newUrl($m[2], $lang);
            return $new !== '' ? $m[1] . e($new) . $m[3] : $m[0];
        }, $html);
    }

    /* ---------- menu ---------- */

    private static function stepMenu(array &$s): bool
    {
        $m = self::model(); $c = self::cfg();
        // menu (termini nav_menu) e le loro voci
        $menus = [];
        foreach ($m['items'] as $it) {
            if ($it['type'] !== 'nav_menu_item') continue;
            foreach ($it['terms'] as [$tax, $slug]) if ($tax === 'nav_menu') $menus[$slug][] = $it;
        }
        if (!$menus) return false;
        $pick = (string)$c['menu'];
        if ($pick === '' || !isset($menus[$pick])) {
            uasort($menus, fn($a, $b) => count($b) <=> count($a));
            foreach ($menus as $slug => $items) { if (self::langOf((int)$items[0]['id']) === I18n::$master) { $pick = $slug; break; } }
            if ($pick === '') $pick = (string)array_key_first($menus);
        }
        $items = $menus[$pick];
        usort($items, fn($a, $b) => $a['order'] <=> $b['order']);
        $depth = [];
        foreach ($items as $it) $depth[$it['id']] = $it['meta']['_menu_item_menu_item_parent'] ?? '0';
        $rows = [];
        foreach ($items as $it) {
            $mm = $it['meta'];
            $level = 0; $p = (int)($mm['_menu_item_menu_item_parent'] ?? 0);
            while ($p && $level < 5) { $level++; $p = (int)($depth[$p] ?? 0); }
            $labels = [];
            // etichette nelle altre lingue: le voci tradotte dello stesso gruppo WPML
            $trid = $m['icl']['posts'][$it['id']][0] ?? null;
            foreach ($m['items'] as $o) {
                if ($o['type'] === 'nav_menu_item' && $trid !== null && ($m['icl']['posts'][$o['id']][0] ?? null) === $trid && trim($o['title']) !== '') $labels[self::langOf($o['id'])] = trim($o['title']);
            }
            if (trim($it['title']) !== '') $labels[I18n::$master] = trim($it['title']);
            // frecce e simboli messi a mano nelle etichette (il tema disegna già i sottomenu)
            $labels = array_filter(array_map(fn($l) => trim(preg_replace('~[\x{25B2}-\x{25BF}\x{2190}-\x{21FF}»›]+~u', '', html_entity_decode($l, ENT_QUOTES, 'UTF-8'))), $labels), 'strlen');
            $labels = array_filter($labels, fn($l) => I18n::valid($l), ARRAY_FILTER_USE_KEY);
            $type = (string)($mm['_menu_item_type'] ?? '');
            $obj = (string)($mm['_menu_item_object_id'] ?? '');
            $row = ['kind' => 'url', 'ref' => '', 'url' => '', 'label' => $labels, 'parent' => $level > 0 ? 1 : 0, 'new' => 0];
            if ($type === 'post_type') {
                $entry = (int)(self::map('post', $obj) ?? 0);
                $e = $entry ? DB::one('SELECT * FROM pp_entries WHERE id = ?', [$entry]) : null;
                if (!$e) { self::note('menu', 'voce saltata: ' . ($it['title'] ?: '#' . $obj)); continue; }
                if ($e['slug'] === 'home') { $row['kind'] = 'section'; $row['ref'] = 'home'; }
                elseif ($e['type'] === 'page') { $row['kind'] = 'page'; $row['ref'] = $entry; }
                else $row['url'] = PP::entryUrl(Content::localize('entry', $e, I18n::$master), I18n::$master);
            } elseif ($type === 'custom') {
                $url = (string)($mm['_menu_item_url'] ?? '');
                $new = self::newUrl($url);
                $row['url'] = $new !== '' ? $new : $url;
                if (in_array(rtrim(preg_replace('~^https?://(www\.)?~', '', $url), '/'), [preg_replace('~^https?://(www\.)?~', '', $m['base']), ''], true)) { $row['kind'] = 'section'; $row['ref'] = 'home'; }
            } else {
                $row = Hooks::filter('wp_import_menu_item', null, $it, $labels, $level) ?? null;
                if (!$row) { self::note('menu', 'voce saltata (' . $type . '): ' . $it['title']); continue; }
            }
            if ($row['kind'] === 'url' && $row['url'] === '') continue;
            $rows[] = $row;
        }
        if ($rows) {
            $all = Settings::get('menus', []);
            $all['main'] = $rows;
            Settings::set('menus', $all);
            self::$counts['menu'] = count($rows);
        }
        return false;
    }

    /* ---------- redirect ---------- */

    private static function stepRedirects(array &$s): bool
    {
        $m = self::model();
        $n = 0;
        foreach ($m['items'] as $it) {
            if ($it['status'] !== 'publish' || !self::map('post', (string)$it['id'])) continue;
            $new = self::newUrl($it['link']);
            if ($new === '') continue;
            [$p, $q] = self::oldPath($it);
            $to = self::rel($new);
            Redirects::add($p . ($q !== '' ? '?' . $q : ''), $to);
            // anche l'indirizzo breve di WordPress (/?p=12, /?page_id=12)
            Redirects::add('/?' . ($it['type'] === 'page' ? 'page_id' : 'p') . '=' . $it['id'], $to);
            $n++;
        }
        // contenuti saltati con una destinazione: il vecchio indirizzo porta lì, in ogni lingua
        foreach ((array)self::cfg()['skip'] as $k => $target) {
            if (is_int($k)) continue;
            foreach ($m['items'] as $it) {
                if (($it['slug'] !== (string)$k && (string)$it['id'] !== (string)$k) || $it['status'] !== 'publish') continue;
                $l = self::langOf($it['id']);
                if (!I18n::valid($l)) continue;
                $to = self::target((string)$target, $l);
                if ($to === '') continue;
                [$p, $q] = self::oldPath($it);
                Redirects::add($p . ($q !== '' ? '?' . $q : ''), self::rel($to));
                Redirects::add('/?page_id=' . $it['id'], self::rel($to));
                $n++;
            }
        }
        // categorie: gli archivi del vecchio sito portano dove dice il sito, col filtro (['from' => '/archivio/x/', 'to' => '/it/portfolio/'])
        foreach ($m['terms'] as $t) {
            $r = Hooks::filter('wp_import_term_url', null, $t + ['entry' => (int)(self::map('term', (string)$t['id']) ?? 0)]);
            if (is_array($r) && !empty($r['from']) && !empty($r['to'])) { Redirects::add($r['from'], self::rel($r['to'])); $n++; }
        }
        // redirect del plugin Redirection: si seguono fino all'indirizzo nuovo
        foreach ($m['redirection'] as $r) {
            $to = self::newUrl($r['to']);
            if ($to === '' && preg_match('~^https?://~', $r['to']) && parse_url($r['to'], PHP_URL_HOST) !== parse_url($m['base'], PHP_URL_HOST)) $to = $r['to'];
            if ($to === '') continue;
            Redirects::add($r['from'], preg_match('~^https?://~', $to) ? $to : self::rel($to), in_array($r['code'], [301, 302], true) ? $r['code'] : 301);
            $n++;
        }
        self::$counts['redirects'] = $n;
        return false;
    }

    /** Destinazione di un contenuto saltato: 'home', '/percorso/', 'category:slug' (pagina della categoria nelle storie) o la chiave di una sezione. */
    private static function target(string $target, string $lang): string
    {
        if ($target === 'home') return PP::link('', $lang);
        if (str_starts_with($target, '/')) return $target;
        if (str_starts_with($target, 'category:')) {
            $slug = substr($target, 9);
            foreach (self::model()['terms'] as $t) {
                if ($t['slug'] !== $slug) continue;
                $id = (int)(self::map('term', (string)$t['id']) ?? 0);
                if ($id) return PP::link(PP::section('stories', $lang) . '/' . Content::slugFor($id, $lang), $lang);
            }
            return '';
        }
        return PP::link(PP::section($target, $lang), $lang);
    }

    /** Indirizzo senza la base del sito (i redirect si salvano così). */
    private static function rel(string $url): string
    {
        $b = PP::base();
        return $b !== '' && str_starts_with($url, $b . '/') ? substr($url, strlen($b)) : $url;
    }

    /* ---------- annulla ---------- */

    /** Toglie tutto quello che l'importazione ha creato (contenuti, file, collegamenti). I redirect e il menu restano. */
    public static function undo(): int
    {
        self::migrate();
        $n = 0;
        foreach (DB::all("SELECT ref FROM pp_wp_map WHERE kind IN ('entry', 'termgroup')") as $r) {
            if ((int)$r['ref'] && DB::val('SELECT 1 FROM pp_entries WHERE id = ?', [(int)$r['ref']])) { Content::deleteItem('entry', (int)$r['ref']); $n++; }
        }
        foreach (DB::all("SELECT ref FROM pp_wp_map WHERE kind = 'media' AND ref <> ''") as $r) {
            try { Media::delete((string)$r['ref']); } catch (Throwable $e) {}
        }
        DB::run('DELETE FROM pp_wp_map');
        @unlink(self::stateFile());
        @unlink(self::mediaMapFile());   // le foto importate non ci sono più
        return $n;
    }
}
