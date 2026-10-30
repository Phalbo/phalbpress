<?php
/* $id, $service (youtube|vimeo). Nessuna richiesta al servizio esterno finché non c'è il consenso e non si preme play. */
$service = $service ?? 'youtube';
$src = $service === 'vimeo' ? 'https://player.vimeo.com/video/' . $id . '?dnt=1' : 'https://www.youtube-nocookie.com/embed/' . $id . '?rel=0';
$ext = $service === 'vimeo' ? 'https://vimeo.com/' . $id : 'https://www.youtube.com/watch?v=' . $id;
?>
<figure class="pp-embed" data-pp-embed data-service="<?= e($service) ?>" data-src="<?= e($src) ?>" data-external="<?= e($ext) ?>" data-title="<?= e(t('play_video')) ?>">
  <div class="pp-embed__box">
    <a class="pp-embed__play" data-pp-play href="<?= e($ext) ?>" target="_blank" rel="noopener"><span><?= e(t('play_video')) ?></span></a>
  </div>
</figure>
