<?php
/**
 * Lo zip del sito di un progetto (la cartella site/: config.sample.php, seed, testi, moduli, tema, immagini iniziali).
 * Lo usano l'installazione guidata (Setup) e Aggiornamenti → "Sito del progetto", per caricarne una versione nuova senza FTP.
 * Non dipende dal resto del motore: Setup lo usa prima che ci sia un config.
 * site/config.php (database e indirizzo della gestione) non si sovrascrive mai.
 */
final class SitePackage
{
    /** Dove sta la cartella del sito nello zip ("site/", "cartella/site/" o la radice). null = non è il sito di un progetto. */
    public static function prefix(ZipArchive $z): ?string
    {
        for ($i = 0; $i < $z->numFiles; $i++) {
            $n = str_replace('\\', '/', (string)$z->getNameIndex($i));
            if (preg_match('~^((?:[^/]+/)?)site/config\.sample\.php$~', $n, $m)) return $m[1] . 'site/';
            if ($n === 'config.sample.php') return '';
        }
        return null;
    }

    /**
     * Scompatta il sito in $dest. Con $mirror toglie prima i file che c'erano (tranne config.php), così un file
     * eliminato nella versione nuova non resta sul server. Ritorna quanti file ha scritto.
     */
    public static function extract(string $zipFile, string $dest, bool $mirror = false): int
    {
        if (!class_exists('ZipArchive')) throw new RuntimeException('Il server non ha ZipArchive (estensione zip di PHP).');
        $z = new ZipArchive();
        if ($z->open($zipFile) !== true) throw new RuntimeException('Il file non è uno zip valido.');
        $prefix = self::prefix($z);
        if ($prefix === null) { $z->close(); throw new RuntimeException('Nello zip non c’è il sito: manca site/config.sample.php.'); }
        $files = [];
        for ($i = 0; $i < $z->numFiles; $i++) {
            $n = str_replace('\\', '/', (string)$z->getNameIndex($i));
            if ($prefix !== '' && !str_starts_with($n, $prefix)) continue;
            $rel = substr($n, strlen($prefix));
            if ($rel === '' || str_ends_with($rel, '/')) continue;
            // niente percorsi che escono dalla cartella, niente file nascosti, niente config.php già compilato
            if (str_contains($rel, '..') || str_contains($rel, "\0") || str_starts_with($rel, '/') || preg_match('~(^|/)\.~', $rel) || $rel === 'config.php') continue;
            $files[$rel] = $i;
        }
        if (!isset($files['config.sample.php'])) { $z->close(); throw new RuntimeException('Nello zip non c’è il sito: manca site/config.sample.php.'); }
        if ($mirror && is_dir($dest)) self::clear($dest);
        if (!is_dir($dest)) mkdir($dest, 0775, true);
        $count = 0;
        foreach ($files as $rel => $i) {
            $to = $dest . '/' . $rel;
            if (!is_dir(dirname($to))) mkdir(dirname($to), 0775, true);
            if (file_put_contents($to, (string)$z->getFromIndex($i)) === false) { $z->close(); throw new RuntimeException('Non riesco a scrivere ' . $rel . ': controlla i permessi della cartella site/.'); }
            if (str_ends_with($rel, '.php') && function_exists('opcache_invalidate')) @opcache_invalidate($to, true);
            $count++;
        }
        $z->close();
        return $count;
    }

    /** Copia della cartella del sito com'è adesso (senza config.php), per tornare indietro. Ritorna il percorso dello zip. */
    public static function snapshot(string $src, string $dir, string $name): string
    {
        if (!class_exists('ZipArchive')) throw new RuntimeException('Il server non ha ZipArchive (estensione zip di PHP).');
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $file = $dir . '/' . $name;
        $z = new ZipArchive();
        if ($z->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Non riesco a fare la copia del sito attuale.');
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if (!$f->isFile()) continue;
            $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($src) + 1));
            if ($rel === 'config.php' || preg_match('~(^|/)\.~', $rel)) continue;
            $z->addFile($f->getPathname(), 'site/' . $rel);
        }
        $z->close();
        return $file;
    }

    /** true se site/config.php prende tutto da config.sample.php (com'è scritto dall'installazione guidata). */
    public static function configFollowsSample(string $siteDir): bool
    {
        $c = (string)@file_get_contents($siteDir . '/config.php');
        return (bool)preg_match('~require\s+__DIR__\s*\.\s*[\'"]/config\.sample\.php[\'"]~', $c);
    }

    /**
     * Riscrive site/config.php come lo scrive l'installazione: tiene database, indirizzo della gestione e del sito, debug
     * (e mail_from/update se c'erano), tutto il resto viene da config.sample.php. La copia del vecchio va in $backupDir.
     */
    public static function followSample(string $siteDir, string $backupDir): string
    {
        $file = $siteDir . '/config.php';
        $cur = require $file;
        if (!is_array($cur)) throw new RuntimeException('site/config.php non restituisce la configurazione: non lo tocco.');
        if (!is_dir($backupDir)) mkdir($backupDir, 0775, true);
        $copy = $backupDir . '/config-' . date('Ymd-His') . '.php.txt';
        copy($file, $copy);
        $over = [];
        foreach (['db', 'admin_slug', 'site_url', 'debug', 'mail_from', 'update'] as $k) if (array_key_exists($k, $cur)) $over[$k] = $cur[$k];
        // il percorso del database SQLite resta relativo alla cartella, come lo scrive l'installazione
        $sqlite = realpath($siteDir . '/../data/site.sqlite');
        if (!empty($over['db']['file']) && $sqlite !== false && realpath((string)$over['db']['file']) === $sqlite) $over['db']['file'] = '__DATA__';
        $export = str_replace("'__DATA__'", "__DIR__ . '/../data/site.sqlite'", var_export($over, true));
        $php = "<?php\n/** Riscritto da Aggiornamenti il " . date('Y-m-d H:i') . ": database e indirizzo della gestione.\n"
            . " * Tutto il resto viene da config.sample.php (struttura del sito) e dalla gestione (contatti, social, colori, privacy). */\n"
            . "\$c = require __DIR__ . '/config.sample.php';\nreturn array_replace(\$c, " . $export . ");\n";
        if (file_put_contents($file, $php, LOCK_EX) === false) throw new RuntimeException('Non riesco a scrivere site/config.php: controlla i permessi.');
        if (function_exists('opcache_invalidate')) @opcache_invalidate($file, true);
        return basename($copy);
    }

    /** Toglie i file della cartella del sito tranne config.php. */
    private static function clear(string $dir): void
    {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $f) {
            $p = $f->getPathname();
            if ($f->isDir() && !$f->isLink()) { @rmdir($p); continue; }
            if ($p === $dir . '/config.php' || $f->getFilename()[0] === '.') continue;   // config.php e le protezioni (.htaccess) restano
            @unlink($p);
        }
    }
}
