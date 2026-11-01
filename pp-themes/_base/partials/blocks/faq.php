<?php /* Domande frequenti a fisarmonica: <details> nativi, si aprono da tastiera e senza JavaScript. */
$qs = []; for ($n = 1; $n <= 6; $n++) if (trim((string)($x['q' . $n] ?? '')) !== '') $qs[] = [$x['q' . $n], (string)($x['a' . $n] ?? '')]; ?>
<section class="faq <?= e(Blocks::toneClass($b['tone'])) ?>" id="<?= e($b['id_attr']) ?>" data-ink="<?= $b['tone'] === 'dark' ? 'light' : 'dark' ?>" aria-labelledby="<?= e($b['id_attr']) ?>-h">
  <div class="faq__top">
    <h2 id="<?= e($b['id_attr']) ?>-h" class="faq__head reveal"<?= $b['lg'] ?>><?= e($b['title']) ?></h2>
    <?php if ($b['excerpt']): ?><p class="faq__intro reveal"<?= $b['lg'] ?>><?= e($b['excerpt']) ?></p><?php endif; ?>
  </div>
  <div class="faq__list">
    <?php foreach ($qs as [$q, $a]): ?>
      <details class="faq__item reveal"><summary class="faq__q"<?= $b['lg'] ?>><?= e($q) ?></summary><div class="faq__a"<?= $b['lg'] ?>><?= nl2br(e($a)) ?></div></details>
    <?php endforeach; ?>
  </div>
</section>
