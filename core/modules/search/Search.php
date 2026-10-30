<?php
final class Search
{
    private const SLUGS = ['en' => 'search', 'it' => 'cerca', 'es' => 'buscar', 'fr' => 'recherche', 'de' => 'suche', 'pt' => 'pesquisa',
        'sl' => 'iskanje', 'sr' => 'pretraga', 'hr' => 'pretraga', 'bs' => 'pretraga', 'sq' => 'kerko', 'ro' => 'cautare', 'el' => 'anazitisi'];

    public static function slug(string $lang): string
    {
        $c = PP::$config['sections']['search'] ?? null;
        if (is_array($c) && !empty($c[$lang])) return $c[$lang];
        if (is_string($c) && $c !== '') return $c;
        return self::SLUGS[$lang] ?? 'search';
    }

    public static function url(?string $lang = null): string { $lang = $lang ?: I18n::$current; return PP::link(self::slug($lang), $lang); }

    private static function plain(string $html, int $max): string
    {
        $t = html_entity_decode(strip_tags(preg_replace(['~<(br|/p|/li|/h\d)[^>]*>~i', '~\[[a-z]+:[^\]]+\]~'], [' ', ''], $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return mb_substr(trim(preg_replace('~\s+~u', ' ', $t)), 0, $max);
    }

    /** Indice JSON di una lingua: titolo, indirizzo, sommario, testo (ridotto), tipo, data. */
    public static function index(string $lang): string
    {
        $saved = I18n::$current;
        I18n::$current = $lang;
        $rows = [];
        $types = Hooks::filter('search_types', ['page', 'news', 'story']);
        foreach (DB::all('SELECT * FROM pp_entries WHERE type IN (' . implode(',', array_fill(0, count($types), '?')) . ') AND ' . Content::live() . ' ORDER BY pub_date DESC', $types) as $r) {
            $e = Content::localize('entry', $r, $lang);
            $u = PP::entryUrl($e, $lang);
            if (!$u || $e['title'] === '') continue;
            $rows[] = ['t' => $e['title'], 'u' => $u, 'x' => self::plain($e['excerpt'], 240), 'b' => self::plain($e['body'], 4000),
                'k' => PP::$config['types'][$e['type']]['one'] ?? $e['type'], 'd' => in_array($e['type'], ['news', 'story'], true) ? $e['pub_date'] : ''];
        }
        foreach (Content::docs($lang) as $d) {
            if (!$d['file_active']) continue;
            $rows[] = ['t' => ($d['code'] ? $d['code'] . ' · ' : '') . $d['title'], 'u' => PP::docUrl($d, $lang), 'x' => self::plain($d['excerpt'], 240),
                'b' => self::plain($d['body'], 1500), 'k' => I18n::t('search_kind_doc', $lang) ?: 'doc', 'd' => $d['pub_date']];
        }
        I18n::$current = $saved;
        return json_encode(Hooks::filter('search_index', $rows, $lang), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
