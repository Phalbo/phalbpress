<?php
/**
 * Aggiornamento del motore da GitHub.
 *
 * Le release stanno su https://github.com/<repo>/releases: ogni release ha
 *   phalbpress-core-X.Y.Z.zip       solo il motore (core/, pp-admin/, pp-assets/, pp-themes/, index.php, cron.php, .htaccess, documentazione)
 *   phalbpress-core-X.Y.Z.zip.sig   firma Ed25519 dello zip (base64), fatta con la chiave di release
 * Il sito accetta solo zip firmati con la chiave pubblica in release.pub.
 *
 * Aggiornare: controlla → scarica → verifica la firma → controlla ogni percorso dello zip → backup completo
 * → estrae in data/updates/ → sostituisce le cartelle del motore (rename) → al caricamento successivo
 * applica le modifiche al database e rigenera le pagine statiche. Se una sostituzione fallisce, rimette la versione di prima.
 * site/, uploads/ e data/ non vengono mai toccati. install.php non viene mai rimesso.
 */
final class Updater
{
    public const REPO = 'Phalbo/phalbpress';
    /** Cartelle e file del motore che un aggiornamento può sostituire. */
    public const ITEMS = ['core', 'pp-admin', 'pp-assets', 'pp-themes', 'index.php', 'cron.php', 'CLAUDE.md', 'LEGGIMI.md', 'CHANGELOG.md'];
    /** Altro che può stare nello zip ma non si sostituisce da solo. */
    private const EXTRA = ['.htaccess', 'phalbpress.json'];
    private const CHECK_EVERY = 43200; // 12 ore

    public static function repo(): string
    {
        $r = (string)(PP::$config['update']['repo'] ?? self::REPO);
        return preg_match('~^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$~', $r) ? $r : self::REPO;
    }

    public static function dir(): string
    {
        $d = PP_ROOT . '/data/updates';
        if (!is_dir($d)) @mkdir($d, 0755, true);
        if (!is_file($d . '/.htaccess')) @file_put_contents($d . '/.htaccess', "Require all denied\n");
        return $d;
    }

    public static function publicKey(): string
    {
        $k = trim((string)@file_get_contents(__DIR__ . '/release.pub'));
        $raw = base64_decode($k, true);
        return $raw !== false && strlen($raw) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES ? $raw : '';
    }

    /* ---------- controllo ---------- */

    /** Ultima release nota (dalla cache). */
    public static function info(): array
    {
        return (array)Settings::get('update_info', []);
    }

    /** C'è una versione più nuova? (solo cache, nessuna richiesta di rete) */
    public static function available(): bool
    {
        $i = self::info();
        return !empty($i['version']) && version_compare($i['version'], PP::VERSION, '>');
    }

    /** Chiede a GitHub l'ultima release. $force = ignora la cache. */
    public static function check(bool $force = false): array
    {
        $i = self::info();
        if (!$force && !empty($i['checked']) && time() - (int)$i['checked'] < self::CHECK_EVERY) return $i;
        $j = json_decode(self::http('https://api.github.com/repos/' . self::repo() . '/releases/latest', true), true);
        if (!is_array($j) || empty($j['tag_name'])) throw new RuntimeException('Risposta di GitHub non valida');
        $ver = ltrim((string)$j['tag_name'], 'v');
        if (!preg_match('~^\d+\.\d+\.\d+$~', $ver)) throw new RuntimeException('Versione della release non valida: ' . $j['tag_name']);
        $zip = $sig = '';
        foreach ($j['assets'] ?? [] as $a) {
            if (($a['name'] ?? '') === "phalbpress-core-$ver.zip") $zip = (string)$a['browser_download_url'];
            if (($a['name'] ?? '') === "phalbpress-core-$ver.zip.sig") $sig = (string)$a['browser_download_url'];
        }
        $i = ['version' => $ver, 'notes' => mb_substr((string)($j['body'] ?? ''), 0, 8000), 'url' => (string)($j['html_url'] ?? ''),
            'published' => substr((string)($j['published_at'] ?? ''), 0, 10), 'zip' => $zip, 'sig' => $sig, 'checked' => time()];
        Settings::set('update_info', $i);
        return $i;
    }

    /* ---------- requisiti ---------- */

    public static function requirements(): array
    {
        $r = [
            ['ok' => class_exists('ZipArchive'), 'label' => 'Estensione zip di PHP'],
            ['ok' => function_exists('sodium_crypto_sign_verify_detached'), 'label' => 'Estensione sodium di PHP (verifica della firma)'],
            ['ok' => function_exists('curl_init') || filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN), 'label' => 'Connessione verso GitHub (curl)'],
            ['ok' => self::publicKey() !== '', 'label' => 'Chiave pubblica di release in core/modules/updater/release.pub'],
            ['ok' => is_writable(PP_ROOT) && is_writable(PP_ROOT . '/data'), 'label' => 'Cartella del sito scrivibile da PHP'],
        ];
        foreach (self::ITEMS as $it) {
            $p = PP_ROOT . '/' . $it;
            if (file_exists($p) && !is_writable($p)) $r[] = ['ok' => false, 'label' => "$it non scrivibile da PHP"];
        }
        return $r;
    }

    private static function ready(): void
    {
        foreach (self::requirements() as $c) if (!$c['ok']) throw new RuntimeException('Requisito mancante: ' . $c['label']);
    }

    /* ---------- aggiornamento ---------- */

    /** Scarica, verifica e installa la versione indicata da check(). Restituisce un riepilogo. */
    public static function apply(): array
    {
        self::ready();
        $lock = self::dir() . '/.lock';
        $fh = fopen($lock, 'c');
        if (!$fh || !flock($fh, LOCK_EX | LOCK_NB)) throw new RuntimeException('Un aggiornamento è già in corso');
        try {
            @set_time_limit(300);
            $i = self::check(true);
            $from = PP::VERSION; $to = $i['version'];
            if (!version_compare($to, $from, '>')) throw new RuntimeException("La versione installata ($from) è già la più recente");
            if (!$i['zip'] || !$i['sig']) throw new RuntimeException("La release $to non ha lo zip firmato del motore");

            // 1. scarica e verifica la firma
            $zipFile = self::dir() . "/phalbpress-core-$to.zip";
            file_put_contents($zipFile, self::http($i['zip']));
            $sig = base64_decode(trim(self::http($i['sig'])), true);
            if ($sig === false || strlen($sig) !== SODIUM_CRYPTO_SIGN_BYTES
                || !sodium_crypto_sign_verify_detached($sig, (string)file_get_contents($zipFile), self::publicKey())) {
                @unlink($zipFile);
                throw new RuntimeException('Firma non valida: lo zip non viene da una release autorizzata. Aggiornamento annullato.');
            }

            // 2. controlla lo zip
            $zip = new ZipArchive();
            if ($zip->open($zipFile) !== true) throw new RuntimeException('Zip della release illeggibile');
            $manifest = json_decode((string)$zip->getFromName('phalbpress.json'), true);
            if (($manifest['version'] ?? '') !== $to) throw new RuntimeException('La versione dentro lo zip non corrisponde alla release');
            if (!empty($manifest['php_min']) && version_compare(PHP_VERSION, $manifest['php_min'], '<')) throw new RuntimeException("Serve PHP {$manifest['php_min']} o più recente");
            $names = [];
            for ($n = 0; $n < $zip->numFiles; $n++) {
                $name = (string)$zip->getNameIndex($n);
                $top = explode('/', $name)[0];
                if ($name === '' || str_contains($name, '\\') || str_starts_with($name, '/') || preg_match('~(^|/)\.\.(/|$)~', $name) || str_contains($name, "\0")
                    || !in_array($top, array_merge(self::ITEMS, self::EXTRA), true)) {
                    throw new RuntimeException('Percorso non ammesso nello zip: ' . $name);
                }
                $names[] = $name;
            }
            if (!in_array('core/PP.php', $names, true)) throw new RuntimeException('Lo zip non contiene il motore');

            // 3. copia del database (l'aggiornamento non tocca immagini e file caricati: non serve copiarli ogni volta)
            $backup = Backup::export('full', false);

            // 4. estrae in una cartella di appoggio
            $stage = self::dir() . "/stage-$to";
            self::rmdir($stage);
            if (!$zip->extractTo($stage)) throw new RuntimeException('Estrazione non riuscita');
            $zip->close();

            // 5. sostituisce le cartelle del motore; la versione di prima resta in data/updates/prev-<versione>
            $prev = self::dir() . "/prev-$from";
            self::rmdir($prev);
            mkdir($prev, 0755, true);
            self::swap($stage, $prev, array_values(array_filter(self::ITEMS, fn($it) => file_exists("$stage/$it"))));

            // .htaccess: non si sovrascrive (può contenere https e HSTS attivati a mano), si segnala se è cambiato
            $ht = '';
            if (is_file("$stage/.htaccess") && hash_file('sha256', "$stage/.htaccess") !== @hash_file('sha256', PP_ROOT . '/.htaccess')) {
                $ht = self::dir() . "/htaccess-$to.txt";
                copy("$stage/.htaccess", $ht);
            }
            self::rmdir($stage);
            self::cleanup($to, $from);
            self::fresh(self::ITEMS);

            $r = ['from' => $from, 'to' => $to, 'at' => Content::now(), 'backup' => $backup, 'prev' => basename($prev), 'htaccess' => $ht ? basename($ht) : ''];
            Settings::set('update_last', $r);
            Settings::set('update_after', true);
            return $r;
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }

    /** Rimette la versione salvata prima dell'ultimo aggiornamento. */
    public static function rollback(): array
    {
        $last = (array)Settings::get('update_last', []);
        $prev = self::dir() . '/' . basename((string)($last['prev'] ?? ''));
        if (empty($last['prev']) || !is_dir($prev) || !is_file("$prev/core/PP.php")) throw new RuntimeException('Non c’è una versione precedente da rimettere');
        $bad = self::dir() . '/bad-' . PP::VERSION;
        self::rmdir($bad);
        mkdir($bad, 0755, true);
        self::swap($prev, $bad, array_values(array_filter(self::ITEMS, fn($it) => file_exists("$prev/$it"))));
        self::rmdir($prev);
        self::fresh(self::ITEMS);
        $r = ['from' => $last['to'] ?? PP::VERSION, 'to' => $last['from'] ?? '', 'at' => Content::now()];
        Settings::set('update_last', ['rolled_back' => true] + $r);
        Settings::set('update_after', true);
        return $r;
    }

    /**
     * Sposta ogni voce da $src alla radice del sito, mettendo prima da parte in $keep quella attuale.
     * Se un passaggio fallisce, rimette tutto com'era.
     */
    private static function swap(string $src, string $keep, array $items): void
    {
        $done = [];
        try {
            foreach ($items as $it) {
                $live = PP_ROOT . '/' . $it;
                $moved = false;
                if (file_exists($live)) {
                    if (!@rename($live, "$keep/$it")) throw new RuntimeException("Impossibile spostare $it");
                    $moved = true;
                }
                if (!@rename("$src/$it", $live)) {
                    if ($moved) @rename("$keep/$it", $live);
                    throw new RuntimeException("Impossibile mettere al suo posto $it");
                }
                $done[] = [$it, $moved];
            }
        } catch (Throwable $e) {
            foreach (array_reverse($done) as [$it, $moved]) {
                @rename(PP_ROOT . '/' . $it, "$src/$it");
                if ($moved) @rename("$keep/$it", PP_ROOT . '/' . $it);
            }
            throw new RuntimeException($e->getMessage() . '. Il sito è rimasto alla versione di prima.');
        }
    }

    /**
     * PHP tiene in memoria il codice già letto (OPcache) e lo rilegge solo se la data del file cambia.
     * Sugli hosting condivisi svuotare OPcache è spesso vietato: si dà la data di adesso a ogni file nuovo, così viene riletto.
     */
    private static function fresh(array $items): void
    {
        $now = time();
        foreach ($items as $it) {
            $p = PP_ROOT . '/' . $it;
            if (is_file($p)) { @touch($p, $now); if (function_exists('opcache_invalidate')) @opcache_invalidate($p, true); continue; }
            if (!is_dir($p)) continue;
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($p, FilesystemIterator::SKIP_DOTS)) as $f) {
                @touch($f->getPathname(), $now);
                if ($f->getExtension() === 'php' && function_exists('opcache_invalidate')) @opcache_invalidate($f->getPathname(), true);
            }
        }
        clearstatcache(true);
        if (function_exists('opcache_reset')) @opcache_reset();
    }

    /** Il codice in uso è ancora quello di prima? (aggiornamento fatto ma PHP non l'ha ancora riletto) */
    public static function stale(): bool
    {
        $last = (array)Settings::get('update_last', []);
        return !empty($last['to']) && empty($last['rolled_back']) && version_compare(PP::VERSION, (string)$last['to'], '<');
    }

    /** Dopo un aggiornamento (al primo caricamento con il codice nuovo): pagine statiche rigenerate. */
    public static function after(): string
    {
        if (!Settings::get('update_after')) return '';
        Settings::set('update_after', false);
        try {
            if (class_exists('StaticSite') && StaticSite::enabled()) { $b = StaticSite::build(); return "pagine statiche rigenerate ({$b['files']} file)"; }
        } catch (Throwable $e) { return 'rigenera le pagine statiche da Sistema: ' . $e->getMessage(); }
        return '';
    }

    /** Tiene solo lo zip e la versione precedente dell'ultimo aggiornamento. */
    private static function cleanup(string $to, string $from): void
    {
        foreach (glob(self::dir() . '/*') ?: [] as $f) {
            $b = basename($f);
            if (in_array($b, ["phalbpress-core-$to.zip", "prev-$from", "htaccess-$to.txt", '.htaccess', '.lock'], true)) continue;
            if (preg_match('~^(phalbpress-core-.+\.zip|prev-.+|bad-.+|stage-.+|htaccess-.+\.txt)$~', $b)) is_dir($f) ? self::rmdir($f) : @unlink($f);
        }
    }

    private static function rmdir(string $d): void
    {
        if (!is_dir($d)) return;
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $f) $f->isDir() && !$f->isLink() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        @rmdir($d);
    }

    /** GET https con redirect (i file delle release passano da objects.githubusercontent.com). */
    private static function http(string $url, bool $api = false): string
    {
        if (!str_starts_with($url, 'https://')) throw new RuntimeException('Indirizzo non sicuro: ' . $url);
        $headers = ['User-Agent: PhalbPress/' . PP::VERSION];
        if ($api) $headers[] = 'Accept: application/vnd.github+json';
        if (function_exists('curl_init')) {
            $c = curl_init($url);
            curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 5,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 120, CURLOPT_HTTPHEADER => $headers, CURLOPT_SSL_VERIFYPEER => true]);
            $body = curl_exec($c);
            $code = (int)curl_getinfo($c, CURLINFO_RESPONSE_CODE);
            $err = curl_error($c);
            curl_close($c);
            if ($body === false || $code >= 400) throw new RuntimeException("GitHub non risponde ($code" . ($err ? ", $err" : '') . ')');
            return (string)$body;
        }
        $ctx = stream_context_create(['http' => ['header' => implode("\r\n", $headers), 'timeout' => 120, 'follow_location' => 1, 'max_redirects' => 5]]);
        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) throw new RuntimeException('GitHub non risponde');
        return $body;
    }

    /* ---------- avviso via email (da cron.php, una volta per versione) ---------- */

    public static function cron(): void
    {
        if (empty(PP::$config['update']['notify'] ?? true)) return;
        try { $i = self::check(); } catch (Throwable $e) { return; }
        if (!self::available() || Settings::get('update_notified') === $i['version'] || !function_exists('mail')) return;
        $to = array_column(DB::all("SELECT email FROM pp_users WHERE role = 'admin'"), 'email');
        if (!$to) return;
        $site = PP::$config['site_name'] ?? 'Sito';
        $from = PP::$config['mail_from'] ?? ('noreply@' . preg_replace('~^www\.~', '', parse_url(PP::origin(), PHP_URL_HOST) ?: 'localhost'));
        $body = "Per $site è disponibile PhalbPress {$i['version']} (installata: " . PP::VERSION . ").\n\n" . trim($i['notes']) . "\n\n"
            . 'Per aggiornare: ' . PP::origin() . '/' . PP::adminSlug() . "/update\n";
        $clean = fn($s) => str_replace(["\r", "\n"], ' ', $s);
        @mail(implode(',', $to), '=?UTF-8?B?' . base64_encode("[$site] Aggiornamento PhalbPress {$i['version']}") . '?=', $body,
            'From: ' . $clean($site) . ' <' . $clean($from) . ">\r\nContent-Type: text/plain; charset=UTF-8");
        Settings::set('update_notified', $i['version']);
    }

    /* ---------- sito del progetto (cartella site/) ---------- */

    public static function siteUploadAllowed(): bool
    {
        return (PP::$config['site_upload'] ?? true) !== false;
    }

    /** Carica una versione nuova della cartella site/ da uno zip: prima ne fa una copia, config.php resta. */
    private static function siteUpload(): string
    {
        if (!Auth::isAdmin() || !self::siteUploadAllowed()) throw new RuntimeException('Il caricamento del sito è spento (site_upload nel config).');
        $hash = (string)DB::val('SELECT pass_hash FROM pp_users WHERE id = ?', [(int)(Auth::user()['id'] ?? 0)]);
        if ($hash === '' || !password_verify((string)Admin::post('password'), $hash)) throw new RuntimeException('Password errata: il sito non è stato toccato.');
        $f = $_FILES['site'] ?? [];
        if (in_array($f['error'] ?? 0, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) throw new RuntimeException('Lo zip è più grande di quanto il server accetta (' . ini_get('upload_max_filesize') . ').');
        if (empty($f['tmp_name']) || !is_uploaded_file($f['tmp_name'])) throw new RuntimeException('Scegli il file .zip del sito.');
        require_once PP_ROOT . '/core/SitePackage.php';
        @set_time_limit(300);
        // la versione di adesso, per tornare indietro (si trova in Backup e modelli)
        $copy = SitePackage::snapshot(PP_SITE, Backup::dir(), 'sito-' . Media::slug(PP::$config['site_name'] ?? 'sito') . '-' . date('Ymd-His') . '.zip');
        $n = SitePackage::extract($f['tmp_name'], PP_SITE, true);
        $msg = "Sito del progetto aggiornato ($n file). Copia della versione di prima: " . basename($copy) . '.';
        if (Admin::post('follow') && !SitePackage::configFollowsSample(PP_SITE)) {
            $old = SitePackage::followSample(PP_SITE, Backup::dir());
            $msg .= " config.php ora prende tutto da config.sample.php (il vecchio è in data/backups/$old).";
        }
        Auth::log('site upload', basename($copy));
        return $msg . ' Al prossimo caricamento della pagina il sito usa la versione nuova; per i contenuti iniziali nuovi: Backup e modelli → Azzera il sito.';
    }

    /** Dopo un caricamento del sito: moduli del sito aggiornati, identità e pagine statiche rifatte (alla richiesta dopo, col codice nuovo). */
    public static function afterSite(): void
    {
        if (empty($_SESSION['pp_site_uploaded'])) return;
        unset($_SESSION['pp_site_uploaded']);
        Modules::migrate();
        try { Hooks::fire('saved', 'site'); } catch (Throwable $e) {}
        if (class_exists('Identity')) Identity::write();
    }

    /* ---------- gestione ---------- */

    public static function admin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = (string)Admin::post('action');
            try {
                if ($act === 'check') { $i = self::check(true); Admin::flash(self::available() ? "Disponibile PhalbPress {$i['version']}." : 'Il motore è già aggiornato.'); }
                if ($act === 'update') { $r = self::apply(); Auth::log("update {$r['from']} → {$r['to']}"); Admin::flash("Aggiornato da {$r['from']} a {$r['to']}. Copia di sicurezza: {$r['backup']}."); }
                if ($act === 'site') { $m = self::siteUpload(); $_SESSION['pp_site_uploaded'] = 1; Admin::flash($m); }
                if ($act === 'rollback') { $r = self::rollback(); Auth::log("update rollback {$r['from']} → {$r['to']}"); Admin::flash("Rimessa la versione {$r['to']}. Se l'aggiornamento aveva cambiato il database, puoi ripristinare la copia di sicurezza da Backup e modelli."); }
            } catch (Throwable $e) {
                Admin::flash($e->getMessage(), 'error');
                Auth::log('update errore: ' . mb_substr($e->getMessage(), 0, 150));
            }
            PP::redirect(Admin::u('update'));
        }
        self::afterSite();
        $after = self::after();
        if ($after) Admin::flash('Aggiornamento completato: ' . $after . '.');
        $error = '';
        try { self::check(); } catch (Throwable $e) { $error = $e->getMessage(); }
        require_once PP_ROOT . '/core/SitePackage.php';
        Admin::view(__DIR__ . '/admin.php', ['title' => 'Aggiornamenti', 'info' => self::info(), 'available' => self::available(), 'error' => $error, 'stale' => self::stale(),
            'last' => (array)Settings::get('update_last', []), 'reqs' => self::requirements(), 'flash_after' => $after,
            'siteUpload' => self::siteUploadAllowed(), 'follows' => SitePackage::configFollowsSample(PP_SITE)]);
    }
}
