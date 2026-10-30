<?php $isMaster = $lang === I18n::$master; $tabBase = Admin::u('doc/' . $row['id']); ?>
<header class="head">
  <div>
    <p class="crumbs"><a href="<?= e(Admin::u('docs')) ?>">Documenti</a></p>
    <h1><?= e($title) ?></h1>
  </div>
  <?php if (!$isNew && $row['file']): ?>
    <div class="head__actions"><a class="btn" href="<?= e(PP::url('download/' . $row['id'] . '/' . $lang)) ?>" target="_blank" rel="noopener">Scarica come visitatore (<?= e(I18n::label($lang)) ?>) ↗</a></div>
  <?php endif; ?>
</header>

<?php if (!$isNew): include __DIR__ . '/_tabs.php'; endif; ?>

<?php if ($autoTranslate): ?>
  <section class="panel queue" data-autoqueue data-kind="doc" data-id="<?= (int)$row['id'] ?>">
    <h2>Traduzione in corso</h2>
    <p class="hint" data-queue-status>Preparo le lingue…</p>
    <ul class="rows" data-queue-list></ul>
  </section>
<?php endif; ?>

<?php if ($isMaster): ?>
<form method="post" enctype="multipart/form-data" class="edit" data-form>
  <?= Admin::csrfField() ?>
  <div class="edit__main panel">
    <label class="field">Titolo<input type="text" name="title" value="<?= e($tr['title'] ?? '') ?>" required class="input--title"></label>
    <label class="field">Descrizione breve<textarea name="excerpt" rows="3"><?= e($tr['excerpt'] ?? '') ?></textarea></label>
    <div class="panel panel--inset stack">
      <h2 class="small-head">File comune a tutte le lingue</h2>
      <?php if ($row['file']): ?>
        <p><strong><?= e(basename($row['file'])) ?></strong> <span class="muted"><?= is_file(PP_ROOT . '/' . $row['file']) ? Media::human(filesize(PP_ROOT . '/' . $row['file'])) : 'file mancante' ?></span></p>
      <?php else: ?><p class="muted">Nessun file caricato.</p><?php endif; ?>
      <label class="field"><?= $row['file'] ? 'Sostituisci con un nuovo file' : 'Carica il file' ?><input type="file" name="file"></label>
      <?php if ($files): ?>
        <label class="check"><input type="checkbox" name="replace_all" value="1"> Usa il nuovo file anche nelle lingue che hanno una versione propria (<?= e(strtoupper(implode(', ', array_keys($files)))) ?>)</label>
      <?php endif; ?>
      <p class="hint">Il link pubblico non cambia: chi ha già il link scarica la versione nuova.</p>
    </div>
    <?php if (!$isNew): ?>
      <p class="hint">Da inserire nei testi: <code>[doc:<?= (int)$row['id'] ?>]</code> mostra titolo, formato e peso nella lingua della pagina.</p>
    <?php endif; ?>
  </div>
  <aside class="edit__side">
    <div class="panel stack">
      <label class="field">Stato<select name="status"><option value="draft">Bozza</option><option value="published" <?= $row['status'] === 'published' ? 'selected' : '' ?>>Pubblicato</option></select></label>
      <button class="btn btn--primary" name="action" value="save" type="submit">Salva</button>
      <button class="btn" name="action" value="save_translate" type="submit" <?= Translator::enabled() ? '' : 'disabled' ?>>Salva e traduci in tutte le lingue</button>
    </div>
    <div class="panel stack">
      <label class="field">Codice <span class="muted">(es. D5.1)</span><input type="text" name="code" value="<?= e($row['code']) ?>"></label>
      <label class="field">Categoria<select name="category">
        <?php foreach (PP::$config['doc_categories'] as $k => $v): ?><option value="<?= e($k) ?>" <?= $row['category'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
      </select></label>
      <label class="field">Work package<input type="text" name="wp" value="<?= e($row['wp']) ?>" placeholder="WP5"></label>
      <label class="field">Data<input type="date" name="pub_date" value="<?= e($row['pub_date']) ?>"></label>
      <label class="field">Ordine<input type="number" name="sort" value="<?= (int)$row['sort'] ?>"></label>
    </div>
    <?php if (!$isNew): ?>
      <div class="panel stack">
        <h2 class="small-head">Versioni per lingua</h2>
        <ul class="rows">
          <?php foreach (I18n::codes() as $l): ?>
            <li><a href="<?= e($tabBase . '/' . $l) ?>"><?= e(I18n::name($l)) ?></a><span class="muted small"><?= isset($files[$l]) ? e(basename($files[$l])) : 'file comune' ?></span></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="panel"><button class="btn btn--danger btn--small" name="action" value="delete" type="submit" data-confirm="Eliminare il documento? I file restano nella libreria.">Elimina</button></div>
    <?php endif; ?>
  </aside>
</form>

<?php else: ?>
<form method="post" enctype="multipart/form-data" class="compare" data-form>
  <?= Admin::csrfField() ?>
  <div class="compare__src panel">
    <h2 class="small-head">Originale · <?= e(I18n::name(I18n::$master)) ?></h2>
    <p class="src-title"><?= e($master['title'] ?? '') ?></p>
    <p class="src-excerpt"><?= e($master['excerpt'] ?? '') ?></p>
    <p class="muted">File comune: <?= $row['file'] ? e(basename($row['file'])) : 'nessuno' ?></p>
  </div>
  <div class="compare__dst panel stack">
    <div class="panel__head">
      <h2 class="small-head"><?= e(I18n::name($lang)) ?> <?= Admin::badge($statuses[$lang] ?? 'missing') ?></h2>
      <button type="button" class="btn btn--small" data-translate-one data-kind="doc" data-id="<?= (int)$row['id'] ?>" data-lang="<?= e($lang) ?>" <?= Translator::enabled() ? '' : 'disabled' ?>>Traduci automaticamente</button>
    </div>
    <label class="field">Titolo<input type="text" name="title" value="<?= e($tr['title'] ?? '') ?>" class="input--title" lang="<?= e($lang) ?>"></label>
    <label class="field">Descrizione breve<textarea name="excerpt" rows="3" lang="<?= e($lang) ?>"><?= e($tr['excerpt'] ?? '') ?></textarea></label>
    <div class="panel panel--inset stack">
      <h3 class="small-head">File in <?= e(I18n::name($lang)) ?></h3>
      <?php if (isset($files[$lang])): ?>
        <p><strong><?= e(basename($files[$lang])) ?></strong></p>
        <button class="btn btn--small" name="action" value="remove_lang_file" type="submit">Togli: usa il file comune</button>
      <?php else: ?>
        <p class="muted">In questa lingua si scarica il file comune.</p>
      <?php endif; ?>
      <label class="field">Carica una versione tradotta del file<input type="file" name="lang_file"></label>
    </div>
    <div class="row"><button class="btn btn--primary" type="submit">Salva come rivista</button></div>
  </div>
</form>
<?php endif; ?>
