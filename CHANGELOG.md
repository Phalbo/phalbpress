# Novità di PhalbPress

Ogni release ha una sezione `## X.Y.Z`: è il testo che i siti mostrano in Gestione → Aggiornamenti.

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
