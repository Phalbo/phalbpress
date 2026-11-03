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
$ctx = ['title' => $pageTitle ?? null, 'desc' => $metaDesc ?? '', 'image' => $ogImage ?? '', 'image_alt' => $ogAlt ?? '', 'type' => $seoType ?? '', 'date' => $seoDate ?? '', 'noindex' => !empty($noindex)];
$head = Hooks::html('head', $ctx);
$legal = Hooks::filter('footer_links', []);
$favicon = $hasId ? Identity::favicon() : '';
$searchUrl = class_exists('Search') ? Search::url() : '';
$themeJs = PP::themeFile('assets/theme.js');
$siteCss = is_file(PP_SITE . '/theme/assets/site.css');
$claim = trim(t('claim'));
$themeBody = (string)(PP::themes()[$theme]['body_class'] ?? '');   // es. "photo-hero": il tema mostra la foto dell'apertura
$themeMotion = PP::themes()[$theme]['motion'] ?? [];                // movimento del tema (base.js), false = niente
$socialPlace = (string)($cfg['social_place'] ?? 'footer');                 // icone dei social: testata, footer o entrambi
$headerModel = class_exists('Layouts') ? Layouts::forType('site', 'header') : 'bar';   // modello di testata: del sito o del tema
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
<body class="theme-<?= e($theme) ?> hm-<?= e($headerModel) ?><?= $themeBody ? ' ' . e($themeBody) : '' ?> route-<?= e($r['name']) ?><?= !empty($bodyClass) ? ' ' . e($bodyClass) : '' ?>" data-motion="<?= e(json_encode($themeMotion, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>" data-ui="<?= e(json_encode(['close' => t('lb_close'), 'prev' => t('lb_prev'), 'next' => t('lb_next'), 'open' => t('lb_open'), 'pause' => t('slides_pause'), 'play' => t('slides_play'), 'slide' => t('slides_go')], JSON_UNESCAPED_UNICODE)) ?>">
<a class="skip" href="#content"><?= e(t('skip')) ?></a>
<?php if (!empty($cfg['toolbar'])): $tel = fn($n) => preg_replace('~[^0-9+]~', '', (string)$n); ?>
<div class="toolbar" data-pp-part="toolbar">
  <div class="toolbar__in">
    <ul class="toolbar__contacts">
      <?php foreach (array_filter([$cfg['phone'] ?? '', $cfg['phone2'] ?? '']) as $ph): ?>
        <li><a href="tel:<?= e($tel($ph)) ?>"><svg viewBox="0 0 24 24" width="15" height="15" aria-hidden="true" focusable="false"><path d="M6.6 3.5 9 6.8l-1.6 2.4a12 12 0 0 0 7.4 7.4L17.2 15l3.3 2.4-1 3a2.5 2.5 0 0 1-2.6 1.6A16.5 16.5 0 0 1 2 6.4 2.5 2.5 0 0 1 3.6 3.8z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg><span class="sr-only"><?= e(t('contact_phone')) ?>: </span><?= e($ph) ?></a></li>
      <?php endforeach; ?>
      <?php if (!empty($cfg['contact_email'])): ?><li><a href="mailto:<?= e($cfg['contact_email']) ?>"><svg viewBox="0 0 24 24" width="15" height="15" aria-hidden="true" focusable="false"><rect x="3" y="5.5" width="18" height="13" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="m3.5 6.5 8.5 6.5 8.5-6.5" fill="none" stroke="currentColor" stroke-width="1.6"/></svg><span class="sr-only"><?= e(t('contact_email')) ?>: </span><?= e($cfg['contact_email']) ?></a></li><?php endif; ?>
    </ul>
    <?php if (in_array($socialPlace, ['toolbar', 'toolbar_footer'], true)) echo PP::partial('social', ['where' => 'bar']); ?>
  </div>
</div>
<?php endif; ?>

<header class="site-head head-model--<?= e($headerModel) ?>" data-head data-pp-part="header">
  <div class="site-head__in">
    <?php $duo = $hasId && (Identity::get()['logo_dark'] || Identity::get()['logo_dark_svg']); // due loghi: base.js mostra quello chiaro se la testata è scura ?>
    <a class="brand<?= $duo ? ' brand--duo' : '' ?>" href="<?= e(link_to('')) ?>"<?= $r['name'] === 'home' ? ' aria-current="page"' : '' ?>>
      <?= $hasId ? Identity::logo(false, 'brand__logo brand__logo--day') : '' ?><?= $duo ? Identity::logo(true, 'brand__logo brand__logo--night') : '' ?>
      <span class="brand__text<?= $hasId && !empty(Identity::get()['logo_has_name']) && Identity::logo(false, 'x') !== '' ? ' sr-only' : '' ?>"><span class="brand__name"><?= e($siteName) ?></span><?php if ($claim !== ''): ?><span class="brand__claim"><?= e($claim) ?></span><?php endif; ?></span>
    </a>
    <nav class="nav" id="site-nav" aria-label="<?= e(t('nav_main')) ?>" data-nav>
      <ul class="nav__list">
        <?php foreach ($menu as $i => $m): ?>
          <li class="nav__item<?= $m['children'] ? ' nav__item--sub' : '' ?><?= $m['current'] ? ' is-current' : '' ?>">
            <a class="nav__link" href="<?= e($m['href']) ?>"<?= $m['current'] ? ' aria-current="page"' : '' ?><?= $m['external'] ? ' target="_blank" rel="noopener"' : '' ?>><?= e($m['label']) ?></a>
            <?php if ($m['children']): ?>
              <button class="nav__subbtn" type="button" aria-expanded="false" aria-controls="sub-<?= $i ?>" data-sub><span class="sr-only"><?= e(str_replace('{item}', $m['label'], t('submenu'))) ?></span></button>
              <ul class="nav__sub" id="sub-<?= $i ?>">
                <?php foreach ($m['children'] as $j => $c): ?><li<?= !empty($c['children']) ? ' class="nav__item nav__item--sub nav__item--deep' . ($c['current'] ? ' is-current' : '') . '"' : '' ?>><a href="<?= e($c['href']) ?>"<?= $c['current'] ? ' aria-current="page"' : '' ?><?= $c['external'] ? ' target="_blank" rel="noopener"' : '' ?>><?= e($c['label']) ?></a>
                  <?php if (!empty($c['children'])): ?>
                    <button class="nav__subbtn" type="button" aria-expanded="false" aria-controls="sub-<?= $i ?>-<?= $j ?>" data-sub><span class="sr-only"><?= e(str_replace('{item}', $c['label'], t('submenu'))) ?></span></button>
                    <ul class="nav__sub nav__sub--deep" id="sub-<?= $i ?>-<?= $j ?>">
                      <?php foreach ($c['children'] as $g): ?><li><a href="<?= e($g['href']) ?>"<?= $g['current'] ? ' aria-current="page"' : '' ?><?= $g['external'] ? ' target="_blank" rel="noopener"' : '' ?>><?= e($g['label']) ?></a></li><?php endforeach; ?>
                    </ul>
                  <?php endif; ?></li><?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <div class="site-head__tools">
      <?php if (in_array($socialPlace, ['header', 'both'], true)) echo PP::partial('social', ['where' => 'head']); ?>
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
  <?php $offices = class_exists('SiteSettings') ? SiteSettings::offices() : []; $tel = fn($n) => preg_replace('~[^0-9+]~', '', (string)$n);
    $hasLogoDark = $hasId && (Identity::get()['logo_dark'] || Identity::get()['logo_dark_svg']);
    $contacts = array_filter(['email' => $cfg['contact_email'] ?? '', 'phone' => $cfg['phone'] ?? '', 'phone2' => $cfg['phone2'] ?? '', 'pec' => $cfg['pec'] ?? '', 'vat' => $cfg['vat'] ?? '']);
    $social = array_filter($cfg['social'] ?? []); $socialFoot = $social && in_array($socialPlace, ['footer', 'both', 'toolbar_footer'], true); ?>
  <div class="foot__cols">
    <div class="foot__col foot__col--brand">
      <?php if ($hasLogoDark): // tutti e due: il tema sceglie quale mostrare a seconda del colore del footer (di base scuro) ?><p class="foot__logo"><?= Identity::logo(true, 'foot__logo-img foot__logo-img--night') ?><?= Identity::logo(false, 'foot__logo-img foot__logo-img--day') ?><span class="sr-only"><?= e($siteName) ?></span></p>
      <?php else: ?><p class="foot__name"><?= e($siteName) ?></p><?php endif; ?>
      <?php if ($claim !== ''): ?><p class="foot__claim"><?= e($claim) ?></p><?php endif; ?>
      <?php if (!empty($cfg['footer_badge']) && is_file(PP_ROOT . '/' . $cfg['footer_badge'])): ?><p class="foot__badge"><img src="<?= e(PP::upload($cfg['footer_badge'])) ?>" alt="<?= e(t('footer_badge_alt')) ?>" loading="lazy"></p><?php endif; ?>
      <?php if (($note = trim(th('footer_note'))) !== '' && $note !== 'footer_note'): ?><p class="foot__note"><?= $note ?></p><?php endif; ?>
      <?= PP::partial('footer-extra') ?>
    </div>
    <?php if ($contacts || $socialFoot): ?>
    <div class="foot__col foot__col--contact">
      <p class="foot__head"><?= e(($cfg['company'] ?? '') !== '' ? $cfg['company'] : t('footer_contact')) ?></p>
      <?php if ($contacts): ?><ul class="foot__contacts">
        <?php if (!empty($contacts['email'])): ?><li><span class="foot__lbl"><?= e(t('contact_email')) ?></span> <a href="mailto:<?= e($contacts['email']) ?>"><?= e($contacts['email']) ?></a></li><?php endif; ?>
        <?php foreach (['phone', 'phone2'] as $k): if (!empty($contacts[$k])): ?><li><span class="foot__lbl"><?= e(t('contact_phone')) ?></span> <a href="tel:<?= e($tel($contacts[$k])) ?>"><?= e($contacts[$k]) ?></a></li><?php endif; endforeach; ?>
        <?php if (!empty($contacts['pec'])): ?><li><span class="foot__lbl"><?= e(t('contact_pec')) ?></span> <a href="mailto:<?= e($contacts['pec']) ?>"><?= e($contacts['pec']) ?></a></li><?php endif; ?>
        <?php if (!empty($contacts['vat'])): ?><li><span class="foot__lbl"><?= e(t('contact_vat')) ?></span> <?= e($contacts['vat']) ?></li><?php endif; ?>
        <?= PP::partial('footer-contact') ?>
      </ul><?php endif; ?>
      <?php if ($socialFoot) echo PP::partial('social', ['where' => 'foot']); ?>
    </div>
    <?php endif; ?>
    <?php if ($footMenu): ?>
    <div class="foot__col">
      <p class="foot__head"><?= e(t('footer_pages')) ?></p>
      <ul><?php foreach ($footMenu as $m): ?><li><a href="<?= e($m['href']) ?>"<?= $m['external'] ? ' target="_blank" rel="noopener"' : '' ?>><?= e($m['label']) ?></a></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>
    <?php if ($offices): ?>
    <div class="foot__col foot__col--offices">
      <p class="foot__head"><?= e(t('footer_offices')) ?></p>
      <?php foreach ($offices as $o): ?><address class="foot__office"><strong><?= e($o['label']) ?></strong><?php foreach ($o['lines'] as $ln): ?><br><?= e($ln) ?><?php endforeach; ?></address><?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  <div class="foot__word" aria-hidden="true">
    <svg viewBox="0 0 1000 150" preserveAspectRatio="xMinYMax meet" focusable="false"><text x="0" y="128" textLength="1000" lengthAdjust="spacingAndGlyphs"><?= e($siteName) ?></text></svg>
  </div>
  <nav class="foot__line" aria-label="<?= e(t('legal_privacy_title')) ?>">
    <span>© <span data-year><?= date('Y') ?></span> <?= ($fl = trim(th('footer_legal'))) !== '' && $fl !== 'footer_legal' ? $fl : e($siteName) ?></span>
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
