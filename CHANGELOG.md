# Novità di PhalbPress

Ogni release ha una sezione `## X.Y.Z`: è il testo che i siti mostrano in Gestione → Aggiornamenti.

## 1.2.1
- **Aggiornamenti dalla gestione**: nuova pagina Account → Aggiornamenti. Controlla le release su GitHub, scarica, verifica la firma, fa una copia di sicurezza completa e sostituisce solo il motore. Pulsante per tornare alla versione precedente. Con `cron.php` attivo arriva un'email agli admin quando esce una versione nuova.
- **Tema Manifesto su telefono**: l'elenco (paesi, partner) va a una colonna e il nome del sito nella testata va a capo invece di uscire dallo schermo.
- Gestione: il controllo dei contenuti programmati girava due volte a ogni pagina.
- `cron.php` lancia anche i lavori periodici dei moduli (hook `cron`).
