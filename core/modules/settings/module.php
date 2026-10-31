<?php
/**
 * Impostazioni del sito modificabili dalla gestione (Sistema → Impostazioni del sito):
 * email di contatto e del mittente, social, finanziatore, traduzione automatica.
 * Valgono sopra site/config.php, che resta per il database e la struttura (lingue, sezioni, tipi).
 */
require_once __DIR__ . '/SiteSettings.php';

return [
    'name' => 'settings',
    'label' => 'Impostazioni del sito',
    'version' => '1.0.0',
    'boot' => function () {
        Hooks::on('admin.pages', fn($p) => $p + [
            'settings' => ['label' => 'Impostazioni del sito', 'group' => 'Sistema', 'admin_only' => true, 'handler' => fn($seg) => SiteSettings::admin()],
        ]);
    },
];
