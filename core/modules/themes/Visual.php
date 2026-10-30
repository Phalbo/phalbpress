<?php
/**
 * Modifica visuale della home: anteprima a sinistra, pannello a destra.
 * Si clicca una fascia nell'anteprima (o nell'elenco) e se ne cambia l'aspetto: fondo, colore libero, immagine di fondo,
 * velo, parallasse, spazio, larghezza. Si spostano, si nascondono, se ne aggiungono di nuove.
 * I testi si cambiano dalla scheda del blocco (link nel pannello): l'aspetto vale per tutte le lingue.
 */
final class Visual
{
    public static function admin(array $seg): void
    {
        $act = $seg[0] ?? '';
        $id = (int)($seg[1] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                match ($act) {
                    'save' => self::save($id),
                    'order' => self::order(),
                    'status' => self::status($id),
                    'add' => self::add(),
                    default => Admin::json(['error' => 'Azione sconosciuta'], 404),
                };
            } catch (Throwable $e) {
                Admin::json(['error' => $e->getMessage()], 500);
            }
        }
        if ($act === 'panel') { self::panel($id); return; }
        if ($act === 'list') { self::listHtml(); return; }
        $langs = I18n::codes();
        $lang = in_array($_GET['lang'] ?? '', $langs, true) ? $_GET['lang'] : I18n::$master;
        Admin::view(__DIR__ . '/visual.php', ['title' => 'Modifica visuale', 'lang' => $lang, 'langs' => $langs,
            'kinds' => array_map(fn($k) => $k['label'], Blocks::kinds())]);
    }

    /** Blocchi della home in ordine, pubblicati e nascosti, con il titolo nell'originale. */
    public static function blocks(): array
    {
        return DB::all("SELECT e.id, e.template, e.status, e.menu_order, e.meta, t.title FROM pp_entries e
            LEFT JOIN pp_entry_tr t ON t.entry_id = e.id AND t.lang = ? WHERE e.type = 'block' ORDER BY e.menu_order, e.id", [I18n::$master]);
    }

    private static function block(int $id): array
    {
        $r = DB::one("SELECT * FROM pp_entries WHERE id = ? AND type = 'block'", [$id]);
        if (!$r) throw new RuntimeException('Fascia non trovata');
        return $r;
    }

    private static function listHtml(): void
    {
        $kinds = Blocks::kinds();
        header('Content-Type: text/html; charset=utf-8');
        foreach (self::blocks() as $b) {
            $kind = $kinds[$b['template']]['label'] ?? $b['template'];
            echo '<li class="vis-item' . ($b['status'] !== 'published' ? ' is-hidden' : '') . '" data-id="' . (int)$b['id'] . '" draggable="true">'
                . '<button type="button" class="vis-item__pick" data-pick="' . (int)$b['id'] . '"><span class="vis-item__kind">' . e($kind) . '</span> '
                . e($b['title'] ?: '(senza titolo)') . ($b['status'] !== 'published' ? ' <span class="pill">nascosta</span>' : '') . '</button>'
                . '<span class="vis-item__move"><button type="button" class="btn btn--small" data-move="-1" aria-label="Sposta su">↑</button>'
                . '<button type="button" class="btn btn--small" data-move="1" aria-label="Sposta giù">↓</button></span></li>';
        }
        exit;
    }

    private static function panel(int $id): void
    {
        $b = self::block($id);
        $meta = Content::meta($b);
        $fields = array_intersect_key(Blocks::fields((string)$b['template']), Blocks::look());
        $title = (string)DB::val('SELECT title FROM pp_entry_tr WHERE entry_id = ? AND lang = ?', [$id, I18n::$master]);
        $colors = class_exists('Identity') ? Identity::get()['colors'] : [];
        header('Content-Type: text/html; charset=utf-8');
        require __DIR__ . '/visual-panel.php';
        exit;
    }

    private static function save(int $id): void
    {
        $b = self::block($id);
        $meta = Content::meta($b);
        foreach (Blocks::look() as $k => $f) {
            $f += ['type' => 'text', 'options' => []];
            $meta[$k] = Admin::fieldValue($f, $_POST['meta'][$k] ?? '');
            if ($meta[$k] === '') unset($meta[$k]);
        }
        if (($meta['tone'] ?? '') !== 'custom') unset($meta['bg_color']);
        if (empty($meta['bg_image'])) unset($meta['bg_focus'], $meta['bg_overlay'], $meta['parallax']);
        DB::update('pp_entries', ['meta' => json_encode($meta, JSON_UNESCAPED_UNICODE), 'updated_at' => Content::now()], 'id = ?', [$id]);
        Auth::log('visual block', 'block #' . $id);
        Admin::changed('visual');
        Admin::json(['ok' => true]);
    }

    private static function order(): void
    {
        $ids = array_values(array_filter(array_map('intval', (array)($_POST['ids'] ?? []))));
        $known = array_map('intval', array_column(self::blocks(), 'id'));
        if (!$ids || array_diff($ids, $known)) throw new RuntimeException('Ordine non valido');
        foreach ($ids as $n => $bid) DB::update('pp_entries', ['menu_order' => ($n + 1) * 10], 'id = ?', [$bid]);
        Auth::log('visual order');
        Admin::changed('visual');
        Admin::json(['ok' => true]);
    }

    private static function status(int $id): void
    {
        $b = self::block($id);
        $new = $b['status'] === 'published' ? 'draft' : 'published';
        DB::update('pp_entries', ['status' => $new, 'updated_at' => Content::now()], 'id = ?', [$id]);
        Auth::log('visual ' . ($new === 'published' ? 'show' : 'hide'), 'block #' . $id);
        Admin::changed('visual');
        Admin::json(['ok' => true, 'status' => $new]);
    }

    /** Nuova fascia, nascosta finché non si scrivono i testi. Va dopo quella indicata (o in fondo). */
    private static function add(): void
    {
        $kind = (string)Admin::post('kind');
        $kinds = Blocks::kinds();
        if (!isset($kinds[$kind])) throw new RuntimeException('Tipo di fascia sconosciuto');
        $after = (int)Admin::post('after');
        $list = self::blocks();
        $order = (int)(end($list)['menu_order'] ?? 0) + 10;
        foreach ($list as $r) if ((int)$r['id'] === $after) $order = (int)$r['menu_order'] + 5;
        $now = Content::now();
        $title = 'Nuova fascia: ' . mb_strtolower($kinds[$kind]['label']);
        $id = DB::insert('pp_entries', ['type' => 'block', 'slug' => 'blocco-' . $kind . '-' . bin2hex(random_bytes(3)), 'template' => $kind,
            'menu_order' => $order, 'in_menu' => 0, 'status' => 'draft', 'pub_date' => date('Y-m-d'), 'image' => '', 'meta' => '{}',
            'publish_at' => '', 'unpublish_at' => '', 'author_id' => (int)Auth::user()['id'], 'created_at' => $now, 'updated_at' => $now]);
        Content::saveText('entry', $id, I18n::$master, ['title' => $title, 'excerpt' => '', 'body' => '', 'slug' => '', 'extra' => []]);
        // ordine pulito: 10, 20, 30…
        $ids = array_map('intval', array_column(self::blocks(), 'id'));
        foreach ($ids as $n => $bid) DB::update('pp_entries', ['menu_order' => ($n + 1) * 10], 'id = ?', [$bid]);
        Auth::log('visual add', 'block #' . $id);
        Admin::changed('visual');
        Admin::json(['ok' => true, 'id' => $id]);
    }
}
