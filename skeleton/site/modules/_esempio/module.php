<?php
/**
 * Modulo di esempio (disattivato: la cartella inizia con "_"). Rinominala in "esempio" per provarlo.
 * Mostra i punti di aggancio principali: testi, shortcode, rotta pubblica, pagina di gestione, tabella propria.
 */
return [
    'name' => 'esempio',
    'label' => 'Esempio: conto alla rovescia',
    'version' => '1.0.0',

    // Gira una volta quando 'version' cambia: crea o aggiorna tabelle con i tipi portabili di DB::t()
    'migrate' => function () {
        DB::pdo()->exec('CREATE TABLE IF NOT EXISTS ex_events (id ' . DB::t('id') . ', title ' . DB::t('string', 190) . ', day ' . DB::t('string', 10) . ')' . DB::t('tail'));
    },

    'boot' => function () {
        // testi traducibili (compaiono in Gestione → Testi del sito)
        Hooks::on('strings', fn($d) => $d + [
            'countdown_days' => ['group' => 'Esempio', 'text' => ['en' => '{n} days to go', 'it' => 'Mancano {n} giorni']],
        ]);

        // shortcode nei testi: [countdown:2028-03-08]
        Hooks::on('body', fn($html) => preg_replace_callback('~\[countdown:(\d{4}-\d{2}-\d{2})\]~', function ($m) {
            $n = max(0, (int)ceil((strtotime($m[1]) - time()) / 86400));
            return '<p class="countdown">' . e(str_replace('{n}', (string)$n, t('countdown_days'))) . '</p>';
        }, $html));

        // pagina pubblica: /{lingua}/eventi/  (con il tema: template "page")
        Hooks::on('route', function ($lang, $rest) {
            if ($rest !== ['eventi']) return null;
            $rows = DB::all('SELECT * FROM ex_events ORDER BY day');
            $body = '<ul>' . implode('', array_map(fn($r) => '<li>' . e($r['day'] . ' · ' . $r['title']) . '</li>', $rows)) . '</ul>';
            return ['name' => 'eventi', 'tpl' => 'page', 'vars' => ['page' => ['title' => 'Eventi', 'excerpt' => '', 'body' => $body, 'image' => '', '_fallback' => false, '_lang' => $lang]]];
        });
        Hooks::on('paths', fn($items) => array_merge($items, [['kind' => 'eventi', 'paths' => array_fill_keys(I18n::codes(), 'eventi')]]));  // sitemap, HTML statico
        Hooks::on('alternate', fn($r, $lang) => $r['name'] === 'eventi' ? PP::link('eventi', $lang) : null);

        // pagina di gestione: /gestione/eventi
        Hooks::on('admin.pages', fn($p) => $p + [
            'eventi' => ['label' => 'Eventi', 'group' => 'Contenuti', 'handler' => function ($seg) {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    DB::insert('ex_events', ['title' => trim((string)Admin::post('title')), 'day' => (string)Admin::post('day')]);
                    Admin::changed('eventi');   // rigenera HTML statico, scansione cookie…
                    Admin::flash('Evento aggiunto.');
                    PP::redirect(Admin::u('eventi'));
                }
                Admin::view(__DIR__ . '/admin.php', ['title' => 'Eventi', 'rows' => DB::all('SELECT * FROM ex_events ORDER BY day')]);
            }],
        ]);
    },
];
