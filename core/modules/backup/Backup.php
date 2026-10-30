<?php
final class Backup
{
    /** Tabelle che un modello non porta con sé. */
    private const PRIVATE = ['pp_users', 'pp_login_attempts', 'pp_log', 'pp_tokens', 'pp_form_entries', 'pp_revisions'];
    /** Impostazioni che non passano a un altro sito. */
    private const PRIVATE_SETTINGS = ['secret', 'static_last', 'cron_last', 'module_versions', 'forms_to'];
    /** File ammessi in uploads/ al ripristino. */
    private const EXT = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'pdf', 'doc', 'docx', 'odt', 'ppt', 'pptx', 'xls', 'xlsx', 'ods', 'zip', 'mp4', 'mp3', 'txt', 'csv', 'woff2', 'css', 'ico'];

    public static function dir(): string
    {
        $d = PP_ROOT . '/data/backups';
        if (!is_dir($d)) mkdir($d, 0775, true);
        if (!is_file($d . '/.htaccess')) file_put_contents($d . '/.htaccess', "Require all denied\n");
        return $d;
    }

    /** Tabelle del sito: quelle del core (pp_) più quelle dichiarate dai moduli col filtro 'backup_tables'. */
    public static function tables(): array
    {
        $all = DB::driver() === 'sqlite'
            ? array_column(DB::all("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"), 'name')
            : array_map(fn($r) => reset($r), DB::all('SHOW TABLES'));
        $want = Hooks::filter('backup_tables', array_values(array_filter($all, fn($t) => str_starts_with($t, 'pp_'))));
        return array_values(array_intersect($want, $all));
    }

    /** Crea il .zip. $kind: 'full' | 'template'. Ritorna il nome del file. */
    public static function export(string $kind = 'full', bool $uploads = true): string
    {
        if (!class_exists('ZipArchive')) throw new RuntimeException('Il server non ha ZipArchive (estensione zip di PHP)');
        @set_time_limit(600);
        $base = ($kind === 'template' ? 'modello-' : 'backup-') . Media::slug(PP::$config['site_name'] ?? 'sito') . '-' . date('Ymd-His') . ($uploads ? '' : '-db');
        $name = $base . '.zip'; $n = 1;
        while (is_file(self::dir() . '/' . $name)) $name = $base . '-' . (++$n) . '.zip';
        $zip = new ZipArchive();
        if ($zip->open(self::dir() . '/' . $name, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Impossibile creare il file zip');
        $tables = self::tables();
        if ($kind === 'template') $tables = array_values(array_diff($tables, self::PRIVATE));
        foreach ($tables as $t) {
            $rows = DB::all('SELECT * FROM ' . $t);
            if ($t === 'pp_settings' && $kind === 'template') $rows = array_values(array_filter($rows, fn($r) => !in_array($r['k'], self::PRIVATE_SETTINGS, true)));
            $zip->addFromString('db/' . $t . '.json', json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
        $files = 0;
        if ($uploads && is_dir(PP_ROOT . '/uploads')) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(PP_ROOT . '/uploads', FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                if (!$f->isFile() || str_contains($f->getPathname(), '/_tmp/')) continue;
                $rel = 'uploads' . str_replace('\\', '/', substr($f->getPathname(), strlen(PP_ROOT . '/uploads')));
                if ($f->getFilename() === '.htaccess' || in_array(strtolower($f->getExtension()), self::EXT, true)) { $zip->addFile($f->getPathname(), $rel); $files++; }
            }
        }
        $zip->addFromString('manifest.json', json_encode([
            'phalbpress' => PP::VERSION, 'kind' => $kind, 'created' => Content::now(), 'site_name' => PP::$config['site_name'] ?? '',
            'master_language' => I18n::$master, 'languages' => I18n::codes(), 'tables' => $tables, 'files' => $files, 'theme' => PP::theme(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $zip->close();
        Auth::log('backup ' . $kind, $name);
        return $name;
    }

    /** Legge il manifest di un .zip. */
    public static function manifest(string $file): array
    {
        $zip = new ZipArchive();
        if ($zip->open($file) !== true) throw new RuntimeException('File zip non leggibile');
        $m = json_decode((string)$zip->getFromName('manifest.json'), true);
        $zip->close();
        if (!is_array($m) || empty($m['phalbpress'])) throw new RuntimeException('Non è un backup di PhalbPress');
        return $m;
    }

    /**
     * Ripristina un backup o applica un modello.
     * 'full' sostituisce tutto (utenti compresi); 'template' sostituisce contenuti e impostazioni e lascia utenti e registro.
     */
    public static function restore(string $file, bool $keepUsers = true): array
    {
        if (!class_exists('ZipArchive')) throw new RuntimeException('Il server non ha ZipArchive (estensione zip di PHP)');
        @set_time_limit(600);
        $m = self::manifest($file);
        $zip = new ZipArchive();
        $zip->open($file);
        $known = self::tables();
        $skip = $keepUsers ? self::PRIVATE : [];
        $done = [];
        $pdo = DB::pdo();
        $pdo->beginTransaction();
        try {
            foreach ((array)$m['tables'] as $t) {
                if (!in_array($t, $known, true) || in_array($t, $skip, true)) continue;
                $rows = json_decode((string)$zip->getFromName('db/' . $t . '.json'), true);
                if (!is_array($rows)) continue;
                $cols = DB::columns($t);
                if ($t === 'pp_settings') {
                    // le impostazioni private di questo sito restano
                    $keep = DB::all('SELECT * FROM pp_settings');
                    DB::run('DELETE FROM pp_settings');
                    foreach ($keep as $r) if (in_array($r['k'], self::PRIVATE_SETTINGS, true)) DB::insert('pp_settings', $r);
                    foreach ($rows as $r) if (!in_array($r['k'], self::PRIVATE_SETTINGS, true)) DB::upsert('pp_settings', array_intersect_key($r, array_flip($cols)), ['k']);
                } else {
                    DB::run('DELETE FROM ' . $t);
                    foreach ($rows as $r) DB::insert($t, array_intersect_key($r, array_flip($cols)));
                }
                $done[] = $t;
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            $zip->close();
            throw $e;
        }
        // file: solo dentro uploads/, solo tipi ammessi, niente percorsi strani
        $files = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $n = (string)$zip->getNameIndex($i);
            if (!str_starts_with($n, 'uploads/') || str_ends_with($n, '/') || str_contains($n, '..') || str_contains($n, '\\') || str_contains($n, "\0")) continue;
            $base = basename($n);
            $ext = strtolower(pathinfo($n, PATHINFO_EXTENSION));
            if ($base !== '.htaccess' && (!in_array($ext, self::EXT, true) || $base[0] === '.')) continue;
            if ($base === '.htaccess' && is_file(PP_ROOT . '/' . $n)) continue;   // le protezioni esistenti non si toccano
            if (!preg_match('~^uploads(/[a-z0-9._-]+)+$~i', $n)) continue;
            $dst = PP_ROOT . '/' . $n;
            if (!is_dir(dirname($dst))) mkdir(dirname($dst), 0775, true);
            $data = $zip->getFromIndex($i);
            if ($ext === 'svg') $data = Media::cleanSvg((string)$data);
            file_put_contents($dst, $data);
            $files++;
        }
        $zip->close();
        Settings::reset();
        if (class_exists('Identity')) Identity::write();
        return ['tables' => count($done), 'files' => $files, 'manifest' => $m];
    }

    public static function list(): array
    {
        $out = [];
        foreach (glob(self::dir() . '/*.zip') ?: [] as $f) $out[] = ['name' => basename($f), 'size' => filesize($f), 'mtime' => filemtime($f)];
        usort($out, fn($a, $b) => $b['mtime'] <=> $a['mtime']);
        return $out;
    }

    private static function file(string $name): string
    {
        $name = basename($name);
        $f = self::dir() . '/' . $name;
        if (!preg_match('~^[a-z0-9._-]+\.zip$~', $name) || !is_file($f)) throw new RuntimeException('File non trovato');
        return $f;
    }

    public static function admin(array $seg): void
    {
        if (($seg[0] ?? '') === 'download') {
            $f = self::file((string)($seg[1] ?? ''));
            header('Content-Type: application/zip');
            header('Content-Length: ' . filesize($f));
            header('Content-Disposition: attachment; filename="' . basename($f) . '"');
            readfile($f);
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = Admin::post('action');
            try {
                if ($act === 'export') {
                    $n = self::export(Admin::post('kind') === 'template' ? 'template' : 'full', (bool)Admin::post('uploads'));
                    Admin::flash('Creato ' . $n . '. Scaricalo e conservalo fuori dal server.');
                } elseif ($act === 'delete') {
                    unlink(self::file((string)Admin::post('name')));
                    Admin::flash('Eliminato.');
                } elseif ($act === 'restore') {
                    if (Admin::post('confirm') !== 'RIPRISTINA') throw new RuntimeException('Scrivi RIPRISTINA per confermare.');
                    if (!empty($_FILES['zip']['tmp_name']) && is_uploaded_file($_FILES['zip']['tmp_name'])) {
                        $f = self::dir() . '/caricato-' . date('Ymd-His') . '.zip';
                        move_uploaded_file($_FILES['zip']['tmp_name'], $f);
                    } else $f = self::file((string)Admin::post('name'));
                    $safety = self::export('full', false);   // prima di ripristinare: copia di sicurezza del database
                    $r = self::restore($f, Admin::post('mode') !== 'full');
                    Auth::log('restore', basename($f));
                    Admin::changed('restore');
                    Admin::flash('Ripristinate ' . $r['tables'] . ' tabelle e ' . $r['files'] . ' file. Copia di sicurezza di prima: ' . $safety . '.');
                }
            } catch (Throwable $e) { Admin::flash($e->getMessage(), 'error'); }
            PP::redirect(Admin::u('backup'));
        }
        Admin::view(__DIR__ . '/admin.php', ['title' => 'Backup e modelli', 'files' => self::list(), 'zip' => class_exists('ZipArchive')]);
    }
}
