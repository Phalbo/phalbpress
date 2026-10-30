<?php /* $tabBase: url senza lingua, $lang, $statuses */ ?>
<nav class="tabs" aria-label="Lingue">
  <?php foreach (I18n::codes() as $l): $s = $statuses[$l] ?? 'missing'; ?>
    <a href="<?= e($tabBase . '/' . $l) ?>" <?= $l === $lang ? 'aria-current="page"' : '' ?> class="tab tab--<?= e($s) ?>">
      <span class="tab__code"><?= e(I18n::label($l)) ?></span>
      <span class="tab__state"><?= e(I18n::statusLabel($s)) ?></span>
    </a>
  <?php endforeach; ?>
</nav>
