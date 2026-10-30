<?php $steps = []; for ($n = 1; $n <= 6; $n++) if (!empty($x['s' . $n . '_t'])) $steps[] = [$x['s' . $n . '_t'], $x['s' . $n . '_d'] ?? '']; ?>
<section class="steps <?= e(Blocks::toneClass($b['tone'])) ?>" id="<?= e($b['id_attr']) ?>" data-ink="<?= $b['tone'] === 'dark' ? 'light' : 'dark' ?>"<?= $b['title'] ? ' aria-labelledby="' . e($b['id_attr']) . '-h"' : '' ?>>
  <?php if ($b['title']): ?><h2 id="<?= e($b['id_attr']) ?>-h" class="steps__head sr-only"<?= $b['lg'] ?>><?= e($b['title']) ?></h2><?php endif; ?>
  <ol class="steps__list">
  <?php foreach ($steps as $n => [$line, $text]): ?>
    <li class="step step--<?= $n + 1 ?> reveal">
      <p class="step__line"<?= $b['lg'] ?>><?= e($line) ?></p>
      <?php if ($text): ?><p class="step__text"<?= $b['lg'] ?>><?= e($text) ?></p><?php endif; ?>
    </li>
  <?php endforeach; ?>
  </ol>
</section>
