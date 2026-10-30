<?php
/**
 * Modulo di contatto: [form:contact] in qualsiasi testo.
 * Antispam senza servizi esterni né cookie: campo trappola nascosto, controllo che il modulo sia stato compilato
 * da una persona (token messo dal browser dopo qualche secondo), limite di invii per indirizzo.
 * Le richieste si salvano nel database (Gestione → Richieste) e arrivano per email.
 */
require_once __DIR__ . '/Forms.php';

return [
    'name' => 'forms',
    'label' => 'Moduli di contatto',
    'version' => '1.0.0',
    'boot' => function () {
        Hooks::on('strings', fn($d) => $d + Forms::strings());
        Hooks::on('body', fn($html) => preg_replace_callback('~(?:<p>\s*)?\[form:([a-z0-9_-]+)\](?:\s*</p>)?~', fn($m) => Forms::render($m[1]), $html));
        Hooks::on('post', fn($path) => preg_match('~^form/([a-z0-9_-]+)$~', $path, $m) ? Forms::submit($m[1]) : null);
        Hooks::on('admin.pages', fn($p) => $p + [
            'forms' => ['label' => 'Richieste', 'group' => 'Contenuti', 'perm' => 'site', 'handler' => fn($seg) => Forms::admin($seg[0] ?? '')],
        ]);
    },
];
