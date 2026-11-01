<?php
$pageTitle = $item['title']; $metaDesc = $item['excerpt'] ?: $item['title']; $ogImage = $item['image']; $seoType = 'event'; $seoDate = Events::start($item);
$lg = $item['_fallback'] ? ' lang="' . e(I18n::hreflang($item['_lang'])) . '"' : '';
$m = $item['meta_arr'];
$head = Layouts::get('event', 'head', $item);
$imgInHead = $item['image'] && in_array($head, ['split', 'cover'], true);
$meta = '<span class="band__cat">' . e(Events::when($item)) . '</span>' . (!empty($item['x']['place']) ? ' · ' . e($item['x']['place']) : '');
?>
<?= PP::partial('band', ['title' => $item['title'], 'lead' => $item['excerpt'], 'tone' => 'field2', 'lg' => $lg, 'meta' => $meta, 'layout' => $head,
    'image' => $imgInHead ? $item['image'] : '', 'imageAlt' => $item['image_alt'], 'focus' => focus_class($item),
    'crumb' => [[t('home'), link_to('')], [t('events_title'), PP::link(Events::slug(lang()))]]]) ?>
<article class="article chapter chapter--paper body--aside" data-ink="dark" data-pp-part="body">
  <?= PP::partial('fallback', ['item' => $item]) ?>
  <?php if ($item['image'] && !$imgInHead): ?><figure class="article__img"><?= img($item['image'], $item['image_alt'], '100vw', '', false) ?></figure><?php endif; ?>
  <div class="prose"<?= $lg ?>><?= PP::body($item['body']) ?></div>
  <aside class="article__side eventinfo">
    <dl class="eventinfo__list">
      <dt><?= e(t('event_when')) ?></dt><dd><?= e(Events::when($item)) ?></dd>
      <?php if (!empty($item['x']['place'])): ?><dt><?= e(t('event_where')) ?></dt><dd<?= $lg ?>><?= e($item['x']['place']) ?></dd><?php endif; ?>
    </dl>
    <?php if (!empty($m['url'])): ?><p><a class="btn btn--dark" href="<?= e($m['url']) ?>" target="_blank" rel="noopener"><?= e(t('event_link')) ?></a></p><?php endif; ?>
    <p><a class="btn" href="<?= e(Events::icsUrl($item)) ?>" download><?= e(t('event_ics')) ?></a></p>
    <?= PP::partial('share', ['url' => entry_url($item), 'title' => $item['title']]) ?>
  </aside>
</article>
