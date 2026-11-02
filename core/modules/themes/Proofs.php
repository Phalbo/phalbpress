<?php
/**
 * Provini per il cliente: per ogni tema una copia statica e navigabile del sito in /provini/<tema>/, con un indice in /provini/.
 * Pagine con i contenuti veri (home, pagine, sezioni, categorie del portfolio, gli ultimi lavori e le ultime news),
 * coi colori di ogni tema o con l'identità del sito. Nascoste ai motori di ricerca; il sito vero non cambia.
 * I link tra le pagine generate restano dentro il provino; quelli verso pagine non generate portano al sito.
 */
final class Proofs
{
    public const DIR = 'provini';
    private const STORIES = 24;
    private const NEWS = 10;

    private static function manifestFile(): string { return PP_ROOT . '/data/provini.json'; }

    public static function info(): array
    {
        return is_file(self::manifestFile()) ? (json_decode((string)file_get_contents(self::manifestFile()), true) ?: []) : [];
    }

    /** Le pagine da generare: [kind, paths per lingua]. */
    private static function items(): array
    {
        $stories = array_flip(array_map('intval', array_column(DB::all("SELECT id FROM pp_entries WHERE type = 'story' AND " . Content::live() . ' ORDER BY pub_date DESC, id DESC LIMIT ' . self::STORIES), 'id')));
        $news = array_flip(array_map('intval', array_column(DB::all("SELECT id FROM pp_entries WHERE type = 'news' AND " . Content::live() . ' ORDER BY pub_date DESC, id DESC LIMIT ' . self::NEWS), 'id')));
        // anche i lavori scelti a mano nelle fasce, così la home non porta fuori dal provino
        foreach (DB::all("SELECT meta FROM pp_entries WHERE type = 'block'") as $r) {
            $m = json_decode((string)$r['meta'], true) ?: [];
            foreach (array_filter(array_map('intval', explode(',', (string)($m['picks'] ?? '')))) as $id) $stories[$id] = 1;
        }
        return array_values(array_filter(PP::items(), function ($it) use ($stories, $news) {
            if ($it['kind'] === 'stories-item') return isset($stories[$it['id'] ?? 0]);
            if ($it['kind'] === 'news-item') return isset($news[$it['id'] ?? 0]);
            return true;
        }));
    }

    /** Genera i provini. $palette: true = colori e font di ogni tema, false = l'identità del sito. $only: slug dei temi ([] = tutti). */
    public static function build(bool $palette, array $only = []): array
    {
        @set_time_limit(900);
        $t0 = microtime(true);
        self::clear();
        $themes = PP::themes();
        if ($only) $themes = array_intersect_key($themes, array_flip($only));
        $items = self::items();
        $base = rtrim(PP::base(), '/');
        $savedTheme = PP::$theme; $savedLang = I18n::$current; $savedRoute = PP::$route;
        PP::$building = true;
        $files = 0;
        foreach ($themes as $slug => $t) {
            PP::$theme = $slug;
            $root = self::DIR . '/' . $slug;
            // percorsi generati per lingua: i link verso queste pagine restano nel provino
            $have = [];
            foreach ($items as $it) foreach ($it['paths'] as $l => $p) $have[trim($l . '/' . $p, '/')] = true;
            $style = $palette && class_exists('Identity') ? '<style>' . Identity::css($t) . '</style>' : '';
            foreach ($items as $it) foreach ($it['paths'] as $l => $p) {
                $seg = array_merge([$l], $p === '' ? [] : explode('/', $p));
                foreach ($seg as $sg) if (!preg_match('~^[a-z0-9][a-z0-9._-]*$~', $sg)) continue 2;
                $page = PP::page($seg);
                if ($page['status'] !== 200) continue;
                $html = self::rewrite($page['html'], $base, $root, $have, $style, (string)($t['name'] ?? $slug));
                self::write($root . '/' . implode('/', $seg) . '/index.html', $html);
                $files++;
            }
            // la radice del provino porta alla home nella lingua principale
            self::write($root . '/index.html', '<!doctype html><meta charset="utf-8"><meta name="robots" content="noindex, nofollow"><meta http-equiv="refresh" content="0; url=' . e($base . '/' . $root . '/' . I18n::$master . '/') . '"><title>' . e($t['name'] ?? $slug) . '</title>');
        }
        PP::$theme = $savedTheme; I18n::$current = $savedLang; PP::$route = $savedRoute;
        PP::$building = false;
        self::write(self::DIR . '/index.html', self::index($themes, $palette));
        self::write(self::DIR . '/robots.txt', "User-agent: *\nDisallow: /\n");
        $info = ['at' => Content::now(), 'themes' => array_keys($themes), 'palette' => $palette, 'files' => $files, 'seconds' => round(microtime(true) - $t0, 1)];
        if (!is_dir(PP_ROOT . '/data')) mkdir(PP_ROOT . '/data', 0775, true);
        file_put_contents(self::manifestFile(), json_encode($info));
        return $info;
    }

    /** Link interni verso il provino, niente indicizzazione, una barretta che dice cos'è. */
    private static function rewrite(string $html, string $base, string $root, array $have, string $style, string $name): string
    {
        $html = preg_replace_callback('~(href|action)="' . preg_quote($base, '~') . '/([a-z]{2}(?:/[^"#?]*)?)/?([#?][^"]*)?"~', function ($m) use ($base, $root, $have) {
            $path = trim($m[2], '/');
            if ($m[1] === 'action' || !isset($have[$path])) return $m[0];
            return $m[1] . '="' . $base . '/' . $root . '/' . $path . '/' . ($m[3] ?? '') . '"';
        }, $html);
        $html = preg_replace('~href="' . preg_quote($base, '~') . '/"~', 'href="' . $base . '/' . $root . '/"', $html);
        $html = preg_replace('~<meta name="robots"[^>]*>~i', '', $html);
        $html = str_replace('</head>', '<meta name="robots" content="noindex, nofollow">' . $style . '</head>', $html);
        $bar = '<div class="pp-proof" role="note" style="position:fixed;z-index:99999;right:12px;bottom:12px;padding:8px 12px;border-radius:999px;background:#111;color:#fff;font:600 12px/1.2 system-ui,sans-serif;box-shadow:0 4px 18px rgba(0,0,0,.25)">Provino · ' . e($name) . ' · <a href="' . e($base . '/' . self::DIR . '/') . '" style="color:#fff">tutti i temi</a></div>';
        return preg_replace('~</body>~', $bar . '</body>', $html, 1);
    }

    private static function index(array $themes, bool $palette): string
    {
        $base = rtrim(PP::base(), '/');
        $site = e(PP::$config['site_name'] ?? 'Sito');
        $cards = '';
        foreach ($themes as $slug => $t) {
            $pal = $palette ? ($t['palette'] ?? []) : (class_exists('Identity') ? Identity::get()['colors'] : []);
            $sw = '';
            foreach (array_slice(array_values(array_filter($pal, fn($c) => is_string($c) && preg_match('~^#[0-9a-f]{6}$~i', $c))), 0, 6) as $c) $sw .= '<i style="background:' . e($c) . '"></i>';
            $cards .= '<a class="c" href="' . e($base . '/' . self::DIR . '/' . $slug . '/' . I18n::$master . '/') . '"><span class="sw">' . $sw . '</span><strong>' . e($t['name'] ?? $slug) . '</strong><span>' . e($t['description'] ?? '') . '</span></a>';
        }
        $how = $palette ? 'ognuno con i suoi colori e i suoi caratteri' : 'tutti con i colori e i caratteri del sito';
        return '<!doctype html><html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow"><title>Provini · ' . $site . '</title>'
            . '<style>:root{--bg:#f3f4f6;--ink:#14181f;--muted:#5d6570;--card:#fff;--line:#d9dde3}@media (prefers-color-scheme:dark){:root{--bg:#14171c;--ink:#eef1f5;--muted:#a1aab5;--card:#1d2128;--line:#323844}}'
            . '*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font:16px/1.5 system-ui,-apple-system,"Segoe UI",sans-serif;padding:clamp(24px,5vw,64px) max(16px,4vw)}'
            . 'h1{font-size:clamp(1.8rem,1.2rem+2vw,2.8rem);line-height:1.1;margin:0 0 8px}p{margin:0 0 28px;color:var(--muted);max-width:62ch}'
            . '.g{display:grid;gap:16px;grid-template-columns:repeat(auto-fill,minmax(min(100%,280px),1fr))}.c{display:grid;gap:8px;align-content:start;padding:18px;background:var(--card);border:1px solid var(--line);border-radius:10px;color:inherit;text-decoration:none}'
            . '.c:hover,.c:focus-visible{border-color:var(--ink);outline:none}.c strong{font-size:1.15rem}.c span:last-child{color:var(--muted);font-size:.92rem}.sw{display:flex;gap:4px}.sw i{width:26px;height:26px;border-radius:50%;border:1px solid var(--line)}</style></head><body>'
            . '<h1>' . $site . ': ' . count($themes) . ' versioni</h1><p>Lo stesso sito, con gli stessi contenuti, in ' . count($themes) . ' temi, ' . $how . '. Si naviga come un sito vero; è una copia di prova e non è il sito pubblico.</p>'
            . '<div class="g">' . $cards . '</div></body></html>';
    }

    private static function write(string $rel, string $content): void
    {
        if (str_contains($rel, '..') || !str_starts_with($rel, self::DIR . '/')) return;
        $abs = PP_ROOT . '/' . $rel;
        if (!is_dir(dirname($abs))) mkdir(dirname($abs), 0755, true);
        file_put_contents($abs, $content);
    }

    /** Toglie tutti i provini. */
    public static function clear(): void
    {
        $dir = PP_ROOT . '/' . self::DIR;
        if (is_dir($dir)) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($it as $f) $f->isDir() && !$f->isLink() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
            @rmdir($dir);
        }
        @unlink(self::manifestFile());
    }
}
