<?php
$src = (string)($m['source'] ?? 'news');
$limit = (int)($m['limit'] ?? 0);
// storie nello stesso ordine della loro pagina (ordine a mano, poi data); news per data
$order = match ($src) { 'news' => 'pub_date DESC, id DESC', 'story' => 'menu_order ASC, pub_date DESC, id DESC', default => 'menu_order ASC, id ASC' };
$items = isset(PP::$config['types'][$src]) ? Content::entries($src, lang(), $limit, $order) : [];
// contenuti scelti a mano, nell'ordine dei campi: prendono il posto degli ultimi
$picked = [];
for ($n = 1; $n <= 8; $n++) if (!empty($m['pick' . $n]) && ($pe = Content::byId((int)$m['pick' . $n], lang()))) $picked[(int)$pe['id']] = $pe;
if ($picked) { $items = array_values($picked); $src = count(array_unique(array_column($items, 'type'))) === 1 ? $items[0]['type'] : $src; }
$cols = in_array($m['cols'] ?? '', ['3', '4'], true) ? ' cards--' . $m['cols'] : '';
$style = ($m['style'] ?? '') === 'cards' ? 'cards' : 'list';
$more = trim((string)($x['more_label'] ?? ''));
$moreUrl = ['news' => 'news', 'story' => 'stories', 'partner' => 'partners'][$src] ?? '';
?>
<section class="collection collection--<?= e($src) ?> collection--<?= $style ?> <?= e(Blocks::toneClass($b['tone'])) ?>" id="<?= e($b['id_attr']) ?>" data-ink="<?= $b['tone'] === 'dark' ? 'light' : 'dark' ?>" aria-labelledby="<?= e($b['id_attr']) ?>-h">
  <div class="collection__top">
    <h2 id="<?= e($b['id_attr']) ?>-h" class="collection__head reveal"<?= $b['lg'] ?>><?= e($b['title']) ?></h2>
    <?php if ($b['excerpt']): ?><p class="collection__intro reveal"<?= $b['lg'] ?>><?= e($b['excerpt']) ?></p><?php endif; ?>
  </div>
  <?php if ($style === 'cards'): ?>
    <div class="cards<?= e($cols) ?>"><?php foreach ($items as $e) echo PP::partial('card', ['e' => $e, 'date' => in_array($src, ['news'], true)]); ?></div>
  <?php else: ?>
  <ul class="collection__list">
    <?php foreach ($items as $e): $em = $e['meta_arr']; $href = PP::entryUrl($e); $lg = $e['_fallback'] ? ' lang="' . e(I18n::hreflang($e['_lang'])) . '"' : '';
      $who = $em['partners'] ?? ($em['country'] ?? ($src === 'news' ? fmt_date($e['pub_date']) : '')); ?>
      <li class="collection__item reveal">
        <span class="collection__name"<?= $lg ?>><?= $href ? '<a href="' . e($href) . '">' . e($e['title']) . '</a>' : e($e['title']) ?></span>
        <?php if ($e['excerpt']): ?><span class="collection__ctx"<?= $lg ?>><?= e($e['excerpt']) ?></span><?php endif; ?>
        <?php if ($who): ?><span class="collection__who"><?= e($who) ?></span><?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
  <?php if ($more !== '' && $moreUrl): ?><p class="collection__more"><a href="<?= e(section_link($moreUrl)) ?>"><?= e($more) ?></a></p><?php endif; ?>
</section>
