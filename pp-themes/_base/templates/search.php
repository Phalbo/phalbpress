<?php
$pageTitle = t('search_title'); $noindex = true;
?>
<?= PP::partial('band', ['title' => t('search_title'), 'tone' => 'paper']) ?>
<section class="chapter chapter--paper pp-search" data-pp-search data-index="<?= e(PP::url('search/' . lang() . '.json')) ?>" data-count="<?= e(t('search_count')) ?>" data-none="<?= e(t('search_none')) ?>">
  <form role="search" action="" method="get">
    <label class="sr-only" for="pp-q"><?= e(t('search_label')) ?></label>
    <input id="pp-q" type="search" name="q" autocomplete="off" placeholder="<?= e(t('search_label')) ?>">
    <button type="submit" class="btn btn--dark"><?= e(t('search_button')) ?></button>
  </form>
  <p data-status role="status" aria-live="polite"></p>
  <ol class="pp-hits" data-results></ol>
  <noscript><p><?= e(t('search_nojs')) ?></p></noscript>
</section>
<script src="<?= e(PP::url('pp-assets/search.js')) ?>?v=<?= PP::VERSION ?>" defer></script>
