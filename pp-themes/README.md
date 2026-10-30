# Temi di PhalbPress

Ogni tema è una cartella con:
- `theme.json` — nome, descrizione, palette e font suggeriti (ruoli dell'Identità visiva), forma dei pulsanti, `body_class` facoltativa;
- `assets/theme.css` — l'aspetto: si appoggia a `_base/assets/base.css` e usa solo le variabili dell'identità per colori e font;
- facoltativi: `assets/theme.js`, `preview.webp` (720×450, immagine nella pagina Temi), e qualsiasi file di `_base/templates/` o `_base/partials/` da sostituire.

`_base/` contiene la struttura comune (template, blocchi della home, testi, CSS e JS di base). Le cartelle che iniziano con `_` non compaiono tra i temi.

| Tema | Direzione (skill design-taste-frontend) | Font suggeriti |
|---|---|---|
| Tipografico | Type and nothing else (VOICE Europe) | Petrona |
| Istituzionale | The printed annual | Bodoni Moda + Jost |
| Editoriale | The page is the object | Libre Franklin + Source Serif 4 |
| Manifesto | Loud and flat | Barlow Condensed + Atkinson Hyperlegible Next |
| Documentario | The place carries it | Newsreader |
| Minimalista | The quiet instrument | Work Sans |
| Tech / Futurista | The well-kept board | League Spartan + Figtree |
| ACE | Faces and fields | Saira Condensed + Work Sans |
| Adrestia | The open door | Figtree |
| Ares 2.0 | Sail and sea | Instrument Sans |
| FINE | The label on the photo | Bricolage Grotesque + Source Serif 4 |
| Rebus | Lights down | Anton + Atkinson Hyperlegible Next |
| Sic2Sic | Route journal | Jost + Newsreader |

Un sito non modifica mai i temi: le sue personalizzazioni stanno in `site/theme/` (stessi percorsi) e in `site/theme/assets/site.css`.
