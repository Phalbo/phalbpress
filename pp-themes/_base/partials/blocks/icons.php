<?php
/* Riquadri con icona (servizi, aree di lavoro): icona disegnata, titolo, testo, pulsante. Fino a 6, per riga 2-4. */
$items = [];
// senza un'icona scelta ("Automatica") ogni riquadro ne prende una diversa, così non restano vuoti; "Nessuna" la toglie
$auto = ['layers', 'send', 'book', 'chart', 'people', 'idea'];
for ($n = 1; $n <= 6; $n++) {
    $t = trim((string)($x["i{$n}_t"] ?? ''));
    if ($t === '') continue;
    $ic = (string)($m["i{$n}_icon"] ?? '');
    $items[] = ['icon' => $ic === 'none' ? '' : ($ic !== '' ? $ic : $auto[count($items) % 6]), 't' => $t, 'd' => trim((string)($x["i{$n}_d"] ?? '')), 'btn' => trim((string)($x["i{$n}_btn"] ?? '')), 'url' => Blocks::href((string)($m["i{$n}_url"] ?? ''))];
}
$per = in_array($m['per_row'] ?? '', ['2', '3', '4'], true) ? (int)$m['per_row'] : min(4, max(2, count($items)));
?>
<section class="icons icons--<?= $per ?><?= !empty($m['boxed']) ? ' icons--boxed' : '' ?> <?= e(Blocks::toneClass($b['tone'])) ?>" id="<?= e($b['id_attr']) ?>" data-ink="<?= $b['tone'] === 'dark' ? 'light' : 'dark' ?>"<?= $b['title'] ? ' aria-labelledby="' . e($b['id_attr']) . '-h"' : '' ?>>
  <?php if ($b['title'] || $b['excerpt']): ?><div class="icons__top">
    <?php if ($b['title']): ?><h2 id="<?= e($b['id_attr']) ?>-h" class="icons__head reveal"<?= $b['lg'] ?>><?= e($b['title']) ?></h2><?php endif; ?>
    <?php if ($b['excerpt']): ?><p class="icons__intro reveal"<?= $b['lg'] ?>><?= e($b['excerpt']) ?></p><?php endif; ?>
  </div><?php endif; ?>
  <ul class="icons__list">
    <?php foreach ($items as $it): ?>
      <li class="icons__item reveal">
        <?php if ($it['icon'] !== ''): ?><span class="icons__icon"><?= PP::partial('icon', ['icon' => $it['icon'], 'size' => 44]) ?></span><?php endif; ?>
        <h3 class="icons__title"<?= $b['lg'] ?>><?= e($it['t']) ?></h3>
        <?php if ($it['d'] !== ''): ?><p class="icons__text"<?= $b['lg'] ?>><?= nl2br(e($it['d'])) ?></p><?php endif; ?>
        <?php if ($it['btn'] !== '' && $it['url'] !== ''): ?><p class="icons__more"><a class="icons__btn" href="<?= e($it['url']) ?>"><span><?= e($it['btn']) ?></span><span class="sr-only">: <?= e($it['t']) ?></span></a></p><?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
</section>
