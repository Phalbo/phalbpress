<header class="head">
  <h1><?= e($title) ?></h1>
  <div class="head__actions"><a class="btn btn--primary" href="<?= e(Admin::u('entry/new/' . $type)) ?>">Aggiungi <?= e(PP::$config['types'][$type]['one']) ?></a></div>
</header>
<section class="panel">
<?php if (!$rows): ?>
  <p class="empty">Nessun contenuto. <a href="<?= e(Admin::u('entry/new/' . $type)) ?>">Aggiungi il primo</a>.</p>
<?php else: ?>
  <table class="table">
    <thead><tr>
      <th scope="col">Titolo</th><th scope="col">Stato</th>
      <?php if (in_array($type, ['news', 'story'], true)): ?><th scope="col">Data</th><?php else: ?><th scope="col" class="num">Ordine</th><?php endif; ?>
      <th scope="col">Lingue</th>
    </tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><a class="strong" href="<?= e(Admin::u('entry/' . $r['id'] . '/' . I18n::$master)) ?>"><?= e($r['title'] ?: 'Senza titolo') ?></a>
          <div class="muted small">/<?= e($r['slug']) ?><?= $r['in_menu'] ? ' · nel menu' : '' ?></div></td>
        <td><?= $r['status'] === 'published' ? '<span class="pill pill--on">Pubblicato</span>' : '<span class="pill">Bozza</span>' ?></td>
        <?php if (in_array($type, ['news', 'story'], true)): ?><td class="nowrap"><?= e($r['pub_date']) ?></td><?php else: ?><td class="num"><?= (int)$r['menu_order'] ?></td><?php endif; ?>
        <td><div class="dots">
          <?php foreach ($r['statuses'] as $l => $s): ?>
            <a href="<?= e(Admin::u('entry/' . $r['id'] . '/' . $l)) ?>" class="dot dot--<?= e($s) ?>" title="<?= e(I18n::name($l) . ': ' . I18n::statusLabel($s)) ?>"><?= e(I18n::label($l)) ?><span class="sr-only">: <?= e(I18n::statusLabel($s)) ?></span></a>
          <?php endforeach; ?>
        </div></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
</section>
<p class="legend"><span class="dot dot--master">EN</span> originale <span class="dot dot--ok">IT</span> rivista <span class="dot dot--auto">IT</span> automatica <span class="dot dot--outdated">IT</span> da aggiornare <span class="dot dot--missing">IT</span> mancante</p>
