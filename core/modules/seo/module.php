<?php
/**
 * SEO: titolo e descrizione automatici per ogni pagina e lingua (modificabili), canonical, hreflang,
 * Open Graph, dati strutturati JSON-LD, sitemap.xml.
 */
require_once __DIR__ . '/Seo.php';

return [
    'name' => 'seo',
    'label' => 'SEO',
    'version' => '1.0.0',
    'boot' => function () {
        Hooks::on('head', fn($ctx) => Seo::head($ctx));
        Hooks::on('file', fn($path) => $path === 'sitemap.xml' ? ['type' => 'application/xml; charset=utf-8', 'body' => Seo::sitemap()] : null);
        Hooks::on('admin.pages', fn($p) => $p + [
            'seo' => ['label' => 'SEO', 'group' => 'Sito', 'handler' => fn($seg) => Seo::admin($seg[0] ?? I18n::$master)],
        ]);
    },
];
