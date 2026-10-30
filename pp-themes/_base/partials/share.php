<?php
/* Link di condivisione: semplici indirizzi, nessuno script dei social (niente tracciamento). $url, $title */
$u = rawurlencode(PP::abs($url)); $tt = rawurlencode($title);
?>
<div class="pp-share" role="group" aria-label="<?= e(t('share_label')) ?>">
  <span class="pp-share__label" aria-hidden="true"><?= e(t('share_label')) ?></span>
  <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $u ?>" target="_blank" rel="noopener">Facebook</a>
  <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= $u ?>" target="_blank" rel="noopener">LinkedIn</a>
  <a href="https://bsky.app/intent/compose?text=<?= $tt ?>%20<?= $u ?>" target="_blank" rel="noopener">Bluesky</a>
  <a href="https://wa.me/?text=<?= $tt ?>%20<?= $u ?>" target="_blank" rel="noopener">WhatsApp</a>
  <a href="mailto:?subject=<?= $tt ?>&amp;body=<?= $u ?>"><?= e(t('share_email')) ?></a>
  <button type="button" data-share-copy="<?= e(PP::abs($url)) ?>" data-done="<?= e(t('share_copied')) ?>" hidden><?= e(t('share_copy')) ?></button>
</div>
