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
                    'switch_label' => $tr('Etichetta dei pulsanti lingua', 'text', 'es. “Dillo in”')]],
            'statement' => ['label' => 'Dichiarazione', 'uses' => ['title' => 'Titolo', 'excerpt' => 'Testo'],
                'fields' => ['fact' => $tr('Dato o frase a lato', 'textarea')]],
            'steps' => ['label' => 'Passi', 'uses' => ['title' => 'Titolo (facoltativo, per i lettori di schermo)'],
                'fields' => ['s1_t' => $tr('Passo 1: frase'), 's1_d' => $tr('Passo 1: testo', 'textarea'), 's2_t' => $tr('Passo 2: frase'), 's2_d' => $tr('Passo 2: testo', 'textarea'),
                    's3_t' => $tr('Passo 3: frase'), 's3_d' => $tr('Passo 3: testo', 'textarea'), 's4_t' => $tr('Passo 4: frase'), 's4_d' => $tr('Passo 4: testo', 'textarea')]],
            'collection' => ['label' => 'Elenco di contenuti', 'uses' => ['title' => 'Titolo', 'excerpt' => 'Introduzione'],
                'fields' => ['source' => ['label' => 'Cosa mostra', 'type' => 'select', 'options' => array_map(fn($t) => $t['label'], array_diff_key(PP::$config['types'] ?? [], ['block' => 1, 'page' => 1]))],
                    'limit' => ['label' => 'Quanti (0 = tutti)', 'type' => 'number'],
                    'style' => ['label' => 'Aspetto', 'type' => 'select', 'options' => ['list' => 'Elenco', 'cards' => 'Schede con immagine']],
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
        ];
        return Hooks::filter('block_kinds', $k);
    }

    public static function uses(string $kind): array { return self::kinds()[$kind]['uses'] ?? ['title' => 'Titolo', 'body' => 'Testo']; }

    public static function fields(string $kind): array
    {
        $common = [
            'tone' => ['label' => 'Colore della fascia', 'type' => 'select', 'options' => ['' => 'Automatico', 'paper' => 'Sfondo', 'accent' => 'Accento', 'field1' => 'Campo 1', 'field2' => 'Campo 2', 'dark' => 'Scuro']],
            'anchor' => ['label' => 'Ancora', 'hint' => 'facoltativa: per linkare #ancora'],
        ];
        $out = [];
        foreach ((self::kinds()[$kind]['fields'] ?? []) + $common as $k => $f) $out[$k] = $f + ['label' => $k, 'type' => 'text', 'options' => [], 'translatable' => false, 'hint' => ''];
        return $out;
    }

    /** Blocchi pubblicati della home, nella lingua. */
    public static function home(?string $lang = null): array
    {
        return Content::entries('block', $lang ?: I18n::$current, 0, 'menu_order ASC, id ASC');
    }

    /** HTML di un blocco (partial del tema, con ripiego su _base). */
    public static function render(array $b, int $i = 0): string
    {
        $kind = preg_replace('~[^a-z0-9_-]~', '', (string)$b['template']) ?: 'text';
        $tone = (string)($b['meta_arr']['tone'] ?? '') ?: 'paper';
        $b['tone'] = in_array($tone, ['paper', 'accent', 'field1', 'field2', 'dark'], true) ? $tone : 'paper';
        $b['lg'] = $b['_fallback'] ? ' lang="' . e(I18n::hreflang($b['_lang'])) . '"' : '';
        $b['id_attr'] = ($a = Media::slug((string)($b['meta_arr']['anchor'] ?? ''))) ? $a : 'b' . (int)$b['id'];
        return PP::partial('blocks/' . $kind, ['b' => $b, 'i' => $i, 'x' => $b['x'], 'm' => $b['meta_arr']]) ?: PP::partial('blocks/text', ['b' => $b, 'i' => $i, 'x' => $b['x'], 'm' => $b['meta_arr']]);
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
