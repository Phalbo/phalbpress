<?php
final class Menus
{
    public const NAMES = ['main' => 'Menu principale', 'footer' => 'Menu del footer'];

    /** Voci salvate: [['kind' => page|section|url, 'ref' => id|chiave sezione, 'url' => …, 'label' => [lang => …], 'parent' => 0|1, 'new' => bool]] */
    public static function saved(string $name): ?array
    {
        $m = Settings::get('menus', []);
        return isset($m[$name]) && is_array($m[$name]) ? $m[$name] : null;
    }

    /** Sezioni fisse disponibili nel menu (stringa traducibile come etichetta). */
    public static function sections(): array
    {
        $s = ['home' => 'home'];
        foreach (array_keys(PP::$config['sections'] ?? []) as $k) $s[$k] = $k . '_title';
        if (isset(PP::$config['types']['category'], PP::$config['sections']['stories'])) $s['stories:cats'] = 'stories_cats_menu';
        return Hooks::filter('menu_sections', $s);
    }

    /** Menu automatico: pagine "nel menu" e sezioni fisse ordinate con section_menu. */
    public static function auto(string $name): array
    {
        if ($name !== 'main') return [];
        $items = [];
        foreach (DB::all("SELECT id, menu_order FROM pp_entries WHERE type = 'page' AND in_menu = 1 AND slug <> 'home' AND " . Content::live() . ' ORDER BY menu_order, id') as $p) {
            $items[] = ['kind' => 'page', 'ref' => (int)$p['id'], 'order' => (int)$p['menu_order']];
        }
        foreach (PP::$config['section_menu'] ?? [] as $k => $o) $items[] = ['kind' => 'section', 'ref' => $k, 'order' => (int)$o];
        usort($items, fn($a, $b) => $a['order'] <=> $b['order']);
        return $items;
    }

    public static function raw(string $name): array { return self::saved($name) ?? self::auto($name); }

    /** Voci pronte per il tema, nella lingua corrente. */
    public static function items(string $name = 'main', ?string $lang = null): array
    {
        $lang = $lang ?: I18n::$current;
        $here = PP::current();
        $out = [];
        foreach (self::raw($name) as $it) {
            $label = trim((string)($it['label'][$lang] ?? '')) ?: trim((string)($it['label'][I18n::$master] ?? ''));
            $href = ''; $ext = false;
            if ($it['kind'] === 'page') {
                $e = Content::byId((int)$it['ref'], $lang);
                if (!$e) continue;
                $href = PP::entryUrl($e, $lang);
                $label = $label ?: $e['title'];
            } elseif ($it['kind'] === 'section' && $it['ref'] === 'stories:cats') {
                // categorie del portfolio: le voci nascono da sole dalle categorie visibili (con le divisioni come terzo livello)
                if (!class_exists('Stories')) continue;
                $cats = Stories::menu($lang);
                if (!empty($it['parent']) && $out) { $out[count($out) - 1]['children'] = array_merge($out[count($out) - 1]['children'], $cats); foreach ($cats as $c) if ($c['current']) $out[count($out) - 1]['current'] = true; continue; }
                $href = PP::link(PP::section('stories', $lang), $lang);
                $out[] = ['label' => $label ?: t('stories_title', $lang), 'href' => $href, 'current' => str_starts_with($here, $href), 'external' => false, 'children' => $cats];
                continue;
            } elseif ($it['kind'] === 'section') {
                $k = (string)$it['ref'];
                $href = $k === 'home' ? PP::link('', $lang) : PP::link(PP::section($k, $lang), $lang);
                $label = $label ?: (I18n::t(self::sections()[$k] ?? $k . '_title', $lang) ?: ucfirst($k));
            } else {
                $href = (string)($it['url'] ?? '');
                if ($href === '' || !preg_match('~^(https?://|mailto:|tel:|/|#)~i', $href)) continue;
                $ext = (bool)preg_match('~^https?://~i', $href) && !str_starts_with($href, PP::origin());
                $label = $label ?: $href;
            }
            $cur = !$ext && $href !== '' && ($href === $here || ($href !== PP::link('', $lang) && str_starts_with($here, $href)));
            $item = ['label' => $label, 'href' => $href, 'current' => $cur, 'external' => $ext || !empty($it['new']), 'children' => []];
            if (!empty($it['parent']) && $out) { $out[count($out) - 1]['children'][] = $item; if ($cur) $out[count($out) - 1]['current'] = true; }
            else $out[] = $item;
        }
        return $out;
    }

    public static function admin(string $name): void
    {
        if (!isset(self::NAMES[$name])) $name = 'main';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $all = Settings::get('menus', []);
            if (Admin::post('action') === 'reset') {
                unset($all[$name]);
                Settings::set('menus', $all);
                Admin::flash('Torna il menu automatico.');
            } else {
                $rows = [];
                foreach ((array)($_POST['m'] ?? []) as $r) {
                    if (!empty($r['remove'])) continue;
                    $kind = in_array($r['kind'] ?? '', ['page', 'section', 'url'], true) ? $r['kind'] : '';
                    if (!$kind) continue;
                    $ref = $kind === 'page' ? (int)($r['page'] ?? 0) : ($kind === 'section' ? (string)($r['section'] ?? '') : '');
                    $url = trim((string)($r['url'] ?? ''));
                    if (($kind === 'page' && !$ref) || ($kind === 'section' && !isset(self::sections()[$ref])) || ($kind === 'url' && !preg_match('~^(https?://|mailto:|tel:|/|#)~i', $url))) continue;
                    $labels = [];
                    foreach (I18n::codes() as $l) { $v = trim(strip_tags((string)($r['label'][$l] ?? ''))); if ($v !== '') $labels[$l] = mb_substr($v, 0, 80); }
                    $rows[] = ['kind' => $kind, 'ref' => $ref, 'url' => $url, 'label' => $labels, 'parent' => !empty($r['parent']) ? 1 : 0,
                        'new' => !empty($r['new']) ? 1 : 0, 'order' => (float)($r['order'] ?? 0)];
                }
                usort($rows, fn($a, $b) => $a['order'] <=> $b['order']);
                foreach ($rows as &$r) unset($r['order']);
                $all[$name] = $rows;
                Settings::set('menus', $all);
                Admin::flash('Menu salvato.');
            }
            Auth::log('save menu', $name);
            Admin::changed('menu');
            PP::redirect(Admin::u('menus/' . $name));
        }
        $pages = DB::all("SELECT e.id, e.status, t.title FROM pp_entries e LEFT JOIN pp_entry_tr t ON t.entry_id = e.id AND t.lang = ? WHERE e.type = 'page' ORDER BY e.menu_order, t.title", [I18n::$master]);
        Admin::view(__DIR__ . '/admin.php', ['title' => 'Menu', 'name' => $name, 'rows' => self::raw($name), 'custom' => self::saved($name) !== null,
            'pages' => $pages, 'sections' => self::sections()]);
    }
}
