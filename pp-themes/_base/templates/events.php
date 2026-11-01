<?php
$pageTitle = t('events_title'); $metaDesc = t('events_intro');
$up = Events::list(lang(), 'upcoming');
$past = Events::list(lang(), 'past');
$head = Layouts::get('events', 'head');
$list = Layouts::get('events', 'list');
?>
<?= PP::partial('band', ['title' => t('events_title'), 'lead' => t('events_intro'), 'tone' => 'field2', 'layout' => $head, 'image' => in_array($head, ['split', 'cover'], true) ? ($up[0]['image'] ?? ($past[0]['image'] ?? '')) : '',
    'crumb' => [[t('home'), link_to('')]]]) ?>
<section class="events chapter chapter--paper list--<?= e($list) ?>" data-ink="dark" aria-labelledby="ev-up-h" data-pp-part="list">
  <h2 id="ev-up-h" class="events__head reveal"><?= e(t('events_upcoming')) ?></h2>
  <?php if (!$up): ?><p class="empty"><?= e(t('events_empty')) ?></p><?php endif; ?>
  <div class="eventlist"><?php foreach ($up as $ev) echo PP::partial('event-card', ['e' => $ev]); ?></div>
</section>
<?php if ($past): ?>
<section class="events events--past chapter chapter--paper list--rows" data-ink="dark" aria-labelledby="ev-past-h">
  <h2 id="ev-past-h" class="events__head reveal"><?= e(t('events_past')) ?></h2>
  <div class="eventlist"><?php foreach ($past as $ev) echo PP::partial('event-card', ['e' => $ev]); ?></div>
</section>
<?php endif; ?>
