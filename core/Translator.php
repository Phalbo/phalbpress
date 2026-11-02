<?php
/**
 * Traduzione automatica. Provider: "anthropic" (Claude, copre tutte le lingue del progetto) o "deepl".
 * Le chiavi si inseriscono in Sistema → Impostazioni del sito (o in site/config.php). Senza chiave, il pulsante "Traduci" avvisa e non fa nulla.
 */
final class Translator
{
    public static function enabled(): bool
    {
        $c = PP::$config['translation'] ?? [];
        return !empty($c['provider']) && !empty($c['api_key']);
    }

    /** @param array<string,string> $fields  es. ['title'=>..., 'excerpt'=>..., 'body'=>...] */
    public static function translate(array $fields, string $from, string $to): array
    {
        $fields = array_filter($fields, fn($v) => trim((string)$v) !== '');
        if (!$fields) return [];
        $c = PP::$config['translation'];
        return $c['provider'] === 'deepl' ? self::deepl($fields, $from, $to, $c) : self::anthropic($fields, $from, $to, $c);
    }

    private static function langName(string $code): string
    {
        $l = I18n::$langs[$code] ?? [];
        return ($l['english'] ?? $code) . (!empty($l['variant']) ? ' (' . $l['variant'] . ')' : '');
    }

    private static function anthropic(array $fields, string $from, string $to, array $c): array
    {
        $glossary = $c['glossary'] ?? [];
        $sys = "You are a professional translator for an EU-funded project website. "
            . "Translate from " . self::langName($from) . " into " . self::langName($to) . ". "
            . ($c['style'] ?? 'Clear, respectful, plain institutional language.') . " "
            . "Keep every HTML tag, attribute, shortcode in square brackets (like [doc:12] or [video:abc]) and placeholder in curly braces (like {controller}) exactly as they are; translate only human-readable text. "
            . ($glossary ? 'Never translate these terms: ' . implode(', ', $glossary) . '. ' : '')
            . "Reply with ONLY a JSON object with the same keys as the input and the translated strings as values. No comments, no code fences.";
        $payload = [
            'model' => $c['model'] ?? 'claude-sonnet-5-5',
            'max_tokens' => 8000,
            'system' => $sys,
            'messages' => [['role' => 'user', 'content' => json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]],
        ];
        $res = self::post('https://api.anthropic.com/v1/messages', $payload, [
            'x-api-key: ' . $c['api_key'],
            'anthropic-version: 2023-06-01',
        ]);
        $text = '';
        foreach ($res['content'] ?? [] as $block) if (($block['type'] ?? '') === 'text') $text .= $block['text'];
        $text = trim(preg_replace('~^```(?:json)?|```$~m', '', trim($text)));
        $out = json_decode($text, true);
        if (!is_array($out)) {
            if (preg_match('~\{.*\}~s', $text, $m)) $out = json_decode($m[0], true);
        }
        if (!is_array($out)) throw new RuntimeException('Risposta di traduzione non leggibile');
        return array_intersect_key($out, $fields);
    }

    private static function deepl(array $fields, string $from, string $to, array $c): array
    {
        $map = $c['deepl_codes'] ?? [];
        $host = str_ends_with($c['api_key'], ':fx') ? 'https://api-free.deepl.com' : 'https://api.deepl.com';
        $keys = array_keys($fields);
        $res = self::post($host . '/v2/translate', [
            'text' => array_values($fields),
            'source_lang' => strtoupper($map[$from] ?? $from),
            'target_lang' => strtoupper($map[$to] ?? $to),
            'tag_handling' => 'html',
        ], ['Authorization: DeepL-Auth-Key ' . $c['api_key']]);
        $out = [];
        foreach ($res['translations'] ?? [] as $i => $t) $out[$keys[$i]] = $t['text'];
        if (!$out) throw new RuntimeException('DeepL non ha restituito traduzioni');
        return $out;
    }

    private static function post(string $url, array $payload, array $headers): array
    {
        if (!function_exists('curl_init')) throw new RuntimeException('cURL non disponibile sul server');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_HTTPHEADER => array_merge(['content-type: application/json'], $headers),
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);
        $raw = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) throw new RuntimeException('Errore di rete: ' . $err);
        $data = json_decode($raw, true);
        if ($code >= 400) {
            $msg = $data['error']['message'] ?? $data['message'] ?? ('HTTP ' . $code);
            throw new RuntimeException('Servizio di traduzione: ' . $msg);
        }
        return is_array($data) ? $data : [];
    }

    /** Traduce e salva un elemento della coda. Ritorna lo stato finale. */
    public static function job(string $kind, string $id, string $lang): string
    {
        $from = I18n::$master;
        if ($lang === $from || !I18n::valid($lang)) throw new RuntimeException('Lingua non valida');
        if ($kind === 'string') {
            $src = (string)DB::val('SELECT value FROM pp_strings WHERE skey = ? AND lang = ?', [$id, $from]);
            if ($src === '') {
                $def = I18n::defaults()[$id]['text'][$from] ?? I18n::defaults()[$id]['text']['en'] ?? '';
                if ($def === '') throw new RuntimeException('Testo originale vuoto');
                Content::saveString($id, $from, $def);
                $src = $def;
            }
            $out = self::translate(['value' => $src], $from, $lang);
            Content::saveString($id, $lang, $out['value'] ?? '', true);
            return Content::stringStatus($id, $lang);
        }
        $m = Content::tr($kind, (int)$id, $from);
        if (!$m) throw new RuntimeException('Manca il testo originale');
        $src = ['title' => $m['title'], 'excerpt' => $m['excerpt'], 'body' => $m['body']];
        if (!empty($m['image_alt'])) $src['image_alt'] = $m['image_alt'];
        // campi personalizzati traducibili (solo testo: numeri, date e link restano come sono)
        $keep = [];
        foreach (json_decode((string)($m['extra'] ?? ''), true) ?: [] as $k => $v) {
            if (is_string($v) && preg_match('~\pL{2}~u', $v) && !preg_match('~^(https?://|mailto:|/)~', $v)) $src['x_' . $k] = $v; else $keep[$k] = $v;
        }
        $out = self::translate($src, $from, $lang);
        $extra = $keep;
        foreach ($out as $k => $v) if (str_starts_with($k, 'x_')) { $extra[substr($k, 2)] = $v; unset($out[$k]); }
        $old = Content::tr($kind, (int)$id, $lang);
        // lo slug resta quello già in uso (link stabili); se è nuovo nasce dal titolo tradotto
        Content::saveText($kind, (int)$id, $lang, $out + ['title' => '', 'excerpt' => '', 'body' => '', 'image_alt' => '', 'extra' => $extra, 'slug' => $old['slug'] ?? ''], true);
        return Content::statuses($kind, (int)$id)[$lang];
    }
}
