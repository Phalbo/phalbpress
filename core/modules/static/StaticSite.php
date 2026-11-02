<?php
final class StaticSite
{
    private const RESERVED = ['core', 'site', 'uploads', 'data', 'pp-admin', 'pp-assets', 'pp-themes', 'download', 'form', 'install.php', 'index.php', 'cron.php', 'provini'];

    public static function enabled(): bool
    {
        return (bool)Settings::get('static_enabled', PP::$config['static'] ?? false);
    }

    private static function manifestFile(): string { return PP_ROOT . '/data/static-manifest.json'; }

    /**
     * Dopo un salvataggio: il sito è "da rigenerare" e la rigenerazione parte a fine richiesta, dopo aver mandato la risposta
     * al browser (PHP-FPM, come SiteGround), così chi salva non aspetta. Più salvataggi vicini (es. la Modifica visuale)
     * non fanno più rigenerazioni insieme: chi trova il lucchetto lascia il segno e la rigenerazione in corso ripassa alla fine.
     * Se una rigenerazione si interrompe, la riprende il primo accesso alla gestione o cron.php.
     */
    public static function later(): void
    {
        Settings::set('static_dirty', (string)microtime(true));
        static $queued = false;
        if ($queued) return;
        $queued = true;
        register_shutdown_function(function () {
            ignore_user_abort(true);
            if (function_exists('fastcgi_finish_request')) @fastcgi_finish_request();
            try { self::pending(); } catch (Throwable $e) { error_log('PhalbPress sito statico: ' . $e->getMessage()); }
        });
    }

    /** C'è una rigenerazione in attesa? (timestamp del salvataggio, 0 = no) */
    public static function dirty(): float
    {
        Settings::reset();
        return (float)Settings::get('static_dirty', 0);
    }

    /** Rigenera se c'è qualcosa in attesa e nessun'altra rigenerazione è in corso. */
    public static function pending(): ?array
    {
        if (!self::enabled() || !self::dirty()) return null;
        if (!is_dir(PP_ROOT . '/data')) mkdir(PP_ROOT . '/data', 0775, true);
        $fh = @fopen(PP_ROOT . '/data/static.lock', 'c');
        if (!$fh || !flock($fh, LOCK_EX | LOCK_NB)) return null;
        $res = null;
        try {
            for ($i = 0; $i < 3; $i++) {
                $mark = (string)Settings::get('static_dirty', '0');
                if (class_exists('SiteSettings')) SiteSettings::apply();   // valori salvati in questa richiesta o in un'altra
                $res = self::build();
                Settings::reset();
                // un salvataggio arrivato durante la rigenerazione: si ripassa una volta, con i dati nuovi
                if ((string)Settings::get('static_dirty', '0') === $mark) { Settings::set('static_dirty', 0); break; }
            }
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
        return $res;
    }

    /** Una rigenerazione rimasta indietro (interrotta o mai partita) da più di un minuto: si fa a fine richiesta. */
    public static function catchUp(): void
    {
        if (!self::enabled()) return;
        $d = self::dirty();
        if ($d && $d < microtime(true) - 60) self::later();
    }

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
        $here = (($https) ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '') . PP::base();
        $c[] = ['ok' => !empty(PP::$config['site_url']) || (string)Settings::get('site_url', '') !== '', 'label' => 'Indirizzo del sito impostato',
            'fix' => 'Senza, canonical, sitemap, feed ed email prendono l’indirizzo da chi visita. Usa il pulsante qui sotto per salvare ' . $here, 'act' => 'site_url'];
        $c[] = ['ok' => (PP::$config['site_upload'] ?? true) === false, 'label' => 'Caricamento del sito del progetto dalla gestione spento',
            'fix' => "Aggiornamenti → Sito del progetto permette a un amministratore di caricare codice PHP. Sui siti consegnati al cliente metti 'site_upload' => false in site/config.php"];
        $cfgPerm = @fileperms(PP_SITE . '/config.php');
        $c[] = ['ok' => $cfgPerm && !($cfgPerm & 0x0004), 'label' => 'config.php non leggibile da altri utenti del server', 'fix' => 'Imposta i permessi di site/config.php a 600 o 640'];
        return $c;
    }

    public static function admin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = Admin::post('action');
            if ($act === 'enable') { Settings::set('static_enabled', true); $r = self::build(); Settings::set('static_dirty', 0); Admin::flash("Sito statico attivo: {$r['files']} file generati in {$r['seconds']} s."); }
            if ($act === 'disable') { Settings::set('static_enabled', false); self::clear(); Admin::flash('Sito statico disattivato: le pagine tornano a essere generate al momento.'); }
            if ($act === 'build') { $r = self::build(); Settings::set('static_dirty', 0); Admin::flash("Rigenerati {$r['files']} file in {$r['seconds']} s."); }
            if ($act === 'site_url') {
                $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
                $host = preg_replace('~[^a-z0-9.:-]~i', '', (string)($_SERVER['HTTP_HOST'] ?? ''));
                if ($host !== '') { Settings::set('site_url', ($https ? 'https://' : 'http://') . $host . PP::base()); Admin::flash('Indirizzo del sito salvato: ' . Settings::get('site_url') . '.'); Admin::changed('settings'); }
            }
            if ($act === 'migrate') { $d = Modules::migrate(); Admin::flash($d ? 'Aggiornati: ' . implode(', ', $d) : 'Database già aggiornato.'); }
            Auth::log('system ' . $act);
            PP::redirect(Admin::u('system'));
        }
        Admin::view(__DIR__ . '/admin.php', ['title' => 'Sistema', 'checks' => self::checks(), 'last' => Settings::get('static_last'), 'enabled' => self::enabled(), 'modules' => Modules::$list]);
    }
}
