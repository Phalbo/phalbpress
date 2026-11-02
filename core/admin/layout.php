<?php $u = Auth::user(); $types = PP::$config['types']; $cur = $_SERVER['REQUEST_URI'] ?? ''; ?>
<!doctype html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title ?? 'Gestione') ?> · <?= e(PP::$config['site_name']) ?></title>
<link rel="stylesheet" href="<?= e(PP::url('pp-admin/admin.css')) ?>?v=<?= PP::VERSION ?>">
</head>
<body data-csrf="<?= e(Auth::csrf()) ?>" data-base="<?= e(PP::url(PP::adminSlug())) ?>" data-root="<?= e(PP::base()) ?>">
<div class="shell">
  <aside class="side">
    <a class="brand" href="<?= e(Admin::u()) ?>">
      <span class="brand__mark" aria-hidden="true"><?= class_exists('Identity') && ($lg = Identity::logo(false, 'brand__logo')) ? $lg : 'P' ?></span>
      <span><strong><?= e(PP::$config['site_name']) ?></strong><small>PhalbPress</small></span>
    </a>
    <nav class="nav" aria-label="Gestione">
      <a href="<?= e(Admin::u()) ?>" <?= preg_match('~/' . preg_quote(PP::adminSlug(), '~') . '/?$~', (string)parse_url($cur, PHP_URL_PATH)) ? 'aria-current="page"' : '' ?>>Panoramica</a>
      <p class="nav__group">Contenuti</p>
      <?php foreach ($types as $k => $t): ?>
        <a href="<?= e(Admin::u('entries/' . $k)) ?>" <?= str_contains($cur, '/entries/' . $k) || (isset($type) && $type === $k && str_contains($cur, '/entry/')) ? 'aria-current="page"' : '' ?>><?= e($t['label']) ?></a>
      <?php endforeach; ?>
 <?php if (Auth::can('translate')): ?><a href="<?= e(Admin::u('docs')) ?>" <?= str_contains($cur, '/doc') ? 'aria-current="page"' : '' ?>>Documenti</a><?php endif; ?>
      <?php if (Auth::can('content')): ?><a href="<?= e(Admin::u('media')) ?>" <?= str_contains($cur, '/media') ? 'aria-current="page"' : '' ?>>Immagini e file</a><?php endif; ?>
      <?php $mods = Admin::pages(); $grp = fn($g) => array_filter($mods, fn($m) => ($m['group'] ?? '') === $g && Auth::can($m['perm'] ?? (!empty($m['admin_only']) ? 'admin' : 'site'))); ?>
      <?php if ($site = $grp('Sito')): ?><p class="nav__group">Sito</p>
        <?php foreach ($site as $slug => $m): ?><a href="<?= e(Admin::u($slug)) ?>" <?= preg_match('~/' . preg_quote($slug, '~') . '(/|$)~', parse_url($cur, PHP_URL_PATH)) ? 'aria-current="page"' : '' ?>><?= e($m['label']) ?></a><?php endforeach; ?>
      <?php endif; ?>
      <?php foreach ($grp('Contenuti') as $slug => $m): ?><a href="<?= e(Admin::u($slug)) ?>"><?= e($m['label']) ?></a><?php endforeach; ?>
      <?php if (Auth::can('translate')): ?><p class="nav__group">Lingue</p>
      <a href="<?= e(Admin::u('strings')) ?>" <?= str_contains($cur, '/strings') ? 'aria-current="page"' : '' ?>>Testi del sito</a>
      <a href="<?= e(Admin::u('translate')) ?>" <?= str_contains($cur, '/translate') ? 'aria-current="page"' : '' ?>>Traduzioni</a><?php endif; ?>
      <p class="nav__group">Account</p>
      <a href="<?= e(Admin::u('users')) ?>" <?= str_contains($cur, '/users') ? 'aria-current="page"' : '' ?>>Utenti</a>
      <?php foreach ($grp('Account') as $slug => $m): ?><a href="<?= e(Admin::u($slug)) ?>" <?= str_contains($cur, '/' . $slug) ? 'aria-current="page"' : '' ?>><?= e($m['label']) ?></a><?php endforeach; ?>
      <a href="<?= e(PP::link('', I18n::$master)) ?>" target="_blank" rel="noopener">Apri il sito ↗</a>
    </nav>
    <form method="post" action="<?= e(Admin::u('logout')) ?>" class="side__foot">
      <?= Admin::csrfField() ?>
      <span><?= e($u['name'] ?: $u['email']) ?></span>
      <button type="submit" class="link">Esci</button>
    </form>
  </aside>
  <main class="main" id="main">
    <?php if (class_exists('Seo') && Seo::hidden()): ?>
      <div class="hidden-site" role="note"><p>Il sito è nascosto ai motori di ricerca: Google non lo indicizza finché non lo apri.</p>
        <a class="btn btn--small" href="<?= e(Admin::u('seo')) ?>">Apri il sito ai motori di ricerca</a></div>
    <?php endif; ?>
    <?php foreach ($flash as [$ft, $fm]): ?>
      <p class="flash flash--<?= e($ft) ?>" role="status"><?= e($fm) ?></p>
    <?php endforeach; ?>
    <?= $body ?>
  </main>
</div>
<script src="<?= e(PP::url('pp-admin/admin.js')) ?>?v=<?= PP::VERSION ?>"></script>
</body>
</html>
