<?php
/**
 * Feed RSS delle news per ogni lingua: /feed/{lang}.xml (anche nel sito statico), segnalato nell'<head>.
 * Serve ad aggregatori, newsletter automatiche e partner che ripubblicano le notizie.
 */
return [
    'name' => 'feed',
    'label' => 'Feed RSS',
    'version' => '1.0.0',
    'boot' => function () {
        $xml = function (string $lang): string {
            $x = fn($s) => htmlspecialchars((string)$s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $name = class_exists('Identity') ? Identity::name() : (PP::$config['site_name'] ?? '');
            $items = '';
            foreach (Content::entries('news', $lang, 20, 'pub_date DESC, id DESC') as $n) {
                $url = PP::abs(PP::entryUrl($n));
                $items .= "  <item>\n    <title>" . $x($n['title']) . "</title>\n    <link>" . $x($url) . "</link>\n    <guid isPermaLink=\"true\">" . $x($url) . "</guid>\n"
                    . '    <pubDate>' . date(DATE_RSS, strtotime($n['pub_date']) ?: time()) . "</pubDate>\n    <description>" . $x($n['excerpt']) . "</description>\n"
                    . ($n['image'] ? '    <enclosure url="' . $x(PP::abs(PP::upload($n['image']))) . '" type="image/' . (str_ends_with($n['image'], '.webp') ? 'webp' : 'jpeg') . "\" length=\"0\"/>\n" : '')
                    . "  </item>\n";
            }
            return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n<channel>\n"
                . '  <title>' . $x($name . ' · ' . I18n::name($lang)) . "</title>\n  <link>" . $x(PP::abs(PP::link(PP::section('news', $lang), $lang))) . "</link>\n"
                . '  <atom:link href="' . $x(PP::abs(PP::url('feed/' . $lang . '.xml'))) . "\" rel=\"self\" type=\"application/rss+xml\"/>\n"
                . '  <description>' . $x(t('news_intro')) . "</description>\n  <language>" . $x(I18n::hreflang($lang)) . "</language>\n" . $items . "</channel>\n</rss>\n";
        };
        Hooks::on('file', function ($path) use ($xml) {
            if (!preg_match('~^feed/([a-z]{2,3}(?:-[A-Za-z]+)?)\.xml$~', $path, $m) || !I18n::valid($m[1])) return null;
            I18n::$current = $m[1];
            return ['type' => 'application/rss+xml; charset=utf-8', 'body' => $xml($m[1])];
        });
        Hooks::on('static_files', function ($f) { foreach (I18n::codes() as $l) $f[] = 'feed/' . $l . '.xml'; return $f; });
        Hooks::on('head', fn($ctx) => '<link rel="alternate" type="application/rss+xml" title="' . e(t('news_title')) . '" href="' . e(PP::url('feed/' . lang() . '.xml')) . '">');
    },
];
