<section class="textblock <?= e(Blocks::toneClass($b['tone'])) ?>" id="<?= e($b['id_attr']) ?>" data-ink="<?= $b['tone'] === 'dark' ? 'light' : 'dark' ?>"<?= $b['title'] ? ' aria-labelledby="' . e($b['id_attr']) . '-h"' : '' ?>>
  <?php if ($b['title']): ?><h2 id="<?= e($b['id_attr']) ?>-h" class="textblock__head reveal"<?= $b['lg'] ?>><?= e($b['title']) ?></h2><?php endif; ?>
  <div class="textblock__body prose reveal"<?= $b['lg'] ?>><?= PP::body($b['body']) ?></div>
</section>
