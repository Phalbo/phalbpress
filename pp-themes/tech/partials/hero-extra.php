<?php
/* Tech: il segno grafico dell'apertura (sopra la foto, sotto il titolo). $b = il blocco; campo "Segno grafico" del blocco:
   '' mosaico di quadrati, dots puntini, bars barre, rings cerchi, none nessuno. Su telefono non c'è (tech/theme.css). */
$mark = (string)($mark ?? '');
if ($mark === 'none') return;
$id = (int)$b['id'];
?>
<svg class="tech-mark tech-mark--<?= e($mark ?: 'mosaic') ?>" viewBox="0 0 160 160" aria-hidden="true" focusable="false">
<?php if ($mark === 'dots'):
  for ($r = 0; $r < 5; $r++) for ($c = 0; $c < 5; $c++): $k = ($r * 5 + $c * 3 + $id) % 7; ?>
  <circle cx="<?= $c * 32 + 16 ?>" cy="<?= $r * 32 + 16 ?>" r="<?= $k === 0 ? 13 : ($k < 3 ? 9 : 5) ?>" class="tm tm--<?= $k === 0 ? 'a' : ($k < 3 ? 'b' : 'o') ?>" style="--i:<?= $r * 5 + $c ?>"/>
<?php endfor; elseif ($mark === 'bars'):
  for ($c = 0; $c < 8; $c++): $h = 30 + (($c * 37 + $id * 11) % 110); ?>
  <rect x="<?= $c * 20 + 2 ?>" y="<?= 158 - $h ?>" width="14" height="<?= $h ?>" rx="3" class="tm tm--<?= $c % 3 === 0 ? 'a' : ($c % 3 === 1 ? 'b' : 'o') ?>" style="--i:<?= $c ?>"/>
<?php endfor; elseif ($mark === 'rings'):
  for ($n = 0; $n < 5; $n++): ?>
  <circle cx="80" cy="80" r="<?= 14 + $n * 15 ?>" fill="none" stroke-width="<?= $n === 2 ? 8 : 3 ?>" class="tm tm-ring tm--<?= $n === 2 ? 'a' : ($n === 4 ? 'b' : 'o') ?>" style="--i:<?= $n ?>"/>
<?php endfor; else:
  for ($r = 0; $r < 4; $r++) for ($c = 0; $c < 4; $c++): $k = ($r * 4 + $c * 7 + $id) % 9; ?>
  <rect x="<?= $c * 40 + 2 ?>" y="<?= $r * 40 + 2 ?>" width="36" height="36" rx="<?= $k === 3 ? 18 : 4 ?>" class="tm tm--<?= $k < 3 ? 'a' : ($k < 5 ? 'b' : ($k < 6 ? 'c' : 'o')) ?>" style="--i:<?= $r * 4 + $c ?>"/>
<?php endfor; endif; ?>
</svg>
