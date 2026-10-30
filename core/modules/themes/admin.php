<header class="head"><h1>Temi</h1>
  <div class="head__actions"><a class="btn btn--primary" href="<?= e(Admin::u('visual')) ?>">Modifica visuale</a> <a class="btn" href="<?= e(Admin::u('entries/block')) ?>">Blocchi della home (<?= $blocks ?>)</a></div></header>
<section class="panel">
  <p class="hint">Il tema cambia l’aspetto di tutto il sito: impaginazione, tipografia, menu, fasce colorate. Testi, immagini, menu e traduzioni restano gli stessi.
    I colori e i font sono quelli di <a href="<?= e(Admin::u('identity')) ?>">Identità visiva</a>; ogni tema ne suggerisce alcuni, che puoi applicare attivandolo.
    Prova sempre con <strong>Anteprima</strong> prima di attivare.</p>
  <?php if ($prev): ?>
    <form method="post" class="row"><?= Admin::csrfField() ?><button class="btn btn--small" name="action" value="restore_identity">Ripristina i colori e i font di prima</button></form>
  <?php endif; ?>
</section>
<ul class="themes">
  <?php foreach ($themes as $slug => $t): $on = $slug === $active; $pal = $t['palette'] ?? []; ?>
  <li class="theme panel<?= $on ? ' theme--on' : '' ?>">
    <a class="theme__shot" href="<?= e(Admin::u('preview') . '?theme=' . rawurlencode($slug)) ?>" target="_blank" rel="noopener" aria-label="Anteprima di <?= e($t['name'] ?? $slug) ?>">
      <?php if (is_file(PP_ROOT . '/pp-themes/' . $slug . '/preview.webp')): ?><img src="<?= e(PP::url('pp-themes/' . $slug . '/preview.webp')) ?>?v=<?= filemtime(PP_ROOT . '/pp-themes/' . $slug . '/preview.webp') ?>" alt="" loading="lazy"><?php else: ?><span><?= e($t['name'] ?? $slug) ?></span><?php endif; ?>
    </a>
    <div class="theme__body stack">
      <h2><?= e($t['name'] ?? $slug) ?> <?= $on ? '<span class="badge badge--ok">attivo</span>' : '' ?></h2>
      <p><?= e($t['description'] ?? '') ?></p>
      <?php if (!empty($t['best_for'])): ?><p class="small muted">Per: <?= e($t['best_for']) ?></p><?php endif; ?>
      <?php if ($pal): ?><p class="theme-swatches" aria-label="Colori suggeriti"><?php foreach ($pal as $k => $c): ?><span style="background:<?= e($c) ?>" title="<?= e($k . ' ' . $c) ?>"></span><?php endforeach; ?></p><?php endif; ?>
      <?php if (!empty($t['fonts'])): ?><p class="small">Font suggeriti: <?= e(implode(' + ', array_unique(array_filter([$t['fonts']['display'] ?? '', $t['fonts']['text'] ?? ''])))) ?></p><?php endif; ?>
      <div class="row">
        <a class="btn btn--small" href="<?= e(Admin::u('preview') . '?theme=' . rawurlencode($slug)) ?>" target="_blank" rel="noopener">Anteprima con i tuoi colori ↗</a>
        <?php if ($pal): ?><a class="btn btn--small" href="<?= e(Admin::u('preview') . '?palette=1&theme=' . rawurlencode($slug)) ?>" target="_blank" rel="noopener">con i colori del tema ↗</a><?php endif; ?>
        <?php if (!$on || $pal): ?>
        <form method="post" class="row"><?= Admin::csrfField() ?><input type="hidden" name="theme" value="<?= e($slug) ?>">
          <?php if ($pal): ?><label class="check small"><input type="checkbox" name="apply" value="1" <?= $on ? 'checked' : '' ?>> applica colori e font suggeriti</label><?php endif; ?>
          <button class="btn btn--small btn--primary" name="action" value="activate"><?= $on ? 'Applica' : 'Attiva' ?></button></form>
        <?php endif; ?>
      </div>
    </div>
  </li>
  <?php endforeach; ?>
</ul>
