<?php
/**
 * Lingue, testi di interfaccia e stato delle traduzioni.
 */
final class I18n
{
    public static array $langs = [];
    public static string $master = 'en';
    public static string $current = 'en';
    private static array $cache = [];
    private static array $defaults = [];
    private static bool $merged = false;

    public static function init(array $config): void
    {
        self::$langs = $config['languages'];
        self::$master = $config['master_language'];
        self::$current = self::$master;
        $f = PP_SITE . '/strings.php';
        self::$defaults = is_file($f) ? require $f : [];
    }

    public static function codes(): array { return array_keys(self::$langs); }
    public static function others(): array { return array_values(array_diff(self::codes(), [self::$master])); }
    public static function valid(string $l): bool { return isset(self::$langs[$l]); }
    public static function name(string $l): string { return self::$langs[$l]['native'] ?? $l; }
    public static function label(string $l): string { return self::$langs[$l]['label'] ?? strtoupper($l); }
    public static function hreflang(string $l): string { return self::$langs[$l]['hreflang'] ?? $l; }
    /** Testi di default: site/strings.php ha la precedenza su quelli dei moduli (filtro 'strings'). */
    public static function defaults(): array
    {
        if (!self::$merged && class_exists('Hooks')) {
            self::$merged = true;
            self::$defaults = Hooks::filter('strings', self::$defaults);
        }
        return self::$defaults;
    }

    /** Lingua preferita dal browser tra quelle disponibili. */
    public static function detect(): string
    {
        $h = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        foreach (explode(',', $h) as $part) {
            $code = strtolower(substr(trim($part), 0, 2));
            if (self::valid($code)) return $code;
        }
        return self::$master;
    }

    /** Testo di interfaccia: DB lingua corrente → DB lingua master → default in site/strings.php → chiave. */
    public static function t(string $key, ?string $lang = null): string
    {
        $lang = $lang ?: self::$current;
        foreach ([$lang, self::$master] as $l) {
            if (!isset(self::$cache[$l])) self::load($l);
            if (isset(self::$cache[$l][$key]) && self::$cache[$l][$key] !== '') return self::$cache[$l][$key];
        }
        $d = self::defaults()[$key] ?? null;
        if (is_array($d)) return $d['text'][$lang] ?? $d['text'][self::$master] ?? $d['text']['en'] ?? '';   // i testi dei moduli hanno sempre l'inglese
        return $key;
    }

    /** true se il testo esiste davvero nella lingua (senza ripiego). */
    public static function has(string $key, string $lang): bool
    {
        if (!isset(self::$cache[$lang])) self::load($lang);
        return isset(self::$cache[$lang][$key]) && self::$cache[$lang][$key] !== '';
    }

    private static function load(string $l): void
    {
        self::$cache[$l] = [];
        try {
            foreach (DB::all('SELECT skey, value FROM pp_strings WHERE lang = ?', [$l]) as $r) {
                self::$cache[$l][$r['skey']] = (string)$r['value'];
            }
        } catch (Throwable $e) { /* prima dell'installazione */ }
    }

    public static function flush(): void { self::$cache = []; }

    public static function hash(array $fields): string
    {
        return sha1(implode("\x1f", array_map(fn($v) => trim((string)$v), $fields)));
    }

    /**
     * Stato di una traduzione rispetto al testo master.
     * master | missing | outdated | auto | ok
     */
    public static function status(string $lang, ?array $tr, string $masterHash): string
    {
        if ($lang === self::$master) return 'master';
        if (!$tr || trim((string)($tr['title'] ?? $tr['value'] ?? '')) === '') return 'missing';
        if (($tr['src_hash'] ?? '') !== $masterHash) return 'outdated';
        if (!empty($tr['auto'])) return 'auto';
        return 'ok';
    }

    public static function statusLabel(string $s): string
    {
        return [
            'master' => 'Originale', 'missing' => 'Mancante', 'outdated' => 'Da aggiornare',
            'auto' => 'Automatica, da rivedere', 'ok' => 'Rivista',
        ][$s] ?? $s;
    }
}
