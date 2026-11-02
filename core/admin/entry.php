<?php
$isMaster = $lang === I18n::$master;
$tabBase = Admin::u('entry/' . $row['id']);
$trFields = array_filter($fields, fn($f) => $f['translatable']);
$baseFields = array_filter($fields, fn($f) => !$f['translatable']);
$hasPage = PP::typePrefix($type, $lang) !== null;
$scheduled = $row['status'] === 'published' && ($row['publish_at'] > Content::now() || ($row['unpublish_at'] !== '' && $row['unpublish_at'] <= Content::now()));
$dtl = fn($v) => $v ? str_replace(' ', 'T', substr($v, 0, 16)) : '';
$uses = $type === 'block' && class_exists('Blocks') ? Blocks::uses((string)$row['template']) : null;
$use = fn($k) => $uses === null || isset($uses[$k]);
$lbl = fn($k, $d) => $uses[$k] ?? $d;
?>
<header class="head">
  <div>
    <p class="crumbs"><a href="<?= e(Admin::u('entries/' . $type)) ?>"><?= e(PP::$config['types'][$type]['label']) ?></a></p>
    <h1><?= e($title) ?></h1>
  </div>
  <?php if (!$isNew && $hasPage): ?>
    <div class="head__actions">
      <?php if ($previewUrl): ?><a class="btn" target="_blank" rel="noopener" href="<?= e($previewUrl) ?>">Anteprima ↗</a><?php endif; ?>
      <?php if ($row['status'] === 'published' && !$scheduled && $publicUrl): ?><a class="btn" target="_blank" rel="noopener" href="<?= e($publicUrl) ?>">Vedi sul sito ↗</a><?php endif; ?>
    </div>
  <?php endif; ?>
</header>

<?php if (!$isNew): include __DIR__ . '/_tabs.php'; endif; ?>

<?php if ($autoTranslate): ?>
  <section class="panel queue" data-autoqueue data-kind="entry" data-id="<?= (int)$row['id'] ?>">
    <h2>Traduzione in corso</h2>
    <p class="hint" role="status" data-queue-status>Preparo le lingue…</p>
    <ul class="rows" data-queue-list></ul>
  </section>
<?php endif; ?>

<?php if ($isMaster): ?>
<form method="post" enctype="multipart/form-data" class="edit" data-form>
  <?= Admin::csrfField() ?>
  <div class="edit__main panel">
    <label class="field"><?= e($lbl('title', 'Titolo')) ?><input type="text" name="title" value="<?= e($tr['title'] ?? '') ?>" required class="input--title" data-slug-source></label>
    <?php if ($hasPage && $row['slug'] !== 'home'): ?>
      <div class="field slugfield">
        <span>Indirizzo</span>
        <div class="row">
          <span class="muted small"><?= e(PP::link(PP::typePrefix($type, $lang), $lang)) ?></span>
          <input type="text" name="slug" value="<?= e($tr['slug'] ?? '') ?>" data-slug data-auto="<?= empty($tr['slug']) ? '1' : '0' ?>" aria-label="Slug" pattern="[a-z0-9-]*">
          <button type="button" class="btn btn--small" data-slug-regen>Rigenera dal titolo</button>
        </div>
        <?php if (!$isNew && $row['status'] === 'published'): ?><p class="hint">Se cambi l’indirizzo, quello vecchio porta da solo al nuovo (redirect 301).</p><?php endif; ?>
      </div>
    <?php endif; ?>
    <?php if ($use('excerpt')): ?>
    <label class="field"><?= $uses ? e($lbl('excerpt', 'Testo')) : 'Sommario <span class="muted">(una o due frasi, usate negli elenchi e nelle anteprime)</span>' ?>
      <textarea name="excerpt" rows="3"><?= e($tr['excerpt'] ?? '') ?></textarea></label>
    <?php endif; ?>
    <?php if ($use('body')): ?>
    <div class="field"><span><?= e($lbl('body', 'Testo')) ?></span><?php $name = 'body'; $value = $tr['body'] ?? ''; include __DIR__ . '/_editor.php'; ?></div>
    <?php endif; ?>
    <?php foreach ($trFields as $k => $f): $fname = 'x[' . $k . ']'; $fval = $x[$k] ?? ''; $flang = ''; include __DIR__ . '/_field.php'; endforeach; ?>
  </div>
  <aside class="edit__side">
    <div class="panel stack">
      <?php if ($canPublish): ?>
        <label class="field">Stato
          <select name="status"><option value="draft">Bozza</option><option value="published" <?= $row['status'] === 'published' ? 'selected' : '' ?>>Pubblicato</option></select></label>
        <details <?= $row['publish_at'] || $row['unpublish_at'] ? 'open' : '' ?>>
          <summary>Programma</summary>
          <label class="field">Pubblica dal<input type="datetime-local" name="publish_at" value="<?= e($dtl($row['publish_at'])) ?>"></label>
          <label class="field">Togli dal sito il<input type="datetime-local" name="unpublish_at" value="<?= e($dtl($row['unpublish_at'])) ?>"></label>
          <p class="hint">Vale se lo stato è “Pubblicato”. Il sito si aggiorna da solo con l’operazione pianificata (vedi Sistema).</p>
        </details>
        <?php if ($scheduled): ?><p class="hint"><span class="pill">Programmato</span> non ancora (o non più) visibile.</p><?php endif; ?>
      <?php else: ?>
        <p class="hint">Stato: <strong><?= $row['status'] === 'published' ? 'Pubblicato' : 'Bozza' ?></strong>. Un redattore lo pubblicherà.</p>
      <?php endif; ?>
      <button class="btn btn--primary" name="action" value="save" type="submit">Salva</button>
      <button class="btn" name="action" value="save_translate" type="submit" <?= Translator::enabled() ? '' : 'disabled title="Inserisci la chiave API in Sistema → Impostazioni del sito"' ?>>Salva e traduci in tutte le lingue</button>
      <?php if (!Translator::enabled()): ?><p class="hint">Traduzione automatica non configurata.</p><?php endif; ?>
    </div>
    <div class="panel stack">
      <?php if (in_array($type, ['news', 'story'], true)): ?>
        <label class="field">Data<input type="date" name="pub_date" value="<?= e($row['pub_date']) ?>"></label>
      <?php else: ?>
        <input type="hidden" name="pub_date" value="<?= e($row['pub_date']) ?>">
        <label class="field">Ordine<input type="number" name="menu_order" value="<?= (int)$row['menu_order'] ?>"></label>
      <?php endif; ?>
      <?php if ($uses !== null): ?>
        <label class="field">Tipo di blocco
          <select name="template"><?php foreach (Blocks::kinds() as $bk => $bd): ?><option value="<?= e($bk) ?>" <?= $row['template'] === $bk ? 'selected' : '' ?>><?= e($bd['label']) ?></option><?php endforeach; ?></select></label>
        <p class="hint">Cambiando tipo, salva per vedere i campi giusti: i contenuti restano (riquadri, passi, domande e numeri passano da un tipo all’altro) e tornando al tipo di prima ritrovi tutto. L’ordine decide la posizione nella pagina.</p>
      <?php endif; ?>
      <?php if ($type === 'page'): ?>
        <label class="check"><input type="checkbox" name="in_menu" value="1" <?= $row['in_menu'] ? 'checked' : '' ?>> Mostra nel menu automatico</label>
        <?php if ($templates): ?>
          <label class="field">Modello
            <select name="template"><option value="default">Normale</option>
              <?php foreach ($templates as $t): ?><option value="<?= e($t) ?>" <?= $row['template'] === $t ? 'selected' : '' ?>><?= e(ucfirst($t)) ?></option><?php endforeach; ?>
            </select></label>
        <?php endif; ?>
      <?php endif; ?>
    </div>
    <?php if ($use('image')): ?>
    <div class="panel stack">
      <span class="field"><?= e($lbl('image', 'Immagine')) ?></span>
      <?php if ($row['image']): ?><img src="<?= e(upload_url($row['image'])) ?>" alt="" class="thumb"><?php endif; ?>
      <div class="field"><input type="text" name="image" value="<?= e($row['image']) ?>" placeholder="uploads/media/…" data-image-path aria-label="Percorso immagine">
        <div class="row"><button type="button" class="btn btn--small" data-pick-image>Scegli dalla libreria</button></div></div>
      <label class="field">Testo alternativo <span class="muted">(cosa mostra l’immagine, per chi non vede)</span><input type="text" name="image_alt" value="<?= e($tr['image_alt'] ?? '') ?>" maxlength="300"></label>
      <label class="field">oppure carica<input type="file" name="image_file" accept="image/*"></label>
      <p class="hint"><?= e(Media::hint($type)) ?> La ridimensioniamo e comprimiamo noi.</p>
    </div>
    <?php endif; ?>
    <?php if ($baseFields): ?>
      <div class="panel stack">
        <?php foreach ($baseFields as $k => $f): $fname = 'meta[' . $k . ']'; $fval = $meta[$k] ?? ''; $flang = ''; include __DIR__ . '/_field.php'; endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if (!$isNew): ?>
      <div class="panel stack">
        <?php if ($author): ?><p class="hint">Creato da <?= e($author) ?></p><?php endif; ?>
        <?php include __DIR__ . '/_revisions.php'; ?>
        <div class="row">
          <?php if (Auth::can('content')): ?><button class="btn btn--small" name="action" value="duplicate" type="submit" formnovalidate>Duplica</button><?php endif; ?>
          <?php if ($row['slug'] !== 'home' && ($canPublish || $row['status'] !== 'published')): ?>
            <button class="btn btn--danger btn--small" name="action" value="delete" type="submit" formnovalidate data-confirm="Eliminare definitivamente questo contenuto in tutte le lingue?">Elimina</button>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </aside>
</form>

<?php else: ?>
<form method="post" class="compare" data-form>
  <?= Admin::csrfField() ?>
  <div class="compare__src panel">
    <h2 class="small-head">Originale · <?= e(I18n::name(I18n::$master)) ?></h2>
    <p class="src-title"><?= e($master['title'] ?? '') ?></p>
    <?php if (!empty($master['excerpt'])): ?><p class="src-excerpt"><?= e($master['excerpt']) ?></p><?php endif; ?>
    <div class="prose src-body"><?= $master['body'] ?? '' ?></div>
    <?php foreach ($trFields as $k => $f): if (($xMaster[$k] ?? '') === '') continue; ?>
      <p class="small"><strong><?= e($f['label']) ?>:</strong> <?= e($xMaster[$k]) ?></p>
    <?php endforeach; ?>
  </div>
  <div class="compare__dst panel">
    <div class="panel__head">
      <h2 class="small-head"><?= e(I18n::name($lang)) ?> <?= Admin::badge($statuses[$lang] ?? 'missing') ?></h2>
      <button type="button" class="btn btn--small" data-translate-one data-kind="entry" data-id="<?= (int)$row['id'] ?>" data-lang="<?= e($lang) ?>" <?= Translator::enabled() ? '' : 'disabled' ?>>Traduci automaticamente</button>
    </div>
    <label class="field"><?= e($lbl('title', 'Titolo')) ?><input type="text" name="title" value="<?= e($tr['title'] ?? '') ?>" class="input--title" lang="<?= e($lang) ?>" data-slug-source></label>
    <?php if ($hasPage && $row['slug'] !== 'home'): ?>
      <div class="field slugfield">
        <span>Indirizzo in <?= e(I18n::name($lang)) ?></span>
        <div class="row">
          <span class="muted small"><?= e(PP::link(PP::typePrefix($type, $lang), $lang)) ?></span>
          <input type="text" name="slug" value="<?= e($tr['slug'] ?? '') ?>" data-slug data-auto="<?= empty($tr['slug']) ? '1' : '0' ?>" aria-label="Slug" pattern="[a-z0-9-]*">
          <button type="button" class="btn btn--small" data-slug-regen>Rigenera dal titolo</button>
        </div>
      </div>
    <?php endif; ?>
    <?php if ($use('excerpt')): ?><label class="field"><?= e($lbl('excerpt', 'Sommario')) ?><textarea name="excerpt" rows="3" lang="<?= e($lang) ?>"><?= e($tr['excerpt'] ?? '') ?></textarea></label><?php endif; ?>
    <?php if ($row['image']): ?>
      <label class="field">Testo alternativo dell’immagine <?php if (!empty($master['image_alt'])): ?><span class="muted">(originale: <?= e($master['image_alt']) ?>)</span><?php endif; ?>
        <input type="text" name="image_alt" value="<?= e($tr['image_alt'] ?? '') ?>" lang="<?= e($lang) ?>" maxlength="300"></label>
    <?php endif; ?>
    <?php if ($use('body')): ?><div class="field"><span><?= e($lbl('body', 'Testo')) ?></span><?php $name = 'body'; $value = $tr['body'] ?? ''; include __DIR__ . '/_editor.php'; ?></div><?php endif; ?>
    <?php foreach ($trFields as $k => $f): $fname = 'x[' . $k . ']'; $fval = $x[$k] ?? ''; $flang = $lang; include __DIR__ . '/_field.php'; endforeach; ?>
    <div class="row">
      <button class="btn btn--primary" type="submit">Salva come rivista</button>
      <span class="hint">Salvando, la traduzione viene allineata all’originale attuale.</span>
    </div>
    <?php include __DIR__ . '/_revisions.php'; ?>
  </div>
</form>
<?php endif; ?>
