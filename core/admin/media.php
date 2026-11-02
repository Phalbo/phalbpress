<header class="head"><h1>Immagini e file</h1></header>
<?php if (class_exists('Demo') && Auth::can('site')): ?>
<section class="panel">
  <form method="post" class="row" style="align-items:center">
    <?= Admin::csrfField() ?><input type="hidden" name="action" value="placeholders">
    <p class="grow"><strong>Foto segnaposto.</strong> Mette una foto di esempio, con le misure giuste stampate sopra, in ogni posto ancora senza foto: apertura, news, storie, gallerie. Le foto già messe non si toccano. Poi le sostituisci una alla volta (cerca “segnaposto”).</p>
    <button class="btn" type="submit">Metti le foto segnaposto</button>
  </form>
</section>
<?php endif; ?>
<section class="panel">
  <form method="post" enctype="multipart/form-data" class="row">
    <?= Admin::csrfField() ?>
    <label class="field grow">Carica uno o più file <span class="muted">(jpg, png, webp, pdf, docx… max <?= (int)(PP::$config['upload_max_mb'] ?? 30) ?> MB)</span>
      <input type="file" name="files[]" multiple></label>
    <button class="btn btn--primary" type="submit">Carica</button>
  </form>
  <ul class="hints">
    <?php foreach ((PP::$config['image_hints'] ?? []) as $k => $h): ?><li><strong><?= e(PP::$config['types'][$k]['label'] ?? ($k === 'default' ? 'Immagini nei testi' : ucfirst($k))) ?>:</strong> <?= e($h) ?></li><?php endforeach; ?>
    <li>Le immagini vengono raddrizzate, ridotte a <?= (int)(PP::$config['image_max_px'] ?? 2400) ?> px sul lato lungo e salvate in <?= strtoupper(PP::$config['image_format'] ?? 'webp') ?> qualità <?= (int)(PP::$config['image_quality'] ?? 82) ?>, con una copia da 960 px per i telefoni. I dati GPS delle foto vengono eliminati.</li>
    <li>I nomi dei file vengono ripuliti: “Foto Città (1).JPG” diventa “foto-citta-1.webp”.</li>
  </ul>
</section>
<section class="panel">
  <form method="get" class="row filters" role="search">
    <label class="field grow">Cerca per nome<input type="search" name="q" value="<?= e($q) ?>"></label>
    <label class="field">Tipo<select name="type"><option value="">Tutti</option>
      <?php foreach (['image' => 'Immagini', 'pdf' => 'PDF', 'office' => 'Documenti Office', 'other' => 'Altro'] as $k => $l): ?><option value="<?= $k ?>" <?= $type === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></label>
    <label class="field">Mese<select name="ym"><option value="">Tutti</option>
      <?php foreach ($months as $m => $n): [$yy, $mm] = explode('/', $m); ?><option value="<?= e($m) ?>" <?= $ym === $m ? 'selected' : '' ?>><?= e(fmt_date("$yy-$mm-01", 'it') ? ucfirst(preg_replace('~^\d+\s+~', '', fmt_date("$yy-$mm-01", 'it'))) : $m) ?> (<?= $n ?>)</option><?php endforeach; ?></select></label>
    <button class="btn" type="submit">Filtra</button>
    <?php if ($q || $type || $ym): ?><a class="btn btn--small" href="<?= e(Admin::u('media')) ?>">Azzera</a><?php endif; ?>
  </form>
  <p class="muted small"><?= (int)$found ?> di <?= $total ?> file</p>
  <?= $pager ?>
  <?php if (!$items): ?><p class="empty"><?= $total ? 'Nessun file corrisponde ai filtri.' : 'La libreria è vuota.' ?></p><?php endif; ?>
  <ul class="grid">
    <?php foreach ($items as $it): ?>
      <li class="tile">
        <?php if ($it['image']): ?><img src="<?= e(upload_url($it['path'])) ?>" alt="" loading="lazy"><?php else: ?><span class="tile__ext"><?= e(strtoupper(pathinfo($it['name'], PATHINFO_EXTENSION))) ?></span><?php endif; ?>
        <div class="tile__meta">
          <span class="tile__name" title="<?= e($it['name']) ?>"><?= e($it['name']) ?></span>
          <span class="muted small"><?= Media::human($it['size']) ?><?= $it['w'] ? ' · ' . $it['w'] . '×' . $it['h'] : '' ?></span>
          <div class="row">
            <a class="btn btn--small" href="<?= e(Admin::u('media') . '?file=' . rawurlencode($it['path'])) ?>">Dettagli</a>
            <button type="button" class="btn btn--small" data-copy="<?= e($it['path']) ?>">Copia percorso</button>
          </div>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
  <?= $pager ?>
</section>
