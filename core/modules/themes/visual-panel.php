<?php
/* Pannello di una fascia. Variabili: $b (riga), $meta, $fields (aspetto), $title, $colors (identità) */
$tones = ['' => ['Automatico', ''], 'paper' => ['Sfondo', $colors['ground'] ?? ''], 'accent' => ['Principale', $colors['accent'] ?? ''],
    'field1' => ['Campo 1', $colors['field1'] ?? ''], 'field2' => ['Campo 2', $colors['field2'] ?? ''], 'dark' => ['Scuro', $colors['dark'] ?? ''], 'custom' => ['Libero', '']];
$tone = (string)($meta['tone'] ?? '');
$kind = Blocks::kinds()[$b['template']]['label'] ?? $b['template'];
?>
<form class="stack vis-form" data-vis-form data-id="<?= (int)$b['id'] ?>">
  <div class="vis-form__head">
    <p class="small muted"><?= e($kind) ?><?= $b['status'] !== 'published' ? ' · <strong>nascosta</strong>' : '' ?></p>
    <h2><?= e($title ?: '(senza titolo)') ?></h2>
    <div class="row">
      <a class="btn btn--small" href="<?= e(Admin::u('entry/' . (int)$b['id'] . '/' . I18n::$master)) ?>">Modifica i testi</a>
      <button type="button" class="btn btn--small" data-vis-toggle><?= $b['status'] === 'published' ? 'Nascondi' : 'Mostra' ?></button>
      <button type="button" class="btn btn--small btn--danger" data-vis-delete>Elimina</button>
    </div>
  </div>
  <fieldset class="vis-tones"><legend>Fondo</legend>
    <?php foreach ($tones as $v => [$label, $hex]): ?>
      <label class="vis-tone"><input type="radio" name="meta[tone]" value="<?= e($v) ?>" <?= $tone === $v ? 'checked' : '' ?>>
        <span class="vis-tone__chip<?= $v === '' ? ' vis-tone__chip--auto' : '' ?><?= $v === 'custom' ? ' vis-tone__chip--custom' : '' ?>"<?= $hex ? ' style="background:' . e($hex) . '"' : '' ?>></span><?= e($label) ?></label>
    <?php endforeach; ?>
  </fieldset>
  <?php foreach ($fields as $k => $f): if ($k === 'tone') continue;
    $fname = 'meta[' . $k . ']'; $fval = $meta[$k] ?? ''; $flang = '';
    echo '<div class="vis-field" data-field="' . e($k) . '">';
    require PP_ROOT . '/core/admin/_field.php';
    echo '</div>';
  endforeach; ?>
  <p class="hint">Le modifiche si salvano da sole e l'anteprima si aggiorna.</p>
</form>
