<?php
$item = $page;
$pageTitle = $page['title']; $metaDesc = $page['excerpt'] ?: $page['title']; $ogImage = $page['image'] ?? '';
$lg = $page['_fallback'] ? ' lang="' . e(I18n::hreflang($page['_lang'])) . '"' : '';
// indice della pagina dai titoli H2
$toc = [];
$body = preg_replace_callback('~<h2>(.*?)</h2>~s', function ($m) use (&$toc) {
    $id = Media::slug(strip_tags($m[1])) ?: 's' . count($toc);
    $toc[] = [$id, strip_tags($m[1])];
    return '<h2 id="' . e($id) . '">' . $m[1] . '</h2>';
}, PP::body($page['body']));
$head = Layouts::get('page', 'head', $page);
$lay = Layouts::get('page', 'body', $page);
$hasToc = count($toc) > 1;
?>
<?= PP::partial('band', ['title' => $page['title'], 'lead' => $page['excerpt'], 'tone' => 'field1', 'lg' => $lg, 'image' => $page['image'] ?? '', 'imageAlt' => $page['image_alt'] ?? '',
    'layout' => $head, 'crumb' => [[t('home'), link_to('')]]]) ?>
<article class="article chapter chapter--paper body--<?= e($lay) ?><?= $hasToc && $lay === 'aside' ? ' article--toc' : '' ?>" data-ink="dark" data-pp-part="body">
  <?= PP::partial('fallback', ['item' => $page]) ?>
  <?php if ($hasToc && $lay === 'read'): ?>
    <nav class="toc toc--inline" aria-label="<?= e(t('on_this_page')) ?>">
      <p class="toc__head"><?= e(t('on_this_page')) ?></p>
      <ol class="toc__list"><?php foreach ($toc as [$id, $label]): ?><li><a href="#<?= e($id) ?>"<?= $lg ?>><?= e($label) ?></a></li><?php endforeach; ?></ol>
    </nav>
  <?php endif; ?>
  <div class="prose"<?= $lg ?>><?= $body ?></div>
  <?php if ($lay === 'aside'): ?>
    <aside class="article__side">
      <?php if ($hasToc): ?>
      <nav class="toc" aria-label="<?= e(t('on_this_page')) ?>">
        <p class="toc__head"><?= e(t('on_this_page')) ?></p>
        <ol class="toc__list"><?php foreach ($toc as [$id, $label]): ?><li><a href="#<?= e($id) ?>"<?= $lg ?>><?= e($label) ?></a></li><?php endforeach; ?></ol>
      </nav>
      <?php endif; ?>
      <?= PP::partial('share', ['url' => entry_url($page), 'title' => $page['title']]) ?>
    </aside>
  <?php endif; ?>
</article>
