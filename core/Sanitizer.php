<?php
/**
 * Pulizia dell'HTML che arriva dall'editor: solo tag e attributi ammessi, niente script né eventi.
 */
final class Sanitizer
{
    private const BLOCK = [
        'p' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'ul' => [], 'ol' => [], 'li' => [],
        'blockquote' => [], 'figure' => [], 'figcaption' => [], 'br' => [], 'hr' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'mark' => [], 'sub' => [], 'sup' => [],
        'a' => ['href', 'title', 'target', 'rel'], 'img' => ['src', 'alt', 'width', 'height'],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => ['scope'], 'td' => [],
    ];
    private const INLINE = [
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'mark' => [], 'br' => [],
        'a' => ['href', 'title', 'target', 'rel'],
    ];

    public static function html(?string $html): string { return self::clean((string)$html, self::BLOCK); }
    public static function inline(?string $html): string { return self::clean((string)$html, self::INLINE); }

    private static function clean(string $html, array $allowed): string
    {
        $html = trim($html);
        if ($html === '') return '';
        if (!class_exists('DOMDocument')) return htmlspecialchars(strip_tags($html), ENT_QUOTES, 'UTF-8');
        $doc = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="pp-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $root = $doc->getElementById('pp-root');
        if (!$root) return '';
        self::walk($root, $allowed);
        $out = '';
        foreach ($root->childNodes as $c) $out .= $doc->saveHTML($c);
        return trim($out);
    }

    private static function walk(DOMNode $node, array $allowed): void
    {
        $children = [];
        foreach ($node->childNodes as $c) $children[] = $c;
        foreach ($children as $c) {
            if ($c instanceof DOMElement) {
                $tag = strtolower($c->tagName);
                if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math', 'link', 'meta'], true)) {
                    $node->removeChild($c);
                    continue;
                }
                if (!isset($allowed[$tag])) {
                    // tieni il contenuto, togli il tag (es. div, span)
                    self::walk($c, $allowed);
                    $frag = $c->ownerDocument->createDocumentFragment();
                    while ($c->firstChild) $frag->appendChild($c->firstChild);
                    if (in_array($tag, ['div', 'section', 'article'], true) && isset($allowed['p'])) {
                        $p = $c->ownerDocument->createElement('p');
                        $p->appendChild($frag);
                        $node->replaceChild($p, $c);
                    } else {
                        $node->replaceChild($frag, $c);
                    }
                    continue;
                }
                $keep = $allowed[$tag];
                $attrs = [];
                foreach ($c->attributes as $a) $attrs[] = $a->name;
                foreach ($attrs as $name) {
                    if (!in_array(strtolower($name), $keep, true)) $c->removeAttribute($name);
                }
                foreach (['href', 'src'] as $u) {
                    if ($c->hasAttribute($u) && !self::safeUrl($c->getAttribute($u))) $c->removeAttribute($u);
                }
                if ($tag === 'a' && $c->getAttribute('target') === '_blank') $c->setAttribute('rel', 'noopener');
                self::walk($c, $allowed);
            } elseif ($c instanceof DOMComment) {
                $node->removeChild($c);
            }
        }
    }

    private static function safeUrl(string $u): bool
    {
        // i browser ignorano tabulazioni e a capo dentro lo schema ("java\tscript:"): si tolgono prima di giudicare
        $u = preg_replace('~[\x00-\x20\x7f]+~', '', html_entity_decode($u, ENT_QUOTES, 'UTF-8'));
        if ($u === '' || $u[0] === '/' || $u[0] === '#' || $u[0] === '?') return true;
        return (bool)preg_match('~^(https?:|mailto:|tel:)~i', $u) || !preg_match('~^[a-z][a-z0-9+.-]*:~i', $u);
    }
}
