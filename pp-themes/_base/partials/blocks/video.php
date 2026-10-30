<?php $vid = preg_match('~^[A-Za-z0-9_-]{6,20}$~', (string)($m['video'] ?? '')) ? $m['video'] : ''; ?>
<section class="videoblock <?= e(Blocks::toneClass($b['tone'])) ?>" id="<?= e($b['id_attr']) ?>" data-ink="<?= $b['tone'] === 'dark' ? 'light' : 'dark' ?>"<?= $b['title'] ? ' aria-labelledby="' . e($b['id_attr']) . '-h"' : '' ?>>
  <div class="videoblock__top">
    <?php if ($b['title']): ?><h2 id="<?= e($b['id_attr']) ?>-h" class="videoblock__head reveal"<?= $b['lg'] ?>><?= e($b['title']) ?></h2><?php endif; ?>
    <?php if ($b['excerpt']): ?><p class="videoblock__text"<?= $b['lg'] ?>><?= e($b['excerpt']) ?></p><?php endif; ?>
  </div>
  <?php if ($vid) echo PP::partial('video', ['id' => $vid]); ?>
</section>
