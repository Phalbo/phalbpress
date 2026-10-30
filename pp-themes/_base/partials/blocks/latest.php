<?php
$news = Content::entries('news', lang(), max(1, (int)($m['limit'] ?? 4)), 'pub_date DESC, id DESC');
$docs = !empty($m['docs']) ? array_slice(Content::docs(lang()), 0, 5) : [];
?>
<section class="latest <?= e(Blocks::toneClass($b['tone'])) ?>" id="<?= e($b['id_attr']) ?>" data-ink="<?= $b['tone'] === 'dark' ? 'light' : 'dark' ?>" aria-labelledby="<?= e($b['id_attr']) ?>-h">
  <h2 id="<?= e($b['id_attr']) ?>-h" class="latest__head reveal"<?= $b['lg'] ?>><?= e($b['title']) ?></h2>
  <div class="latest__cols<?= $docs ? '' : ' latest__cols--one' ?>">
    <div class="latest__col reveal">
      <h3 class="latest__sub"><a href="<?= e(section_link('news')) ?>"><?= e(t('news_title')) ?></a></h3>
      <ul class="rows2">
        <?php foreach ($news as $n): ?>
          <li><a href="<?= e(entry_url($n)) ?>"<?= $n['_fallback'] ? ' lang="' . e(I18n::hreflang($n['_lang'])) . '"' : '' ?>><?= e($n['title']) ?></a><span><?= e(fmt_date($n['pub_date'])) ?></span></li>
        <?php endforeach; ?>
        <li class="rows2__more"><a href="<?= e(section_link('news')) ?>"><?= e(t('all_news')) ?></a></li>
      </ul>
    </div>
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
</section>
