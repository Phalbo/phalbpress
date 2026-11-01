<?php
/**
 * Eventi (workshop, conferenze, incontri): tipo "event" con data di inizio e fine, ora, luogo, link.
 * Pagine: /{lang}/{events}/ (prossimi e passati), /{lang}/{events}/{slug}/, file .ics, blocco "Prossimi eventi" nella home.
 * Si spegne con 'events' => false nel config del sito; l'indirizzo si cambia con config['sections']['events'].
 */
require_once __DIR__ . '/Events.php';

return [
    'name' => 'events',
    'label' => 'Eventi',
    'version' => '1.0.0',
    'boot' => function () {
        if (!Events::enabled()) return;
        PP::$config['types']['event'] ??= ['label' => 'Eventi', 'one' => 'evento', 'fields' => [
            'start' => ['label' => 'Data di inizio', 'type' => 'date'],
            'end' => ['label' => 'Data di fine', 'type' => 'date', 'hint' => 'facoltativa, per gli eventi di più giorni'],
            'time' => ['label' => 'Ora', 'hint' => 'facoltativa, es. 10:00-13:00'],
            'place' => ['label' => 'Luogo', 'translatable' => true, 'hint' => 'es. Roma, Palazzo Valentini · oppure Online'],
            'url' => ['label' => 'Link per iscriversi o seguirlo', 'type' => 'url'],
        ]];
        PP::$config['sections']['events'] ??= ['en' => 'events', 'it' => 'eventi', 'fr' => 'evenements', 'de' => 'veranstaltungen', 'es' => 'eventos', 'pt' => 'eventos'];
        Hooks::on('strings', fn($d) => $d + [
            'events_title' => ['group' => 'Sezioni', 'text' => ['en' => 'Events', 'it' => 'Eventi']],
            'events_intro' => ['group' => 'Sezioni', 'text' => ['en' => 'Workshops, meetings and conferences of the project.', 'it' => 'Workshop, incontri e conferenze del progetto.']],
            'events_upcoming' => ['group' => 'Sezioni', 'text' => ['en' => 'Coming up', 'it' => 'In programma']],
            'events_past' => ['group' => 'Sezioni', 'text' => ['en' => 'Past events', 'it' => 'Eventi passati']],
            'events_empty' => ['group' => 'Sezioni', 'text' => ['en' => 'New events will be announced here.', 'it' => 'Qui verranno annunciati i prossimi eventi.']],
            'events_all' => ['group' => 'Sezioni', 'text' => ['en' => 'All events', 'it' => 'Tutti gli eventi']],
            'event_when' => ['group' => 'Sezioni', 'text' => ['en' => 'When', 'it' => 'Quando']],
            'event_where' => ['group' => 'Sezioni', 'text' => ['en' => 'Where', 'it' => 'Dove']],
            'event_link' => ['group' => 'Sezioni', 'text' => ['en' => 'Register or follow', 'it' => 'Iscriviti o segui']],
            'event_ics' => ['group' => 'Sezioni', 'text' => ['en' => 'Add to calendar', 'it' => 'Aggiungi al calendario']],
        ]);
        Hooks::on('type_prefix', fn($type, $lang) => $type === 'event' ? Events::slug($lang) . '/' : null);
        Hooks::on('route', function ($lang, $rest) {
            if (($rest[0] ?? '') !== Events::slug($lang)) return null;
            if (!isset($rest[1])) return ['name' => 'events', 'tpl' => 'events'];
            $item = Content::entry('event', $rest[1], $lang, Content::$preview);
            return $item ? ['name' => 'events-item', 'params' => [(int)$item['id']], 'tpl' => 'event', 'vars' => ['item' => $item]] : null;
        });
        Hooks::on('alternate', function ($r, $lang) {
            if ($r['name'] === 'events') return PP::link(Events::slug($lang), $lang);
            if ($r['name'] === 'events-item') return PP::link(Events::slug($lang) . '/' . Content::slugFor((int)$r['params'][0], $lang), $lang);
            return null;
        });
        Hooks::on('paths', function ($items) {
            $p = [];
            foreach (I18n::codes() as $l) $p[$l] = Events::slug($l);
            $items[] = ['kind' => 'events', 'paths' => $p];
            foreach (DB::all("SELECT id FROM pp_entries WHERE type = 'event' AND " . Content::live()) as $e) {
                $q = [];
                foreach (I18n::codes() as $l) $q[$l] = Events::slug($l) . '/' . Content::slugFor((int)$e['id'], $l);
                $items[] = ['kind' => 'events-item', 'id' => (int)$e['id'], 'paths' => $q];
            }
            return $items;
        });
        Hooks::on('file', function ($path) {
            if (!preg_match('~^ics/([a-z]{2,3}(?:-[A-Za-z]+)?)/(\d+)\.ics$~', $path, $m) || !I18n::valid($m[1])) return null;
            I18n::$current = $m[1];
            $body = Events::ics((int)$m[2], $m[1]);
            return $body ? ['type' => 'text/calendar; charset=utf-8', 'body' => $body] : null;
        });
        Hooks::on('static_files', function ($f) {
            foreach (DB::all("SELECT id FROM pp_entries WHERE type = 'event' AND " . Content::live()) as $e) foreach (I18n::codes() as $l) $f[] = 'ics/' . $l . '/' . (int)$e['id'] . '.ics';
            return $f;
        });
        Hooks::on('menu_sections', fn($s) => $s + ['events' => 'events_title']);
        Hooks::on('search_types', fn($t) => array_merge($t, ['event']));
    },
];
