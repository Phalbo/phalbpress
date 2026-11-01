<?php /* Un evento negli elenchi: data grande a sinistra (giorno e mese), titolo, luogo. $e */
$href = PP::entryUrl($e);
$ts = strtotime(Events::start($e)) ?: time();
$lg = $e['_fallback'] ? ' lang="' . e(I18n::hreflang($e['_lang'])) . '"' : '';
$month = class_exists('IntlDateFormatter') ? (new IntlDateFormatter(I18n::$langs[lang()]['locale'] ?? lang(), IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, 'MMM'))->format($ts) : date('M', $ts);
?>
<article class="event reveal">
  <p class="event__date" aria-hidden="true"><span class="event__day"><?= date('j', $ts) ?></span><span class="event__month"><?= e(rtrim((string)$month, '.')) ?></span><span class="event__year"><?= date('Y', $ts) ?></span></p>
  <?php if ($e['image']): ?><a class="event__img<?= e(focus_class($e)) ?>" href="<?= e($href) ?>" tabindex="-1" aria-hidden="true"><?= img($e['image'], '', '(min-width: 900px) 25vw, 100vw') ?></a><?php endif; ?>
  <div class="event__body">
    <p class="event__when"><?= e(Events::when($e)) ?></p>
    <h3 class="event__title"<?= $lg ?>><a href="<?= e($href) ?>"><?= e($e['title']) ?></a></h3>
    <?php if (!empty($e['x']['place'])): ?><p class="event__place"<?= $lg ?>><?= e($e['x']['place']) ?></p><?php endif; ?>
  </div>
</article>
