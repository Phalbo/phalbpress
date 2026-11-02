<?php
/* Home a blocchi (Gestione → Blocchi della home). Senza blocchi: apertura semplice con nome e claim, più le ultime news. */
$blocks = class_exists('Blocks') ? Blocks::home() : [];
$metaDesc = trim(t('ai_summary')) !== 'ai_summary' ? t('ai_summary') : t('claim');
foreach ($blocks as $bb) if ($bb['template'] === 'hero' && $bb['excerpt']) { $metaDesc = $bb['excerpt']; break; }
$bodyClass = $blocks && $blocks[0]['template'] === 'hero' ? 'has-hero' : '';
if (!$blocks): ?>
  <?= PP::partial('band', ['title' => class_exists('Identity') ? Identity::name() : PP::$config['site_name'], 'lead' => t('claim'), 'tone' => 'accent']) ?>
  <?= class_exists('Blocks') ? PP::partial('blocks/latest', ['b' => ['id' => 0, 'title' => t('news_title'), 'tone' => 'paper', 'lg' => '', 'id_attr' => 'latest', 'excerpt' => '', 'body' => ''], 'i' => 1, 'x' => [], 'm' => ['limit' => 6, 'docs' => 1]]) : '' ?>
<?php else:
  // la home ha sempre un titolo principale: lo dà l'apertura; se la prima fascia è un'altra, il nome del sito (solo per chi legge con lo screen reader)
  if ($blocks[0]['template'] !== 'hero'): ?><h1 class="sr-only"><?= e(class_exists('Identity') ? Identity::name() : (PP::$config['site_name'] ?? '')) ?></h1><?php endif;
  foreach ($blocks as $i => $b) echo Blocks::render($b, $i);
endif;
