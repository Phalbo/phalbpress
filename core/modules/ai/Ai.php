<?php
final class Ai
{
    /** Crawler che raccolgono testi per addestrare i modelli. */
    public const TRAINING = ['GPTBot', 'ClaudeBot', 'anthropic-ai', 'Google-Extended', 'CCBot', 'Applebot-Extended', 'meta-externalagent', 'Bytespider', 'cohere-training-data-crawler'];
    /** Crawler che cercano e citano le pagine quando un utente fa una domanda. */
    public const SEARCH = ['OAI-SearchBot', 'ChatGPT-User', 'Claude-SearchBot', 'Claude-User', 'PerplexityBot', 'Perplexity-User'];

    public static function policy(): string
    {
        return Settings::get('ai_policy', PP::$config['ai_policy'] ?? 'open');
    }

    public static function robots(): string
    {
        $p = self::policy();
        $admin = PP::base() . '/' . PP::adminSlug() . '/';
        $out = "# robots.txt generato da PhalbPress\n";
        // sito in lavorazione: tutto chiuso
        if (class_exists('Seo') && Seo::hidden()) return $out . "# Il sito è nascosto ai motori di ricerca (Gestione → SEO)\nUser-agent: *\nDisallow: /\n";
        $block = $p === 'closed' ? array_merge(self::TRAINING, self::SEARCH) : ($p === 'search' ? self::TRAINING : []);
        foreach ($block as $bot) $out .= "User-agent: $bot\nDisallow: /\n\n";
        if ($p !== 'closed') foreach (self::SEARCH as $bot) $out .= "User-agent: $bot\nAllow: /\nDisallow: $admin\n\n";
        $out .= "User-agent: *\nAllow: /\nDisallow: $admin\nDisallow: " . PP::base() . "/install.php\n\n";
        $out .= 'Sitemap: ' . PP::abs(PP::url('sitemap.xml')) . "\n";
        return $out;
    }

    public static function md(string $html): string
    {
        $html = PP::body($html);
        $html = preg_replace('~<h2[^>]*>(.*?)</h2>~si', "\n## $1\n", $html);
        $html = preg_replace('~<h3[^>]*>(.*?)</h3>~si', "\n### $1\n", $html);
        $html = preg_replace('~<li[^>]*>(.*?)</li>~si', "- $1\n", $html);
        $html = preg_replace('~<a [^>]*href="([^"]+)"[^>]*>(.*?)</a>~si', '[$2]($1)', $html);
        $html = preg_replace('~<(strong|b)>(.*?)</\1>~si', '**$2**', $html);
        $html = preg_replace('~</p>|<br\s*/?>~i', "\n\n", $html);
        $txt = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
        $txt = preg_replace('~\]\((/[^)]*)\)~', '](' . PP::abs('') . '$1)', $txt);
        return trim(preg_replace("~\n{3,}~", "\n\n", preg_replace('~[ \t]+~', ' ', $txt)));
    }

    private static function summary(string $l): string
    {
        $s = trim(t('ai_summary', $l));
        if ($s === '') $s = trim(t('claim', $l));
        return $s;
    }

    public static function llms(string $l): string
    {
        $prev = I18n::$current; I18n::$current = $l;
        $site = class_exists('Identity') ? Identity::name() : PP::$config['site_name'];
        $out = "# $site\n\n";
        if ($sum = self::summary($l)) $out .= "> " . str_replace("\n", ' ', $sum) . "\n\n";
        $facts = array_filter(array_map('trim', explode("\n", t('ai_facts', $l))));
        if ($facts) $out .= implode("\n", array_map(fn($f) => '- ' . $f, $facts)) . "\n\n";
        $out .= "## Pages\n\n";
        $out .= '- [' . t('home') . '](' . PP::abs(PP::link('', $l)) . ")\n";
        foreach (Content::menu($l) as $p) $out .= '- [' . $p['title'] . '](' . PP::abs(PP::entryUrl($p, $l)) . ')' . ($p['excerpt'] ? ': ' . $p['excerpt'] : '') . "\n";
        foreach (['news' => 'news_title', 'stories' => 'stories_title', 'results' => 'results_title', 'partners' => 'partners_title'] as $k => $tk) $out .= '- [' . t($tk) . '](' . PP::abs(PP::link(PP::section($k, $l), $l)) . ")\n";
        $news = Content::entries('news', $l, 20, 'pub_date DESC, id DESC');
        if ($news) {
            $out .= "\n## News\n\n";
            foreach ($news as $n) $out .= '- [' . $n['title'] . '](' . PP::abs(PP::entryUrl($n, $l)) . ') (' . $n['pub_date'] . ')' . ($n['excerpt'] ? ': ' . $n['excerpt'] : '') . "\n";
        }
        $docs = array_filter(Content::docs($l), fn($d) => $d['file_active'] && $d['file_size']);
        if ($docs) {
            $out .= "\n## Documents\n\n";
            foreach ($docs as $d) $out .= '- [' . trim($d['code'] . ' ' . $d['title']) . '](' . PP::abs(PP::docUrl($d, $l)) . ')' . ($d['excerpt'] ? ': ' . $d['excerpt'] : '') . "\n";
        }
        $out .= "\n## Optional\n\n- [Full text](" . PP::abs(PP::url('llms-full.txt')) . ")\n";
        foreach (I18n::codes() as $o) if ($o !== $l) $out .= '- [' . I18n::name($o) . '](' . PP::abs(PP::url(($o === I18n::$master ? '' : $o . '/') . 'llms.txt')) . ")\n";
        I18n::$current = $prev;
        return $out;
    }

    public static function full(string $l): string
    {
        $prev = I18n::$current; I18n::$current = $l;
        $site = class_exists('Identity') ? Identity::name() : PP::$config['site_name'];
        $out = "# $site\n\n";
        if ($sum = self::summary($l)) $out .= "> $sum\n\n";
        foreach (['page', 'news', 'story', 'partner', 'country'] as $type) {
            foreach (Content::entries($type, $l, 0) as $e) {
                if ($type === 'page' && $e['base_slug'] === 'home') continue;
                $url = PP::entryUrl($e, $l) ?: PP::link($type === 'partner' ? PP::section('partners', $l) : '', $l);
                $out .= "\n---\n\n# " . $e['title'] . "\n\nURL: " . PP::abs($url) . "\n";
                if (!empty($e['pub_date']) && $type === 'news') $out .= 'Date: ' . $e['pub_date'] . "\n";
                if ($e['excerpt']) $out .= "\n" . $e['excerpt'] . "\n";
                if ($e['body']) $out .= "\n" . self::md($e['body']) . "\n";
            }
        }
        I18n::$current = $prev;
        return $out;
    }

    public static function admin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (in_array(Admin::post('policy'), ['open', 'search', 'closed'], true)) Settings::set('ai_policy', Admin::post('policy'));
            if (isset($_POST['summary'])) Content::saveString('ai_summary', I18n::$master, (string)$_POST['summary']);
            if (isset($_POST['facts'])) Content::saveString('ai_facts', I18n::$master, (string)$_POST['facts']);
            Auth::log('save ai');
            Admin::changed('ai');
            Admin::flash('Impostazioni AI salvate. Traduci riassunto e fatti in Testi del sito → AI.');
            PP::redirect(Admin::u('ai'));
        }
        Admin::view(__DIR__ . '/admin.php', ['title' => 'AI search', 'policy' => self::policy(), 'preview' => self::llms(I18n::$master)]);
    }
}
