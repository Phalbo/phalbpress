<?php /* Intestazione delle pagine interne. $title, $lead, $tone (paper|accent|field1|field2|dark), $lg (attributo lang), $meta (HTML), $kicker, $image, $imageAlt */
$tone = $tone ?? 'field1';
?>
<header class="band <?= e(class_exists('Blocks') ? Blocks::toneClass($tone) : 'chapter chapter--' . $tone) ?><?= mb_strlen($title) > 24 ? ' band--long' : '' ?><?= !empty($image) ? ' band--img' : '' ?>" data-ink="<?= $tone === 'dark' ? 'light' : 'dark' ?>">
  <?php if (!empty($kicker)): ?><p class="band__kicker"><?= $kicker ?></p><?php endif; ?>
  <h1 class="band__title"<?= $lg ?? '' ?>><?= e($title) ?></h1>
  <?php if (!empty($lead)): ?><p class="band__lead"<?= $lg ?? '' ?>><?= e($lead) ?></p><?php endif; ?>
  <?php if (!empty($meta)): ?><p class="band__meta"><?= $meta ?></p><?php endif; ?>
  <?php if (!empty($image)): ?><figure class="band__img"><?= img($image, $imageAlt ?? '', '100vw', '', false) ?></figure><?php endif; ?>
</header>
