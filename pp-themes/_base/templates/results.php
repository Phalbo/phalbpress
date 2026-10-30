<?php
$pageTitle = t('results_title'); $metaDesc = t('results_intro');
$all = Content::docs(lang());
$groups = [];
foreach ($all as $d) $groups[$d['category']][] = $d;
?>
<?= PP::partial('band', ['title' => t('results_title'), 'lead' => t('results_intro'), 'tone' => 'dark']) ?>
<section class="results chapter chapter--paper" data-ink="dark" aria-label="<?= e(t('results_title')) ?>">
  <?php if (!$all): ?><p class="empty"><?= e(t('results_empty')) ?></p><?php endif; ?>
  <?php foreach (PP::$config['doc_categories'] ?? [] as $cat => $catLabel): if (empty($groups[$cat])) continue; $cl = t('cat_' . $cat); ?>
    <div class="results__group reveal">
      <h2 class="results__head"><?= e($cl !== 'cat_' . $cat ? $cl : $catLabel) ?></h2>
      <table class="doctable">
        <tbody>
        <?php foreach ($groups[$cat] as $d): $has = $d['file_active'] && $d['file_size']; $lg = $d['_fallback'] ? ' lang="' . e(I18n::hreflang($d['_lang'])) . '"' : ''; ?>
          <tr id="doc-<?= (int)$d['id'] ?>">
            <th scope="row" class="doctable__code"><?= e($d['code']) ?></th>
            <td class="doctable__title"><span<?= $lg ?>><?= e($d['title']) ?></span><?php if ($d['excerpt']): ?><span class="doctable__ex"<?= $lg ?>><?= e($d['excerpt']) ?></span><?php endif; ?></td>
            <td class="doctable__date"><?= e(fmt_date($d['pub_date'])) ?></td>
            <td class="doctable__get">
              <?php if ($has): ?>
                <a class="btn btn--dark" href="<?= e(PP::docUrl($d)) ?>"><?= e(t('download')) ?> <span class="doctable__fmt"><?= e($d['file_ext']) ?> <?= e(Media::human((int)$d['file_size'])) ?><?= !$d['file_is_translated'] && lang() !== I18n::$master ? ' · ' . e(t('doc_in_english')) : '' ?></span></a>
              <?php else: ?><span class="doctable__soon" aria-hidden="true">○</span><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endforeach; ?>
</section>
