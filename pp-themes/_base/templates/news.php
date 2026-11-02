<?php
$pageTitle = t('news_title'); $metaDesc = t('news_intro');
$items = Content::entries('news', lang(), 0, 'pub_date DESC, id DESC');
$cats = isset(PP::$config['types']['category']) ? Content::entries('category', lang()) : [];
$used = array_unique(array_filter(array_map(fn($n) => (int)($n['meta_arr']['category'] ?? 0), $items)));
$cats = array_values(array_filter($cats, fn($c) => in_array((int)$c['id'], $used, true)));
$head = Layouts::get('news', 'head');
$list = Layouts::get('news', 'list');
// "prima in evidenza": la più recente esce dalla griglia e va sopra, grande
$lead = $list === 'feature' && count($items) > 1 ? array_shift($items) : null;
?>
<?= PP::partial('band', ['title' => t('news_title'), 'lead' => t('news_intro'), 'tone' => 'field2', 'layout' => $head, 'image' => in_array($head, ['split', 'cover'], true) ? ($items[0]['image'] ?? ($lead['image'] ?? '')) : '',
    'crumb' => [[t('home'), link_to('')]]]) ?>
<section class="newslist chapter chapter--paper list--<?= e($list) ?>" data-ink="dark" aria-label="<?= e(t('news_title')) ?>" data-pp-part="list">
  <?php if (!$items && !$lead): ?><p class="empty"><?= e(t('news_empty')) ?></p><?php endif; ?>
  <?php if ($lead): $cat = !empty($lead['meta_arr']['category']) ? Content::byId((int)$lead['meta_arr']['category'], lang()) : null; $href = PP::entryUrl($lead);
    $lg = $lead['_fallback'] ? ' lang="' . e(I18n::hreflang($lead['_lang'])) . '"' : ''; ?>
    <article class="lead-item reveal">
      <?php if ($lead['image']): ?><a class="lead-item__img<?= e(focus_class($lead)) ?>" href="<?= e($href) ?>" tabindex="-1" aria-hidden="true"><?= img($lead['image'], '', '(min-width: 961px) 55vw, 100vw', '', false) ?></a><?php endif; ?>
      <div class="lead-item__body">
        <p class="lead-item__meta"><span class="lead-item__tag"><?= e(t('latest_label')) ?></span> <time datetime="<?= e($lead['pub_date']) ?>"><?= e(fmt_date($lead['pub_date'])) ?></time><?= $cat ? ' · <span class="card__cat">' . e($cat['title']) . '</span>' : '' ?></p>
        <h2 class="lead-item__title"<?= $lg ?>><a href="<?= e($href) ?>"><?= e($lead['title']) ?></a></h2>
        <?php if ($lead['excerpt']): ?><p class="lead-item__ex"<?= $lg ?>><?= e($lead['excerpt']) ?></p><?php endif; ?>
        <p><a class="btn btn--dark" href="<?= e($href) ?>"><?= e(t('read_more')) ?></a></p>
      </div>
    </article>
  <?php endif; ?>
  <?php if (count($cats) > 1): ?>
    <div class="filter" role="group" aria-label="<?= e(t('filter_label')) ?>" data-filter="cat" hidden>
      <button type="button" aria-pressed="true" data-f=""><?= e(t('categories_all')) ?></button>
      <?php foreach ($cats as $c): ?><button type="button" aria-pressed="false" data-f="<?= (int)$c['id'] ?>"><?= e($c['title']) ?></button><?php endforeach; ?>
    </div>
  <?php endif; ?>
  <div class="cards cards--news" data-filter-items>
    <?php foreach ($items as $n) echo PP::partial('card', ['e' => $n, 'date' => true]); ?>
  </div>
</section>
