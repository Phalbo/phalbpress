<?php
/**
 * PhalbPress — installazione. Crea le tabelle, carica i contenuti iniziali (site/seed.php) e il primo amministratore.
 * Si blocca da sola quando esiste già un utente. Dopo l'installazione puoi cancellare questo file.
 */
require __DIR__ . '/core/bootstrap.php';
Auth::start(PP::base() . '/install.php');
DB::migrate();
Modules::migrate();
$done = (int)DB::val('SELECT COUNT(*) FROM pp_users') > 0;
$error = '';

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
    // testi di interfaccia: prima l'originale, poi le lingue presenti nei valori iniziali
    $machine = ['sl', 'sr', 'sq', 'es']; // valori iniziali scritti senza revisione madrelingua
    foreach (I18n::defaults() as $k => $d) {
        $m = $d['text'][I18n::$master] ?? '';
        if ($m !== '' && !DB::val('SELECT 1 FROM pp_strings WHERE skey = ? AND lang = ?', [$k, I18n::$master])) Content::saveString($k, I18n::$master, $m);
        foreach ($d['text'] as $l => $v) {
            if ($l === I18n::$master || !I18n::valid($l)) continue;
            if (DB::val('SELECT 1 FROM pp_strings WHERE skey = ? AND lang = ?', [$k, $l])) continue;
            // i numeri ("150") valgono per tutte le lingue: non serve tradurli
            Content::saveString($k, $l, $v, in_array($l, $machine, true));
        }
        if (preg_match('~^\d+$~', $m)) foreach (I18n::others() as $l) {
            if (!DB::val('SELECT 1 FROM pp_strings WHERE skey = ? AND lang = ?', [$k, $l])) Content::saveString($k, $l, $m);
        }
    }
}

if (!$done && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::checkCsrf();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $pass = (string)($_POST['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Email non valida.';
    elseif ($e = Auth::strong($pass)) $error = $e;
    else {
        $tpl = $_FILES['template'] ?? null;
        if ($tpl && !empty($tpl['tmp_name']) && is_uploaded_file($tpl['tmp_name']) && class_exists('Backup')) {
            // parti da un modello: contenuti, testi, identità e immagini di un altro sito PhalbPress
            try { Backup::restore($tpl['tmp_name'], true); }
            catch (Throwable $e) { $error = 'Modello non valido: ' . $e->getMessage(); }
        } else {
            pp_seed();
        }
    }
    if (!$error && !$done && $_SERVER['REQUEST_METHOD'] === 'POST' && !Auth::strong($pass) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        DB::insert('pp_users', ['email' => $email, 'name' => trim((string)($_POST['name'] ?? '')), 'pass_hash' => password_hash($pass, PASSWORD_DEFAULT), 'role' => 'admin', 'created_at' => Content::now()]);
        Hooks::fire('saved', 'install'); // scansione cookie, identità, HTML statico
        if (class_exists('Identity')) Identity::write();
        $done = true;
        $justInstalled = true;
    }
}
?><!doctype html>
<html lang="it">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Installazione PhalbPress</title>
<link rel="stylesheet" href="<?= e(PP::url('pp-admin/admin.css')) ?>"></head>
<body class="login">
<main class="login__box">
  <p class="brand"><span class="brand__mark" aria-hidden="true">P</span><span><strong><?= e(PP::$config['site_name']) ?></strong><small>PhalbPress <?= PP::VERSION ?></small></span></p>
  <?php if ($done): ?>
    <h1><?= !empty($justInstalled) ? 'Installato' : 'Già installato' ?></h1>
    <p>Database pronto (<?= e(DB::driver()) ?>). La gestione è su <code>/<?= e(PP::adminSlug()) ?>/</code>. Per sicurezza cancella <code>install.php</code> dal server.</p>
    <p><a class="btn btn--primary" href="<?= e(PP::url(PP::adminSlug() . '/login')) ?>">Vai alla gestione</a> <a class="btn" href="<?= e(PP::url('')) ?>">Apri il sito</a></p>
  <?php else: ?>
    <h1>Installazione</h1>
    <p class="hint">Connessione al database riuscita (<?= e(DB::driver()) ?>). Crea il primo amministratore: verranno caricati anche i contenuti iniziali.</p>
    <?php if ($error): ?><p class="flash flash--error"><?= e($error) ?></p><?php endif; ?>
    <form method="post" class="stack" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= e(Auth::csrf()) ?>">
      <label class="field">Nome<input type="text" name="name" value="<?= e($_POST['name'] ?? '') ?>"></label>
      <label class="field">Email<input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
      <label class="field">Password (almeno 12 caratteri)<input type="password" name="password" minlength="12" required autocomplete="new-password"></label>
      <details><summary>Parti da un modello (facoltativo)</summary>
        <label class="field">File .zip creato da un altro sito PhalbPress in Gestione → Backup e modelli
          <input type="file" name="template" accept=".zip"></label>
        <p class="hint">Senza modello si caricano i contenuti di esempio di <code>site/seed.php</code>.</p></details>
      <button class="btn btn--primary">Installa</button>
    </form>
  <?php endif; ?>
</main>
</body>
</html>
