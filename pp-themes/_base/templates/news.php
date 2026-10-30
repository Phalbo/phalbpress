<?php
$pageTitle = t('news_title'); $metaDesc = t('news_intro');
$items = Content::entries('news', lang(), 0, 'pub_date DESC, id DESC');
$cats = isset(PP::$config['types']['category']) ? Content::entries('category', lang()) : [];
$used = array_unique(array_filter(array_map(fn($n) => (int)($n['meta_arr']['category'] ?? 0), $items)));
$cats = array_values(array_filter($cats, fn($c) => in_array((int)$c['id'], $used, true)));
?>
<?= PP::partial('band', ['title' => t('news_title'), 'lead' => t('news_intro'), 'tone' => 'field2']) ?>
<section class="newslist chapter chapter--paper" data-ink="dark" aria-label="<?= e(t('news_title')) ?>">
  <?php if (!$items): ?><p class="empty"><?= e(t('news_empty')) ?></p><?php endif; ?>
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
