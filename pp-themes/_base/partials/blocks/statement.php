<section class="statement<?= ($m['align'] ?? '') === 'center' ? ' statement--center' : '' ?> <?= e(Blocks::toneClass($b['tone'])) ?>" id="<?= e($b['id_attr']) ?>" data-ink="<?= $b['tone'] === 'dark' ? 'light' : 'dark' ?>" aria-labelledby="<?= e($b['id_attr']) ?>-h">
  <h2 id="<?= e($b['id_attr']) ?>-h" class="statement__head reveal"<?= $b['lg'] ?>><?= e($b['title']) ?></h2>
  <div class="statement__body">
    <?php if ($b['excerpt']): ?><p class="statement__text reveal"<?= $b['lg'] ?>><?= e($b['excerpt']) ?></p><?php endif; ?>
    <?php if (!empty($x['fact'])): ?><p class="statement__fact reveal"<?= $b['lg'] ?>><?= e($x['fact']) ?></p><?php endif; ?>
  </div>
</section>
