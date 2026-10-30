# site/ — la parte del progetto

Qui va tutto ciò che è di un progetto: `config.sample.php` (poi `config.php`), `strings.php`, `seed.php`, `seed-media/`,
`theme/` (personalizzazioni valide con ogni tema), `assets/logo.svg`, `modules/` (moduli solo di questo sito).

Per un sito nuovo: copia la cartella `site/` di un progetto esistente (es. VOICE Europe o CWL) e cambiala,
oppure chiedi a Claude con la skill **phalbpress-nuovo-sito** allegando questo zip e i materiali del progetto.
Il motore (`core/`, `pp-admin/`, `pp-assets/`, `pp-themes/`, `index.php`, `cron.php`) si aggiorna da Gestione → Aggiornamenti e non tocca mai questa cartella.
