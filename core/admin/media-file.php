<header class="head">
  <div><p class="crumbs"><a href="<?= e(Admin::u('media')) ?>">Immagini e file</a></p><h1><?= e($title) ?></h1></div>
  <div class="head__actions"><a class="btn" href="<?= e(upload_url($file)) ?>" target="_blank" rel="noopener">Apri ↗</a></div>
</header>
<div class="cols">
  <section class="panel stack">
    <?php if ($img || str_ends_with($file, '.svg')): ?><img src="<?= e(upload_url($file)) ?>" alt="" class="preview-img"><?php endif; ?>
    <p><code><?= e($file) ?></code> <button type="button" class="btn btn--small" data-copy="<?= e($file) ?>">Copia percorso</button></p>
    <p class="muted"><?= Media::human($size) ?><?= $w ? ' · ' . $w . '×' . $h . ' px' : '' ?> · caricato il <?= e(date('d.m.Y H:i', $mtime)) ?></p>
  </section>
  <section class="panel stack">
    <h2>Dove è usato</h2>
    <?php if (!$usage): ?><p class="muted">In nessun contenuto, testo o impostazione.</p>
    <?php else: ?><ul class="rows"><?php foreach ($usage as $u): ?><li><?= $u['url'] ? '<a href="' . e(Admin::u($u['url'])) . '">' . e($u['label']) . '</a>' : e($u['label']) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <?php if (Auth::can('site')): ?>
      <h2 class="mt">Sostituisci</h2>
      <form method="post" enctype="multipart/form-data" class="stack">
        <?= Admin::csrfField() ?><input type="hidden" name="path" value="<?= e($file) ?>">
        <label class="field">Nuovo file <span class="muted">(stesso tipo; il percorso non cambia, tutte le pagine si aggiornano)</span><input type="file" name="file" required></label>
        <button class="btn btn--primary" name="action" value="replace">Sostituisci</button>
      </form>
      <h2 class="mt">Elimina</h2>
      <form method="post"><?= Admin::csrfField() ?><input type="hidden" name="path" value="<?= e($file) ?>">
        <button class="btn btn--danger" name="action" value="delete" data-confirm="<?= $usage ? 'Questo file è usato in ' . count($usage) . ' punti: dopo l’eliminazione lì non si vedrà più. Eliminare comunque?' : 'Eliminare il file?' ?>">Elimina il file</button>
        <?php if ($usage): ?><p class="hint">Attenzione: è usato in <?= count($usage) ?> punti.</p><?php endif; ?>
      </form>
    <?php endif; ?>
  </section>
</div>
