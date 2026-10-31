<?php
/**
 * PhalbPress — installazione.
 * Senza site/config.php parte l'installazione guidata (core/Setup.php): controllo del server, sito del progetto, database.
 * Poi: tabelle, contenuti iniziali (del progetto, da un modello o di esempio), primo amministratore e le cose da fare dopo.
 * Si blocca da sola quando esiste già un utente; alla fine si può cancellare da qui.
 */
if (!is_file(__DIR__ . '/site/config.php')) {
    require __DIR__ . '/core/Setup.php';
    Setup::run(__DIR__);
    exit;
}
require __DIR__ . '/core/bootstrap.php';
Auth::start(PP::base() . '/install.php');
DB::migrate();
Modules::migrate();
$done = (int)DB::val('SELECT COUNT(*) FROM pp_users') > 0;
$error = '';
$note = '';

/** Immagini del seed: da site/seed-media/ a uploads/media/ (una volta). */
function pp_seed_media(string $rel): string
{
    if ($rel === '' || !str_starts_with($rel, 'seed-media/')) return $rel;
    $src = PP_SITE . '/' . $rel;
    if (!is_file($src)) return '';
    $dst = 'uploads/media/' . date('Y/m') . '/' . basename($rel);
    if (!is_dir(dirname(PP_ROOT . '/' . $dst))) mkdir(dirname(PP_ROOT . '/' . $dst), 0775, true);
    copy($src, PP_ROOT . '/' . $dst);
    $small = preg_replace('~(\.[a-z0-9]+)$~', '-960$1', $src);
    if (is_file($small)) copy($small, preg_replace('~(\.[a-z0-9]+)$~', '-960$1', PP_ROOT . '/' . $dst));
    return $dst;
}

function pp_seed(): void
{
    $now = Content::now();
    $seed = is_file(PP_SITE . '/seed.php') ? require PP_SITE . '/seed.php' : ['entries' => [], 'docs' => []];
    foreach ($seed['entries'] as $e) {
        if (DB::val('SELECT id FROM pp_entries WHERE type = ? AND slug = ?', [$e['type'], $e['slug']])) continue;
        $id = DB::insert('pp_entries', [
            'type' => $e['type'], 'slug' => $e['slug'], 'template' => $e['template'] ?? 'default',
            'menu_order' => $e['menu_order'] ?? 0, 'in_menu' => $e['in_menu'] ?? 0, 'status' => $e['status'] ?? 'published',
            'pub_date' => $e['pub_date'] ?? '', 'image' => pp_seed_media($e['image'] ?? ''), 'meta' => json_encode(array_map(fn($v) => is_string($v) && str_contains($v, 'seed-media/') ? implode("\n", array_map('pp_seed_media', explode("\n", $v))) : $v, $e['meta'] ?? []), JSON_UNESCAPED_UNICODE),
            'created_at' => $now, 'updated_at' => $now,
        ]);
        foreach (array_unique(array_merge([I18n::$master], array_keys($e['tr']))) as $l) {
            if (!isset($e['tr'][$l])) continue;
            // nell'originale lo slug è quello del seed; nelle altre lingue nasce dal titolo tradotto (o da 'slug' nel seed)
            Content::saveText('entry', $id, $l, $e['tr'][$l] + ['excerpt' => '', 'body' => '', 'slug' => $l === I18n::$master && $e['slug'] !== 'home' ? $e['slug'] : ''], in_array($l, $e['auto'] ?? [], true));
        }
    }
    foreach ($seed['docs'] as $d) {
        if (DB::val('SELECT id FROM pp_docs WHERE code = ?', [$d['code']])) continue;
        $id = DB::insert('pp_docs', [
            'code' => $d['code'], 'category' => $d['category'], 'wp' => $d['wp'] ?? '', 'pub_date' => $d['pub_date'] ?? '',
            'file' => '', 'status' => 'published', 'sort' => $d['sort'] ?? 0, 'created_at' => $now, 'updated_at' => $now,
        ]);
        Content::saveText('doc', $id, I18n::$master, $d['tr'][I18n::$master] + ['excerpt' => '', 'body' => '']);
        foreach ($d['tr'] as $l => $f) if ($l !== I18n::$master) Content::saveText('doc', $id, $l, $f + ['excerpt' => '', 'body' => '']);
    }
}

/** Testi di interfaccia: prima l'originale, poi le lingue presenti nei valori iniziali. */
function pp_seed_strings(): void
{
    $machine = ['sl', 'sr', 'sq', 'es']; // valori iniziali scritti senza revisione madrelingua
    foreach (I18n::defaults() as $k => $d) {
        $m = $d['text'][I18n::$master] ?? '';
        if ($m !== '' && !DB::val('SELECT 1 FROM pp_strings WHERE skey = ? AND lang = ?', [$k, I18n::$master])) Content::saveString($k, I18n::$master, $m);
        foreach ($d['text'] as $l => $v) {
            if ($l === I18n::$master || !I18n::valid($l)) continue;
            if (DB::val('SELECT 1 FROM pp_strings WHERE skey = ? AND lang = ?', [$k, $l])) continue;
            Content::saveString($k, $l, $v, in_array($l, $machine, true));
        }
        // i numeri ("150") valgono per tutte le lingue: non serve tradurli
        if (preg_match('~^\d+$~', $m)) foreach (I18n::others() as $l) {
            if (!DB::val('SELECT 1 FROM pp_strings WHERE skey = ? AND lang = ?', [$k, $l])) Content::saveString($k, $l, $m);
        }
    }
}

$hasSeed = is_file(PP_SITE . '/seed.php');
if (!$done && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::checkCsrf();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $pass = (string)($_POST['password'] ?? '');
    $start = (string)($_POST['start'] ?? ($hasSeed ? 'seed' : 'empty'));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Email non valida.';
    elseif ($e = Auth::strong($pass)) $error = $e;
    else {
        try {
            $tpl = $_FILES['template'] ?? null;
            if ($start === 'model') {
                if (!$tpl || empty($tpl['tmp_name']) || !is_uploaded_file($tpl['tmp_name']) || !class_exists('Backup')) throw new RuntimeException('Scegli il file .zip del modello.');
                // parti da un modello: contenuti, testi, identità e immagini di un altro sito PhalbPress
                Backup::restore($tpl['tmp_name'], true);
            } elseif ($start === 'demo' && class_exists('Demo')) {
                Demo::install();
            } elseif ($start === 'seed') {
                pp_seed();
            }
            pp_seed_strings();
            if (!empty($_POST['placeholders']) && $start !== 'demo' && class_exists('Demo')) {
                $n = Demo::placeholders();
                if ($n) $note = "Messe $n foto segnaposto: le trovi cercando “segnaposto” in Immagini e file.";
            }
        } catch (Throwable $e) { $error = ($start === 'model' ? 'Modello non valido: ' : '') . $e->getMessage(); }
        if (!$error) {
            DB::insert('pp_users', ['email' => $email, 'name' => trim((string)($_POST['name'] ?? '')), 'pass_hash' => password_hash($pass, PASSWORD_DEFAULT), 'role' => 'admin', 'created_at' => Content::now()]);
            Hooks::fire('saved', 'install'); // scansione cookie, identità, HTML statico
            if (class_exists('Identity')) Identity::write();
            $done = true;
            $justInstalled = true;
            $_SESSION['installed_by'] = $email;
        }
    }
}

// dopo l'installazione: email di prova e chiusura dell'installazione (solo da chi ha appena installato)
$mine = $done && !empty($_SESSION['installed_by']);
if ($mine && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['after'])) {
    Auth::checkCsrf();
    if ($_POST['after'] === 'mail') {
        $to = $_SESSION['installed_by'];
        $site = PP::$config['site_name'] ?? 'Sito';
        $from = PP::$config['mail_from'] ?? ('noreply@' . preg_replace('~^www\.~', '', parse_url(PP::origin(), PHP_URL_HOST) ?: 'localhost'));
        $ok = function_exists('mail') && @mail($to, '=?UTF-8?B?' . base64_encode("[$site] Email di prova") . '?=',
            "Se leggi questo messaggio, le email del sito $site partono (modulo di contatto, avvisi degli aggiornamenti).\n\n" . PP::origin() . "\n",
            'From: ' . str_replace(["\r", "\n"], '', $site) . ' <' . $from . ">\r\nContent-Type: text/plain; charset=UTF-8");
        $note = $ok ? "Email di prova inviata a $to: controlla anche lo spam." : 'Il server non ha accettato l’email: su SiteGround controlla che l’indirizzo del mittente sia del tuo dominio (Impostazioni del sito).';
    }
    if ($_POST['after'] === 'remove') {
        $gone = [];
        foreach ([__FILE__, __DIR__ . '/phalbpress-installa.php'] as $f) if (is_file($f) && @unlink($f)) $gone[] = basename($f);
        if ($gone) { header('Location: ' . PP::url(PP::adminSlug() . '/login'), true, 303); exit; }
        $note = 'Non riesco a cancellare install.php: toglilo dal File Manager.';
    }
}
$cron = (PHP_BINARY && !str_contains(PHP_BINARY, 'fpm') ? PHP_BINARY : 'php') . ' ' . PP_ROOT . '/cron.php';
?><!doctype html>
<html lang="it">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Installazione PhalbPress</title>
<link rel="stylesheet" href="<?= e(PP::url('pp-admin/admin.css')) ?>"></head>
<body class="login">
<main class="login__box setup">
  <p class="brand"><span class="brand__mark" aria-hidden="true">P</span><span><strong><?= e(PP::$config['site_name'] ?? 'PhalbPress') ?></strong><small>PhalbPress <?= PP::VERSION ?><?= !$done ? ' · passo 4 di 4' : '' ?></small></span></p>
  <?php if ($note): ?><p class="flash" role="status"><?= e($note) ?></p><?php endif; ?>
  <?php if ($done): ?>
    <h1><?= !empty($justInstalled) || $mine ? 'Installato' : 'Già installato' ?></h1>
    <p>La gestione è su <a href="<?= e(PP::url(PP::adminSlug() . '/login')) ?>"><code>/<?= e(PP::adminSlug()) ?>/</code></a>. Salvati questo indirizzo.</p>
    <?php if ($mine): ?>
      <h2>Quando vuoi (tutto facoltativo)</h2>
      <ul class="todo">
        <li><strong>Lavori programmati</strong> (contenuti a orario, avvisi delle nuove versioni): su SiteGround Site Tools → Devs → Cron Jobs, ogni 15 minuti, con questo comando:
          <code><?= e($cron) ?></code></li>
        <li><strong>Email</strong>: verifica che il sito riesca a mandarle (modulo di contatto, avvisi).
          <form method="post"><input type="hidden" name="csrf" value="<?= e(Auth::csrf()) ?>"><button class="btn btn--small" name="after" value="mail">Mandami un’email di prova</button></form></li>
        <li><strong>Accesso in due passaggi</strong> per il tuo utente: <a href="<?= e(PP::url(PP::adminSlug() . '/users')) ?>#totp">Utenti</a>.</li>
        <li><strong>Contatti, social, traduzione automatica</strong>: <a href="<?= e(PP::url(PP::adminSlug() . '/settings')) ?>">Impostazioni del sito</a>. <strong>Titolare e privacy</strong>: <a href="<?= e(PP::url(PP::adminSlug() . '/privacy')) ?>">Privacy e cookie</a>.</li>
        <li><strong>Foto</strong>: i segnaposto si cambiano uno alla volta da <a href="<?= e(PP::url(PP::adminSlug() . '/media')) ?>?q=segnaposto">Immagini e file</a> → Sostituisci.</li>
        <li><strong>Chiudi l’installazione</strong>: cancella questo file dal server.
          <form method="post"><input type="hidden" name="csrf" value="<?= e(Auth::csrf()) ?>"><button class="btn btn--small" name="after" value="remove">Cancella install.php e vai alla gestione</button></form></li>
      </ul>
    <?php else: ?>
      <p>Per sicurezza cancella <code>install.php</code> dal server.</p>
    <?php endif; ?>
    <p><a class="btn btn--primary" href="<?= e(PP::url(PP::adminSlug() . '/login')) ?>">Vai alla gestione</a> <a class="btn" href="<?= e(PP::url('')) ?>">Apri il sito</a></p>
  <?php else: ?>
    <h1>Contenuti e amministratore</h1>
    <p class="hint">Database collegato (<?= e(DB::driver()) ?>).</p>
    <?php if ($error): ?><p class="flash flash--error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post" class="stack" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= e(Auth::csrf()) ?>">
      <fieldset class="field"><legend>Con cosa parte il sito</legend>
        <div class="choices">
          <?php $sel = $_POST['start'] ?? ($hasSeed ? 'seed' : 'demo'); ?>
          <?php if ($hasSeed): ?><label class="check"><input type="radio" name="start" value="seed"<?= $sel === 'seed' ? ' checked' : '' ?>><span><strong>I contenuti del progetto</strong> quelli preparati nel sito caricato (pagine, partner, documenti, fasce della home)</span></label><?php endif; ?>
          <label class="check"><input type="radio" name="start" value="demo"<?= $sel === 'demo' ? ' checked' : '' ?>><span><strong>Contenuti di esempio</strong> news, storie, partner e galleria di un progetto inventato, con le foto segnaposto: per vedere subito il sito pieno e cambiarlo pezzo per pezzo</span></label>
          <label class="check"><input type="radio" name="start" value="model"<?= $sel === 'model' ? ' checked' : '' ?>><span><strong>Un modello</strong> lo zip di un altro sito PhalbPress (Gestione → Backup e modelli)</span></label>
          <label class="check"><input type="radio" name="start" value="empty"<?= $sel === 'empty' ? ' checked' : '' ?>><span><strong>Vuoto</strong> solo la struttura, i contenuti li metti dopo</span></label>
        </div>
      </fieldset>
      <label class="field">Zip del modello <span class="muted">(solo per “Un modello”)</span><input type="file" name="template" accept=".zip"></label>
      <label class="check"><input type="checkbox" name="placeholders" value="1"<?= !isset($_POST['start']) || !empty($_POST['placeholders']) ? ' checked' : '' ?>> Metti le foto segnaposto (con le misure stampate sopra) dove mancano le foto</label>
      <label class="field">Nome<input type="text" name="name" value="<?= e($_POST['name'] ?? '') ?>"></label>
      <label class="field">Email<input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
      <label class="field">Password (almeno 12 caratteri)<input type="password" name="password" minlength="12" required autocomplete="new-password"></label>
      <button class="btn btn--primary">Installa</button>
    </form>
  <?php endif; ?>
</main>
</body>
</html>
