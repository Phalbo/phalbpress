<?php
/**
 * Installazione guidata, prima che esista site/config.php (la chiama install.php).
 *   1. controllo del server (PHP, estensioni, cartelle scrivibili)
 *   2. il sito del progetto: lo zip preparato per il progetto (cartella site/), oppure un sito nuovo con le lingue scelte qui
 *   3. database: prova la connessione e scrive site/config.php
 * Poi install.php prosegue come sempre (contenuti iniziali e primo amministratore).
 * Funziona senza il resto del motore: niente database, niente moduli, solo PHP.
 */
final class Setup
{
    /** Lingue proposte per un sito nuovo (codice => [nome nella lingua, inglese, locale]). */
    private const LANGS = [
        'en' => ['English', 'English', 'en_GB'], 'it' => ['Italiano', 'Italian', 'it_IT'], 'fr' => ['Français', 'French', 'fr_FR'],
        'de' => ['Deutsch', 'German', 'de_DE'], 'es' => ['Español', 'Spanish', 'es_ES'], 'pt' => ['Português', 'Portuguese', 'pt_PT'],
        'el' => ['Ελληνικά', 'Greek', 'el_GR'], 'ro' => ['Română', 'Romanian', 'ro_RO'], 'bg' => ['Български', 'Bulgarian', 'bg_BG'],
        'pl' => ['Polski', 'Polish', 'pl_PL'], 'sl' => ['Slovenščina', 'Slovenian', 'sl_SI'], 'hr' => ['Hrvatski', 'Croatian', 'hr_HR'],
        'sr' => ['Srpski', 'Serbian', 'sr_RS'], 'bs' => ['Bosanski', 'Bosnian', 'bs_BA'], 'sq' => ['Shqip', 'Albanian', 'sq_AL'],
        'nl' => ['Nederlands', 'Dutch', 'nl_NL'], 'hu' => ['Magyar', 'Hungarian', 'hu_HU'], 'cs' => ['Čeština', 'Czech', 'cs_CZ'],
    ];

    private static string $root = '';
    private static array $errors = [];

    public static function run(string $root): void
    {
        self::$root = $root;
        session_name('pp_setup');
        session_set_cookie_params(['path' => self::base() . '/install.php', 'httponly' => true, 'samesite' => 'Strict', 'secure' => self::https()]);
        session_start();
        if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
        header('X-Frame-Options: DENY');
        header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'none'; form-action 'self'; base-uri 'none'");
        header('Referrer-Policy: no-referrer');

        $checks = self::checks();
        $blocking = array_filter($checks, fn($c) => !$c['ok'] && $c['need']);
        $hasSite = is_file($root . '/site/config.sample.php');
        $step = $blocking ? 'checks' : (!$hasSite ? 'site' : 'db');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$blocking) {
            if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) self::$errors[] = 'Sessione scaduta: ricarica la pagina e riprova.';
            else try {
                $act = (string)($_POST['action'] ?? '');
                if ($act === 'site_zip') { self::importSite($_FILES['site'] ?? []); self::redirect(); }
                if ($act === 'site_new') { self::newSite($_POST); self::redirect(); }
                if ($act === 'db' && $hasSite) { self::writeConfig($_POST); self::redirect(); }
            } catch (Throwable $e) { self::$errors[] = $e->getMessage(); }
        }
        self::render($step, $checks);
    }

    /* ---------- 1. controllo del server ---------- */

    private static function checks(): array
    {
        $r = self::$root;
        $w = fn(string $d) => is_dir($r . '/' . $d) ? is_writable($r . '/' . $d) : is_writable($r);
        $gdWebp = function_exists('imagewebp');
        return [
            ['label' => 'PHP 8.1 o più recente (questo server: ' . PHP_VERSION . ')', 'ok' => PHP_VERSION_ID >= 80100, 'need' => true],
            ['label' => 'Database MySQL/MariaDB (pdo_mysql)', 'ok' => extension_loaded('pdo_mysql'), 'need' => !extension_loaded('pdo_sqlite')],
            ['label' => 'Database SQLite per le prove (pdo_sqlite)', 'ok' => extension_loaded('pdo_sqlite'), 'need' => false],
            ['label' => 'Testi in tutte le lingue (mbstring)', 'ok' => extension_loaded('mbstring'), 'need' => true],
            ['label' => 'Immagini e WebP (gd con WebP)', 'ok' => $gdWebp, 'need' => false, 'hint' => 'senza, le foto si caricano così come sono'],
            ['label' => 'Zip (backup, modelli, aggiornamenti)', 'ok' => class_exists('ZipArchive'), 'need' => true],
            ['label' => 'Firma degli aggiornamenti (sodium)', 'ok' => function_exists('sodium_crypto_sign_verify_detached'), 'need' => false, 'hint' => 'senza, gli aggiornamenti dalla gestione non partono'],
            ['label' => 'Date nella lingua del sito (intl)', 'ok' => class_exists('IntlDateFormatter'), 'need' => false],
            ['label' => 'Raggiungere GitHub per gli aggiornamenti', 'ok' => function_exists('curl_init') || ini_get('allow_url_fopen'), 'need' => false],
            ['label' => 'Cartella site/ scrivibile', 'ok' => $w('site'), 'need' => true],
            ['label' => 'Cartella uploads/ scrivibile', 'ok' => $w('uploads'), 'need' => true],
            ['label' => 'Cartella data/ scrivibile', 'ok' => $w('data'), 'need' => true],
        ];
    }

    /* ---------- 2. il sito del progetto ---------- */

    /** Lo zip del sito (preparato per il progetto): si prende solo la cartella site/, ovunque stia nello zip. */
    private static function importSite(array $f): void
    {
        if (in_array($f['error'] ?? 0, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || (empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0))
            throw new RuntimeException('Lo zip è più grande di quanto il server accetta (' . ini_get('upload_max_filesize') . '). Su SiteGround: Site Tools → Devs → PHP Manager → upload_max_filesize e post_max_size, almeno 64M.');
        if (empty($f['tmp_name']) || !is_uploaded_file($f['tmp_name'])) throw new RuntimeException('Scegli il file .zip del sito.');
        require_once __DIR__ . '/SitePackage.php';
        $dest = self::$root . '/site';
        $count = SitePackage::extract($f['tmp_name'], $dest);
        if (!is_file($dest . '/config.sample.php')) throw new RuntimeException('Lo zip non si è scompattato: controlla i permessi della cartella site/.');
        $_SESSION['flash'] = "Sito del progetto caricato ($count file).";
    }

    /** Un sito nuovo senza zip: config.sample.php scritto qui con nome e lingue. Il resto si cambia poi dalla gestione. */
    private static function newSite(array $p): void
    {
        $name = trim(strip_tags((string)($p['site_name'] ?? '')));
        if ($name === '') throw new RuntimeException('Scrivi il nome del sito.');
        $codes = array_values(array_intersect(array_keys(self::LANGS), (array)($p['langs'] ?? [])));
        $master = (string)($p['master'] ?? '');
        if (!in_array($master, $codes, true)) array_unshift($codes, $master = in_array($master, array_keys(self::LANGS), true) ? $master : 'en');
        $codes = array_values(array_unique(array_merge([$master], $codes)));
        $langs = '';
        foreach ($codes as $c) {
            [$native, $english, $locale] = self::LANGS[$c];
            $langs .= sprintf("        %s => ['native' => %s, 'label' => %s, 'english' => %s, 'locale' => %s, 'hreflang' => %s],\n",
                var_export($c, true), var_export($native, true), var_export(strtoupper($c), true), var_export($english, true), var_export($locale, true), var_export($c, true));
        }
        $email = filter_var(trim((string)($p['contact_email'] ?? '')), FILTER_VALIDATE_EMAIL) ?: '';
        $php = "<?php\n/** Configurazione del sito " . str_replace('*/', '', $name) . ", scritta dall'installazione il " . date('Y-m-d') . ".\n"
            . " * Contatti, social, traduzione, privacy, colori e logo si cambiano dalla gestione. */\nreturn [\n"
            . "    'site_name' => " . var_export($name, true) . ",\n"
            . "    'timezone' => 'Europe/Rome',\n"
            . "    'master_language' => " . var_export($master, true) . ",\n"
            . "    'languages' => [\n" . $langs . "    ],\n"
            . "    'sections' => ['news' => 'news', 'stories' => 'stories', 'results' => 'results', 'partners' => 'partners'],\n"
            . "    'section_menu' => ['news' => 3, 'results' => 5, 'partners' => 6],\n"
            . "    'theme' => " . var_export(preg_replace('~[^a-z0-9_-]~', '', (string)($p['theme'] ?? 'tipografico')) ?: 'tipografico', true) . ",\n"
            . "    'types' => [\n"
            . "        'page' => ['label' => 'Pagine', 'one' => 'pagina'],\n"
            . "        'news' => ['label' => 'News', 'one' => 'news', 'fields' => ['category' => ['label' => 'Categoria', 'type' => 'entry', 'entry_type' => 'category']]],\n"
            . "        'story' => ['label' => 'Storie', 'one' => 'storia', 'fields' => ['country' => 'Paese', 'role' => ['label' => 'Ruolo', 'translatable' => true], 'anonymous' => ['label' => 'Senza foto né nome completo', 'type' => 'checkbox']]],\n"
            . "        'category' => ['label' => 'Categorie', 'one' => 'categoria'],\n"
            . "        'partner' => ['label' => 'Partner', 'one' => 'partner', 'fields' => ['country' => 'Paese', 'short' => 'Sigla', 'url' => ['label' => 'Sito', 'type' => 'url'], 'role' => ['label' => 'Ruolo', 'type' => 'select', 'options' => ['Coordinator' => 'Capofila', 'Partner' => 'Partner']]]],\n"
            . "        'block' => ['label' => 'Fasce della home', 'one' => 'fascia'],\n"
            . "    ],\n"
            . "    'doc_categories' => ['deliverable' => 'Deliverable', 'report' => 'Report', 'toolkit' => 'Toolkit', 'press' => 'Comunicati'],\n"
            . "    'contact_email' => " . var_export($email, true) . ",\n"
            . "    'image_max_px' => 2400, 'image_quality' => 82, 'image_format' => 'webp', 'upload_max_mb' => 20,\n"
            . "    'revisions_keep' => 10,\n"
            . "];\n";
        if (!is_dir(self::$root . '/site')) mkdir(self::$root . '/site', 0775, true);
        if (file_put_contents(self::$root . '/site/config.sample.php', $php) === false) throw new RuntimeException('Non riesco a scrivere in site/: controlla i permessi.');
        $_SESSION['flash'] = 'Sito nuovo preparato.';
    }

    /* ---------- 3. database e site/config.php ---------- */

    private static function writeConfig(array $p): void
    {
        $driver = ($p['driver'] ?? 'mysql') === 'sqlite' ? 'sqlite' : 'mysql';
        if ($driver === 'mysql') {
            $db = ['driver' => 'mysql', 'host' => trim((string)($p['host'] ?? '')) ?: 'localhost', 'port' => (int)($p['port'] ?? 0) ?: 3306,
                'name' => trim((string)($p['name'] ?? '')), 'user' => trim((string)($p['user'] ?? '')), 'pass' => (string)($p['pass'] ?? '')];
            if ($db['name'] === '' || $db['user'] === '') throw new RuntimeException('Scrivi nome del database e utente.');
            try {
                $pdo = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $db['name']), $db['user'], $db['pass'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8]);
                $pdo->exec('CREATE TABLE IF NOT EXISTS pp_setup_test (id INT)');
                $pdo->exec('DROP TABLE pp_setup_test');
                $users = $pdo->query("SHOW TABLES LIKE 'pp_users'")->fetchColumn();
                if ($users && (int)$pdo->query('SELECT COUNT(*) FROM pp_users')->fetchColumn() > 0)
                    throw new RuntimeException('Questo database contiene già un altro sito PhalbPress. Usa un database nuovo e vuoto.');
            } catch (PDOException $e) {
                $m = $e->getMessage();
                $hint = match (true) {
                    str_contains($m, '1045') => 'utente o password sbagliati. Su SiteGround nome del database e utente hanno davanti il prefisso dell’account (es. u12345_).',
                    str_contains($m, '1049') => 'il database non esiste. Controlla il nome, compreso il prefisso.',
                    str_contains($m, '1044'), str_contains($m, '1142') => 'l’utente non ha i permessi su questo database: aggiungilo al database con tutti i privilegi.',
                    str_contains($m, '2002'), str_contains($m, '2005') => 'il server del database non risponde. Su SiteGround l’host è localhost.',
                    default => $m,
                };
                throw new RuntimeException('Connessione non riuscita: ' . $hint);
            }
        } else {
            $db = ['driver' => 'sqlite', 'file' => '__DATA__'];
        }
        $slug = strtolower(trim((string)($p['admin_slug'] ?? ''), " /"));
        if (!preg_match('~^[a-z0-9][a-z0-9-]{2,40}$~', $slug)) throw new RuntimeException('Indirizzo della gestione: solo lettere minuscole, numeri e trattini (almeno 3).');
        if (in_array($slug, ['admin', 'core', 'site', 'uploads', 'data', 'download', 'form', 'search'], true)) throw new RuntimeException('Scegli un indirizzo della gestione meno prevedibile di “' . $slug . '”.');
        $url = rtrim(trim((string)($p['site_url'] ?? '')), '/');
        if ($url !== '' && !preg_match('~^https?://[^\s/]+(/[^\s]*)?$~', $url)) throw new RuntimeException('Indirizzo del sito non valido (es. https://progetto.eu).');
        // vuoto: quello da cui si sta installando (canonical, sitemap ed email non devono dipendere da chi visita)
        $host = preg_replace('~[^a-z0-9.:-]~i', '', (string)($_SERVER['HTTP_HOST'] ?? ''));
        if ($url === '' && $host !== '') $url = (self::https() ? 'https://' : 'http://') . $host . self::base();
        $over = ['db' => $db, 'admin_slug' => $slug, 'site_url' => $url, 'debug' => false];
        $export = var_export($over, true);
        $export = str_replace("'__DATA__'", "__DIR__ . '/../data/site.sqlite'", $export);
        $php = "<?php\n/** Scritto dall'installazione il " . date('Y-m-d H:i') . ": database e indirizzo della gestione.\n"
            . " * Tutto il resto viene da config.sample.php (struttura del sito) e dalla gestione (contatti, social, colori, privacy). */\n"
            . "\$c = require __DIR__ . '/config.sample.php';\nreturn array_replace(\$c, " . $export . ");\n";
        $file = self::$root . '/site/config.php';
        if (is_file($file)) throw new RuntimeException('site/config.php esiste già.');
        if (file_put_contents($file, $php, LOCK_EX) === false) throw new RuntimeException('Non riesco a scrivere site/config.php: controlla i permessi della cartella site/.');
        @chmod($file, 0640);
    }

    /* ---------- pagina ---------- */

    private static function render(string $step, array $checks): void
    {
        $flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
        $e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        $csrf = '<input type="hidden" name="csrf" value="' . $e($_SESSION['csrf']) . '">';
        $themes = [];
        foreach (glob(self::$root . '/pp-themes/*/theme.json') as $f) {
            $slug = basename(dirname($f));
            if ($slug[0] === '_') continue;
            $j = json_decode((string)file_get_contents($f), true) ?: [];
            $themes[$slug] = [$j['name'] ?? $slug, $j['order'] ?? 99];
        }
        uasort($themes, fn($a, $b) => $a[1] <=> $b[1]);
        $sample = $step === 'db' ? (string)@file_get_contents(self::$root . '/site/config.sample.php') : '';
        preg_match("~'admin_slug'\s*=>\s*'([a-z0-9-]+)'~", $sample, $m);
        $slug = ($m[1] ?? '') && $m[1] !== 'admin' ? $m[1] : 'gestione-' . substr(bin2hex(random_bytes(3)), 0, 5);
        preg_match("~'site_name'\s*=>\s*'([^']*)'~", $sample, $n);
        $guess = (self::https() ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '') . self::base();
        $num = ['checks' => 1, 'site' => 2, 'db' => 3][$step];
        $sqliteOk = extension_loaded('pdo_sqlite');
        $mysqlOk = extension_loaded('pdo_mysql');
        ?><!doctype html>
<html lang="it">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Installazione PhalbPress</title>
<link rel="stylesheet" href="<?= $e(self::base() . '/pp-admin/admin.css') ?>"></head>
<body class="login">
<main class="login__box setup">
  <p class="brand"><span class="brand__mark" aria-hidden="true">P</span><span><strong>Installazione</strong><small>Passo <?= $num ?> di 4</small></span></p>
  <?php foreach (self::$errors as $err): ?><p class="flash flash--error" role="alert"><?= $e($err) ?></p><?php endforeach; ?>
  <?php if ($flash): ?><p class="flash" role="status"><?= $e($flash) ?></p><?php endif; ?>

  <details class="setup__checks"<?= $step === 'checks' ? ' open' : '' ?>><summary>Controllo del server</summary>
    <ul class="checks">
      <?php foreach ($checks as $c): ?>
        <li class="<?= $c['ok'] ? 'is-ok' : ($c['need'] ? 'is-bad' : 'is-warn') ?>"><span aria-hidden="true"><?= $c['ok'] ? '✓' : ($c['need'] ? '✕' : '!') ?></span>
          <?= $e($c['label']) ?><?= !$c['ok'] ? ' — ' . ($c['need'] ? 'necessario' : 'facoltativo' . (!empty($c['hint']) ? ': ' . $e($c['hint']) : '')) : '' ?></li>
      <?php endforeach; ?>
    </ul>
  </details>

  <?php if ($step === 'checks'): ?>
    <h1>Il server non è pronto</h1>
    <p>Le voci con la croce servono per installare. Su SiteGround: Site Tools → Devs → PHP Manager (versione ed estensioni); per i permessi delle cartelle, File Manager.</p>
    <p><a class="btn btn--primary" href="<?= $e(self::base() . '/install.php') ?>">Ricontrolla</a></p>

  <?php elseif ($step === 'site'): ?>
    <h1>Il sito del progetto</h1>
    <form method="post" enctype="multipart/form-data" class="stack">
      <?= $csrf ?><input type="hidden" name="action" value="site_zip">
      <label class="field">Zip del sito <span class="muted">(quello preparato per il progetto, con dentro la cartella site/)</span>
        <input type="file" name="site" accept=".zip" required></label>
      <button class="btn btn--primary">Carica il sito</button>
    </form>
    <details><summary>Non ho lo zip: fai un sito nuovo</summary>
      <form method="post" class="stack">
        <?= $csrf ?><input type="hidden" name="action" value="site_new">
        <label class="field">Nome del sito<input type="text" name="site_name" required></label>
        <label class="field">Lingua originale (quella in cui scrivi i testi)
          <select name="master"><?php foreach (self::LANGS as $c => $l): ?><option value="<?= $c ?>"<?= $c === 'en' ? ' selected' : '' ?>><?= $e($l[0]) ?></option><?php endforeach; ?></select></label>
        <fieldset class="field"><legend>Altre lingue</legend>
          <div class="langs-grid"><?php foreach (self::LANGS as $c => $l): ?><label class="check"><input type="checkbox" name="langs[]" value="<?= $c ?>"<?= $c === 'it' ? ' checked' : '' ?>> <?= $e($l[0]) ?></label><?php endforeach; ?></div>
        </fieldset>
        <label class="field">Tema<select name="theme"><?php foreach ($themes as $s => [$tn]): ?><option value="<?= $e($s) ?>"><?= $e($tn) ?></option><?php endforeach; ?></select></label>
        <label class="field">Email di contatto <span class="muted">(facoltativa)</span><input type="email" name="contact_email"></label>
        <button class="btn">Prepara il sito nuovo</button>
      </form>
    </details>

  <?php else: ?>
    <h1>Database<?= !empty($n[1]) ? ' per ' . $e($n[1]) : '' ?></h1>
    <p class="hint">Su SiteGround: Site Tools → Site → MySQL. Crea un database nuovo e vuoto e un utente con tutti i privilegi. Nome e utente hanno davanti il prefisso dell’account (es. <code>u12345_</code>).</p>
    <form method="post" class="stack" autocomplete="off">
      <?= $csrf ?><input type="hidden" name="action" value="db">
      <?php if ($mysqlOk): ?>
        <input type="hidden" name="driver" value="mysql">
        <label class="field">Host<input type="text" name="host" value="<?= $e($_POST['host'] ?? 'localhost') ?>"></label>
        <label class="field">Nome del database<input type="text" name="name" value="<?= $e($_POST['name'] ?? '') ?>"></label>
        <label class="field">Utente<input type="text" name="user" value="<?= $e($_POST['user'] ?? '') ?>"></label>
        <label class="field">Password<input type="password" name="pass" autocomplete="new-password"></label>
        <details><summary>Porta (di solito non serve)</summary><label class="field">Porta<input type="number" name="port" value="<?= $e($_POST['port'] ?? '3306') ?>"></label></details>
      <?php endif; ?>
      <?php if ($sqliteOk): ?>
        <label class="check"><input type="checkbox" name="driver" value="sqlite"<?= !$mysqlOk ? ' checked' : '' ?>> Usa SQLite, un file nella cartella data/ (per prove in locale, senza MySQL)</label>
      <?php endif; ?>
      <label class="field">Indirizzo della gestione <span class="muted">(il sito risponderà su /<?= $e($slug) ?>/)</span>
        <input type="text" name="admin_slug" value="<?= $e($_POST['admin_slug'] ?? $slug) ?>" pattern="[a-z0-9][a-z0-9-]{2,40}" required></label>
      <label class="field">Indirizzo del sito <span class="muted">(quello da cui stai installando; cambialo se il sito avrà un altro dominio)</span>
        <input type="url" name="site_url" value="<?= $e($_POST['site_url'] ?? $guess) ?>" placeholder="<?= $e($guess) ?>"></label>
      <button class="btn btn--primary">Prova la connessione e continua</button>
    </form>
  <?php endif; ?>
</main>
</body>
</html>
<?php
    }

    /* ---------- utilità ---------- */

    private static function base(): string
    {
        $d = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        return $d === '/' || $d === '.' ? '' : rtrim($d, '/');
    }

    private static function https(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    }

    private static function redirect(): never
    {
        header('Location: ' . self::base() . '/install.php', true, 303);
        exit;
    }
}
