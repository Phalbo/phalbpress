<?php
/* Elenco delle storie (o dei lavori di un portfolio). Con $category è la pagina di una categoria: /{lang}/{storie}/{categoria}/.
   I filtri sono link a pagine vere (funzionano senza JavaScript e nel sito statico); la disposizione si sceglie per tipo
   o per singola categoria (Modifica visuale). Oltre i primi elementi, "Mostra altri" (con JS) alleggerisce la pagina. */
$cat = $category ?? null;
$L = lang();
$all = Stories::all($L);
$items = $cat ? Stories::inCategory($cat, $L) : $all;
$cats = Stories::categories($L);
$parentId = $cat ? (int)($cat['meta_arr']['parent'] ?? 0) : 0;
$parent = $parentId && isset($cats[$parentId]) ? $cats[$parentId] : null;
$nested = Stories::nested($L);
$top = Stories::children($L, 0, true);
// sottocategorie da mostrare: quelle della categoria aperta, o delle sue sorelle
$area = $cat ? ($parent ?: $cat) : null;
$subs = $area ? Stories::children($L, (int)$area['id'], true) : [];
$pageTitle = $cat ? $cat['title'] : t('stories_title');
$metaDesc = $cat ? ($cat['excerpt'] ?: t('stories_intro')) : t('stories_intro');
$heading = t('stories_heading');
$head = Layouts::get('stories', 'head');
$list = Layouts::get('stories', 'stories', $cat);
$crumb = [[t('home'), link_to('')]];
if ($cat) { $crumb[] = [t('stories_title'), section_link('stories')]; if ($parent) $crumb[] = [$parent['title'], Stories::catUrl($parent)]; }
$lead = $cat ? (string)$cat['excerpt'] : t('stories_intro');
$more = in_array($list, ['index', 'thumbs'], true) ? 40 : 24;
$isWork = (bool)$cats;   // storie con categorie = lavori di un portfolio: anno e categoria nelle schede
$sizes = ['thumbs' => '160px', 'cards' => '(min-width: 1200px) 22vw, (min-width: 700px) 30vw, 50vw', 'tiles' => '(min-width: 1200px) 22vw, (min-width: 700px) 30vw, 50vw', 'covers' => '(min-width: 1200px) 18vw, (min-width: 700px) 25vw, 50vw',
    'screens' => '(min-width: 900px) 45vw, 100vw', 'index' => '320px', 'mosaic' => '(min-width: 1000px) 33vw, 100vw', 'grid' => '(min-width: 1000px) 25vw, 50vw'][$list] ?? '(min-width: 1000px) 40vw, 100vw';
$countries = $isWork ? [] : array_values(array_unique(array_filter(array_map(fn($s) => $s['meta_arr']['country'] ?? '', $items))));
?>
<?php // intestazione grafica: la foto solo se la categoria ne ha una sua (mai l'ultimo lavoro, che cambia e copre il titolo) ?>
<?= PP::partial('band', ['title' => $pageTitle, 'lead' => $lead, 'tone' => 'accent', 'layout' => $head, 'image' => $cat && in_array($head, ['split', 'cover'], true) ? (string)$cat['image'] : '', 'crumb' => $crumb]) ?>
<section class="stories chapter chapter--paper stories--<?= e($list) ?>" data-ink="dark" data-pp-part="stories" aria-label="<?= e($pageTitle) ?>">
  <?php if ($heading !== 'stories_heading' && $heading !== '' && !$cat): ?><h2 class="stories__head"><?= e($heading) ?></h2><?php endif; ?>
  <?php if (count($top) > 1 || $subs): ?>
  <nav class="catnav<?= $nested ? ' catnav--areas' : '' ?>" aria-label="<?= e(t('filter_label')) ?>">
    <ul class="catnav__list">
      <?php if (!$nested): // con aree e divisioni in alto ci sono solo le aree: "Tutte" sta sotto, nell'area aperta ?>
      <li><a class="catnav__link" href="<?= e(section_link('stories')) ?>"<?= !$cat ? ' aria-current="page"' : '' ?>><?= e(t('categories_all')) ?> <span class="catnav__n"><?= count($all) ?></span></a></li>
      <?php endif; ?>
      <?php foreach ($top as $c): $on = $cat && ((int)$c['id'] === (int)$cat['id'] || (int)$c['id'] === $parentId); ?>
        <li><a class="catnav__link" href="<?= e(Stories::catUrl($c)) ?>"<?= $on ? ' aria-current="' . ((int)$c['id'] === (int)$cat['id'] ? 'page' : 'true') . '"' : '' ?>><?= e($c['title']) ?> <span class="catnav__n"><?= count(Stories::inCategory($c, $L)) ?></span></a></li>
      <?php endforeach; ?>
    </ul>
    <?php if ($subs): ?>
    <ul class="catnav__list catnav__list--sub">
      <li><a class="catnav__link" href="<?= e(Stories::catUrl($area)) ?>"<?= (int)$area['id'] === (int)$cat['id'] ? ' aria-current="page"' : '' ?>><?= e(t('categories_all')) ?></a></li>
      <?php foreach ($subs as $c): ?>
        <li><a class="catnav__link" href="<?= e(Stories::catUrl($c)) ?>"<?= (int)$c['id'] === (int)$cat['id'] ? ' aria-current="page"' : '' ?>><?= e($c['title']) ?> <span class="catnav__n"><?= count(Stories::inCategory($c, $L)) ?></span></a></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </nav>
  <?php endif; ?>
  <?php if (!$items): ?>
    <div class="stories__empty">
      <p class="stories__soon"><?= e(t('stories_empty')) ?></p>
      <?php $facts = array_filter([1, 2, 3], fn($i) => !in_array(t('stories_fact' . $i), ['', 'stories_fact' . $i], true)); if ($facts): ?>
      <ul class="stories__facts">
        <?php foreach ($facts as $i): ?><li><?= th('stories_fact' . $i) ?></li><?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <?php if (count($countries) > 1): ?>
      <div class="filter" role="group" aria-label="<?= e(t('filter_label')) ?>" data-filter="country" hidden>
        <button type="button" aria-pressed="true" data-f=""><?= e(t('categories_all')) ?></button>
        <?php foreach ($countries as $c): ?><button type="button" aria-pressed="false" data-f="<?= e($c) ?>"><?= e($c) ?></button><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="storylist" data-filter-items<?= count($items) > $more ? ' data-more="' . $more . '" data-more-label="' . e(t('stories_more')) . '"' : '' ?>>
    <?php foreach ($items as $i => $s): $m = $s['meta_arr']; $anon = !empty($m['anonymous']); $lg = $s['_fallback'] ? ' lang="' . e(I18n::hreflang($s['_lang'])) . '"' : '';
      $line = Stories::line($s); $cn = $isWork ? Stories::catName($s, $L) : ''; $yr = $isWork ? Stories::year($s) : ''; $href = entry_url($s); ?>
      <article class="story reveal<?= $i % 2 && $list === 'zigzag' ? ' story--flip' : '' ?><?= $s['image'] && !$anon ? '' : ' story--noimg' ?>" data-country="<?= e($m['country'] ?? '') ?>">
        <a class="story__pic<?= e(focus_class($s)) ?>" href="<?= e($href) ?>" tabindex="-1" aria-hidden="true">
          <?php if ($s['image'] && !$anon): ?><?= img($s['image'], '', $sizes) ?>
          <?php else: $mark = class_exists('Identity') ? Identity::logo(false, 'story__mark') : ''; ?><span class="story__initial<?= $mark ? ' story__initial--logo' : '' ?>"><?= $mark ? preg_replace('~\s(role="img"|aria-label="[^"]*")~', '', str_replace(' alt="' . e(Identity::name()) . '"', ' alt=""', $mark)) : e(mb_substr($s['title'], 0, 1)) ?></span><?php endif; ?>
          <?php if (!empty($m['video']) && $list === 'screens'): ?><span class="story__play" aria-hidden="true"></span><?php endif; ?>
        </a>
        <div class="story__text">
          <?php if ($yr !== '' && in_array($list, ['index'], true)): ?><p class="story__year"><?= e($yr) ?></p><?php endif; ?>
          <h3 class="story__name"<?= $lg ?>><a href="<?= e($href) ?>"><?= e($s['title']) ?></a></h3>
          <?php if ($line !== ''): ?><p class="story__where"<?= $lg ?>><?= e($line) ?></p><?php endif; ?>
          <?php if ($cn !== '' || ($yr !== '' && $list !== 'index')): ?><p class="story__meta"><?php if ($cn !== ''): ?><span class="story__cat"><?= e($cn) ?></span><?php endif; ?><?php if ($yr !== '' && $list !== 'index'): ?> <span class="story__yr"><?= e($yr) ?></span><?php endif; ?></p><?php endif; ?>
          <?php if ($s['excerpt'] && in_array($list, ['zigzag', 'grid', 'mosaic'], true)): ?><blockquote class="story__quote"<?= $lg ?>><p><?= e($s['excerpt']) ?></p></blockquote><?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
