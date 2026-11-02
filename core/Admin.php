<?php
/**
 * Area di gestione: /admin
 */
final class Admin
{
    public static array $flash = [];

    public static function handle(array $seg): void
    {
        if (!Auth::ipAllowed()) { http_response_code(403); exit('Accesso non consentito da questo indirizzo.'); }
        Auth::start();
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex, nofollow');
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline'; script-src 'self'; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
        I18n::$current = I18n::$master;
        $a = $seg[0] ?? '';

        if ($a === 'login') { ($seg[1] ?? '') === 'forgot' ? self::forgot() : (($seg[1] ?? '') === 'reset' ? self::reset() : self::login()); return; }
        if (!Auth::user()) PP::redirect(self::u('login'));
        if ($_SERVER['REQUEST_METHOD'] === 'POST') Auth::checkCsrf();

        // aggiornamenti del core o dei moduli: applica da solo le modifiche al database
        $migrated = Modules::migrate();
        if ($migrated) self::flash('Aggiornamento applicato: ' . implode(', ', $migrated));
        // riserva per i contenuti programmati, se cron.php non è attivo
        if ($_SERVER['REQUEST_METHOD'] === 'GET') { try { Scheduler::tick(); } catch (Throwable $e) {} }

        $pages = self::pages();
        $deny = fn() => self::view('error', ['title' => 'Permesso negato', 'message' => 'Il tuo ruolo (' . (Auth::ROLES[Auth::role()] ?? Auth::role()) . ') non comprende questa sezione.']);
        // permessi per le sezioni del core
        $need = ['docs' => 'translate', 'doc' => (($seg[2] ?? I18n::$master) === I18n::$master ? 'site' : 'translate'), 'media' => 'content',
            'strings' => 'translate', 'translate' => 'translate'][$a] ?? null;
        if ($need && !Auth::can($need)) { $deny(); return; }
        if (in_array($a, ['doc', 'strings'], true) && !Auth::canLang((string)($seg[$a === 'doc' ? 2 : 1] ?? I18n::$master)) && Auth::role() === 'translator') {
            $mine = array_values(array_filter(I18n::others(), [Auth::class, 'canLang']));
            if ($a === 'strings' && $mine) PP::redirect(self::u('strings/' . $mine[0]));
            $deny(); return;
        }
        try {
            if (isset($pages[$a]['handler'])) {
                if (!Auth::can($pages[$a]['perm'] ?? (!empty($pages[$a]['admin_only']) ? 'admin' : 'site'))) { $deny(); return; }
                ($pages[$a]['handler'])(array_slice($seg, 1));
                return;
            }
            match ($a) {
                '' => self::dashboard(),
                'logout' => self::logout(),
                'entries' => self::entries($seg[1] ?? 'page'),
                'entry' => self::entry($seg[1] ?? 'new', $seg[2] ?? ''),
                'docs' => self::docs(),
                'doc' => self::doc($seg[1] ?? 'new', $seg[2] ?? I18n::$master),
                'preview' => self::preview(),
                'preview-download' => PP::download((int)($seg[1] ?? 0), (string)($seg[2] ?? I18n::$master), true),
                'strings' => self::strings($seg[1] ?? I18n::$master),
                'media' => self::media(),
                'translate' => self::translatePage(),
                'users' => self::users(),
                'api' => self::api($seg[1] ?? ''),
                default => self::view('notfound', ['title' => 'Non trovato']),
            };
        } catch (Throwable $e) {
            if (!empty(PP::$config['debug'])) throw $e;
            self::view('error', ['title' => 'Errore', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Pagine di gestione aggiunte dai moduli, con il filtro 'admin.pages':
     * ['slug' => ['label' => 'Nome', 'group' => 'Sito', 'handler' => fn(array $seg) => …, 'admin_only' => false]]
     */
    public static function pages(): array
    {
        static $p = null;
        return $p ??= Hooks::filter('admin.pages', []);
    }

    /** Da chiamare dopo ogni modifica dei contenuti: i moduli (HTML statico, scansione cookie…) si aggiornano. */
    public static function changed(string $what = ''): void
    {
        try { Hooks::fire('saved', $what); }
        catch (Throwable $e) { self::flash('Contenuto salvato, ma l’aggiornamento automatico non è riuscito: ' . $e->getMessage(), 'error'); }
    }

    /* ---------- utilità ---------- */

    public static function view(string $tpl, array $vars = []): void
    {
        $vars['flash'] = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        extract($vars, EXTR_SKIP);
        ob_start();
        require str_contains($tpl, '/') ? $tpl : __DIR__ . '/admin/' . $tpl . '.php';
        $body = ob_get_clean();
        require __DIR__ . '/admin/layout.php';
    }

    public static function flash(string $msg, string $type = 'ok'): void { $_SESSION['flash'][] = [$type, $msg]; }
    public static function u(string $p = ''): string { return PP::url(PP::adminSlug() . '/' . ltrim($p, '/')); }

    /**
     * Pagine di un elenco lungo: ['items' => solo quelli della pagina, 'page', 'pages', 'total', 'all' => bool, 'html' => navigazione].
     * Legge ?p=N e ?all=1 dall'indirizzo; gli altri parametri (filtri) restano nei link.
     */
    public static function pager(array $items, int $per = 50): array
    {
        $total = count($items);
        $all = !empty($_GET['all']);
        $pages = max(1, (int)ceil($total / $per));
        $page = $all ? 1 : min($pages, max(1, (int)($_GET['p'] ?? 1)));
        $slice = $all ? $items : array_slice($items, ($page - 1) * $per, $per);
        $q = $_GET; unset($q['p'], $q['all']);
        $path = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $href = fn(array $extra) => $path . '?' . http_build_query($q + $extra);
        $html = '';
        if ($pages > 1 || $all) {
            $html = '<nav class="pager-adm" aria-label="Pagine"><span class="muted small">' . $total . ' in tutto' . ($all ? '' : ' · pagina ' . $page . ' di ' . $pages) . '</span><span class="pager-adm__links">';
            if (!$all) {
                if ($page > 1) $html .= '<a class="btn btn--small" href="' . e($href(['p' => $page - 1])) . '" rel="prev">← Precedenti</a>';
                $win = array_unique(array_filter([1, $page - 2, $page - 1, $page, $page + 1, $page + 2, $pages], fn($n) => $n >= 1 && $n <= $pages));
                sort($win);
                $last = 0;
                foreach ($win as $n) {
                    if ($n - $last > 1) $html .= '<span class="muted">…</span>';
                    $html .= $n === $page ? '<span class="btn btn--small" aria-current="page">' . $n . '</span>' : '<a class="btn btn--small" href="' . e($href(['p' => $n])) . '">' . $n . '</a>';
                    $last = $n;
                }
                if ($page < $pages) $html .= '<a class="btn btn--small" href="' . e($href(['p' => $page + 1])) . '" rel="next">Successivi →</a>';
                $html .= '<a class="btn btn--small" href="' . e($href(['all' => 1])) . '">Vedi tutto</a>';
            } else {
                $html .= '<a class="btn btn--small" href="' . e($href([])) . '">A pagine</a>';
            }
            $html .= '</span></nav>';
        }
        return ['items' => $slice, 'page' => $page, 'pages' => $pages, 'total' => $total, 'all' => $all, 'html' => $html];
    }
    public static function csrfField(): string { return '<input type="hidden" name="csrf" value="' . e(Auth::csrf()) . '">'; }
    public static function post(string $k, $d = '') { return $_POST[$k] ?? $d; }
    public static function json($data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function badge(string $status): string
    {
        return '<span class="badge badge--' . e($status) . '">' . e(I18n::statusLabel($status)) . '</span>';
    }

    /* ---------- accesso ---------- */

    private static function login(): void
    {
        if (Auth::user()) PP::redirect(self::u());
        $error = '';
        $step = !empty($_SESSION['pending_uid']) ? 'totp' : 'password';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::checkCsrf();
            if (Auth::locked()) $error = 'Troppi tentativi. Riprova tra 15 minuti.';
            elseif (self::post('step') === 'totp') {
                if (Auth::verifyTotp((string)self::post('code'))) PP::redirect(self::u());
                $error = 'Codice non valido o scaduto.';
                $step = !empty($_SESSION['pending_uid']) ? 'totp' : 'password';
            } else {
                $r = Auth::login((string)self::post('email'), (string)self::post('password'));
                if ($r === 'ok') PP::redirect(self::u());
                if ($r === 'totp') $step = 'totp';
                else $error = 'Email o password non corrette.';
            }
        }
        $flash = [];
        require __DIR__ . '/admin/login.php';
    }

    /** Password dimenticata: link via email, valido un'ora, usabile una volta. La risposta è sempre la stessa (non rivela chi è registrato). */
    private static function forgot(): void
    {
        if (Auth::user()) PP::redirect(self::u());
        $error = ''; $notice = ''; $step = 'forgot'; $flash = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::checkCsrf();
            if (Auth::locked()) $error = 'Troppi tentativi. Riprova tra 15 minuti.';
            else {
                Auth::fail();   // anche le richieste di reset contano come tentativi: niente invii a raffica
                $u = DB::one('SELECT id, email FROM pp_users WHERE email = ?', [strtolower(trim((string)self::post('email')))]);
                if ($u) {
                    $tok = bin2hex(random_bytes(32));
                    DB::run('DELETE FROM pp_tokens WHERE user_id = ? OR expires < ?', [$u['id'], time()]);
                    DB::insert('pp_tokens', ['user_id' => $u['id'], 'hash' => hash('sha256', $tok), 'expires' => time() + 3600, 'used' => 0]);
                    $link = PP::origin() . '/' . PP::adminSlug() . '/login/reset?t=' . $tok;
                    $site = PP::$config['site_name'] ?? 'PhalbPress';
                    $from = PP::$config['mail_from'] ?? ('noreply@' . preg_replace('~^www\.~', '', parse_url(PP::origin(), PHP_URL_HOST) ?: 'localhost'));
                    $ok = function_exists('mail') && @mail($u['email'], '=?UTF-8?B?' . base64_encode($site . ': nuova password') . '?=',
                        "Qualcuno (speriamo tu) ha chiesto di cambiare la password della gestione di $site.\n\nPer sceglierne una nuova apri questo link entro un'ora:\n$link\n\nSe non sei stato tu, ignora questo messaggio: la password resta quella di prima.\n",
                        'From: ' . $site . ' <' . $from . ">\r\nContent-Type: text/plain; charset=UTF-8");
                    Auth::log('password reset requested', $u['email']);
                    if (!$ok && !empty(PP::$config['debug'])) $notice = 'Email non inviata (debug): ' . $link;
                }
                $notice = $notice ?: 'Se l’indirizzo è registrato, tra poco arriva un’email con il link. Controlla anche lo spam.';
            }
        }
        require __DIR__ . '/admin/login.php';
    }

    private static function reset(): void
    {
        $error = ''; $notice = ''; $step = 'reset'; $flash = [];
        $token = (string)($_POST['t'] ?? $_GET['t'] ?? '');
        $row = preg_match('~^[a-f0-9]{64}$~', $token) ? DB::one('SELECT * FROM pp_tokens WHERE hash = ? AND used = 0 AND expires > ?', [hash('sha256', $token), time()]) : null;
        $tokenOk = (bool)$row;
        if (!$row) $error = 'Il link non è valido o è scaduto.';
        if ($row && $_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::checkCsrf();
            $pass = (string)self::post('password');
            if ($e = Auth::strong($pass)) $error = $e;
            else {
                DB::update('pp_users', ['pass_hash' => password_hash($pass, PASSWORD_DEFAULT)], 'id = ?', [$row['user_id']]);
                DB::run('UPDATE pp_tokens SET used = 1 WHERE user_id = ?', [$row['user_id']]);
                Auth::log('password reset', (string)$row['user_id']);
                // se c'è l'accesso in due passaggi, serve comunque il codice
                $u = DB::one('SELECT email FROM pp_users WHERE id = ?', [$row['user_id']]);
                $r = Auth::login($u['email'], $pass);
                PP::redirect(self::u($r === 'totp' ? 'login' : ''));
            }
        }
        require __DIR__ . '/admin/login.php';
    }

    private static function logout(): void
    {
        Auth::log('logout');
        Auth::logout();
        PP::redirect(self::u('login'));
    }

    /* ---------- cruscotto ---------- */

    private static function dashboard(): void
    {
        $matrix = [];
        foreach (I18n::others() as $l) $matrix[$l] = ['ok' => 0, 'auto' => 0, 'outdated' => 0, 'missing' => 0];
        $count = function (array $st) use (&$matrix) { foreach ($st as $l => $s) if (isset($matrix[$l][$s])) $matrix[$l][$s]++; };
        foreach (DB::all('SELECT id FROM pp_entries') as $r) $count(Content::statuses('entry', (int)$r['id']));
        foreach (DB::all('SELECT id FROM pp_docs') as $r) $count(Content::statuses('doc', (int)$r['id']));
        foreach (array_keys(I18n::defaults()) as $k) foreach (I18n::others() as $l) { $s = Content::stringStatus($k, $l); if (isset($matrix[$l][$s])) $matrix[$l][$s]++; }
        $recent = DB::all("SELECT e.id, e.type, e.updated_at, t.title FROM pp_entries e LEFT JOIN pp_entry_tr t ON t.entry_id = e.id AND t.lang = ? ORDER BY e.updated_at DESC LIMIT 8", [I18n::$master]);
        $counts = [];
        foreach (array_keys(PP::$config['types']) as $type) $counts[$type] = (int)DB::val('SELECT COUNT(*) FROM pp_entries WHERE type = ?', [$type]);
        $counts['docs'] = (int)DB::val('SELECT COUNT(*) FROM pp_docs');
        $log = DB::all('SELECT * FROM pp_log ORDER BY id DESC LIMIT 8');
        self::view('dashboard', ['title' => 'Panoramica', 'matrix' => $matrix, 'recent' => $recent, 'counts' => $counts, 'log' => $log]);
    }

    /* ---------- contenuti ---------- */

    private static function entries(string $type): void
    {
        if (!isset(PP::$config['types'][$type])) $type = 'page';
        $rows = DB::all("SELECT e.*, t.title, t.slug AS tslug FROM pp_entries e LEFT JOIN pp_entry_tr t ON t.entry_id = e.id AND t.lang = ? WHERE e.type = ? ORDER BY e.menu_order ASC, e.pub_date DESC, e.id DESC", [I18n::$master, $type]);
        foreach ($rows as &$r) $r['statuses'] = Content::statuses('entry', (int)$r['id']);
        self::view('entries', ['title' => PP::$config['types'][$type]['label'], 'type' => $type, 'rows' => $rows]);
    }

    /**
     * Campi personalizzati di un tipo, normalizzati. In config['types'][tipo]['fields'] si può scrivere
     * 'chiave' => 'Etichetta' (testo semplice) oppure
     * 'chiave' => ['label' => …, 'type' => text|textarea|number|date|url|select|image|checkbox|entry, 'options' => [...], 'translatable' => true, 'hint' => …]
     * I campi "translatable" si traducono come il resto del testo (pp_entry_tr.extra); gli altri valgono per tutte le lingue (meta).
     */
    public static function fields(string $type, ?array $row = null): array
    {
        $out = [];
        foreach (PP::$config['types'][$type]['fields'] ?? [] as $k => $f) {
            $f = is_array($f) ? $f : ['label' => $f];
            $out[$k] = $f + ['label' => $k, 'type' => 'text', 'options' => [], 'translatable' => false, 'hint' => ''];
        }
        // i campi aggiunti dai moduli (filtro 'fields') prendono gli stessi valori di partenza
        return array_map(fn($f) => (is_array($f) ? $f : ['label' => $f]) + ['type' => 'text', 'options' => [], 'translatable' => false, 'hint' => ''],
            Hooks::filter('fields', $out, $type, $row));
    }

    public static function fieldValue(array $f, $v)
    {
        $v = is_string($v) ? trim($v) : $v;
        return match ($f['type']) {
            'number' => $v === '' || $v === null ? '' : (string)(0 + str_replace(',', '.', (string)$v)),
            'checkbox' => $v ? '1' : '',
            'url' => preg_match('~^(https?://|mailto:|/)~i', (string)$v) ? (string)$v : ((string)$v !== '' ? 'https://' . $v : ''),
            'date' => preg_match('~^\d{4}-\d{2}-\d{2}$~', (string)$v) ? (string)$v : '',
            'select' => array_key_exists((string)$v, $f['options']) || in_array((string)$v, $f['options'], true) ? (string)$v : '',
            'entry' => (string)(int)$v ?: '',
            'color' => preg_match('~^#[0-9a-f]{6}$~i', (string)$v) ? strtolower((string)$v) : '',
            'image' => preg_match('~^uploads/[A-Za-z0-9/_.-]+$~', (string)$v) && !str_contains((string)$v, '..') ? (string)$v : '',
            default => (string)$v,
        };
    }

    private static function entry(string $id, string $arg): void
    {
        $isNew = $id === 'new';
        $me = Auth::user();
        if ($isNew) {
            $type = isset(PP::$config['types'][$arg]) ? $arg : 'page';
            $lang = I18n::$master;
        } else {
            $type = 'page';
            $lang = I18n::valid($arg) ? $arg : I18n::$master;
        }

        $row = $isNew ? ['id' => 0, 'type' => $type, 'slug' => '', 'template' => $type === 'block' ? 'text' : 'default', 'menu_order' => 0, 'in_menu' => 0,
            'status' => 'draft', 'pub_date' => date('Y-m-d'), 'image' => '', 'meta' => '{}', 'publish_at' => '', 'unpublish_at' => '', 'author_id' => $me['id']]
            : DB::one('SELECT * FROM pp_entries WHERE id = ?', [(int)$id]);
        if (!$row) { self::view('notfound', ['title' => 'Non trovato']); return; }
        $type = $row['type'];
        $isMaster = $lang === I18n::$master;

        // permessi
        if (Auth::role() === 'translator' && $isMaster) {
            $mine = array_values(array_filter(I18n::others(), [Auth::class, 'canLang']));
            if (!$isNew && $mine) PP::redirect(self::u('entry/' . $row['id'] . '/' . $mine[0]));
            self::view('error', ['title' => 'Permesso negato', 'message' => 'Come traduttore puoi lavorare solo sulle tue lingue.']); return;
        }
        if (!Auth::canLang($lang) || ($isMaster && !Auth::canEdit($row)) || (!$isMaster && !Auth::can('translate') && !Auth::canEdit($row))) {
            self::view('error', ['title' => 'Permesso negato', 'message' => Auth::role() === 'author' ? 'Gli autori possono modificare solo i propri contenuti finché sono in bozza.' : 'Non puoi modificare questo contenuto.']); return;
        }
        $canPublish = Auth::can('publish');
        if ($type === 'block' && class_exists('Blocks') && $_SERVER['REQUEST_METHOD'] === 'POST' && $isMaster && isset(Blocks::kinds()[(string)self::post('template')])) $row['template'] = (string)self::post('template');
        $fields = self::fields($type, $row);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = (string)self::post('action');
            if ($act === 'delete' && !$isNew && $isMaster) {
                if (!$canPublish && $row['status'] === 'published') PP::redirect($_SERVER['REQUEST_URI']);
                Content::deleteItem('entry', (int)$row['id']);
                Auth::log('delete entry', $type . ' #' . $row['id']);
                self::changed('entry');
                self::flash('Eliminato.');
                PP::redirect(self::u('entries/' . $type));
            }
            if ($act === 'duplicate' && !$isNew && Auth::can('content')) {
                $new = Content::duplicate((int)$row['id']);
                Auth::log('duplicate entry', $type . ' #' . $row['id'] . ' → #' . $new);
                self::flash('Copia creata in bozza, con tutte le traduzioni.');
                PP::redirect(self::u('entry/' . $new . '/' . I18n::$master));
            }
            if (self::post('restore') && !$isNew) {
                $rev = DB::one('SELECT * FROM pp_revisions WHERE id = ? AND kind = ? AND ref_id = ? AND lang = ?', [(int)self::post('restore'), 'entry', $row['id'], $lang]);
                if ($rev) {
                    Content::restore((int)$rev['id']);
                    Auth::log('restore revision', $type . ' #' . $row['id'] . ' ' . $lang);
                    self::changed('entry');
                    self::flash('Versione del ' . $rev['at'] . ' ripristinata. Quella che c’era prima è tra le versioni precedenti.');
                }
                PP::redirect(self::u('entry/' . $row['id'] . '/' . $lang));
            }

            // campi personalizzati
            $meta = Content::meta($row); $extra = [];
            foreach ($fields as $k => $f) {
                if ($f['translatable']) $extra[$k] = self::fieldValue($f, $_POST['x'][$k] ?? '');
                elseif ($isMaster) $meta[$k] = self::fieldValue($f, $_POST['meta'][$k] ?? '');
            }
            if (isset($meta['video'])) $meta['video'] = self::youtubeId((string)$meta['video']);
            $text = ['title' => self::post('title'), 'excerpt' => self::post('excerpt'), 'body' => self::post('body'),
                'image_alt' => self::post('image_alt'), 'slug' => Media::slug((string)self::post('slug')), 'extra' => $extra];

            if ($isMaster) {
                $title = trim((string)self::post('title'));
                if ($title === '') { self::flash('Il titolo è obbligatorio.', 'error'); PP::redirect($_SERVER['REQUEST_URI']); }
                $image = trim((string)self::post('image'));
                if (!empty($_FILES['image_file']['name'])) {
                    try { $image = Media::upload($_FILES['image_file'], 'media', Media::IMAGES); if (Media::$note) self::flash(Media::$note); }
                    catch (Throwable $e) { self::flash($e->getMessage(), 'error'); }
                }
                $dt = fn($v) => preg_match('~^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}~', (string)$v) ? str_replace('T', ' ', substr((string)$v, 0, 16)) . ':00' : '';
                $status = $canPublish ? (self::post('status') === 'published' ? 'published' : 'draft') : 'draft';
                $data = [
                    'template' => preg_replace('~[^a-z0-9_-]~', '', (string)self::post('template', 'default')) ?: 'default',
                    'menu_order' => (int)self::post('menu_order', 0), 'in_menu' => self::post('in_menu') ? 1 : 0,
                    'status' => $status, 'pub_date' => (string)self::post('pub_date', ''), 'image' => $image,
                    'publish_at' => $canPublish ? $dt(self::post('publish_at')) : $row['publish_at'],
                    'unpublish_at' => $canPublish ? $dt(self::post('unpublish_at')) : $row['unpublish_at'],
                    'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE), 'updated_at' => Content::now(),
                ];
                $wasLive = !$isNew && $row['status'] === 'published';
                if ($isNew) {
                    $data['slug'] = Content::uniqueSlug($type, I18n::$master, $text['slug'] ?: $title, 0);
                    $row['id'] = DB::insert('pp_entries', $data + ['type' => $type, 'created_at' => Content::now(), 'author_id' => (int)$me['id']]);
                } else {
                    DB::update('pp_entries', $data, 'id = ?', [$row['id']]);
                }
                // il redirect automatico serve solo se la pagina era già pubblica
                if (!$wasLive) DB::update('pp_entries', ['status' => 'draft'], 'id = ?', [$row['id']]);
                Content::saveText('entry', (int)$row['id'], $lang, $text);
                if (!$wasLive) DB::update('pp_entries', ['status' => $status], 'id = ?', [$row['id']]);
                if ($row['slug'] !== 'home') {
                    $s = (string)DB::val('SELECT slug FROM pp_entry_tr WHERE entry_id = ? AND lang = ?', [$row['id'], $lang]);
                    if ($s !== '') DB::update('pp_entries', ['slug' => $s], 'id = ?', [$row['id']]);
                }
                Auth::log('save entry', $type . ' #' . $row['id']);
                self::changed('entry');
                self::flash($canPublish ? 'Salvato. Le traduzioni che non corrispondono più al testo sono segnate come “Da aggiornare”.' : 'Salvato in bozza. Un redattore lo pubblicherà.');
                $q = $act === 'save_translate' ? '?translate=all' : '';
                PP::redirect(self::u('entry/' . $row['id'] . '/' . $lang) . $q);
            } else {
                Content::saveText('entry', (int)$row['id'], $lang, $text, false);
                Auth::log('save translation', $type . ' #' . $row['id'] . ' ' . $lang);
                self::changed('entry');
                self::flash('Traduzione ' . I18n::name($lang) . ' salvata come rivista.');
                PP::redirect(self::u('entry/' . $row['id'] . '/' . $lang));
            }
        }

        $tr = $isNew ? null : Content::tr('entry', (int)$row['id'], $lang);
        $master = $isNew ? null : Content::tr('entry', (int)$row['id'], I18n::$master);
        $statuses = $isNew ? [] : Content::statuses('entry', (int)$row['id']);
        $skip = ['layout', 'home', 'news', 'news-item', 'stories', 'story', 'results', 'partners', '404', 'page', 'search'];
        $templates = [];
        foreach ([PP_ROOT . '/pp-themes/_base/templates', PP_ROOT . '/pp-themes/' . PP::theme() . '/templates', PP_SITE . '/theme/templates'] as $d) {
            foreach (glob($d . '/*.php') ?: [] as $f) { $t = basename($f, '.php'); if ($t[0] !== '_' && !in_array($t, $skip, true)) $templates[$t] = $t; }
        }
        $localized = $isNew ? null : Content::localize('entry', $row, $lang);
        self::view('entry', [
            'title' => $isNew ? 'Nuovo: ' . PP::$config['types'][$type]['one'] : ($master['title'] ?? 'Senza titolo'),
            'row' => $row, 'type' => $type, 'lang' => $lang, 'tr' => $tr, 'master' => $master, 'statuses' => $statuses,
            'meta' => Content::meta($row), 'isNew' => $isNew, 'templates' => array_values($templates), 'fields' => $fields,
            'x' => json_decode((string)($tr['extra'] ?? ''), true) ?: [], 'xMaster' => json_decode((string)($master['extra'] ?? ''), true) ?: [],
            'publicUrl' => $localized ? PP::entryUrl($localized, $lang) : '',
            'previewUrl' => $localized && PP::entryUrl($localized, $lang) ? self::u('preview') . '?path=' . rawurlencode(substr(PP::entryUrl($localized, $lang), strlen(PP::base()))) : '',
            'revisions' => $isNew ? [] : Content::revisions('entry', (int)$row['id'], $lang),
            'canPublish' => $canPublish, 'author' => $row['author_id'] ? DB::val('SELECT email FROM pp_users WHERE id = ?', [$row['author_id']]) : '',
            'autoTranslate' => ($_GET['translate'] ?? '') === 'all',
        ]);
    }

    /** Anteprima di una pagina pubblica com'è adesso (anche bozze e contenuti programmati), con un tema a scelta. */
    private static function preview(): void
    {
        $path = '/' . trim((string)($_GET['path'] ?? '/' . I18n::$master . '/'), '/');
        Content::$preview = true;
        if (!empty($_GET['theme']) && isset(PP::themes()[$_GET['theme']])) PP::$theme = (string)$_GET['theme'];
        // contenuti dimostrativi al posto di quelli del sito (database a parte: il sito non cambia)
        $demo = !empty($_GET['demo']) && class_exists('Demo');
        if ($demo) Demo::use();
        $seg = array_values(array_filter(explode('/', $path), 'strlen'));
        // Modifica visuale: le fasce della home si possono cliccare
        $edit = !empty($_GET['edit']) && Auth::can('publish') && class_exists('Blocks');
        if ($edit) Blocks::$edit = true;
        $page = PP::page($seg ?: [I18n::$master]);
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https://i.ytimg.com; frame-src https://www.youtube-nocookie.com https://player.vimeo.com; style-src 'self' 'unsafe-inline'; script-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
        $html = $page['html'];
        // "con i colori del tema": la palette e i font suggeriti sostituiscono per un momento quelli dell'identità
        if (PP::$theme && !empty($_GET['palette']) && class_exists('Identity')) {
            $html = str_replace('</head>', '<style>' . Identity::css(PP::themes()[PP::$theme]) . '</style></head>', $html);
        }
        if ($edit) {
            if (class_exists('Layouts') && Layouts::$type !== '') $html = preg_replace('~<html\b~', '<html data-pp-type="' . e(Layouts::$type) . '" data-pp-entry="' . (int)Layouts::$entry . '"', $html, 1);
            $tools = '<link rel="stylesheet" href="' . e(PP::url('pp-admin/visual-frame.css')) . '?v=' . PP::VERSION . '">'
                . '<script src="' . e(PP::url('pp-admin/visual-frame.js')) . '?v=' . PP::VERSION . '" defer></script>';
            echo str_replace('</head>', $tools . '</head>', $html);
            return;
        }
        if ($demo) $html = Demo::links($html, http_build_query(array_filter(['demo' => 1, 'theme' => PP::$theme, 'palette' => $_GET['palette'] ?? ''])));
        $bar = '<div role="status" style="position:sticky;top:0;z-index:9999;background:#111;color:#fff;font:600 13px/1 system-ui,sans-serif;padding:8px 16px;text-align:center">Anteprima' . (PP::$theme ? ' · tema ' . e(PP::themes()[PP::$theme]['name'] ?? PP::$theme) : '') . ($demo ? ' · contenuti dimostrativi' : '') . ' · non è il sito pubblico</div>';
        echo preg_replace('~<body([^>]*)>~', '<body$1>' . $bar, $html, 1);
    }

    private static function langList($v): string
    {
        $l = is_array($v) ? $v : preg_split('~[\s,;]+~', strtolower((string)$v));
        return implode(',', array_values(array_filter($l, fn($x) => I18n::valid((string)$x) && $x !== I18n::$master)));
    }

    public static function youtubeId(string $v): string
    {
        if (preg_match('~(?:youtu\.be/|v=|embed/|shorts/)([A-Za-z0-9_-]{6,20})~', $v, $m)) return $m[1];
        return preg_match('~^[A-Za-z0-9_-]{6,20}$~', $v) ? $v : '';
    }

    /* ---------- documenti ---------- */

    private static function docs(): void
    {
        $rows = DB::all('SELECT d.*, t.title FROM pp_docs d LEFT JOIN pp_doc_tr t ON t.doc_id = d.id AND t.lang = ? ORDER BY d.sort ASC, d.pub_date DESC, d.id DESC', [I18n::$master]);
        foreach ($rows as &$r) {
            $r['statuses'] = Content::statuses('doc', (int)$r['id']);
            $r['files'] = array_column(DB::all('SELECT lang FROM pp_doc_files WHERE doc_id = ?', [$r['id']]), 'lang');
        }
        self::view('docs', ['title' => 'Documenti', 'rows' => $rows]);
    }

    private static function doc(string $id, string $lang): void
    {
        if (!I18n::valid($lang)) $lang = I18n::$master;
        $isNew = $id === 'new';
        $row = $isNew ? ['id' => 0, 'code' => '', 'category' => 'deliverable', 'wp' => '', 'pub_date' => date('Y-m-d'), 'file' => '', 'status' => 'draft', 'sort' => 0]
            : DB::one('SELECT * FROM pp_docs WHERE id = ?', [(int)$id]);
        if (!$row) { self::view('notfound', ['title' => 'Non trovato']); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = (string)self::post('action');
            if ($act === 'delete' && !$isNew) {
                Content::deleteItem('doc', (int)$row['id']);
                Auth::log('delete doc', (string)$row['id']);
                self::changed('doc');
                self::flash('Documento eliminato (i file restano nella libreria).');
                PP::redirect(self::u('docs'));
            }
            if ($act === 'remove_lang_file' && !$isNew) {
                DB::run('DELETE FROM pp_doc_files WHERE doc_id = ? AND lang = ?', [$row['id'], $lang]);
                self::flash('Versione ' . I18n::name($lang) . ' rimossa: in questa lingua si scarica di nuovo il file comune.');
                PP::redirect(self::u('doc/' . $row['id'] . '/' . $lang));
            }
            if ($lang === I18n::$master) {
                if (trim((string)self::post('title')) === '') { self::flash('Il titolo è obbligatorio.', 'error'); PP::redirect($_SERVER['REQUEST_URI']); }
                $data = [
                    'code' => trim((string)self::post('code')),
                    'category' => array_key_exists(self::post('category'), PP::$config['doc_categories']) ? self::post('category') : 'deliverable',
                    'wp' => trim((string)self::post('wp')), 'pub_date' => (string)self::post('pub_date'),
                    'status' => self::post('status') === 'published' ? 'published' : 'draft',
                    'sort' => (int)self::post('sort', 0), 'updated_at' => Content::now(),
                ];
                if (!empty($_FILES['file']['name'])) {
                    try {
                        $data['file'] = Media::upload($_FILES['file'], 'docs', Media::FILES);
                        if (Media::$note) self::flash(Media::$note, str_starts_with(Media::$note, 'Attenzione') ? 'error' : 'ok');
                        if (self::post('replace_all')) DB::run('DELETE FROM pp_doc_files WHERE doc_id = ?', [$row['id']]);
                    } catch (Throwable $e) { self::flash($e->getMessage(), 'error'); }
                }
                if ($isNew) $row['id'] = DB::insert('pp_docs', $data + ['created_at' => Content::now()]);
                else DB::update('pp_docs', $data, 'id = ?', [$row['id']]);
                Content::saveText('doc', (int)$row['id'], $lang, $_POST);
                Auth::log('save doc', '#' . $row['id']);
                self::changed('doc');
                self::flash(!empty($data['file']) ? 'File sostituito: il nuovo file vale in tutte le lingue che non hanno una versione propria. Il link pubblico non cambia.' : 'Documento salvato.');
                $q = $act === 'save_translate' ? '?translate=all' : '';
                PP::redirect(self::u('doc/' . $row['id'] . '/' . $lang) . $q);
            } else {
                if (!empty($_FILES['lang_file']['name'])) {
                    try {
                        $f = Media::upload($_FILES['lang_file'], 'docs', Media::FILES);
                        if (Media::$note) self::flash(Media::$note, str_starts_with(Media::$note, 'Attenzione') ? 'error' : 'ok');
                        DB::upsert('pp_doc_files', ['doc_id' => $row['id'], 'lang' => $lang, 'file' => $f], ['doc_id', 'lang']);
                    } catch (Throwable $e) { self::flash($e->getMessage(), 'error'); }
                }
                Content::saveText('doc', (int)$row['id'], $lang, $_POST, false);
                self::changed('doc');
                self::flash('Versione ' . I18n::name($lang) . ' salvata.');
                PP::redirect(self::u('doc/' . $row['id'] . '/' . $lang));
            }
        }

        $files = [];
        if (!$isNew) foreach (DB::all('SELECT * FROM pp_doc_files WHERE doc_id = ?', [$row['id']]) as $f) $files[$f['lang']] = $f['file'];
        self::view('doc', [
            'title' => $isNew ? 'Nuovo documento' : (Content::tr('doc', (int)$row['id'], I18n::$master)['title'] ?? 'Documento'),
            'row' => $row, 'lang' => $lang, 'isNew' => $isNew, 'files' => $files,
            'tr' => $isNew ? null : Content::tr('doc', (int)$row['id'], $lang),
            'master' => $isNew ? null : Content::tr('doc', (int)$row['id'], I18n::$master),
            'statuses' => $isNew ? [] : Content::statuses('doc', (int)$row['id']),
            'autoTranslate' => ($_GET['translate'] ?? '') === 'all',
        ]);
    }

    /* ---------- testi del sito ---------- */

    private static function strings(string $lang): void
    {
        if (!I18n::valid($lang)) $lang = I18n::$master;
        $defs = I18n::defaults();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $vals = $_POST['s'] ?? [];
            $orig = $_POST['o'] ?? [];
            $markAll = self::post('action') === 'review_all';
            $n = 0;
            foreach ($vals as $k => $v) {
                if (!isset($defs[$k])) continue;
                $changed = (string)$v !== (string)($orig[$k] ?? '');
                if ($changed || ($markAll && trim((string)$v) !== '')) { Content::saveString($k, $lang, (string)$v, false); $n++; }
            }
            Auth::log('save strings', $lang);
            if ($n) self::changed('strings');
            self::flash($n ? "$n testi salvati." : 'Nessuna modifica.');
            PP::redirect(self::u('strings/' . $lang));
        }
        $rows = [];
        foreach ($defs as $k => $d) {
            $cur = DB::one('SELECT * FROM pp_strings WHERE skey = ? AND lang = ?', [$k, $lang]);
            $mas = DB::val('SELECT value FROM pp_strings WHERE skey = ? AND lang = ?', [$k, I18n::$master]);
            $rows[$d['group'] ?? 'Altro'][$k] = [
                'value' => $cur['value'] ?? '', 'master' => (string)$mas, 'note' => $d['note'] ?? '', 'html' => !empty($d['html']), 'block' => ($d['html'] ?? '') === 'block',
                'status' => Content::stringStatus($k, $lang), 'long' => mb_strlen((string)$mas) > 90,
            ];
        }
        self::view('strings', ['title' => 'Testi del sito', 'lang' => $lang, 'groups' => $rows]);
    }

    /* ---------- media ---------- */

    private static function media(): void
    {
        $file = (string)($_GET['file'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = (string)self::post('action');
            $path = (string)self::post('path');
            try {
                if ($act === 'delete') {
                    if (!Auth::can('site')) throw new RuntimeException('Solo i redattori possono eliminare i file.');
                    Media::delete($path);
                    Auth::log('delete media', $path);
                    self::flash('File eliminato.');
                    PP::redirect(self::u('media'));
                }
                if ($act === 'placeholders' && class_exists('Demo')) {
                    if (!Auth::can('site')) throw new RuntimeException('Solo i redattori possono mettere le foto segnaposto.');
                    $n = Demo::placeholders();
                    Auth::log('placeholders', (string)$n);
                    if ($n) self::changed('media');
                    self::flash($n ? "Messe $n foto segnaposto, con le misure stampate sopra. Si sostituiscono una alla volta qui sotto (nome che inizia con “segnaposto”) con Sostituisci." : 'Nessun posto vuoto: apertura, news, storie e gallerie hanno già le loro foto.');
                    PP::redirect(self::u('media') . '?q=segnaposto');
                }
                if ($act === 'replace') {
                    if (!Auth::can('site')) throw new RuntimeException('Solo i redattori possono sostituire i file.');
                    Media::replace($path, $_FILES['file'] ?? []);
                    Auth::log('replace media', $path);
                    self::changed('media');
                    self::flash('File sostituito: tutte le pagine che lo usano mostrano il nuovo. ' . Media::$note);
                    PP::redirect(self::u('media') . '?file=' . rawurlencode($path));
                }
            } catch (Throwable $e) {
                self::flash($e->getMessage(), 'error');
                PP::redirect(self::u('media') . ($path ? '?file=' . rawurlencode($path) : ''));
            }
            if (!empty($_FILES['files']['name'][0])) {
                $ok = 0;
                foreach ($_FILES['files']['name'] as $i => $name) {
                    $f = ['name' => $name, 'type' => $_FILES['files']['type'][$i], 'tmp_name' => $_FILES['files']['tmp_name'][$i], 'error' => $_FILES['files']['error'][$i], 'size' => $_FILES['files']['size'][$i]];
                    try { Media::upload($f, 'media'); $ok++; if (Media::$note) self::flash($name . ': ' . Media::$note); } catch (Throwable $e) { self::flash($name . ': ' . $e->getMessage(), 'error'); }
                }
                if ($ok) self::flash("$ok file caricati.");
            }
            PP::redirect(self::u('media'));
        }
        if ($file !== '') {
            try { $file = Media::safePath($file); } catch (Throwable $e) { self::view('notfound', ['title' => 'File non trovato']); return; }
            $abs = PP_ROOT . '/' . $file;
            $img = in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), Media::IMAGES, true);
            $dim = $img ? @getimagesize($abs) : false;
            self::view('media-file', ['title' => basename($file), 'file' => $file, 'img' => $img, 'w' => $dim[0] ?? 0, 'h' => $dim[1] ?? 0,
                'size' => filesize($abs), 'mtime' => filemtime($abs), 'usage' => Media::usage($file)]);
            return;
        }
        $all = Media::library('media');
        $q = mb_strtolower(trim((string)($_GET['q'] ?? '')));
        $type = (string)($_GET['type'] ?? '');
        $ym = preg_match('~^\d{4}/\d{2}$~', (string)($_GET['ym'] ?? '')) ? $_GET['ym'] : '';
        $kind = fn($it) => $it['image'] || str_ends_with($it['name'], '.svg') ? 'image' : (str_ends_with($it['name'], '.pdf') ? 'pdf' : (preg_match('~\.(docx?|odt|pptx?|xlsx?|ods|csv|txt)$~', $it['name']) ? 'office' : 'other'));
        $months = [];
        foreach ($all as $it) if (preg_match('~uploads/media/(\d{4}/\d{2})/~', $it['path'], $m)) $months[$m[1]] = ($months[$m[1]] ?? 0) + 1;
        krsort($months);
        $items = array_values(array_filter($all, fn($it) => ($q === '' || str_contains(mb_strtolower($it['name']), $q))
            && ($type === '' || $kind($it) === $type) && ($ym === '' || str_contains($it['path'], '/' . $ym . '/'))));
        $pg = self::pager($items, 48);
        self::view('media', ['title' => 'Immagini e file', 'items' => $pg['items'], 'found' => count($items), 'pager' => $pg['html'], 'total' => count($all), 'months' => $months, 'q' => $q, 'type' => $type, 'ym' => $ym]);
    }

    /* ---------- coda di traduzione ---------- */

    private static function translatePage(): void
    {
        self::view('translate', ['title' => 'Traduzioni', 'enabled' => Translator::enabled()]);
    }

    private static function api(string $what): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') self::json(['error' => 'POST richiesto'], 405);
        if ($what === 'jobs') {
            $lang = (string)self::post('lang');
            $jobs = Content::pending(I18n::valid($lang) ? $lang : null, (bool)self::post('include_auto'));
            if (self::post('kind') && self::post('id')) {
                $jobs = array_values(array_filter($jobs, fn($j) => $j['kind'] === self::post('kind') && (string)$j['id'] === (string)self::post('id')));
                if (self::post('force')) {
                    $jobs = [];
                    foreach (I18n::others() as $l) $jobs[] = ['kind' => self::post('kind'), 'id' => self::post('id'), 'lang' => $l, 'label' => I18n::name($l)];
                }
            }
            self::json(['jobs' => $jobs]);
        }
        if ($what === 'translate') {
            if (!Translator::enabled()) self::json(['error' => 'Traduzione automatica non configurata: inserisci la chiave API in site/config.php'], 400);
            @set_time_limit(180);
            try {
                $st = Translator::job((string)self::post('kind'), (string)self::post('id'), (string)self::post('lang'));
                Auth::log('auto translate', self::post('kind') . ' ' . self::post('id') . ' ' . self::post('lang'));
                if (self::post('last')) self::changed('translate');
                self::json(['ok' => true, 'status' => $st, 'label' => I18n::statusLabel($st)]);
            } catch (Throwable $e) {
                self::json(['error' => $e->getMessage()], 500);
            }
        }
        if ($what === 'media') self::json(['items' => Media::library('media')]);
        if ($what === 'docs') {
            $rows = DB::all('SELECT d.id, d.code, t.title FROM pp_docs d LEFT JOIN pp_doc_tr t ON t.doc_id = d.id AND t.lang = ? ORDER BY d.sort, d.id DESC', [I18n::$master]);
            self::json(['items' => $rows]);
        }
        self::json(['error' => 'Sconosciuto'], 404);
    }

    /* ---------- utenti ---------- */

    private static function users(): void
    {
        $me = Auth::user();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = self::post('action');
            if ($act === 'totp_start') {
                $_SESSION['totp_new'] = Totp::secret();
                PP::redirect(self::u('users') . '#totp');
            } elseif ($act === 'totp_confirm') {
                $sec = (string)($_SESSION['totp_new'] ?? '');
                if ($sec && Totp::check($sec, (string)self::post('code'))) {
                    DB::update('pp_users', ['totp_secret' => $sec], 'id = ?', [$me['id']]);
                    unset($_SESSION['totp_new']);
                    Auth::log('2fa on');
                    self::flash('Accesso in due passaggi attivo. Da ora, dopo la password, servirà il codice dell’app.');
                } else self::flash('Codice non valido: controlla l’ora del telefono e riprova.', 'error');
            } elseif ($act === 'totp_off') {
                $u = DB::one('SELECT * FROM pp_users WHERE id = ?', [$me['id']]);
                if (password_verify((string)self::post('current'), $u['pass_hash'])) { DB::update('pp_users', ['totp_secret' => ''], 'id = ?', [$me['id']]); Auth::log('2fa off'); self::flash('Accesso in due passaggi disattivato.'); }
                else self::flash('Password errata.', 'error');
            } elseif ($act === 'totp_reset' && Auth::isAdmin() && (int)self::post('id') !== (int)$me['id']) {
                DB::update('pp_users', ['totp_secret' => ''], 'id = ?', [(int)self::post('id')]);
                self::flash('Accesso in due passaggi azzerato per l’utente: lo riattiverà al prossimo accesso.');
            } elseif ($act === 'password') {
                $u = DB::one('SELECT * FROM pp_users WHERE id = ?', [$me['id']]);
                if (!password_verify((string)self::post('current'), $u['pass_hash'])) self::flash('Password attuale errata.', 'error');
                elseif ($err = Auth::strong((string)self::post('new'))) self::flash($err, 'error');
                else { DB::update('pp_users', ['pass_hash' => password_hash((string)self::post('new'), PASSWORD_DEFAULT)], 'id = ?', [$me['id']]); self::flash('Password aggiornata.'); }
            } elseif ($act === 'add' && Auth::isAdmin()) {
                $email = strtolower(trim((string)self::post('email')));
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) self::flash('Email non valida.', 'error');
                elseif ($err = Auth::strong((string)self::post('password'))) self::flash($err, 'error');
                elseif (DB::val('SELECT id FROM pp_users WHERE email = ?', [$email])) self::flash('Utente già presente.', 'error');
                else {
                    DB::insert('pp_users', ['email' => $email, 'name' => trim((string)self::post('name')), 'pass_hash' => password_hash((string)self::post('password'), PASSWORD_DEFAULT),
                        'role' => isset(Auth::ROLES[self::post('role')]) ? self::post('role') : 'editor', 'langs' => self::langList(self::post('langs', [])), 'created_at' => Content::now()]);
                    Auth::log('add user', $email);
                    self::flash('Utente creato.');
                }
            } elseif ($act === 'role' && Auth::isAdmin() && (int)self::post('id') !== (int)$me['id'] && isset(Auth::ROLES[self::post('role')])) {
                DB::update('pp_users', ['role' => self::post('role'), 'langs' => self::langList(self::post('langs', ''))], 'id = ?', [(int)self::post('id')]);
                Auth::log('change role', (string)self::post('id') . ' ' . self::post('role'));
                self::flash('Ruolo aggiornato.');
            } elseif ($act === 'remove' && Auth::isAdmin() && (int)self::post('id') !== (int)$me['id']) {
                DB::run('DELETE FROM pp_users WHERE id = ?', [(int)self::post('id')]);
                self::flash('Utente rimosso.');
            }
            PP::redirect(self::u('users'));
        }
        $myTotp = (bool)DB::val("SELECT totp_secret <> '' FROM pp_users WHERE id = ?", [$me['id']]);
        self::view('users', ['title' => 'Utenti', 'users' => DB::all("SELECT id, email, name, role, langs, created_at, totp_secret <> '' AS has2fa FROM pp_users ORDER BY id"), 'me' => $me,
            'myTotp' => $myTotp, 'newSecret' => $_SESSION['totp_new'] ?? '']);
    }
}
