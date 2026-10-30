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
?>
<?= PP::partial('band', ['title' => $page['title'], 'lead' => $page['excerpt'], 'tone' => 'field1', 'lg' => $lg, 'image' => $page['image'] ?? '', 'imageAlt' => $page['image_alt'] ?? '']) ?>
<article class="article chapter chapter--paper<?= count($toc) > 1 ? ' article--toc' : '' ?>" data-ink="dark">
  <?= PP::partial('fallback', ['item' => $page]) ?>
  <div class="prose"<?= $lg ?>><?= $body ?></div>
  <?php if (count($toc) > 1): ?>
    <nav class="toc" aria-label="<?= e(t('on_this_page')) ?>">
      <p class="toc__head"><?= e(t('on_this_page')) ?></p>
      <ol class="toc__list">
        <?php foreach ($toc as [$id, $label]): ?><li><a href="#<?= e($id) ?>"<?= $lg ?>><?= e($label) ?></a></li><?php endforeach; ?>
      </ol>
    </nav>
  <?php endif; ?>
</article>
