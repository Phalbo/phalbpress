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
require_once __DIR__ . '/Proofs.php';

return [
    'name' => 'themes',
    'label' => 'Temi e blocchi',
    'version' => '1.3.0',
    // 1.2.0: le fasce che avevano perso i testi cambiando tipo li ritrovano dalle versioni precedenti
    // 1.3.0: "scelti a mano" in un campo solo, senza limite
    'migrate' => function () { Blocks::recover(); Blocks::migratePicks(); },
    'boot' => function () {
        // testi comuni a tutti i temi (pulsanti, etichette): il sito può riscriverli in site/strings.php
        Hooks::on('strings', fn($d) => $d + (require PP_ROOT . '/pp-themes/_base/strings.php'), 5);
        Hooks::on('fields', fn($f, $type, $row = null) => $type === 'block' ? Blocks::fields((string)($row['template'] ?? 'text')) : $f + Layouts::fields((string)$type));
        // fasce legate a una pagina per slug (contenuti iniziali): si legano all'id quando la pagina c'è
        Hooks::on('saved', fn($what) => Blocks::bindPages(), 30);
        Hooks::on('admin.pages', fn($p) => $p + [
            'themes' => ['label' => 'Temi', 'group' => 'Sito', 'perm' => 'admin', 'handler' => fn($seg) => Themes::admin()],
            'visual' => ['label' => 'Modifica visuale', 'group' => 'Sito', 'perm' => 'publish', 'handler' => fn($seg) => Visual::admin($seg)],
        ]);
    },
];
