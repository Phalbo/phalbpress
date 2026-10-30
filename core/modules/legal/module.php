<?php
/**
 * Legale e accessibilità: privacy policy, cookie policy, dichiarazione di accessibilità (generate dalla configurazione,
 * in tutte le lingue), scansione dei servizi esterni che usano cookie, banner di consenso solo se servono,
 * controlli di accessibilità dei contenuti.
 */
require_once __DIR__ . '/Legal.php';
require_once __DIR__ . '/Checks.php';

return [
    'name' => 'legal',
    'label' => 'Legale e accessibilità',
    'version' => '1.0.0',
    'boot' => function () {
        Hooks::on('strings', fn($d) => $d + require __DIR__ . '/strings.php');
        Hooks::on('route', fn($lang, $rest) => Legal::route($lang, $rest));
        Hooks::on('alternate', fn($r, $lang) => $r['name'] === 'legal' ? PP::link(Legal::slug($r['params'][0], $lang), $lang) : null);
        Hooks::on('paths', fn($items) => array_merge($items, Legal::items()));
        Hooks::on('body_end', fn() => Legal::banner());
        Hooks::on('footer_links', fn($l) => array_merge($l, Legal::footerLinks()));
        Hooks::on('saved', fn($what) => Legal::scan());
        Hooks::on('admin.pages', fn($p) => $p + [
            'privacy' => ['label' => 'Privacy e cookie', 'group' => 'Sito', 'handler' => fn($seg) => Legal::admin()],
            'accessibility' => ['label' => 'Accessibilità', 'group' => 'Sito', 'handler' => fn($seg) => Checks::admin()],
        ]);
    },
];
