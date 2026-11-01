<?php
/**
 * Testo di WordPress → HTML pulito di PhalbPress.
 * Gli shortcode dei costruttori di pagine (WPBakery, Jupiter, Gutenberg classico) diventano HTML semplice:
 * le colonne e le righe spariscono (l'impaginazione la fa il tema), restano titoli, testo, foto, gallerie, video, pulsanti.
 * Le foto e i file vengono chiesti a $media (url o id → percorso nuovo in uploads/), che li importa al primo uso.
 */
final class WpContent
{
    /** Contenitori: si tiene solo quello che c'è dentro. */
    private const WRAP = ['vc_row', 'vc_row_inner', 'vc_column', 'vc_column_inner', 'vc_section', 'vc_column_text', 'mk_page_section',
        'mk_text_block', 'vc_tta_tabs', 'vc_tta_accordion', 'vc_tta_tour', 'vc_tta_section', 'mk_tabs', 'mk_tab', 'mk_toggle', 'mk_accordion',
        'mk_accordion_section', 'mk_custom_list', 'mk_custom_box', 'mk_dropcaps', 'mk_highlight', 'mk_tooltip', 'vc_accordions', 'mk_mini_callout', 'mk_content_box', 'mk_icon_box', 'mk_icon_box2', 'mk_box_icon', 'et_pb_section',
        'et_pb_row', 'et_pb_column', 'et_pb_text', 'fusion_builder_container', 'fusion_builder_row', 'fusion_builder_column', 'fusion_text'];
    /** Elementi solo grafici o elenchi automatici del vecchio tema: si tolgono. */
    private const DROP = ['vc_separator', 'vc_empty_space', 'mk_padding_divider', 'mk_divider', 'mk_font_icons', 'mk_social_networks',
        'mk_portfolio', 'mk_portfolio_carousel', 'mk_news', 'mk_blog', 'mk_employees', 'mk_clients', 'mk_fullwidth_slideshow', 'mk_slideshow',
        'mk_layerslider', 'mk_revslider', 'rev_slider', 'rev_slider_vc', 'layerslider', 'layerslider_vc', 'masterslider', 'masterslider_pb',
        'mk_edge_slider', 'mk_animated_columns', 'vc_basic_grid', 'vc_masonry_grid', 'mk_contact_form', 'contact-form-7', 'mc4wp_form',
        'mk_moving_image', 'mk_svg_icons', 'mk_blog_carousel', 'mk_blog_showcase', 'mk_page_title_box', 'mk_testimonials', 'vc_raw_js',
        'wpcode', 'mk_category', 'vc_facebook', 'ares_logo_grid', 'modern_services_cards', 'vc_icon', 'mk_countdown', 'mk_google_maps', 'vc_gmaps', 'wpml_language_selector_widget'];

    /** Shortcode incontrati e non gestiti: nome → quante volte (per il resoconto). */
    public static array $unknown = [];
    /** Elenchi automatici tolti (mk_portfolio, mk_news…): nome → quante volte. */
    public static array $dropped = [];

    /** @param callable(string $urlOrId, string $alt): array{0:string,1:string} $media restituisce [percorso nuovo, alt] o ['', ''] */
    public static function html(string $src, callable $media): string
    {
        $src = preg_replace('~<!--\s*/?wp:[^>]*-->~', '', $src);           // commenti di Gutenberg
        $src = str_replace(["\r\n", "\r"], "\n", $src);
        $nodes = self::parse($src);
        $html = self::render($nodes, $media);
        $html = self::embeds($html);
        $html = self::autop($html);
        $html = self::rewriteFiles($html, $media);
        $html = Sanitizer::html($html);
        // paragrafi vuoti lasciati dai contenitori
        $html = preg_replace('~<p>(?:\s|&nbsp;|<br\s*/?>)*</p>~u', '', $html);
        return trim(preg_replace("~\n{3,}~", "\n\n", $html));
    }

    /* ---------- parser degli shortcode ---------- */

    private static function parse(string $s): array
    {
        preg_match_all('~\[(/?)([A-Za-z][\w-]*)((?:[^\[\]"\']|"[^"]*"|\'[^\']*\')*?)(/?)\]~', $s, $all, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
        // un nome è "a coppia" se da qualche parte c'è la sua chiusura
        $closed = [];
        foreach ($all as $m) if ($m[1][0] === '/') $closed[strtolower($m[2][0])] = true;
        $root = ['name' => '#root', 'attrs' => [], 'children' => []];
        $stack = [&$root];
        $pos = 0;
        foreach ($all as $m) {
            $start = $m[0][1];
            if ($start > $pos) self::push($stack, substr($s, $pos, $start - $pos));
            $pos = $start + strlen($m[0][0]);
            $name = strtolower($m[2][0]);
            if ($m[1][0] === '/') {
                // chiude il più vicino aperto con quel nome
                for ($i = count($stack) - 1; $i > 0; $i--) {
                    if ($stack[$i]['name'] === $name) { array_splice($stack, $i); break; }
                }
                continue;
            }
            $node = ['name' => $name, 'attrs' => self::attrs($m[3][0]), 'children' => []];
            $top = &$stack[count($stack) - 1];
            $top['children'][] = $node;
            if (isset($closed[$name]) && $m[4][0] !== '/') $stack[] = &$top['children'][count($top['children']) - 1];
            unset($top);
        }
        if ($pos < strlen($s)) self::push($stack, substr($s, $pos));
        return $root['children'];
    }

    private static function push(array &$stack, string $text): void
    {
        $top = &$stack[count($stack) - 1];
        $top['children'][] = $text;
    }

    private static function attrs(string $s): array
    {
        $out = [];
        preg_match_all('~([\w-]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|(\S+))~', $s, $mm, PREG_SET_ORDER);
        foreach ($mm as $m) {
            $v = ($m[2] ?? '') !== '' ? $m[2] : ((($m[3] ?? '') !== '') ? $m[3] : (string)($m[4] ?? ''));
            $out[strtolower($m[1])] = html_entity_decode($v, ENT_QUOTES, 'UTF-8');
        }
        return $out;
    }

    /* ---------- da shortcode a HTML ---------- */

    private static function render(array $nodes, callable $media): string
    {
        $out = '';
        foreach ($nodes as $n) $out .= is_string($n) ? $n : self::node($n, $media);
        return $out;
    }

    private static function node(array $n, callable $media): string
    {
        $name = $n['name']; $a = $n['attrs'];
        $inner = fn() => self::render($n['children'], $media);
        $text = fn() => trim(html_entity_decode(strip_tags($inner()), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (in_array($name, self::WRAP, true)) return "\n\n" . $inner() . "\n\n";
        if (in_array($name, self::DROP, true)) { self::$dropped[$name] = (self::$dropped[$name] ?? 0) + 1; return ''; }
        switch ($name) {
            case 'mk_fancy_title': case 'mk_title_box': case 'mk_ornamental_title': case 'vc_custom_heading': case 'mk_heading': case 'et_pb_heading': case 'fusion_title':
                $t = $text() !== '' ? $text() : trim((string)($a['text'] ?? $a['title'] ?? ''));
                return $t === '' ? '' : "\n\n<h2>" . e($t) . "</h2>\n\n";
            case 'mk_blockquote': case 'vc_message': case 'mk_message_box':
                return "\n\n<blockquote>" . $inner() . "</blockquote>\n\n";
            case 'mk_image': case 'vc_single_image': case 'et_pb_image': case 'fusion_imageframe':
                $ref = (string)($a['src'] ?? $a['image'] ?? $a['image_id'] ?? '');
                if ($ref === '' && $name === 'fusion_imageframe') $ref = trim(strip_tags($inner()));
                [$path, $alt] = $media($ref, (string)($a['title'] ?? $a['alt'] ?? ''));
                if ($path === '') return '';
                $cap = trim((string)($a['caption'] ?? ''));
                $size = preg_match('~-(\d+)x(\d+)\.[A-Za-z0-9]+$~', $ref, $wh) ? ' width="' . (int)$wh[1] . '" height="' . (int)$wh[2] . '"' : '';
                return "\n\n<figure><img src=\"" . e(PP::url($path)) . '" alt="' . e($alt) . '"' . $size . '>' . ($cap !== '' ? '<figcaption>' . e($cap) . '</figcaption>' : '') . "</figure>\n\n";
            case 'mk_gallery': case 'vc_gallery': case 'gallery': case 'vc_images_carousel': case 'mk_image_slideshow':
                $ids = array_filter(array_map('trim', explode(',', (string)($a['images'] ?? $a['ids'] ?? $a['include'] ?? ''))));
                $parts = [];
                foreach ($ids as $id) {
                    [$p, $alt] = $media($id, '');
                    if ($p !== '' && preg_match('~\.(webp|jpe?g|png|gif)$~i', $p)) $parts[] = $p . '=' . str_replace(['|', '=', ']'], ' ', $alt);
                }
                return $parts ? "\n\n<p>[gallery:" . e(implode('|', $parts)) . "]</p>\n\n" : '';
            case 'vc_video': case 'mk_video': case 'embed': case 'et_pb_video': case 'fusion_youtube': case 'fusion_vimeo':
                $url = (string)($a['link'] ?? $a['src'] ?? $a['url'] ?? '');
                if (($a['host'] ?? '') !== '' && !empty($a['video_id'])) $url = ($a['host'] === 'vimeo' ? 'https://vimeo.com/' : 'https://youtu.be/') . $a['video_id'];
                if ($name === 'fusion_youtube' && !empty($a['id'])) $url = 'https://youtu.be/' . $a['id'];
                if ($name === 'fusion_vimeo' && !empty($a['id'])) $url = 'https://vimeo.com/' . $a['id'];
                if ($url === '') $url = $text();
                return "\n\n" . (self::videoCode($url) ?: '<p><a href="' . e($url) . '">' . e($url) . '</a></p>') . "\n\n";
            case 'mk_button': case 'mk_button_gradient': case 'vc_btn': case 'vc_button': case 'vc_button2': case 'et_pb_button': case 'fusion_button':
                $url = (string)($a['url'] ?? $a['link'] ?? $a['href'] ?? $a['button_url'] ?? '');
                if (str_starts_with($url, 'url:')) $url = self::vcLink($url)['url'];
                $label = $text() !== '' ? $text() : trim((string)($a['title'] ?? $a['text'] ?? $a['button_text'] ?? ''));
                if ($url === '' || $label === '') return '';
                return "\n\n<p><a href=\"" . e($url) . '">' . e($label) . "</a></p>\n\n";
            case 'mk_audio': case 'audio':
                $f = (string)($a['mp3_file'] ?? $a['mp3'] ?? $a['src'] ?? '');
                [$p] = $f !== '' ? $media($f, '') : [''];
                $label = trim((string)($a['audio_author'] ?? '')) ?: 'Audio';
                return $p !== '' ? "\n\n<p><a href=\"" . e(PP::url($p)) . '">' . e($label) . " (MP3)</a></p>\n\n" : '';
            case 'caption': case 'wp_caption':
                $html = $inner();
                if (!preg_match('~<img[^>]*>~i', $html, $img)) return $html;
                $cap = trim(strip_tags(str_replace($img[0], '', preg_replace('~<a[^>]*>\s*(<img[^>]*>)\s*</a>~i', '$1', $html))));
                return "\n\n<figure>" . $img[0] . ($cap !== '' ? '<figcaption>' . e($cap) . '</figcaption>' : '') . "</figure>\n\n";
            case 'vc_raw_html':
                $raw = trim(strip_tags($inner()));
                $dec = base64_decode(rawurldecode($raw), true);
                return "\n\n" . ($dec !== false && $dec !== '' ? rawurldecode($dec) : '') . "\n\n";
            case 'mk_milestone': case 'vc_counter': case 'mk_skill_meter':
                // contatori animati: resta il numero col suo testo
                $num = trim((string)($a['stop'] ?? $a['value'] ?? $a['percent'] ?? ''));
                $label = trim((string)($a['text'] ?? $a['title'] ?? '')) ?: $text();
                if ($num === '' && $label === '') return '';
                $num .= $name === 'mk_skill_meter' && $num !== '' ? '%' : (string)($a['suffix'] ?? $a['units'] ?? '');
                return "\n\n<p><strong>" . e(trim(($a['prefix'] ?? '') . $num)) . '</strong> ' . e(mb_strtoupper($label) === $label ? mb_strtoupper(mb_substr($label, 0, 1)) . mb_strtolower(mb_substr($label, 1)) : $label) . "</p>\n\n";
            case 'vc_toggle': case 'vc_accordion_tab':
                $t = trim((string)($a['title'] ?? ''));
                return "\n\n" . ($t !== '' ? '<h3>' . e($t) . '</h3>' : '') . $inner() . "\n\n";
        }
        self::$unknown[$name] = (self::$unknown[$name] ?? 0) + 1;
        return $inner();
    }

    /** "url:https%3A%2F%2F…|title:…|target:_blank" (WPBakery) → ['url' => …, 'title' => …] */
    private static function vcLink(string $s): array
    {
        $out = ['url' => '', 'title' => ''];
        foreach (explode('|', $s) as $part) {
            [$k, $v] = array_pad(explode(':', $part, 2), 2, '');
            if (isset($out[$k])) $out[$k] = rawurldecode($v);
        }
        return $out;
    }

    /** Link di YouTube o Vimeo → shortcode di PhalbPress (che chiede il consenso prima di caricarli). */
    public static function videoCode(string $url): string
    {
        $url = html_entity_decode(trim($url), ENT_QUOTES, 'UTF-8');
        if (preg_match('~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|v/)|youtu\.be/)([A-Za-z0-9_-]{6,20})~', $url, $m)) return '<p>[video:' . $m[1] . ']</p>';
        if (preg_match('~vimeo\.com/(?:video/|channels/[\w-]+/)?(\d{5,12})~', $url, $m)) return '<p>[vimeo:' . $m[1] . ']</p>';
        return '';
    }

    /** iframe di YouTube/Vimeo e link nudi su una riga → shortcode video; gli altri iframe si tolgono. */
    private static function embeds(string $html): string
    {
        $html = preg_replace_callback('~<iframe[^>]*\ssrc=["\']([^"\']+)["\'][^>]*>.*?</iframe>~is', fn($m) => "\n\n" . self::videoCode($m[1]) . "\n\n", $html);
        return preg_replace_callback('~^\s*(https?://\S+)\s*$~m', fn($m) => self::videoCode($m[1]) ?: $m[0], $html);
    }

    /** Come wpautop di WordPress, semplificato: righe vuote → paragrafi, a capo singolo → <br>. */
    private static function autop(string $html): string
    {
        // div, section e span sono solo impaginazione del vecchio tema
        $html = preg_replace('~</?(?:div|section|article|span|font|center)\b[^>]*>~i', "\n\n", $html);
        $block = 'p|h[1-6]|ul|ol|li|blockquote|figure|table|thead|tbody|tr|hr|pre';
        // a capo doppio prima di un blocco che si apre e dopo uno che si chiude: il blocco resta intero
        $html = preg_replace('~(<(?:' . $block . ')\b[^>]*>)~i', "\n\n$1", $html);
        $html = preg_replace('~(</(?:' . $block . ')>|<hr\s*/?>)~i', "$1\n\n", $html);
        $out = [];
        foreach (preg_split("~\n\s*\n~", $html) as $chunk) {
            $c = trim($chunk);
            if ($c === '') continue;
            if (preg_match('~^</?(?:' . $block . '|td|th|figcaption)[\s>/]~i', $c) || preg_match('~^\[(?:gallery|video|vimeo):~', $c)) { $out[] = $c; continue; }
            $out[] = '<p>' . preg_replace("~\s*\n\s*~", "<br>\n", $c) . '</p>';
        }
        return implode("\n", $out);
    }

    /** Foto e file del vecchio sito dentro il testo (src delle immagini, link a PDF…) → file nuovi. */
    private static function rewriteFiles(string $html, callable $media): string
    {
        $html = preg_replace_callback('~<img\b[^>]*>~i', function ($m) use ($media) {
            if (!preg_match('~\ssrc=["\']([^"\']+)["\']~i', $m[0], $s)) return '';
            if (!str_contains($s[1], '/wp-content/uploads/')) return $m[0];
            preg_match('~\salt=["\']([^"\']*)["\']~i', $m[0], $al);
            [$p, $alt] = $media($s[1], html_entity_decode($al[1] ?? '', ENT_QUOTES, 'UTF-8'));
            if ($p === '') return '';
            // la misura scelta nel vecchio sito (attributi o versione ridotta -300x200): la foto non diventa enorme
            preg_match('~\swidth=["\']?(\d+)~i', $m[0], $w); preg_match('~\sheight=["\']?(\d+)~i', $m[0], $h);
            if (!$w && preg_match('~-(\d+)x(\d+)\.[A-Za-z0-9]+(?:\?.*)?$~', $s[1], $wh)) { $w = [0, $wh[1]]; $h = [0, $wh[2]]; }
            $size = $w && $h ? ' width="' . (int)$w[1] . '" height="' . (int)$h[1] . '"' : '';
            return '<img src="' . e(PP::url($p)) . '" alt="' . e($alt) . '"' . $size . '>';
        }, $html);
        return preg_replace_callback('~(<a\b[^>]*\shref=)(["\'])([^"\']*/wp-content/uploads/[^"\']+)\2~i', function ($m) use ($media) {
            [$p] = $media($m[3], '');
            return $m[1] . '"' . e($p !== '' ? PP::url($p) : $m[3]) . '"';
        }, $html);
    }
}
