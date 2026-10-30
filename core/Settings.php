<?php
/**
 * Impostazioni salvate nel database (identità visiva, SEO, AI, legale…). Valori JSON.
 */
final class Settings
{
    private static ?array $cache = null;

    private static function load(): void
    {
        self::$cache = [];
        try {
            foreach (DB::all('SELECT k, v FROM pp_settings') as $r) self::$cache[$r['k']] = json_decode((string)$r['v'], true);
        } catch (Throwable $e) {}
    }

    public static function get(string $k, $default = null)
    {
        if (self::$cache === null) self::load();
        return array_key_exists($k, self::$cache) && self::$cache[$k] !== null ? self::$cache[$k] : $default;
    }

    /** Legge un gruppo di impostazioni con i valori di default della configurazione. */
    public static function group(string $k, array $defaults = []): array
    {
        $v = self::get($k, []);
        return array_replace_recursive($defaults, is_array($v) ? $v : []);
    }

    public static function reset(): void { self::$cache = null; }

    public static function set(string $k, $v): void
    {
        DB::upsert('pp_settings', ['k' => $k, 'v' => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)], ['k']);
        if (self::$cache !== null) self::$cache[$k] = $v;
    }
}
