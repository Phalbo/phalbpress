<?php
/* Mappa di OpenStreetMap: si carica solo col consenso (riceve l'indirizzo IP di chi guarda), prima c'è un riquadro con l'indirizzo e il link. */
$lat = (float)str_replace(',', '.', (string)($m['map_lat'] ?? ''));
$lon = (float)str_replace(',', '.', (string)($m['map_lon'] ?? ''));
$ok = $lat !== 0.0 && $lon !== 0.0 && abs($lat) <= 90 && abs($lon) <= 180;
$zoom = max(3, min(19, (int)($m['map_zoom'] ?? 0) ?: 16));
$d = 0.0035 * 2 ** (16 - $zoom);   // mezza larghezza del riquadro, in gradi
$bbox = implode(',', array_map(fn($v) => number_format($v, 6, '.', ''), [$lon - $d * 1.6, $lat - $d, $lon + $d * 1.6, $lat + $d]));
$src = 'https://www.openstreetmap.org/export/embed.html?bbox=' . rawurlencode($bbox) . '&layer=mapnik&marker=' . rawurlencode(number_format($lat, 6, '.', '') . ',' . number_format($lon, 6, '.', ''));
$ext = 'https://www.openstreetmap.org/?mlat=' . $lat . '&mlon=' . $lon . '#map=' . $zoom . '/' . $lat . '/' . $lon;
$side = in_array($m['map_side'] ?? '', ['below', 'none'], true) ? $m['map_side'] : 'side';
$label = trim((string)($x['map_label'] ?? ''));
?>
<section class="mapblock mapblock--<?= e($side) ?> <?= e(Blocks::toneClass($b['tone'])) ?>" id="<?= e($b['id_attr']) ?>" data-ink="<?= $b['tone'] === 'dark' ? 'light' : 'dark' ?>"<?= $b['title'] ? ' aria-labelledby="' . e($b['id_attr']) . '-h"' : ' aria-label="' . e(t('map_title')) . '"' ?>>
  <?php if ($side !== 'none' && ($b['title'] || $b['excerpt'] || $label !== '')): ?>
  <div class="mapblock__text">
    <?php if ($b['title']): ?><h2 id="<?= e($b['id_attr']) ?>-h" class="mapblock__head reveal"<?= $b['lg'] ?>><?= e($b['title']) ?></h2><?php endif; ?>
    <?php if ($label !== ''): ?><p class="mapblock__label"<?= $b['lg'] ?>><strong><?= e($label) ?></strong></p><?php endif; ?>
    <?php if ($b['excerpt']): ?><p class="mapblock__addr"<?= $b['lg'] ?>><?= nl2br(e($b['excerpt'])) ?></p><?php endif; ?>
    <?php if ($ok): ?><p><a href="<?= e($ext) ?>" target="_blank" rel="noopener"><?= e(t('map_open')) ?></a></p><?php endif; ?>
  </div>
  <?php endif; ?>
  <?php if ($ok): ?>
  <figure class="pp-embed pp-embed--map mapblock__map" data-pp-embed data-kind="map" data-service="openstreetmap" data-src="<?= e($src) ?>" data-external="<?= e($ext) ?>" data-title="<?= e(t('map_title') . ($label !== '' ? ': ' . $label : '')) ?>">
    <div class="pp-embed__box">
      <a class="pp-embed__play pp-embed__play--map" data-pp-play href="<?= e($ext) ?>" target="_blank" rel="noopener"><span><?= e(t('map_show')) ?></span></a>
    </div>
  </figure>
  <?php endif; ?>
</section>
