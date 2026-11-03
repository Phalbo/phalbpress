# PhalbPress — guida per Claude

CMS leggero in PHP 8.1+ per siti di progetto multilingua (progetti europei, ONG). Autore: Andrea Falbo (Ares 2.0).
Hosting tipico: SiteGround (Apache + PHP-FPM + MySQL/MariaDB). Nessuna dipendenza esterna: niente Composer, niente npm a runtime, niente CDN.
Rispondi ad Andrea in italiano, diretto, con codice completo e pronto.

## Principi (non negoziabili)
- **Core e sito separati.** `core/`, `pp-admin/`, `pp-assets/`, `pp-themes/`, `index.php`, `install.php`, `cron.php` sono il motore e si aggiornano sostituendoli.
  Tutto ciò che riguarda un progetto sta in `site/` (config, testi, contenuti iniziali, personalizzazioni del tema, moduli del sito). Mai codice di progetto nel core.
- **Sicurezza prima della comodità.** PDO con prepared statements, `e()` su ogni output, CSRF su ogni POST, HTML dell'editor passato da `Sanitizer`,
  upload controllati (`Media`), nessuno script esterno (CSP `script-src 'self'`: niente JS inline nei template, dati via attributi `data-*`).
- **Sito pubblico senza cookie** (il cookie di sessione vale solo sotto `/<admin_slug>/`). Servizi esterni (YouTube, Vimeo) solo con consenso.
- **Accessibilità WCAG 2.1 AA**: `lang`, focus visibile, tastiera, alt text, contrasto, `prefers-reduced-motion`, tutto usabile senza JS.
- **Ogni testo visibile è traducibile.** Mai stringhe fisse nei template: `t('chiave')`, chiave dichiarata in `site/strings.php`,
  in `pp-themes/_base/strings.php` (testi comuni dei temi) o nel filtro `strings` di un modulo.

## Struttura
```
index.php            ingresso unico; in sviluppo: php -S localhost:8000 index.php
install.php          senza site/config.php → core/Setup.php (controllo server, zip del sito o sito nuovo, database → scrive config.php);
                     poi tabelle, contenuti (progetto / esempio / modello / vuoto), segnaposto, primo admin, cose da fare dopo; si blocca se esiste un utente
cron.php             solo CLI: contenuti programmati → rigenera lo statico (Scheduler::tick), rigenerazioni rimaste indietro (StaticSite::pending), poi hook `cron`
core/PP.php          config, URL, sezioni per lingua (PP::section), router (run → page → resolve), temi (themeFile/asset), render, shortcode, helper
core/DB.php          PDO MySQL/SQLite, migrate(), addColumn(), t() tipi portabili
core/Content.php     entry e doc traducibili, slug per lingua, revisioni, duplica, programmazione (live()), classe Redirects
core/Admin.php       gestione: rotte, permessi per ruolo, editor contenuti (campi personalizzati), media, utenti, password dimenticata, anteprima
core/Auth.php        sessione, login, TOTP, ruoli (can/canLang/canEdit), CSRF, log
core/Media.php       upload (nomi puliti, WebP, -960, SVG ripulito, PDF), replace() stesso percorso, usage() dove è usato
core/Scheduler.php   contenuti programmati
core/Translator.php  traduzione automatica (Anthropic / DeepL), anche i campi personalizzati traducibili
core/modules/        identity, seo, ai, legal, static, menus, redirects, search, forms (+ newsletter), backup, activity, themes, updater, settings,
                     events (tipo `event`, sezione eventi, file .ics; `'events' => false` nel config lo spegne), feed (RSS `/feed/{lang}.xml`),
                     wpimport (importazione da WordPress: vedi sotto)
                     (settings: contact_email, mail_from, social, funder, owner, translation modificabili in gestione, applicati sopra il config in PP::boot;
                     owner = organizzazione o persona per i dati strutturati, `Seo::owner()`: Person + ProfilePage per i siti personali)
pp-themes/_base/     struttura comune a tutti i temi: templates/, partials/ (+ blocks/), assets/base.css, base.js, strings.php
pp-themes/<tema>/    theme.json + assets/theme.css (+ theme.js, + template/partial che sostituisce) — 16 temi (Istituzionale e Minimalista tolti nella 1.16: un tema che non c'è più ripiega su tipografico in `PP::theme()`)
site/config.php      scritto dall'installazione: `array_replace(require config.sample.php, [db, admin_slug, site_url, debug])`
site/strings.php     testi del progetto; site/seed.php contenuti iniziali; site/seed-media/ immagini del seed
site/theme/          SOLO personalizzazioni del sito valide con ogni tema (es. partials/footer-extra.php con emblema UE)
site/modules/        moduli solo di questo sito ("_" davanti = disattivato; _esempio è il modello)
uploads/ data/       file caricati; SQLite, manifest statico, backups/
```

## Modello dati
- `pp_entries` (type, slug base, template, menu_order, in_menu, status, pub_date, publish_at, unpublish_at, author_id, image, meta JSON)
  + `pp_entry_tr` (lang, title, excerpt, body, image_alt, **slug** per lingua, **extra** JSON = campi traducibili, src_hash, auto).
- Slug: ogni lingua ha il suo (`Content::slugFor`), nasce dal titolo tradotto; se cambia su un contenuto pubblicato nasce un redirect 301 automatico.
  Per le pagine sono riservati (`Content::uniqueSlug`) gli indirizzi delle sezioni e dei moduli (news, portfolio, eventi, pagine legali, ricerca), `provini` e i file generati: una pagina con quello slug sarebbe irraggiungibile.
- Campi personalizzati in `config['types'][tipo]['fields']`: `'k' => 'Etichetta'` o `['label','type'=>text|textarea|number|date|url|select|image|checkbox|color|entry|entries,
  'options','entry_type','translatable','hint']`. Non traducibili → `meta`; traducibili → `pp_entry_tr.extra` (nel template: `$e['x']['k']`).
- `pp_revisions` (ultime N versioni per contenuto e lingua, `config['revisions_keep']`), `pp_redirects`, `pp_tokens` (reset password), `pp_form_entries`.
- `pp_docs` + `pp_doc_tr` + `pp_doc_files`; `pp_strings`; `pp_seo`; `pp_settings` (theme, menus, identity, legal…); `pp_users` (role, langs, totp_secret); `pp_log`.
- **Stato traduzioni**: `src_hash` = `Content::hashOf` (title, excerpt, body, + image_alt ed extra solo se compilati). Non cambiare la formula.

## Ruoli
admin (tutto) · editor (contenuti, pubblicazione, menu, testi, documenti, redirect, richieste) · author (solo i propri contenuti, in bozza) ·
translator (solo le lingue in `users.langs`). Controlli: `Auth::can('admin'|'site'|'publish'|'content'|'translate')`, `Auth::canLang()`, `Auth::canEdit($row)`.
Pagine di modulo: chiave `perm` in `admin.pages` (default `site`; `admin_only` = `admin`).

## Funzioni comuni (in _base, valgono con ogni tema)
- **Galleria nel testo**: shortcode `[gallery:percorso=alt|percorso=alt]` (pulsante "Galleria" nell'editor) → `PP::gallery()` → `partials/gallery.php`.
- **Campo Galleria** (1.18, tipo di campo `gallery`, es. `'gallery' => ['label' => 'Galleria', 'type' => 'gallery']` nei campi di `story`): stesso formato del codice (`Media::gallery()` normalizza; in gestione una riga per foto `percorso | descrizione`),
  nella colonna principale dell'editor; con JS anteprime da trascinare, descrizione per foto, foto dal computer caricate subito con `POST /{admin}/api/upload` (permesso `content`); senza JS file multipli `gallery_<campo>[]` aggiunti in fondo.
  `story.php` la stampa dopo il testo. L'importazione da WordPress (`WpImport::intoFields()`) sposta lì i `[gallery:…]` del testo e il primo `[video:…]` nel campo `video` se il tipo li ha.
- **Lightbox**: `<dialog class="pp-lightbox">` in base.js per gallerie, `[data-lightbox]` e immagini della `.prose`; frecce, tastiera, swipe, testi da `data-ui` del body.
- **Punto focale**: meta `image_focus` (top|bottom|left|right) → `focus_class($e)` = classe `pp-focus--X` su card, intestazione, mosaico.
- **Pagine madri**: meta `parent` (id) o `parent_slug` nel seed → `page_parent($e)`; percorso sopra il titolo ed elenco `.children` (parte `list`) nella madre.
- **Social**: `partials/social.php` (icone SVG dal nome o dal dominio); `social_place` = footer|header|both|toolbar|toolbar_footer|none nelle Impostazioni.
- **Barra in testata e footer** (Impostazioni, chiavi del config): `toolbar` (barra sottile con telefoni, email, social), `company`, `phone`, `phone2`, `pec`, `vat`,
  `offices` (blocchi separati da una riga vuota, `SiteSettings::offices()`), `footer_badge` (immagine, es. certificazioni). Footer: logo per fondi scuri, frase, `footer_note`,
  contatti, menu `footer`, sedi, riga finale `© <span data-year>` + `footer_legal`. L'anno si aggiorna da solo (firma del Scheduler con `date('Y')` + base.js; con eventi pubblicati la firma ha il giorno, così prossimi/passati cambiano a mezzanotte).
  Footer di base scuro: si stampano tutti e due i loghi (`foot__logo-img--night|--day`); i temi col footer chiaro hanno `foot-light` in `body_class`.
- **Sottomenu**: si aprono passandoci sopra (e col clic/tastiera); un terzo livello si apre di lato (`nav__sub--deep`).
  Voce di menu `section` con ref `stories:cats` = Portfolio con le sue categorie (`Stories::menu()`); con `parent=1` una voce va sotto la precedente.
- **Newsletter**: `Forms::render('newsletter')` (email + consenso con data), esportazione CSV in Richieste.
- **Portfolio** (`core/modules/themes/Stories.php`): una storia ha `meta.category` + `meta.categories` (id di contenuti `category`, con `meta.parent` per le sottocategorie).
  Ogni categoria usata ha la pagina `/{lang}/{sezione storie}/{slug}/` (rotta `stories-cat`, template `stories` con `$category`, in `PP::items()` quindi anche statica);
  la disposizione dell'elenco si sceglie per tipo o per categoria (meta `layout_stories`). Dati della scheda: campi del tipo `story` con `'public' => chiave del testo` (`Stories::facts`).
  Categorie: ordine con `menu_order`, `meta.nav_hidden` le toglie da filtri e menu (restano raggiungibili); con aree e divisioni (`Stories::nested()`) in alto ci sono solo le aree.
- **Identità**: `logo_has_name` (il logo contiene già il nome: `.brand__text` diventa `sr-only`); `logo_h` (altezza del logo in px), `photo_fx` (1.18: ''=a colori|soft=velo tenue dell'accento|theme=come il tema|mono|tint; i vecchi `none` = a colori.
  Fuori da `theme` le foto degli elenchi e quelle aperte – `.storypage__pic`, `.article__img`, `.prose` – non prendono filtri del tema; il velo non tocca i mosaici, dove `::after` è la sfumatura del titolo),
  `photo_ratio` (1.18: ''=quadrate|layout|4:3|3:2|16:9|3:4|auto, per card/storie/news degli elenchi tranne mosaici e indice; `img()` mette `pp-tall` (l/a < 0,8) o `pp-wide` (> 1,9) e quelle restano intere su fondo tenue, `pp-crop` da meta `image_fit`=cover le riempie),
  `photo_hover` (color|zoom|none), `band_h` (compact|tall: altezza delle intestazioni). Scritte in `identity.css` da `Identity::extraCss()`.
  1.16 (`Identity::layoutCss()`): `logo_head` (''|day|night), `space` (''|compact|wide → `--section`), `boxed`, `hero_shade`/`band_shade` (0-80, velo in `.pp-shade`
  dentro la figura), `hero_text`/`band_text` (shadow|panel), `hero_fx` (color|mono), `band_fx` (color|mono|tint, `.pp-tint`).
- **Logo in testata**: con il logo per fondi scuri si stampano `brand__logo--day` e `--night` (`.brand--duo`); base.js mette `body.head-dark` se la testata è scura
  (fondo opaco, o colore del testo se la testata è trasparente sopra una foto). Senza logo chiaro: fondino rettangolare. Mai ovali nei temi.
  Con la barra dei contatti, una testata `fixed`/`absolute` parte sotto la barra (base.js).
- **Varianti di colore**: `palettes` nel theme.json (`[{name, note, palette, fonts, buttons}]`), applicate da Identità visiva (azione `variant`, ripristino `variant_undo`),
  anteprima `/{admin}/preview?theme=x&variant=N`. Dalla 1.18 ogni tema ne ha almeno quattro (Originale + tre), tutte con `Identity::checks()` senza errori.
- **Colori personali** (1.18, `core/modules/identity/Palette.php`): OKLCH; `Palette::derive($base, $primary, $secondary, $tertiary, $mood, $heroOn, $accentText)` tiene luminosità e intensità di ogni ruolo del tema
  e cambia la tinta (accento = colore principale; campo 1/2 = secondo/terzo o tinte dal carattere `sobrio|armonico|vivace|triade|istituzionale`; neutri restano neutri; fondi molto scuri o chiari prendono la tinta principale;
  ruoli uguali nel tema restano uguali), poi `Palette::fix()` muove solo la luminosità finché i contrasti passano. Scelta in `identity_personal` (con i colori applicati): `Palette::onThemeChange()` (Temi → attiva)
  la ricalcola se i colori del sito sono ancora quelli. Gestione: azioni `personal` e `personal_json` (anteprima dal vivo), anteprima `/{admin}/preview?p1=#..&use2=1&p2=..&use3=1&p3=..&mood=..`.
  Le varianti dei 15 temi sono nate da qui (primario e carattere scelti per tema). `accent_text: false` nel theme.json = l'accento si usa solo come fondo (manifesto): niente controllo 3:1 sullo sfondo.
- **Provini** (`core/modules/themes/Proofs.php`, pannello in Temi): `Proofs::build($palette, $only)` scrive `/provini/<tema>/…` (pagine di `PP::items()` tranne
  i lavori oltre i 24 più recenti e le news oltre le 10 più recenti, più i lavori scelti nelle fasce), link interni riscritti, `noindex`, indice `/provini/index.html`;
  manifesto in `data/provini.json`, `Proofs::clear()`. `provini` è riservato nel sito statico.
- **Campi `entries`** (più contenuti in ordine, id separati da virgola): ricerca e riordino in pp-admin/admin.js; i `select` di tipo `entry` con più di 12 voci hanno la casella di ricerca (`data-search`).
- **Contatti**: `[form:contact]` dentro `.contact-wrap` con i recapiti delle Impostazioni accanto (`Forms::contacts()`).
  Antispam (1.17): il browser chiede un gettone a `GET /form/{nome}/key` all'apertura della pagina (`Forms::key()`, HMAC di nome e ora con la chiave `secret`; vale anche col sito statico),
  il server accetta l'invio da 3 secondi a 4 ore dopo il rilascio; campo trappola `website`; 5 invii l'ora per impronta dell'indirizzo (colonna `pp_form_entries.ip`, indicizzata).
  Id per modulo: `Forms::anchor($form)` = `pp-form` per il contatto, `pp-form-<nome>` per gli altri; l'esito torna in `#<ancora>-ok|err`.
- **Fasce nelle pagine**: un blocco col campo `page` (id) o `page_slug` (nel seed: la pagina può arrivare dopo, es. con l'importazione) va in fondo a quella pagina
  (`Blocks::forPage()`, in `templates/page.php`) e non in home. `Blocks::bindPages()` (hook `saved`) trasforma `page_slug` in `page` quando la pagina esiste.
- **Anteprime social** (1.18): `Media::share($img)` → `uploads/share/<hash>.jpg` 1200×630 (cover se l/a fra 1,45 e 2,6, altrimenti intera su 12% dell'accento; si rifà se cambiano foto o accento;
  al massimo 25 nuove per richiesta, poi la foto com'è); `Seo::head()` scrive `og:image:width/height/alt` (alt da `$ogAlt` dei template, se no il titolo). Con `search_hidden` niente OG.
- **Statistiche** (1.18, `core/modules/legal/Stats.php`, Privacy e cookie): impostazione `stats` (`ga4`, `matomo_url` + `matomo_id`, `plausible` + `plausible_host`). Plausible con il suo script,
  Matomo (`disableCookies`) e GA4 da `pp-assets/stats.js` (configurazione negli attributi `data-`, GA4 solo con `ppConsent.has('ga4')` e l'evento `pp-consent`). GA4 è un servizio di `cookie_services`
  sempre trovato dalla scansione (banner con `cookie_title_stats`/`cookie_text_stats`); la privacy ha `{stats}` (`Stats::privacy()`; nei testi salvati prima il paragrafo "nessuna statistica" viene sostituito).
  Mai Meta/Facebook. Niente statistiche nell'anteprima.
- **CSP** (`PP::csp()`): filtri `csp_frames`, `csp_img`, `csp_scripts`, `csp_connect`. Col sito statico `StaticSite::build()` scrive `<lingua>/.htaccess` con la stessa CSP (vale al posto di quella fissa del `.htaccess` principale).
- **Mappa** (blocco `map`): OpenStreetMap in iframe solo dopo il consenso (`data-pp-embed`, servizio `openstreetmap` in `Legal::services`, `csp_frames`); senza consenso link "Apri su OpenStreetMap".
- **Sito del progetto** (`core/SitePackage.php`): `extract()` scompatta la cartella `site/` da uno zip (anche annidata), mai `config.php`; lo usano l'installazione guidata
  e Aggiornamenti → "Sito del progetto" (`Updater::siteUpload()`: password, copia `sito-*.zip` in data/backups, `extract(..., mirror)`, `followSample()` per riscrivere un config.php
  completo come quello dell'installazione). `'site_upload' => false` lo spegne. Un config.php scritto dall'installazione fa `array_replace(require config.sample.php, [db, admin_slug, site_url, debug])`.
- **Contenuti di partenza**: `core/Starter.php` (`seed()`, `strings()`, `start('seed'|'demo'|'empty')`), usato da install.php e da **Azzera il sito**
  (`Backup::wipe()`: svuota le tabelle tranne l'utente che azzera e le impostazioni `secret`/`module_versions`, poi `Starter::start()`; opzioni backup, keep_uploads, keep_import, keep_backups, keep_users).
  Le foto importate da WordPress sono anche in `data/wp-media-map.json` (`WpImport::saveMediaMap()`): un'importazione col database nuovo le ritrova.
  `Backup::wipe()` tiene anche l'impostazione `site_url`; un modello (`export('template')`) non porta `site_url`, `static_dirty` né la chiave API della traduzione.
- **Sito statico** (`core/modules/static/StaticSite.php`): l'hook `saved` chiama `StaticSite::later()`, che segna `static_dirty` e rigenera a fine richiesta dopo `fastcgi_finish_request()`;
  `pending()` usa il lucchetto `data/static.lock` (chi lo trova occupato lascia il segno, la rigenerazione in corso ripassa una volta); `catchUp()` (gestione) e `cron.php` riprendono quelle interrotte.
  `build()` chiama prima `Content::flush()` (slug e storie letti prima del salvataggio). Per una rigenerazione subito: `StaticSite::build()`.
- **Indirizzo del sito**: `PP::origin()` = `site_url` del config → impostazione `site_url` (salvata da Sistema con "Usa questo indirizzo") → l'Host della richiesta. L'installazione scrive l'indirizzo da cui si installa.
- **Cache per richiesta**: `PP::themes()`, `Content::slugFor()` e `Stories::all()/categories()`; `Content::flush()` le svuota (lo fanno `saveText()`, `StaticSite::build()`, `Demo::use()`).
- **Gestione**: `Admin::pager($items, $per)` (`?p`, `?all`) per elenchi lunghi: Immagini e file, SEO, Redirect, Accessibilità hanno filtri e pagine.
  `search_hidden` (SEO, attivo a ogni installazione): `noindex` in tutte le pagine, robots.txt `Disallow: /`, avviso in cima alla gestione finché non si toglie.

## Importa da WordPress (core/modules/wpimport)
- File in `data/wp-import/`: `.xml` (WXR, obbligatorio), `.sql` (facoltativo: SOLO `icl_translations`, `term_taxonomy`, `redirection_items`; gli `INSERT` devono avere l'elenco delle colonne, formato phpMyAdmin: con `mysqldump`/WP-CLI le tre tabelle vengono ignorate), `.zip` di uploads (facoltativo; le voci devono contenere `uploads/`, cioè va zippata la cartella e non il suo contenuto). Senza zip i file si scaricano dal vecchio sito: `download` è attivo di default, `'download' => false` lo spegne.
- `WpSource` legge (XMLReader, una riga SQL alla volta) e salva il modello in `data/wp-import/model.ser`; `WpContent::html()` converte gli shortcode (liste `WRAP`/`DROP`, il resto tiene il testo);
  `WpImport` lavora a passi `scan → media → terms → entries → links → menu → redirects` con stato in `state.json`, collegamenti in `pp_wp_map` (kind: media|post|entry|term|termgroup).
- Gruppi di traduzione = `trid` di WPML; senza la lingua principale il contenuto diventa bozza. Slug di WordPress mantenuti. Foto con `Media::upload` (stesso trattamento del caricamento).
- Config del sito `wp_import`: `types` (wp → pp), `taxonomies` (`['tax' => ['type','field']]`), `drafts`, `skip` (lista o `'slug' => 'home'|chiave sezione|'/percorso/'|'category:slug'|'page:slug'` per il redirect; vale anche per le traduzioni WPML dello stesso gruppo, `WpImport::skipped()`), `download`, `menu`.
- Filtri: `wp_import_entry` ($data con type, title, excerpt, body, slug, image, image_alt, meta, extra, skip), `wp_import_term` (termine prima del salvataggio), `wp_import_term_url` (→ `['from','to']`), `wp_import_menu_item`.
- CLI: `php cron.php wpimport plan|run|reset|undo` (hook `cli` dei moduli: `Hooks::first('cli', $args)`).
- Ripresa (1.17, `WpImport::prepare()`): se cambia la sorgente delle foto (`sourceSig()`: zip nome/data/peso + `download`) i file mappati a `''` (mancanti) si riprovano e il passo torna a `media`, anche a importazione finita;
  `data/wp-import/media-in-corso.txt` è il segnalibro del file in lavorazione: se PHP muore lì, alla ripresa quel file è segnato non importabile e saltato. `newUrl()` non punta a contenuti in bozza.

## Rotte pubbliche
`/{lang}/` home a blocchi · `/{lang}/{section news}/[slug]` · stories · results · partners · `/{lang}/{slug pagina}/` · legali · ricerca (`/{lang}/cerca/`…)
· `/{lang}/{section events}/[slug]` · `/ics/{lang}/{id}.ics` · `/feed/{lang}.xml`
· `/search/{lang}.json` · `/download/{id}/{lang}` · `POST /form/{nome}` (anche `newsletter`) · sitemap, robots, llms · `/{admin_slug}/…`.
`config['sections']`: stringa o array per lingua. `PP::items()` = tutte le pagine con il percorso per lingua (sitemap, statico, llms); i moduli aggiungono col filtro `paths`.
404 → `Redirects::find($path, $query)` prima di mostrare la pagina d'errore (funziona anche col sito statico). I redirect possono avere i parametri dei vecchi siti
(`Redirects::QUERY_KEYS`: lang, p, page_id, post_type), es. `/chi-siamo/?lang=en`; `/?lang=xx` porta alla home in quella lingua.

## Temi
- Catena dei file: `site/theme/<file>` → `pp-themes/<tema attivo>/<file>` → `pp-themes/_base/<file>` (`PP::themeFile`, `PP::partial`, `PP::asset`).
- Layout di base: testata con logo, menu esteso (`Menus::items('main')`, sottomenu), ricerca, lingue in `<details>`; footer con `partials/footer-extra.php` del sito.
- CSS in ordine: base.css → theme.css del tema → identity.css (colori e font dell'Identità visiva vincono) → pp.css → `site/theme/assets/site.css` se c'è.
- **Il tema usa solo le variabili dell'identità** per colori e font: `--bg --ink --surface --muted --accent(-ink) --field-1(-ink) --field-2(-ink) --black(-ink) --hero-1 --hero-2 --font --font-display --radius`.
- `theme.json`: name, order, description, best_for, direction, palette (ruoli identità), fonts {display,text}, buttons, body_class (es. `photo-hero`), palettes, layouts, `accent_text`,
  `hero_on` (`accent` | `ground` | `photo`): il fondo per cui sono pensati `hero1`/`hero2`. Un'apertura con tono "Automatico" va lì (`Blocks::render`) e `Identity::checks()` misura la frase grande su quel fondo: senza foto il titolo deve restare leggibile.
- Home: se la prima fascia non è un'apertura, `templates/home.php` stampa un `h1` `sr-only` col nome del sito. Fascia loghi con pulsante di pausa (`[data-logos-pause]`, `.logos.is-paused`).
  Sottomenu: sul computer il passaggio del mouse mette anche `aria-expanded`; Esc aggiunge `html.sub-esc` che chiude anche quelli aperti da `:hover`/`:focus-within` finché non si esce dalla voce.
- Home a blocchi: entry di tipo `block`, `template` = tipo (hero, statement, icons, steps, collection, figures, latest, text, gallery, cta, video, logos, team, roster, map, quote, faq, newsletter, events), ordine = menu_order.
  L'apertura può essere uno slideshow (campo `slides`: percorsi uno per riga, `slides_alts` tradotto; `[data-slides]` in base.js: `data-nav` dots|arrows|both|none, `data-time` secondi,
  `slides_fx` fade|slide, pausa al passaggio e al fuoco, pulsantino pausa, niente giro con meno movimento); `height` short|medium|tall, `title_size` s|m|l.
  `statement` con `align=center`; `icons` (fino a 6 riquadri: icona da `partials/icon.php` – icone disegnate per PhalbPress –, titolo, testo, pulsante; `per_row`, `boxed`);
  `collection` con `cols` 3|4 e `picks` (contenuti scelti a mano, campo `entries`; i vecchi `pick1`…`pick8` vengono migrati); `logos` con `speed` e link sotto (`more_label`, `more_url`); `team` (tipo `person` se il sito lo definisce);
  `roster` (testo: "Nome: ruolo" per riga, `#` = titolo di gruppo, nomi separati da virgola = elenco continuo); `map`.
  Cambiare tipo non perde niente: `Blocks::ITEMS` (voci di icons, steps, faq, figures) e `Blocks::carry()` passano le voci al tipo nuovo; la gestione salva
  anche i valori dei campi che il modulo non mostra (extra e meta restano), e al cambio di tipo i campi del tipo nuovo non inviati tengono il loro valore.
  Un tema aggiunge un segno all'apertura con `partials/hero-extra.php` (riceve `$b` e `$mark`, campo `mark` dell'apertura: ''|dots|bars|rings|none; non ricopiare `blocks/hero.php`).
  Elenco di contenuti: `picks` (campo `entries`, sostituisce pick1…8, migrati da `Blocks::migratePicks()`), `ratio` (''=quadrate|4:3|3:2|16:9|3:4|auto → `cards--r-*`),
  `photo_fx`/`photo_hover` per fascia (`collection--fx-*`, `collection--hover-*`). Con più foto i comandi vanno nella figura se la figura non è `absolute` (`.has-ctl`).
  Definizioni e campi in `Blocks::kinds()` (filtro `block_kinds`); render `Blocks::render()` → `partials/blocks/<tipo>.php`; tono → `Blocks::toneClass()`.
- **Aspetto delle fasce** (`Blocks::look()`: tone con `custom`, bg_color, bg_image, bg_focus, bg_overlay, parallax, spacing, width; in `meta`, non tradotti):
  si applicano in `Blocks::decorate()` al primo `<section>` del blocco, quindi valgono con ogni tema senza toccarne i partial. Un fondo scuro (colore libero scuro
  o velo scuro) diventa `chapter--ink`, uno chiaro `chapter--paper`: testi e riquadri seguono le regole del tema per quei toni. CSS in `pp-assets/pp.css`
  (dopo tema e identità), parallasse in `_base/assets/base.js`. Un tema nuovo deve reggere `chapter--custom`, `chapter--has-bg`, `chapter--over-dark|light`,
  `chapter--sp-compact|wide`, `chapter--w-narrow|full`: per colori di card e testi usare `--surface` e `--muted`, che quelle classi ridefiniscono.
- **Modifica visuale** (`core/modules/themes/Visual.php`, `pp-admin/visual.js`, anteprima con `&edit=1` → `pp-admin/visual-frame.js`): permesso `publish`; gestisce le fasce della home e le disposizioni delle pagine interne (per tipo o per singola pagina, news, storia o categoria).
- **Modelli di testata** (`Layouts`, tipo `site`, parte `header`: bar|right|center|tabs|drawer): stesso HTML per tutti, classe `head-model--X` sulla testata e `hm-X` sul body, struttura in base.css. Il tema dà quello di partenza in `layouts.site.header`, il sito lo cambia cliccando la testata in Modifica visuale. Su telefono diventano tutti logo + strumenti + Menu.
- **Disposizioni delle pagine interne** (`core/modules/themes/Layouts.php`): ogni tipo di pagina (page, news, news-item, stories, story, results, partners, contact, search, 404) ha delle parti
  (`head`: left|center|split|cover|minimal · `body`: read|wide|aside|columns · `list`: grid|feature|rows|mosaic · `stories`: zigzag|grid|mosaic|tiles|cards|covers|screens|index|thumbs
  · `work` (tipo `story`, la scheda): portrait|sheet|showcase|journal · `docs`: cards|table · `partners`: cards|logos|rows),
  uguali per tutti i temi. Dalla 1.18 ogni tema ha in `layouts` la sua disposizione di `stories.stories`, `story.work` e `news.list`, diversa dagli altri (tabella nelle skill). `Layouts::get($tipo, $parte, $contenuto)` cerca: meta `layout_<parte>` della singola pagina, news, storia o categoria → impostazione `layouts` del sito (per tipo) → `layouts` del theme.json
  (`"*"` vale per tutti i tipi) → base. I template mettono la classe `head--X` sull'intestazione (partial `band`, struttura piatta: percorso, etichetta, titolo, frase, dati, foto o `.band__deco`),
  `body--X`, `list--X`, `stories--X`, `docs--X`, `partners--X` sulle parti e `data-pp-part`; la struttura sta in base.css, il tema ci mette il carattere e deve reggerle tutte, anche senza foto.
  Nella Modifica visuale si sceglie la pagina in alto e si clicca una parte: vale per tutte le pagine del tipo o solo per quella (anteprima con `data-pp-type`/`data-pp-entry` sull'html).
- **Movimento comune** (`_base/assets/base.js` + base.css): comparse a cascata (`data-fx` = up|down|left|right|zoom|pop|tilt|spin|fade, ritardo `--fx-d`), entrata di apertura e intestazioni (`body.fx-go`),
  numeri che contano. Il tema lo regola in theme.json `"motion": {"marks": {selettore: [tipo, ms tra fratelli]}, "count": ms|false, "tuck": bool}` o lo spegne con `"motion": false`; tempi con `--fx-t --fx-e --fx-y`.
  Con "meno movimento" del sistema non si muove niente; sui telefoni niente entrate di lato.
- Anteprima: `/{admin}/preview?path=/it/…&theme=slug[&palette=1]` (bozze e programmati visibili, palette del tema al posto di quella dell'identità).
  Con `&demo=1` l'anteprima usa i contenuti dimostrativi (`pp-assets/demo/seed.php` + `media/`, gli stessi del sito di `tools/demo`): `Demo::use()` passa a `data/demo-preview.sqlite`
  (rifatto quando cambiano seed, lingue o versione) con `DB::reconnect()`; ogni lingua del sito prende il testo nella sua lingua o in inglese; `Demo::links()` tiene i link interni nell'anteprima.
- Foto segnaposto nel sito vero: `Demo::placeholders()` (Immagini e file, azione `placeholders`) copia `pp-assets/demo/media` in `uploads/media/…/segnaposto-*.webp` solo nei posti vuoti (apertura, news, storie, gallerie), un file per contenuto.
- Titoli: niente `hyphens: auto`; `base.js` riduce il carattere di un titolo se una parola non entra (`overflow-wrap: anywhere` solo senza JS).
- Nuovi temi: skill design-taste-frontend (draw → direction sheet, inspect). Le direzioni usate sono in ogni theme.json.

## Hook
| hook | tipo | uso |
|---|---|---|
| `strings` | filter | testi traducibili `$d + ['k' => ['group','html'=>false|true|'block','text'=>['en'=>…]]]` |
| `route` / `alternate` | first | rotta pubblica / stesso indirizzo in un'altra lingua |
| `paths` | filter | `[['kind','paths'=>[lang=>path],'noindex'=>bool], …]` |
| `file` / `static_files` | first / filter | file generati (sitemap, json…) e quali scrivere in statico |
| `post` | first($path) | ricevere POST pubblici (es. form/contact) |
| `type_prefix` | first($type,$lang) | prefisso URL di un tipo con pagine proprie |
| `fields` | filter($fields,$type,$row) | campi personalizzati |
| `block_kinds` / `menu_sections` / `search_types` / `search_index` | filter | blocchi, sezioni nel menu, ricerca |
| `form_data` | filter | dati di una richiesta prima del salvataggio |
| `backup_tables` | filter | tabelle dei moduli da includere nei backup |
| `head`, `body_end`, `footer_links`, `body`, `cookie_services`, `csp_frames`, `csp_img`, `csp_scripts`, `csp_connect` | | come prima |
| `saved` | fire | dopo ogni salvataggio (scansione cookie 10, statico 50) |
| `admin.pages` | filter | `slug => ['label','group','handler','perm']` |
| `cron` | fire | lavori periodici dei moduli, da `cron.php` |
| `cli` | first($args) | comandi dei moduli: `php cron.php <comando> …` (restituire true se gestito) |
| `wp_import_entry` / `wp_import_term` / `wp_import_term_url` / `wp_import_menu_item` | filter | ritocchi del sito all'importazione da WordPress |

## Repo e release
- Repo pubblico `Phalbo/phalbpress`: SOLO il motore (+ `tools/`, `skeleton/` per il pacchetto completo, `.github/`). Mai `site/`, `uploads/`, `data/`.
- Versione in `core/PP.php` (`VERSION`). Ogni release: alza la versione, aggiungi `## X.Y.Z` in `CHANGELOG.md` (testo mostrato nei siti), commit su `main`, tag `vX.Y.Z`.
- La Action `release.yml` sul tag lancia `tools/build.php --tag`: `php -l` di tutto, `dist/phalbpress-core-X.Y.Z.zip` (+ `phalbpress.json` con versione e sha256), firma Ed25519 con il secret `PP_SIGNING_KEY` (seme base64), `dist/phalbpress-X.Y.Z.zip` completo per i siti nuovi (firmato anche lui), `dist/phalbpress-installa.php` (da `tools/installa.php` con la chiave pubblica dentro: scarica il pacchetto completo, verifica la firma, scompatta), release con le note.
- I siti (Gestione → Aggiornamenti, `core/modules/updater/`) accettano solo zip firmati con la chiave in `core/modules/updater/release.pub`; backup completo prima; sostituiscono solo `Updater::ITEMS`; `.htaccess` non si sovrascrive (copia in `data/updates/htaccess-X.txt`); la versione precedente resta in `data/updates/prev-X/` per il ritorno indietro.
- **Commit e release: solo il messaggio.** Niente righe `Co-Authored-By`, niente link alle sessioni, niente firme di strumenti: il repo è pubblico.
- Gli zip non devono mai avere una data fissa nei file: OPcache rilegge un file solo se la data cambia (`BUILT` in `tools/build.php`, `Updater::fresh()`).
- Le migrazioni del database devono restare additive (colonne e tabelle nuove), così il ritorno alla versione precedente non rompe nulla.

## Stato al 3 novembre 2026
- `main` = **1.18.0** (tag `v1.18.0` lo crea Andrea). 1.17.0 = audit di Fable più correzioni (storia nel ramo `audit-1.17`); 1.18.0 = ramo `v1.18`: disposizioni diverse per tema,
  foto a colori e quadrate, varianti per tutti i temi, colori personali, statistiche, campo Galleria, anteprime social, servizi di Ares.
- **`AUDIT.md`** nella radice: quello che resta aperto dall'audit, in ordine di importanza.
- Sito Ares: Andrea lo reinstalla da zero con la 1.18 (database e file nuovi, poi Importa da WordPress con xml, dump e zip delle foto). Le scelte di Ares stanno nel seed, nel config e nel modulo `site/modules/ares`, non in migrazioni.
- Sui siti installati prima della 1.17 l'`.htaccess` non si aggiorna da solo: vanno copiate a mano le righe `RewriteRule ^(site|pp-assets)/.+\.php$ - [F,L]` e `RewriteRule ^uploads/.+\.(php\d?|phtml|phar|shtml)$ - [F,L]` (dopo quella di `pp-themes`) e `https://www.openstreetmap.org` nella `frame-src`.

## Test locale
Sito dimostrativo (progetto inventato "Common Ground", 3 lingue, foto con le misure stampate sopra): `php tools/demo.php` (o `--force` per rifarlo) copia `tools/demo/site` in `site/`,
poi `php -S 127.0.0.1:8080 index.php` → `/install.php`. Le foto del demo si rifanno con `python3 tools/demo/foto.py <cartella-foto>`. `tools/` non entra negli zip delle release.
```
cp site/config.sample.php site/config.php   # 'driver' => 'sqlite', 'file' => __DIR__.'/../data/site.sqlite', 'debug' => true
php -S 127.0.0.1:8080 index.php             # /install.php, poi /gestione
```
Verifiche dopo una modifica: `php -l` ovunque, tutte le rotte pubbliche e di gestione 200 senza warning, nessun `Set-Cookie` pubblico,
installazione pulita su SQLite e MariaDB, `StaticSite::build()` senza errori, i temi in anteprima a 1440 e 390 px senza scroll orizzontale, anche con l'apertura senza foto.
