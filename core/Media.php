<?php
/**
 * Caricamento file in /uploads.
 * - nomi file ripuliti (minuscole, niente accenti/spazi/simboli, mai sovrascritti)
 * - immagini: raddrizzate secondo l'EXIF, ridotte, ricompresse (WebP o formato originale), metadati GPS eliminati,
 *   più una versione da 960 px per gli schermi piccoli
 * - SVG ripuliti da script e dati superflui
 * - PDF: controllo di base dell'accessibilità (tag, lingua, titolo)
 */
final class Media
{
    public const IMAGES = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    public const FILES = ['pdf', 'doc', 'docx', 'odt', 'ppt', 'pptx', 'xls', 'xlsx', 'ods', 'zip', 'mp4', 'mp3', 'txt', 'csv'];

    /** Ultimo messaggio sul trattamento dell'immagine (per mostrarlo in gestione). */
    public static string $note = '';
    /** Se impostato, l'immagine elaborata viene salvata in questo formato (serve a "Sostituisci"). */
    private static ?string $forceExt = null;

    public static function upload(array $f, string $sub = 'media', ?array $allow = null): string
    {
        self::$note = '';
        if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException(in_array($f['error'] ?? 0, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                ? 'File troppo grande per il server' : 'Caricamento non riuscito');
        }
        $max = (int)(PP::$config['upload_max_mb'] ?? 30) * 1024 * 1024;
        if ($f['size'] > $max) throw new RuntimeException('File oltre il limite di ' . (PP::$config['upload_max_mb'] ?? 30) . ' MB');
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if ($ext === 'jpeg') $ext = 'jpg';
        $allow = $allow ?: array_merge(self::IMAGES, self::FILES);
        if (!in_array($ext, $allow, true)) throw new RuntimeException('Tipo di file non ammesso: .' . $ext);

        $mime = class_exists('finfo') ? (string)(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) : '';
        $isImg = in_array($ext, self::IMAGES, true);
        if ($ext === 'svg') {
            if (!preg_match('~svg|xml|text~', $mime)) throw new RuntimeException('Il file non è un SVG valido');
        } elseif ($isImg && $mime && !str_starts_with($mime, 'image/')) {
            throw new RuntimeException('Il file non è un\'immagine valida');
        } elseif (preg_match('~(php|x-httpd|javascript|html)~i', $mime)) {
            throw new RuntimeException('Contenuto del file non ammesso');
        }
        if ($ext === 'pdf' && $mime && $mime !== 'application/pdf') throw new RuntimeException('Il file non è un PDF valido');

        $dir = 'uploads/' . $sub . '/' . date('Y/m');
        $abs = PP_ROOT . '/' . $dir;
        if (!is_dir($abs)) mkdir($abs, 0775, true);
        $base = self::filename(pathinfo($f['name'], PATHINFO_FILENAME));

        if ($ext === 'svg') {
            $svg = self::cleanSvg((string)file_get_contents($f['tmp_name']));
            $name = self::unique($abs, $base, 'svg');
            file_put_contents($abs . '/' . $name, $svg);
            self::$note = 'SVG ripulito: ' . self::human((int)$f['size']) . ' → ' . self::human(strlen($svg)) . '.';
            return $dir . '/' . $name;
        }

        if ($isImg && $ext !== 'gif' && function_exists('imagecreatetruecolor')) {
            return $dir . '/' . self::processImage($f['tmp_name'], $abs, $base, $ext, (int)$f['size']);
        }

        $name = self::unique($abs, $base, $ext);
        if (!move_uploaded_file($f['tmp_name'], $abs . '/' . $name) && !rename($f['tmp_name'], $abs . '/' . $name)) {
            throw new RuntimeException('Impossibile salvare il file');
        }
        @chmod($abs . '/' . $name, 0644);
        if ($ext === 'pdf') {
            $p = self::pdfInfo($abs . '/' . $name);
            if (!$p['tagged']) self::$note = 'Attenzione: il PDF sembra senza tag di accessibilità. Esportalo da InDesign/Word con “PDF con tag” attivo.';
            elseif (!$p['lang']) self::$note = 'PDF con tag, ma senza lingua del documento impostata.';
        }
        return $dir . '/' . $name;
    }

    /* ---------- immagini ---------- */

    private static function processImage(string $tmp, string $absDir, string $base, string $ext, int $origSize): string
    {
        $info = @getimagesize($tmp);
        if (!$info) throw new RuntimeException('Immagine non leggibile');
        $src = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($tmp),
            IMAGETYPE_PNG => @imagecreatefrompng($tmp),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false,
            default => false,
        };
        if (!$src) throw new RuntimeException('Formato immagine non supportato dal server');

        // raddrizza le foto del telefono
        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($tmp);
            $o = (int)($exif['Orientation'] ?? 1);
            $rot = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
            if ($rot) { $r = imagerotate($src, $rot, 0); if ($r) { imagedestroy($src); $src = $r; } }
        }
        $w = imagesx($src); $h = imagesy($src);
        $hasAlpha = $info[2] === IMAGETYPE_PNG && self::hasAlpha($src);

        $format = PP::$config['image_format'] ?? 'webp';
        $outExt = self::$forceExt ?: (($format === 'webp' && function_exists('imagewebp')) ? 'webp' : ($ext === 'png' ? 'png' : ($ext === 'webp' ? 'webp' : 'jpg')));
        $quality = (int)(PP::$config['image_quality'] ?? 82);
        $maxPx = (int)(PP::$config['image_max_px'] ?? 2400);

        $name = self::unique($absDir, $base, $outExt);
        [$fw, $fh] = self::save($src, $w, $h, $maxPx, $absDir . '/' . $name, $outExt, $quality, $hasAlpha);
        if (max($fw, $fh) > 960) {
            $small = preg_replace('~\.' . $outExt . '$~', '-960.' . $outExt, $name);
            self::save($src, $w, $h, 960, $absDir . '/' . $small, $outExt, $quality, $hasAlpha);
        }
        imagedestroy($src);
        $newSize = (int)filesize($absDir . '/' . $name);
        self::$note = sprintf('Immagine ottimizzata: %d×%d %s → %d×%d %s (%s).', $w, $h, self::human($origSize), $fw, $fh, self::human($newSize), strtoupper($outExt));
        return $name;
    }

    private static function save($src, int $w, int $h, int $maxPx, string $file, string $ext, int $q, bool $alpha): array
    {
        $r = max($w, $h) > $maxPx ? $maxPx / max($w, $h) : 1;
        $nw = (int)round($w * $r); $nh = (int)round($h * $r);
        $dst = imagecreatetruecolor($nw, $nh);
        if ($alpha || $ext === 'png' || $ext === 'webp') { imagealphablending($dst, false); imagesavealpha($dst, true); }
        if (!$alpha && $ext === 'jpg') imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        match ($ext) {
            'webp' => imagewebp($dst, $file, $q),
            'png' => imagepng($dst, $file, 8),
            default => (function () use ($dst, $file, $q) { imageinterlace($dst, true); imagejpeg($dst, $file, $q); })(),
        };
        imagedestroy($dst);
        @chmod($file, 0644);
        return [$nw, $nh];
    }

    private static function hasAlpha($img): bool
    {
        $w = imagesx($img); $h = imagesy($img);
        $step = max(1, (int)(min($w, $h) / 40));
        for ($x = 0; $x < $w; $x += $step) for ($y = 0; $y < $h; $y += $step) {
            if (((imagecolorat($img, $x, $y) >> 24) & 0x7F) > 0) return true;
        }
        return false;
    }

    /* ---------- SVG ---------- */

    /** Toglie script, eventi, link esterni, dati privati di Illustrator e metadati. */
    public static function cleanSvg(string $svg): string
    {
        if (!class_exists('DOMDocument')) throw new RuntimeException('Il server non può ripulire gli SVG');
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $ok = $doc->loadXML($svg, LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_clear_errors();
        if (!$ok || !$doc->documentElement || strtolower($doc->documentElement->localName) !== 'svg') throw new RuntimeException('SVG non valido');
        $allowed = ['svg', 'g', 'path', 'circle', 'ellipse', 'rect', 'line', 'polyline', 'polygon', 'defs', 'style', 'lineargradient', 'radialgradient', 'stop', 'clippath', 'mask', 'use', 'symbol', 'title', 'desc', 'text', 'tspan'];
        $xp = new DOMXPath($doc);
        foreach (iterator_to_array($xp->query('//node()')) as $n) {
            if ($n instanceof DOMComment || $n instanceof DOMProcessingInstruction) { $n->parentNode?->removeChild($n); continue; }
            if (!$n instanceof DOMElement) continue;
            if (!in_array(strtolower($n->localName), $allowed, true) || ($n->namespaceURI && $n->namespaceURI !== 'http://www.w3.org/2000/svg')) {
                $n->parentNode?->removeChild($n);
                continue;
            }
            foreach (iterator_to_array($n->attributes) as $a) {
                $name = strtolower($a->nodeName);
                $val = strtolower(trim($a->nodeValue));
                if (str_starts_with($name, 'on') || str_contains($val, 'javascript:') || (str_contains($name, 'href') && !str_starts_with($val, '#'))
                    || str_starts_with($name, 'xmlns:') || (str_contains($name, ':') && !in_array($name, ['xlink:href', 'xml:space'], true))) {
                    $n->removeAttributeNode($a);
                }
            }
            if (strtolower($n->localName) === 'style') $n->textContent = preg_replace('~@import[^;]+;|url\s*\([^)]*\)|expression\s*\(~i', '', $n->textContent);
        }
        // le classi del <style> diventano attributi: così l'SVG si può inserire nella pagina senza conflitti
        $rules = [];
        foreach (iterator_to_array($xp->query('//*[local-name()="style"]')) as $st) {
            preg_match_all('~((?:\.[\w-]+\s*,?\s*)+)\{([^}]*)\}~', $st->textContent, $mm, PREG_SET_ORDER);
            foreach ($mm as $m) foreach (preg_split('~\s*,\s*~', trim($m[1])) as $sel) $rules[ltrim(trim($sel), '.')] = trim(preg_replace('~\s+~', ' ', $m[2]));
            if ($mm) $st->parentNode->removeChild($st);
        }
        if ($rules) foreach (iterator_to_array($xp->query('//*[@class]')) as $el) {
            $css = [];
            foreach (preg_split('~\s+~', $el->getAttribute('class')) as $c) if (isset($rules[$c])) $css[] = rtrim($rules[$c], '; ');
            if ($css) { $el->setAttribute('style', implode('; ', $css)); $el->removeAttribute('class'); }
        }
        foreach (iterator_to_array($xp->query('//*[local-name()="defs"][not(*)]')) as $d) $d->parentNode->removeChild($d);
        $root = $doc->documentElement;
        $root->removeAttribute('id');
        $out = $doc->saveXML($root);
        $out = preg_replace('~\sxmlns:(?!xlink)[a-z0-9]+="[^"]*"~i', '', $out);
        return preg_replace('~>\s+<~', '><', $out);
    }

    /* ---------- PDF ---------- */

    /** Controllo euristico: PDF con tag (struttura), lingua e titolo. */
    public static function pdfInfo(string $file): array
    {
        $raw = (string)@file_get_contents($file, false, null, 0, 6 * 1024 * 1024);
        return [
            'tagged' => str_contains($raw, '/StructTreeRoot') || (bool)preg_match('~/Marked\s*true~', $raw),
            'lang' => (bool)preg_match('~/Lang\s*\(~', $raw),
            'title' => (bool)preg_match('~/Title\s*[(<]~', $raw),
        ];
    }

    /* ---------- nomi file ---------- */

    private const MAP = [
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c', 'č' => 'c', 'ć' => 'c',
        'đ' => 'd', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ñ' => 'n',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o', 'ø' => 'o', 'œ' => 'oe', 'š' => 's', 'ß' => 'ss', 'ù' => 'u',
        'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'ÿ' => 'y', 'ž' => 'z', 'ğ' => 'g', 'ı' => 'i', 'ş' => 's', 'ł' => 'l',
        'ő' => 'o', 'ű' => 'u', 'ř' => 'r', 'ě' => 'e', 'ů' => 'u', 'ť' => 't', 'ď' => 'd', 'ň' => 'n', 'ą' => 'a', 'ę' => 'e',
        'ś' => 's', 'ź' => 'z', 'ż' => 'z', 'ń' => 'n', '&' => '-e-', '@' => '-at-',
    ];

    /** Slug per indirizzi e nomi: "Piano d'Azione – Città (v2).PDF" → "piano-d-azione-citta-v2" */
    public static function slug(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        if (function_exists('transliterator_transliterate')) {
            $t = transliterator_transliterate('Any-Latin; Latin-ASCII', $s);
            if (is_string($t)) $s = $t;
        }
        $s = strtr($s, self::MAP);
        $s = preg_replace('~[^a-z0-9]+~', '-', $s);
        return trim(substr((string)$s, 0, 80), '-');
    }

    /** Nome file sicuro e leggibile (senza estensione). */
    public static function filename(string $s): string
    {
        $s = self::slug($s) ?: 'file';
        if (in_array($s, ['index', 'con', 'aux', 'nul', 'prn', 'htaccess'], true)) $s .= '-file';
        return $s;
    }

    private static function unique(string $dir, string $base, string $ext): string
    {
        $name = $base . '.' . $ext;
        $i = 1;
        while (is_file($dir . '/' . $name)) $name = $base . '-' . (++$i) . '.' . $ext;
        return $name;
    }

    /* ---------- libreria ---------- */

    public static function library(string $sub = 'media'): array
    {
        $root = PP_ROOT . '/uploads/' . $sub;
        if (!is_dir($root)) return [];
        $out = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if (!$f->isFile() || $f->getFilename()[0] === '.' || preg_match('~-960\.[a-z]+$~', $f->getFilename())) continue;
            $rel = str_replace('\\', '/', 'uploads/' . $sub . substr($f->getPathname(), strlen($root)));
            $img = in_array(strtolower($f->getExtension()), self::IMAGES, true);
            $dim = $img ? @getimagesize($f->getPathname()) : false;
            $out[] = ['path' => $rel, 'name' => $f->getFilename(), 'size' => $f->getSize(), 'mtime' => $f->getMTime(),
                'image' => $img, 'w' => $dim[0] ?? 0, 'h' => $dim[1] ?? 0];
        }
        usort($out, fn($a, $b) => $b['mtime'] <=> $a['mtime']);
        return $out;
    }

    /** Percorso dentro uploads/, ripulito; eccezione se non valido. */
    public static function safePath(string $rel): string
    {
        $rel = ltrim(str_replace('\\', '/', $rel), '/');
        if (!str_starts_with($rel, 'uploads/') || str_contains($rel, '..') || !is_file(PP_ROOT . '/' . $rel)) throw new RuntimeException('File non trovato');
        return $rel;
    }

    /**
     * Sostituisce un file mantenendo lo stesso percorso: pagine e testi che lo usano mostrano subito il nuovo.
     * Le immagini vengono elaborate come al caricamento e salvate nel formato del file originale.
     */
    public static function replace(string $rel, array $f): void
    {
        $rel = self::safePath($rel);
        $old = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
        $new = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
        $norm = fn($e) => $e === 'jpeg' ? 'jpg' : $e;
        $raster = array_diff(self::IMAGES, ['gif']);
        $bothImg = in_array($norm($old), $raster, true) && in_array($norm($new), $raster, true);
        if (!$bothImg && $norm($old) !== $norm($new)) throw new RuntimeException('Per sostituire serve un file dello stesso tipo (.' . $old . ')');
        if ($bothImg && $norm($old) === 'webp' && !function_exists('imagewebp')) throw new RuntimeException('Il server non sa scrivere WebP');
        self::$forceExt = $bothImg ? $norm($old) : null;
        try {
            $tmp = self::upload($f, '_tmp', $old === 'svg' ? ['svg'] : array_merge(self::IMAGES, self::FILES));
        } finally {
            self::$forceExt = null;
        }
        $abs = PP_ROOT . '/' . $rel;
        $small = preg_replace('~(\.[a-z0-9]+)$~i', '-960$1', $abs);
        $tmpAbs = PP_ROOT . '/' . $tmp;
        $tmpSmall = preg_replace('~(\.[a-z0-9]+)$~i', '-960$1', $tmpAbs);
        rename($tmpAbs, $abs);
        if (is_file($tmpSmall)) rename($tmpSmall, $small); elseif (is_file($small)) unlink($small);
        @rmdir(dirname($tmpAbs)); @rmdir(dirname($tmpAbs, 2)); @rmdir(dirname($tmpAbs, 3));
        clearstatcache();
    }

    /** Dove è usato un file: [['label' => …, 'url' => link in gestione], …] */
    public static function usage(string $rel): array
    {
        $out = [];
        $like = ['%' . $rel . '%', '%' . str_replace('/', '\\/', $rel) . '%'];
        $title = fn($id) => (string)DB::val('SELECT title FROM pp_entry_tr WHERE entry_id = ? AND lang = ?', [$id, I18n::$master]);
        $ids = [];
        foreach (DB::all('SELECT id, type FROM pp_entries WHERE image = ? OR meta LIKE ? OR meta LIKE ?', [$rel, ...$like]) as $r) $ids[$r['id']] = $r['type'];
        foreach (DB::all('SELECT DISTINCT e.id, e.type FROM pp_entry_tr t JOIN pp_entries e ON e.id = t.entry_id WHERE t.body LIKE ? OR t.extra LIKE ? OR t.extra LIKE ?', [$like[0], ...$like]) as $r) $ids[$r['id']] = $r['type'];
        foreach ($ids as $id => $type) $out[] = ['label' => (PP::$config['types'][$type]['one'] ?? $type) . ': ' . ($title($id) ?: '#' . $id), 'url' => 'entry/' . $id . '/' . I18n::$master];
        foreach (DB::all('SELECT DISTINCT d.id, d.code FROM pp_docs d LEFT JOIN pp_doc_files f ON f.doc_id = d.id WHERE d.file = ? OR f.file = ?', [$rel, $rel]) as $r) $out[] = ['label' => 'documento ' . ($r['code'] ?: '#' . $r['id']), 'url' => 'doc/' . $r['id'] . '/' . I18n::$master];
        foreach (DB::all('SELECT DISTINCT skey FROM pp_strings WHERE value LIKE ?', [$like[0]]) as $r) $out[] = ['label' => 'testo del sito: ' . $r['skey'], 'url' => 'strings'];
        foreach (DB::all('SELECT k FROM pp_settings WHERE v LIKE ? OR v LIKE ?', $like) as $r) $out[] = ['label' => 'impostazioni: ' . $r['k'], 'url' => $r['k'] === 'identity' ? 'identity' : ''];
        return $out;
    }

    public static function delete(string $rel): void
    {
        $rel = self::safePath($rel);
        $abs = PP_ROOT . '/' . $rel;
        if (is_file($abs)) unlink($abs);
        $small = preg_replace('~(\.[a-z0-9]+)$~i', '-960$1', $abs);
        if ($small !== $abs && is_file($small)) unlink($small);
    }

    public static function human(int $bytes): string
    {
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
        return max(1, (int)round($bytes / 1024)) . ' KB';
    }

    /** Suggerimento di formato per un campo immagine (dalla configurazione). */
    public static function hint(string $key): string
    {
        $h = PP::$config['image_hints'][$key] ?? PP::$config['image_hints']['default'] ?? '';
        return $h;
    }

    /**
     * Galleria: una foto per riga ("percorso | descrizione") o già nella forma "percorso=descrizione|…".
     * Si salva come il codice [gallery:…]: percorsi delle immagini caricate, descrizione senza i caratteri che separano.
     */
    public static function gallery(string $v): string
    {
        $parts = [];
        foreach (preg_split('~\R|\|(?=\s*uploads/)~', str_contains($v, "\n") ? $v : str_replace('|', "\n", $v)) as $line) {
            $line = trim($line);
            if ($line === '') continue;
            [$path, $alt] = array_pad(preg_split('~\s*[=|]\s*~', $line, 2), 2, '');
            $path = trim($path);
            if (!preg_match('~^uploads/[A-Za-z0-9/_.-]+\.(webp|jpe?g|png|gif)$~i', $path) || str_contains($path, '..')) continue;
            $alt = trim(preg_replace('~\s+~', ' ', preg_replace('~[|=\]]+~', ' ', $alt)));
            $parts[] = $alt !== '' ? $path . '=' . $alt : $path;
        }
        return implode('|', array_values(array_unique($parts)));
    }
}
