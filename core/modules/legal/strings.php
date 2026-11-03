<?php
/* Testi del modulo legale. Si traducono come gli altri da Gestione → Testi del sito / Traduzioni.
   Segnaposto: {site} {controller} {address} {email} {dpo} {hosting} {updated} {services} {privacy_link} {cookie_link}
   {prefs_button} {status} {issues} {a11y_email} {a11y_date} */
return [
    'legal_privacy_title' => ['group' => 'Legale', 'text' => ['en' => 'Privacy policy', 'it' => 'Privacy policy']],
    'legal_cookies_title' => ['group' => 'Legale', 'text' => ['en' => 'Cookie policy', 'it' => 'Cookie policy']],
    'legal_a11y_title' => ['group' => 'Legale', 'text' => ['en' => 'Accessibility statement', 'it' => 'Dichiarazione di accessibilità']],
    'legal_updated' => ['group' => 'Legale', 'text' => ['en' => 'Last updated: {updated}', 'it' => 'Ultimo aggiornamento: {updated}']],

    'legal_privacy_body' => ['group' => 'Legale', 'html' => 'block', 'text' => [
        'en' => '<h2>Data controller</h2><p>{controller}, {address}. Contact: {email}. Data protection officer: {dpo}.</p>'
            . '<h2>What data we process</h2><p><strong>Browsing data.</strong> The web server that hosts {site} ({hosting}) automatically records technical data needed to deliver the pages and keep them secure, such as IP address, date and time, the page requested and the browser used. We use them only for security and to fix problems, and they are deleted by the hosting provider after a short period.</p>'
            . '{stats}'
            . '<p><strong>Content from other services.</strong> Some pages include videos hosted by external services (for example YouTube). They load only if you give your consent or press play, and from that moment the external service processes your data under its own privacy policy. Details are in the {cookie_link}.</p>'
            . '<p><strong>When you write to us.</strong> If you send us an email, we use your address and the content of your message only to reply.</p>'
            . '<h2>Legal basis</h2><ul><li>Security and technical operation of the site: legitimate interest (Art. 6(1)(f) GDPR).</li><li>External content such as videos: your consent (Art. 6(1)(a) GDPR), which you can withdraw at any time.</li><li>Replies to your messages: your request (Art. 6(1)(b) GDPR).</li></ul>'
            . '<h2>Who receives the data</h2><p>The hosting provider, as data processor, and the external services you choose to activate. Some of them, such as Google for YouTube, may transfer data outside the European Union on the basis of the EU–US Data Privacy Framework or standard contractual clauses.</p>'
            . '<h2>Your rights</h2><p>You can ask to access, correct or delete your data, to restrict or object to its processing and to receive it in a portable format (Articles 15–22 GDPR) by writing to {email}. You also have the right to lodge a complaint with your data protection authority.</p>',
        'it' => '<h2>Titolare del trattamento</h2><p>{controller}, {address}. Contatto: {email}. Responsabile della protezione dei dati: {dpo}.</p>'
            . '<h2>Quali dati trattiamo</h2><p><strong>Dati di navigazione.</strong> Il server che ospita {site} ({hosting}) registra in automatico i dati tecnici necessari per mostrare le pagine e tenerle sicure, come indirizzo IP, data e ora, pagina richiesta e browser usato. Li usiamo solo per la sicurezza e per risolvere problemi; il fornitore di hosting li cancella dopo un breve periodo.</p>'
            . '{stats}'
            . '<p><strong>Contenuti di altri servizi.</strong> Alcune pagine contengono video ospitati da servizi esterni (per esempio YouTube). Si caricano solo se dai il consenso o premi play, e da quel momento il servizio esterno tratta i tuoi dati secondo la propria privacy policy. I dettagli sono nella {cookie_link}.</p>'
            . '<p><strong>Quando ci scrivi.</strong> Se ci mandi un’email usiamo il tuo indirizzo e il contenuto del messaggio solo per risponderti.</p>'
            . '<h2>Base giuridica</h2><ul><li>Sicurezza e funzionamento tecnico del sito: legittimo interesse (art. 6, par. 1, lett. f GDPR).</li><li>Contenuti esterni come i video: il tuo consenso (art. 6, par. 1, lett. a GDPR), che puoi revocare in qualsiasi momento.</li><li>Risposte ai tuoi messaggi: la tua richiesta (art. 6, par. 1, lett. b GDPR).</li></ul>'
            . '<h2>A chi arrivano i dati</h2><p>Al fornitore di hosting, come responsabile del trattamento, e ai servizi esterni che scegli di attivare. Alcuni di questi, come Google per YouTube, possono trasferire dati fuori dall’Unione europea sulla base del Data Privacy Framework UE–USA o delle clausole contrattuali standard.</p>'
            . '<h2>I tuoi diritti</h2><p>Puoi chiedere di accedere ai tuoi dati, correggerli o cancellarli, limitarne o opporti al trattamento e riceverli in un formato portabile (artt. 15–22 GDPR) scrivendo a {email}. Hai anche il diritto di presentare reclamo all’autorità di controllo, in Italia il Garante per la protezione dei dati personali.</p>',
    ]],

    'legal_cookies_body' => ['group' => 'Legale', 'html' => 'block', 'text' => [
        'en' => '<h2>Our cookies</h2><p>{site} does not set any cookie for visitors. The only cookie is a technical session cookie used by the site editors when signing in to the content management area; it is never set on public pages.</p>'
            . '<p>To remember your choice about external content, your browser stores a small preference (“pp-consent”) in its local storage. It contains no personal data and is never sent to us. After 6 months we ask you again.</p>'
            . '<h2>External services</h2><p>These services are embedded in some pages. They load, and may set their own cookies, only after your consent:</p>{services}'
            . '<h2>Change your choice</h2><p>You can change or withdraw your consent at any time.</p><p>{prefs_button}</p><p>You can also delete cookies and site data from your browser settings. More about how we process data in the {privacy_link}.</p>',
        'it' => '<h2>I nostri cookie</h2><p>{site} non installa nessun cookie ai visitatori. L’unico cookie è un cookie tecnico di sessione usato da chi cura il sito per accedere all’area di gestione dei contenuti; non viene mai impostato sulle pagine pubbliche.</p>'
            . '<p>Per ricordare la tua scelta sui contenuti esterni, il browser salva una piccola preferenza (“pp-consent”) nella sua memoria locale. Non contiene dati personali e non ci viene mai inviata. Dopo 6 mesi te lo chiediamo di nuovo.</p>'
            . '<h2>Servizi esterni</h2><p>Questi servizi sono incorporati in alcune pagine. Si caricano, e possono installare i loro cookie, solo dopo il tuo consenso:</p>{services}'
            . '<h2>Cambiare la scelta</h2><p>Puoi cambiare o revocare il consenso in qualsiasi momento.</p><p>{prefs_button}</p><p>Puoi anche cancellare cookie e dati dei siti dalle impostazioni del browser. Come trattiamo i dati è spiegato nella {privacy_link}.</p>',
    ]],

    'legal_a11y_body' => ['group' => 'Legale', 'html' => 'block', 'text' => [
        'en' => '<p>{controller} is committed to making {site} accessible, in line with the Web Content Accessibility Guidelines (WCAG) 2.1 level AA and the European standard EN 301 549.</p>'
            . '<h2>Compliance status</h2><p>{site} is <strong>{status}</strong> with WCAG 2.1 level AA.</p>'
            . '<h2>Content that is not accessible</h2>{issues}'
            . '<h2>How we checked</h2><p>Self-assessment by the team that built the site, with automatic checks on contrast, headings, alternative texts and documents, and manual tests with keyboard and screen reader. Last review: {a11y_date}.</p>'
            . '<h2>Feedback and contact</h2><p>If you find a barrier or need a document in an accessible format, write to {a11y_email}. We answer within 30 days.</p>',
        'it' => '<p>{controller} si impegna a rendere {site} accessibile, secondo le Linee guida per l’accessibilità dei contenuti web (WCAG) 2.1 livello AA e la norma europea EN 301 549.</p>'
            . '<h2>Stato di conformità</h2><p>{site} è <strong>{status}</strong> alle WCAG 2.1 livello AA.</p>'
            . '<h2>Contenuti non accessibili</h2>{issues}'
            . '<h2>Come abbiamo verificato</h2><p>Autovalutazione da parte del gruppo che ha realizzato il sito, con controlli automatici su contrasto, titoli, testi alternativi e documenti, e prove manuali con tastiera e lettore di schermo. Ultima revisione: {a11y_date}.</p>'
            . '<h2>Segnalazioni e contatti</h2><p>Se trovi una barriera o ti serve un documento in formato accessibile, scrivi a {a11y_email}. Rispondiamo entro 30 giorni.</p>',
    ]],
    'a11y_full' => ['group' => 'Legale', 'text' => ['en' => 'fully compliant', 'it' => 'conforme']],
    'a11y_partial' => ['group' => 'Legale', 'text' => ['en' => 'partially compliant', 'it' => 'parzialmente conforme']],
    'a11y_none' => ['group' => 'Legale', 'text' => ['en' => 'not compliant', 'it' => 'non conforme']],
    'a11y_issues' => ['group' => 'Legale', 'note' => 'Barriere note, una per riga', 'text' => ['en' => '', 'it' => '']],
    'a11y_pdf_issue' => ['group' => 'Legale', 'text' => ['en' => '{n} downloadable PDF documents may not be fully accessible: ask us for an accessible version.', 'it' => '{n} documenti PDF scaricabili potrebbero non essere del tutto accessibili: chiedici una versione accessibile.']],
    'a11y_no_issues' => ['group' => 'Legale', 'text' => ['en' => 'No known barriers at the date of the last review.', 'it' => 'Nessuna barriera nota alla data dell’ultima revisione.']],

    'cookie_none' => ['group' => 'Cookie', 'text' => ['en' => 'At the moment no external service is embedded in the site.', 'it' => 'Al momento nel sito non è incorporato nessun servizio esterno.']],
    'cookie_th_service' => ['group' => 'Cookie', 'text' => ['en' => 'Service', 'it' => 'Servizio']],
    'cookie_th_purpose' => ['group' => 'Cookie', 'text' => ['en' => 'Purpose', 'it' => 'Finalità']],
    'cookie_purpose_video' => ['group' => 'Cookie', 'text' => ['en' => 'Video playback; the provider may use cookies to remember preferences and measure views.', 'it' => 'Riproduzione dei video; il fornitore può usare cookie per ricordare le preferenze e misurare le visualizzazioni.']],
    'cookie_title' => ['group' => 'Cookie', 'text' => ['en' => 'External content', 'it' => 'Contenuti esterni']],
    'cookie_text' => ['group' => 'Cookie', 'text' => ['en' => 'This site uses no tracking cookies. Some pages include videos from external services that set their own cookies: they load only if you agree.', 'it' => 'Questo sito non usa cookie di tracciamento. Alcune pagine contengono video di servizi esterni che usano i propri cookie: si caricano solo se sei d’accordo.']],
    'cookie_accept' => ['group' => 'Cookie', 'text' => ['en' => 'Accept', 'it' => 'Accetta']],
    'cookie_reject' => ['group' => 'Cookie', 'text' => ['en' => 'Reject', 'it' => 'Rifiuta']],
    'cookie_customize' => ['group' => 'Cookie', 'text' => ['en' => 'Choose', 'it' => 'Scegli']],
    'cookie_save' => ['group' => 'Cookie', 'text' => ['en' => 'Save choices', 'it' => 'Salva le scelte']],
    'cookie_close' => ['group' => 'Cookie', 'text' => ['en' => 'Close and reject', 'it' => 'Chiudi e rifiuta']],
    'cookie_prefs' => ['group' => 'Cookie', 'text' => ['en' => 'Cookie preferences', 'it' => 'Preferenze cookie']],
    'cookie_policy_link' => ['group' => 'Cookie', 'text' => ['en' => 'Cookie policy', 'it' => 'Cookie policy']],
    'video_consent' => ['group' => 'Cookie', 'text' => ['en' => 'This video is hosted by {service}, which may set cookies.', 'it' => 'Questo video è ospitato da {service}, che può usare cookie.']],
    'map_consent' => ['group' => 'Cookie', 'text' => ['en' => 'This map is loaded from {service}, which receives your IP address.', 'it' => 'Questa mappa si carica da {service}, che riceve il tuo indirizzo IP.']],
    'map_accept' => ['group' => 'Cookie', 'text' => ['en' => 'Accept and show the map', 'it' => 'Accetta e mostra la mappa']],
    'map_external' => ['group' => 'Cookie', 'text' => ['en' => 'Open the map on {service}', 'it' => 'Apri la mappa su {service}']],
    'video_accept' => ['group' => 'Cookie', 'text' => ['en' => 'Accept and play', 'it' => 'Accetta e guarda']],
    'video_external' => ['group' => 'Cookie', 'text' => ['en' => 'Watch on {service}', 'it' => 'Guarda su {service}']],
    // statistiche delle visite ({stats} nella privacy policy): il paragrafo cambia con quelle scelte in Privacy e cookie
    'legal_stats_none' => ['group' => 'Legale', 'html' => 'block', 'text' => [
        'en' => '<p><strong>No profiling and no statistics.</strong> {site} does not use analytics, advertising or profiling cookies, does not load fonts or scripts from third parties and does not track you across sites.</p>',
        'it' => '<p><strong>Nessuna profilazione e nessuna statistica.</strong> {site} non usa cookie di analisi, pubblicitari o di profilazione, non carica font o script di terze parti e non ti traccia su altri siti.</p>']],
    'legal_stats_on' => ['group' => 'Legale', 'html' => 'block', 'text' => [
        'en' => '<p><strong>Visit statistics.</strong> To understand which pages are useful, we count visits with:</p>{tools}<p>{site} does not use advertising or profiling cookies and does not track you across sites.</p>',
        'it' => '<p><strong>Statistiche delle visite.</strong> Per capire quali pagine sono utili contiamo le visite con:</p>{tools}<p>{site} non usa cookie pubblicitari o di profilazione e non ti traccia su altri siti.</p>']],
    'legal_stats_matomo' => ['group' => 'Legale', 'html' => 'inline', 'text' => [
        'en' => 'Matomo, on {host}: without cookies and with shortened IP addresses, so the statistics do not identify you (legitimate interest, Art. 6(1)(f) GDPR).',
        'it' => 'Matomo, su {host}: senza cookie e con l’indirizzo IP accorciato, così le statistiche non ti identificano (legittimo interesse, art. 6, par. 1, lett. f GDPR).']],
    'legal_stats_plausible' => ['group' => 'Legale', 'html' => 'inline', 'text' => [
        'en' => 'Plausible Analytics (Plausible Insights OÜ, European Union): without cookies and without storing personal data (legitimate interest, Art. 6(1)(f) GDPR).',
        'it' => 'Plausible Analytics (Plausible Insights OÜ, Unione europea): senza cookie e senza conservare dati personali (legittimo interesse, art. 6, par. 1, lett. f GDPR).']],
    'legal_stats_ga4' => ['group' => 'Legale', 'html' => 'inline', 'text' => [
        'en' => 'Google Analytics 4 (Google Ireland Ltd), only if you give your consent (Art. 6(1)(a) GDPR): it sets its own cookies and Google may transfer data outside the European Union under the EU–US Data Privacy Framework. Details in the {cookie_link}.',
        'it' => 'Google Analytics 4 (Google Ireland Ltd), solo se dai il consenso (art. 6, par. 1, lett. a GDPR): installa i suoi cookie e Google può trasferire dati fuori dall’Unione europea sulla base del Data Privacy Framework UE–USA. I dettagli sono nella {cookie_link}.']],
    'cookie_purpose_stats' => ['group' => 'Cookie', 'text' => ['en' => 'Visit statistics: which pages are read and how people arrive, in aggregate form.', 'it' => 'Statistiche delle visite: quali pagine si leggono e da dove si arriva, in forma aggregata.']],
    'cookie_title_stats' => ['group' => 'Cookie', 'text' => ['en' => 'Cookies and external content', 'it' => 'Cookie e contenuti esterni']],
    'cookie_text_stats' => ['group' => 'Cookie', 'text' => ['en' => 'With your consent we measure visits with Google Analytics, and some pages include videos from external services. Both set their own cookies: they load only if you agree.', 'it' => 'Con il tuo consenso misuriamo le visite con Google Analytics, e alcune pagine contengono video di servizi esterni. Entrambi usano i propri cookie: si caricano solo se sei d’accordo.']],
];
