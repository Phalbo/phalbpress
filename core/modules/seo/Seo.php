<?php
final class Seo
{
    public const TITLE_MAX = 60;
    public const DESC_MAX = 155;
    public static array $last = [];

    public static function ref(): string
    {
        $r = PP::$route;
        return $r['name'] . (isset($r['params'][0]) ? ':' . $r['params'][0] : '');
    }

    /** Sito nascosto ai motori di ricerca (impostazione "Scoraggia i motori di ricerca"): noindex su ogni pagina e robots.txt chiuso. */
    public static function hidden(): bool { return (bool)Settings::get('search_hidden', 0); }

    public static function clip(string $text, int $max): string
    {
        $text = trim(preg_replace('~\s+~u', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8')));
        if (mb_strlen($text) <= $max) return $text;
        $cut = mb_substr($text, 0, $max - 1);
        $sp = mb_strrpos($cut, ' ');
        return rtrim(($sp > $max * 0.6 ? mb_substr($cut, 0, $sp) : $cut), ' ,.;:–-') . '…';
    }

    /** Titolo e descrizione automatici dalla pagina. */
    public static function auto(array $ctx): array
    {
        $site = class_exists('Identity') ? Identity::name() : PP::$config['site_name'];
        $claim = trim(t('claim'));
        $title = !empty($ctx['title']) && PP::$route['name'] !== 'home' ? $ctx['title'] . ' · ' . $site : $site . ($claim ? ' – ' . $claim : '');
        $desc = $ctx['desc'] ?? '';
        if (trim(strip_tags((string)$desc)) === '') $desc = $claim ?: $site;
        return ['title' => self::clip($title, self::TITLE_MAX), 'desc' => self::clip((string)$desc, self::DESC_MAX)];
    }

    public static function custom(string $ref, string $lang): ?array
    {
        try { return DB::one('SELECT title, description FROM pp_seo WHERE ref = ? AND lang = ?', [$ref, $lang]); }
        catch (Throwable $e) { return null; }
    }

    public static function head(array $ctx): string
    {
        $L = I18n::$current;
        $auto = self::auto($ctx);
        $c = self::custom(self::ref(), $L);
        $title = $c && trim($c['title']) !== '' ? $c['title'] : $auto['title'];
        $desc = $c && trim($c['description']) !== '' ? $c['description'] : $auto['desc'];
        self::$last = ['auto' => $auto, 'title' => $title, 'desc' => $desc];

        $id = class_exists('Identity') ? Identity::get() : [];
        $img = !empty($ctx['image']) ? $ctx['image'] : ($id['share_image'] ?? '');
        $site = class_exists('Identity') ? Identity::name() : PP::$config['site_name'];
        $url = PP::abs(PP::current());
        $is404 = PP::$route['name'] === '404' || !empty($ctx['noindex']) || Content::$preview;
        if (self::hidden()) return '<title>' . e($title) . "</title>\n<meta name=\"description\" content=\"" . e($desc) . "\">\n<meta name=\"robots\" content=\"noindex, nofollow\">\n";

        $h = '<title>' . e($title) . "</title>\n";
        $h .= '<meta name="description" content="' . e($desc) . "\">\n";
        if ($is404) return $h . "<meta name=\"robots\" content=\"noindex\">\n";
        $h .= '<link rel="canonical" href="' . e($url) . "\">\n";
        foreach (I18n::codes() as $l) $h .= '<link rel="alternate" hreflang="' . e(I18n::hreflang($l)) . '" href="' . e(PP::abs(PP::alternate($l))) . "\">\n";
        $h .= '<link rel="alternate" hreflang="x-default" href="' . e(PP::abs(PP::alternate(I18n::$master))) . "\">\n";
        $og = [
            'og:type' => ($ctx['type'] ?? '') === 'article' ? 'article' : 'website',
            'og:site_name' => $site, 'og:title' => $title, 'og:description' => $desc, 'og:url' => $url,
            'og:locale' => I18n::$langs[$L]['locale'] ?? $L,
        ];
        if ($img) $og['og:image'] = PP::abs(PP::url($img));
        foreach ($og as $k => $v) $h .= '<meta property="' . $k . '" content="' . e($v) . "\">\n";
        $h .= '<meta name="twitter:card" content="' . ($img ? 'summary_large_image' : 'summary') . "\">\n";
        $h .= '<script type="application/ld+json">' . json_encode(self::jsonld($ctx, $title, $desc, $img, $url), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) . "</script>\n";
        return $h;
    }

    private static function jsonld(array $ctx, string $title, string $desc, string $img, string $url): array
    {
        $L = I18n::$current;
        $home = PP::abs(PP::link('', $L));
        $id = class_exists('Identity') ? Identity::get() : [];
        $site = class_exists('Identity') ? Identity::name() : PP::$config['site_name'];
        $org = self::owner($home, $site, $id);
        $pub = ['@id' => $org['@id']];
        $graph = [$org, ['@type' => 'WebSite', '@id' => $home . '#site', 'url' => $home, 'name' => $site, 'inLanguage' => I18n::hreflang($L), 'publisher' => $pub]];
        $page = ['@type' => ($ctx['type'] ?? '') === 'article' ? 'NewsArticle' : 'WebPage', '@id' => $url, 'url' => $url, 'name' => $title, 'headline' => $ctx['title'] ?? $title,
            'description' => $desc, 'inLanguage' => I18n::hreflang($L), 'isPartOf' => ['@id' => $home . '#site'], 'publisher' => $pub];
        if (($org['@type'] ?? '') === 'Person') { $page['author'] = $pub; if (PP::$route['name'] === 'home') $page = ['@type' => 'ProfilePage', 'mainEntity' => $pub] + $page; }
        if ($img) $page['image'] = PP::abs(PP::url($img));
        if (!empty($ctx['date'])) $page['datePublished'] = $ctx['date'];
        $graph[] = $page;
        if (PP::$route['name'] !== 'home') {
            $graph[] = ['@type' => 'BreadcrumbList', 'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => $site, 'item' => $home],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $ctx['title'] ?? $title, 'item' => $url],
            ]];
        }
        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }

    /**
     * Chi c'è dietro il sito, per i dati strutturati: un'organizzazione (progetti, enti) o una persona (sito personale, portfolio).
     * config['owner'] = ['type' => 'organization'|'person', 'job_title', 'works_for', 'location', 'alumni_of', 'knows_about' => [...], 'image']
     * (modificabile in Sistema → Impostazioni del sito).
     */
    public static function owner(string $home, string $site, array $id): array
    {
        $o = (array)(PP::$config['owner'] ?? []);
        $person = ($o['type'] ?? '') === 'person';
        $same = array_values(array_filter(PP::$config['social'] ?? []));
        $email = PP::$config['contact_email'] ?? '';
        $claim = trim(t('claim'));
        if (!$person) {
            $org = ['@type' => 'Organization', '@id' => $home . '#org', 'name' => $site, 'url' => $home];
            if ($claim !== '') $org['slogan'] = $claim;
            if (!empty($id['logo'])) $org['logo'] = PP::abs(PP::url($id['logo']));
            if ($same) $org['sameAs'] = $same;
            if (!empty(PP::$config['funder'])) $org['funder'] = ['@type' => 'GovernmentOrganization', 'name' => PP::$config['funder']];
            if ($email) $org['email'] = $email;
            return $org;
        }
        $p = ['@type' => 'Person', '@id' => $home . '#person', 'name' => trim((string)($o['name'] ?? '')) ?: $site, 'url' => $home];
        if ($claim !== '') $p['description'] = $claim;
        $str = fn(string $k) => trim(strip_tags((string)($o[$k] ?? '')));
        if ($str('job_title') !== '') $p['jobTitle'] = $str('job_title');
        if ($str('works_for') !== '') $p['worksFor'] = ['@type' => 'Organization', 'name' => $str('works_for')];
        if ($str('alumni_of') !== '') $p['alumniOf'] = ['@type' => 'EducationalOrganization', 'name' => $str('alumni_of')];
        if ($str('location') !== '') $p['address'] = ['@type' => 'PostalAddress', 'addressLocality' => $str('location')];
        $knows = array_values(array_filter(array_map(fn($x) => trim(strip_tags((string)$x)), (array)($o['knows_about'] ?? []))));
        if ($knows) $p['knowsAbout'] = $knows;
        $img = $str('image') ?: ($id['share_image'] ?? '');
        if ($img) $p['image'] = PP::abs(PP::url($img));
        if ($same) $p['sameAs'] = $same;
        if ($email) $p['email'] = $email;
        return $p;
    }

    public static function sitemap(): string
    {
        $x = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
        foreach (PP::items() as $it) {
            if (!empty($it['noindex'])) continue;
            foreach ($it['paths'] as $l => $p) {
                $x .= '<url><loc>' . e(PP::abs(PP::link($p, $l))) . '</loc>';
                foreach ($it['paths'] as $l2 => $p2) $x .= '<xhtml:link rel="alternate" hreflang="' . e(I18n::hreflang($l2)) . '" href="' . e(PP::abs(PP::link($p2, $l2))) . '"/>';
                $x .= "</url>\n";
            }
        }
        return $x . '</urlset>';
    }

    /* ---------- gestione ---------- */

    public static function admin(string $lang): void
    {
        if (!I18n::valid($lang)) $lang = I18n::$master;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Admin::post('action') === 'visibility') {
            $hide = (bool)Admin::post('hide');
            Settings::set('search_hidden', $hide ? 1 : 0);
            Auth::log('seo visibility', $hide ? 'nascosto' : 'visibile');
            Admin::changed('seo');
            Admin::flash($hide ? 'Il sito ora scoraggia i motori di ricerca.' : 'Il sito ora è visibile ai motori di ricerca.');
            PP::redirect(Admin::u('seo/' . $lang));
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($_POST['seo'] ?? [] as $ref => $v) {
                $ref = substr((string)$ref, 0, 160);
                $t = trim((string)($v['title'] ?? '')); $d = trim((string)($v['desc'] ?? ''));
                if ($t === '' && $d === '') DB::run('DELETE FROM pp_seo WHERE ref = ? AND lang = ?', [$ref, $lang]);
                else DB::upsert('pp_seo', ['ref' => $ref, 'lang' => $lang, 'title' => mb_substr($t, 0, 255), 'description' => mb_substr($d, 0, 400), 'updated_at' => Content::now()], ['ref', 'lang']);
            }
            Auth::log('save seo', $lang);
            Admin::changed('seo');
            Admin::flash('SEO salvata. I campi vuoti usano i valori automatici.');
            $back = array_intersect_key($_GET, array_flip(['kind', 'q', 'p', 'all']));
            PP::redirect(Admin::u('seo/' . $lang) . ($back ? '?' . http_build_query($back) : ''));
        }
        // filtri e pagine prima di calcolare: ogni riga visita la sua pagina, con centinaia di contenuti la pagina diventerebbe enorme
        $all = PP::paths($lang);
        $kinds = array_count_values($all);
        $kind = (string)($_GET['kind'] ?? '');
        $q = mb_strtolower(trim((string)($_GET['q'] ?? '')));
        $sel = array_filter($all, fn($k, $path) => ($kind === '' || $k === $kind) && ($q === '' || str_contains(mb_strtolower((string)$path), $q)), ARRAY_FILTER_USE_BOTH);
        $pg = Admin::pager(array_keys($sel), 40);
        $rows = [];
        $saved = I18n::$current;
        foreach (array_intersect_key($sel, array_flip($pg['items'])) as $path => $kind) {
            $seg = array_merge([$lang], $path === '' ? [] : explode('/', $path));
            PP::page($seg);
            $ref = self::ref();
            $rows[$ref] = ['path' => $path, 'kind' => $kind, 'auto' => self::$last['auto'] ?? ['title' => '', 'desc' => ''], 'custom' => self::custom($ref, $lang)];
        }
        I18n::$current = $saved;
        $titles = array_count_values(array_map(fn($r) => $r['custom']['title'] ?? $r['auto']['title'], $rows));
        Admin::view(__DIR__ . '/admin.php', ['title' => 'SEO', 'rows' => $rows, 'lang' => $lang, 'dupes' => $titles, 'kinds' => $kinds, 'kind' => (string)($_GET['kind'] ?? ''), 'q' => $q, 'pager' => $pg['html'], 'found' => count($sel)]);
    }
}
