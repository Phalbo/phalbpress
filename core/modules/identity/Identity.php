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
            'logo_has_name' => !empty($d['logo_has_name']),   // il logo contiene già il nome: in testata il nome resta solo per i lettori di schermo
            'logo_h' => (int)($d['logo_h'] ?? 0),               // altezza del logo in testata (px); 0 = quella del tema
            'photo_fx' => (string)($d['photo_fx'] ?? ''),        // foto negli elenchi: '' come il tema, none, mono (bianco e nero), tint (virate nel colore principale)
            'photo_hover' => (string)($d['photo_hover'] ?? ''),  // passandoci sopra: '' come il tema, color (tornano a colori), zoom, none
            'band_h' => (string)($d['band_h'] ?? ''),            // altezza delle intestazioni delle pagine interne: '' come il tema, compact, tall
            'logo_head' => (string)($d['logo_head'] ?? ''),      // logo in testata: '' da solo (chiaro se la testata è scura), day (sempre quello normale), night (sempre quello per fondi scuri)
            'space' => (string)($d['space'] ?? ''),              // spazi tra le fasce: '' come il tema, compact, wide
            'boxed' => !empty($d['boxed']),                      // contenuto in un riquadro centrale (le foto di apertura restano a tutta larghezza)
            'hero_shade' => (string)($d['hero_shade'] ?? ''),    // apertura con foto: velo scuro 0-80 (%), '' come il tema
            'hero_text' => (string)($d['hero_text'] ?? ''),      // apertura con foto: '' come il tema, shadow (ombra leggera), panel (fondino)
            'band_shade' => (string)($d['band_shade'] ?? ''),    // intestazioni con foto: velo scuro 0-80 (%)
            'band_text' => (string)($d['band_text'] ?? ''),      // intestazioni con foto: shadow, panel
            'band_fx' => (string)($d['band_fx'] ?? ''),          // foto delle intestazioni: '' come il tema, color (a colori), mono, tint
            'hero_fx' => (string)($d['hero_fx'] ?? ''),          // foto dell'apertura della home: '' come il tema, color, mono
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
        return (max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05);   // senza arrotondare: 4,496 non è 4,5
    }

    /** Testo attenuato: il testo schiarito verso lo sfondo, ma mai sotto 4.6:1 (WCAG AA con un po' di margine). */
    public static function muted(string $ink, string $ground): string
    {
        for ($t = 0.3; $t > 0; $t -= 0.02) {
            $m = self::mix($ink, $ground, $t);
            if (self::contrast($m, $ground) >= 4.6) return $m;
        }
        return $ink;
    }

    /** Testo leggibile su un colore: nero o bianco, quello con più contrasto. */
    public static function inkOn(string $bg): string
    {
        return self::contrast($bg, '#000000') >= self::contrast($bg, '#ffffff') ? '#000000' : '#ffffff';
    }

    /** Coppie da verificare (WCAG 2.1: 4,5 testo normale, 3 testo grande). */
    /**
     * Contrasti della palette. La frase grande dell'apertura si controlla sul fondo dove il tema la mette:
     * "hero_on" nel theme.json = accent (sul colore principale), ground (sullo sfondo, predefinito) o photo (sempre bianca sulla foto: non dipende dalla palette).
     */
    public static function checks(array $c, ?string $heroOn = null): array
    {
        $heroOn ??= (string)((PP::themes()[PP::theme()] ?? [])['hero_on'] ?? 'ground');
        $heroBg = $heroOn === 'ground' ? $c['ground'] : $c['accent'];
        $where = $heroOn === 'ground' ? 'sullo sfondo' : 'sul colore principale';
        $rows = [
            ['Testo sullo sfondo', $c['ink'], $c['ground'], 4.5],
            ['Testo sul colore principale', self::inkOn($c['accent']), $c['accent'], 4.5],
            ['Testo sul campo 1', self::inkOn($c['field1']), $c['field1'], 4.5],
            ['Testo sul campo 2', self::inkOn($c['field2']), $c['field2'], 4.5],
            ['Testo sul campo scuro', self::inkOn($c['dark']), $c['dark'], 4.5],
        ];
        if ($heroOn !== 'photo') {
            $rows[] = ['Frase grande, prima riga ' . $where, $c['hero1'], $heroBg, 3];
            $rows[] = ['Frase grande, seconda riga ' . $where, $c['hero2'], $heroBg, 3];
        }
        $rows[] = ['Colore principale su sfondo (titoli, link evidenziati)', $c['accent'], $c['ground'], 3];
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
            . "--bg:{$c['ground']};--ink:{$c['ink']};--surface:" . self::mix($c['ground'], $c['ink'], 0.05) . ";--muted:" . self::muted($c['ink'], $c['ground']) . ";\n"
            . "--accent:{$c['accent']};--accent-ink:" . self::inkOn($c['accent']) . ";\n"
            . "--field-1:{$c['field1']};--field-1-ink:" . self::inkOn($c['field1']) . ";\n"
            . "--field-2:{$c['field2']};--field-2-ink:" . self::inkOn($c['field2']) . ";\n"
            . "--black:{$c['dark']};--black-ink:" . self::inkOn($c['dark']) . ";\n"
            . "--hero-1:{$c['hero1']};--hero-2:{$c['hero2']};\n"
            . "--font:" . $fb($s['font_text']) . ";--font-display:" . $fb($s['font_display']) . ";\n"
            . "--radius:" . ($s['buttons'] === 'square' ? '0px' : ($s['buttons'] === 'soft' ? '10px' : '999px')) . ";\n}\n";
        $css .= self::extraCss($s);
        return $css;
    }

    /** Altezza del logo ed effetto sulle foto degli elenchi: valgono con ogni tema (identity.css viene dopo il tema). */
    private static function extraCss(array $s): string
    {
        $css = '';
        $h = max(0, min(160, (int)($s['logo_h'] ?? 0)));
        if ($h >= 16) $css .= ".site-head .brand__logo{height:{$h}px;max-height:none}\n@media (max-width:960px){.site-head .brand__logo{height:" . min($h, 44) . "px}}\n";
        // foto degli elenchi (schede, portfolio, gallerie, altri lavori): mai quelle aperte, che restano a colori
        $box = ':is(.story__pic,.card__img,.worknav__pic,.gallery__item,.collection__pic)';
        $item = ':is(.story,.card,.worknav__item,.gallery__item,.collection__item)';
        $fx = (string)($s['photo_fx'] ?? '');
        if ($fx !== '') {
            $css .= "{$box}{position:relative}\n{$box}::after{content:\"\";position:absolute;inset:0;pointer-events:none;border-radius:inherit;transition:opacity .5s cubic-bezier(.16,1,.3,1);opacity:0}\n";
            $css .= "{$box} img{transition:filter .5s cubic-bezier(.16,1,.3,1),transform .9s cubic-bezier(.16,1,.3,1);filter:" . ($fx === 'none' ? 'none' : 'grayscale(1) contrast(1.08)') . "}\n";
            if ($fx === 'tint') $css .= "{$box}::after{background:color-mix(in srgb,var(--accent) 40%,#fff);mix-blend-mode:multiply;opacity:1}\n";
        }
        $hv = (string)($s['photo_hover'] ?? '');
        if ($hv === 'color') $css .= "{$item}:is(:hover,:focus-within) {$box} img{filter:none}\n{$item}:is(:hover,:focus-within) {$box}::after{opacity:0}\n";
        if ($hv === 'zoom') $css .= "{$item}:is(:hover,:focus-within) {$box} img{transform:scale(1.05)}\n";
        if ($hv === 'none') $css .= "{$item}:is(:hover,:focus-within) {$box} img{transform:none}\n";
        if ($css !== '') $css .= "@media (prefers-reduced-motion:reduce){{$box} img,{$box}::after{transition:none}}\n";
        // intestazioni delle pagine interne più basse o più alte
        if (($s['band_h'] ?? '') === 'compact') $css .= ".site-main .band{padding-top:clamp(2rem,4vw,3.25rem)!important;padding-bottom:clamp(1.75rem,3vw,2.75rem)!important;min-height:0!important}\n.site-main .band.head--cover.band--img{min-height:min(40svh,380px)!important}\n.site-main .band .band__title{font-size:clamp(2rem,1.2rem + 2.6vw,3.6rem)}\n.site-main .band.band--long .band__title{font-size:clamp(1.7rem,1.1rem + 1.8vw,2.8rem)}\n.site-main .head--split .band__img img{max-height:340px;min-height:220px}\n";
        if (($s['band_h'] ?? '') === 'tall') $css .= ".site-main .band.head--cover.band--img{min-height:min(80svh,760px)!important}\n";
        return $css . self::layoutCss($s);
    }

    /** Logo in testata, spazi, riquadro, velo e testo sulle foto di apertura e delle intestazioni. */
    private static function layoutCss(array $s): string
    {
        $css = '';
        $h = max(0, min(160, (int)($s['logo_h'] ?? 0)));
        if ($h >= 16) $css .= ":root{--logo-h:{$h}px}\n";
        if (($s['logo_head'] ?? '') === 'day') $css .= ".site-head .brand--duo .brand__logo--night{display:none!important}.site-head .brand--duo .brand__logo--day{display:block!important}\n";
        if (($s['logo_head'] ?? '') === 'night') $css .= ".site-head .brand--duo .brand__logo--day{display:none!important}.site-head .brand--duo .brand__logo--night{display:block!important}\n";
        // spazi tra le fasce: --section è la misura che tutti i temi usano
        if (($s['space'] ?? '') === 'compact') $css .= ":root{--section:clamp(2.5rem,4.5vw,4.5rem)}\n";
        if (($s['space'] ?? '') === 'wide') $css .= ":root{--section:clamp(5rem,10vw,10rem)}\n";
        // riquadro: le fasce e il footer in una colonna centrale su un fondo appena più scuro; l'apertura con la foto resta a tutta larghezza
        if (!empty($s['boxed'])) $css .= "body{background:color-mix(in srgb,var(--ink) 7%,var(--bg))}\n"
            . ".site-main>:not(.hero--img),.site-foot{max-width:var(--boxed-w,1340px);margin-left:auto!important;margin-right:auto!important;box-sizing:border-box}\n"
            . ":where(.site-main>*),:where(.site-foot){background-color:var(--bg)}\n"
            . "@media (max-width:1380px){.site-main>:not(.hero--img),.site-foot{max-width:none}}\n";
        // velo e testo sulle foto
        foreach (['hero' => ['.photo-hero .hero--img', '.hero__img', '.photo-hero .hero--img > :not(.hero__img)', '.photo-hero .hero--img :is(.hero__l1,.hero__l2,.hero__sentence)'],
                  'band' => ['.band.head--cover.band--img', '.band__img', '.band.head--cover.band--img > :not(.band__img):not(.band__deco)', '.band.head--cover.band--img :is(.band__title,.band__lead,.band__label)']] as $k => [$box, $fig, $txt, $lines]) {
            $sh = (string)($s[$k . '_shade'] ?? '');
            if ($sh !== '' && ctype_digit($sh)) {
                $a = max(0, min(80, (int)$sh)) / 100;
                $css .= "{$box} > {$fig}::after{background:none!important;mix-blend-mode:normal!important;opacity:0!important}\n"
                    . "{$box} > {$fig} .pp-shade{display:block;position:absolute;inset:0;z-index:2;pointer-events:none;background:rgb(0 0 0/{$a})}\n";
            }
            $tx = (string)($s[$k . '_text'] ?? '');
            if ($tx === 'shadow') $css .= "{$txt}{text-shadow:0 1px 2px rgb(0 0 0/.5),0 2px 22px rgb(0 0 0/.45)}\n";
            if ($tx === 'panel') $css .= "{$lines}{background:rgb(0 0 0/.55);color:#fff;width:fit-content;max-width:100%;padding:.06em .3em;-webkit-box-decoration-break:clone;box-decoration-break:clone}\n";
        }
        // foto dell'apertura: a colori (senza il viraggio del tema) o in bianco e nero
        $hf = (string)($s['hero_fx'] ?? '');
        if ($hf !== '') $css .= ".photo-hero .hero--img .hero__img img{filter:" . ($hf === 'color' ? 'none' : 'grayscale(1) contrast(1.06)') . "!important}\n"
            . ($hf === 'color' && (string)($s['hero_shade'] ?? '') === '' ? ".photo-hero .hero--img .hero__img::after{mix-blend-mode:normal!important;background:linear-gradient(to top,rgb(0 0 0/.55),rgb(0 0 0/.05) 60%)!important;opacity:1!important}\n" : '');
        // foto delle intestazioni: a colori, in bianco e nero o virate, indipendenti da quelle degli elenchi
        $bf = (string)($s['band_fx'] ?? '');
        if ($bf !== '') {
            $css .= ".band__img img{filter:" . ($bf === 'color' ? 'none' : 'grayscale(1) contrast(1.06)') . "!important}\n.band:not(.head--cover) .band__img::after{display:none!important}\n";
            if ($bf === 'color' && (string)($s['band_shade'] ?? '') === '') $css .= ".band.head--cover.band--img > .band__img::after{mix-blend-mode:normal!important;background:linear-gradient(to top,rgb(0 0 0/.62),rgb(0 0 0/.1) 65%)!important;opacity:1!important}\n";
            if ($bf === 'tint') $css .= ".band__img{position:relative}.band__img .pp-tint{display:block;position:absolute;inset:0;z-index:0;pointer-events:none;background:color-mix(in srgb,var(--accent) 45%,#fff);mix-blend-mode:multiply}\n";
        }
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
            // varianti di colore del tema attivo (theme.json "palettes"): si applicano come i colori suggeriti, la versione di prima si ripristina
            if ($act === 'variant') {
                $vars = PP::themes()[PP::theme()]['palettes'] ?? [];
                $v = $vars[(int)Admin::post('variant')] ?? null;
                if ($v) {
                    self::apply($v['palette'] ?? [], $v['fonts']['display'] ?? null, $v['fonts']['text'] ?? null, $v['buttons'] ?? null);
                    Admin::changed('identity');
                    Admin::flash('Variante “' . ($v['name'] ?? '') . '” applicata. Quella di prima si ripristina qui sotto.');
                }
                PP::redirect(Admin::u('identity'));
            }
            if ($act === 'variant_undo' && ($prev = Settings::get('identity_previous'))) {
                self::apply($prev['colors'] ?? [], $prev['font_display'] ?? null, $prev['font_text'] ?? null, $prev['buttons'] ?? null);
                Settings::set('identity_previous', null);
                Admin::changed('identity');
                Admin::flash('Colori e font di prima ripristinati.');
                PP::redirect(Admin::u('identity'));
            }
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
            $s['logo_has_name'] = (bool)Admin::post('logo_has_name');
            $s['logo_h'] = max(0, min(160, (int)Admin::post('logo_h')));
            $s['photo_fx'] = in_array(Admin::post('photo_fx'), ['none', 'mono', 'tint'], true) ? Admin::post('photo_fx') : '';
            $s['photo_hover'] = in_array(Admin::post('photo_hover'), ['color', 'zoom', 'none'], true) ? Admin::post('photo_hover') : '';
            $s['band_h'] = in_array(Admin::post('band_h'), ['compact', 'tall'], true) ? Admin::post('band_h') : '';
            $s['logo_head'] = in_array(Admin::post('logo_head'), ['day', 'night'], true) ? Admin::post('logo_head') : '';
            $s['space'] = in_array(Admin::post('space'), ['compact', 'wide'], true) ? Admin::post('space') : '';
            $s['boxed'] = (bool)Admin::post('boxed');
            foreach (['hero', 'band'] as $k) {
                $v = (string)Admin::post($k . '_shade');
                $s[$k . '_shade'] = ctype_digit($v) ? (string)max(0, min(80, (int)$v)) : '';
                $s[$k . '_text'] = in_array(Admin::post($k . '_text'), ['shadow', 'panel'], true) ? Admin::post($k . '_text') : '';
            }
            $s['band_fx'] = in_array(Admin::post('band_fx'), ['color', 'mono', 'tint'], true) ? Admin::post('band_fx') : '';
            $s['hero_fx'] = in_array(Admin::post('hero_fx'), ['color', 'mono'], true) ? Admin::post('hero_fx') : '';
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
