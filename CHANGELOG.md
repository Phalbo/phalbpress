# Novità di PhalbPress

Ogni release ha una sezione `## X.Y.Z`: è il testo che i siti mostrano in Gestione → Aggiornamenti.

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
