<?php
/* Ultime news (e documenti). Aspetto (campo style; vuoto = quello del tema): list elenco con le date · mono riquadri con le foto in bianco e nero che si colorano e il titolo piccolo sotto · grid riquadri quadrati con la foto e il titolo sopra
   · cards schede con foto, data e titolo · feature la prima in grande, le altre in elenco accanto. */
// '' = l'aspetto scelto dal tema (theme.json "blocks": {"latest": {"style": …}}), se no l'elenco
$styles = ['list', 'grid', 'cards', 'feature', 'mono'];
$style = (string)($m['style'] ?? '');
if (!in_array($style, $styles, true)) $style = (string)((PP::themes()[PP::theme()]['blocks']['latest']['style'] ?? '') ?: 'list');
if (!in_array($style, $styles, true) || $style === 'list') $style = '';
$news = Content::entries('news', lang(), max(1, (int)($m['limit'] ?? ($style === '' ? 4 : 3))), 'pub_date DESC, id DESC');
$docs = !empty($m['docs']) ? array_slice(Content::docs(lang()), 0, 5) : [];
$lgOf = fn($n) => $n['_fallback'] ? ' lang="' . e(I18n::hreflang($n['_lang'])) . '"' : '';
$rows = function (array $list) use ($lgOf) { ob_start(); ?>
      <ul class="rows2">
        <?php foreach ($list as $n): ?>
          <li><a href="<?= e(entry_url($n)) ?>"<?= $lgOf($n) ?>><?= e($n['title']) ?></a><span><?= e(fmt_date($n['pub_date'])) ?></span></li>
        <?php endforeach; ?>
        <li class="rows2__more"><a href="<?= e(section_link('news')) ?>"><?= e(t('all_news')) ?></a></li>
      </ul>
<?php return ob_get_clean(); };
?>
<section class="latest<?= $style ? ' latest--' . e($style) : '' ?> <?= e(Blocks::toneClass($b['tone'])) ?>" id="<?= e($b['id_attr']) ?>" data-ink="<?= $b['tone'] === 'dark' ? 'light' : 'dark' ?>" aria-labelledby="<?= e($b['id_attr']) ?>-h">
  <h2 id="<?= e($b['id_attr']) ?>-h" class="latest__head reveal"<?= $b['lg'] ?>><?= e($b['title']) ?></h2>
  <?php if ($style === 'grid'): ?>
    <ul class="latest__grid">
      <?php foreach ($news as $n): ?>
        <li class="latest__tile reveal<?= $n['image'] ? '' : ' latest__tile--noimg' ?>">
          <?php if ($n['image']): ?><span class="latest__pic<?= e(focus_class($n)) ?>" aria-hidden="true"><?= img($n['image'], '', '(min-width: 900px) 33vw, 100vw') ?></span><?php endif; ?>
          <span class="latest__info"><time datetime="<?= e($n['pub_date']) ?>"><?= e(fmt_date($n['pub_date'])) ?></time>
            <a class="latest__title" href="<?= e(entry_url($n)) ?>"<?= $lgOf($n) ?>><?= e($n['title']) ?></a></span>
        </li>
      <?php endforeach; ?>
    </ul>
    <p class="latest__more"><a href="<?= e(section_link('news')) ?>"><?= e(t('all_news')) ?></a></p>
  <?php elseif ($style === 'mono'): ?>
    <ul class="latest__mono">
      <?php foreach ($news as $n): ?>
        <li class="latest__m reveal">
          <a class="latest__mpic<?= e(focus_class($n)) ?><?= $n['image'] ? '' : ' latest__mpic--noimg' ?>" href="<?= e(entry_url($n)) ?>" tabindex="-1" aria-hidden="true"><?php if ($n['image']) echo img($n['image'], '', '(min-width: 900px) 25vw, 50vw'); ?></a>
          <a class="latest__mtitle" href="<?= e(entry_url($n)) ?>"<?= $lgOf($n) ?>><?= e($n['title']) ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
    <p class="latest__more"><a href="<?= e(section_link('news')) ?>"><?= e(t('all_news')) ?></a></p>
  <?php elseif ($style === 'cards'): ?>
    <div class="cards latest__cards"><?php foreach ($news as $n) echo PP::partial('card', ['e' => $n, 'date' => true, 'sizes' => '(min-width: 900px) 33vw, 100vw']); ?></div>
    <p class="latest__more"><a href="<?= e(section_link('news')) ?>"><?= e(t('all_news')) ?></a></p>
  <?php elseif ($style === 'feature' && $news): $first = array_shift($news); ?>
    <div class="latest__feature">
      <article class="latest__lead reveal">
        <?php if ($first['image']): ?><a class="latest__pic<?= e(focus_class($first)) ?>" href="<?= e(entry_url($first)) ?>" tabindex="-1" aria-hidden="true"><?= img($first['image'], '', '(min-width: 900px) 50vw, 100vw') ?></a><?php endif; ?>
        <p class="latest__date"><time datetime="<?= e($first['pub_date']) ?>"><?= e(fmt_date($first['pub_date'])) ?></time></p>
        <h3 class="latest__title"<?= $lgOf($first) ?>><a href="<?= e(entry_url($first)) ?>"><?= e($first['title']) ?></a></h3>
        <?php if ($first['excerpt']): ?><p class="latest__ex"<?= $lgOf($first) ?>><?= e($first['excerpt']) ?></p><?php endif; ?>
      </article>
      <div class="latest__col reveal"><?= $rows($news) ?></div>
    </div>
  <?php endif; ?>
  <?php if ($style === '' || $docs): ?>
  <div class="latest__cols<?= ($docs && $style === '') ? '' : ' latest__cols--one' ?>">
    <?php if ($style === ''): ?>
    <div class="latest__col reveal">
      <h3 class="latest__sub"><a href="<?= e(section_link('news')) ?>"><?= e(t('news_title')) ?></a></h3>
      <?= $rows($news) ?>
    </div>
    <?php endif; ?>
    <?php if ($docs): ?>
    <div class="latest__col reveal">
      <h3 class="latest__sub"><a href="<?= e(section_link('results')) ?>"><?= e(t('results_title')) ?></a></h3>
      <ul class="rows2">
        <?php foreach ($docs as $d): $has = $d['file_active'] && $d['file_size']; ?>
          <li><?php if ($has): ?><a href="<?= e(PP::docUrl($d)) ?>"><?= e(trim($d['code'] . ' ' . $d['title'])) ?></a><span><?= e($d['file_ext'] . ' ' . Media::human((int)$d['file_size'])) ?></span>
              <?php else: ?><a href="<?= e(section_link('results')) ?>#doc-<?= (int)$d['id'] ?>"><?= e(trim($d['code'] . ' ' . $d['title'])) ?></a><span><?= e(fmt_date($d['pub_date'])) ?></span><?php endif; ?></li>
        <?php endforeach; ?>
        <li class="rows2__more"><a href="<?= e(section_link('results')) ?>"><?= e(t('all_results')) ?></a></li>
      </ul>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</section>
