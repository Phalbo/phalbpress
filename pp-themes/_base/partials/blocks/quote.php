<?php /* Citazione grande: titolo = la frase, frase sotto = chi la dice, ruolo facoltativo, foto facoltativa. */ ?>
<section class="quote <?= e(Blocks::toneClass($b['tone'])) ?><?= $b['image'] ? ' quote--img' : '' ?>" id="<?= e($b['id_attr']) ?>" data-ink="<?= $b['tone'] === 'dark' ? 'light' : 'dark' ?>">
  <figure class="quote__fig reveal">
    <?php if ($b['image']): ?><span class="quote__pic"><?= img($b['image'], '', '160px') ?></span><?php endif; ?>
    <blockquote class="quote__text"<?= $b['lg'] ?>><p><?= e($b['title']) ?></p></blockquote>
    <?php if ($b['excerpt'] || !empty($x['role'])): ?>
      <figcaption class="quote__who"<?= $b['lg'] ?>><?php if ($b['excerpt']): ?><span class="quote__name"><?= e($b['excerpt']) ?></span><?php endif; ?><?php if (!empty($x['role'])): ?> <span class="quote__role"><?= e($x['role']) ?></span><?php endif; ?></figcaption>
    <?php endif; ?>
  </figure>
</section>
