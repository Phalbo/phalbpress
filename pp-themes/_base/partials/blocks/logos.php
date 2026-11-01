<?php
/* Loghi dei partner (dal tipo "partner": logo, nome, sito). Scorrono piano in un nastro, o restano fermi in griglia; con "meno movimento" sempre fermi. */
$partners = isset(PP::$config['types']['partner']) ? array_values(array_filter(Content::entries('partner', lang()), fn($p) => $p['image'])) : [];
$still = ($m['motion'] ?? '') === 'still' || count($partners) < 4;
?>
<section class="logos <?= $still ? 'logos--still' : 'logos--run' ?> <?= e(Blocks::toneClass($b['tone'])) ?>" id="<?= e($b['id_attr']) ?>" data-ink="<?= $b['tone'] === 'dark' ? 'light' : 'dark' ?>"<?= $b['title'] ? ' aria-labelledby="' . e($b['id_attr']) . '-h"' : ' aria-label="' . e(t('partners_title')) . '"' ?>>
  <?php if ($b['title']): ?><h2 id="<?= e($b['id_attr']) ?>-h" class="logos__head reveal"<?= $b['lg'] ?>><?= e($b['title']) ?></h2><?php endif; ?>
  <div class="logos__track">
    <ul class="logos__list">
      <?php foreach ($partners as $p): $mm = $p['meta_arr']; $alt = $p['image_alt'] ?: $p['title']; ?>
        <li class="logos__item"><?php if (!empty($mm['url'])): ?><a href="<?= e($mm['url']) ?>" target="_blank" rel="noopener"><?= img($p['image'], $alt, '200px', 'logos__img') ?></a><?php else: ?><?= img($p['image'], $alt, '200px', 'logos__img') ?><?php endif; ?></li>
      <?php endforeach; ?>
    </ul>
    <?php if (!$still): ?>
    <ul class="logos__list" aria-hidden="true">
      <?php foreach ($partners as $p): ?><li class="logos__item"><?= img($p['image'], '', '200px', 'logos__img') ?></li><?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>
</section>
