<?php
/**
 * Colori "Personale": da un colore principale (e, se si vuole, un secondo e un terzo) una palette completa per il tema attivo.
 * Si lavora in OKLCH (luminosità percepita, intensità, tinta): ogni ruolo del tema (sfondo, testo, campi, campo scuro,
 * frase grande) tiene la luminosità e l'intensità che ha nel tema, e prende la tinta dei colori scelti. Così un tema chiaro
 * resta chiaro e uno scuro resta scuro; poi i colori che non arrivano ai contrasti WCAG AA si correggono da soli.
 *
 * I caratteri decidono le tinte che mancano: sobrio (una tinta sola), armonico (tinte vicine), vivace (complementari),
 * triade (tre tinte a 120°), istituzionale (tinta principale profonda e neutri).
 */
final class Palette
{
    public const MOODS = [
        'sobrio' => ['Sobrio', 'una tinta sola, in più toni'],
        'armonico' => ['Armonico', 'tinte vicine sul cerchio dei colori'],
        'vivace' => ['Vivace', 'colori opposti, più accesi'],
        'triade' => ['Triade', 'tre tinte lontane in equilibrio'],
        'istituzionale' => ['Istituzionale', 'il principale, profondo, con neutri sobri'],
    ];

    /* ---------- conversioni ---------- */

    /** #rrggbb → [L 0-1, C, h gradi] */
    public static function oklch(string $hex): array
    {
        $hex = ltrim($hex, '#');
        $rgb = array_map(fn($x) => hexdec($x) / 255, str_split(substr($hex . '000000', 0, 6), 2));
        $lin = array_map(fn($v) => $v <= 0.04045 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4, $rgb);
        [$r, $g, $b] = $lin;
        $l = 0.4122214708 * $r + 0.5363325363 * $g + 0.0514459929 * $b;
        $m = 0.2119034982 * $r + 0.6806995451 * $g + 0.1073969566 * $b;
        $s = 0.0883024619 * $r + 0.2817188376 * $g + 0.6299787005 * $b;
        [$l, $m, $s] = [self::cbrt($l), self::cbrt($m), self::cbrt($s)];
        $L = 0.2104542553 * $l + 0.7936177850 * $m - 0.0040720468 * $s;
        $A = 1.9779984951 * $l - 2.4285922050 * $m + 0.4505937099 * $s;
        $B = 0.0259040371 * $l + 0.7827717662 * $m - 0.8086757660 * $s;
        $h = rad2deg(atan2($B, $A));
        return [$L, sqrt($A * $A + $B * $B), $h < 0 ? $h + 360 : $h];
    }

    /** [L, C, h] → #rrggbb, riducendo l'intensità finché il colore esiste sullo schermo. */
    public static function hex(float $L, float $C, float $h): string
    {
        $L = max(0.0, min(1.0, $L));
        $lo = 0.0; $hi = max(0.0, $C);
        if (self::rgb($L, $hi, $h) !== null) return self::toHex(self::rgb($L, $hi, $h));
        for ($i = 0; $i < 22; $i++) { $mid = ($lo + $hi) / 2; if (self::rgb($L, $mid, $h) !== null) $lo = $mid; else $hi = $mid; }
        return self::toHex(self::rgb($L, $lo, $h) ?? [$L, $L, $L]);
    }

    private static function rgb(float $L, float $C, float $h): ?array
    {
        $A = $C * cos(deg2rad($h)); $B = $C * sin(deg2rad($h));
        $l = ($L + 0.3963377774 * $A + 0.2158037573 * $B) ** 3;
        $m = ($L - 0.1055613458 * $A - 0.0638541728 * $B) ** 3;
        $s = ($L - 0.0894841775 * $A - 1.2914855480 * $B) ** 3;
        $rgb = [4.0767416621 * $l - 3.3077115913 * $m + 0.2309699292 * $s, -1.2684380046 * $l + 2.6097574011 * $m - 0.3413193965 * $s, -0.0041960863 * $l - 0.7034186147 * $m + 1.7076147010 * $s];
        foreach ($rgb as $v) if ($v < -0.0005 || $v > 1.0005) return null;
        return array_map(fn($v) => max(0.0, min(1.0, $v)), $rgb);
    }

    private static function toHex(array $lin): string
    {
        $srgb = array_map(fn($v) => $v <= 0.0031308 ? 12.92 * $v : 1.055 * $v ** (1 / 2.4) - 0.055, $lin);
        return '#' . implode('', array_map(fn($v) => str_pad(dechex((int)round(max(0, min(1, $v)) * 255)), 2, '0', STR_PAD_LEFT), $srgb));
    }

    private static function cbrt(float $x): float { return $x < 0 ? -((-$x) ** (1 / 3)) : $x ** (1 / 3); }

    private static function hueDist(float $a, float $b): float { $d = fmod(abs($a - $b), 360); return $d > 180 ? 360 - $d : $d; }

    /* ---------- palette ---------- */

    /**
     * Palette per un tema da colori scelti. $base = palette del tema (theme.json "palette"), $heroOn come nel tema.
     * $secondary e $tertiary vuoti = li sceglie il carattere.
     */
    public static function derive(array $base, string $primary, string $secondary = '', string $tertiary = '', string $mood = 'armonico', string $heroOn = 'ground', bool $accentText = true): array
    {
        $ok = fn($v) => is_string($v) && preg_match('~^#[0-9a-f]{6}$~i', $v);
        if (!$ok($primary)) return $base;
        $mood = isset(self::MOODS[$mood]) ? $mood : 'armonico';
        $roles = array_keys(Identity::COLORS);
        foreach ($roles as $r) if (!$ok($base[$r] ?? null)) $base[$r] = Identity::COLORS[$r][1];
        [$pL, $pC, $pH] = self::oklch($primary);
        // tinte del secondo e del terzo colore secondo il carattere (se non scelti)
        $off = ['sobrio' => [0, 0], 'armonico' => [38, -38], 'vivace' => [180, 150], 'triade' => [120, 240], 'istituzionale' => [0, 205]][$mood];
        $scale = ['sobrio' => 0.55, 'armonico' => 0.9, 'vivace' => 1.15, 'triade' => 1.0, 'istituzionale' => 0.6][$mood];
        $hues = [$pH, fmod($pH + $off[0] + 360, 360), fmod($pH + $off[1] + 360, 360)];
        $given = [1 => $ok($secondary) ? strtolower($secondary) : '', 2 => $ok($tertiary) ? strtolower($tertiary) : ''];

        // ruoli nel tema: campo 1 e campo 2 prendono il secondo e il terzo colore; sfondo, testo, campo scuro e frase grande
        // restano neutri se lo sono, prendono la tinta principale se sono fondi molto scuri o chiari, altrimenti seguono il campo
        // con la tinta più vicina. Un ruolo che nel tema ha lo stesso colore di un altro resta uguale a quello.
        $lch = array_combine($roles, array_map(fn($r) => self::oklch($base[$r]), $roles));
        $same = fn($a, $b) => strtolower($base[$a]) === strtolower($base[$b]);
        $slot = [];
        foreach (['field1' => 1, 'field2' => 2] as $r => $n) {
            [$L, $C] = $lch[$r];
            $slot[$r] = $same($r, 'accent') ? 'copy:accent' : ($C < 0.035 ? 'neutral' : $n);
        }
        if ($slot['field2'] === 2 && $same('field2', 'field1')) $slot['field2'] = 'copy:field1';
        foreach (['ink', 'ground', 'dark', 'hero1', 'hero2'] as $r) {
            [$L, $C, $H] = $lch[$r];
            foreach (['accent', 'field1', 'field2'] as $o) if ($same($r, $o)) { $slot[$r] = 'copy:' . $o; continue 2; }
            if ($C < 0.035) { $slot[$r] = 'neutral'; continue; }
            if ($L < 0.32 || $L > 0.9 || self::hueDist($H, $lch['accent'][2]) < 28) { $slot[$r] = 0; continue; }
            $d1 = self::hueDist($H, $lch['field1'][2]); $d2 = self::hueDist($H, $lch['field2'][2]);
            $slot[$r] = is_int($slot['field2']) && $d2 < $d1 ? 2 : 1;
        }

        $out = ['accent' => strtolower($primary)];
        foreach (['field1', 'field2', 'ink', 'ground', 'dark', 'hero1', 'hero2'] as $r) {
            [$L, $C, $H] = $lch[$r];
            $s = $slot[$r];
            if (is_string($s) && str_starts_with($s, 'copy:')) { $out[$r] = $out[substr($s, 5)]; continue; }
            if ($s === 'neutral') {
                // neutri: stessa luminosità, appena un velo della tinta principale (se nel tema ce n'era uno)
                $out[$r] = $C < 0.006 ? $base[$r] : self::hex($L, min($C, 0.03), $pH);
            } elseif ($s === 0) {
                $out[$r] = self::hex($L, $C * ($mood === 'sobrio' ? 0.8 : 1), $pH);
            } elseif ($given[$s] !== '') {
                // il colore scelto, alla luminosità del ruolo se il ruolo è un fondo chiaro o scuro molto diverso
                [$gL, $gC, $gH] = self::oklch($given[$s]);
                $out[$r] = abs($gL - $L) > 0.25 ? self::hex($L, min($gC, max($C, 0.04)), $gH) : $given[$s];
            } else {
                // i gialli scuri diventano oliva spento: meglio un ocra
                $h = $hues[$s];
                if ($L < 0.7 && $h > 80 && $h < 118) $h = 68;
                $out[$r] = self::hex($L, min(0.3, $C * $scale), $h);
            }
        }
        return self::fix($out, $heroOn, $accentText);
    }

    /** Palette personale per un tema di PP::themes(). */
    public static function forTheme(string $slug, string $primary, string $secondary = '', string $tertiary = '', string $mood = 'armonico'): array
    {
        $t = PP::themes()[$slug] ?? [];
        return self::derive($t['palette'] ?? [], $primary, $secondary, $tertiary, $mood, (string)($t['hero_on'] ?? 'ground'), (bool)($t['accent_text'] ?? true));
    }

    /** Porta ai contrasti WCAG AA, muovendo solo la luminosità del colore che conviene (la tinta resta). */
    public static function fix(array $c, string $heroOn = 'ground', bool $accentText = true): array
    {
        $move = function (string $role, string $against, float $min, bool $textOnIt = false) use (&$c) {
            for ($n = 0; $n < 50; $n++) {
                $fg = $textOnIt ? Identity::inkOn($c[$role]) : $c[$role];
                $bg = $textOnIt ? $c[$role] : $c[$against];
                if (Identity::contrast($fg, $bg) >= $min + 0.05) return;
                [$L, $C, $H] = self::oklch($c[$role]);
                // testo su un fondo: il fondo si allontana dal grigio medio; un colore su un altro: si allontana da quello
                $up = $textOnIt ? $L >= 0.62 : $L >= self::oklch($c[$against])[0];
                $L += $up ? 0.02 : -0.02;
                if ($L <= 0 || $L >= 1) return;
                $c[$role] = self::hex($L, $C, $H);
            }
        };
        $move('ink', 'ground', 4.5);
        foreach (['accent', 'field1', 'field2', 'dark'] as $r) $move($r, '', 4.5, true);
        if ($accentText) { $move('accent', 'ground', 3); $move('accent', '', 4.5, true); }
        if ($heroOn !== 'photo') foreach (['hero1', 'hero2'] as $r) {
            $move($r, $heroOn === 'ground' ? 'ground' : 'accent', 3);
            // la frase è già bianca o nera: si muove il fondo
            if ($heroOn === 'accent') $move('accent', $r, 3);
        }
        return array_map('strtolower', $c);
    }

    /* ---------- "Personale": i colori scelti restano nel sito e valgono per ogni tema ---------- */

    /** Scelta salvata: ['primary', 'secondary', 'tertiary', 'mood', 'colors' => palette applicata] o []. */
    public static function personal(): array
    {
        $p = Settings::get('identity_personal', []);
        return is_array($p) && preg_match('~^#[0-9a-f]{6}$~i', (string)($p['primary'] ?? '')) ? $p : [];
    }

    /** Dai campi del modulo (p1, p2 con use2, p3 con use3, mood). */
    public static function fromInput(array $in): array
    {
        $hex = fn($k) => preg_match('~^#[0-9a-f]{6}$~i', (string)($in[$k] ?? '')) ? strtolower((string)$in[$k]) : '';
        return ['primary' => $hex('p1'), 'secondary' => !empty($in['use2']) ? $hex('p2') : '', 'tertiary' => !empty($in['use3']) ? $hex('p3') : '',
            'mood' => isset(self::MOODS[(string)($in['mood'] ?? '')]) ? (string)$in['mood'] : 'armonico'];
    }

    public static function colorsFor(array $p, ?string $theme = null): array
    {
        return self::forTheme($theme ?? PP::theme(), $p['primary'], $p['secondary'] ?? '', $p['tertiary'] ?? '', $p['mood'] ?? 'armonico');
    }

    /** Applica e ricorda la scelta. */
    public static function applyPersonal(array $p): void
    {
        $c = self::colorsFor($p);
        Identity::apply($c);
        Settings::set('identity_personal', $p + ['colors' => $c]);
    }

    /**
     * Cambio di tema: se i colori del sito sono ancora quelli calcolati dalla scelta personale (nessuno li ha ritoccati),
     * si ricalcolano per il tema nuovo. Torna true se l'ha fatto.
     */
    public static function onThemeChange(string $theme): bool
    {
        $p = self::personal();
        if (!$p) return false;
        $now = Identity::get()['colors'];
        foreach ($p['colors'] ?? [] as $k => $v) if (strtolower((string)($now[$k] ?? '')) !== strtolower((string)$v)) { Settings::set('identity_personal', null); return false; }
        $c = self::colorsFor($p, $theme);
        Identity::apply($c);
        Settings::set('identity_personal', $p + ['colors' => $c]);
        return true;
    }
}
