<?php
/* Prossimi eventi nella home (c'è solo se gli eventi sono accesi). */
$items = class_exists('Events') ? Events::list(lang(), 'upcoming', max(1, (int)($m['limit'] ?? 3))) : [];
$more = trim((string)($x['more_label'] ?? ''));
?>
<section class="eventsblock <?= e(Blocks::toneClass($b['tone'])) ?>" id="<?= e($b['id_attr']) ?>" data-ink="<?= $b['tone'] === 'dark' ? 'light' : 'dark' ?>" aria-labelledby="<?= e($b['id_attr']) ?>-h">
  <div class="collection__top"><h2 id="<?= e($b['id_attr']) ?>-h" class="collection__head reveal"<?= $b['lg'] ?>><?= e($b['title'] ?: t('events_upcoming')) ?></h2></div>
  <?php if (!$items): ?><p class="empty"><?= e(t('events_empty')) ?></p><?php endif; ?>
  <div class="eventlist"><?php foreach ($items as $ev) echo PP::partial('event-card', ['e' => $ev]); ?></div>
  <p class="collection__more"><a href="<?= e(PP::link(Events::slug(lang()))) ?>"><?= e($more !== '' ? $more : t('events_all')) ?></a></p>
</section>
