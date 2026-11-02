<?php
/**
 * Modulo di contatto: [form:contact] in qualsiasi testo.
 * Antispam senza servizi esterni né cookie: campo trappola nascosto; gettone chiesto dal browser all'apertura della pagina
 * (GET /form/{nome}/key, valido da 3 secondi a 4 ore dopo il rilascio: un invio diretto senza la pagina non passa);
 * al massimo 5 invii l'ora per indirizzo (colonna ip, impronta dell'indirizzo, mai l'indirizzo in chiaro).
 * Le richieste si salvano nel database (Gestione → Richieste) e arrivano per email.
 */
require_once __DIR__ . '/Forms.php';

return [
    'name' => 'forms',
    'label' => 'Moduli di contatto',
    'version' => '1.1.0',
    'migrate' => function () {
        DB::addColumn('pp_form_entries', 'ip', DB::t('string', 40) . " NOT NULL DEFAULT ''");
        try { DB::pdo()->exec('CREATE INDEX pp_form_entries_ip ON pp_form_entries (ip, at)'); } catch (Throwable $e) {}   // c'è già
    },
    'boot' => function () {
        Hooks::on('strings', fn($d) => $d + Forms::strings());
        Hooks::on('body', fn($html) => preg_replace_callback('~(?:<p>\s*)?\[form:([a-z0-9_-]+)\](?:\s*</p>)?~', fn($m) => Forms::render($m[1]), $html));
        Hooks::on('post', fn($path) => preg_match('~^form/([a-z0-9_-]+)$~', $path, $m) ? Forms::submit($m[1]) : null);
        Hooks::on('file', fn($path) => preg_match('~^form/([a-z0-9_-]+)/key$~', $path, $m) ? Forms::key($m[1]) : null);
        Hooks::on('admin.pages', fn($p) => $p + [
            'forms' => ['label' => 'Richieste', 'group' => 'Contenuti', 'perm' => 'site', 'handler' => fn($seg) => Forms::admin($seg[0] ?? '')],
        ]);
    },
];
