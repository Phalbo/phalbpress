<?php
/* Un campo personalizzato. Variabili: $k, $f (definizione), $fname (nome input), $fval (valore), $flang (lingua o '') */
$fl = $flang ? ' lang="' . e($flang) . '"' : '';
$hint = $f['hint'] ? '<span class="muted">(' . e($f['hint']) . ')</span>' : '';
switch ($f['type']):
  case 'textarea': ?>
    <label class="field"><?= e($f['label']) ?> <?= $hint ?><textarea name="<?= e($fname) ?>" rows="4"<?= $fl ?>><?= e($fval) ?></textarea></label>
  <?php break; case 'checkbox': ?>
    <label class="check"><input type="checkbox" name="<?= e($fname) ?>" value="1" <?= $fval ? 'checked' : '' ?>> <?= e($f['label']) ?></label>
  <?php break; case 'select': ?>
    <label class="field"><?= e($f['label']) ?> <?= $hint ?><select name="<?= e($fname) ?>"><?php if (!array_key_exists('', $f['options'])): ?><option value="">—</option><?php endif; ?>
      <?php foreach ($f['options'] as $ok => $ol): $ov = is_int($ok) ? $ol : $ok; ?><option value="<?= e($ov) ?>" <?= (string)$fval === (string)$ov ? 'selected' : '' ?>><?= e($ol) ?></option><?php endforeach; ?>
    </select></label>
  <?php break; case 'entry':
    $opts = DB::all("SELECT e.id, t.title FROM pp_entries e LEFT JOIN pp_entry_tr t ON t.entry_id = e.id AND t.lang = ? WHERE e.type = ? ORDER BY e.menu_order, t.title", [I18n::$master, $f['entry_type'] ?? 'category']); ?>
    <label class="field"><?= e($f['label']) ?> <?= $hint ?><select name="<?= e($fname) ?>"><option value="">—</option>
      <?php foreach ($opts as $o): ?><option value="<?= (int)$o['id'] ?>" <?= (string)$fval === (string)$o['id'] ? 'selected' : '' ?>><?= e($o['title']) ?></option><?php endforeach; ?>
    </select></label>
  <?php break; case 'color': ?>
    <label class="field"><?= e($f['label']) ?> <?= $hint ?><span class="row"><input type="color" name="<?= e($fname) ?>" value="<?= e(preg_match('~^#[0-9a-f]{6}$~i', (string)$fval) ? $fval : '#ffffff') ?>"></span></label>
  <?php break; case 'image': ?>
    <div class="field"><span><?= e($f['label']) ?> <?= $hint ?></span>
      <?php if ($fval): ?><img src="<?= e(upload_url($fval)) ?>" alt="" class="thumb"><?php endif; ?>
      <div class="row"><input type="text" name="<?= e($fname) ?>" value="<?= e($fval) ?>" placeholder="uploads/media/…" data-image-path><button type="button" class="btn btn--small" data-pick-image>Libreria</button></div></div>
  <?php break; default:
    $it = ['number' => 'number', 'date' => 'date', 'url' => 'url'][$f['type']] ?? 'text'; ?>
    <label class="field"><?= e($f['label']) ?> <?= $hint ?><input type="<?= $it ?>" name="<?= e($fname) ?>" value="<?= e($fval) ?>"<?= $fl ?><?= $it === 'number' ? ' step="any"' : '' ?>></label>
<?php endswitch;
