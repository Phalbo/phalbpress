<?php
$pageTitle = $item['title']; $metaDesc = $item['excerpt'] ?: $item['title']; $seoType = 'article'; $seoDate = $item['pub_date']; $ogImage = $item['image'];
$lg = $item['_fallback'] ? ' lang="' . e(I18n::hreflang($item['_lang'])) . '"' : '';
$cat = !empty($item['meta_arr']['category']) ? Content::byId((int)$item['meta_arr']['category'], lang()) : null;
$meta = '<a href="' . e(section_link('news')) . '">' . e(t('news_title')) . '</a> · <time datetime="' . e($item['pub_date']) . '">' . e(fmt_date($item['pub_date'])) . '</time>' . ($cat ? ' · ' . e($cat['title']) : '');
?>
<?= PP::partial('band', ['title' => $item['title'], 'lead' => $item['excerpt'], 'tone' => 'field2', 'lg' => $lg, 'meta' => $meta]) ?>
<article class="article chapter chapter--paper" data-ink="dark">
  <?= PP::partial('fallback', ['item' => $item]) ?>
  <?php if ($item['image']): ?><figure class="article__img"><?= img($item['image'], $item['image_alt'], '100vw', '', false) ?></figure><?php endif; ?>
  <div class="prose"<?= $lg ?>><?= PP::body($item['body']) ?></div>
  <?= PP::partial('share', ['url' => entry_url($item), 'title' => $item['title']]) ?>
  <p class="back"><a href="<?= e(section_link('news')) ?>"><?= e(t('all_news')) ?></a></p>
</article>
