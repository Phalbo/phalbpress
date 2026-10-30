<?php
/**
 * PhalbPress — cuore: configurazione, indirizzi, router pubblico, temi, rendering.
 */
final class PP
{
    public const VERSION = '1.3.1';
    public static array $config = [];
    public static array $route = ['name' => 'home', 'params' => []];
    public static bool $building = false;   // true durante la generazione delle pagine statiche
    public static ?string $theme = null;     // tema forzato (anteprima)

    public static function boot(): void
    {
        $cfgFile = PP_SITE . '/config.php';
        if (!is_file($cfgFile)) {
            http_response_code(503);
            exit('PhalbPress: manca site/config.php (copia site/config.sample.php e compilalo).');
        }
        self::$config = require $cfgFile;
        date_default_timezone_set(self::$config['timezone'] ?? 'Europe/Rome');
        if (!empty(self::$config['debug'])) { ini_set('display_errors', '1'); error_reporting(E_ALL); }
        else { ini_set('display_errors', '0'); }
        I18n::init(self::$config);
        DB::connect(self::$config['db']);
        Modules::boot();
    }

    /* ---------- indirizzi ---------- */

    public static function base(): string
    {
        if (!empty(self::$config['base_path'])) return rtrim(self::$config['base_path'], '/');
        static $b = null;
        if ($b !== null) return $b;
        // dalla posizione della cartella rispetto alla radice del sito (più affidabile di SCRIPT_NAME con le riscritture)
        $root = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? '')) ?: '';
        $me = realpath(PP_ROOT) ?: PP_ROOT;
        if ($root !== '' && str_starts_with($me, $root)) return $b = rtrim(str_replace('\\', '/', substr($me, strlen($root))), '/');
        $d = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        return $b = ($d === '/' || $d === '.' ? '' : rtrim($d, '/'));
    }

    public static function origin(): string
    {
        if (!empty(self::$config['site_url'])) return rtrim(self::$config['site_url'], '/');
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        return ($https ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . self::base();
    }

    public static function abs(string $url): string
    {
        if (preg_match('~^https?://~', $url)) return $url;
        $host = preg_replace('~' . preg_quote(self::base(), '~') . '$~', '', self::origin());
        return $host . $url;
    }

    public static function adminSlug(): string { return trim((string)(self::$config['admin_slug'] ?? 'admin'), '/') ?: 'admin'; }
    public static function url(string $path = ''): string { return self::base() . '/' . ltrim($path, '/'); }

    public static function upload(string $rel): string
    {
        if (!$rel) return '';
        if (preg_match('~^https?://~', $rel)) return $rel;
        $f = PP_ROOT . '/' . $rel;
        return self::url($rel) . (is_file($f) ? '?v=' . substr((string)filemtime($f), -6) : '');
    }

    /** Indirizzo di una pagina in una lingua. $path è la parte dopo la lingua, es. 'notizie/titolo'. */
    public static function link(string $path = '', ?string $lang = null): string
    {
        $lang = $lang ?: I18n::$current;
        $path = trim($path, '/');
        return self::url($lang . '/' . ($path === '' ? '' : $path . (str_contains($path, '.') ? '' : '/')));
    }

    /** Slug di una sezione fissa nella lingua (config 'sections': stringa o array per lingua). */
    public static function section(string $key, ?string $lang = null): string
    {
        $v = self::$config['sections'][$key] ?? $key;
        if (!is_array($v)) return $v;
        $lang = $lang ?: I18n::$current;
        return $v[$lang] ?? $v[I18n::$master] ?? reset($v);
    }

    /** Prefisso dell'indirizzo per un tipo di contenuto (null = il tipo non ha pagine proprie). */
    public static function typePrefix(string $type, string $lang): ?string
    {
        return match ($type) {
            'page' => '',
            'news' => self::section('news', $lang) . '/',
            'story' => self::section('stories', $lang) . '/',
            default => Hooks::first('type_prefix', $type, $lang),
        };
    }

    /** Indirizzo pubblico di un contenuto. */
    public static function entryUrl(array $e, ?string $lang = null): string
    {
        $lang = $lang ?: I18n::$current;
        $prefix = self::typePrefix($e['type'], $lang);
        if ($prefix === null) return '';
        if ($e['type'] === 'page' && ($e['base_slug'] ?? $e['slug']) === 'home') return self::link('', $lang);
        return self::link($prefix . Content::slugFor((int)$e['id'], $lang), $lang);
    }

    /* ---------- temi ---------- */

    public static function theme(): string
    {
        $t = self::$theme ?? Settings::get('theme', self::$config['theme'] ?? 'tipografico');
        return preg_replace('~[^a-z0-9_-]~', '', (string)$t) ?: 'tipografico';
    }

    public static function themes(): array
    {
        $out = [];
        foreach (glob(PP_ROOT . '/pp-themes/*/theme.json') as $f) {
            $slug = basename(dirname($f));
            if ($slug[0] === '_') continue;
            $out[$slug] = (json_decode((string)file_get_contents($f), true) ?: []) + ['slug' => $slug];
        }
        uasort($out, fn($a, $b) => ($a['order'] ?? 99) <=> ($b['order'] ?? 99));
        return $out;
    }

    /** Cerca un file del tema: personalizzazioni del sito → tema attivo → base comune. */
    public static function themeFile(string $rel): ?string
    {
        foreach ([PP_SITE . '/theme/', PP_ROOT . '/pp-themes/' . self::theme() . '/', PP_ROOT . '/pp-themes/_base/'] as $dir) {
            if (is_file($dir . $rel)) return $dir . $rel;
        }
        return null;
    }

    /** URL di un file statico del tema, con la stessa catena di ricerca. */
    public static function asset(string $path): string
    {
        $path = ltrim($path, '/');
        $file = self::themeFile('assets/' . $path);
        if (!$file) return self::url('pp-themes/_base/assets/' . $path);
        $rel = substr($file, strlen(PP_ROOT) + 1);
        return self::url(str_replace('\\', '/', $rel)) . '?v=' . substr((string)filemtime($file), -6);
    }

    /* ---------- router pubblico ---------- */

    public static function run(): void
    {
        $uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        $path = trim((string)substr($uri, strlen(self::base())), '/');
        $seg = array_values(array_filter(explode('/', $path), 'strlen'));
        self::headers();

        if (($seg[0] ?? '') === self::adminSlug()) { require PP_ROOT . '/core/Admin.php'; Admin::handle(array_slice($seg, 1)); return; }
        if (($seg[0] ?? '') === 'download') { self::download((int)($seg[1] ?? 0), (string)($seg[2] ?? I18n::$master), false); return; }

        // richieste ai moduli (form, file generati: sitemap.xml, robots.txt, llms.txt…)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { Hooks::first('post', $path); http_response_code(405); return; }
        $file = Hooks::first('file', $path);
        if ($file) {
            header('Content-Type: ' . $file['type']);
            echo $file['body'];
            return;
        }
        if (!$seg) self::redirect(self::link('', I18n::detect()), 302);

        $page = self::page($seg);
        if ($page['status'] === 404 && ($r = Redirects::find('/' . $path))) {
            try { DB::run('UPDATE pp_redirects SET hits = hits + 1 WHERE id = ?', [$r['id']]); } catch (Throwable $e) {}
            self::redirect(preg_match('~^https?://~', $r['to_path']) ? $r['to_path'] : self::url($r['to_path']), (int)$r['code']);
        }
        http_response_code($page['status']);
        echo $page['html'];
    }

    /** Risolve un percorso pubblico e restituisce ['status' => 200|404, 'html' => …]. Usato anche per l'HTML statico. */
    public static function page(array $seg): array
    {
        $lang = $seg[0] ?? I18n::$master;
        if (!I18n::valid($lang)) return self::notFound(I18n::$master);
        I18n::$current = $lang;
        $r = self::resolve($lang, array_slice($seg, 1));
        if (!$r) return self::notFound($lang);
        self::$route = ['name' => $r['name'], 'params' => $r['params'] ?? []];
        return ['status' => 200, 'html' => self::render($r['tpl'], $r['vars'] ?? [])];
    }

    private static function resolve(string $lang, array $rest): ?array
    {
        if (!$rest) return ['name' => 'home', 'tpl' => 'home'];
        if (count($rest) > 2) return null;
        $first = $rest[0];
        $second = $rest[1] ?? null;

        $m = Hooks::first('route', $lang, $rest);
        if ($m) return $m;

        foreach (['news' => 'news', 'stories' => 'story'] as $sec => $type) {
            if ($first !== self::section($sec, $lang)) continue;
            if (!$second) return ['name' => $sec, 'tpl' => $sec === 'news' ? 'news' : 'stories'];
            $item = Content::entry($type, $second, $lang, Content::$preview);
            return $item ? ['name' => $sec . '-item', 'params' => [(int)$item['id']], 'tpl' => $type === 'news' ? 'news-item' : 'story', 'vars' => ['item' => $item]] : null;
        }
        if (!$second && $first === self::section('results', $lang)) return ['name' => 'results', 'tpl' => 'results'];
        if (!$second && $first === self::section('partners', $lang)) return ['name' => 'partners', 'tpl' => 'partners'];
        if (!$second) {
            $page = Content::entry('page', $first, $lang, Content::$preview);
            if ($page && $page['base_slug'] !== 'home') {
                $tpl = $page['template'] !== 'default' && self::themeFile('templates/' . $page['template'] . '.php') ? $page['template'] : 'page';
                return ['name' => 'page', 'params' => [(int)$page['id']], 'tpl' => $tpl, 'vars' => ['page' => $page]];
            }
        }
        return null;
    }

    /**
     * Tutte le pagine pubbliche, con il percorso in ogni lingua:
     * [['kind' => 'page', 'paths' => ['en' => 'project', 'it' => 'progetto', …]], …]
     * I moduli aggiungono le loro con il filtro 'paths'.
     */
    public static function items(): array
    {
        $items = [['kind' => 'home', 'paths' => array_fill_keys(I18n::codes(), '')]];
        foreach (['news', 'stories', 'results', 'partners'] as $sec) {
            $p = [];
            foreach (I18n::codes() as $l) $p[$l] = self::section($sec, $l);
            $items[] = ['kind' => $sec, 'paths' => $p];
        }
        foreach (DB::all("SELECT * FROM pp_entries WHERE type IN ('page','news','story') AND " . Content::live() . " ORDER BY type, menu_order, id") as $e) {
            if ($e['type'] === 'page' && $e['slug'] === 'home') continue;
            $p = [];
            foreach (I18n::codes() as $l) $p[$l] = self::typePrefix($e['type'], $l) . Content::slugFor((int)$e['id'], $l);
            $items[] = ['kind' => $e['type'] === 'page' ? 'page' : ($e['type'] === 'news' ? 'news-item' : 'stories-item'), 'id' => (int)$e['id'], 'paths' => $p];
        }
        return Hooks::filter('paths', $items);
    }

    /** Percorsi di una lingua: [path => kind]. */
    public static function paths(?string $lang = null): array
    {
        $lang = $lang ?: I18n::$master;
        $out = [];
        foreach (self::items() as $it) if (isset($it['paths'][$lang])) $out[$it['paths'][$lang]] = $it['kind'];
        return $out;
    }

    /** Stessa pagina in un'altra lingua (selettore lingue e hreflang). */
    public static function alternate(string $lang): string
    {
        $r = self::$route;
        $alt = Hooks::first('alternate', $r, $lang);
        if ($alt) return $alt;
        $id = (int)($r['params'][0] ?? 0);
        return match ($r['name']) {
            'home', '404' => self::link('', $lang),
            'news', 'stories', 'results', 'partners' => self::link(self::section($r['name'], $lang), $lang),
            'news-item' => self::link(self::section('news', $lang) . '/' . Content::slugFor($id, $lang), $lang),
            'stories-item' => self::link(self::section('stories', $lang) . '/' . Content::slugFor($id, $lang), $lang),
            'page' => self::link(Content::slugFor($id, $lang), $lang),
            default => self::link('', $lang),
        };
    }

    public static function current(): string { return self::alternate(I18n::$current); }

    public static function render(string $tpl, array $vars): string
    {
        $file = str_contains($tpl, '/') ? $tpl : self::themeFile('templates/' . $tpl . '.php');
        if (!$file || !is_file($file)) $file = self::themeFile('templates/page.php');
        [$html, $defined] = (function () use ($file, $vars) {
            extract($vars, EXTR_SKIP);
            ob_start();
            require $file;
            return [ob_get_clean(), get_defined_vars()];
        })();
        // le variabili impostate dal template (pageTitle, metaDesc, ogImage…) passano al layout
        $layoutVars = array_intersect_key($defined, array_flip(['pageTitle', 'metaDesc', 'ogImage', 'seoType', 'seoDate', 'bodyClass', 'noindex']));
        $layoutVars['content'] = $html;
        return (function () use ($layoutVars) {
            extract($layoutVars, EXTR_SKIP);
            ob_start();
            require PP::themeFile('templates/layout.php');
            return ob_get_clean();
        })();
    }

    public static function notFound(string $lang): array
    {
        I18n::$current = $lang;
        self::$route = ['name' => '404', 'params' => []];
        return ['status' => 404, 'html' => self::render('404', [])];
    }

    public static function redirect(string $to, int $code = 303): never
    {
        header('Location: ' . $to, true, $code);
        exit;
    }

    public static function csp(): string
    {
        $frames = implode(' ', Hooks::filter('csp_frames', ['https://www.youtube-nocookie.com', 'https://player.vimeo.com']));
        return "default-src 'self'; img-src 'self' data: https://i.ytimg.com; media-src 'self'; frame-src $frames; style-src 'self' 'unsafe-inline'; script-src 'self'; connect-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'";
    }

    private static function headers(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-Frame-Options: SAMEORIGIN');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), interest-cohort=()');
        header('Content-Security-Policy: ' . self::csp());
    }

    /* ---------- download documenti: link stabili, il file può cambiare ---------- */

    public static function download(int $id, string $lang, bool $drafts): void
    {
        if (!I18n::valid($lang)) $lang = I18n::$master;
        $doc = Content::doc($id, $lang, $drafts);
        $file = $doc ? PP_ROOT . '/' . $doc['file_active'] : '';
        if (!$doc || !$doc['file_active'] || !is_file($file)) {
            $p = self::notFound($lang);
            http_response_code(404);
            echo $p['html'];
            return;
        }
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $name = Media::slug(($doc['code'] ? $doc['code'] . '-' : '') . $doc['title']) . ($doc['file_is_translated'] ? '-' . $lang : '') . '.' . $ext;
        $mime = class_exists('finfo') ? (new finfo(FILEINFO_MIME_TYPE))->file($file) : 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($file));
        header('Content-Disposition: ' . ($ext === 'pdf' ? 'inline' : 'attachment') . '; filename="' . $name . '"');
        header('Cache-Control: public, max-age=300');
        readfile($file);
        exit;
    }

    public static function docUrl(array $doc, ?string $lang = null): string
    {
        return self::url('download/' . (int)$doc['id'] . '/' . ($lang ?: I18n::$current));
    }

    /* ---------- testo dei contenuti: shortcode ---------- */

    /**
     * [doc:12] link al documento · [docs:policy] elenco di una categoria · [video:ID] YouTube · [vimeo:ID]
     * I moduli aggiungono altri shortcode con il filtro 'body' (es. [form:contact]).
     */
    public static function body(string $html): string
    {
        $lang = I18n::$current;
        $html = preg_replace_callback('~(?:<p>\s*)?\[doc:(\d+)\](?:\s*</p>)?~', function ($m) use ($lang) {
            $d = Content::doc((int)$m[1], $lang);
            return $d ? self::partial('doc-link', ['doc' => $d]) : '';
        }, $html);
        $html = preg_replace_callback('~(?:<p>\s*)?\[docs:([a-z0-9_-]+)\](?:\s*</p>)?~', fn($m) => self::partial('doc-list', ['docs' => Content::docs($lang, $m[1])]), $html);
        $html = preg_replace_callback('~(?:<p>\s*)?\[video:([A-Za-z0-9_-]{6,20})\](?:\s*</p>)?~', fn($m) => self::partial('video', ['id' => $m[1], 'service' => 'youtube']), $html);
        $html = preg_replace_callback('~(?:<p>\s*)?\[vimeo:(\d{5,12})\](?:\s*</p>)?~', fn($m) => self::partial('video', ['id' => $m[1], 'service' => 'vimeo']), $html);
        return Hooks::filter('body', $html);
    }

    public static function partial(string $name, array $vars = []): string
    {
        $file = self::themeFile('partials/' . $name . '.php');
        if (!$file) return '';
        extract($vars, EXTR_SKIP);
        ob_start();
        require $file;
        return (string)ob_get_clean();
    }
}

/* ---------- funzioni brevi per i template ---------- */

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function t(string $key, ?string $lang = null): string { return I18n::t($key, $lang); }
/** Testo di interfaccia già pulito che può contenere <mark>, <em>, <strong>, <a>. */
function th(string $key, ?string $lang = null): string { return Sanitizer::inline(I18n::t($key, $lang)); }
function lang(): string { return I18n::$current; }
function link_to(string $path = '', ?string $lang = null): string { return PP::link($path, $lang); }
function section_link(string $key, string $rest = ''): string { return PP::link(PP::section($key) . ($rest !== '' ? '/' . $rest : '')); }
function entry_url(array $e): string { return PP::entryUrl($e); }
function asset(string $p): string { return PP::asset($p); }
function upload_url(string $p): string { return PP::upload($p); }
function setting(string $k, $d = null) { return Settings::get($k, $d); }
function partial(string $name, array $vars = []): string { return PP::partial($name, $vars); }

/**
 * <img> con versione ridotta per gli schermi piccoli (srcset), dimensioni e caricamento pigro.
 * $alt vuoto = immagine decorativa.
 */
function img(string $rel, string $alt = '', string $sizes = '100vw', string $class = '', bool $lazy = true): string
{
    if (!$rel) return '';
    $abs = PP_ROOT . '/' . $rel;
    $info = is_file($abs) ? @getimagesize($abs) : false;
    $w = $info[0] ?? 0; $h = $info[1] ?? 0;
    $small = preg_replace('~(\.[a-z0-9]+)$~i', '-960$1', $rel);
    $srcset = '';
    if ($small !== $rel && is_file(PP_ROOT . '/' . $small) && $w > 960) {
        $srcset = ' srcset="' . e(PP::upload($small)) . ' 960w, ' . e(PP::upload($rel)) . ' ' . $w . 'w" sizes="' . e($sizes) . '"';
    }
    return '<img src="' . e(PP::upload($rel)) . '"' . $srcset . ' alt="' . e($alt) . '"'
        . ($w ? ' width="' . $w . '" height="' . $h . '"' : '')
        . ($class ? ' class="' . e($class) . '"' : '')
        . ($lazy ? ' loading="lazy" decoding="async"' : '') . '>';
}

function fmt_date(string $d, ?string $lang = null): string
{
    if (!$d) return '';
    $ts = strtotime($d);
    if (!$ts) return e($d);
    $lang = $lang ?: I18n::$current;
    if (class_exists('IntlDateFormatter')) {
        $f = new IntlDateFormatter(I18n::$langs[$lang]['locale'] ?? $lang, IntlDateFormatter::LONG, IntlDateFormatter::NONE);
        $out = $f->format($ts);
        if ($out) return $out;
    }
    return date('d.m.Y', $ts);
}
