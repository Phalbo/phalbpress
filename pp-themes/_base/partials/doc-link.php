<?php /* $doc */ $has = $doc['file_active'] && $doc['file_size']; ?>
<p class="doclink">
  <?php if ($has): ?>
    <a href="<?= e(PP::docUrl($doc)) ?>" class="doclink__a"<?= $doc['_fallback'] ? ' lang="' . e(I18n::hreflang($doc['_lang'])) . '"' : '' ?>>
      <span class="doclink__title"><?= e(($doc['code'] ? $doc['code'] . ' · ' : '') . $doc['title']) ?></span>
      <span class="doclink__meta"><?= e(t('download')) ?> · <?= e($doc['file_ext']) ?> · <?= e(Media::human((int)$doc['file_size'])) ?><?= !$doc['file_is_translated'] && lang() !== I18n::$master ? ' · ' . e(t('doc_in_english')) : '' ?></span>
    </a>
  <?php else: ?>
    <span class="doclink__a is-planned"><span class="doclink__title"><?= e(($doc['code'] ? $doc['code'] . ' · ' : '') . $doc['title']) ?></span>
      <span class="doclink__meta"><?= e(fmt_date($doc['pub_date'])) ?></span></span>
  <?php endif; ?>
</p>
