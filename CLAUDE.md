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
cron.php             solo CLI: contenuti programmati → rigenera lo statico (Scheduler::tick), poi hook `cron` (es. controllo aggiornamenti)
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
pp-themes/<tema>/    theme.json + assets/theme.css (+ theme.js, + template/partial che sostituisce) — 18 temi
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
- Campi personalizzati in `config['types'][tipo]['fields']`: `'k' => 'Etichetta'` o `['label','type'=>text|textarea|number|date|url|select|image|checkbox|entry,
  'options','entry_type','translatable','hint']`. Non traducibili → `meta`; traducibili → `pp_entry_tr.extra` (nel template: `$e['x']['k']`).
- `pp_revisions` (ultime N versioni per contenuto e lingua, `config['revisions_keep']`), `pp_redirects`, `pp_tokens` (reset password), `pp_form_entries`.
- `pp_docs` + `pp_doc_tr` + `pp_doc_files`; `pp_strings`; `pp_seo`; `pp_settings` (theme, menus, identity, legal…); `pp_users` (role, langs, totp_secret); `pp_log`.
- **Stato traduzioni**: `src_hash` = `Content::hashOf` (title, excerpt, body, + image_alt ed extra solo se compilati). Non cambiare la formula.

## Ruoli
admin (tutto) · editor (contenuti, pubblicazione, menu, testi, documenti, redirect, richieste) · author (solo i propri contenuti, in bozza) ·
translator (solo le lingue in `users.langs`). Controlli: `Auth::can('admin'|'site'|'publish'|'content'|'translate')`, `Auth::canLang()`, `Auth::canEdit($row)`.
Pagine di modulo: chiave `perm` in `admin.pages` (default `site`; `admin_only` = `admin`).

## Funzioni comuni (in _base, valgono con ogni tema)
- **Galleria nel testo**: shortcode `[gallery:percorso=alt|percorso=alt]` (pulsante "Galleria" nell'editor) → `partials/gallery.php`.
- **Lightbox**: `<dialog class="pp-lightbox">` in base.js per gallerie, `[data-lightbox]` e immagini della `.prose`; frecce, tastiera, swipe, testi da `data-ui` del body.
- **Punto focale**: meta `image_focus` (top|bottom|left|right) → `focus_class($e)` = classe `pp-focus--X` su card, intestazione, mosaico.
- **Pagine madri**: meta `parent` (id) o `parent_slug` nel seed → `page_parent($e)`; percorso sopra il titolo ed elenco `.children` (parte `list`) nella madre.
- **Social**: `partials/social.php` (icone SVG dal nome o dal dominio); `social_place` = footer|header|both|toolbar|toolbar_footer|none nelle Impostazioni.
- **Barra in testata e footer** (Impostazioni, chiavi del config): `toolbar` (barra sottile con telefoni, email, social), `company`, `phone`, `phone2`, `pec`, `vat`,
  `offices` (blocchi separati da una riga vuota, `SiteSettings::offices()`), `footer_badge` (immagine, es. certificazioni). Footer: logo per fondi scuri, frase, `footer_note`,
  contatti, menu `footer`, sedi, riga finale `© <span data-year>` + `footer_legal`. L'anno si aggiorna da solo (firma del Scheduler con `date('Y')` + base.js).
  Footer di base scuro: si stampano tutti e due i loghi (`foot__logo-img--night|--day`); i temi col footer chiaro hanno `foot-light` in `body_class`.
- **Sottomenu**: si aprono passandoci sopra (e col clic/tastiera); un terzo livello si apre di lato (`nav__sub--deep`).
  Voce di menu `section` con ref `stories:cats` = Portfolio con le sue categorie (`Stories::menu()`); con `parent=1` una voce va sotto la precedente.
- **Newsletter**: `Forms::render('newsletter')` (email + consenso con data), esportazione CSV in Richieste.
- **Portfolio** (`core/modules/themes/Stories.php`): una storia ha `meta.category` + `meta.categories` (id di contenuti `category`, con `meta.parent` per le sottocategorie).
  Ogni categoria usata ha la pagina `/{lang}/{sezione storie}/{slug}/` (rotta `stories-cat`, template `stories` con `$category`, in `PP::items()` quindi anche statica);
  la disposizione dell'elenco si sceglie per tipo o per categoria (meta `layout_stories`). Dati della scheda: campi del tipo `story` con `'public' => chiave del testo` (`Stories::facts`).
  Categorie: ordine con `menu_order`, `meta.nav_hidden` le toglie da filtri e menu (restano raggiungibili); con aree e divisioni (`Stories::nested()`) in alto ci sono solo le aree.
- **Identità**: `logo_has_name` (il logo contiene già il nome: `.brand__text` diventa `sr-only`); `logo_h` (altezza del logo in px), `photo_fx` (none|mono|tint: foto degli elenchi),
  `photo_hover` (color|zoom|none), `band_h` (compact|tall: altezza delle intestazioni). Scritte in `identity.css` da `Identity::extraCss()`.
- **Fasce nelle pagine**: un blocco col campo `page` (id) o `page_slug` (nel seed: la pagina può arrivare dopo, es. con l'importazione) va in fondo a quella pagina
  (`Blocks::forPage()`, in `templates/page.php`) e non in home. `Blocks::bindPages()` (hook `saved`) trasforma `page_slug` in `page` quando la pagina esiste.
- **Mappa** (blocco `map`): OpenStreetMap in iframe solo dopo il consenso (`data-pp-embed`, servizio `openstreetmap` in `Legal::services`, `csp_frames`); senza consenso link "Apri su OpenStreetMap".
- **Sito del progetto** (`core/SitePackage.php`): `extract()` scompatta la cartella `site/` da uno zip (anche annidata), mai `config.php`; lo usano l'installazione guidata
  e Aggiornamenti → "Sito del progetto" (`Updater::siteUpload()`: password, copia `sito-*.zip` in data/backups, `extract(..., mirror)`, `followSample()` per riscrivere un config.php
  completo come quello dell'installazione). `'site_upload' => false` lo spegne. Un config.php scritto dall'installazione fa `array_replace(require config.sample.php, [db, admin_slug, site_url, debug])`.
- **Contenuti di partenza**: `core/Starter.php` (`seed()`, `strings()`, `start('seed'|'demo'|'empty')`), usato da install.php e da **Azzera il sito**
  (`Backup::wipe()`: svuota le tabelle tranne l'utente che azzera e le impostazioni `secret`/`module_versions`, poi `Starter::start()`; opzioni backup, keep_uploads, keep_import, keep_backups, keep_users).
  Le foto importate da WordPress sono anche in `data/wp-media-map.json` (`WpImport::saveMediaMap()`): un'importazione col database nuovo le ritrova.
- **Gestione**: `Admin::pager($items, $per)` (`?p`, `?all`) per elenchi lunghi: Immagini e file, SEO, Redirect, Accessibilità hanno filtri e pagine.
  `search_hidden` (SEO, attivo a ogni installazione): `noindex` in tutte le pagine, robots.txt `Disallow: /`, avviso in cima alla gestione finché non si toglie.

## Importa da WordPress (core/modules/wpimport)
- File in `data/wp-import/`: `.xml` (WXR, obbligatorio), `.sql` (facoltativo: SOLO `icl_translations`, `term_taxonomy`, `redirection_items`), `.zip` di uploads (facoltativo, altrimenti scarica dal vecchio sito con `'download' => true`).
- `WpSource` legge (XMLReader, una riga SQL alla volta) e salva il modello in `data/wp-import/model.ser`; `WpContent::html()` converte gli shortcode (liste `WRAP`/`DROP`, il resto tiene il testo);
  `WpImport` lavora a passi `scan → media → terms → entries → links → menu → redirects` con stato in `state.json`, collegamenti in `pp_wp_map` (kind: media|post|entry|term|termgroup).
- Gruppi di traduzione = `trid` di WPML; senza la lingua principale il contenuto diventa bozza. Slug di WordPress mantenuti. Foto con `Media::upload` (stesso trattamento del caricamento).
- Config del sito `wp_import`: `types` (wp → pp), `taxonomies` (`['tax' => ['type','field']]`), `drafts`, `skip` (lista o `'slug' => 'home'|chiave sezione|'/percorso/'` per il redirect), `download`, `menu`.
- Filtri: `wp_import_entry` ($data con type, title, excerpt, body, slug, image, image_alt, meta, extra, skip), `wp_import_term_url` (→ `['from','to']`), `wp_import_menu_item`.
- CLI: `php cron.php wpimport plan|run|reset|undo` (hook `cli` dei moduli: `Hooks::first('cli', $args)`).

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
- `theme.json`: name, order, description, best_for, direction, palette (ruoli identità), fonts {display,text}, buttons, body_class (es. `photo-hero`),
  `hero_on` (`accent` | `ground`): il fondo per cui sono pensati `hero1`/`hero2`. Un'apertura con tono "Automatico" va lì (`Blocks::render`) e `Identity::checks()` misura la frase grande su quel fondo: senza foto il titolo deve restare leggibile.
- Home a blocchi: entry di tipo `block`, `template` = tipo (hero, statement, icons, steps, collection, figures, latest, text, gallery, cta, video, logos, team, roster, map, quote, faq, newsletter, events), ordine = menu_order.
  L'apertura può essere uno slideshow (campo `slides`: percorsi uno per riga, `slides_alts` tradotto; `[data-slides]` in base.js: `data-nav` dots|arrows|both|none, `data-time` secondi,
  `slides_fx` fade|slide, pausa al passaggio e al fuoco, pulsantino pausa, niente giro con meno movimento); `height` short|medium|tall, `title_size` s|m|l.
  `statement` con `align=center`; `icons` (fino a 6 riquadri: icona da `partials/icon.php` – icone disegnate per PhalbPress –, titolo, testo, pulsante; `per_row`, `boxed`);
  `collection` con `cols` 3|4 e `pick1`…`pick8` (contenuti scelti a mano); `logos` con `speed` e link sotto (`more_label`, `more_url`); `team` (tipo `person` se il sito lo definisce);
  `roster` (testo: "Nome: ruolo" per riga, `#` = titolo di gruppo, nomi separati da virgola = elenco continuo); `map`.
  Cambiare tipo non perde niente: `Blocks::ITEMS` (voci di icons, steps, faq, figures) e `Blocks::carry()` passano le voci al tipo nuovo; la gestione salva
  anche i valori dei campi che il modulo non mostra (extra e meta restano), e al cambio di tipo i campi del tipo nuovo non inviati tengono il loro valore.
  Un tema aggiunge un segno all'apertura con `partials/hero-extra.php` (non ricopiare `blocks/hero.php`).
  Definizioni e campi in `Blocks::kinds()` (filtro `block_kinds`); render `Blocks::render()` → `partials/blocks/<tipo>.php`; tono → `Blocks::toneClass()`.
- **Aspetto delle fasce** (`Blocks::look()`: tone con `custom`, bg_color, bg_image, bg_focus, bg_overlay, parallax, spacing, width; in `meta`, non tradotti):
  si applicano in `Blocks::decorate()` al primo `<section>` del blocco, quindi valgono con ogni tema senza toccarne i partial. Un fondo scuro (colore libero scuro
  o velo scuro) diventa `chapter--ink`, uno chiaro `chapter--paper`: testi e riquadri seguono le regole del tema per quei toni. CSS in `pp-assets/pp.css`
  (dopo tema e identità), parallasse in `_base/assets/base.js`. Un tema nuovo deve reggere `chapter--custom`, `chapter--has-bg`, `chapter--over-dark|light`,
  `chapter--sp-compact|wide`, `chapter--w-narrow|full`: per colori di card e testi usare `--surface` e `--muted`, che quelle classi ridefiniscono.
- **Modifica visuale** (`core/modules/themes/Visual.php`, `pp-admin/visual.js`, anteprima con `&edit=1` → `pp-admin/visual-frame.js`): permesso `publish`.
- **Modelli di testata** (`Layouts`, tipo `site`, parte `header`: bar|right|center|tabs|drawer): stesso HTML per tutti, classe `head-model--X` sulla testata e `hm-X` sul body, struttura in base.css. Il tema dà quello di partenza in `layouts.site.header`, il sito lo cambia cliccando la testata in Modifica visuale. Su telefono diventano tutti logo + strumenti + Menu.
- **Disposizioni delle pagine interne** (`core/modules/themes/Layouts.php`): ogni tipo di pagina (page, news, news-item, stories, story, results, partners, contact, search, 404) ha delle parti
  (`head`: left|center|split|cover|minimal · `body`: read|wide|aside|columns · `list`: grid|feature|rows|mosaic · `stories`: zigzag|grid|mosaic|tiles|cards|covers|screens|index|thumbs
  · `work` (tipo `story`, la scheda): portrait|sheet|showcase|journal · `docs`: cards|table · `partners`: cards|logos|rows),
  uguali per tutti i temi. `Layouts::get($tipo, $parte, $contenuto)` cerca: meta `layout_<parte>` della singola pagina, news, storia o categoria → impostazione `layouts` del sito (per tipo) → `layouts` del theme.json
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
| `head`, `body_end`, `footer_links`, `body`, `cookie_services`, `csp_frames` | | come prima |
| `saved` | fire | dopo ogni salvataggio (scansione cookie 10, statico 50) |
| `admin.pages` | filter | `slug => ['label','group','handler','perm']` |
| `cron` | fire | lavori periodici dei moduli, da `cron.php` |
| `cli` | first($args) | comandi dei moduli: `php cron.php <comando> …` (restituire true se gestito) |
| `wp_import_entry` / `wp_import_term_url` / `wp_import_menu_item` | filter | ritocchi del sito all'importazione da WordPress |

## Repo e release
- Repo pubblico `Phalbo/phalbpress`: SOLO il motore (+ `tools/`, `skeleton/` per il pacchetto completo, `.github/`). Mai `site/`, `uploads/`, `data/`.
- Versione in `core/PP.php` (`VERSION`). Ogni release: alza la versione, aggiungi `## X.Y.Z` in `CHANGELOG.md` (testo mostrato nei siti), commit su `main`, tag `vX.Y.Z`.
- La Action `release.yml` sul tag lancia `tools/build.php --tag`: `php -l` di tutto, `dist/phalbpress-core-X.Y.Z.zip` (+ `phalbpress.json` con versione e sha256), firma Ed25519 con il secret `PP_SIGNING_KEY` (seme base64), `dist/phalbpress-X.Y.Z.zip` completo per i siti nuovi (firmato anche lui), `dist/phalbpress-installa.php` (da `tools/installa.php` con la chiave pubblica dentro: scarica il pacchetto completo, verifica la firma, scompatta), release con le note.
- I siti (Gestione → Aggiornamenti, `core/modules/updater/`) accettano solo zip firmati con la chiave in `core/modules/updater/release.pub`; backup completo prima; sostituiscono solo `Updater::ITEMS`; `.htaccess` non si sovrascrive (copia in `data/updates/htaccess-X.txt`); la versione precedente resta in `data/updates/prev-X/` per il ritorno indietro.
- **Commit e release: solo il messaggio.** Niente righe `Co-Authored-By`, niente link alle sessioni, niente firme di strumenti: il repo è pubblico.
- Gli zip non devono mai avere una data fissa nei file: OPcache rilegge un file solo se la data cambia (`BUILT` in `tools/build.php`, `Updater::fresh()`).
- Le migrazioni del database devono restare additive (colonne e tabelle nuove), così il ritorno alla versione precedente non rompe nulla.

## Test locale
Sito dimostrativo (progetto inventato "Common Ground", 3 lingue, foto con le misure stampate sopra): `php tools/demo.php` (o `--force` per rifarlo) copia `tools/demo/site` in `site/`,
poi `php -S 127.0.0.1:8080 index.php` → `/install.php`. Le foto del demo si rifanno con `python3 tools/demo/foto.py <cartella-foto>`. `tools/` non entra negli zip delle release.
```
cp site/config.sample.php site/config.php   # 'driver' => 'sqlite', 'file' => __DIR__.'/../data/site.sqlite', 'debug' => true
php -S 127.0.0.1:8080 index.php             # /install.php, poi /gestione
```
Verifiche dopo una modifica: `php -l` ovunque, tutte le rotte pubbliche e di gestione 200 senza warning, nessun `Set-Cookie` pubblico,
installazione pulita su SQLite e MariaDB, `StaticSite::build()` senza errori, i temi in anteprima a 1440 e 390 px senza scroll orizzontale, anche con l'apertura senza foto.
