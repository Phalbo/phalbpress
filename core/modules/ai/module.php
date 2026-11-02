<?php
/**
 * Ottimizzazione per i motori di ricerca con intelligenza artificiale (ChatGPT, Claude, Perplexity, Gemini…):
 * robots.txt con le regole per i crawler AI, llms.txt (indice del sito per le AI) e llms-full.txt (testo completo).
 */
require_once __DIR__ . '/Ai.php';

return [
    'name' => 'ai',
    'label' => 'AI search',
    'version' => '1.0.0',
    'boot' => function () {
        Hooks::on('strings', fn($d) => $d + [
            'ai_summary' => ['group' => 'AI', 'note' => 'Riassunto del sito per le AI (2-4 frasi, fatti concreti)', 'text' => ['en' => '']],
            'ai_facts' => ['group' => 'AI', 'note' => 'Fatti chiave, uno per riga (chi, cosa, dove, quando, finanziamento)', 'text' => ['en' => '']],
        ]);
        Hooks::on('file', function ($path) {
            if ($path === 'robots.txt') return ['type' => 'text/plain; charset=utf-8', 'body' => Ai::robots()];
            if ($path === 'llms.txt') return ['type' => 'text/plain; charset=utf-8', 'body' => Ai::llms(I18n::$master)];
            if ($path === 'llms-full.txt') return ['type' => 'text/plain; charset=utf-8', 'body' => Ai::full(I18n::$master)];
            if (preg_match('~^([a-z]{2,3}(?:-[a-z]{2,4})?)/llms\.txt$~', $path, $m) && I18n::valid($m[1])) return ['type' => 'text/plain; charset=utf-8', 'body' => Ai::llms($m[1])];
            return null;
        });
        Hooks::on('head', fn($ctx) => '<link rel="alternate" type="text/plain" title="llms.txt" href="' . e(PP::url('llms.txt')) . "\">\n", 20);
        Hooks::on('admin.pages', fn($p) => $p + [
            'ai' => ['label' => 'AI search', 'group' => 'Sito', 'handler' => fn($seg) => Ai::admin()],
        ]);
        Hooks::on('static_files', fn($files) => array_merge($files, ['robots.txt', 'llms.txt', 'llms-full.txt'], array_map(fn($l) => $l . '/llms.txt', I18n::others())));
    },
];
