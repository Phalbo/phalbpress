<?php $cta = Blocks::href((string)($m['cta_url'] ?? '')); ?>
<section class="cta <?= e(Blocks::toneClass($b['tone'])) ?>" id="<?= e($b['id_attr']) ?>" data-ink="<?= $b['tone'] === 'dark' ? 'light' : 'dark' ?>" aria-labelledby="<?= e($b['id_attr']) ?>-h">
  <h2 id="<?= e($b['id_attr']) ?>-h" class="cta__head reveal"<?= $b['lg'] ?>><?= e($b['title']) ?></h2>
  <?php if ($b['excerpt']): ?><p class="cta__text reveal"<?= $b['lg'] ?>><?= e($b['excerpt']) ?></p><?php endif; ?>
  <?php if ($cta && !empty($x['cta_label'])): ?><p class="cta__act reveal"><a class="btn btn--dark" href="<?= e($cta) ?>"><?= e($x['cta_label']) ?></a></p><?php endif; ?>
</section>
