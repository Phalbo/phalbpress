<header class="head"><h1>Registro attività</h1></header>
<section class="panel">
  <form method="get" class="row filters">
    <label class="field">Utente<select name="user"><option value="">Tutti</option><?php foreach ($users as $u): ?><option value="<?= e($u) ?>" <?= $u === $user ? 'selected' : '' ?>><?= e($u ?: '(sistema)') ?></option><?php endforeach; ?></select></label>
    <label class="field">Azione<select name="action"><option value="">Tutte</option><?php foreach ($actions as $a): ?><option value="<?= e($a) ?>" <?= $a === $act ? 'selected' : '' ?>><?= e($a) ?></option><?php endforeach; ?></select></label>
    <button class="btn">Filtra</button>
  </form>
  <p class="muted small"><?= $total ?> voci</p>
  <table class="table">
    <thead><tr><th scope="col">Quando</th><th scope="col">Chi</th><th scope="col">Cosa</th><th scope="col">Dettaglio</th></tr></thead>
    <tbody><?php foreach ($rows as $r): ?><tr><td class="nowrap"><?= e(substr($r['at'], 0, 16)) ?></td><td><?= e($r['user_email'] ?: '—') ?></td><td><?= e($r['action']) ?></td><td><?= e($r['ref']) ?></td></tr><?php endforeach; ?></tbody>
  </table>
  <?php if ($pages > 1): ?><p class="row"><?php for ($i = 1; $i <= $pages; $i++): ?><a class="btn btn--small" <?= $i === $page ? 'aria-current="page"' : '' ?> href="?<?= e(http_build_query(['user' => $user, 'action' => $act, 'p' => $i])) ?>"><?= $i ?></a><?php endfor; ?></p><?php endif; ?>
  <form method="post" class="mt"><?= Admin::csrfField() ?><button class="btn btn--small" name="action" value="purge" data-confirm="Eliminare le voci più vecchie di 24 mesi?">Elimina le voci oltre 24 mesi</button></form>
</section>
