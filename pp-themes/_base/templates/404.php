<?php $pageTitle = t('not_found_title'); $noindex = true; ?>
<?= PP::partial('band', ['title' => t('not_found_title'), 'lead' => t('not_found_text'), 'tone' => 'accent']) ?>
<section class="article chapter chapter--paper" data-ink="dark" aria-label="404">
  <p><a class="btn btn--dark" href="<?= e(link_to('')) ?>"><?= e(t('home')) ?></a><?php if (class_exists('Search')): ?> <a class="btn" href="<?= e(Search::url()) ?>"><?= e(t('search_title')) ?></a><?php endif; ?></p>
</section>
