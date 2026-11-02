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
    // entry_type '*' = qualunque contenuto con una pagina (storie, news, pagine, eventi…), raggruppato per tipo
    $et = $f['entry_type'] ?? 'category';
    $opts = $et === '*'
        ? DB::all("SELECT e.id, e.type, t.title FROM pp_entries e LEFT JOIN pp_entry_tr t ON t.entry_id = e.id AND t.lang = ? WHERE e.type NOT IN ('block', 'category') ORDER BY e.type, e.pub_date DESC, t.title", [I18n::$master])
        : DB::all("SELECT e.id, e.type, t.title FROM pp_entries e LEFT JOIN pp_entry_tr t ON t.entry_id = e.id AND t.lang = ? WHERE e.type = ? ORDER BY e.menu_order, t.title", [I18n::$master, $et]); ?>
    <label class="field"><?= e($f['label']) ?> <?= $hint ?><select name="<?= e($fname) ?>"><option value="">—</option>
      <?php $grp = null; foreach ($opts as $o): if ($et === '*' && $grp !== $o['type']): if ($grp !== null): ?></optgroup><?php endif; $grp = $o['type']; ?><optgroup label="<?= e(PP::$config['types'][$grp]['label'] ?? $grp) ?>"><?php endif; ?>
        <option value="<?= (int)$o['id'] ?>" <?= (string)$fval === (string)$o['id'] ? 'selected' : '' ?>><?= e($o['title']) ?></option><?php endforeach; ?>
      <?php if ($et === '*' && $grp !== null): ?></optgroup><?php endif; ?>
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
