# Novità di PhalbPress

Ogni release ha una sezione `## X.Y.Z`: è il testo che i siti mostrano in Gestione → Aggiornamenti.

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
