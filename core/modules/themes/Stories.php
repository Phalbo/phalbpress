<?php
/**
 * Storie (o lavori di un portfolio) e le loro categorie.
 * Una storia ha le categorie nei meta: `category` (la principale) e `categories` (tutte, id di contenuti di tipo category).
 * Ogni categoria usata ha la sua pagina: /{lang}/{sezione storie}/{slug della categoria}/ (anche nel sito statico),
 * con le sottocategorie (meta `parent` della categoria) e la sua disposizione dell'elenco (meta `layout_stories`).
 * Dati mostrati nella scheda: i campi del tipo che hanno 'public' => chiave del testo (es. 'client' => ['public' => 'work_client']).
 */
final class Stories
{
    /** Storie pubblicate nella lingua, dalla più recente. */
    public static function all(string $lang): array
    {
        static $cache = [];
        return $cache[$lang] ??= Content::entries('story', $lang, 0, 'menu_order ASC, pub_date DESC, id DESC');
    }

    /** Id delle categorie di una storia. */
    public static function catIds(array $s): array
    {
        $m = $s['meta_arr'] ?? Content::meta($s);
        $cats = $m['categories'] ?? [];
        $ids = array_map('intval', is_string($cats) ? preg_split('~[^0-9]+~', $cats) : (array)$cats);
        if (!empty($m['category'])) array_unshift($ids, (int)$m['category']);
        return array_values(array_unique(array_filter($ids)));
    }

    /** Categorie usate da almeno una storia pubblicata (con le loro madri), in ordine di titolo: [id => entry localizzata + _count]. */
    public static function categories(string $lang): array
    {
        static $cache = [];
        if (isset($cache[$lang])) return $cache[$lang];
        if (!isset(PP::$config['types']['category'])) return $cache[$lang] = [];
        $count = [];
        foreach (self::all($lang) as $s) foreach (self::catIds($s) as $c) $count[$c] = ($count[$c] ?? 0) + 1;
        $out = [];
        foreach (Content::entries('category', $lang, 0, 'menu_order ASC, id ASC') as $c) $out[(int)$c['id']] = $c + ['_count' => $count[(int)$c['id']] ?? 0];
        // una madre senza storie dirette resta, se ha figlie usate
        foreach ($out as $id => $c) {
            $p = (int)($c['meta_arr']['parent'] ?? 0);
            if ($c['_count'] && $p && isset($out[$p])) $out[$p]['_kids'] = true;
        }
        $out = array_filter($out, fn($c) => $c['_count'] > 0 || !empty($c['_kids']));
        // ordine scelto in gestione (campo Ordine), poi alfabetico
        uasort($out, fn($a, $b) => [(int)$a['menu_order'] ?: 999, mb_strtolower($a['title'])] <=> [(int)$b['menu_order'] ?: 999, mb_strtolower($b['title'])]);
        return $cache[$lang] = $out;
    }

    /** Categoria nascosta nei filtri e nel menu (campo "Nascondi"): la sua pagina resta, i lavori restano in "Tutte". */
    public static function hidden(array $c): bool { return !empty($c['meta_arr']['nav_hidden']); }

    /** Categorie di primo livello (o le figlie di $parent), senza quelle nascoste se $visible. */
    public static function children(string $lang, int $parent = 0, bool $visible = false): array
    {
        $all = self::categories($lang);
        return array_filter($all, fn($c) => ((int)($c['meta_arr']['parent'] ?? 0) === $parent || ($parent === 0 && !isset($all[(int)($c['meta_arr']['parent'] ?? 0)])))
            && (!$visible || !self::hidden($c)));
    }

    /** true se le categorie hanno sottocategorie (portfolio a due livelli: aree e divisioni). */
    public static function nested(string $lang): bool
    {
        foreach (self::categories($lang) as $c) if (!empty($c['meta_arr']['parent'])) return true;
        return false;
    }

    /** Voci di menu delle categorie visibili, con le sottocategorie: [['label','href','current','external','children'=>[…]], …] */
    public static function menu(string $lang): array
    {
        $here = PP::current();
        $item = fn($c, $kids = []) => ['label' => $c['title'], 'href' => self::catUrl($c, $lang), 'current' => str_starts_with($here, self::catUrl($c, $lang)), 'external' => false, 'children' => $kids];
        $out = [];
        foreach (self::children($lang, 0, true) as $c) {
            $kids = array_values(array_map(fn($k) => $item($k), self::children($lang, (int)$c['id'], true)));
            $out[] = $item($c, $kids);
        }
        return $out;
    }

    /** Categoria per slug nella lingua (solo quelle con una pagina). */
    public static function bySlug(string $slug, string $lang): ?array
    {
        foreach (self::categories($lang) as $c) if ($c['slug'] === $slug || $c['base_slug'] === $slug) return $c;
        return null;
    }

    public static function catUrl(array $c, ?string $lang = null): string
    {
        $lang = $lang ?: I18n::$current;
        return PP::link(PP::section('stories', $lang) . '/' . Content::slugFor((int)$c['id'], $lang), $lang);
    }

    /** Storie di una categoria, comprese quelle delle sottocategorie. */
    public static function inCategory(array $cat, string $lang): array
    {
        $ids = [(int)$cat['id']];
        foreach (self::categories($lang) as $c) if ((int)($c['meta_arr']['parent'] ?? 0) === (int)$cat['id']) $ids[] = (int)$c['id'];
        return array_values(array_filter(self::all($lang), fn($s) => (bool)array_intersect($ids, self::catIds($s))));
    }

    /** Nome della categoria più precisa di una storia (una sottocategoria prima di un'area), per le schede. */
    public static function catName(array $s, string $lang): string
    {
        $all = self::categories($lang);
        $mine = array_values(array_filter(self::catIds($s), fn($id) => isset($all[$id])));
        foreach ($mine as $id) if (!empty($all[$id]['meta_arr']['parent'])) return $all[$id]['title'];
        return $mine ? $all[$mine[0]]['title'] : '';
    }

    /** Dati pubblici della storia: [[etichetta, valore], …] dai campi con 'public'. */
    public static function facts(array $s): array
    {
        $out = [];
        foreach (PP::$config['types']['story']['fields'] ?? [] as $k => $f) {
            if (!is_array($f) || empty($f['public'])) continue;
            $v = trim((string)(($f['translatable'] ?? false) ? ($s['x'][$k] ?? '') : ($s['meta_arr'][$k] ?? '')));
            if ($v !== '') $out[] = [t((string)$f['public']), $v];
        }
        return $out;
    }

    /** Riga breve sotto il titolo nelle schede: il primo dato pubblico (es. cliente), o paese · ruolo delle storie di persone. */
    public static function line(array $s): string
    {
        $f = self::facts($s);
        if ($f) return $f[0][1];
        $m = $s['meta_arr'];
        $role = $s['x']['role'] ?? ($m['role'] ?? '');
        return trim(($m['country'] ?? '') . ($role ? ' · ' . $role : ''), ' ·');
    }

    /** Storie vicine: precedente e successiva nell'ordine dell'elenco. */
    public static function around(array $s, string $lang): array
    {
        $all = self::all($lang);
        foreach ($all as $i => $x) if ((int)$x['id'] === (int)$s['id']) return [$all[$i - 1] ?? null, $all[$i + 1] ?? null];
        return [null, null];
    }

    /** Altre storie della stessa categoria (le più recenti), al massimo $n. */
    public static function related(array $s, string $lang, int $n = 3): array
    {
        $mine = self::catIds($s);
        if (!$mine) return [];
        $out = [];
        foreach (self::all($lang) as $x) {
            if ((int)$x['id'] === (int)$s['id'] || !array_intersect($mine, self::catIds($x))) continue;
            $out[] = $x;
            if (count($out) >= $n) break;
        }
        return $out;
    }

    /** Anno del lavoro (dalla data). */
    public static function year(array $s): string
    {
        return preg_match('~^(\d{4})~', (string)$s['pub_date'], $m) ? $m[1] : '';
    }
}
