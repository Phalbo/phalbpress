<?php
/* Intestazione delle pagine interne.
   $title, $lead, $tone (paper|accent|field1|field2|dark), $lg (attributo lang), $meta (HTML), $kicker, $image, $imageAlt,
   $layout (left|center|split|cover|minimal: Layouts::get), $crumb ([[etichetta, link], …] percorso sopra il titolo).
   La struttura è piatta (percorso, etichetta, titolo, frase, dati, foto): ogni disposizione la sistema con la griglia del CSS. */
$layout = $layout ?? 'left';
$tone = $tone ?? 'field1';
if ($layout === 'minimal') $tone = 'paper';
if ($layout === 'cover' && !empty($image)) $tone = 'dark';
$hasImg = !empty($image);
$cls = 'band ' . (class_exists('Blocks') ? Blocks::toneClass($tone) : 'chapter chapter--' . $tone) . ' head--' . $layout
    . (mb_strlen($title) > 24 ? ' band--long' : '') . ($hasImg ? ' band--img' : ' band--noimg');
?>
<header class="<?= e($cls) ?>" data-ink="<?= $tone === 'dark' ? 'light' : 'dark' ?>" data-pp-part="head">
  <?php if (!empty($crumb)): ?>
  <nav class="band__crumb" aria-label="<?= e(t('breadcrumb')) ?>"><ol>
    <?php foreach ($crumb as [$label, $href]): ?><li><a href="<?= e($href) ?>"><?= e($label) ?></a></li><?php endforeach; ?>
  </ol></nav>
  <?php endif; ?>
  <?php if (!empty($kicker)): ?><p class="band__kicker"><?= $kicker ?></p><?php endif; ?>
  <h1 class="band__title"<?= $lg ?? '' ?>><?= e($title) ?></h1>
  <?php if (!empty($lead)): ?><p class="band__lead"<?= $lg ?? '' ?>><?= e($lead) ?></p><?php endif; ?>
  <?php if (!empty($meta)): ?><p class="band__meta"><?= $meta ?></p><?php endif; ?>
  <?php if ($hasImg): ?><figure class="band__img"><?= img($image, $imageAlt ?? '', $layout === 'split' ? '(min-width: 961px) 50vw, 100vw' : '100vw', '', false) ?></figure>
  <?php elseif (in_array($layout, ['split', 'cover'], true)): ?><span class="band__deco" aria-hidden="true"><i></i><i></i><i></i></span><?php endif; ?>
</header>
