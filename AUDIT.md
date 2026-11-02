# Audit PhalbPress: cosa resta aperto dopo la 1.17.0

L'audit completo della 1.16.0 (2-3 novembre 2026) e le sue correzioni sono nella storia di git (ramo `audit-1.17`, unito in `main` con la 1.17.0) e nel CHANGELOG. Qui resta solo quello che non è stato fatto, in ordine di importanza. Ogni voce: dove, cosa succede, correzione proposta.

## Sicurezza
- **Anteprima per tutti i ruoli** — `core/Admin.php` (`preview` non è nella mappa dei permessi): autori e traduttori vedono tutte le bozze; `demo=1` ricrea `data/demo-preview.sqlite`. I traduttori vedono comunque tutto in gestione. Proposta: `demo` solo con `site`; valutare `preview` con `content|translate`.
- **CSP delle pagine statiche** scolpita in `.htaccess` (che l'aggiornamento non tocca), diversa da `PP::csp()` quando un modulo usa il filtro `csp_frames`. Proposta: avviso in Sistema se la riga dell'`.htaccess` non coincide.
- **Messaggi d'eccezione grezzi** in `Admin::view('error')` e nel JSON di `Visual.php` (testo PDO, percorsi). Proposta: messaggio generico fuori debug, dettaglio nel registro.
- **Installazione senza autenticazione** (`install.php`, `phalbpress-installa.php`; quest'ultimo elenca i file della cartella). Documentato in LEGGIMI. Proposta: codice monouso stampato nel file.
- **Caricamento font** (`Identity.php` ~310): solo l'estensione; mancano `error`, dimensione, firma `wOF2`.
- `X-Powered-By` esposto dal server: `Header unset X-Powered-By` in `.htaccess`.

## Correttezza
- **"Sito del progetto" con "follow"** (`SitePackage::followSample`) perde le chiavi messe a mano in `config.php` (`translation.api_key`, `base_path`, `timezone`, `static`, `events`, `site_upload`, `legal`…). Copia in `data/backups/config-*.php.txt`. Proposta: elencarle prima di spuntare.
- **WordPress**: dump SQL senza elenco colonne (mysqldump, WP-CLI) → WPML e Redirection ignorati in silenzio (`WpSource.php` 158,165); zip fatto "dentro" `uploads` → tutto mancante (`WpSource.php` 226); nessun lock su `run()` (due esecuzioni insieme duplicano); "Importa di nuovo" con stato `done` non fa nulla; `undo()` lascia `pp_seo` orfane e cancella file riusati; SEO Yoast solo per page/news/story/event; `wp:tag` non letto; e le basse: `map('media')` senza `is_file`, meta presi dalla traduzione se il master non ha foto, chiave media per nomi con `-WxH`, download senza http→https, catene di Redirection, menu nella sola lingua master, `future` → bozza senza `publish_at`, `total` non azzerato, "Ricomincia" senza conferma, `fgets` senza limite sulle tabelle vietate del dump, `files()` a ogni fetch, `newUrl()` una query per item, `neededMedia()` ripetuto a ogni `run`, file letti interi prima del limite dei 30 MB.
- **Sanitizer**: `'<p>a</div><p>b</p>'` → `'<p>a</p>'` (un `</div>` spurio fa perdere il resto del testo). Proposta: radice non chiudibile (`body->childNodes`).
- **Provini**: `build()` senza `try/finally` (un tema rotto lascia provini senza manifesto e senza "Cancella"); `robots.txt` in sottocartella inutile; regex lingue `[a-z]{2}`.
- **Modifica visuale**: l'elenco "Fasce della home" mostra anche le fasce legate alle pagine; `Layouts::setEntry` accetta parti di altri tipi; ramo `reset_page` mai inviato.
- **SEO**: `Seo::admin` salva `ref` arbitrari (righe orfane).
- `404.html` statico mai usato; Sistema chiede PHP 8.2, il motore 8.1.
- `data-ink` ripetuto in 19 partial e sbagliato col tono `accent` su un accento scuro (lo legge `voci/theme.js`). Proposta: calcolarlo in `Blocks::decorate()`.
- Anteprima demo: 4 link "Documenti" con `#doc-N` escono dall'anteprima (`Demo::links` non riscrive i link con l'ancora); una risorsa 404 compare in console solo ogni tanto, non riprodotta.

## Accessibilità
- **Editor della gestione**: area `contenteditable` senza ruolo e nome; pulsanti con nome "¶", "B", "I". Proposta: `role="textbox" aria-multiline`, `aria-label`.
- Menu su telefono senza focus trap; salti h1→h3 in news e storie; id dell'indice duplicabili; nome del sito ripetuto con due loghi; `aria-label` su `span`/`p`; pausa dello slideshow con `aria-pressed` più etichetta che cambia; bersagli touch da 28 px; bordo dei campi della gestione 1,67:1; "Coordinator" senza `t()`; tabella dei contrasti senza intestazioni; "Sposta su/giù" tutti uguali.

## Prestazioni
- Query nei cicli della home: `collection` fa `byId` per ogni contenuto scelto, `Blocks::href()` 1-4 query per pulsante, `entries('block')` calcola gli slug inutilmente (60-100 query per home).
- Cruscotto: 2 query per contenuto più 2 per testo e lingua (≈1300 su Ares, 21 ms su SQLite: da misurare su MySQL).
- `Media::library()` fa `getimagesize` su ogni file a ogni apertura.
- `Content::uniqueSlug` per le pagine chiama `PP::items()` a ogni salvataggio.
- Frontend: `fitHeads` forza molti reflow; 4-5 CSS sincroni in catena; regole morte `.hero__pause`; slideshow attivo anche nei temi senza foto; logo senza `width/height`.

## Codice
- Codice morto: `Layouts::cls()`, `Themes::admin` passa `fonts`, `steps.php` fino a 6 con max 4, ripiego `pick1…8` dopo la migrazione, `WpImport::media($dry)`, controllo svg morto.
- Duplicati: `restore_identity` (Temi) e `variant_undo` (Identità); rinumerazione in `Visual::add/order`; indice dello zip in `WpImport` e `WpSource`; liste di nomi riservati in `StaticSite::RESERVED` e `Content::uniqueSlug`.

## Non verificato
MySQL/MariaDB, invio delle email, aggiornamento dalla gestione (serve la release pubblicata), importazione WordPress da capo, Apache vero (provato col server di sviluppo), screen reader a mano.
