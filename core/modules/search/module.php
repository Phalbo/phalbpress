<?php
/**
 * Ricerca interna che funziona anche col sito statico: per ogni lingua si genera un indice JSON
 * (/search/{lang}.json) e la pagina di ricerca lo filtra nel browser. Nessun cookie, nessun servizio esterno.
 * Template del tema: templates/search.php (c'è sempre quello di base).
 */
require_once __DIR__ . '/Search.php';

return [
    'name' => 'search',
    'label' => 'Ricerca',
    'version' => '1.0.0',
    'boot' => function () {
        Hooks::on('strings', fn($d) => $d + [
            'search_title' => ['group' => 'Ricerca', 'text' => ['en' => 'Search', 'it' => 'Cerca']],
            'search_label' => ['group' => 'Ricerca', 'text' => ['en' => 'Search the site', 'it' => 'Cerca nel sito']],
            'search_button' => ['group' => 'Ricerca', 'text' => ['en' => 'Search', 'it' => 'Cerca']],
            'search_count' => ['group' => 'Ricerca', 'note' => '{n} = numero di risultati', 'text' => ['en' => '{n} results', 'it' => '{n} risultati']],
            'search_none' => ['group' => 'Ricerca', 'text' => ['en' => 'Nothing found. Try a different word.', 'it' => 'Nessun risultato. Prova con un’altra parola.']],
            'search_nojs' => ['group' => 'Ricerca', 'text' => ['en' => 'Search needs JavaScript. You can browse the menu instead.', 'it' => 'La ricerca richiede JavaScript. Puoi usare il menu.']],
            'search_kind_doc' => ['group' => 'Ricerca', 'text' => ['en' => 'Document', 'it' => 'Documento']],
        ]);
        Hooks::on('route', fn($lang, $rest) => count($rest) === 1 && $rest[0] === Search::slug($lang) ? ['name' => 'search', 'tpl' => 'search'] : null);
        Hooks::on('alternate', fn($r, $lang) => $r['name'] === 'search' ? PP::link(Search::slug($lang), $lang) : null);
        Hooks::on('paths', function ($items) {
            $p = [];
            foreach (I18n::codes() as $l) $p[$l] = Search::slug($l);
            $items[] = ['kind' => 'search', 'paths' => $p, 'noindex' => true];
            return $items;
        });
        Hooks::on('file', function ($path) {
            if (!preg_match('~^search/([a-z]{2,3}(?:-[a-z]+)?)\.json$~', $path, $m) || !I18n::valid($m[1])) return null;
            return ['type' => 'application/json; charset=utf-8', 'body' => Search::index($m[1])];
        });
        Hooks::on('static_files', function ($f) { foreach (I18n::codes() as $l) $f[] = 'search/' . $l . '.json'; return $f; });
        Hooks::on('menu_sections', fn($s) => $s + ['search' => 'search_title']);
    },
];
