<?php
/**
 * Sito dimostrativo di PhalbPress: progetto inventato "Common Ground".
 * Serve per provare i temi con contenuti e foto credibili: php tools/demo.php
 */
return [
    'site_name' => 'Common Ground',
    'site_url' => '',
    'base_path' => '',
    'admin_slug' => 'gestione',
    'debug' => true,
    'timezone' => 'Europe/Rome',
    'db' => ['driver' => 'sqlite', 'file' => __DIR__ . '/../data/site.sqlite'],

    'master_language' => 'en',
    'languages' => [
        'en' => ['native' => 'English', 'label' => 'EN', 'english' => 'English', 'locale' => 'en_GB', 'hreflang' => 'en'],
        'it' => ['native' => 'Italiano', 'label' => 'IT', 'english' => 'Italian', 'locale' => 'it_IT', 'hreflang' => 'it'],
        'de' => ['native' => 'Deutsch', 'label' => 'DE', 'english' => 'German', 'locale' => 'de_DE', 'hreflang' => 'de'],
    ],
    'sections' => [
        'news' => ['en' => 'news', 'it' => 'notizie', 'de' => 'aktuelles'],
        'stories' => ['en' => 'stories', 'it' => 'storie', 'de' => 'geschichten'],
        'results' => ['en' => 'results', 'it' => 'risultati', 'de' => 'ergebnisse'],
        'partners' => ['en' => 'partners', 'it' => 'partner', 'de' => 'partner'],
        'search' => ['en' => 'search', 'it' => 'cerca', 'de' => 'suche'],
    ],
    'section_menu' => ['news' => 3, 'stories' => 4, 'results' => 5, 'partners' => 6],
    'theme' => 'istituzionale',

    'types' => [
        'page' => ['label' => 'Pagine', 'one' => 'pagina'],
        'news' => ['label' => 'News', 'one' => 'news', 'fields' => [
            'category' => ['label' => 'Categoria', 'type' => 'entry', 'entry_type' => 'category'],
        ]],
        'story' => ['label' => 'Storie', 'one' => 'storia', 'fields' => [
            'country' => 'Paese',
            'role' => ['label' => 'Ruolo', 'translatable' => true],
            'anonymous' => ['label' => 'Senza foto né nome completo', 'type' => 'checkbox'],
            'video' => ['label' => 'Video YouTube', 'hint' => 'ID o link'],
        ]],
        'category' => ['label' => 'Categorie', 'one' => 'categoria'],
        'partner' => ['label' => 'Partner', 'one' => 'partner', 'fields' => [
            'country' => 'Paese', 'short' => 'Sigla', 'url' => ['label' => 'Sito', 'type' => 'url'],
            'role' => ['label' => 'Ruolo', 'type' => 'select', 'options' => ['Coordinator' => 'Capofila', 'Partner' => 'Partner']],
        ]],
        'block' => ['label' => 'Fasce della home', 'one' => 'fascia'],
    ],
    'doc_categories' => ['deliverable' => 'Deliverable', 'toolkit' => 'Toolkit', 'report' => 'Report', 'press' => 'Comunicati'],

    'identity' => [
        'logo' => 'site/assets/logo.svg', 'favicon' => 'site/assets/favicon.svg',
        'colors' => ['ground' => '#f6f3ec', 'ink' => '#16181d', 'accent' => '#c8402a', 'field1' => '#9fc7a8', 'field2' => '#a9c3e0', 'dark' => '#16181d', 'hero1' => '#ffffff', 'hero2' => '#f2c94c'],
        'font_display' => 'Bodoni Moda', 'font_text' => 'Jost', 'buttons' => 'pill',
    ],

    'image_max_px' => 2400, 'image_quality' => 82, 'image_format' => 'webp', 'upload_max_mb' => 20,
    'image_hints' => [
        'default' => 'orizzontale, almeno 1600 px',
        'news' => 'orizzontale 3:2, almeno 1800 px',
        'story' => 'verticale 4:5, almeno 1200 px di larghezza',
        'partner' => 'logo SVG o PNG trasparente',
    ],
    'revisions_keep' => 10,
    'static' => false,
    'translation' => ['provider' => '', 'api_key' => '', 'glossary' => ['Common Ground', 'CERV']],
    'publish_machine_translations' => true,

    'contact_email' => 'hello@example.org',
    'mail_from' => 'no-reply@example.org',
    'social' => ['Instagram' => 'https://www.instagram.com/', 'LinkedIn' => 'https://www.linkedin.com/'],
    'funder' => 'European Union',
    'legal' => ['controller' => 'Common Ground consortium (demo)', 'email' => 'privacy@example.org', 'address' => 'Via dell’Esempio 1, 00100 Roma'],
    'update' => ['repo' => 'Phalbo/phalbpress', 'notify' => false],
];
