<?php
final class StaticSite
{
    private const RESERVED = ['core', 'site', 'uploads', 'data', 'pp-admin', 'pp-assets', 'pp-themes', 'download', 'form', 'install.php', 'index.php', 'cron.php', 'provini'];

    public static function enabled(): bool
    {
        return (bool)Settings::get('static_enabled', PP::$config['static'] ?? false);
    }

    private static function manifestFile(): string { return PP_ROOT . '/data/static-manifest.json'; }

    /** Genera tutte le pagine pubbliche in HTML. */
    public static function build(): array
    {
        $t0 = microtime(true);
        @set_time_limit(300);
        $old = is_file(self::manifestFile()) ? (json_decode((string)file_get_contents(self::manifestFile()), true) ?: []) : [];
        $written = [];
        $savedLang = I18n::$current;
        $savedRoute = PP::$route;
        PP::$building = true;
        $reserved = array_merge(self::RESERVED, [PP::adminSlug()]);

        foreach (PP::items() as $it) {
            foreach ($it['paths'] as $l => $path) {
                $seg = array_merge([$l], $path === '' ? [] : explode('/', $path));
                foreach ($seg as $s) if (!preg_match('~^[a-z0-9][a-z0-9._-]*$~', $s)) continue 2;
                $page = PP::page($seg);
                if ($page['status'] !== 200) continue;
                $rel = implode('/', $seg) . '/index.html';
                self::write($rel, $page['html']);
                $written[] = $rel;
            }
        }
        // file per motori di ricerca e AI
        foreach (Hooks::filter('static_files', ['sitemap.xml']) as $f) {
            if (in_array(explode('/', $f)[0], $reserved, true)) continue;
            $res = Hooks::first('file', $f);
            if ($res) { self::write($f, $res['body']); $written[] = $f; }
        }
        // pagina 404 statica
        $nf = PP::notFound(I18n::$master);
        self::write('404.html', $nf['html']);
        $written[] = '404.html';

        // togli ciò che non esiste più
        foreach (array_diff($old, $written) as $gone) {
            $abs = PP_ROOT . '/' . $gone;
            if (is_file($abs) && self::safe($gone)) unlink($abs);
            $dir = dirname($abs);
            while ($dir !== PP_ROOT && is_dir($dir) && count(scandir($dir)) === 2) { rmdir($dir); $dir = dirname($dir); }
        }
        if (!is_dir(PP_ROOT . '/data')) mkdir(PP_ROOT . '/data', 0775, true);
        file_put_contents(self::manifestFile(), json_encode(array_values($written)));
        PP::$building = false;
        I18n::$current = $savedLang;
        PP::$route = $savedRoute;
        $res = ['at' => Content::now(), 'files' => count($written), 'seconds' => round(microtime(true) - $t0, 2)];
        Settings::set('static_last', $res);
        return $res;
    }

    private static function safe(string $rel): bool
    {
        $first = explode('/', $rel)[0];
        return !str_contains($rel, '..') && !in_array($first, array_merge(self::RESERVED, [PP::adminSlug()]), true);
    }

    private static function write(string $rel, string $content): void
    {
        if (!self::safe($rel)) return;
        $abs = PP_ROOT . '/' . $rel;
        if (!is_dir(dirname($abs))) mkdir(dirname($abs), 0755, true);
        $tmp = $abs . '.tmp';
        file_put_contents($tmp, $content);
        rename($tmp, $abs);
    }

    /** Toglie tutte le pagine statiche (torna al sito dinamico). */
    public static function clear(): void
    {
        $old = is_file(self::manifestFile()) ? (json_decode((string)file_get_contents(self::manifestFile()), true) ?: []) : [];
        foreach ($old as $gone) {
            $abs = PP_ROOT . '/' . $gone;
            if (is_file($abs) && self::safe($gone)) unlink($abs);
            $dir = dirname($abs);
            while ($dir !== PP_ROOT && is_dir($dir) && count(scandir($dir)) === 2) { rmdir($dir); $dir = dirname($dir); }
        }
        @unlink(self::manifestFile());
    }

    /* ---------- controlli ---------- */

    public static function checks(): array
    {
        $c = [];
        $add = fn($ok, $label, $fix = '') => $c[] = ['ok' => $ok, 'label' => $label, 'fix' => $fix];
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        $c[] = ['ok' => $https, 'label' => 'Connessione HTTPS', 'fix' => 'Attiva il certificato SSL su SiteGround e togli il commento a “Forza https” in .htaccess'];
        $c[] = ['ok' => !is_file(PP_ROOT . '/install.php'), 'label' => 'File di installazione rimosso', 'fix' => 'Cancella install.php dal server'];
        $c[] = ['ok' => empty(PP::$config['debug']), 'label' => 'Modalità debug spenta', 'fix' => "Metti 'debug' => false in site/config.php"];
        $c[] = ['ok' => PP::adminSlug() !== 'admin', 'label' => 'Indirizzo della gestione personalizzato', 'fix' => "Cambia 'admin_slug' in site/config.php (es. 'gestione-k7')"];
        $no2fa = DB::val("SELECT COUNT(*) FROM pp_users WHERE totp_secret = ''");
        $c[] = ['ok' => !$no2fa, 'label' => 'Accesso in due passaggi per tutti gli utenti', 'fix' => $no2fa . ' utenti senza: attivalo da Utenti'];
        $c[] = ['ok' => self::enabled(), 'label' => 'Sito pubblico in HTML statico', 'fix' => 'Attivalo qui sotto'];
        $c[] = ['ok' => PHP_VERSION_ID >= 80200, 'label' => 'PHP ' . PHP_VERSION, 'fix' => 'Da Site Tools → Devs → PHP Manager scegli la versione 8.2 o più recente'];
        $cfgPerm = @fileperms(PP_SITE . '/config.php');
        $c[] = ['ok' => $cfgPerm && !($cfgPerm & 0x0004), 'label' => 'config.php non leggibile da altri utenti del server', 'fix' => 'Imposta i permessi di site/config.php a 600 o 640'];
        return $c;
    }

    public static function admin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = Admin::post('action');
            if ($act === 'enable') { Settings::set('static_enabled', true); $r = self::build(); Admin::flash("Sito statico attivo: {$r['files']} file generati in {$r['seconds']} s."); }
            if ($act === 'disable') { Settings::set('static_enabled', false); self::clear(); Admin::flash('Sito statico disattivato: le pagine tornano a essere generate al momento.'); }
            if ($act === 'build') { $r = self::build(); Admin::flash("Rigenerati {$r['files']} file in {$r['seconds']} s."); }
            if ($act === 'migrate') { $d = Modules::migrate(); Admin::flash($d ? 'Aggiornati: ' . implode(', ', $d) : 'Database già aggiornato.'); }
            Auth::log('system ' . $act);
            PP::redirect(Admin::u('system'));
        }
        Admin::view(__DIR__ . '/admin.php', ['title' => 'Sistema', 'checks' => self::checks(), 'last' => Settings::get('static_last'), 'enabled' => self::enabled(), 'modules' => Modules::$list]);
    }
}
