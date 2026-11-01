# Temi di PhalbPress

Ogni tema è una cartella con:
- `theme.json` — nome, descrizione, palette e font suggeriti (ruoli dell'Identità visiva), forma dei pulsanti, `body_class` facoltativa, `hero_on` (`accent` o `ground`: il fondo su cui la frase grande dell'apertura è leggibile senza foto);
- `assets/theme.css` — l'aspetto: si appoggia a `_base/assets/base.css` e usa solo le variabili dell'identità per colori e font;
- facoltativi: `assets/theme.js`, `preview.webp` (720×450, immagine nella pagina Temi), e qualsiasi file di `_base/templates/` o `_base/partials/` da sostituire;
- in `theme.json` anche `layouts` (disposizioni di partenza delle pagine interne per tipo, `"*"` per tutti) e `motion` (movimento: vedi CLAUDE.md), con `layouts.site.header` per il modello di testata. Ogni tema deve reggere tutte le disposizioni di `Layouts::parts()` e tutti i modelli di testata.

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
| European Excellence | Night lab (rif. .eu Web Awards 2025) | Jost |
| Sustainability Champion | River bank (rif. .eu Web Awards 2025) | Figtree |
| SME Star | Studio desk (rif. .eu Web Awards 2025) | Work Sans |
| Cultural Ambassador | Color block stage (rif. .eu Web Awards 2025) | League Spartan + Figtree |
| People's Choice | Open mic (rif. .eu Web Awards 2025) | Bricolage Grotesque + Figtree |

Un sito non modifica mai i temi: le sue personalizzazioni stanno in `site/theme/` (stessi percorsi) e in `site/theme/assets/site.css`.
