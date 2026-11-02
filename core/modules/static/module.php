<?php
/**
 * Sito statico: dopo ogni salvataggio genera le pagine pubbliche come file HTML (a fine richiesta, dopo la risposta).
 * Chi visita il sito riceve solo HTML: nessun PHP e nessun database da attaccare sulle pagine pubbliche.
 * Più la pagina "Sistema" con versioni, moduli e controlli di sicurezza.
 */
require_once __DIR__ . '/StaticSite.php';

return [
    'name' => 'static',
    'label' => 'Sito statico e sistema',
    'version' => '1.0.0',
    'boot' => function () {
        // la rigenerazione parte dopo la risposta (StaticSite::later): salvare non fa più aspettare tutto il sito
        Hooks::on('saved', fn($what) => StaticSite::enabled() ? StaticSite::later() : null, 50);
        Hooks::on('admin.pages', fn($p) => $p + [
            'system' => ['label' => 'Sistema', 'group' => 'Account', 'admin_only' => true, 'handler' => fn($seg) => StaticSite::admin()],
        ]);
    },
];
