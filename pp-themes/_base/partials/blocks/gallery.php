<?php
$imgs = array_values(array_filter(array_map('trim', preg_split('~\R~', (string)($m['images'] ?? '')))));
$alts = array_map('trim', preg_split('~\R~', (string)($x['alts'] ?? '')));
?>
<section class="gallery gallery--<?= min(count($imgs), 6) ?> <?= e(Blocks::toneClass($b['tone'])) ?>" id="<?= e($b['id_attr']) ?>" data-ink="<?= $b['tone'] === 'dark' ? 'light' : 'dark' ?>"<?= $b['title'] ? ' aria-labelledby="' . e($b['id_attr']) . '-h"' : '' ?>>
  <?php if ($b['title']): ?><div class="gallery__top"><h2 id="<?= e($b['id_attr']) ?>-h" class="gallery__head reveal"<?= $b['lg'] ?>><?= e($b['title']) ?></h2>
    <?php if ($b['excerpt']): ?><p class="gallery__intro"<?= $b['lg'] ?>><?= e($b['excerpt']) ?></p><?php endif; ?></div><?php endif; ?>
  <ul class="gallery__list">
    <?php foreach ($imgs as $n => $p): ?><li class="gallery__item gallery__item--<?= $n + 1 ?> reveal"><?= img($p, $alts[$n] ?? '', '(min-width: 900px) 33vw, 100vw') ?></li><?php endforeach; ?>
  </ul>
</section>
