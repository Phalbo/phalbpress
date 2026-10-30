<?php
final class Identity
{
    public const COLORS = [
        'ground' => ['Sfondo', '#ffffff'],
        'ink'    => ['Testo', '#000000'],
        'accent' => ['Colore principale (accento)', '#fe5a36'],
        'field1' => ['Colore campo 1', '#9fd39a'],
        'field2' => ['Colore campo 2', '#9ccaf4'],
        'dark'   => ['Campo scuro (footer, menu)', '#000000'],
        'hero1'  => ['Frase grande, prima riga', '#ffffff'],
        'hero2'  => ['Frase grande, seconda riga', '#000000'],
    ];

    public static function get(): array
    {
        $d = PP::$config['identity'] ?? [];
        $def = [
            'site_name' => PP::$config['site_name'] ?? '',
            'logo' => $d['logo'] ?? '', 'logo_dark' => $d['logo_dark'] ?? '', 'logo_svg' => '', 'logo_dark_svg' => '',
            'favicon' => $d['favicon'] ?? '', 'share_image' => $d['share_image'] ?? '',
            'colors' => array_merge(array_map(fn($c) => $c[1], self::COLORS), $d['colors'] ?? []),
            'font_display' => $d['font_display'] ?? 'Petrona', 'font_text' => $d['font_text'] ?? 'Petrona',
            'buttons' => $d['buttons'] ?? 'pill',
            'fonts_custom' => [],
        ];
        $s = Settings::group('identity', $def);
        // logo SVG di default del tema (se non ne è stato caricato uno)
        foreach (['logo', 'logo_dark'] as $k) {
            if (!$s[$k . '_svg'] && $s[$k] && str_ends_with(strtolower($s[$k]), '.svg') && is_file(PP_ROOT . '/' . $s[$k])) {
                $s[$k . '_svg'] = (string)file_get_contents(PP_ROOT . '/' . $s[$k]);
            }
        }
        return $s;
    }

    public static function name(): string { return self::get()['site_name'] ?: (PP::$config['site_name'] ?? ''); }

    /* ---------- colori ---------- */

    public static function lum(string $hex): float
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        $c = array_map(fn($x) => hexdec($x) / 255, str_split(substr($hex . '000000', 0, 6), 2));
        $c = array_map(fn($v) => $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4, $c);
        return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
    }

    public static function contrast(string $a, string $b): float
    {
        $l1 = self::lum($a); $l2 = self::lum($b);
        return round((max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05), 2);
    }

    /** Testo leggibile su un colore: nero o bianco, quello con più contrasto. */
    public static function inkOn(string $bg): string
    {
        return self::contrast($bg, '#000000') >= self::contrast($bg, '#ffffff') ? '#000000' : '#ffffff';
    }

    /** Coppie da verificare (WCAG 2.1: 4,5 testo normale, 3 testo grande). */
    public static function checks(array $c): array
    {
        $rows = [
            ['Testo sullo sfondo', $c['ink'], $c['ground'], 4.5],
            ['Testo sul colore principale', self::inkOn($c['accent']), $c['accent'], 4.5],
            ['Testo sul campo 1', self::inkOn($c['field1']), $c['field1'], 4.5],
            ['Testo sul campo 2', self::inkOn($c['field2']), $c['field2'], 4.5],
            ['Testo sul campo scuro', self::inkOn($c['dark']), $c['dark'], 4.5],
            ['Frase grande, prima riga', $c['hero1'], $c['accent'], 3],
            ['Frase grande, seconda riga', $c['hero2'], $c['accent'], 3],
            ['Colore principale su sfondo (titoli, link evidenziati)', $c['accent'], $c['ground'], 3],
        ];
        return array_map(function ($r) {
            $ratio = self::contrast($r[1], $r[2]);
            $sug = '';
            if ($ratio < $r[3]) {
                // la tonalità più vicina che passa: scurisce (o schiarisce) lo sfondo a piccoli passi
                $toward = self::lum($r[1]) > 0.5 ? '#000000' : '#ffffff';
                for ($t = 0.02; $t <= 0.6; $t += 0.02) {
                    $c = self::mix($r[2], $toward, $t);
                    if (self::contrast($r[1], $c) >= $r[3]) { $sug = $c; break; }
                }
            }
            return ['label' => $r[0], 'fg' => $r[1], 'bg' => $r[2], 'min' => $r[3], 'ratio' => $ratio, 'suggest' => $sug];
        }, $rows);
    }

    /* ---------- font ---------- */

    public static function fonts(): array
    {
        $f = PP_ROOT . '/pp-assets/fonts/fonts.json';
        $list = is_file($f) ? json_decode((string)file_get_contents($f), true) : [];
        foreach (self::get()['fonts_custom'] as $name => $def) $list[$name] = $def + ['custom' => true];
        return $list;
    }

    private static function fontFace(string $family, array $def): string
    {
        $css = '';
        foreach ($def['files'] as $file) {
            $url = !empty($def['custom']) ? PP::url($file['file']) : PP::url('pp-assets/fonts/' . $file['file']);
            $css .= '@font-face{font-family:"' . addslashes($family) . '";font-style:' . $file['style'] . ';font-weight:' . ($def['weight'] ?? '400')
                . ';font-display:swap;src:url("' . $url . '") format("woff2");' . (!empty($file['range']) ? 'unicode-range:' . $file['range'] . ';' : '') . "}\n";
        }
        return $css;
    }

    /* ---------- CSS generato ---------- */

    /** CSS dell'identità; con $preset (palette, fonts, buttons di un tema) per l'anteprima "con i colori del tema". */
    public static function css(?array $preset = null): string
    {
        $s = self::get();
        if ($preset) {
            foreach ($preset['palette'] ?? [] as $k => $v) if (isset(self::COLORS[$k]) && preg_match('~^#[0-9a-f]{6}$~i', (string)$v)) $s['colors'][$k] = $v;
            if (!empty($preset['fonts']['display'])) $s['font_display'] = $preset['fonts']['display'];
            if (!empty($preset['fonts']['text'])) $s['font_text'] = $preset['fonts']['text'];
            if (!empty($preset['buttons'])) $s['buttons'] = $preset['buttons'];
        }
        $c = $s['colors'];
        $fonts = self::fonts();
        $css = "/* Generato da PhalbPress · Identità visiva. Non modificare a mano. */\n";
        foreach (array_unique([$s['font_display'], $s['font_text']]) as $fam) if (isset($fonts[$fam])) $css .= self::fontFace($fam, $fonts[$fam]);
        $fb = fn($fam) => '"' . $fam . '", ' . ($fonts[$fam]['fallback'] ?? 'system-ui, sans-serif');
        $css .= ":root{\n"
            . "--bg:{$c['ground']};--ink:{$c['ink']};--surface:" . self::mix($c['ground'], $c['ink'], 0.05) . ";--muted:" . self::mix($c['ink'], $c['ground'], 0.3) . ";\n"
            . "--accent:{$c['accent']};--accent-ink:" . self::inkOn($c['accent']) . ";\n"
            . "--field-1:{$c['field1']};--field-1-ink:" . self::inkOn($c['field1']) . ";\n"
            . "--field-2:{$c['field2']};--field-2-ink:" . self::inkOn($c['field2']) . ";\n"
            . "--black:{$c['dark']};--black-ink:" . self::inkOn($c['dark']) . ";\n"
            . "--hero-1:{$c['hero1']};--hero-2:{$c['hero2']};\n"
            . "--font:" . $fb($s['font_text']) . ";--font-display:" . $fb($s['font_display']) . ";\n"
            . "--radius:" . ($s['buttons'] === 'square' ? '0px' : ($s['buttons'] === 'soft' ? '10px' : '999px')) . ";\n}\n";
        return $css;
    }

    public static function mix(string $a, string $b, float $t): string
    {
        $pa = sscanf(ltrim($a, '#'), '%02x%02x%02x'); $pb = sscanf(ltrim($b, '#'), '%02x%02x%02x');
        $o = '#';
        for ($i = 0; $i < 3; $i++) $o .= sprintf('%02x', (int)round($pa[$i] + ($pb[$i] - $pa[$i]) * $t));
        return $o;
    }

    public static function write(): string
    {
        $dir = PP_ROOT . '/uploads/identity';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        file_put_contents($dir . '/identity.css', self::css());
        return 'uploads/identity/identity.css';
    }

    public static function cssUrl(): string
    {
        if (!is_file(PP_ROOT . '/uploads/identity/identity.css')) self::write();
        return PP::upload('uploads/identity/identity.css');
    }

    /** Logo inline (SVG ripulito) o <img>. $dark = versione per fondi scuri, se c'è. */
    public static function logo(bool $dark = false, string $class = 'logo'): string
    {
        $s = self::get();
        $k = $dark && ($s['logo_dark'] || $s['logo_dark_svg']) ? 'logo_dark' : 'logo';
        if ($s[$k . '_svg']) {
            return preg_replace('~^<svg~', '<svg class="' . e($class) . '" role="img" aria-label="' . e(self::name()) . '" focusable="false"', $s[$k . '_svg'], 1);
        }
        if ($s[$k]) return '<img class="' . e($class) . '" src="' . e(PP::upload($s[$k])) . '" alt="' . e(self::name()) . '">';
        return '';
    }

    public static function favicon(): string
    {
        $s = self::get();
        if ($s['favicon']) return PP::upload($s['favicon']);
        if ($s['logo'] && str_ends_with($s['logo'], '.svg')) return PP::upload($s['logo']);
        return '';
    }

    /* ---------- gestione ---------- */

    public static function admin(): void
    {
        $s = self::get();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = (string)Admin::post('action');
            if ($act === 'font_upload') {
                $fam = trim(preg_replace('~[^\pL\pN \-]~u', '', (string)Admin::post('family')));
                if ($fam === '' || empty($_FILES['font']['name'])) { Admin::flash('Indica il nome della famiglia e scegli un file .woff2', 'error'); }
                else {
                    try {
                        $ext = strtolower(pathinfo($_FILES['font']['name'], PATHINFO_EXTENSION));
                        if ($ext !== 'woff2') throw new RuntimeException('Serve un file .woff2 (convertilo con un servizio come transfonter, se necessario)');
                        $dir = PP_ROOT . '/uploads/fonts';
                        if (!is_dir($dir)) mkdir($dir, 0775, true);
                        $name = Media::filename($fam . '-' . Admin::post('style', 'normal')) . '.woff2';
                        move_uploaded_file($_FILES['font']['tmp_name'], $dir . '/' . $name) || rename($_FILES['font']['tmp_name'], $dir . '/' . $name);
                        $s['fonts_custom'][$fam]['kind'] = 'custom';
                        $s['fonts_custom'][$fam]['fallback'] = Admin::post('fallback') === 'serif' ? 'Georgia, serif' : 'system-ui, Arial, sans-serif';
                        $s['fonts_custom'][$fam]['weight'] = preg_replace('~[^0-9 ]~', '', (string)Admin::post('weight', '400')) ?: '400';
                        $s['fonts_custom'][$fam]['files'][] = ['file' => 'uploads/fonts/' . $name, 'style' => Admin::post('style') === 'italic' ? 'italic' : 'normal', 'range' => ''];
                        self::save($s);
                        Admin::flash('Font “' . $fam . '” aggiunto. Ora puoi sceglierlo qui sotto.');
                    } catch (Throwable $e) { Admin::flash($e->getMessage(), 'error'); }
                }
                PP::redirect(Admin::u('identity'));
            }
            $s['site_name'] = trim((string)Admin::post('site_name')) ?: (PP::$config['site_name'] ?? '');
            foreach (self::COLORS as $k => $_) {
                $v = strtolower(trim((string)($_POST['colors'][$k] ?? '')));
                if (preg_match('~^#[0-9a-f]{6}$~', $v)) $s['colors'][$k] = $v;
            }
            $fonts = self::fonts();
            if (isset($fonts[Admin::post('font_display')])) $s['font_display'] = Admin::post('font_display');
            if (isset($fonts[Admin::post('font_text')])) $s['font_text'] = Admin::post('font_text');
            if (in_array(Admin::post('buttons'), ['pill', 'soft', 'square'], true)) $s['buttons'] = Admin::post('buttons');
            foreach (['logo' => ['svg', 'png', 'webp'], 'logo_dark' => ['svg', 'png', 'webp'], 'favicon' => ['svg', 'png'], 'share_image' => ['jpg', 'jpeg', 'png', 'webp']] as $k => $allow) {
                if (Admin::post('remove_' . $k)) { $s[$k] = ''; if (isset($s[$k . '_svg'])) $s[$k . '_svg'] = ''; }
                if (empty($_FILES[$k]['name'])) continue;
                try {
                    $path = Media::upload($_FILES[$k], 'identity', $allow);
                    $s[$k] = $path;
                    if (isset($s[$k . '_svg'])) $s[$k . '_svg'] = str_ends_with($path, '.svg') ? (string)file_get_contents(PP_ROOT . '/' . $path) : '';
                    if (Media::$note) Admin::flash(Media::$note);
                } catch (Throwable $e) { Admin::flash($e->getMessage(), 'error'); }
            }
            if (isset($_POST['claim'])) Content::saveString('claim', I18n::$master, (string)$_POST['claim']);
            self::save($s);
            Auth::log('save identity');
            Admin::changed('identity');
            Admin::flash('Identità visiva salvata.');
            PP::redirect(Admin::u('identity'));
        }
        Admin::view(__DIR__ . '/admin.php', ['title' => 'Identità visiva', 's' => $s, 'fonts' => self::fonts(), 'checks' => self::checks($s['colors'])]);
    }

    /** Applica colori, font e forma dei pulsanti suggeriti da un tema. La versione precedente resta in 'identity_previous'. */
    public static function apply(array $colors, ?string $display = null, ?string $text = null, ?string $buttons = null): void
    {
        $s = self::get();
        Settings::set('identity_previous', ['colors' => $s['colors'], 'font_display' => $s['font_display'], 'font_text' => $s['font_text'], 'buttons' => $s['buttons']]);
        foreach ($colors as $k => $v) if (isset(self::COLORS[$k]) && preg_match('~^#[0-9a-f]{6}$~i', (string)$v)) $s['colors'][$k] = strtolower($v);
        $fonts = self::fonts();
        if ($display && isset($fonts[$display])) $s['font_display'] = $display;
        if ($text && isset($fonts[$text])) $s['font_text'] = $text;
        if (in_array($buttons, ['pill', 'soft', 'square'], true)) $s['buttons'] = $buttons;
        self::save($s);
    }

    private static function save(array $s): void
    {
        Settings::set('identity', $s);
        self::write();
    }
}
