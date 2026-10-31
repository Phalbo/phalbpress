<?php
/**
 * Contenuti del sito dimostrativo "Common Ground" (progetto, persone e partner inventati).
 * Le due categorie stanno in cima: su un database nuovo prendono gli id 1 e 2, usati dalle news.
 * Il tedesco non è scritto a mano: copia dell'inglese segnata come traduzione automatica.
 */
$auto = function (array $e): array {
    if (isset($e['tr']['en']) && !isset($e['tr']['de'])) { $e['tr']['de'] = $e['tr']['en']; $e['auto'] = ['de']; }
    return $e;
};
$p = fn(string ...$x) => '<p>' . implode('</p><p>', $x) . '</p>';

$entries = [
    // ---------- categorie (id 1 e 2) ----------
    ['type' => 'category', 'slug' => 'events', 'tr' => ['en' => ['title' => 'Events'], 'it' => ['title' => 'Eventi']]],
    ['type' => 'category', 'slug' => 'labs', 'tr' => ['en' => ['title' => 'Local labs'], 'it' => ['title' => 'Cantieri locali']]],

    // ---------- pagine ----------
    ['type' => 'page', 'slug' => 'home', 'template' => 'home', 'tr' => ['en' => ['title' => 'Common Ground'], 'it' => ['title' => 'Common Ground']]],
    ['type' => 'page', 'slug' => 'the-project', 'in_menu' => 1, 'menu_order' => 1, 'image' => 'seed-media/hero-2.webp', 'tr' => [
        'en' => ['title' => 'The project', 'excerpt' => 'Twenty-four months to test a simple idea: the people who use a square should help decide what it becomes.',
            'image_alt' => 'Rooftops of Rome seen from above, with a dome in the distance',
            'body' => $p('Common Ground brings together youth workers, small municipalities and researchers from four countries. In each town a group of young people between 16 and 25 works with the local council on one real public space: a square, a disused school yard, a riverside path.',
                'The project runs in three phases. First we listen: walks, interviews and mapping with residents. Then we build: low-cost temporary changes tested for a few weeks. Finally we share: what worked becomes a toolkit that any small town can use.')
                . '<h2>Who it is for</h2>' . $p('Young people who rarely take part in local decisions, the officers who manage public spaces, and the associations that already work with both.')
                . '<h2>What we will leave behind</h2><ul><li>Six transformed public spaces</li><li>An open toolkit in five languages</li><li>A network of towns willing to try again</li></ul>'],
        'it' => ['title' => 'Il progetto', 'excerpt' => 'Ventiquattro mesi per mettere alla prova un’idea semplice: chi vive una piazza dovrebbe aiutare a decidere cosa diventa.',
            'image_alt' => 'I tetti di Roma visti dall’alto, con una cupola in lontananza',
            'body' => $p('Common Ground mette insieme educatori, piccoli comuni e ricercatori di quattro paesi. In ogni comune un gruppo di ragazze e ragazzi tra i 16 e i 25 anni lavora con l’amministrazione su uno spazio pubblico vero: una piazza, un cortile scolastico abbandonato, un argine.',
                'Il progetto ha tre fasi. Prima ascoltiamo: passeggiate, interviste e mappe con chi ci abita. Poi costruiamo: piccoli interventi temporanei e a basso costo, provati per qualche settimana. Infine condividiamo: quello che ha funzionato diventa un toolkit che qualsiasi piccolo comune può usare.')
                . '<h2>Per chi è</h2>' . $p('Per i giovani che di solito non partecipano alle decisioni locali, per i funzionari che gestiscono gli spazi pubblici e per le associazioni che già lavorano con entrambi.')
                . '<h2>Cosa resterà</h2><ul><li>Sei spazi pubblici trasformati</li><li>Un toolkit aperto in cinque lingue</li><li>Una rete di comuni pronti a riprovarci</li></ul>'],
    ]],
    ['type' => 'page', 'slug' => 'how-we-work', 'in_menu' => 1, 'menu_order' => 2, 'tr' => [
        'en' => ['title' => 'How we work', 'excerpt' => 'Listen, build, share: the method behind every local lab.',
            'body' => '<h2>Listen</h2>' . $p('Each lab starts with a month of walks and conversations. The young participants interview residents, shopkeepers and council staff, and draw the space as it is used, not as it appears on the map.')
                . '<h2>Build</h2>' . $p('Together with the municipality they choose one change to test: benches, shade, a stage, a new route. Everything is temporary and cheap, so that failure costs little.')
                . '<blockquote><p>The best idea in our town came from a fifteen-year-old who walks across the square twice a day.</p></blockquote>'
                . '<h2>Share</h2>' . $p('At the end of each lab the group writes down what happened, including what went wrong. Those notes become the chapters of the toolkit.')],
        'it' => ['title' => 'Come lavoriamo', 'excerpt' => 'Ascoltare, costruire, condividere: il metodo dietro ogni cantiere locale.',
            'body' => '<h2>Ascoltare</h2>' . $p('Ogni cantiere comincia con un mese di passeggiate e conversazioni. I ragazzi intervistano residenti, negozianti e personale del comune, e disegnano lo spazio per come viene usato, non per come appare sulla mappa.')
                . '<h2>Costruire</h2>' . $p('Insieme al comune scelgono un cambiamento da provare: panchine, ombra, un palco, un nuovo percorso. Tutto è temporaneo ed economico, così sbagliare costa poco.')
                . '<blockquote><p>L’idea migliore del nostro paese è venuta da una quindicenne che attraversa la piazza due volte al giorno.</p></blockquote>'
                . '<h2>Condividere</h2>' . $p('Alla fine di ogni cantiere il gruppo scrive cosa è successo, compreso quello che è andato storto. Quelle note diventano i capitoli del toolkit.')],
    ]],
    ['type' => 'page', 'slug' => 'contact', 'in_menu' => 1, 'menu_order' => 9, 'tr' => [
        'en' => ['title' => 'Contact', 'excerpt' => 'Write to us: we answer within a week.', 'body' => $p('Questions about the project, the labs or the toolkit? Use the form below.') . '[form:contact]'],
        'it' => ['title' => 'Contatti', 'excerpt' => 'Scriveteci: rispondiamo entro una settimana.', 'body' => $p('Domande sul progetto, sui cantieri o sul toolkit? Usate il modulo qui sotto.') . '[form:contact]'],
    ]],

    // ---------- blocchi della home ----------
    ['type' => 'block', 'slug' => 'home-hero', 'template' => 'hero', 'menu_order' => 1, 'image' => 'seed-media/hero-1.webp',
        'meta' => ['cta_url' => 'the-project'], 'tr' => [
        'en' => ['title' => 'Common ground', 'excerpt' => 'Young people and small towns, shaping public space together.', 'image_alt' => 'A square in Rome with a fountain and a bell tower',
            'extra' => ['line2' => 'starts in the square', 'cta_label' => 'Discover the project']],
        'it' => ['title' => 'Il terreno comune', 'excerpt' => 'Giovani e piccoli comuni, insieme per gli spazi pubblici.', 'image_alt' => 'Una piazza di Roma con una fontana e un campanile',
            'extra' => ['line2' => 'parte dalla piazza', 'cta_label' => 'Scopri il progetto']],
    ]],
    ['type' => 'block', 'slug' => 'home-statement', 'template' => 'statement', 'menu_order' => 2, 'tr' => [
        'en' => ['title' => 'Public space is decided by the people who are already in the room.', 'excerpt' => 'We want to change who is in the room. In six towns, young people sit at the same table as the council and work on one real place.',
            'extra' => ['fact' => "6 towns\n4 countries\n24 months"]],
        'it' => ['title' => 'Lo spazio pubblico lo decide chi è già nella stanza.', 'excerpt' => 'Vogliamo cambiare chi c’è nella stanza. In sei comuni i giovani siedono allo stesso tavolo dell’amministrazione e lavorano su un luogo vero.',
            'extra' => ['fact' => "6 comuni\n4 paesi\n24 mesi"]],
    ]],
    ['type' => 'block', 'slug' => 'home-steps', 'template' => 'steps', 'menu_order' => 3, 'tr' => [
        'en' => ['title' => 'How a local lab works', 'extra' => [
            's1_t' => 'Walk the space', 's1_d' => 'A month of walks, interviews and maps with the people who use it every day.',
            's2_t' => 'Choose one change', 's2_d' => 'Young people and council staff agree on a single, concrete thing to test.',
            's3_t' => 'Build it cheap', 's3_d' => 'Temporary, low-cost, reversible: benches, shade, a route, a stage.',
            's4_t' => 'Write it down', 's4_d' => 'What worked and what did not becomes a chapter of the toolkit.']],
        'it' => ['title' => 'Come funziona un cantiere locale', 'extra' => [
            's1_t' => 'Percorrere lo spazio', 's1_d' => 'Un mese di passeggiate, interviste e mappe con chi lo usa ogni giorno.',
            's2_t' => 'Scegliere un cambiamento', 's2_d' => 'Ragazzi e personale del comune si accordano su una cosa concreta da provare.',
            's3_t' => 'Costruire con poco', 's3_d' => 'Temporaneo, economico, reversibile: panchine, ombra, un percorso, un palco.',
            's4_t' => 'Scriverlo', 's4_d' => 'Quello che ha funzionato e quello che no diventa un capitolo del toolkit.']],
    ]],
    ['type' => 'block', 'slug' => 'home-figures', 'template' => 'figures', 'menu_order' => 4, 'meta' => ['n1_v' => '6', 'n2_v' => '120', 'n3_v' => '1,400', 'n4_v' => '5'], 'tr' => [
        'en' => ['title' => 'In numbers', 'excerpt' => 'Targets for the two years of the project.',
            'extra' => ['n1_l' => 'public spaces transformed', 'n2_l' => 'young participants', 'n3_l' => 'residents consulted', 'n4_l' => 'languages of the toolkit']],
        'it' => ['title' => 'In numeri', 'excerpt' => 'Gli obiettivi dei due anni di progetto.',
            'extra' => ['n1_l' => 'spazi pubblici trasformati', 'n2_l' => 'giovani partecipanti', 'n3_l' => 'residenti consultati', 'n4_l' => 'lingue del toolkit']],
    ]],
    ['type' => 'block', 'slug' => 'home-stories', 'template' => 'collection', 'menu_order' => 5, 'meta' => ['source' => 'story', 'limit' => 3, 'style' => 'cards'], 'tr' => [
        'en' => ['title' => 'Voices from the labs', 'excerpt' => 'Three people, three towns, one question: who gets to decide?', 'extra' => ['more_label' => 'All stories']],
        'it' => ['title' => 'Voci dai cantieri', 'excerpt' => 'Tre persone, tre comuni, una domanda: chi decide?', 'extra' => ['more_label' => 'Tutte le storie']],
    ]],
    ['type' => 'block', 'slug' => 'home-latest', 'template' => 'latest', 'menu_order' => 6, 'meta' => ['limit' => 3, 'docs' => 1], 'tr' => [
        'en' => ['title' => 'Latest from the project'], 'it' => ['title' => 'Le ultime dal progetto'],
    ]],
    ['type' => 'block', 'slug' => 'home-text', 'template' => 'text', 'menu_order' => 7,
        'meta' => ['bg_image' => 'seed-media/fascia-2.webp', 'bg_overlay' => 'light', 'width' => 'narrow', 'spacing' => 'wide'], 'tr' => [
        'en' => ['title' => 'Why small towns', 'body' => $p('Small towns lose young people first. Those who stay rarely see their ideas reach the council, and those who leave rarely come back.', 'A square is a good place to start: it is concrete, everyone uses it, and a small change is visible the next morning.')],
        'it' => ['title' => 'Perché i piccoli comuni', 'body' => $p('I piccoli comuni perdono per primi i giovani. Chi resta raramente vede le proprie idee arrivare in consiglio, e chi parte raramente torna.', 'Una piazza è un buon punto di partenza: è concreta, la usano tutti, e un piccolo cambiamento si vede già la mattina dopo.')],
    ]],
    ['type' => 'block', 'slug' => 'home-gallery', 'template' => 'gallery', 'menu_order' => 8,
        'meta' => ['images' => implode("\n", array_map(fn($i) => "seed-media/galleria-$i.webp", range(1, 6)))], 'tr' => [
        'en' => ['title' => 'From the labs', 'excerpt' => 'Moments from the first season of local labs.',
            'extra' => ['alts' => "Colleagues celebrating in an open-plan office\nA teacher smiling with students working behind her\nPeople walking on a wooden boardwalk, seen at ground level\nA hand pinning a sticky note to a wall\nA young woman working on a laptop\nTwo girls looking at a smartphone under the trees"]],
        'it' => ['title' => 'Dai cantieri', 'excerpt' => 'Momenti dalla prima stagione dei cantieri locali.',
            'extra' => ['alts' => "Colleghi che festeggiano in un ufficio open space\nUn’insegnante sorride con gli studenti al lavoro dietro di lei\nPersone che camminano su una passerella di legno, viste da terra\nUna mano attacca un post-it al muro\nUna ragazza al lavoro sul portatile\nDue bambine guardano uno smartphone sotto gli alberi"]],
    ]],
    ['type' => 'block', 'slug' => 'home-video', 'template' => 'video', 'menu_order' => 9, 'meta' => ['video' => 'aqz-KE-bpKQ'], 'tr' => [
        'en' => ['title' => 'Watch', 'excerpt' => 'A sample video: it loads only after you accept YouTube.'],
        'it' => ['title' => 'Guarda', 'excerpt' => 'Un video di esempio: si carica solo dopo il consenso a YouTube.'],
    ]],
    ['type' => 'block', 'slug' => 'home-cta', 'template' => 'cta', 'menu_order' => 10,
        'meta' => ['cta_url' => 'contact', 'bg_image' => 'seed-media/fascia-1.webp'], 'tr' => [
        'en' => ['title' => 'Is your town next?', 'excerpt' => 'We are looking for municipalities that want to try the method in 2027.', 'extra' => ['cta_label' => 'Get in touch']],
        'it' => ['title' => 'Il prossimo è il tuo comune?', 'excerpt' => 'Cerchiamo comuni che vogliano provare il metodo nel 2027.', 'extra' => ['cta_label' => 'Scrivici']],
    ]],
];

// ---------- news ----------
$news = [
    [1, '2026-10-12', 1, 'Kick-off in Rome: five partners, one table', 'Kick-off a Roma: cinque partner, un tavolo',
        'Two days to agree on the method, the calendar and the six towns.', 'Due giorni per accordarsi su metodo, calendario e i sei comuni.',
        'Colleagues working together around a desk', 'Colleghi che lavorano insieme attorno a una scrivania'],
    [2, '2026-10-02', 2, 'Training the facilitators', 'La formazione dei facilitatori',
        'Twenty youth workers learned how to run a listening walk.', 'Venti educatori hanno imparato a condurre una passeggiata di ascolto.',
        'A trainer standing in a classroom while participants work', 'Un formatore in aula mentre i partecipanti lavorano'],
    [3, '2026-09-18', 2, 'Mapping the square, one laptop at a time', 'Mappare la piazza, un portatile alla volta',
        'How the first lab turned interviews into a shared digital map.', 'Come il primo cantiere ha trasformato le interviste in una mappa digitale condivisa.',
        'A young woman working on a laptop by the window', 'Una ragazza al portatile vicino alla finestra'],
    [4, '2026-09-05', 1, 'A public evening in the town hall', 'Una serata pubblica in municipio',
        'Over a hundred residents came to hear what the young people proposed.', 'Più di cento residenti sono venuti ad ascoltare le proposte dei ragazzi.',
        'An audience listening to a speaker', 'Un pubblico che ascolta un relatore'],
    [5, '2026-08-21', 2, 'Testing shade on the riverside path', 'Prove d’ombra sull’argine',
        'Three weeks of temporary canopies and what the counters told us.', 'Tre settimane di coperture temporanee e cosa ci hanno detto i conteggi.',
        'Hands joined together in a circle on the grass', 'Mani unite in cerchio sul prato'],
    [6, '2026-07-30', 1, 'The first progress report is out', 'È uscito il primo rapporto di avanzamento',
        'What we did in the first ten months, in twelve readable pages.', 'Cosa abbiamo fatto nei primi dieci mesi, in dodici pagine leggibili.',
        'Documents and a cup of coffee on a desk', 'Documenti e una tazza di caffè su una scrivania'],
];
foreach ($news as [$i, $date, $cat, $ten, $tit, $exen, $exit, $alten, $altit]) {
    $entries[] = ['type' => 'news', 'slug' => 'news-' . $i, 'pub_date' => $date, 'image' => "seed-media/news-$i.webp", 'meta' => ['category' => $cat], 'tr' => [
        'en' => ['title' => $ten, 'excerpt' => $exen, 'image_alt' => $alten, 'slug' => '',
            'body' => $p($exen . ' This is demonstration content: the project and the people are invented, but the format is the one a real project would use.',
                'Each news item has a date, a category, a photo with its description and a body text that can contain headings, lists, links and quotes.')
                . '<h2>What happens next</h2>' . $p('The next step is planned for the coming weeks. Follow the news section or write to us to know more.')],
        'it' => ['title' => $tit, 'excerpt' => $exit, 'image_alt' => $altit,
            'body' => $p($exit . ' Questo è un contenuto dimostrativo: progetto e persone sono inventati, ma la forma è quella di un progetto vero.',
                'Ogni news ha una data, una categoria, una foto con la sua descrizione e un testo che può contenere titoli, elenchi, link e citazioni.')
                . '<h2>Cosa succede ora</h2>' . $p('Il prossimo passo è previsto per le prossime settimane. Seguite le notizie o scriveteci per saperne di più.')],
    ]];
}

// ---------- storie ----------
$stories = [
    [1, 'Lena', 'Germany', 'Participant, 19', 'Partecipante, 19 anni', 'I had never been inside the town hall. Now I know the name of the person who decides where the benches go.', 'Non ero mai entrata in municipio. Ora so come si chiama chi decide dove vanno le panchine.'],
    [2, 'Marco', 'Italy', 'Youth worker', 'Educatore', 'The kids saw things in the square that we adults had stopped seeing years ago.', 'I ragazzi hanno visto nella piazza cose che noi adulti non vedevamo più da anni.'],
    [3, 'Ana', 'Slovenia', 'Resident, 63', 'Residente, 63 anni', 'They asked me where I sit in the afternoon. Nobody had ever asked me that.', 'Mi hanno chiesto dove mi siedo il pomeriggio. Nessuno me l’aveva mai chiesto.'],
];
foreach ($stories as [$i, $name, $country, $ren, $rit, $qen, $qit]) {
    $entries[] = ['type' => 'story', 'slug' => strtolower($name), 'menu_order' => $i, 'pub_date' => '2026-10-0' . $i, 'image' => "seed-media/storia-$i.webp", 'meta' => ['country' => $country], 'tr' => [
        'en' => ['title' => $name, 'excerpt' => $qen, 'image_alt' => "Portrait of $name", 'extra' => ['role' => $ren],
            'body' => $p("$name took part in the first season of local labs. This is a demonstration story: the person is invented.", 'A story page can hold a longer interview, a short video and the links to share it.')],
        'it' => ['title' => $name, 'excerpt' => $qit, 'image_alt' => "Ritratto di $name", 'extra' => ['role' => $rit],
            'body' => $p("$name ha partecipato alla prima stagione dei cantieri. Questa è una storia dimostrativa: la persona è inventata.", 'Una storia può contenere un’intervista più lunga, un breve video e i link per condividerla.')],
    ]];
}

// ---------- partner (inventati) ----------
$partners = [
    ['open-squares', 'Open Squares Network', 'OSN', 'Italy', 'Coordinator', 'Network of youth associations working on public space in central Italy.', 'Rete di associazioni giovanili che lavorano sugli spazi pubblici nell’Italia centrale.'],
    ['stadtlabor', 'Stadtlabor Nord', 'SLN', 'Germany', 'Partner', 'Urban research lab with ten years of participatory design projects.', 'Laboratorio di ricerca urbana con dieci anni di progetti di progettazione partecipata.'],
    ['zavod-trg', 'Zavod Trg', 'ZT', 'Slovenia', 'Partner', 'Non-profit that runs community spaces in small Slovenian towns.', 'Ente non profit che gestisce spazi di comunità nei piccoli comuni sloveni.'],
    ['municipio-valle', 'Comune di Valle Esempio', 'CVE', 'Italy', 'Partner', 'Small municipality hosting the first local lab.', 'Piccolo comune che ospita il primo cantiere locale.'],
    ['pla-ca', 'Plaça Comuna', 'PC', 'Spain', 'Partner', 'Cooperative of architects and educators based in Catalonia.', 'Cooperativa di architetti ed educatori con sede in Catalogna.'],
];
foreach ($partners as $i => [$slug, $name, $short, $country, $role, $aen, $ait]) {
    $entries[] = ['type' => 'partner', 'slug' => $slug, 'menu_order' => $i + 1, 'image' => "seed-media/partner-$slug.svg",
        'meta' => ['country' => $country, 'short' => $short, 'role' => $role, 'url' => 'https://example.org/'], 'tr' => [
        'en' => ['title' => $name, 'excerpt' => $aen, 'image_alt' => "$name logo"],
        'it' => ['title' => $name, 'excerpt' => $ait, 'image_alt' => "Logo di $name"],
    ]];
}

// ---------- documenti (senza file) ----------
$docs = [
    ['D1.1', 'deliverable', 'WP1', '2026-07-31', 'Progress report, first ten months', 'Rapporto di avanzamento, primi dieci mesi'],
    ['D2.1', 'toolkit', 'WP2', '2026-11-30', 'Listening walks: a field guide', 'Passeggiate di ascolto: guida pratica'],
    ['D3.2', 'report', 'WP3', '2027-03-31', 'Six squares: what changed', 'Sei piazze: cosa è cambiato'],
    ['PR-01', 'press', 'WP5', '2026-10-15', 'Press release: Common Ground starts', 'Comunicato stampa: parte Common Ground'],
];
$docSeed = [];
foreach ($docs as $i => [$code, $cat, $wp, $date, $ten, $tit]) {
    $docSeed[] = ['code' => $code, 'category' => $cat, 'wp' => $wp, 'pub_date' => $date, 'sort' => $i, 'tr' => [
        'en' => ['title' => $ten, 'excerpt' => 'Demonstration document: no file attached yet.'],
        'it' => ['title' => $tit, 'excerpt' => 'Documento dimostrativo: nessun file ancora allegato.'],
    ]];
}

return ['entries' => array_map($auto, $entries), 'docs' => $docSeed];
