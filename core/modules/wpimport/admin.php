<?php
$steps = WpImport::STEPS;
$cur = array_search($state['step'], $steps, true);
$noteLabels = ['missing' => 'File non trovati', 'media_error' => 'File non importati', 'entry_error' => 'Contenuti con errori', 'no_master' => 'Senza la lingua principale (importati come bozza)', 'menu' => 'Menu', 'static' => 'Sito statico'];
?>
<header class="head"><h1>Importa da WordPress</h1></header>

<?php if ($running): ?>
<section class="panel stack">
  <h2>Importazione in corso</h2>
  <p><strong><?= e(WpImport::label($state['step'])) ?></strong>
    <?php if (!empty($state['total'])): ?> · <?= (int)$state['cursor'] ?> di <?= (int)$state['total'] ?><?php endif; ?></p>
  <p class="hint">Non chiudere la pagina: il lavoro va avanti a passi di 20 secondi e riparte da solo. Se si ferma, premi Continua: riprende da dove era rimasto.</p>
  <form method="post" data-autosubmit>
    <?= Admin::csrfField() ?><input type="hidden" name="action" value="run">
    <button class="btn btn--primary">Continua</button>
  </form>
</section>
<?php return; endif; ?>

<section class="panel stack">
  <h2>1. I file</h2>
  <p class="hint">Metti nella cartella <code>data/wp-import/</code> del sito (con l’FTP o il File Manager di SiteGround):</p>
  <ul class="rows">
    <li><span><?= !empty($files['xml']) ? '<span class="badge badge--ok">OK</span> ' . e(basename($files['xml'])) : '<span class="badge badge--missing">Manca</span>' ?>
      <br><span class="small muted">L’esportazione di WordPress: Strumenti → Esporta → Tutti i contenuti. Con WPML scegli prima “Tutte le lingue” nella barra in alto.</span></span></li>
    <li><span><?= !empty($files['sql']) ? '<span class="badge badge--ok">OK</span> ' . e(basename($files['sql'])) : '<span class="badge badge--auto">Facoltativo</span>' ?>
      <br><span class="small muted">Il dump del database (phpMyAdmin → Esporta). Serve per abbinare le traduzioni di WPML e portare i redirect: si leggono solo quelle tabelle, mai utenti e password.</span></span></li>
    <li><span><?= !empty($files['zip']) ? '<span class="badge badge--ok">OK</span> ' . e(basename($files['zip'])) . ' · ' . e(Media::human((int)filesize($files['zip']))) : '<span class="badge badge--auto">Facoltativo</span>' ?>
      <br><span class="small muted">Uno zip della cartella wp-content/uploads (bastano gli originali, senza le miniature). Senza, foto e file si scaricano dal vecchio sito, uno ogni mezzo secondo.</span></span></li>
  </ul>
  <form method="post" class="row">
    <?= Admin::csrfField() ?>
    <button class="btn btn--primary" name="action" value="plan" <?= empty($files['xml']) ? 'disabled' : '' ?>>Prova a secco</button>
    <span class="hint">Legge i file e dice cosa verrebbe importato, senza toccare il sito.</span>
  </form>
</section>

<?php if ($plan): ?>
<section class="panel stack">
  <h2>2. Cosa verrebbe importato</h2>
  <p class="hint">Dal sito <strong><?= e($plan['base']) ?></strong><?= $plan['wpml'] ? ', con le traduzioni di WPML' : '' ?>.</p>
  <table class="table">
    <thead><tr><th scope="col">Tipo</th><th scope="col" class="num">Contenuti</th><th scope="col" class="num">Pubblicati</th><th scope="col">Lingue</th></tr></thead>
    <tbody>
      <?php foreach ($plan['by_type'] as $type => $r): ?>
        <tr><td><?= e(PP::$config['types'][$type]['label'] ?? $type) ?></td><td class="num"><?= (int)$r['total'] ?></td><td class="num"><?= (int)($r['published'] ?? 0) ?></td>
          <td><?php foreach ($r['langs'] ?? [] as $l => $n): ?><span class="pill"><?= e(strtoupper($l)) ?> <?= (int)$n ?></span> <?php endforeach; ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <ul class="rows">
    <li><span>Foto e file usati: <strong><?= (int)$plan['media_needed'] ?></strong> · nello zip <?= (int)$plan['media_in_zip'] ?>
      <?php if ($plan['media_missing']): ?> · <strong><?= count($plan['media_missing']) ?></strong> da scaricare o mancanti<?php endif; ?></span></li>
    <?php if ($plan['terms']): ?><li><span>Categorie: <?php foreach ($plan['terms'] as $t => $n): ?><?= e($t) ?> <?= (int)$n ?> <?php endforeach; ?></span></li><?php endif; ?>
    <li><span>Redirect del plugin Redirection: <?= (int)$plan['redirection'] ?>, più quelli automatici da ogni indirizzo vecchio</span></li>
  </ul>
  <details><summary>Tipi di WordPress e come vengono presi</summary>
    <p class="hint">Si cambiano nel config del sito, chiave <code>wp_import['types']</code>.</p>
    <ul class="rows"><?php foreach ($plan['wp_types'] as $t => $n): ?><li><span><code><?= e($t) ?></code> <?= (int)$n ?> → <?= isset($plan['map'][$t]) ? '<strong>' . e($plan['map'][$t]) . '</strong>' : '<span class="muted">non importato</span>' ?></span></li><?php endforeach; ?></ul>
  </details>
  <?php if ($plan['no_master']): ?><details><summary><?= count($plan['no_master']) ?> contenuti senza la lingua principale (diventano bozze)</summary>
    <ul class="rows"><?php foreach (array_slice($plan['no_master'], 0, 100) as $t): ?><li><span><?= e($t) ?></span></li><?php endforeach; ?></ul></details><?php endif; ?>
  <?php if ($plan['skipped']): ?><details><summary><?= count($plan['skipped']) ?> contenuti lasciati fuori</summary>
    <ul class="rows"><?php foreach (array_slice($plan['skipped'], 0, 150) as $s): ?><li><span><?= e($s['title'] ?: '#' . $s['id']) ?> <span class="muted small"><?= e($s['type']) ?> · <?= e($s['why']) ?></span></span></li><?php endforeach; ?></ul></details><?php endif; ?>
  <?php if ($plan['dropped'] || $plan['unknown']): ?><details><summary>Elementi del vecchio costruttore di pagine</summary>
    <p class="hint">Tolti perché erano solo grafica o elenchi automatici (si rifanno coi blocchi e con le pagine del tema):</p>
    <p><?php foreach ($plan['dropped'] as $k => $n): ?><span class="pill"><?= e($k) ?> <?= (int)$n ?></span> <?php endforeach; ?></p>
    <?php if ($plan['unknown']): ?><p class="hint">Non riconosciuti: se ne tiene solo il testo.</p>
      <p><?php foreach ($plan['unknown'] as $k => $n): ?><span class="pill"><?= e($k) ?> <?= (int)$n ?></span> <?php endforeach; ?></p><?php endif; ?>
  </details><?php endif; ?>
  <?php if ($plan['media_missing']): ?><details><summary>File non presenti nello zip</summary>
    <ul class="rows"><?php foreach (array_slice($plan['media_missing'], 0, 200) as $f): ?><li><span><code><?= e($f) ?></code></span></li><?php endforeach; ?></ul></details><?php endif; ?>
</section>

<section class="panel stack">
  <h2>3. Importa</h2>
  <p class="hint">Si può rilanciare quante volte vuoi: quello già importato viene aggiornato, non duplicato. Il sito vecchio non viene toccato.
    Le pagine importate prendono la disposizione del tema; la home si fa con le fasce.</p>
  <ol class="steps">
    <?php foreach ($steps as $i => $st): if ($st === 'done') continue; ?>
      <li class="<?= $cur !== false && $i < $cur ? 'is-done' : ($i === $cur ? 'is-current' : '') ?>"><?= e(WpImport::label($st)) ?></li>
    <?php endforeach; ?>
  </ol>
  <form method="post" class="row">
    <?= Admin::csrfField() ?>
    <button class="btn btn--primary" name="action" value="run"><?= $state['step'] === 'done' ? 'Importa di nuovo' : ($cur ? 'Continua l’importazione' : 'Importa') ?></button>
    <?php if ($state['started']): ?><button class="btn" name="action" value="reset">Ricomincia da capo</button><?php endif; ?>
  </form>
</section>
<?php endif; ?>

<?php if (!empty($state['finished']) || !empty($state['counts'])): ?>
<section class="panel stack">
  <h2>Risultato</h2>
  <p><?php foreach (['entries' => 'contenuti nuovi', 'terms' => 'categorie', 'media' => 'foto e file', 'seo' => 'titoli e descrizioni SEO', 'menu' => 'voci di menu', 'redirects' => 'redirect'] as $k => $l): ?>
    <?php if (!empty($state['counts'][$k])): ?><span class="pill"><?= (int)$state['counts'][$k] ?> <?= e($l) ?></span> <?php endif; ?>
  <?php endforeach; ?></p>
  <?php foreach ($state['notes'] ?? [] as $k => $list): ?>
    <details><summary><?= e($noteLabels[$k] ?? $k) ?> (<?= count($list) ?>)</summary>
      <ul class="rows"><?php foreach ($list as $n): ?><li><span class="small"><?= e($n) ?></span></li><?php endforeach; ?></ul></details>
  <?php endforeach; ?>
  <form method="post" class="row">
    <?= Admin::csrfField() ?>
    <button class="btn btn--danger btn--small" name="action" value="undo" data-confirm="Togliere tutti i contenuti e i file importati da WordPress? Le modifiche fatte dopo su quei contenuti si perdono.">Togli tutto quello importato</button>
  </form>
</section>
<?php endif; ?>
