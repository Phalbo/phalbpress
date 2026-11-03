<?php
/**
 * Identità visiva: logo, nome, claim, colori, font, forma dei pulsanti, immagine di condivisione, favicon.
 * Genera uploads/identity/identity.css con le variabili CSS che il tema usa.
 */
require_once __DIR__ . '/Identity.php';
require_once __DIR__ . '/Palette.php';

return [
    'name' => 'identity',
    'label' => 'Identità visiva',
    'version' => '1.0.0',
    'boot' => function () {
        Hooks::on('strings', fn($d) => $d + [
            'claim' => ['group' => 'Identità', 'note' => 'Claim / payoff del progetto', 'text' => ['en' => '']],
        ]);
        Hooks::on('admin.pages', fn($p) => $p + [
            'identity' => ['label' => 'Identità visiva', 'group' => 'Sito', 'admin_only' => true, 'handler' => fn($seg) => Identity::admin()],
        ]);
    },
];
