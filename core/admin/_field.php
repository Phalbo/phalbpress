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
  <?php break; case 'entries':
    // più contenuti in ordine (id separati da virgola): con JavaScript ricerca per titolo, frecce per l'ordine, × per togliere
    $et = $f['entry_type'] ?? '*';
    $opts = $et === '*'
        ? DB::all("SELECT e.id, e.type, e.status, t.title FROM pp_entries e LEFT JOIN pp_entry_tr t ON t.entry_id = e.id AND t.lang = ? WHERE e.type NOT IN ('block', 'category') ORDER BY e.type, e.pub_date DESC, t.title", [I18n::$master])
        : DB::all("SELECT e.id, e.type, e.status, t.title FROM pp_entries e LEFT JOIN pp_entry_tr t ON t.entry_id = e.id AND t.lang = ? WHERE e.type = ? ORDER BY e.menu_order, t.title", [I18n::$master, $et]);
    $data = array_map(fn($o) => ['id' => (int)$o['id'], 't' => (string)$o['title'], 'k' => (string)(PP::$config['types'][$o['type']]['one'] ?? $o['type']), 'd' => $o['status'] !== 'published'], $opts);
    $fid = 'f-' . preg_replace('~[^a-z0-9]+~i', '-', $fname); ?>
    <div class="field entries" data-entries>
      <label for="<?= e($fid) ?>"><?= e($f['label']) ?> <?= $hint ?></label>
      <input type="text" id="<?= e($fid) ?>" name="<?= e($fname) ?>" value="<?= e($fval) ?>" data-entries-value placeholder="numeri dei contenuti separati da virgola">
      <script type="application/json" data-entries-data><?= json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    </div>
  <?php break; case 'entry':
    // entry_type '*' = qualunque contenuto con una pagina (storie, news, pagine, eventi…), raggruppato per tipo
    $et = $f['entry_type'] ?? 'category';
    $opts = $et === '*'
        ? DB::all("SELECT e.id, e.type, t.title FROM pp_entries e LEFT JOIN pp_entry_tr t ON t.entry_id = e.id AND t.lang = ? WHERE e.type NOT IN ('block', 'category') ORDER BY e.type, e.pub_date DESC, t.title", [I18n::$master])
        : DB::all("SELECT e.id, e.type, t.title FROM pp_entries e LEFT JOIN pp_entry_tr t ON t.entry_id = e.id AND t.lang = ? WHERE e.type = ? ORDER BY e.menu_order, t.title", [I18n::$master, $et]); ?>
    <label class="field"><?= e($f['label']) ?> <?= $hint ?><select name="<?= e($fname) ?>"<?= count($opts) > 12 ? ' data-search' : '' ?>><option value="">—</option>
      <?php $grp = null; foreach ($opts as $o): if ($et === '*' && $grp !== $o['type']): if ($grp !== null): ?></optgroup><?php endif; $grp = $o['type']; ?><optgroup label="<?= e(PP::$config['types'][$grp]['label'] ?? $grp) ?>"><?php endif; ?>
        <option value="<?= (int)$o['id'] ?>" <?= (string)$fval === (string)$o['id'] ? 'selected' : '' ?>><?= e($o['title']) ?></option><?php endforeach; ?>
      <?php if ($et === '*' && $grp !== null): ?></optgroup><?php endif; ?>
    </select></label>
  <?php break; case 'color': ?>
    <label class="field"><?= e($f['label']) ?> <?= $hint ?><span class="row"><input type="color" name="<?= e($fname) ?>" value="<?= e(preg_match('~^#[0-9a-f]{6}$~i', (string)$fval) ? $fval : '#ffffff') ?>"></span></label>
  <?php break; case 'image': ?>
    <div class="field"><span><?= e($f['label']) ?> <?= $hint ?></span>
      <?php if ($fval): ?><img src="<?= e(upload_url($fval)) ?>" alt="" class="thumb"><?php endif; ?>
      <div class="row"><input type="text" name="<?= e($fname) ?>" value="<?= e($fval) ?>" placeholder="uploads/media/…" aria-label="<?= e($f['label']) ?>: percorso del file" data-image-path><button type="button" class="btn btn--small" data-pick-image>Libreria</button></div></div>
  <?php break; case 'gallery':
    // una foto per riga "percorso | descrizione". Con JavaScript: anteprime da trascinare per l'ordine, descrizione per ogni foto,
    // foto trascinate dal computer caricate subito. Senza: si scrive qui e si aggiungono file col campo sotto
    $gid = 'f-' . preg_replace('~[^a-z0-9]+~i', '-', $fname);
    $glines = [];
    foreach (array_filter(explode('|', (string)$fval)) as $gp) { [$gpath, $galt] = array_pad(explode('=', $gp, 2), 2, ''); $glines[] = $gpath . ($galt !== '' ? ' | ' . $galt : ''); } ?>
    <div class="field gallery-field" data-gallery>
      <label for="<?= e($gid) ?>"><?= e($f['label']) ?> <?= $hint ?></label>
      <textarea id="<?= e($gid) ?>" name="<?= e($fname) ?>" rows="5" data-gallery-value placeholder="uploads/media/foto.webp | cosa mostra la foto"><?= e(implode("\n", $glines)) ?></textarea>
      <label class="field gallery-field__add">Aggiungi foto dal computer <span class="muted">(anche più di una; si aggiungono in fondo)</span>
        <input type="file" name="gallery_<?= e($k) ?>[]" accept="image/*" multiple data-gallery-files></label>
    </div>
  <?php break; default:
    $it = ['number' => 'number', 'date' => 'date', 'url' => 'url'][$f['type']] ?? 'text'; ?>
    <label class="field"><?= e($f['label']) ?> <?= $hint ?><input type="<?= $it ?>" name="<?= e($fname) ?>" value="<?= e($fval) ?>"<?= $fl ?><?= $it === 'number' ? ' step="any"' : '' ?>></label>
<?php endswitch;
