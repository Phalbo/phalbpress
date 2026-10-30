<?php
/**
 * Struttura comune a tutti i temi: testata con menu esteso e lingue a tendina, contenuto, footer.
 * Ogni tema cambia l'aspetto con assets/theme.css (e, se serve, sostituisce singoli template o partial).
 */
$L = lang();
$cfg = PP::$config;
$r = PP::$route;
$theme = PP::theme();
$hasId = class_exists('Identity');
$siteName = $hasId ? Identity::name() : $cfg['site_name'];
$menu = class_exists('Menus') ? Menus::items('main') : [];
$footMenu = class_exists('Menus') ? (Menus::items('footer') ?: $menu) : [];
$ctx = ['title' => $pageTitle ?? null, 'desc' => $metaDesc ?? '', 'image' => $ogImage ?? '', 'type' => $seoType ?? '', 'date' => $seoDate ?? '', 'noindex' => !empty($noindex)];
$head = Hooks::html('head', $ctx);
$legal = Hooks::filter('footer_links', []);
$favicon = $hasId ? Identity::favicon() : '';
$searchUrl = class_exists('Search') ? Search::url() : '';
$themeJs = PP::themeFile('assets/theme.js');
$siteCss = is_file(PP_SITE . '/theme/assets/site.css');
$claim = trim(t('claim'));
$themeBody = (string)(PP::themes()[$theme]['body_class'] ?? '');   // es. "photo-hero": il tema mostra la foto dell'apertura
?>
<!doctype html>
<html lang="<?= e(I18n::hreflang($L)) ?>" class="no-js">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?= $head ?: '<title>' . e(($pageTitle ?? '') ? $pageTitle . ' · ' . $siteName : $siteName) . '</title>' ?>
<?php if (!empty($noindex) && !$head): ?><meta name="robots" content="noindex"><?php endif; ?>
<meta name="theme-color" content="<?= e($hasId ? Identity::get()['colors']['accent'] : '#000') ?>">
<?php if ($favicon): ?><link rel="icon" href="<?= e($favicon) ?>"><?php endif; ?>
<link rel="stylesheet" href="<?= e(PP::url('pp-themes/_base/assets/base.css')) ?>?v=<?= e(substr((string)filemtime(PP_ROOT . '/pp-themes/_base/assets/base.css'), -6)) ?>">
<link rel="stylesheet" href="<?= e(asset('theme.css')) ?>">
<?php if ($hasId): ?><link rel="stylesheet" href="<?= e(Identity::cssUrl()) ?>"><?php endif; ?>
<link rel="stylesheet" href="<?= e(PP::url('pp-assets/pp.css')) ?>?v=<?= PP::VERSION ?>">
<?php if ($siteCss): ?><link rel="stylesheet" href="<?= e(PP::url('site/theme/assets/site.css')) ?>?v=<?= e(substr((string)filemtime(PP_SITE . '/theme/assets/site.css'), -6)) ?>"><?php endif; ?>
<script src="<?= e(PP::url('pp-themes/_base/assets/base.js')) ?>?v=<?= e(substr((string)filemtime(PP_ROOT . '/pp-themes/_base/assets/base.js'), -6)) ?>" defer></script>
<?php if ($themeJs): ?><script src="<?= e(asset('theme.js')) ?>" defer></script><?php endif; ?>
</head>
<body class="theme-<?= e($theme) ?><?= $themeBody ? ' ' . e($themeBody) : '' ?> route-<?= e($r['name']) ?><?= !empty($bodyClass) ? ' ' . e($bodyClass) : '' ?>">
<a class="skip" href="#content"><?= e(t('skip')) ?></a>

<header class="site-head" data-head>
  <div class="site-head__in">
    <a class="brand" href="<?= e(link_to('')) ?>"<?= $r['name'] === 'home' ? ' aria-current="page"' : '' ?>>
      <?= $hasId ? Identity::logo(false, 'brand__logo') : '' ?>
      <span class="brand__text"><span class="brand__name"><?= e($siteName) ?></span><?php if ($claim !== ''): ?><span class="brand__claim"><?= e($claim) ?></span><?php endif; ?></span>
    </a>
    <nav class="nav" id="site-nav" aria-label="<?= e(t('nav_main')) ?>" data-nav>
      <ul class="nav__list">
        <?php foreach ($menu as $i => $m): ?>
          <li class="nav__item<?= $m['children'] ? ' nav__item--sub' : '' ?><?= $m['current'] ? ' is-current' : '' ?>">
            <a class="nav__link" href="<?= e($m['href']) ?>"<?= $m['current'] ? ' aria-current="page"' : '' ?><?= $m['external'] ? ' target="_blank" rel="noopener"' : '' ?>><?= e($m['label']) ?></a>
            <?php if ($m['children']): ?>
              <button class="nav__subbtn" type="button" aria-expanded="false" aria-controls="sub-<?= $i ?>" data-sub><span class="sr-only"><?= e(str_replace('{item}', $m['label'], t('submenu'))) ?></span></button>
              <ul class="nav__sub" id="sub-<?= $i ?>">
                <?php foreach ($m['children'] as $c): ?><li><a href="<?= e($c['href']) ?>"<?= $c['current'] ? ' aria-current="page"' : '' ?><?= $c['external'] ? ' target="_blank" rel="noopener"' : '' ?>><?= e($c['label']) ?></a></li><?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <div class="site-head__tools">
      <?php if ($searchUrl): ?>
        <a class="tool tool--search" href="<?= e($searchUrl) ?>"<?= $r['name'] === 'search' ? ' aria-current="page"' : '' ?>>
          <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><circle cx="10.5" cy="10.5" r="6.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="M15.5 15.5 21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
          <span class="sr-only"><?= e(t('search_title')) ?></span></a>
      <?php endif; ?>
      <?php if (count(I18n::codes()) > 1): ?>
      <details class="langs" data-langs>
        <summary class="langs__btn"><span class="langs__code" aria-hidden="true"><?= e(I18n::label($L)) ?></span><span class="sr-only"><?= e(t('languages')) ?>: </span><span class="langs__name"><?= e(I18n::name($L)) ?></span>
          <svg class="langs__chev" viewBox="0 0 12 8" width="12" height="8" aria-hidden="true" focusable="false"><path d="M1 1.5 6 6.5 11 1.5" fill="none" stroke="currentColor" stroke-width="1.8"/></svg></summary>
        <ul class="langs__list">
          <?php foreach (I18n::codes() as $l): ?>
            <li><a href="<?= e(PP::alternate($l)) ?>" hreflang="<?= e(I18n::hreflang($l)) ?>" lang="<?= e(I18n::hreflang($l)) ?>"<?= $l === $L ? ' aria-current="true"' : '' ?>><span class="langs__item-code" aria-hidden="true"><?= e(I18n::label($l)) ?></span> <?= e(I18n::name($l)) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </details>
      <?php endif; ?>
      <?php if ($menu): ?><button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" data-nav-toggle><span class="nav-toggle__open"><?= e(t('menu')) ?></span><span class="nav-toggle__close"><?= e(t('close')) ?></span></button><?php endif; ?>
    </div>
  </div>
</header>

<main id="content" class="site-main" tabindex="-1">
<?= $content ?>
</main>

<footer class="site-foot">
  <div class="foot__cols">
    <div class="foot__col foot__col--brand">
      <p class="foot__name"><?= e($siteName) ?></p>
      <?php if ($claim !== ''): ?><p class="foot__claim"><?= e($claim) ?></p><?php endif; ?>
      <?= PP::partial('footer-extra') ?>
    </div>
    <?php if ($footMenu): ?>
    <div class="foot__col">
      <p class="foot__head"><?= e(t('footer_pages')) ?></p>
      <ul><?php foreach ($footMenu as $m): ?><li><a href="<?= e($m['href']) ?>"<?= $m['external'] ? ' target="_blank" rel="noopener"' : '' ?>><?= e($m['label']) ?></a></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>
    <?php $social = array_filter($cfg['social'] ?? []); if ($social): ?>
    <div class="foot__col">
      <p class="foot__head"><?= e(t('footer_follow')) ?></p>
      <ul><?php foreach ($social as $n => $u): ?><li><a href="<?= e($u) ?>" rel="noopener" target="_blank"><?= e($n) ?></a></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>
    <?php if (!empty($cfg['contact_email'])): ?>
    <div class="foot__col">
      <p class="foot__head"><?= e(t('footer_contact')) ?></p>
      <ul><li><a href="mailto:<?= e($cfg['contact_email']) ?>"><?= e($cfg['contact_email']) ?></a></li><?= PP::partial('footer-contact') ?></ul>
    </div>
    <?php endif; ?>
  </div>
  <div class="foot__word" aria-hidden="true">
    <svg viewBox="0 0 1000 150" preserveAspectRatio="xMinYMax meet" focusable="false"><text x="0" y="128" textLength="1000" lengthAdjust="spacingAndGlyphs"><?= e($siteName) ?></text></svg>
  </div>
  <nav class="foot__line" aria-label="<?= e(t('legal_privacy_title')) ?>">
    <span>© <?= date('Y') ?> <?= e($siteName) ?></span>
    <?php foreach ($legal as $lk): ?>
      <?php if (!empty($lk['button'])): ?><button type="button" class="foot__btn" data-pp-consent-open><?= e($lk['label']) ?></button>
      <?php else: ?><a href="<?= e($lk['href']) ?>"><?= e($lk['label']) ?></a><?php endif; ?>
    <?php endforeach; ?>
    <span><?= e(t('no_cookies')) ?></span>
  </nav>
</footer>
<?= Hooks::html('body_end') ?>
</body>
</html>
