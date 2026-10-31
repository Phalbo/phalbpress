<?php $nums = []; for ($n = 1; $n <= 4; $n++) if (trim((string)($m['n' . $n . '_v'] ?? '')) !== '') $nums[] = [$m['n' . $n . '_v'], $x['n' . $n . '_l'] ?? '']; ?>
<section class="figures figures--<?= count($nums) ?> <?= e(Blocks::toneClass($b['tone'])) ?>" id="<?= e($b['id_attr']) ?>" data-ink="<?= $b['tone'] === 'dark' ? 'light' : 'dark' ?>" aria-labelledby="<?= e($b['id_attr']) ?>-h">
  <div class="figures__top">
    <h2 id="<?= e($b['id_attr']) ?>-h" class="figures__head reveal"<?= $b['lg'] ?>><?= e($b['title']) ?></h2>
    <?php if ($b['excerpt']): ?><p class="figures__text reveal"<?= $b['lg'] ?>><?= e($b['excerpt']) ?></p><?php endif; ?>
  </div>
  <div class="figures__list">
    <?php foreach ($nums as $n => [$v, $l]): ?>
      <figure class="figure figure--<?= $n + 1 ?> reveal">
        <p class="figure__val"><span class="figure__num" data-count="<?= e(preg_replace('~[^0-9]~', '', (string)$v)) ?>"><?= e($v) ?></span></p>
        <figcaption class="figure__cap"<?= $b['lg'] ?>><?= e($l) ?></figcaption>
      </figure>
    <?php endforeach; ?>
  </div>
</section>
