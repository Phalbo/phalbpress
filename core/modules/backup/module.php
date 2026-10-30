<?php
/**
 * Backup e modelli.
 * - Backup completo: database (tutte le tabelle del sito) + cartella uploads in un unico .zip, da scaricare o ripristinare.
 * - Modello: contenuti, testi, impostazioni, identità e immagini, senza utenti, richieste e registro.
 *   Serve a far partire un nuovo sito (CWL, Prosolidar…) da uno esistente: in installazione "Parti da un modello".
 * Il codice (site/theme, site/modules) non entra mai nei .zip: si copia a mano, così un .zip non può portare PHP sul server.
 */
require_once __DIR__ . '/Backup.php';

return [
    'name' => 'backup',
    'label' => 'Backup e modelli',
    'version' => '1.0.0',
    'boot' => function () {
        Hooks::on('admin.pages', fn($p) => $p + [
            'backup' => ['label' => 'Backup e modelli', 'group' => 'Account', 'perm' => 'admin', 'handler' => fn($seg) => Backup::admin($seg)],
        ]);
    },
];
