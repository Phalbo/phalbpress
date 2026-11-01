<?php
$pageTitle = t('stories_title'); $metaDesc = t('stories_intro');
$items = Content::entries('story', lang(), 0, 'menu_order ASC, pub_date DESC, id DESC');
$countries = array_values(array_unique(array_filter(array_map(fn($s) => $s['meta_arr']['country'] ?? '', $items))));
$heading = t('stories_heading');
$head = Layouts::get('stories', 'head');
$list = Layouts::get('stories', 'stories');
?>
<?= PP::partial('band', ['title' => t('stories_title'), 'lead' => t('stories_intro'), 'tone' => 'accent', 'layout' => $head, 'image' => in_array($head, ['split', 'cover'], true) ? ($items[0]['image'] ?? '') : '', 'crumb' => [[t('home'), link_to('')]]]) ?>
<section class="stories chapter chapter--paper stories--<?= e($list) ?>" data-ink="dark" data-pp-part="stories" aria-label="<?= e(t('stories_title')) ?>">
  <?php if ($heading !== 'stories_heading' && $heading !== ''): ?><h2 class="stories__head"><?= e($heading) ?></h2><?php endif; ?>
  <?php if (!$items): ?>
    <div class="stories__empty">
      <p class="stories__soon"><?= e(t('stories_empty')) ?></p>
      <?php $facts = array_filter([1, 2, 3], fn($i) => !in_array(t('stories_fact' . $i), ['', 'stories_fact' . $i], true)); if ($facts): ?>
      <ul class="stories__facts">
        <?php foreach ($facts as $i): ?><li><?= th('stories_fact' . $i) ?></li><?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <?php if (count($countries) > 1): ?>
      <div class="filter" role="group" aria-label="<?= e(t('filter_label')) ?>" data-filter="country" hidden>
        <button type="button" aria-pressed="true" data-f=""><?= e(t('categories_all')) ?></button>
        <?php foreach ($countries as $c): ?><button type="button" aria-pressed="false" data-f="<?= e($c) ?>"><?= e($c) ?></button><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="storylist" data-filter-items>
    <?php foreach ($items as $i => $s): $m = $s['meta_arr']; $anon = !empty($m['anonymous']); $lg = $s['_fallback'] ? ' lang="' . e(I18n::hreflang($s['_lang'])) . '"' : ''; $role = $s['x']['role'] ?? ($m['role'] ?? ''); ?>
      <article class="story reveal<?= $i % 2 && $list === 'zigzag' ? ' story--flip' : '' ?>" data-country="<?= e($m['country'] ?? '') ?>">
        <a class="story__pic<?= e(focus_class($s)) ?>" href="<?= e(entry_url($s)) ?>" tabindex="-1" aria-hidden="true">
          <?php if ($s['image'] && !$anon): ?><?= img($s['image'], '', '(min-width: 1000px) 40vw, 100vw') ?>
          <?php else: ?><span class="story__initial"><?= e(mb_substr($s['title'], 0, 1)) ?></span><?php endif; ?>
        </a>
        <div class="story__text">
          <p class="story__where"><?= e(trim(($m['country'] ?? '') . ($role ? ' · ' . $role : ''))) ?></p>
          <h3 class="story__name"<?= $lg ?>><a href="<?= e(entry_url($s)) ?>"><?= e($s['title']) ?></a></h3>
          <?php if ($s['excerpt']): ?><blockquote class="story__quote"<?= $lg ?>><p><?= e($s['excerpt']) ?></p></blockquote><?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
