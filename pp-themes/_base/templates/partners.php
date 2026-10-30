<?php
$pageTitle = t('partners_title'); $metaDesc = t('partners_intro');
$items = Content::entries('partner', lang());
?>
<?= PP::partial('band', ['title' => t('partners_title'), 'lead' => t('partners_intro'), 'tone' => 'field1']) ?>
<section class="partners chapter chapter--paper" data-ink="dark" aria-label="<?= e(t('partners_title')) ?>">
  <?php foreach ($items as $p): $m = $p['meta_arr']; $lg = $p['_fallback'] ? ' lang="' . e(I18n::hreflang($p['_lang'])) . '"' : ''; ?>
    <article class="partner reveal">
      <p class="partner__where"><?= e($m['country'] ?? '') ?><?= ($m['role'] ?? '') === 'Coordinator' ? ' · ' . e($m['role']) : '' ?></p>
      <h2 class="partner__name"><?php if (!empty($m['url'])): ?><a href="<?= e($m['url']) ?>" rel="noopener" target="_blank"><?= e($p['title']) ?></a><?php else: ?><?= e($p['title']) ?><?php endif; ?></h2>
      <p class="partner__about"<?= $lg ?>><?= e($p['excerpt']) ?></p>
      <?php if ($p['image']): ?><?= img($p['image'], $p['image_alt'] ?: ($m['short'] ?? $p['title']), '240px', 'partner__logo') ?><?php endif; ?>
    </article>
  <?php endforeach; ?>
</section>
