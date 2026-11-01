<?php /* Galleria dentro un testo ([gallery:…]). $items = [[percorso, descrizione], …]. Ogni foto apre la versione grande (lightbox con JS, il file senza). */
$n = count($items);
?>
<div class="pp-gallery pp-gallery--<?= min($n, 6) ?>" data-lightbox-group>
  <ul class="pp-gallery__list">
    <?php foreach ($items as [$path, $alt]): ?>
      <li class="pp-gallery__item"><a href="<?= e(PP::upload($path)) ?>" data-lightbox<?= $alt !== '' ? ' data-caption="' . e($alt) . '"' : '' ?>><?= img($path, $alt, $n > 1 ? '(min-width: 900px) 33vw, 50vw' : '100vw') ?></a></li>
    <?php endforeach; ?>
  </ul>
</div>
