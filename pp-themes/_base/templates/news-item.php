<?php
$pageTitle = $item['title']; $metaDesc = $item['excerpt'] ?: $item['title']; $seoType = 'article'; $seoDate = $item['pub_date']; $ogImage = $item['image']; $ogAlt = $item['image_alt'] ?? '';
$lg = $item['_fallback'] ? ' lang="' . e(I18n::hreflang($item['_lang'])) . '"' : '';
$cat = !empty($item['meta_arr']['category']) ? Content::byId((int)$item['meta_arr']['category'], lang()) : null;
$meta = '<time datetime="' . e($item['pub_date']) . '">' . e(fmt_date($item['pub_date'])) . '</time>' . ($cat ? ' · <span class="band__cat">' . e($cat['title']) . '</span>' : '');
$head = Layouts::get('news-item', 'head', $item);
$body = Layouts::get('news-item', 'body', $item);
// con l'intestazione divisa o al vivo la foto sta in alto; altrimenti apre il testo
$imgInHead = $item['image'] && in_array($head, ['split', 'cover'], true);
$others = array_values(array_filter(Content::entries('news', lang(), 4, 'pub_date DESC, id DESC'), fn($n) => (int)$n['id'] !== (int)$item['id']));
$others = array_slice($others, 0, 3);
?>
<?= PP::partial('band', ['title' => $item['title'], 'lead' => $item['excerpt'], 'tone' => 'field2', 'lg' => $lg, 'meta' => $meta, 'layout' => $head,
    'image' => $imgInHead ? $item['image'] : '', 'imageAlt' => $item['image_alt'], 'focus' => focus_class($item),
    'crumb' => [[t('home'), link_to('')], [t('news_title'), section_link('news')]]]) ?>
<article class="article chapter chapter--paper body--<?= e($body) ?>" data-ink="dark" data-pp-part="body">
  <?= PP::partial('fallback', ['item' => $item]) ?>
  <?php if ($item['image'] && !$imgInHead): ?><figure class="article__img"><?= img($item['image'], $item['image_alt'], '100vw', '', false) ?></figure><?php endif; ?>
  <div class="prose"<?= $lg ?>><?= PP::body($item['body']) ?></div>
  <?php if ($body === 'aside'): ?>
    <aside class="article__side">
      <p class="article__side-meta"><?= $meta ?></p>
      <?= PP::partial('share', ['url' => entry_url($item), 'title' => $item['title']]) ?>
      <?php if ($others): ?>
        <p class="article__side-head"><?= e(t('more_news')) ?></p>
        <ul class="rows2"><?php foreach ($others as $n): ?><li><a href="<?= e(entry_url($n)) ?>"><?= e($n['title']) ?></a><span><?= e(fmt_date($n['pub_date'])) ?></span></li><?php endforeach; ?></ul>
      <?php endif; ?>
    </aside>
  <?php else: ?>
    <?= PP::partial('share', ['url' => entry_url($item), 'title' => $item['title']]) ?>
  <?php endif; ?>
</article>
<?php if ($others && $body !== 'aside'): ?>
<section class="morenews chapter chapter--paper" data-ink="dark" aria-labelledby="more-news-h">
  <h2 id="more-news-h" class="morenews__head reveal"><?= e(t('more_news')) ?></h2>
  <div class="cards cards--news"><?php foreach ($others as $n) echo PP::partial('card', ['e' => $n, 'date' => true]); ?></div>
  <p class="back"><a href="<?= e(section_link('news')) ?>"><?= e(t('all_news')) ?></a></p>
</section>
<?php endif; ?>
