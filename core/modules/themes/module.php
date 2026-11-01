<?php
/**
 * Temi intercambiabili (pp-themes/) e home a blocchi.
 * Tutti i temi usano gli stessi contenuti e la stessa struttura HTML di pp-themes/_base: cambiarne uno non tocca i testi.
 * I colori e i font restano quelli dell'Identità visiva; ogni tema ha una palette suggerita che si può applicare.
 */
require_once __DIR__ . '/Blocks.php';
require_once __DIR__ . '/Themes.php';
require_once __DIR__ . '/Visual.php';
require_once __DIR__ . '/Layouts.php';
require_once __DIR__ . '/Stories.php';
require_once __DIR__ . '/Demo.php';

return [
    'name' => 'themes',
    'label' => 'Temi e blocchi',
    'version' => '1.1.0',
    'boot' => function () {
        // testi comuni a tutti i temi (pulsanti, etichette): il sito può riscriverli in site/strings.php
        Hooks::on('strings', fn($d) => $d + (require PP_ROOT . '/pp-themes/_base/strings.php'), 5);
        Hooks::on('fields', fn($f, $type, $row = null) => $type === 'block' ? Blocks::fields((string)($row['template'] ?? 'text')) : $f + Layouts::fields((string)$type));
        Hooks::on('admin.pages', fn($p) => $p + [
            'themes' => ['label' => 'Temi', 'group' => 'Sito', 'perm' => 'admin', 'handler' => fn($seg) => Themes::admin()],
            'visual' => ['label' => 'Modifica visuale', 'group' => 'Sito', 'perm' => 'publish', 'handler' => fn($seg) => Visual::admin($seg)],
        ]);
    },
];
