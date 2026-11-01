<?php
$item = $page;
$pageTitle = $page['title']; $metaDesc = $page['excerpt'];
$lg = $page['_fallback'] ? ' lang="' . e(I18n::hreflang($page['_lang'])) . '"' : '';
$partners = isset(PP::$config['types']['partner']) ? Content::entries('partner', lang()) : [];
$mail = PP::$config['contact_email'] ?? '';
$body = PP::body($page['body']);
// se il testo non contiene già un modulo, lo aggiungiamo in fondo
if (class_exists('Forms') && !str_contains($page['body'], '[form:')) $body .= Forms::render('contact');
?>
<?= PP::partial('band', ['title' => $page['title'], 'lead' => $page['excerpt'], 'tone' => 'accent', 'lg' => $lg, 'layout' => Layouts::get('contact', 'head', $page), 'image' => $page['image'] ?? '', 'imageAlt' => $page['image_alt'] ?? '', 'crumb' => [[t('home'), link_to('')]]]) ?>
<section class="contact chapter chapter--paper" data-ink="dark" aria-label="<?= e($page['title']) ?>">
  <?= PP::partial('fallback', ['item' => $page]) ?>
  <div class="contact__main">
    <?php if ($mail): ?><p class="contact__mail"><a href="mailto:<?= e($mail) ?>"><?= e($mail) ?></a></p><?php endif; ?>
    <div class="prose"<?= $lg ?>><?= $body ?></div>
  </div>
  <?php if ($partners): ?>
  <ul class="rows2 contact__list">
    <?php foreach ($partners as $p): $m = $p['meta_arr']; ?>
      <li><?php if (!empty($m['url'])): ?><a href="<?= e($m['url']) ?>" rel="noopener" target="_blank"><?= e($p['title']) ?></a><?php else: ?><span class="rows2__name"><?= e($p['title']) ?></span><?php endif; ?><span><?= e($m['country'] ?? '') ?></span></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</section>
