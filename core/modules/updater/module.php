<?php
/**
 * Aggiornamenti del motore da GitHub (release firmate), con backup automatico e ritorno alla versione precedente.
 * Configurazione facoltativa in site/config.php:
 *   'update' => ['repo' => 'Phalbo/phalbpress', 'notify' => true]   // notify: email agli admin quando esce una versione (da cron.php)
 */
require_once __DIR__ . '/Updater.php';

return [
    'name' => 'updater',
    'label' => 'Aggiornamenti',
    'version' => '1.0.0',
    'boot' => function () {
        Hooks::on('admin.pages', fn($p) => $p + [
            'update' => ['label' => Updater::available() ? 'Aggiornamenti (' . Updater::info()['version'] . ')' : 'Aggiornamenti',
                'group' => 'Account', 'admin_only' => true, 'handler' => fn($seg) => Updater::admin()],
        ]);
        Hooks::on('cron', fn() => Updater::cron());
    },
];
