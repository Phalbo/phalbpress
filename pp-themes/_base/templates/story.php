<?php
$m = $item['meta_arr']; $anon = !empty($m['anonymous']);
$pageTitle = $item['title']; $metaDesc = $item['excerpt'] ?: $item['title']; $ogImage = $anon ? '' : $item['image'];
$lg = $item['_fallback'] ? ' lang="' . e(I18n::hreflang($item['_lang'])) . '"' : '';
$role = $item['x']['role'] ?? ($m['role'] ?? '');
$video = preg_match('~^[A-Za-z0-9_-]{6,20}$~', (string)($m['video'] ?? '')) ? $m['video'] : '';
?>
<article class="storypage chapter chapter--accent" data-ink="dark">
  <div class="storypage__pic">
    <?php if ($item['image'] && !$anon): ?><?= img($item['image'], $item['image_alt'] ?: $item['title'], '(min-width: 1000px) 40vw, 100vw', '', false) ?>
    <?php else: ?><span class="story__initial"><?= e(mb_substr($item['title'], 0, 1)) ?></span><?php endif; ?>
  </div>
  <div class="storypage__text">
    <p class="band__meta"><a href="<?= e(section_link('stories')) ?>"><?= e(t('stories_title')) ?></a><?= !empty($m['country']) ? ' · ' . e($m['country']) : '' ?><?= $role ? ' · ' . e($role) : '' ?></p>
    <h1 class="storypage__name"<?= $lg ?>><?= e($item['title']) ?></h1>
    <?php if ($item['excerpt']): ?><blockquote class="storypage__quote"<?= $lg ?>><p><?= e($item['excerpt']) ?></p></blockquote><?php endif; ?>
  </div>
</article>
<section class="article chapter chapter--paper" data-ink="dark" aria-label="<?= e($item['title']) ?>">
  <?= PP::partial('fallback', ['item' => $item]) ?>
  <?php if ($video) echo PP::partial('video', ['id' => $video]); ?>
  <div class="prose"<?= $lg ?>><?= PP::body($item['body']) ?></div>
  <?= PP::partial('share', ['url' => entry_url($item), 'title' => $item['title']]) ?>
  <p class="back"><a href="<?= e(section_link('stories')) ?>"><?= e(t('back_to')) ?> <?= e(t('stories_title')) ?></a></p>
</section>
