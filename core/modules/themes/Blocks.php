<?php
/**
 * Blocchi della home: contenuti di tipo "block" (Gestione → Blocchi della home), in ordine, ognuno con un "tipo".
 * Si traducono come tutto il resto. Ogni tema li disegna con pp-themes/<tema>/partials/blocks/<tipo>.php
 * (se manca, quello di _base). Campi comuni: tono (colore della fascia) e ancora.
 */
final class Blocks
{
    /** Tipi di blocco: etichetta, uso dei campi standard (title, excerpt, body, image), campi propri. */
    public static function kinds(): array
    {
        $tr = fn($label, $type = 'text', $hint = '') => ['label' => $label, 'type' => $type, 'translatable' => true, 'hint' => $hint];
        $k = [
            'hero' => ['label' => 'Apertura', 'uses' => ['title' => 'Frase grande, prima riga', 'excerpt' => 'Frase sotto', 'image' => 'Foto (i temi fotografici la mostrano grande)'],
                'fields' => ['line2' => $tr('Frase grande, seconda riga', 'text', 'facoltativo'), 'cta_label' => $tr('Pulsante: testo'),
                    'cta_url' => ['label' => 'Pulsante: dove porta', 'hint' => 'slug di una pagina (es. leadership-labs), una sezione (news) o https://…'],
                    'switch' => ['label' => 'Mostra la frase in tutte le lingue, con i pulsanti per cambiarla', 'type' => 'checkbox'],
                    'switch_label' => $tr('Etichetta dei pulsanti lingua', 'text', 'es. “Dillo in”'),
                    'slides' => ['label' => 'Altre foto (scorrono)', 'type' => 'textarea', 'hint' => 'facoltativo: un percorso per riga, da “Copia percorso” in Immagini e file. La prima resta la Foto qui sopra'],
                    'slides_alts' => $tr('Descrizioni delle altre foto', 'textarea', 'una per riga, nello stesso ordine'),
                    'slides_nav' => ['label' => 'Foto che scorrono: comandi', 'type' => 'select', 'options' => ['dots' => 'Puntini sotto', 'arrows' => 'Frecce ai lati', 'both' => 'Puntini e frecce', 'none' => 'Nessuno']],
                    'slides_time' => ['label' => 'Foto che scorrono: secondi per foto', 'type' => 'number', 'hint' => 'da 3 a 20 (di base 6)'],
                    'slides_fx' => ['label' => 'Foto che scorrono: passaggio', 'type' => 'select', 'options' => ['fade' => 'Dissolvenza', 'slide' => 'Scorrimento di lato']],
                    'height' => ['label' => 'Altezza dell’apertura', 'type' => 'select', 'options' => ['' => 'Come il tema', 'short' => 'Bassa', 'medium' => 'Media', 'tall' => 'Alta, quasi tutto lo schermo']],
                    'title_size' => ['label' => 'Grandezza della frase', 'type' => 'select', 'options' => ['' => 'Come il tema', 's' => 'Piccola', 'm' => 'Media', 'l' => 'Grande']]]],
            'statement' => ['label' => 'Dichiarazione', 'uses' => ['title' => 'Titolo', 'excerpt' => 'Testo'],
                'fields' => ['fact' => $tr('Dato o frase a lato', 'textarea'),
                    'align' => ['label' => 'Impaginazione', 'type' => 'select', 'options' => ['' => 'Come il tema (titolo a sinistra, testo a destra)', 'center' => 'Centrata in un riquadro, titolo di misura']]]],
            'icons' => ['label' => 'Riquadri con icona', 'uses' => ['title' => 'Titolo (facoltativo)', 'excerpt' => 'Introduzione (facoltativa)'],
                'fields' => self::iconFields($tr)],
            'steps' => ['label' => 'Passi', 'uses' => ['title' => 'Titolo (facoltativo, per i lettori di schermo)'],
                'fields' => ['s1_t' => $tr('Passo 1: frase'), 's1_d' => $tr('Passo 1: testo', 'textarea'), 's2_t' => $tr('Passo 2: frase'), 's2_d' => $tr('Passo 2: testo', 'textarea'),
                    's3_t' => $tr('Passo 3: frase'), 's3_d' => $tr('Passo 3: testo', 'textarea'), 's4_t' => $tr('Passo 4: frase'), 's4_d' => $tr('Passo 4: testo', 'textarea')]],
            'collection' => ['label' => 'Elenco di contenuti', 'uses' => ['title' => 'Titolo', 'excerpt' => 'Introduzione'],
                'fields' => ['source' => ['label' => 'Cosa mostra', 'type' => 'select', 'options' => array_map(fn($t) => $t['label'], array_diff_key(PP::$config['types'] ?? [], ['block' => 1, 'page' => 1]))],
                    'limit' => ['label' => 'Quanti (0 = tutti)', 'type' => 'number'],
                    'style' => ['label' => 'Aspetto', 'type' => 'select', 'options' => ['list' => 'Elenco', 'cards' => 'Schede con immagine']],
                    'cols' => ['label' => 'Schede per riga', 'type' => 'select', 'options' => ['' => 'Automatico', '3' => '3', '4' => '4']],
                    'pick1' => ['label' => 'Scelti a mano: 1', 'type' => 'entry', 'entry_type' => '*', 'hint' => 'facoltativo: se scegli dei contenuti, si mostrano questi e in quest’ordine, al posto degli ultimi'],
                    'pick2' => ['label' => 'Scelti a mano: 2', 'type' => 'entry', 'entry_type' => '*'], 'pick3' => ['label' => 'Scelti a mano: 3', 'type' => 'entry', 'entry_type' => '*'],
                    'pick4' => ['label' => 'Scelti a mano: 4', 'type' => 'entry', 'entry_type' => '*'], 'pick5' => ['label' => 'Scelti a mano: 5', 'type' => 'entry', 'entry_type' => '*'],
                    'pick6' => ['label' => 'Scelti a mano: 6', 'type' => 'entry', 'entry_type' => '*'], 'pick7' => ['label' => 'Scelti a mano: 7', 'type' => 'entry', 'entry_type' => '*'],
                    'pick8' => ['label' => 'Scelti a mano: 8', 'type' => 'entry', 'entry_type' => '*'],
                    'more_label' => $tr('Link “vedi tutti”: testo', 'text', 'facoltativo')]],
            'figures' => ['label' => 'Numeri', 'uses' => ['title' => 'Titolo', 'excerpt' => 'Testo'],
                'fields' => ['n1_v' => ['label' => 'Numero 1'], 'n1_l' => $tr('Numero 1: cosa conta'), 'n2_v' => ['label' => 'Numero 2'], 'n2_l' => $tr('Numero 2: cosa conta'),
                    'n3_v' => ['label' => 'Numero 3'], 'n3_l' => $tr('Numero 3: cosa conta'), 'n4_v' => ['label' => 'Numero 4'], 'n4_l' => $tr('Numero 4: cosa conta')]],
            'latest' => ['label' => 'Ultime news e documenti', 'uses' => ['title' => 'Titolo'],
                'fields' => ['limit' => ['label' => 'Quante news', 'type' => 'number'], 'docs' => ['label' => 'Mostra anche i documenti', 'type' => 'checkbox']]],
            'text' => ['label' => 'Testo', 'uses' => ['title' => 'Titolo', 'body' => 'Testo']],
            'gallery' => ['label' => 'Galleria', 'uses' => ['title' => 'Titolo', 'excerpt' => 'Introduzione'],
                'fields' => ['images' => ['label' => 'Immagini', 'type' => 'textarea', 'hint' => 'un percorso per riga, da “Copia percorso” in Immagini e file'],
                    'alts' => $tr('Descrizioni delle immagini', 'textarea', 'una per riga, nello stesso ordine')]],
            'cta' => ['label' => 'Invito all’azione', 'uses' => ['title' => 'Titolo', 'excerpt' => 'Testo'],
                'fields' => ['cta_label' => $tr('Pulsante: testo'), 'cta_url' => ['label' => 'Pulsante: dove porta', 'hint' => 'slug di una pagina, una sezione o https://…']]],
            'video' => ['label' => 'Video', 'uses' => ['title' => 'Titolo', 'excerpt' => 'Testo'],
                'fields' => ['video' => ['label' => 'Video YouTube', 'hint' => 'ID o link']]],
            'logos' => ['label' => 'Loghi dei partner', 'uses' => ['title' => 'Titolo (facoltativo)'],
                'fields' => ['motion' => ['label' => 'Movimento', 'type' => 'select', 'options' => ['' => 'Scorrono piano', 'still' => 'Fermi in griglia']],
                    'speed' => ['label' => 'Velocità dello scorrimento', 'type' => 'select', 'options' => ['' => 'Normale', 'slow' => 'Lenta', 'slower' => 'Molto lenta', 'fast' => 'Veloce']],
                    'more_label' => $tr('Link sotto i loghi: testo', 'text', 'facoltativo, es. Visualizza la lista completa'),
                    'more_url' => ['label' => 'Link sotto i loghi: dove porta', 'hint' => 'slug di una pagina, una sezione o https://…']]],
            'team' => ['label' => 'Squadra', 'uses' => ['title' => 'Titolo', 'excerpt' => 'Introduzione (facoltativa)'],
                'fields' => ['shape' => ['label' => 'Foto', 'type' => 'select', 'options' => ['' => 'Tonde', 'square' => 'Quadrate', 'portrait' => 'Verticali']],
                    'mono' => ['label' => 'Foto in bianco e nero', 'type' => 'checkbox']]],
            'roster' => ['label' => 'Elenco di persone', 'uses' => ['title' => 'Titolo', 'excerpt' => 'Introduzione (facoltativa)'],
                'fields' => ['list' => $tr('Persone', 'textarea', 'una per riga: “Nome: ruolo”; una riga che inizia con # è il titolo di un gruppo; più nomi sulla stessa riga separati da virgola'),
                    'cols' => ['label' => 'Colonne', 'type' => 'select', 'options' => ['' => '2', '1' => '1', '3' => '3']]]],
            'map' => ['label' => 'Mappa', 'uses' => ['title' => 'Titolo (facoltativo)', 'excerpt' => 'Indirizzo o testo accanto'],
                'fields' => ['map_lat' => ['label' => 'Latitudine', 'hint' => 'es. 41.8826 (da openstreetmap.org: tasto destro → Mostra indirizzo)'],
                    'map_lon' => ['label' => 'Longitudine', 'hint' => 'es. 12.5169'],
                    'map_zoom' => ['label' => 'Zoom', 'type' => 'number', 'hint' => 'da 12 (città) a 18 (strada), di base 16'],
                    'map_label' => $tr('Nome del luogo', 'text', 'es. Ares 2.0, sede di Roma'),
                    'map_side' => ['label' => 'Testo', 'type' => 'select', 'options' => ['' => 'A lato della mappa', 'below' => 'Sotto la mappa', 'none' => 'Solo la mappa']]]],
            'quote' => ['label' => 'Citazione', 'uses' => ['title' => 'Citazione', 'excerpt' => 'Chi lo dice', 'image' => 'Foto della persona (facoltativa)'],
                'fields' => ['role' => $tr('Ruolo o organizzazione', 'text', 'facoltativo')]],
            'faq' => ['label' => 'Domande frequenti', 'uses' => ['title' => 'Titolo', 'excerpt' => 'Introduzione'],
                'fields' => ['q1' => $tr('Domanda 1'), 'a1' => $tr('Risposta 1', 'textarea'), 'q2' => $tr('Domanda 2'), 'a2' => $tr('Risposta 2', 'textarea'),
                    'q3' => $tr('Domanda 3'), 'a3' => $tr('Risposta 3', 'textarea'), 'q4' => $tr('Domanda 4'), 'a4' => $tr('Risposta 4', 'textarea'),
                    'q5' => $tr('Domanda 5'), 'a5' => $tr('Risposta 5', 'textarea'), 'q6' => $tr('Domanda 6'), 'a6' => $tr('Risposta 6', 'textarea')]],
            'newsletter' => ['label' => 'Iscrizione alla newsletter', 'uses' => ['title' => 'Titolo', 'excerpt' => 'Testo']],
        ];
        // la squadra c'è solo se il sito ha il tipo "person" (persone con foto, ruolo, email)
        if (!isset(PP::$config['types']['person'])) unset($k['team']);
        if (isset(PP::$config['types']['event'])) $k['events'] = ['label' => 'Prossimi eventi', 'uses' => ['title' => 'Titolo'],
            'fields' => ['limit' => ['label' => 'Quanti', 'type' => 'number'], 'more_label' => $tr('Link “tutti gli eventi”: testo', 'text', 'facoltativo')]];
        return Hooks::filter('block_kinds', $k);
    }

    /** Campi dei riquadri con icona: fino a 6 riquadri. */
    private static function iconFields(callable $tr): array
    {
        $icons = ['layers' => 'Livelli', 'send' => 'Aeroplano di carta', 'folders' => 'Raccoglitori', 'book' => 'Libro', 'chart' => 'Grafico', 'megaphone' => 'Megafono',
            'video' => 'Videocamera', 'globe' => 'Mondo', 'people' => 'Persone', 'search' => 'Lente', 'document' => 'Documento', 'idea' => 'Lampadina',
            'handshake' => 'Stretta di mano', 'calendar' => 'Calendario', 'pen' => 'Penna', 'target' => 'Bersaglio', 'scale' => 'Bilancia', 'leaf' => 'Foglia'];
        $f = ['per_row' => ['label' => 'Riquadri per riga', 'type' => 'select', 'options' => ['' => 'Automatico', '2' => '2', '3' => '3', '4' => '4']],
            'boxed' => ['label' => 'Riquadri con il fondo', 'type' => 'checkbox']];
        for ($n = 1; $n <= 6; $n++) {
            $f["i{$n}_icon"] = ['label' => "Riquadro $n: icona", 'type' => 'select', 'options' => ['' => '—'] + $icons];
            $f["i{$n}_t"] = $tr("Riquadro $n: titolo");
            $f["i{$n}_d"] = $tr("Riquadro $n: testo", 'textarea');
            $f["i{$n}_btn"] = $tr("Riquadro $n: pulsante", 'text', 'facoltativo, es. Vai ai servizi');
            $f["i{$n}_url"] = ['label' => "Riquadro $n: dove porta", 'hint' => 'slug di una pagina, una sezione o https://…'];
        }
        return $f;
    }

    public static function uses(string $kind): array { return self::kinds()[$kind]['uses'] ?? ['title' => 'Titolo', 'body' => 'Testo']; }

    public static function fields(string $kind): array
    {
        $common = self::look() + [
            'anchor' => ['label' => 'Ancora', 'hint' => 'facoltativa: per linkare #ancora'],
            'page' => ['label' => 'Pagina', 'type' => 'entry', 'entry_type' => 'page', 'hint' => 'vuoto = home; scegli una pagina per mettere la fascia in fondo a quella pagina'],
        ];
        $out = [];
        foreach ((self::kinds()[$kind]['fields'] ?? []) + $common as $k => $f) $out[$k] = $f + ['label' => $k, 'type' => 'text', 'options' => [], 'translatable' => false, 'hint' => ''];
        return $out;
    }

    /** Blocchi pubblicati della home, nella lingua. */
    public static function home(?string $lang = null): array
    {
        return array_values(array_filter(Content::entries('block', $lang ?: I18n::$current, 0, 'menu_order ASC, id ASC'), fn($b) => empty($b['meta_arr']['page']) && empty($b['meta_arr']['page_slug'])));
    }

    /**
     * Fasce dei contenuti iniziali legate a una pagina per slug ("page_slug"): quando la pagina esiste
     * (es. dopo l'importazione) diventano legate al suo id, così il campo "Pagina" della gestione le mostra.
     */
    public static function bindPages(): void
    {
        foreach (DB::all("SELECT id, meta FROM pp_entries WHERE type = 'block' AND meta LIKE '%page_slug%'") as $r) {
            $m = json_decode((string)$r['meta'], true) ?: [];
            if (empty($m['page_slug']) || !empty($m['page'])) continue;
            $pid = (int)DB::val("SELECT id FROM pp_entries WHERE type = 'page' AND slug = ? ORDER BY id LIMIT 1", [(string)$m['page_slug']]);
            if (!$pid) continue;
            $m['page'] = $pid; unset($m['page_slug']);
            DB::run('UPDATE pp_entries SET meta = ? WHERE id = ?', [json_encode($m, JSON_UNESCAPED_UNICODE), (int)$r['id']]);
        }
    }

    /** Fasce in fondo a una pagina (campo "Pagina" del blocco). */
    public static function forPage(int $pageId, ?string $lang = null): array
    {
        if (!$pageId) return [];
        // "page_slug" nei contenuti iniziali: la pagina può arrivare dopo (es. con l'importazione)
        $slug = (string)DB::val('SELECT slug FROM pp_entries WHERE id = ?', [$pageId]);
        return array_values(array_filter(Content::entries('block', $lang ?: I18n::$current, 0, 'menu_order ASC, id ASC'),
            fn($b) => (int)($b['meta_arr']['page'] ?? 0) === $pageId || (empty($b['meta_arr']['page']) && $slug !== '' && ($b['meta_arr']['page_slug'] ?? '') === $slug)));
    }

    /** HTML di un blocco (partial del tema, con ripiego su _base). */
    public static function render(array $b, int $i = 0): string
    {
        $kind = preg_replace('~[^a-z0-9_-]~', '', (string)$b['template']) ?: 'text';
        // tono "Automatico": l'apertura va sul fondo per cui il tema ha pensato i colori della frase grande ("hero_on" nel theme.json)
        $auto = $kind === 'hero' && ((PP::themes()[PP::theme()] ?? [])['hero_on'] ?? 'ground') === 'accent' ? 'accent' : 'paper';
        $tone = (string)($b['meta_arr']['tone'] ?? '') ?: $auto;
        $b['tone'] = in_array($tone, ['paper', 'accent', 'field1', 'field2', 'dark'], true) ? $tone : 'paper';
        $b['lg'] = $b['_fallback'] ? ' lang="' . e(I18n::hreflang($b['_lang'])) . '"' : '';
        $b['id_attr'] = ($a = Media::slug((string)($b['meta_arr']['anchor'] ?? ''))) ? $a : 'b' . (int)$b['id'];
        $html = PP::partial('blocks/' . $kind, ['b' => $b, 'i' => $i, 'x' => $b['x'], 'm' => $b['meta_arr']]) ?: PP::partial('blocks/text', ['b' => $b, 'i' => $i, 'x' => $b['x'], 'm' => $b['meta_arr']]);
        return self::decorate($html, $b);
    }

    /** Anteprima della Modifica visuale: le fasce si possono cliccare. */
    public static bool $edit = false;

    /**
     * Aspetto della fascia, uguale per tutti i tipi di blocco (Modifica visuale e scheda del blocco).
     * Si applica in decorate() all'apertura della fascia, così vale con ogni tema, anche con quelli che riscrivono i blocchi.
     */
    public static function look(): array
    {
        return [
            'tone' => ['label' => 'Fondo', 'type' => 'select', 'options' => ['' => 'Automatico (dal tema)', 'paper' => 'Sfondo', 'accent' => 'Colore principale', 'field1' => 'Campo 1', 'field2' => 'Campo 2', 'dark' => 'Scuro', 'custom' => 'Colore libero']],
            'bg_color' => ['label' => 'Colore libero', 'type' => 'color', 'hint' => 'solo con Fondo: Colore libero; il testo diventa nero o bianco da solo'],
            'bg_image' => ['label' => 'Immagine di fondo', 'type' => 'image', 'hint' => 'orizzontale, almeno 1920 px'],
            'bg_focus' => ['label' => 'Parte dell’immagine da tenere', 'type' => 'select', 'options' => ['' => 'Centro', 'top' => 'Alto', 'bottom' => 'Basso', 'left' => 'Sinistra', 'right' => 'Destra']],
            'bg_overlay' => ['label' => 'Velo sopra l’immagine', 'type' => 'select', 'options' => ['' => 'Scuro, testo bianco', 'light' => 'Chiaro, testo scuro', 'none' => 'Nessuno (solo se l’immagine è già uniforme)']],
            'parallax' => ['label' => 'Parallasse: l’immagine scorre più lenta del testo', 'type' => 'checkbox'],
            'spacing' => ['label' => 'Spazio sopra e sotto', 'type' => 'select', 'options' => ['' => 'Normale', 'compact' => 'Compatto', 'wide' => 'Ampio']],
            'width' => ['label' => 'Larghezza del contenuto', 'type' => 'select', 'options' => ['' => 'Normale', 'narrow' => 'Stretta', 'full' => 'Piena']],
        ];
    }

    /** Aggiunge all'apertura della fascia (il primo <section>) le scelte di aspetto: classi, colore libero, immagine di fondo. */
    public static function decorate(string $html, array $b): string
    {
        $m = $b['meta_arr'] ?? [];
        if (!preg_match('~<section\b[^>]*>~', $html, $tag, PREG_OFFSET_CAPTURE)) return $html;
        [$open, $pos] = $tag[0];
        $add = [];
        $style = '';
        $ink = null;   // 'light' = testo chiaro (fondo scuro), 'dark' = testo scuro
        $color = (string)($m['bg_color'] ?? '');
        if (($m['tone'] ?? '') === 'custom' && preg_match('~^#[0-9a-f]{6}$~i', $color)) {
            $txt = class_exists('Identity') ? Identity::inkOn($color) : '#000000';
            $add[] = 'chapter--custom';
            $style .= '--chapter-bg:' . $color . ';--chapter-ink:' . $txt . ';';
            $ink = $txt === '#ffffff' ? 'light' : 'dark';
        }
        $bg = '';
        $image = (string)($m['bg_image'] ?? '');
        if ($image !== '' && preg_match('~^(uploads|pp-assets/demo/media)/[A-Za-z0-9/_.-]+\.(webp|jpe?g|png|avif)$~i', $image) && is_file(PP_ROOT . '/' . $image)) {
            $over = in_array($m['bg_overlay'] ?? '', ['light', 'none'], true) ? $m['bg_overlay'] : 'dark';
            $add[] = 'chapter--has-bg';
            $add[] = 'chapter--over-' . $over;
            if ($over !== 'none') $ink = $over === 'dark' ? 'light' : 'dark';
            $focus = ['top' => '50% 15%', 'bottom' => '50% 85%', 'left' => '15% 50%', 'right' => '85% 50%'][$m['bg_focus'] ?? ''] ?? '50% 50%';
            $img = img($image, '', '100vw', '', true);
            $img = preg_replace('~^<img~', '<img style="object-position:' . $focus . '"', $img, 1);
            $bg = '<div class="chapter__bg" aria-hidden="true"' . (!empty($m['parallax']) ? ' data-parallax' : '') . '>' . $img . '</div>';
        }
        // fondo scuro (colore libero scuro o velo scuro) = fascia "scura" del tema; fondo chiaro = fascia "chiara":
        // il tema la disegna con le regole che ha già per quei toni, cambia solo il colore o l'immagine sotto
        if ($ink !== null) $open = preg_replace('~\bchapter--(paper|accent|green|blue|ink)\b~', $ink === 'light' ? 'chapter--ink' : 'chapter--paper', $open, 1);
        if (in_array($m['spacing'] ?? '', ['compact', 'wide'], true)) $add[] = 'chapter--sp-' . $m['spacing'];
        if (in_array($m['width'] ?? '', ['narrow', 'full'], true)) $add[] = 'chapter--w-' . $m['width'];
        if ($add) $open = preg_replace('~\bclass="~', 'class="' . implode(' ', $add) . ' ', $open, 1);
        if ($style !== '') $open = preg_replace('~^<section~', '<section style="' . e($style) . '"', $open, 1);
        if ($ink !== null) $open = preg_match('~\bdata-ink="~', $open) ? preg_replace('~\bdata-ink="[^"]*"~', 'data-ink="' . $ink . '"', $open, 1) : preg_replace('~^<section~', '<section data-ink="' . $ink . '"', $open, 1);
        if (self::$edit) $open = preg_replace('~^<section~', '<section data-pp-block="' . (int)$b['id'] . '" data-pp-kind="' . e((string)$b['template']) . '"'
            . (($b['status'] ?? 'published') !== 'published' ? ' data-pp-hidden' : ''), $open, 1);
        return substr($html, 0, $pos) . $open . $bg . substr($html, $pos + strlen($tag[0][0]));
    }

    /** Link di un pulsante: https://…, /percorso, #ancora, nome di una sezione o slug di una pagina. */
    public static function href(string $v): string
    {
        $v = trim($v);
        if ($v === '') return '';
        if (preg_match('~^(https?://|mailto:|tel:|/|#)~i', $v)) return $v;
        if (isset(PP::$config['sections'][$v])) return PP::link(PP::section($v));
        $id = DB::val("SELECT id FROM pp_entries WHERE type = 'page' AND slug = ?", [$v]);
        $e = $id ? Content::byId((int)$id, I18n::$current) : null;
        return $e ? PP::entryUrl($e) : PP::link($v);
    }

    /** Fasce colorate: nome della classe secondo il tono. */
    public static function toneClass(string $tone): string
    {
        return 'chapter chapter--' . (['field1' => 'green', 'field2' => 'blue', 'dark' => 'ink'][$tone] ?? $tone);
    }
}
