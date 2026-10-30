<?php
/**
 * Menu: principale e footer, con pagine, sezioni e link esterni, in ordine libero.
 * Senza un menu salvato si usa quello automatico (pagine "nel menu" + sezioni fisse).
 * Nei temi: Menus::items('main') → [['label','href','current','external','children'=>[]]]
 */
require_once __DIR__ . '/Menus.php';

return [
    'name' => 'menus',
    'label' => 'Menu',
    'version' => '1.0.0',
    'boot' => function () {
        Hooks::on('strings', fn($d) => $d + [
            'nav_main' => ['group' => 'Generale', 'note' => 'nome del menu principale per i lettori di schermo', 'text' => ['en' => 'Main menu', 'it' => 'Menu principale']],
        ]);
        Hooks::on('admin.pages', fn($p) => $p + [
            'menus' => ['label' => 'Menu', 'group' => 'Sito', 'perm' => 'site', 'handler' => fn($seg) => Menus::admin($seg[0] ?? 'main')],
        ]);
    },
];
