<?php
/* Scheda di un contenuto negli elenchi. $e (contenuto localizzato), $date (bool), $sizes */
$href = PP::entryUrl($e);
$lg = $e['_fallback'] ? ' lang="' . e(I18n::hreflang($e['_lang'])) . '"' : '';
$m = $e['meta_arr'];
$anon = !empty($m['anonymous']);
$cat = !empty($m['category']) ? Content::byId((int)$m['category'], lang()) : null;
?>
<article class="card reveal"<?= $cat ? ' data-cat="' . (int)$cat['id'] . '"' : '' ?><?= !empty($m['country']) ? ' data-country="' . e($m['country']) . '"' : '' ?>>
  <?php if ($e['image'] && !$anon): ?><a class="card__img<?= e(focus_class($e)) ?>" href="<?= e($href) ?>" tabindex="-1" aria-hidden="true"><?= img($e['image'], '', $sizes ?? '(min-width: 900px) 33vw, 100vw') ?></a><?php endif; ?>
  <div class="card__body">
    <?php if (!empty($date) || $cat): ?><p class="card__meta"><?php if (!empty($date) && $e['pub_date']): ?><time datetime="<?= e($e['pub_date']) ?>"><?= e(fmt_date($e['pub_date'])) ?></time><?php endif; ?><?= $cat ? '<span class="card__cat">' . e($cat['title']) . '</span>' : '' ?></p><?php endif; ?>
    <h3 class="card__title"<?= $lg ?>><?= $href ? '<a href="' . e($href) . '">' . e($e['title']) . '</a>' : e($e['title']) ?></h3>
    <?php if ($e['excerpt']): ?><p class="card__ex"<?= $lg ?>><?= e($e['excerpt']) ?></p><?php endif; ?>
  </div>
</article>
