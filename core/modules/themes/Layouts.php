<?php
/**
 * Disposizioni delle pagine interne.
 * Ogni tipo di pagina ha delle parti (intestazione, corpo, elenco…) e ogni parte ha un piccolo menu di disposizioni,
 * uguali per tutti i temi: il tema le disegna a modo suo. La scelta si cerca in quest'ordine:
 *   1. la singola pagina (meta `layout_<parte>` del contenuto, solo per pagine e news)
 *   2. il sito, per tipo di pagina (impostazione `layouts`, da Modifica visuale)
 *   3. il tema attivo (`layouts` in theme.json)
 *   4. il valore di base qui sotto.
 * Una disposizione che non esiste più (o scritta male) ricade sulla successiva: non si rompe mai una pagina.
 */
final class Layouts
{
    /** Tipo di pagina e contenuto della pagina che si sta disegnando (serve alla Modifica visuale). */
    public static string $type = '';
    public static int $entry = 0;

    /** Disposizioni per parte: chiave => [nome, descrizione breve]. */
    public static function parts(): array
    {
        return [
            'header' => ['label' => 'Testata e menu', 'options' => [
                'bar' => ['Barra', 'logo a sinistra, menu a destra sulla stessa riga'],
                'right' => ['Logo a destra', 'menu a sinistra, logo a destra'],
                'center' => ['Centrata', 'logo al centro, menu centrato sotto'],
                'tabs' => ['Menu a pulsanti', 'logo sopra, sotto una fascia colorata con le voci come pulsanti'],
                'drawer' => ['Essenziale', 'solo logo e pulsante Menu: le voci si aprono a tutto schermo'],
            ]],
            'head' => ['label' => 'Intestazione', 'options' => [
                'left' => ['A sinistra', 'titolo e frase allineati a sinistra'],
                'center' => ['Centrata', 'titolo e frase al centro'],
                'split' => ['Divisa con foto', 'testo da una parte, foto dall’altra'],
                'cover' => ['Foto al vivo', 'foto a tutta larghezza con il titolo sopra'],
                'minimal' => ['Essenziale', 'solo il titolo, su fondo chiaro'],
            ]],
            'body' => ['label' => 'Testo', 'options' => [
                'read' => ['Colonna di lettura', 'una colonna comoda da leggere'],
                'wide' => ['Larga', 'testo e immagini più larghi'],
                'aside' => ['Con barra laterale', 'indice, condivisione e altre notizie a lato'],
            ]],
            'list' => ['label' => 'Elenco', 'options' => [
                'grid' => ['Griglia di schede', 'foto, data e titolo in schede'],
                'feature' => ['Prima in evidenza', 'la più recente grande, le altre in griglia'],
                'rows' => ['Elenco', 'righe con miniatura, comodo con tante notizie'],
                'mosaic' => ['Mosaico', 'foto al vivo con il titolo sopra'],
            ]],
            'stories' => ['label' => 'Elenco', 'options' => [
                'zigzag' => ['Alternate', 'foto e testo a zig-zag'],
                'grid' => ['Ritratti', 'ritratti in griglia'],
                'mosaic' => ['Mosaico', 'foto al vivo con il nome sopra'],
                'tiles' => ['Riquadri', 'quadrati fitti, il titolo sotto: per campagne e grafiche social'],
                'covers' => ['Copertine', 'formato verticale come libri e riviste: per editoria e report'],
                'screens' => ['Schermi', 'formato 16:9 largo: per video e siti'],
                'index' => ['Indice', 'righe di testo con anno e cliente, la foto compare passandoci sopra: per ricerche e lunghi archivi'],
            ]],
            'work' => ['label' => 'Scheda', 'options' => [
                'portrait' => ['Ritratto', 'foto verticale accanto al nome, poi il testo: per le persone'],
                'sheet' => ['Scheda', 'dati del lavoro in una colonna fissa a lato, foto e testo accanto'],
                'showcase' => ['Vetrina', 'la foto grande in apertura su fondo scuro, i dati in una riga sotto il titolo'],
                'journal' => ['Racconto', 'titolo grande, testo stretto e immagini a tutta larghezza tra un paragrafo e l’altro'],
            ]],
            'docs' => ['label' => 'Documenti', 'options' => [
                'cards' => ['Schede', 'un documento per scheda con il pulsante per scaricarlo'],
                'table' => ['Tabella', 'righe compatte, tante voci in poco spazio'],
            ]],
            'partners' => ['label' => 'Partner', 'options' => [
                'cards' => ['Schede', 'paese, nome, descrizione e logo'],
                'logos' => ['Loghi', 'i loghi in griglia, il nome sotto'],
                'rows' => ['Elenco', 'righe con paese e sito'],
            ]],
        ];
    }

    /** Tipi di pagina, con il loro nome nella gestione e le parti che si possono cambiare. */
    public static function types(): array
    {
        return [
            'site' => ['Tutto il sito', ['header']],
            'page' => ['Pagine', ['head', 'body', 'list']],
            'news' => ['Elenco delle news', ['head', 'list']],
            'news-item' => ['Singola news', ['head', 'body']],
            'stories' => ['Elenco delle storie (e delle loro categorie)', ['head', 'stories']],
            'story' => ['Singola storia', ['work']],
            'events' => ['Elenco degli eventi', ['head']],
            'event' => ['Singolo evento', ['head']],
            'results' => ['Risultati', ['head', 'docs']],
            'partners' => ['Partner', ['head', 'partners']],
            'contact' => ['Contatti', ['head']],
            'search' => ['Ricerca', ['head']],
            '404' => ['Pagina non trovata', ['head']],
        ];
    }

    /** Valori di base, se né il sito né il tema dicono altro. */
    private static function base(string $part): string
    {
        return ['header' => 'bar', 'head' => 'left', 'body' => 'read', 'list' => 'grid', 'stories' => 'zigzag', 'work' => 'portrait', 'docs' => 'table', 'partners' => 'cards'][$part] ?? '';
    }

    private static function valid(string $part, $v): bool
    {
        return is_string($v) && isset(self::parts()[$part]['options'][$v]);
    }

    /** La disposizione da usare. $entry = contenuto della pagina (riga con meta o meta_arr) quando c'è. */
    public static function get(string $type, string $part, ?array $entry = null): string
    {
        self::$type = $type;
        if ($entry && !empty($entry['id'])) self::$entry = (int)$entry['id'];
        if ($entry) {
            $m = $entry['meta_arr'] ?? (is_string($entry['meta'] ?? null) ? (json_decode($entry['meta'], true) ?: []) : []);
            if (self::valid($part, $m['layout_' . $part] ?? null)) return $m['layout_' . $part];
        }
        return self::forType($type, $part);
    }

    /** Scelta per tipo di pagina (sito → tema → base), senza guardare la singola pagina. */
    public static function forType(string $type, string $part): string
    {
        $site = class_exists('Settings') ? Settings::get('layouts', []) : [];
        if (self::valid($part, $site[$type][$part] ?? null)) return $site[$type][$part];
        return self::themeDefault($type, $part);
    }

    public static function themeDefault(string $type, string $part): string
    {
        $theme = PP::themes()[PP::theme()] ?? [];
        $t = $theme['layouts'] ?? [];
        foreach ([$t[$type][$part] ?? null, $t['*'][$part] ?? null] as $v) if (self::valid($part, $v)) return $v;
        return self::base($part);
    }

    /** Il sito sceglie per un tipo di pagina ('' = torna a quella del tema). */
    public static function setType(string $type, string $part, string $value): void
    {
        if (!isset(self::types()[$type]) || !in_array($part, self::types()[$type][1], true)) throw new RuntimeException('Parte non valida');
        if ($value !== '' && !self::valid($part, $value)) throw new RuntimeException('Disposizione sconosciuta');
        $all = Settings::get('layouts', []);
        if (!is_array($all)) $all = [];
        if ($value === '') unset($all[$type][$part]); else $all[$type][$part] = $value;
        if (empty($all[$type])) unset($all[$type]);
        Settings::set('layouts', $all ?: null);
    }

    /** Una sola pagina fa eccezione ('' = come le altre del suo tipo). */
    public static function setEntry(int $id, string $part, string $value): void
    {
        $row = DB::one("SELECT * FROM pp_entries WHERE id = ? AND type IN ('page', 'news', 'story', 'category')", [$id]);
        if (!$row) throw new RuntimeException('Contenuto non trovato');
        if ($value !== '' && !self::valid($part, $value)) throw new RuntimeException('Disposizione sconosciuta');
        $meta = Content::meta($row);
        if ($value === '') unset($meta['layout_' . $part]); else $meta['layout_' . $part] = $value;
        DB::update('pp_entries', ['meta' => json_encode($meta, JSON_UNESCAPED_UNICODE), 'updated_at' => Content::now()], 'id = ?', [$id]);
    }

    /** Campi nella scheda di pagine e news: "come le altre" più le disposizioni possibili; punto focale della foto; pagina madre. */
    public static function fields(string $type): array
    {
        $out = [];
        if (in_array($type, ['page', 'news', 'story', 'event'], true)) $out['image_focus'] = ['label' => 'Foto: dove sta il soggetto', 'type' => 'select',
            'options' => ['' => 'Al centro', 'top' => 'In alto', 'bottom' => 'In basso', 'left' => 'A sinistra', 'right' => 'A destra'],
            'hint' => 'quando la foto viene tagliata (schede, intestazioni, mosaico) resta visibile questa parte'];
        if ($type === 'page') $out['parent'] = ['label' => 'Pagina madre', 'type' => 'entry', 'entry_type' => 'page',
            'hint' => 'facoltativa: la pagina compare nell’elenco della madre e nel percorso sopra il titolo (es. Progetti → Progetto 1)'];
        if ($type === 'category') $out['parent'] = ['label' => 'Categoria madre', 'type' => 'entry', 'entry_type' => 'category',
            'hint' => 'facoltativa: la categoria compare tra le sottocategorie della madre (es. Comunicazione → Audiovisivi)'];
        $map = ['page' => 'page', 'news' => 'news-item', 'story' => 'story', 'category' => 'stories'][$type] ?? '';
        if (!$map) return $out;
        foreach (self::types()[$map][1] as $part) {
            if ($part === 'head' && $type === 'category') continue;
            $p = self::parts()[$part];
            $opts = ['' => 'Come le altre (' . $p['options'][self::forType($map, $part)][0] . ')'];
            foreach ($p['options'] as $k => [$name]) $opts[$k] = $name;
            $out['layout_' . $part] = ['label' => 'Disposizione: ' . mb_strtolower($p['label']), 'type' => 'select', 'options' => $opts,
                'hint' => 'si sceglie anche cliccando sulla pagina in Modifica visuale'];
        }
        return $out;
    }

    /** Classi CSS della disposizione, da mettere sulla parte. */
    public static function cls(string $part, string $value): string
    {
        return $part . '--' . $value;
    }
}
