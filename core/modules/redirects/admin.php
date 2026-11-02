<header class="head"><h1>Redirect</h1></header>
<section class="panel">
  <p class="hint">Un redirect porta chi apre un indirizzo vecchio a quello nuovo. Quando cambi l’indirizzo di una pagina pubblicata, il redirect si crea da solo.
    Usa <strong>301</strong> per gli spostamenti definitivi (Google trasferisce la posizione), <strong>302</strong> per quelli temporanei.</p>
  <form method="post" class="row">
    <?= Admin::csrfField() ?>
    <label class="field grow">Da<input type="text" name="from" placeholder="/it/vecchia-pagina/" required></label>
    <label class="field grow">A<input type="text" name="to" placeholder="/it/nuova-pagina/ oppure https://…" required></label>
    <label class="field">Tipo<select name="code"><option value="301">301 definitivo</option><option value="302">302 temporaneo</option></select></label>
    <button class="btn btn--primary" name="action" value="add">Aggiungi</button>
  </form>
</section>
<section class="panel">
  <?php if ($total): ?>
  <form method="get" class="row filters" role="search">
    <label class="field grow">Cerca negli indirizzi<input type="search" name="q" value="<?= e($q) ?>"></label>
    <label class="field">Mostra<select name="kind"><option value="">Tutti</option>
      <?php foreach (['manual' => 'Aggiunti a mano', 'auto' => 'Automatici', 'used' => 'Già visitati', 'unused' => 'Mai visitati'] as $k => $l): ?><option value="<?= $k ?>" <?= $kind === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></label>
    <button class="btn" type="submit">Filtra</button>
    <?php if ($q !== '' || $kind !== ''): ?><a class="btn btn--small" href="<?= e(Admin::u('redirects')) ?>">Azzera</a><?php endif; ?>
  </form>
  <p class="muted small"><?= (int)$found ?> di <?= (int)$total ?> redirect</p>
  <?= $pager ?>
  <?php endif; ?>
  <?php if (!$rows): ?><p class="empty"><?= $total ? 'Nessun redirect corrisponde ai filtri.' : 'Nessun redirect.' ?></p><?php else: ?>
  <table class="table">
    <thead><tr><th scope="col">Da</th><th scope="col">A</th><th scope="col">Tipo</th><th scope="col" class="num">Visite</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr><td><code><?= e($r['from_path']) ?></code><?= $r['auto'] ? ' <span class="pill">automatico</span>' : '' ?></td><td><code><?= e($r['to_path']) ?></code></td>
        <td><?= (int)$r['code'] ?></td><td class="num"><?= (int)$r['hits'] ?></td>
        <td><form method="post"><?= Admin::csrfField() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn--small btn--danger" name="action" value="delete">Elimina</button></form></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?= $pager ?>
  <?php endif; ?>
</section>
