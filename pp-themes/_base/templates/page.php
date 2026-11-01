<?php
$item = $page;
$pageTitle = $page['title']; $metaDesc = $page['excerpt'] ?: $page['title']; $ogImage = $page['image'] ?? '';
$lg = $page['_fallback'] ? ' lang="' . e(I18n::hreflang($page['_lang'])) . '"' : '';
// indice della pagina dai titoli H2
$toc = [];
$body = preg_replace_callback('~<h2>(.*?)</h2>~s', function ($m) use (&$toc) {
    // il testo del titolo è HTML (es. "&amp;"): si decodifica, perché l'indice lo codifica di nuovo
    $label = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $id = Media::slug($label) ?: 's' . count($toc);
    $toc[] = [$id, $label];
    return '<h2 id="' . e($id) . '">' . $m[1] . '</h2>';
}, PP::body($page['body']));
$head = Layouts::get('page', 'head', $page);
$lay = Layouts::get('page', 'body', $page);
$hasToc = count($toc) > 1;
// pagine madri: percorso Home / Madre / Pagina, ed elenco delle figlie sotto il testo
$crumb = [[t('home'), link_to('')]];
$seen = [(int)$page['id']];
$pid = page_parent($page);
$chain = [];
while ($pid && !in_array($pid, $seen, true) && count($chain) < 4) {
    $seen[] = $pid;
    $par = Content::byId($pid, lang());
    if (!$par || ($par['type'] ?? '') !== 'page') break;
    array_unshift($chain, [$par['title'], PP::entryUrl($par)]);
    $pid = page_parent($par);
}
$crumb = array_merge($crumb, $chain);
$children = array_values(array_filter(Content::entries('page', lang(), 0, 'menu_order ASC, id ASC'), fn($c) => page_parent($c) === (int)$page['id']));
$listLay = Layouts::get('page', 'list', $page);
?>
<?= PP::partial('band', ['title' => $page['title'], 'lead' => $page['excerpt'], 'tone' => 'field1', 'lg' => $lg, 'image' => $page['image'] ?? '', 'imageAlt' => $page['image_alt'] ?? '',
    'layout' => $head, 'focus' => focus_class($page), 'crumb' => $crumb]) ?>
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
<?php if ($children): $ct = trim(t('children_title')); ?>
<section class="children newslist chapter chapter--paper list--<?= e($listLay) ?>" data-ink="dark" aria-label="<?= e($ct !== '' && $ct !== 'children_title' ? $ct : $page['title']) ?>" data-pp-part="list">
  <?php if ($ct !== '' && $ct !== 'children_title'): ?><h2 class="children__head reveal"><?= e($ct) ?></h2><?php endif; ?>
  <div class="cards"><?php foreach ($children as $c) echo PP::partial('card', ['e' => $c]); ?></div>
</section>
<?php endif; ?>
