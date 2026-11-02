# Novità di PhalbPress

Ogni release ha una sezione `## X.Y.Z`: è il testo che i siti mostrano in Gestione → Aggiornamenti.

## 1.16.0
Tutti i temi rivisti con i contenuti veri di un sito (Ares 2.0), con le correzioni chieste tema per tema.

- **Temi**: tolti Istituzionale e Minimalista. Un sito che ne usava uno passa da solo a Tipografico; i contenuti non cambiano.
- **Provini per il cliente** (Temi, solo amministratori): "Genera i provini" crea una copia navigabile del sito per ogni tema in `/provini/`, con un indice da mandare al cliente.
  - Ogni copia ha i colori del tema, oppure quelli del sito.
  - Ci sono home, pagine, portfolio con le categorie, gli ultimi lavori e le ultime news.
  - Il sito pubblico non cambia e i provini non vanno nei motori di ricerca; si cancellano con un clic.
- **Varianti di colore di un tema**: un tema può proporre più combinazioni di colori e caratteri. Si guardano in anteprima e si applicano da Identità visiva, con "Torna ai colori di prima". Il tema Ares 2.0 ne ha quattro.
- **Identità visiva**, nuove opzioni valide per ogni tema:
  - **logo in testata**: su una testata scura si usa da solo il logo per fondi scuri; senza quel logo, il logo sta su un fondino rettangolare e mai più in un ovale;
  - **spazio tra le fasce**: compatto, come il tema, ampio;
  - **pagina in un riquadro centrale** sugli schermi larghi; le foto di apertura restano a tutta larghezza;
  - **testo sulle foto** dell'apertura e delle intestazioni: velo scuro dallo 0 all'80%, ombra leggera o fondino;
  - **foto dell'apertura e delle intestazioni** a colori, in bianco e nero o virate, separate da quelle degli elenchi.
- **Spazi**: meno spazio tra le fasce in tutti i temi. Due fasce bianche di seguito non sommano più i loro margini.
- **Apertura con più foto**: il velo del tema resta sopra la foto che scorre, così il testo bianco si legge. Nei temi dove la foto è un riquadro (es. Voci) i comandi stanno sul riquadro.
- **Barra dei contatti**: con le testate fisse non copre più il logo e il menu.
- **Elenco di contenuti**:
  - le foto delle schede sono quadrate di base, oppure 4:3, 3:2, 16:9, 3:4 o intere, così copertine e locandine non vengono tagliate;
  - colore delle foto e passaggio del mouse si scelgono per singola fascia;
  - "Scelti a mano" non ha più limite: si cerca il contenuto per titolo, si aggiunge con un clic e si riordina con le frecce. Gli 8 scelti di prima passano da soli nel campo nuovo.
- **Tendine lunghe** nella gestione (scegliere un contenuto tra tanti): una casella sopra per cercare.
- **Pagina dei contatti**: accanto al modulo i recapiti del sito (email, telefoni, PEC, sedi); la mappa occupa la sua colonna intera.
- **Numeri**: misure più contenute (Manifesto).
- Correzioni tema per tema:
  - **Tipografico**: apertura più bassa;
  - **Tech**: segno grafico dell'apertura a scelta (mosaico, puntini, barre, cerchi o nessuno), sparisce su telefono; niente riquadri decorativi nelle intestazioni;
  - **Documentario**: intestazioni senza il riquadro decorativo, con una linea che si disegna;
  - **Voci**: apertura più bassa e foto intera; nel portfolio niente maschera che tagliava le immagini;
  - **Rebus**: il fondino della seconda riga non copre più la prima;
  - **Sic2Sic**: apertura e titolo più misurati;
  - **Eccellenza**: testo dell'apertura più centrato;
  - **FINE**: pagine col testo a tutta larghezza;
  - **Adrestia**: il riquadro della dichiarazione ritrova il suo spazio interno.
- Mappa OpenStreetMap: ammessa anche nel sito statico e nell'anteprima. Nel sito statico va aggiunta a mano nel tuo `.htaccess`, che l'aggiornamento non tocca: confrontalo con quello nuovo.

## 1.15.3
- **Fasce: cambiare tipo non cancella più i contenuti.**
  - Riquadri con icona, Passi, Domande frequenti e Numeri si passano le voci: titolo, testo, pulsante e link vanno al tipo nuovo, anche nelle altre lingue.
  - I campi che il tipo nuovo non usa restano salvati: tornando al tipo di prima si ritrova tutto com'era.
  - Lo stesso vale per ogni scheda: i valori dei campi che il modulo non mostra non si perdono più al salvataggio.
- Le fasce che avevano già perso i testi cambiando tipo li ritrovano da sole con l'aggiornamento, dalle versioni precedenti.

## 1.15.2
- **Sito del progetto da zip** (Aggiornamenti, solo amministratori): si carica una versione nuova della cartella `site/` senza FTP.
  - `config.php` non si tocca; i file che nella versione nuova non ci sono più vengono tolti; prima viene fatta una copia della versione di adesso (in Backup e modelli).
  - Se `config.php` è una copia completa (fatta a mano), una casella lo riscrive come lo scrive l'installazione: tiene database e indirizzi, tutto il resto viene dal `config.sample.php` dello zip. Il vecchio resta in `data/backups/`.
  - Serve la password; si spegne con `'site_upload' => false` nel config.
- Aggiornamenti del motore: prima di aggiornare si copia solo il database. Immagini e file caricati l'aggiornamento non li tocca, quindi non si copiano più ogni volta.

## 1.15.1
- **Azzera il sito** (Backup e modelli, solo amministratori): riporta il sito come appena installato senza toccare `site/` e `config.php`. Si sceglie:
  - se fare prima un backup completo (spento di base);
  - cosa tenere: immagini e file caricati, i file da importare da WordPress, i backup, gli altri utenti (chi azzera resta sempre, con la sua password);
  - da dove ripartire: contenuti iniziali del sito, contenuti di esempio o vuoto.

  Serve la password e la parola AZZERA. Il sito ripartito è nascosto ai motori di ricerca; se ci sono file da importare si apre Importa da WordPress.
- Importa da WordPress: le foto già importate si ritrovano anche dopo aver azzerato il sito (o rifatto il database) e non vengono rifatte né duplicate. Il collegamento sta anche in `data/wp-media-map.json`.

## 1.15.0
Le opzioni nate per il sito di Ares 2.0 ora stanno nel motore e valgono con ogni tema.

- **Gestione più leggera**: Immagini e file, SEO, Redirect e Accessibilità hanno filtri e pagine (non caricano più centinaia di righe insieme; "Mostra tutto" resta).
- **Scoraggia i motori di ricerca** (SEO): attivo a ogni nuova installazione, con un avviso in cima alla gestione finché non si toglie. Quando è attivo le pagine hanno `noindex` e robots.txt chiude tutto.
- **Testata**:
  - barra sottile in alto con telefoni, email e social (Impostazioni, si spegne);
  - i social si mettono nella barra, nel footer, in tutti e due, in testata o da nessuna parte;
  - i sottomenu si aprono passandoci sopra, con un terzo livello a lato;
  - altezza del logo regolabile (Identità visiva).
- **Footer**: colonne con ragione sociale, email, telefoni, PEC, partita IVA, menu del footer e sedi (Impostazioni); logo per fondi scuri, immagine di certificazioni, nota e riga legale. L'anno del copyright si aggiorna da solo, anche nel sito statico.
- **Apertura della home**:
  - altezza (bassa, media, alta) e grandezza del titolo;
  - con più foto si scelgono pallini, frecce, tutti e due o niente, ogni quanti secondi cambiano e se sfumano o scorrono;
  - niente più barra "ferma le foto": c'è un piccolo pulsante di pausa, e le foto si fermano passandoci sopra o con la tastiera.
- **Fasce**:
  - nuove: Riquadri con icona (fino a 6, con icone disegnate per PhalbPress e un pulsante ciascuno), Squadra (foto e ruoli), Elenco di persone (solo testo, a gruppi), Mappa;
  - Dichiarazione centrata, con il titolo più piccolo;
  - Elenco di contenuti a 3 o 4 colonne, con i contenuti scelti a mano;
  - Loghi con la velocità e un link sotto (es. "La lista completa").
- **Fasce nelle pagine**: ogni fascia può andare in fondo a una pagina (campo "Pagina"), per esempio squadra, numeri e loghi in "Chi siamo" o la mappa in "Contatti".
- **Mappa OpenStreetMap**: si carica solo dopo il consenso (è nell'elenco dei servizi esterni della cookie policy); senza consenso resta il link alla mappa.
- **Foto e intestazioni** (Identità visiva): foto degli elenchi a colori, in bianco e nero o virate nel colore principale; passandoci sopra prendono colore o si ingrandiscono; intestazioni delle pagine più basse ("compatte"). Le foto di apertura e intestazioni coprono tutta la larghezza anche sugli schermi molto larghi.
- **Portfolio**:
  - le categorie hanno un ordine e si possono nascondere da filtri e menu ("Nascondi nei filtri e nel menu"), restando raggiungibili;
  - con aree e divisioni (es. Ricerca, Comunicazione) in alto ci sono solo le aree, grandi, e sotto le divisioni dell'area aperta;
  - nel menu, la voce "Portfolio con le categorie" si apre sulle aree e le loro divisioni;
  - l'intestazione usa la foto della categoria, mai quella dell'ultimo lavoro;
  - Riquadri ora a 4 colonne, con le foto quadrate intere; due disposizioni in più: Griglia (schede 4:3, quattro per riga) ed Elenco con miniatura (foto quadrata piccola a sinistra, titolo, cliente e anno);
  - la scheda del singolo lavoro ha titoli più misurati.
- **Pagine**: nuova disposizione del testo "A colonne", per elenchi lunghi divisi in gruppi.
- **News**: la foto sta nella colonna del testo, a misura.
- Importa da WordPress: i link a pagine saltate (es. il vecchio archivio delle news) portano dove la pagina è stata mandata; l'ordine delle categorie di WordPress viene mantenuto.
- Tema Tech: il segno a quadrati dell'apertura non sostituisce più l'apertura comune, quindi ha anche foto a scorrimento, altezza e grandezza del titolo.

## 1.14.0
- **Importa da WordPress** (Gestione → Importa da WordPress, solo amministratori): porta in PhalbPress pagine, articoli, tipi personalizzati (es. un portfolio), categorie, foto e file, pagine madri, menu, titoli e descrizioni di Yoast.
  - Si mettono in `data/wp-import/` l'esportazione di WordPress (.xml), e se ci sono il dump del database (.sql) e uno zip della cartella uploads. Dal dump si leggono solo le traduzioni di WPML e i redirect del plugin Redirection, mai utenti e password.
  - Prima la **prova a secco**: dice cosa verrebbe importato, in che lingue, quali file mancano e cosa del vecchio costruttore di pagine si toglie.
  - Il testo di WPBakery, Jupiter e Gutenberg diventa HTML pulito: titoli, testo, foto, gallerie, video (col consenso), pulsanti, numeri dei contatori. Righe, colonne, slider ed elenchi automatici si tolgono: l'impaginazione la fa il tema.
  - Le traduzioni di WPML diventano le lingue dello stesso contenuto. Le foto si importano una volta sola, ridotte e in WebP come al caricamento.
  - Ogni vecchio indirizzo (anche `?lang=en`, `?p=12`, `?page_id=34`) porta con un redirect 301 a quello nuovo; i link interni nei testi vengono riscritti.
  - Lavora a passi di 20 secondi e riprende da dove era rimasto; si può rilanciare (aggiorna, non duplica) o annullare. Anche da riga di comando: `php cron.php wpimport plan|run|reset|undo`.
  - Il sito decide i tipi e cosa saltare in `config['wp_import']` (una pagina saltata può portare alla home, a una sezione o a una categoria), e può ritoccare ogni contenuto col filtro `wp_import_entry`.
  - Le categorie portano la gerarchia di WordPress; i contatori animati diventano numeri scritti; le foto tengono la misura scelta nel vecchio sito.
- **Portfolio (storie con categorie)**:
  - ogni categoria usata ha la sua pagina (`/it/portfolio/ricerca/`), anche nel sito statico e senza JavaScript, con le sottocategorie (campo "Categoria madre");
  - sopra l'elenco i filtri sono link alle categorie, con quanti lavori ha ciascuna; negli elenchi lunghi "Mostra altri" carica i successivi;
  - quattro disposizioni nuove dell'elenco, scelte per tutto il portfolio o **per singola categoria** (anche in Modifica visuale): Riquadri, Copertine, Schermi (16:9), Indice (righe con anno, la foto compare passandoci sopra);
  - la pagina del singolo lavoro ha quattro disposizioni: Ritratto (quella di prima, per le storie di persone), Scheda (dati in una colonna fissa a lato), Vetrina (la foto grande su fondo scuro), Racconto (immagini a tutta larghezza);
  - i dati del lavoro (cliente, attività…) sono i campi con `'public' => chiave del testo`, più categorie e anno; in fondo altri lavori della stessa categoria, precedente e successivo.
- Identità visiva: "Il logo contiene già il nome" (in testata il nome resta solo per i lettori di schermo).
- Tema Ares 2.0: categorie del portfolio come il menu, griglie fitte, scheda del lavoro con i dati a lato; le foto dei lavori restano in blu e prendono i loro colori passandoci sopra. Tema Istituzionale: i dati del lavoro stanno nell'asse.
- Gallerie nel testo: non hanno più il rientro a sinistra degli elenchi.
- Redirect: possono distinguere i parametri dei vecchi siti WordPress (`lang`, `p`, `page_id`, `post_type`); `/?lang=en` porta alla home in quella lingua.
- Pagine: l'indice "In questa pagina" non mostra più `&amp;` al posto di `&` nei titoli.

## 1.13.1
- Gestione: la scheda di pagine, news, storie ed eventi non scrive più avvisi nel registro degli errori di PHP (campi “Pagina madre”, “Punto focale” e “Disposizione” senza i valori di partenza).

## 1.13.0
- **Gallerie in news e pagine**: pulsante "Galleria" nell'editor, si scelgono le foto e il testo alternativo di ognuna.
- **Foto ingrandite (lightbox)**: un clic su una foto della galleria o del testo la apre grande, con frecce, tastiera e scorrimento col dito.
- **Apertura a scorrimento**: nell'apertura della home si possono mettere più foto, che si alternano ogni 6 secondi (pulsante pausa; ferme con "meno movimento").
- **Punto focale delle foto**: in pagine, news, storie ed eventi si sceglie dove sta il soggetto (in alto, in basso, a sinistra, a destra), così resta visibile quando la foto viene tagliata.
- **Pagine madri**: una pagina può stare sotto un'altra (es. Progetti → Progetto 1). La madre mostra l'elenco delle figlie, le figlie il percorso sopra il titolo.
- **Eventi**: nuovo tipo con data di inizio e fine, ora, luogo e link di iscrizione; pagina degli eventi (prossimi e passati), pagina del singolo evento con "Aggiungi al calendario" (.ics), blocco "Eventi" per la home. Chi non li vuole mette `'events' => false` nel config.
- **Newsletter**: blocco per la home con email e consenso; le iscrizioni stanno in Richieste e si scaricano in CSV.
- **RSS**: un feed delle news per ogni lingua (`/feed/it.xml`…), segnalato nelle pagine.
- **Nuovi blocchi della home**: loghi (fermi o a scorrimento lento), citazione, domande frequenti.
- **Icone social** nella testata, nel piè di pagina o in tutti e due (Impostazioni).
- Tutto vale con ogni tema e con ogni disposizione, anche nel sito statico.

## 1.12.0
- **Modelli di testata**, uguali per tutti i temi: *Barra* (logo a sinistra, menu a destra), *Logo a destra*, *Centrata* (logo al centro e menu sotto), *Menu a pulsanti* (fascia colorata con le voci come pulsanti), *Essenziale* (solo logo e Menu, le voci si aprono a tutto schermo, grandi). Ogni tema parte dal suo; si cambia per tutto il sito cliccando la testata in **Modifica visuale**. Su telefono tutti diventano logo, ricerca, lingue e Menu.
- **Pagine interne ridisegnate in tutti i temi**:
  - ACE, Adrestia, Ares 2.0, FINE, Rebus e Sic2Sic seguono le pagine interne dei siti originali: striscia di ritratti in bianco e nero (ACE), forma morbida e cornici arrotondate (Adrestia), foto in bicromia col taglio della vela (Ares 2.0), etichetta blu sulla foto e fasce in diagonale (FINE), sala buia con la frase evidenziata (Rebus), striscia di paesaggio e diario a grandi foto di tappa (Sic2Sic).
  - Tipografico, Istituzionale, Editoriale, Manifesto, Documentario, Minimalista e Tech hanno intestazioni, testi, elenchi, documenti e partner nel loro carattere (capolettera, filetti doppi, blocchi pieni, foto al vivo, tessere…).
  - Ogni tema ha le sue disposizioni di partenza e regge tutte le altre, anche senza foto.
- Corretto: l'intestazione "al vivo" senza foto non copre più il titolo con la decorazione.

## 1.11.0
- **Disposizioni delle pagine interne**: intestazione, testo ed elenchi si scelgono da un piccolo menu, uguale per tutti i temi.
  - Intestazione: a sinistra, centrata, divisa con foto, foto al vivo, essenziale. Senza foto le disposizioni con la foto mostrano una decorazione del tema.
  - Testo di pagine e news: colonna di lettura, larga, con barra laterale (indice, condivisione, altre notizie).
  - News: griglia di schede, prima in evidenza, elenco con miniature, mosaico. Storie: alternate, ritratti, mosaico. Risultati: schede o tabella. Partner: schede, loghi, elenco.
  - Ogni tema parte con le sue; il sito le cambia per tipo di pagina e una singola pagina o news può fare eccezione. Si sceglie in **Modifica visuale** (ora c'è "Pagina" in alto: si clicca l'intestazione o il contenuto) o nella scheda della pagina.
- **Percorso sopra il titolo** (Home / Notizie) e **altre notizie** in fondo a ogni news.
- **Pagine interne ridisegnate** nei temi European Excellence, Sustainability Champion, SME Star, Cultural Ambassador e People's Choice, con le scelte di partenza prese dai siti di riferimento.
- **Movimento in tutti i temi**: comparse a cascata allo scroll, apertura e intestazioni che entrano, numeri che contano, sottolineature che si allungano. Ogni tema ha il suo carattere (dal basso, di lato, a scatto, in dissolvenza…) e "riduci il movimento" del sistema lo spegne del tutto.
- Corretto: nessuno scorrimento orizzontale sui telefoni con le entrate di lato.

## 1.10.0
- **Cinque temi nuovi**, ispirati ai vincitori dei .eu Web Awards 2025: l'apertura ha la **foto protagonista** e il titolo di misura (non più la frase gigante), con animazioni in entrata, comparse a cascata allo scroll, numeri che contano ed effetti al passaggio del mouse. Rispettano "riduci il movimento" e senza JavaScript si vede tutto.
  - **European Excellence**: foto scura a tutto schermo con zoom lento, titolo centrato, schede di vetro che salgono sopra la foto, card delle news con la data in etichetta (Jost).
  - **Sustainability Champion**: foto con angoli larghi e bordo inferiore curvo, numeri in tessere verde acqua che galleggiano, fasce e footer che si aprono con una curva (Figtree).
  - **SME Star**: apertura divisa testo/foto con blocco verde sfalsato, numeri in cerchi, filo verde sopra i titoli, ultima voce del menu come pulsante (Work Sans).
  - **Cultural Ambassador**: colori pieni, forme geometriche che scorrono sulla foto, titolo in fascia nera, nastro di forme sopra le fasce colorate, ombre colorate sulle foto (League Spartan + Figtree).
  - **People's Choice**: foto in una forma morbida che cambia lentamente, titoli di sezione in maiuscolo, titolo a contorno che scorre nelle fasce scure, pulsante verde nella testata (Bricolage Grotesque + Figtree).
- Si provano da Gestione → Temi "con contenuti e foto di esempio"; reggono tutte le scelte della Modifica visuale.

## 1.9.0
- **Siti personali e portfolio**: in Sistema → Impostazioni del sito, nuova parte "Chi c'è dietro il sito": un'organizzazione (come prima) o **una persona**. Per una persona i dati strutturati che leggono Google e i motori AI diventano quelli di un profilo (`Person` con professione, per chi lavora, città, formazione, competenze e i social come profili collegati) e la home diventa una pagina profilo. Si può impostare anche da `config['owner']`.
- **Siti con l'originale non in inglese** (es. un sito personale scritto in italiano): l'avviso per un testo non ancora tradotto diceva sempre "stai leggendo la versione inglese", e i documenti senza file tradotto dicevano "in inglese". Ora dicono la lingua originale vera ("stai leggendo la versione originale (Italiano)", "in Italiano"). Testi nuovi `fallback_notice_lang` e `doc_in_lang` in Testi del sito; sui siti in inglese non cambia niente.
- **Elenco di storie nella home**: segue lo stesso ordine della pagina Storie (l'ordine messo a mano, poi la data), invece della sola data.
- **Banner dei cookie senza motivo**: un semplice link a YouTube (nel testo o nel pulsante di una fascia, es. "Apri il canale") faceva credere alla scansione che nel sito ci fossero video incorporati, e compariva il banner. Ora contano solo i video veri (`[video:…]`, il campo Video delle storie e la fascia Video). Rifai la scansione da Privacy e cookie.
- **Ricerca**: il tipo di ogni risultato è nella lingua della pagina ("Page", "News", il nome della sezione Storie…) invece del nome italiano della gestione ("pagina", "storia"), e le date sono scritte per esteso come nel resto del sito invece che "2026-10-12".
- **Impostazioni del sito e sito statico**: dopo aver salvato (email, social…) le pagine statiche venivano rigenerate con i valori di prima, e il footer restava vecchio fino al salvataggio successivo. Ora escono subito con quelli nuovi.
- **Titoli con una parola lunga** (es. "Showroomdummies") nelle storie: in Istituzionale la parola allargava la colonna e la pagina scorreva di lato; con "riduci il movimento" attivo nel sistema (o in un tema con transizioni) il titolo non si riduceva affatto e usciva dalla pagina. Ora le colonne delle griglie non si allargano per una parola, il calcolo ignora le transizioni e si rifà quando cambiano la larghezza della colonna o i font.
- Cookie policy: il cookie della gestione è "usato da chi cura il sito" invece che "dal gruppo di progetto", così il testo va bene anche per un sito personale.

## 1.8.0
- **Installazione guidata**: niente più `config.php` da copiare e compilare a mano. `install.php` controlla il server (versione di PHP, estensioni, cartelle scrivibili) e dice cosa manca; poi chiede lo **zip del sito del progetto** (o fa un sito nuovo con nome, lingue e tema), i dati del **database** con la prova della connessione e messaggi chiari (prefisso SiteGround, permessi), e l'indirizzo della gestione. Scrive da sola `site/config.php`.
- **Con cosa parte il sito**: i contenuti del progetto, i **contenuti di esempio** con le foto segnaposto, un modello di un altro sito, o vuoto. Più l'opzione per mettere le foto segnaposto dove mancano.
- **Dopo l'installazione**, tutto facoltativo: comando del cron da copiare, email di prova, link all'accesso in due passaggi e alle impostazioni, e un pulsante che cancella `install.php`.
- **Installatore in un file** (`phalbpress-installa.php`, allegato a ogni release): si carica solo quello nella cartella vuota del sito. Scarica l'ultima versione da GitHub, controlla la firma, scompatta tutto e porta all'installazione guidata.
- **Impostazioni del sito** (Sistema → Impostazioni del sito): email di contatto e del mittente, social, finanziatore, traduzione automatica (servizio, chiave, glossario) si cambiano dalla gestione, senza toccare il file. Nel file restano database e struttura.

## 1.7.0
- **Foto segnaposto nel sito vero** (Immagini e file → "Metti le foto segnaposto"): ogni posto ancora senza foto (apertura, news, storie, gallerie) riceve una foto di esempio con le misure giuste stampate sopra. Ognuna è un file a sé, con il nome che inizia per "segnaposto": la cambi una alla volta con **Sostituisci** e la foto vera compare dove stava il segnaposto. Le foto già messe non si toccano mai.
- **Titoli senza trattino**: le parole dei titoli non vanno più a capo spezzate ("bam-/bini"). Se una parola è più larga della colonna, il titolo si riduce quanto basta per farla stare intera (senza JavaScript, come ultima risorsa la parola va a capo senza allargare la pagina).

## 1.6.0
- **Temi con contenuti e foto di esempio**: in Gestione → Temi ogni tema ha il pulsante "con contenuti e foto di esempio". Si apre l'anteprima del tema con news, storie, partner, documenti, galleria e foto di un progetto inventato, così si vede com'è il tema anche su un sito che ha ancora poche foto. Nell'anteprima si può girare per le pagine e cambiare lingua. Il sito non cambia: i contenuti di esempio stanno in un database a parte (`data/demo-preview.sqlite`), creato al primo uso.

## 1.5.0
Revisione di tutti i 13 temi (accessibilità, impaginazione a computer e telefono, resa con foto vere).
- **Apertura senza foto**: in ACE, Adrestia, Ares 2.0, FINE, Sic2Sic e Tipografico la prima riga del titolo era bianca su bianco quando l'apertura non aveva la foto. Ora ogni tema dichiara su che fondo vanno i colori della frase grande, e un'apertura con fondo "Automatico" va lì (di solito il colore principale). Anche il controllo dei contrasti in Identità visiva misura la frase grande sul fondo giusto. In Istituzionale il titolo dell'apertura era verde su verde: corretto.
- **Testi attenuati sempre leggibili**: il grigio di date, sottotitoli e note ora non scende mai sotto il contrasto minimo WCAG AA, con qualsiasi palette. Sulle fasce colorate è più deciso. In Minimalista i sottotitoli delle pagine interne erano quasi invisibili.
- **Fasce con immagine di fondo**: nei temi a colonne (Istituzionale, ACE, Rebus…) l'immagine copriva solo una colonna; ora copre tutta la fascia. Con "larghezza stretta" titolo e testo partono dalla stessa linea.
- **Anello di focus** visibile anche sulle fasce colorate e sulle foto (prende il colore del testo). Selezione del testo nei colori del tema.
- **Video**: il riquadro d'attesa è scuro in tutti i temi e il pulsante "Play" si legge sempre come pulsante.
- **Modulo di contatto**: nei temi con pulsanti tondi i campi diventavano pillole e il messaggio un'ellisse; ora hanno angoli morbidi. Casella della privacy più grande.
- **Risultati**: i documenti senza file mostrano "In arrivo" invece di un cerchietto; date e pulsanti allineati tra un gruppo e l'altro.
- **Storie**: la foto delle storie alternate non è più più grande delle altre. **Elenco a schede**: con tre contenuti niente colonna vuota. Il dato a lato della Dichiarazione rispetta gli a capo.
- Correzioni per tema: loghi invisibili in testata (Editoriale, Manifesto, Tech, Tipografico), menu su telefono in Documentario, numeri tagliati in Tipografico, partner e indice in Istituzionale, colonna in Minimalista, galleria e taglio del footer in Ares 2.0 (con le foto virate nel blu come da direzione), invito all'azione in Adrestia, tessere vuote in Tech, evidenziatore in Rebus, strada tratteggiata in Sic2Sic, misura del testo in Manifesto e Tech.

## 1.4.2
- **Numeri della home** (blocco Numeri): i numeri lunghi come “12.000” o “30.000” uscivano dalla loro colonna e si sovrapponevano a quello accanto, in tutti i temi. Ora il numero si riduce quanto basta per stare nella colonna.
- **Footer su telefono**: un indirizzo email lungo nei contatti faceva scorrere la pagina di lato; ora va a capo.

## 1.4.1
- **Installazione da Windows e macOS**: in `core/modules/themes/` c'erano `Visual.php` e `visual.php`, due file con lo stesso nome a parte la maiuscola. Scompattando lo zip sul computer uno sovrascriveva l'altro e il sito dava errore 500 (`Failed opening required … Visual.php`). La schermata della Modifica visuale ora si chiama `visual-view.php`, e la creazione delle release si blocca se due file hanno lo stesso nome a parte le maiuscole.
- Chi ha già un sito con l'errore: carica `core/modules/themes/Visual.php` dal pacchetto con il File Manager del server, poi aggiorna da Gestione → Aggiornamenti.

## 1.4.0
- **Sei temi nuovi** (Gestione → Temi, prova con "Anteprima con i tuoi colori" prima di attivarli):
  - **ACE**: ritratti in bianco e nero, il colore solo dai campi pieni rosso, verde e azzurro; titoli condensati grandi. Per progetti sui diritti e contro le discriminazioni.
  - **Adrestia**: blu notte e verde menta, riquadri arrotondati, foto incorniciate. Per associazioni e reti civiche con più aree di attività.
  - **Ares 2.0**: foto virate nel blu, un taglio diagonale come una vela tra apertura, pagine e footer, griglie da portfolio. Per comunicazione, ricerca e formazione.
  - **FINE**: blu reale e arancio, titolo in un pannello pieno sopra la foto, serif da libro per leggere. Per istruzione, università e orientamento.
  - **Rebus**: fondo scuro, foto a tutta pagina, maiuscolo condensato, una riga evidenziata in magenta. Per campagne di sensibilizzazione e progetti con video e fumetti.
  - **Sic2Sic**: carta color paglia, rosso da segnaletica, le tappe su una strada tratteggiata. Per ambiente, territorio, cammini e itinerari.
- Nuovi font nel motore: Anton e Saira Condensed (licenza OFL).

## 1.3.2
- **Modifica visuale**: pulsante **Elimina** nel pannello della fascia (chiede conferma, toglie la fascia in tutte le lingue).

## 1.3.1
- **Aggiornamenti**: dopo "Aggiorna" alcuni server (SiteGround compreso) continuavano a usare il codice di prima che tenevano in memoria (OPcache), perché i file nuovi avevano la stessa data di quelli vecchi. Ora gli zip delle release hanno la data vera e, dopo la sostituzione, ogni file nuovo prende la data del momento: il server li rilegge subito. Se il sito resta indietro lo stesso, la pagina Aggiornamenti lo dice e spiega come sistemare.

## 1.3.0
- **Modifica visuale** (Sito → Modifica visuale, anche dalla pagina Temi): l'anteprima della home a sinistra, il pannello a destra. Clicchi una fascia e ne cambi il fondo (i colori dell'Identità o un colore libero, con il testo che diventa nero o bianco da solo), l'immagine di fondo con il velo scuro o chiaro e la parte da tenere, il **parallasse**, lo spazio sopra e sotto, la larghezza. Le fasce si spostano (frecce o trascinando), si nascondono, se ne aggiungono di nuove. Si salva da solo; anteprima a computer e telefono, in ogni lingua.
- Le stesse scelte sono anche nella scheda di ogni blocco, e valgono con tutti i temi.
- Temi: nelle fasce colorate il testo attenuato si calcola dal colore della fascia (in Minimalista e altri era un grigio illeggibile sul fondo scuro); i titoli con parole più larghe dello schermo vanno a capo invece di allargare la pagina su telefono.
- Gestione: i menu a tendina con un valore vuoto non mostrano più due voci vuote; nuovo tipo di campo `color` per i campi personalizzati.

## 1.2.2
- **Identità visiva**: i colori tornano in una griglia leggibile; le etichette non si sovrappongono più (una regola della pagina Temi con lo stesso nome le schiacciava a 22 px).

## 1.2.1
- **Aggiornamenti dalla gestione**: nuova pagina Account → Aggiornamenti. Controlla le release su GitHub, scarica, verifica la firma, fa una copia di sicurezza completa e sostituisce solo il motore. Pulsante per tornare alla versione precedente. Con `cron.php` attivo arriva un'email agli admin quando esce una versione nuova.
- **Tema Manifesto su telefono**: l'elenco (paesi, partner) va a una colonna e il nome del sito nella testata va a capo invece di uscire dallo schermo.
- Gestione: il controllo dei contenuti programmati girava due volte a ogni pagina.
- `cron.php` lancia anche i lavori periodici dei moduli (hook `cron`).
