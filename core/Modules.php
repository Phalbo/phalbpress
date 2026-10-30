<?php
/**
 * Hook e moduli (i "plugin" di PhalbPress).
 *
 * Un modulo è una cartella con un file module.php che restituisce un array:
 *   return [
 *     'name'    => 'nome-breve',            // univoco
 *     'label'   => 'Nome leggibile',
 *     'version' => '1.0.0',
 *     'migrate' => function () { ... },     // facoltativo: crea/aggiorna tabelle (gira quando cambia 'version')
 *     'boot'    => function () { ... },     // registra hook, pagine di gestione, rotte
 *   ];
 *
 *   core/modules/<nome>/   moduli del motore: si aggiornano insieme al core
 *   site/modules/<nome>/   moduli solo di questo sito: il core non li tocca mai
 *   Le cartelle che iniziano con "_" sono disattivate.
 */
final class Hooks
{
    private static array $h = [];

    public static function on(string $event, callable $fn, int $priority = 10): void
    {
        self::$h[$event][$priority][] = $fn;
    }

    private static function listeners(string $event): array
    {
        if (empty(self::$h[$event])) return [];
        ksort(self::$h[$event]);
        return array_merge(...array_values(self::$h[$event]));
    }

    /** Esegue tutti gli ascoltatori. */
    public static function fire(string $event, ...$args): void
    {
        foreach (self::listeners($event) as $fn) $fn(...$args);
    }

    /** Passa un valore attraverso gli ascoltatori, che possono modificarlo. */
    public static function filter(string $event, $value, ...$args)
    {
        foreach (self::listeners($event) as $fn) $value = $fn($value, ...$args);
        return $value;
    }

    /** Il primo ascoltatore che restituisce qualcosa di diverso da null vince. */
    public static function first(string $event, ...$args)
    {
        foreach (self::listeners($event) as $fn) { $r = $fn(...$args); if ($r !== null) return $r; }
        return null;
    }

    /** Concatena l'HTML restituito dagli ascoltatori (per head, footer, ecc.). */
    public static function html(string $event, ...$args): string
    {
        $out = '';
        foreach (self::listeners($event) as $fn) $out .= (string)$fn(...$args);
        return $out;
    }
}

final class Modules
{
    public static array $list = [];

    public static function boot(): void
    {
        foreach ([PP_ROOT . '/core/modules', PP_SITE . '/modules'] as $base) {
            if (!is_dir($base)) continue;
            foreach (glob($base . '/*/module.php') as $file) {
                $dir = dirname($file);
                if (basename($dir)[0] === '_') continue;
                $m = require $file;
                if (!is_array($m) || empty($m['name'])) continue;
                $m['dir'] = $dir;
                $m['scope'] = str_starts_with($dir, PP_SITE) ? 'site' : 'core';
                self::$list[$m['name']] = $m;
            }
        }
        foreach (self::$list as $m) if (isset($m['boot'])) ($m['boot'])();
    }

    /** Applica le migrazioni dei moduli la cui versione è cambiata. */
    public static function migrate(): array
    {
        $done = [];
        $known = Settings::get('module_versions', []);
        if (($known['core'] ?? '') !== PP::VERSION) { DB::migrate(); $known['core'] = PP::VERSION; $done[] = 'core ' . PP::VERSION; }
        foreach (self::$list as $name => $m) {
            if (($known[$name] ?? '') === ($m['version'] ?? '')) continue;
            if (isset($m['migrate'])) ($m['migrate'])();
            $known[$name] = $m['version'] ?? '';
            $done[] = $name . ' ' . ($m['version'] ?? '');
        }
        if ($done) Settings::set('module_versions', $known);
        return $done;
    }

    public static function has(string $name): bool { return isset(self::$list[$name]); }
}
