<?php
/**
 * Lettura dei file di un sito WordPress messi in data/wp-import/:
 *  - l'esportazione WXR (Strumenti → Esporta → Tutti i contenuti), obbligatoria;
 *  - il dump SQL del database (phpMyAdmin), facoltativo: serve solo per le coppie di traduzioni di WPML,
 *    le categorie tradotte e i redirect del plugin Redirection. Si leggono SOLO quelle tabelle: utenti e opzioni mai;
 *  - uno zip della cartella wp-content/uploads (anche solo gli originali), facoltativo: senza, i file si scaricano dal sito vecchio.
 * Il risultato (modello) si salva in data/wp-import/model.ser per i passaggi successivi.
 */
final class WpSource
{
    /** Tabelle che si possono leggere dal dump (suffisso, il prefisso dipende dal sito). */
    private const TABLES = ['icl_translations', 'term_taxonomy', 'redirection_items'];

    public static function dir(): string
    {
        $d = PP_ROOT . '/data/wp-import';
        if (!is_dir($d)) mkdir($d, 0775, true);
        if (!is_file($d . '/.htaccess')) file_put_contents($d . '/.htaccess', "Require all denied\n");
        return $d;
    }

    /** File trovati nella cartella: ['xml' => …, 'sql' => …, 'zip' => …] (il più recente di ogni tipo). */
    public static function files(): array
    {
        $out = [];
        foreach (['xml', 'sql', 'zip'] as $ext) {
            $all = glob(self::dir() . '/*.' . $ext) ?: [];
            usort($all, fn($a, $b) => filemtime($b) <=> filemtime($a));
            if ($all) $out[$ext] = $all[0];
        }
        return $out;
    }

    public static function modelFile(): string { return self::dir() . '/model.ser'; }

    public static function model(): ?array
    {
        $f = self::modelFile();
        if (!is_file($f)) return null;
        $m = @unserialize((string)file_get_contents($f), ['allowed_classes' => false]);
        return is_array($m) ? $m : null;
    }

    /** Legge i file e salva il modello. */
    public static function scan(): array
    {
        $f = self::files();
        if (empty($f['xml'])) throw new RuntimeException('Manca il file .xml dell’esportazione di WordPress in data/wp-import/.');
        $m = self::readWxr($f['xml']);
        $m['files'] = array_map('basename', $f);
        $m['icl'] = ['posts' => [], 'terms' => []];
        $m['redirection'] = [];
        if (!empty($f['sql'])) {
            $rows = self::readSql($f['sql']);
            $tt = [];
            foreach ($rows['term_taxonomy'] ?? [] as $r) $tt[(int)$r['term_taxonomy_id']] = (int)$r['term_id'];
            foreach ($rows['icl_translations'] ?? [] as $r) {
                $type = (string)$r['element_type'];
                $lang = (string)$r['language_code'];
                $trid = (int)$r['trid'];
                if ($lang === '' || !$trid) continue;
                if (str_starts_with($type, 'post_')) $m['icl']['posts'][(int)$r['element_id']] = [$trid, $lang];
                elseif (str_starts_with($type, 'tax_') && isset($tt[(int)$r['element_id']])) $m['icl']['terms'][$tt[(int)$r['element_id']]] = [$trid, $lang];
            }
            foreach ($rows['redirection_items'] ?? [] as $r) {
                if (($r['status'] ?? 'enabled') !== 'enabled' || (int)($r['regex'] ?? 0) === 1 || ($r['action_type'] ?? 'url') !== 'url') continue;
                $to = (string)($r['action_data'] ?? '');
                if (str_starts_with($to, 'a:') || str_starts_with($to, '{')) continue;   // regole condizionali: non si portano
                $m['redirection'][] = ['from' => (string)$r['url'], 'to' => $to, 'code' => (int)($r['action_code'] ?? 301)];
            }
        }
        $m['scanned_at'] = Content::now();
        file_put_contents(self::modelFile(), serialize($m));
        return $m;
    }

    /* ---------- WXR ---------- */

    private static function readWxr(string $file): array
    {
        $NS_WP = 'http://wordpress.org/export/1.2/';
        $NS_C = 'http://purl.org/rss/1.0/modules/content/';
        $NS_E = 'http://wordpress.org/export/1.2/excerpt/';
        $m = ['base' => '', 'items' => [], 'media' => [], 'terms' => []];
        $x = new XMLReader();
        if (!$x->open($file, null, LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE)) throw new RuntimeException('Il file .xml non si apre.');
        while ($x->read()) {
            if ($x->nodeType !== XMLReader::ELEMENT) continue;
            if ($x->name === 'wp:base_site_url' && $m['base'] === '') { $m['base'] = rtrim(trim($x->readString()), '/'); continue; }
            if ($x->name === 'wp:term' || $x->name === 'wp:category') {
                $s = @simplexml_load_string($x->readOuterXml(), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
                if ($s === false) continue;
                $w = $s->children($NS_WP);
                $tax = $x->name === 'wp:category' ? 'category' : (string)$w->term_taxonomy;
                $id = (int)$w->term_id;
                $m['terms'][$id] = ['id' => $id, 'tax' => $tax, 'slug' => (string)($w->term_slug ?: $w->category_nicename),
                    'name' => html_entity_decode((string)($w->term_name ?: $w->cat_name), ENT_QUOTES, 'UTF-8'),
                    'parent' => (string)($w->term_parent ?: $w->category_parent)];
                continue;
            }
            if ($x->name !== 'item') continue;
            $s = @simplexml_load_string($x->readOuterXml(), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET | LIBXML_PARSEHUGE);
            if ($s === false) continue;
            $w = $s->children($NS_WP);
            $type = (string)$w->post_type;
            $id = (int)$w->post_id;
            $meta = [];
            foreach ($w->postmeta as $pm) {
                $k = (string)$pm->meta_key; $v = (string)$pm->meta_value;
                if (strlen($v) > 4000 || preg_match('~^_(wpb|vc|wpml|alp|oembed|edit_|dp_|theme_|mk_|menu_item_classes|menu_item_xfn)~', $k)) continue;
                $meta[$k] = $v;
            }
            if ($type === 'attachment') {
                $url = (string)$w->attachment_url;
                $m['media'][$id] = ['id' => $id, 'url' => $url, 'file' => (string)($meta['_wp_attached_file'] ?? self::uploadPath($url)),
                    'alt' => trim((string)($meta['_wp_attachment_image_alt'] ?? '')), 'title' => (string)$s->title];
                continue;
            }
            $terms = [];
            foreach ($s->category as $c) $terms[] = [(string)$c['domain'], (string)$c['nicename'], (string)$c];
            $m['items'][$id] = [
                'id' => $id, 'type' => $type, 'status' => (string)$w->status, 'date' => (string)$w->post_date,
                'title' => html_entity_decode((string)$s->title, ENT_QUOTES, 'UTF-8'), 'slug' => rawurldecode((string)$w->post_name),
                'parent' => (int)$w->post_parent, 'order' => (int)$w->menu_order, 'link' => (string)$s->link,
                'content' => (string)$s->children($NS_C)->encoded, 'excerpt' => (string)$s->children($NS_E)->encoded,
                'meta' => $meta, 'terms' => $terms,
            ];
        }
        $x->close();
        return $m;
    }

    /** "https://sito/wp-content/uploads/2017/08/foto-300x200.jpg" → "2017/08/foto-300x200.jpg" ('' se non è un file caricato). */
    public static function uploadPath(string $url): string
    {
        $p = (string)parse_url(html_entity_decode($url, ENT_QUOTES, 'UTF-8'), PHP_URL_PATH);
        $i = strpos($p, '/wp-content/uploads/');
        return $i === false ? '' : rawurldecode(substr($p, $i + 20));
    }

    /* ---------- dump SQL ---------- */

    /** Righe delle sole tabelle ammesse: ['icl_translations' => [[col => val], …], …]. Legge il file una riga alla volta. */
    private static function readSql(string $file): array
    {
        $out = [];
        $h = fopen($file, 'rb');
        if (!$h) return $out;
        $table = null; $cols = []; $buf = '';
        while (($line = fgets($h)) !== false) {
            if ($table === null) {
                if (!preg_match('~^INSERT INTO `([A-Za-z0-9_]+)`\s*(\(([^)]*)\))?\s*VALUES\s*(.*)$~s', $line, $mm)) continue;
                $suffix = null;
                foreach (self::TABLES as $t) if (str_ends_with($mm[1], '_' . $t) || $mm[1] === $t) $suffix = $t;
                if ($suffix === null) continue;
                $table = $suffix;
                $cols = $mm[3] !== '' ? array_map(fn($c) => trim($c, " `\t"), explode(',', $mm[3])) : [];
                $buf = $mm[4];
            } else {
                $buf .= $line;
            }
            if (!preg_match('~\);\s*$~', $buf)) continue;   // l'istruzione continua sulla riga dopo
            foreach (self::tuples($buf) as $vals) {
                if (!$cols) continue;   // senza elenco delle colonne non si sa cosa sia: si salta
                $out[$table][] = array_combine(array_slice($cols, 0, count($vals)), array_slice($vals, 0, count($cols)));
            }
            $table = null; $buf = '';
        }
        fclose($h);
        return $out;
    }

    /** "(1,'a\'b',NULL),(2,…);" → [[1,"a'b",null],[2,…]] */
    private static function tuples(string $s): array
    {
        $rows = []; $row = null; $val = ''; $inStr = false; $had = false;
        $n = strlen($s);
        for ($i = 0; $i < $n; $i++) {
            $ch = $s[$i];
            if ($inStr) {
                if ($ch === '\\' && $i + 1 < $n) {
                    $nx = $s[++$i];
                    $val .= match ($nx) { 'n' => "\n", 'r' => "\r", 't' => "\t", '0' => "\0", default => $nx };
                } elseif ($ch === "'") {
                    if ($i + 1 < $n && $s[$i + 1] === "'") { $val .= "'"; $i++; }
                    else $inStr = false;
                } else $val .= $ch;
                continue;
            }
            if ($ch === "'") { $inStr = true; $had = true; $val = ''; continue; }
            if ($ch === '(' && $row === null) { $row = []; $val = ''; $had = false; continue; }
            if ($row === null) continue;
            if ($ch === ',' || $ch === ')') {
                $v = trim($val);
                $row[] = (!$had && strtoupper($v) === 'NULL') ? null : ($had ? $val : $v);
                $val = ''; $had = false;
                if ($ch === ')') { $rows[] = $row; $row = null; }
                continue;
            }
            $val .= $ch;
        }
        return $rows;
    }

    /* ---------- file caricati ---------- */

    /**
     * Copia locale di un file del vecchio sito (percorso dentro uploads/), dallo zip o scaricata dal sito vecchio.
     * Restituisce il percorso di un file temporaneo, o '' se non si trova.
     */
    public static function fetch(string $rel, string $base, bool $download): string
    {
        $rel = ltrim($rel, '/');
        if ($rel === '' || str_contains($rel, '..')) return '';
        $tmp = self::dir() . '/tmp';
        if (!is_dir($tmp)) mkdir($tmp, 0775, true);
        $dest = $tmp . '/' . basename($rel);
        $files = self::files();
        if (!empty($files['zip']) && class_exists('ZipArchive')) {
            static $zip = null, $index = null;
            if ($zip === null) {
                $zip = new ZipArchive();
                if ($zip->open($files['zip']) !== true) $zip = false;
                $index = [];
                if ($zip) for ($i = 0; $i < $zip->numFiles; $i++) {
                    $n = (string)$zip->getNameIndex($i);
                    $p = strpos($n, 'uploads/');
                    if ($p !== false) $index[substr($n, $p + 8)] = $i;
                }
            }
            if ($zip && isset($index[$rel])) {
                $data = $zip->getFromIndex($index[$rel]);
                if ($data !== false) { file_put_contents($dest, $data); return $dest; }
            }
        }
        if (!$download || $base === '' || !preg_match('~^https?://~', $base)) return '';
        // solo dal sito vecchio, piano: una richiesta ogni mezzo secondo
        $url = $base . '/wp-content/uploads/' . implode('/', array_map('rawurlencode', explode('/', $rel)));
        usleep(500000);
        $ctx = stream_context_create(['http' => ['timeout' => 30, 'follow_location' => 0, 'user_agent' => 'PhalbPress-import/' . PP::VERSION], 'ssl' => ['verify_peer' => true]]);
        $data = @file_get_contents($url, false, $ctx);
        if ($data === false || $data === '') return '';
        file_put_contents($dest, $data);
        return $dest;
    }

    /** Toglie i file temporanei. */
    public static function cleanTmp(): void
    {
        foreach (glob(self::dir() . '/tmp/*') ?: [] as $f) @unlink($f);
    }
}
