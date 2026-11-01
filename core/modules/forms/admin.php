<header class="head"><h1>Richieste</h1>
  <div class="head__actions"><?php if ($rows): ?><a class="btn" href="<?= e(Admin::u('forms/csv')) ?>">Scarica CSV</a> <a class="btn" href="<?= e(Admin::u('forms/newsletter')) ?>">Iscritti alla newsletter (CSV)</a><?php endif; ?></div></header>
<section class="panel">
  <p class="hint">Messaggi arrivati dal modulo di contatto (<code>[form:contact]</code> in una pagina) e iscrizioni alla newsletter (blocco della home o <code>[form:newsletter]</code>). Arrivano anche per email a <strong><?= e($to ?: 'nessuno: imposta contact_email') ?></strong>.
    Per la privacy conviene non tenerli più di un anno.</p>
  <?php if (Auth::isAdmin()): ?>
    <form method="post" class="row"><?= Admin::csrfField() ?>
      <label class="field">Mandali a<input type="email" name="to" value="<?= e(Settings::get('forms_to', '')) ?>" placeholder="<?= e(PP::$config['contact_email'] ?? '') ?>"></label>
      <button class="btn" name="action" value="to">Salva</button>
      <button class="btn btn--danger" name="action" value="purge" data-confirm="Eliminare le richieste più vecchie di 12 mesi?">Elimina quelle oltre 12 mesi</button></form>
  <?php endif; ?>
</section>
<section class="panel">
  <?php if (!$rows): ?><p class="empty">Nessuna richiesta.</p><?php endif; ?>
  <ul class="rows">
  <?php foreach ($rows as $r): $d = $r['d']; ?>
    <li class="<?= $r['is_read'] ? '' : 'unread' ?>">
      <div><strong><?= e($d['name'] ?? '') ?></strong> <a href="mailto:<?= e($d['email'] ?? '') ?>"><?= e($d['email'] ?? '') ?></a><?= !empty($d['org']) ? ' · ' . e($d['org']) : '' ?>
        <span class="muted small">· <?= e(substr($r['at'], 0, 16)) ?> · <?= e(strtoupper($r['lang'])) ?> · <?= e($d['page'] ?? '') ?></span>
        <p class="msg"><?= nl2br(e($d['message'] ?? '')) ?></p></div>
      <form method="post" class="row"><?= Admin::csrfField() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
        <button class="btn btn--small" name="action" value="read"><?= $r['is_read'] ? 'Segna da leggere' : 'Segna letta' ?></button>
        <button class="btn btn--small btn--danger" name="action" value="delete" data-confirm="Eliminare questa richiesta?">Elimina</button></form>
    </li>
  <?php endforeach; ?>
  </ul>
</section>
