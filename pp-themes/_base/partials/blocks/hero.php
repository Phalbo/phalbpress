<?php
/* Apertura. $b (blocco), $x (campi tradotti), $m (campi comuni). Con "switch" la frase si può leggere in tutte le lingue. */
$line2 = trim((string)($x['line2'] ?? ''));
$lines = [];
if (!empty($m['switch'])) {
    foreach (I18n::codes() as $l) {
        $tr = Content::tr('entry', (int)$b['id'], $l);
        if (!$tr || trim((string)$tr['title']) === '') continue;
        $tx = json_decode((string)($tr['extra'] ?? ''), true) ?: [];
        $lines[$l] = ['l1' => $tr['title'], 'l2' => (string)($tx['line2'] ?? ''), 'lang' => I18n::hreflang($l), 'name' => I18n::name($l)];
    }
}
$cta = Blocks::href((string)($m['cta_url'] ?? ''));
$tag = $i === 0 ? 'h1' : 'h2';
?>
<section class="hero <?= e(Blocks::toneClass($b['tone'])) ?><?= $b['image'] ? ' hero--img' : '' ?>" id="<?= e($b['id_attr']) ?>" data-ink="<?= $b['tone'] === 'dark' ? 'light' : 'dark' ?>"<?= count($lines) > 1 ? ' data-hero data-lines="' . e(json_encode($lines, JSON_UNESCAPED_UNICODE)) . '"' : '' ?>>
  <?php if ($b['image']): ?><figure class="hero__img"><?= img($b['image'], $b['image_alt'], '100vw', '', false) ?></figure><?php endif; ?>
  <<?= $tag ?> class="hero__title" data-hero-title<?= $b['lg'] ?>>
    <span class="hero__l1" data-l1><?= e($b['title']) ?></span>
    <?php if ($line2 !== '' || $lines): ?><span class="hero__l2" data-l2><?= e($line2) ?></span><?php endif; ?>
  </<?= $tag ?>>
  <?php if (count($lines) > 1): ?>
  <div class="hero__say" role="group" aria-label="<?= e($x['switch_label'] ?? t('languages')) ?>">
    <span class="hero__say-label"><?= e($x['switch_label'] ?? t('languages')) ?></span>
    <?php foreach ($lines as $l => $ln): ?><button type="button" data-say="<?= e($l) ?>" lang="<?= e($ln['lang']) ?>" aria-pressed="<?= $l === lang() ? 'true' : 'false' ?>"><?= e($ln['name']) ?></button><?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php if ($b['excerpt'] || $cta): ?>
  <div class="hero__aside">
    <?php if ($b['excerpt']): ?><p class="hero__sentence"<?= $b['lg'] ?>><?= e($b['excerpt']) ?></p><?php endif; ?>
    <?php if ($cta && !empty($x['cta_label'])): ?><a class="btn btn--dark" href="<?= e($cta) ?>"><?= e($x['cta_label']) ?></a><?php endif; ?>
  </div>
  <?php endif; ?>
</section>
