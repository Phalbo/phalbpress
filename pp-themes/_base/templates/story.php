<?php
/* Pagina di una storia o di un lavoro. Quattro disposizioni (Layouts, parte "work"):
   portrait = foto verticale e nome (storie di persone) · sheet = dati in una colonna fissa a lato · showcase = foto grande su fondo scuro
   · journal = racconto con immagini a tutta larghezza. In fondo, per i lavori con categorie: precedente/successivo e altri della stessa categoria. */
$m = $item['meta_arr']; $anon = !empty($m['anonymous']);
$L = lang();
$pageTitle = $item['title']; $metaDesc = $item['excerpt'] ?: (Stories::line($item) ?: $item['title']); $ogImage = $anon ? '' : $item['image']; $ogAlt = $item['image_alt'] ?? '';
$lg = $item['_fallback'] ? ' lang="' . e(I18n::hreflang($item['_lang'])) . '"' : '';
$role = $item['x']['role'] ?? ($m['role'] ?? '');
$video = preg_match('~^[A-Za-z0-9_-]{6,20}$~', (string)($m['video'] ?? '')) ? $m['video'] : '';
$body = PP::body($item['body']);
// galleria del lavoro (campo "Galleria"): dopo il testo
foreach (PP::$config['types']['story']['fields'] ?? [] as $gk => $gf) if (($gf['type'] ?? '') === 'gallery') $body .= PP::gallery((string)($m[$gk] ?? ''));
// il video del campo non si ripete se è già nel testo
if ($video && str_contains($item['body'], '[video:' . $video . ']')) $video = '';
$lay = Layouts::get('story', 'work', $item);
$facts = Stories::facts($item);
$allCats = Stories::categories($L);
$myCats = array_values(array_filter(array_map(fn($id) => $allCats[$id] ?? null, Stories::catIds($item))));
$year = $myCats ? Stories::year($item) : '';
[$prev, $next] = $myCats ? Stories::around($item, $L) : [null, null];
$related = $myCats ? Stories::related($item, $L, 3) : [];
$crumb = [[t('stories_title'), section_link('stories')]];
if ($myCats) $crumb[] = [$myCats[0]['title'], Stories::catUrl($myCats[0])];
$hasImg = $item['image'] && !$anon;
$alt = $item['image_alt'] ?: $item['title'];

/* dati del lavoro: cliente, attività… poi categorie e anno */
$factsHtml = function (string $cls) use ($facts, $myCats, $year) {
    if (!$facts && !$myCats && $year === '') return '';
    $h = '<dl class="' . $cls . '">';
    foreach ($facts as [$label, $value]) $h .= '<div class="facts__row"><dt>' . e($label) . '</dt><dd>' . nl2br(e($value)) . '</dd></div>';
    if ($myCats) {
        $h .= '<div class="facts__row"><dt>' . e(t('story_categories')) . '</dt><dd><ul class="facts__cats">';
        foreach ($myCats as $c) $h .= '<li><a href="' . e(Stories::catUrl($c)) . '">' . e($c['title']) . '</a></li>';
        $h .= '</ul></dd></div>';
    }
    if ($year !== '') $h .= '<div class="facts__row"><dt>' . e(t('story_year')) . '</dt><dd>' . e($year) . '</dd></div>';
    return $h . '</dl>';
};
$crumbHtml = '<nav class="band__crumb work__crumb" aria-label="' . e(t('breadcrumb')) . '"><ol>';
foreach ($crumb as [$label, $href]) $crumbHtml .= '<li><a href="' . e($href) . '">' . e($label) . '</a></li>';
$crumbHtml .= '</ol></nav>';
?>
<?php if ($lay === 'portrait'): ?>
<article class="storypage chapter chapter--accent" data-ink="dark">
  <div class="storypage__pic">
    <?php if ($hasImg): ?><?= img($item['image'], $alt, '(min-width: 1000px) 40vw, 100vw', '', false) ?>
    <?php else: ?><span class="story__initial"><?= e(mb_substr($item['title'], 0, 1)) ?></span><?php endif; ?>
  </div>
  <div class="storypage__text">
    <p class="band__meta"><a href="<?= e(section_link('stories')) ?>"><?= e(t('stories_title')) ?></a><?= !empty($m['country']) ? ' · ' . e($m['country']) : '' ?><?= $role ? ' · ' . e($role) : '' ?></p>
    <h1 class="storypage__name"<?= $lg ?>><?= e($item['title']) ?></h1>
    <?php if ($item['excerpt']): ?><blockquote class="storypage__quote"<?= $lg ?>><p><?= e($item['excerpt']) ?></p></blockquote><?php endif; ?>
  </div>
</article>
<section class="article chapter chapter--paper" data-ink="dark" aria-label="<?= e($item['title']) ?>" data-pp-part="work">
  <?= PP::partial('fallback', ['item' => $item]) ?>
  <?= $factsHtml('facts facts--row') ?>
  <?php if ($video) echo PP::partial('video', ['id' => $video]); ?>
  <div class="prose"<?= $lg ?>><?= $body ?></div>
  <?= PP::partial('share', ['url' => entry_url($item), 'title' => $item['title']]) ?>
  <p class="back"><a href="<?= e(section_link('stories')) ?>"><?= e(t('back_to')) ?> <?= e(t('stories_title')) ?></a></p>
</section>

<?php elseif ($lay === 'sheet'): ?>
<article class="work work--sheet chapter chapter--paper" data-ink="dark" data-pp-part="work"><div class="work__in">
  <header class="work__head">
    <?= $crumbHtml ?>
    <h1 class="work__title"<?= $lg ?>><?= e($item['title']) ?></h1>
    <?php if ($item['excerpt']): ?><p class="work__lead"<?= $lg ?>><?= e($item['excerpt']) ?></p><?php endif; ?>
  </header>
  <aside class="work__side">
    <?= $factsHtml('facts') ?>
    <?= PP::partial('share', ['url' => entry_url($item), 'title' => $item['title']]) ?>
  </aside>
  <div class="work__main">
    <?= PP::partial('fallback', ['item' => $item]) ?>
    <?php if ($hasImg): ?><figure class="work__img<?= e(focus_class($item)) ?>"><?= img($item['image'], $alt, '(min-width: 1000px) 62vw, 100vw', '', false) ?></figure><?php endif; ?>
    <?php if ($video) echo PP::partial('video', ['id' => $video]); ?>
    <div class="prose"<?= $lg ?>><?= $body ?></div>
  </div>
</div></article>

<?php elseif ($lay === 'showcase'): ?>
<article class="work work--showcase" data-pp-part="work">
  <header class="work__stage chapter chapter--ink" data-ink="light"><div class="work__in">
    <?= $crumbHtml ?>
    <?php if ($hasImg): ?><figure class="work__img"><?= img($item['image'], $alt, '(min-width: 1200px) 1100px, 100vw', '', false) ?></figure>
    <?php elseif ($video): echo PP::partial('video', ['id' => $video]); $video = ''; endif; ?>
    <h1 class="work__title"<?= $lg ?>><?= e($item['title']) ?></h1>
    <?php if ($item['excerpt']): ?><p class="work__lead"<?= $lg ?>><?= e($item['excerpt']) ?></p><?php endif; ?>
  </div></header>
  <div class="work__body chapter chapter--paper" data-ink="dark"><div class="work__in">
    <?= $factsHtml('facts facts--row') ?>
    <?= PP::partial('fallback', ['item' => $item]) ?>
    <?php if ($video) echo PP::partial('video', ['id' => $video]); ?>
    <div class="prose"<?= $lg ?>><?= $body ?></div>
    <?= PP::partial('share', ['url' => entry_url($item), 'title' => $item['title']]) ?>
  </div></div>
</article>

<?php else: /* journal */ ?>
<article class="work work--journal chapter chapter--paper" data-ink="dark" data-pp-part="work">
  <header class="work__head work__in">
    <?= $crumbHtml ?>
    <h1 class="work__title"<?= $lg ?>><?= e($item['title']) ?></h1>
    <?php if ($item['excerpt']): ?><p class="work__lead"<?= $lg ?>><?= e($item['excerpt']) ?></p><?php endif; ?>
    <?= $factsHtml('facts facts--row') ?>
  </header>
  <?php if ($hasImg): ?><figure class="work__img work__bleed<?= e(focus_class($item)) ?>"><?= img($item['image'], $alt, '100vw', '', false) ?></figure><?php endif; ?>
  <div class="work__main work__in">
    <?= PP::partial('fallback', ['item' => $item]) ?>
    <?php if ($video) echo PP::partial('video', ['id' => $video]); ?>
    <div class="prose"<?= $lg ?>><?= $body ?></div>
    <?= PP::partial('share', ['url' => entry_url($item), 'title' => $item['title']]) ?>
  </div>
</article>
<?php endif; ?>

<?php if ($lay !== 'portrait' || $prev || $next || $related): ?>
<section class="worknav chapter chapter--paper" data-ink="dark" aria-label="<?= e(t('stories_related')) ?>">
  <?php if ($related): ?>
  <h2 class="worknav__head"><?= e(t('stories_related')) ?></h2>
  <ul class="worknav__list">
    <?php foreach ($related as $r): $rl = $r['_fallback'] ? ' lang="' . e(I18n::hreflang($r['_lang'])) . '"' : ''; ?>
      <li class="worknav__item">
        <a class="worknav__pic<?= e(focus_class($r)) ?>" href="<?= e(entry_url($r)) ?>" tabindex="-1" aria-hidden="true"><?= $r['image'] ? img($r['image'], '', '(min-width: 900px) 30vw, 100vw') : '' ?></a>
        <h3 class="worknav__name"<?= $rl ?>><a href="<?= e(entry_url($r)) ?>"><?= e($r['title']) ?></a></h3>
        <?php if (($rline = Stories::line($r)) !== ''): ?><p class="worknav__line"<?= $rl ?>><?= e($rline) ?></p><?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
  <?php if ($prev || $next): ?>
  <nav class="pager" aria-label="<?= e(t('stories_pager')) ?>">
    <?php if ($prev): ?><a class="pager__prev" href="<?= e(entry_url($prev)) ?>" rel="prev"><span class="pager__dir"><?= e(t('stories_prev')) ?></span> <span class="pager__name"><?= e($prev['title']) ?></span></a><?php endif; ?>
    <?php if ($next): ?><a class="pager__next" href="<?= e(entry_url($next)) ?>" rel="next"><span class="pager__dir"><?= e(t('stories_next')) ?></span> <span class="pager__name"><?= e($next['title']) ?></span></a><?php endif; ?>
  </nav>
  <?php endif; ?>
  <?php if ($lay !== 'portrait'): ?><p class="back"><a href="<?= e($myCats ? Stories::catUrl($myCats[0]) : section_link('stories')) ?>"><?= e(t('back_to')) ?> <?= e($myCats ? $myCats[0]['title'] : t('stories_title')) ?></a></p><?php endif; ?>
</section>
<?php endif; ?>
