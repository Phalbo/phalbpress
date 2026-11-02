# PhalbPress

Motore leggero in PHP per siti di progetto multilingua. Primo sito: **VOICE Europe**.
PHP 8.1+ e MySQL/MariaDB (SiteGround). Niente WordPress, niente plugin di terzi, niente librerie esterne.

**Cosa fa**
- **Lingue**: scrivi in inglese, il resto sono traduzioni tenute allineate. Se cambi l'originale, le traduzioni diventano "Da aggiornare". La traduzione automatica è facoltativa (Claude o DeepL).
- **Documenti**: un file comune per tutte le lingue, più un file tradotto facoltativo per lingua. Il link pubblico non cambia mai.
- **Identità visiva**: logo, claim, colori con controllo del contrasto, font ospitati sul sito, forma dei pulsanti.
- **SEO automatica**: titoli e descrizioni in ogni lingua, sitemap, hreflang, Open Graph, dati strutturati.
- **AI search**: robots.txt per i crawler AI, llms.txt.
- **Legale**: privacy policy, cookie policy e dichiarazione di accessibilità in tutte le lingue. Scansione dei servizi esterni e banner cookie solo se serve.
- **Accessibilità**: controlli automatici su testi alternativi, titoli, link, PDF e contrasto.
- **Sicurezza**:
  - sito pubblico in HTML statico;
  - nessun cookie per i visitatori;
  - accesso in due passaggi;
  - indirizzo della gestione personalizzato;
  - blocco dopo i tentativi falliti.
- **Immagini**: nomi file ripuliti; le immagini vengono raddrizzate, ridotte, convertite in WebP e private dei dati GPS.
- **Temi**: 16 temi intercambiabili (Tipografico, Editoriale, Manifesto, Documentario, Tech, ACE, Adrestia, Ares 2.0, FINE, Rebus, Sic2Sic, European Excellence, Sustainability Champion, SME Star, Cultural Ambassador, People's Choice) con anteprima prima di attivarli, anche con contenuti di esempio e con i colori del sito. I contenuti non cambiano. Ogni tema può avere **varianti di colore** (Identità visiva) e i **provini** generano una copia navigabile del sito per ogni tema, da far vedere al cliente (Temi → Genera i provini).
- **Home a blocchi**: apertura (anche a scorrimento), dichiarazione, riquadri con icona, passi, elenchi (anche scelti a mano), numeri, ultime news, testo, galleria, invito, video, loghi, squadra, elenco di persone, mappa, citazione, FAQ, newsletter, eventi. Si riordinano, si traducono, si mettono anche in fondo alle pagine interne. Cambiare tipo a una fascia non perde i contenuti.
- **Importazione da WordPress** (Sito → Importa da WordPress): export WXR, dump SQL facoltativo (solo le tabelle di WPML e Redirection) e zip delle immagini; mantiene indirizzi e redirect.
- **Novità 1.2**: indirizzi tradotti per lingua, menu principale e footer, redirect, ricerca interna, modulo di contatto, programmazione,
  versioni precedenti, duplica, anteprima delle bozze, campi personalizzati, categorie delle news, ruoli (redattore, autore, traduttore),
  password dimenticata, registro attività, backup e modelli, sostituzione dei file mantenendo il link.

---

## Installazione su SiteGround

1. **PHP**: Site Tools → Devs → PHP Manager → versione 8.2 o più recente; `upload_max_filesize` e `post_max_size` almeno 64M (per caricare lo zip del sito).
2. **Database**: Site Tools → Site → MySQL. Crea un database **nuovo e vuoto** e un utente con tutti i privilegi su quel database.
3. **File**: carica nella cartella del sito (vuota) **solo `phalbpress-installa.php`** (lo trovi in ogni release su GitHub) e aprilo nel browser:
   scarica l'ultima versione, controlla la firma, scompatta tutto e ti porta all'installazione. In alternativa carica il contenuto di `phalbpress/` dal pacchetto completo.
4. **Installazione guidata** (`/install.php`):
   - controllo del server, con quello che manca;
   - **il sito del progetto**: carichi lo zip del sito (la cartella `site/` preparata per il progetto), oppure ne fai uno nuovo scegliendo nome, lingue e tema;
   - **database**: host, nome, utente, password (con il prefisso SiteGround), indirizzo della gestione. Prova la connessione e scrive da sola `site/config.php`;
   - **contenuti e amministratore**: i contenuti del progetto, quelli di esempio, un modello o niente; foto segnaposto dove mancano; il primo amministratore.
5. Alla fine ti propone, tutto facoltativo: il comando per i lavori programmati (cron), un'email di prova, l'accesso in due passaggi, le impostazioni, e cancella `install.php`.
6. **HTTPS**: Site Tools → Security → SSL. Poi togli il commento a "Forza https" in `.htaccess` e, quando tutto funziona, alla riga HSTS.
7. Dalla gestione: **Sistema → Impostazioni del sito** (email di contatto e mittente, social, finanziatore, chi c’è dietro il sito: organizzazione o persona, traduzione automatica), **Privacy e cookie**, **Identità visiva**, **Temi**.

Durante l'installazione il sito è aperto a chi conosce l'indirizzo: fai i passi di seguito, senza lasciarlo a metà.

Per provarlo in locale senza MySQL: `'driver' => 'sqlite'` in `config.php`, poi `php -S localhost:8000 index.php`.

---

## Uso quotidiano

| Cosa | Dove |
|---|---|
| Pagine, news, storie, partner, paesi | Contenuti → scrivi in EN → **Salva** o **Salva e traduci in tutte le lingue** |
| Rivedere una traduzione | scheda lingua in alto: originale a sinistra, traduzione a destra → **Salva come rivista** |
| Caricare o sostituire un PDF | Documenti → il nuovo file vale subito in tutte le lingue, stesso link |
| PDF tradotto per una lingua | Documenti → scheda della lingua → "Carica una versione tradotta" |
| Inserire un documento in un testo | pulsante **Documento** dell'editor (`[doc:12]`): si aggiorna da solo |
| Video YouTube / Vimeo | pulsante **Video** (`[video:ID]`, `[vimeo:ID]`). Si caricano solo con il consenso; banner e cookie policy si aggiornano da soli |
| Testi di pulsanti, home, footer, pagine legali | Testi del sito |
| Tradurre tutto ciò che manca | Traduzioni → Avvia |
| Logo, colori, font, claim | Identità visiva |
| Titoli e descrizioni per Google | SEO (si compilano da soli; scrivi solo per cambiarli) |
| ChatGPT, Claude, Perplexity | AI search |
| Privacy, cookie, dichiarazione accessibilità | Privacy e cookie / Accessibilità |
| Sito statico, controlli, versioni, programmati | Sistema |
| Aspetto del sito | Temi (anteprima con i tuoi colori o con quelli del tema, poi Attiva); provini per il cliente |
| Velo e testo sulle foto, spazi, riquadro, logo chiaro, varianti di colore | Identità visiva |
| Nuova versione della cartella del progetto (`site/`) senza FTP | Aggiornamenti → Sito del progetto (chiede la password; copia della versione di prima in Backup e modelli) |
| Ripartire da capo (database nuovo, stessi file) | Backup e modelli → Azzera il sito (scegli cosa tenere: foto, importazione, backup, utenti) |
| Home | **Modifica visuale**: clicchi una fascia nell'anteprima e ne cambi fondo, immagine, parallasse, spazio, larghezza; sposti, nascondi, aggiungi. I testi da Blocchi della home |
| Voci del menu, link esterni, footer | Menu |
| Indirizzo di una pagina | campo Indirizzo sotto il titolo: si compila da solo dal titolo, in ogni lingua. Se lo cambi, il vecchio porta al nuovo |
| Pubblicare più tardi / togliere a una data | nella pagina del contenuto → Programma |
| Tornare a una versione di prima | nella pagina del contenuto → Versioni precedenti → Ripristina |
| Copiare una pagina | nella pagina del contenuto → Duplica |
| Messaggi dal sito | Richieste (anche per email; CSV scaricabile). Il modulo si mette in una pagina con `[form:contact]` |
| Vecchi indirizzi | Redirect |
| Sostituire un'immagine o un PDF ovunque | Immagini e file → Dettagli → Sostituisci (stesso link) |
| Chi ha fatto cosa | Registro attività |
| Copia del sito, o nuovo sito da questo | Backup e modelli |

**Immagini**: i formati consigliati per ogni tipo di contenuto sono scritti accanto al campo, e si cambiano in `config['image_hints']`. Ogni immagine caricata viene:
- raddrizzata secondo i dati del telefono;
- ridotta a 2400 px sul lato lungo;
- salvata in WebP qualità 82, con una copia da 960 px per i telefoni;
- privata dei dati GPS.

La gestione mostra prima e dopo, per esempio "4032×3024 5,1 MB → 2400×1800 310 KB".

**PDF**: al caricamento il sistema controlla che il PDF abbia i tag di accessibilità. Da InDesign o Word esporta sempre con "PDF con tag" e la lingua impostata.

---

## Attivare la traduzione automatica

Gestione → Sistema → **Impostazioni del sito** → Traduzione automatica: servizio (Claude di Anthropic oppure DeepL), chiave API, glossario dei termini da non tradurre.
In `site/config.php` resta `publish_machine_translations => false` per nascondere le traduzioni automatiche finché qualcuno non le rivede.

---

## Aggiornare il motore

**Gestione → Aggiornamenti → Aggiorna.** Il sito:
1. scarica l'ultima release da https://github.com/Phalbo/phalbpress e controlla la firma (accetta solo release firmate con la chiave del motore);
2. fa una copia di sicurezza del database (in Backup e modelli; i file caricati non cambiano con un aggiornamento);
3. sostituisce solo `core/`, `pp-admin/`, `pp-assets/`, `pp-themes/`, `index.php`, `cron.php` e la documentazione: `site/`, `uploads/` e `data/` non si toccano, `install.php` non viene rimesso;
4. applica le modifiche al database e rigenera le pagine statiche.

Se qualcosa va storto durante la sostituzione, rimette da solo la versione di prima. Dopo, il pulsante **Torna alla X.Y.Z** rimette la versione precedente.
Se la gestione non si aprisse più: File Manager → `data/updates/prev-X.Y.Z/` → sposta le cartelle al posto di quelle attuali.
Il `.htaccess` non viene sovrascritto (può avere https e HSTS attivati a mano): se la nuova versione ne ha uno diverso, la pagina lo segnala e la copia nuova sta in `data/updates/htaccess-X.Y.Z.txt`: confrontala e riporta a mano le righe nuove (es. la `frame-src` di OpenStreetMap per la mappa, i blocchi dei file PHP sotto `site/`, `pp-assets/`, `uploads/`).

Con `cron.php` attivo, quando esce una versione nuova arriva un'email agli amministratori (`'update' => ['notify' => false]` in `config.php` per spegnerla).

**Pubblicare una nuova versione (Andrea)**: correggi i file sul repo, alza `VERSION` in `core/PP.php`, scrivi le novità in `CHANGELOG.md` sotto `## X.Y.Z`,
poi su GitHub → Releases → *Draft a new release* → tag `vX.Y.Z` → *Publish*. La Action controlla la sintassi, crea gli zip, li firma e li allega alla release.
A mano: sostituisci le stesse cartelle e i file via FTP; le modifiche al database si applicano da sole al primo accesso alla gestione.

## Moduli (plugin)

- `core/modules/`: funzioni del motore (identità, SEO, AI, legale, statico, menu, redirect, ricerca, moduli di contatto, backup, registro, temi). Si aggiornano col core.
- `site/modules/`: funzioni solo di questo sito. Il core non le tocca. `site/modules/_esempio/` è un modello completo (testi, shortcode, pagina pubblica, pagina di gestione, tabella propria): togli il `_` dal nome della cartella per attivarlo.

## Nuovo sito per un altro progetto (CWL, Prosolidar…)

**Il modo veloce**: dal sito di partenza, Gestione → Backup e modelli → crea un **Modello**. Sul nuovo sito, in `install.php`, apri "Parti da un modello" e carica il .zip: arrivano contenuti, testi, menu, identità e immagini, senza utenti né richieste.

**Da zero**: si prepara solo la cartella `site/` del progetto (con la skill phalbpress-nuovo-sito, o a mano partendo da `tools/demo/site/` del repo):
`config.sample.php` (lingue, tipi, sezioni, categorie dei documenti, identità, tema), `strings.php`, `seed.php` con le foto in `seed-media/`,
in `theme/partials/` solo ciò che è del progetto (es. emblema UE nel footer), il logo in `assets/logo.svg`. Si comprime in uno zip e si carica al passo "il sito del progetto" dell'installazione.

`CLAUDE.md` spiega tutta la struttura: passalo a Claude e riparte senza spiegazioni.

## Sicurezza: cosa c'è

- **Sito statico**: con l'HTML statico attivo, chi visita il sito non esegue PHP e non tocca il database.
- **Gestione**:
  - indirizzo personalizzato;
  - accesso facoltativo solo da certi IP (`admin_ips`);
  - password di almeno 12 caratteri, con hash;
  - accesso in due passaggi (app di autenticazione);
  - blocco di 15 minuti dopo 5 tentativi;
  - sessione `HttpOnly` / `SameSite=Strict`, token CSRF su ogni modifica.
- **Contenuti e upload**:
  - HTML dell'editor ripulito;
  - SVG ripuliti da script e dati superflui (il logo VOICE è passato da 471 KB a 4 KB);
  - upload controllati per tipo reale, nessuno script eseguibile in `uploads/`.
- **Server e browser**:
  - Content-Security-Policy senza script esterni;
  - cartelle private bloccate da `.htaccess`.
- **Dipendenze**: nessuna libreria di terzi da aggiornare.

Resta da fare ogni tanto: aggiornare la versione di PHP su SiteGround quando quella in uso scade, e tenere password robuste.

## Backup

Gestione → Backup e modelli: un .zip con tutto il database e, se vuoi, i file caricati, da scaricare; da lì si ripristina anche (prima viene fatta da sola una copia di sicurezza). Un **Modello** è lo stesso zip senza utenti, richieste e chiave API: serve a far nascere un altro sito. **Azzera il sito** svuota il database e riparte dai contenuti del progetto (chiede password e la parola AZZERA; si sceglie cosa tenere). SiteGround fa comunque il suo backup giornaliero. Le pagine statiche si rigenerano da Sistema → Rigenera ora.

---

## Prima di andare online (VOICE)

- [ ] **Titolare del trattamento** in Privacy e cookie. I testi legali sono un modello per un sito senza tracciamento: falli vedere a chi segue la privacy per il consorzio.
- [ ] **Disclaimer UE** ufficiale EACEA per ES, SL, SR, SQ (Testi del sito → `eu_disclaimer`).
- [ ] **Emblema UE**: confrontalo con il file ufficiale EACEA e scegli "Co-funded" o "Funded" secondo il Grant Agreement.
- [ ] **Frase d'apertura** ("Take the floor") in ES, SL, SR, SQ: falla verificare ai partner.
- [ ] **News di esempio** e **date dei deliverable** (calcolate con M1 = gennaio 2027).
- [ ] **Contatti e social** in `config.php`.
- [ ] **Foto segnaposto**: la foto dell'apertura (si vede solo nei temi fotografici), quella della news di esempio e la galleria in bozza vanno sostituite con foto vere (Immagini e file → Dettagli → Sostituisci).
- [ ] **Traduzioni dei blocchi della home** in ES, SL, SR, SQ: per ora c'è l'inglese (segnato "automatica") tranne la frase d'apertura.
