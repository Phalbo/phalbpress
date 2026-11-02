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
        <a class="btn btn--small" href="<?= e(Admin::u('preview') . '?demo=1' . ($pal ? '&palette=1' : '') . '&theme=' . rawurlencode($slug)) ?>" target="_blank" rel="noopener" title="Il tema con news, storie e foto di un progetto inventato: il sito non cambia">con contenuti e foto di esempio ↗</a>
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

<?php if (Auth::isAdmin()): ?>
<section class="panel stack" id="provini">
  <h2>Provini per il cliente</h2>
  <p>Una copia del sito per ogni tema, da navigare come un sito vero, in <code>/<?= e(Proofs::DIR) ?>/</code>: home, pagine, portfolio con le sue categorie, gli ultimi lavori e le ultime news. Il sito pubblico non cambia; i provini non compaiono nei motori di ricerca, ma chi ha il link li vede.</p>
  <?php if (!empty($proofs['at'])): ?>
    <p><span class="badge badge--ok">Pronti</span> <?= count($proofs['themes'] ?? []) ?> temi, <?= (int)($proofs['files'] ?? 0) ?> pagine, del <?= e(substr((string)$proofs['at'], 0, 16)) ?> · <?= !empty($proofs['palette']) ? 'con i colori di ogni tema' : 'con i colori del sito' ?>
      · <a href="<?= e(PP::url(Proofs::DIR . '/')) ?>" target="_blank" rel="noopener">Apri l’indice dei provini ↗</a></p>
    <p class="hint">Da mandare al cliente: <code><?= e(PP::origin() . PP::url(Proofs::DIR . '/')) ?></code>. Se cambi i contenuti, rigenerali.</p>
  <?php endif; ?>
  <form method="post" class="stack"><?= Admin::csrfField() ?>
    <fieldset class="stack"><legend class="field">Colori e caratteri</legend>
      <label class="check"><input type="radio" name="colors" value="theme" checked> Quelli di ogni tema: si vede il carattere di ciascuno</label>
      <label class="check"><input type="radio" name="colors" value="site"> Quelli del sito (Identità visiva): stessi colori, temi diversi</label>
    </fieldset>
    <details><summary>Solo alcuni temi</summary>
      <div class="row" style="flex-wrap:wrap"><?php foreach ($themes as $slug => $t): ?><label class="check small"><input type="checkbox" name="only[]" value="<?= e($slug) ?>"> <?= e($t['name'] ?? $slug) ?></label><?php endforeach; ?></div>
      <p class="hint">Se non ne scegli nessuno si fanno tutti.</p>
    </details>
    <div class="row">
      <button class="btn btn--primary" name="action" value="proofs" data-confirm="Generare i provini? Ci vuole circa un minuto; quelli di prima vengono rifatti.">Genera i provini</button>
      <?php if (!empty($proofs['at'])): ?><button class="btn btn--danger" name="action" value="proofs_clear" data-confirm="Cancellare tutti i provini?">Cancella i provini</button><?php endif; ?>
    </div>
  </form>
</section>
<?php endif; ?>
