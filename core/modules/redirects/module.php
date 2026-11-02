<?php
/**
 * Redirect 301/302. Quelli automatici nascono quando cambia l'indirizzo di un contenuto pubblicato.
 * Funzionano anche col sito statico: il server passa a PHP solo gli indirizzi che non esistono.
 */
return [
    'name' => 'redirects',
    'label' => 'Redirect',
    'version' => '1.0.0',
    'boot' => function () {
        Hooks::on('admin.pages', fn($p) => $p + [
            'redirects' => ['label' => 'Redirect', 'group' => 'Sito', 'perm' => 'site', 'handler' => function ($seg) {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $act = Admin::post('action');
                    if ($act === 'add') {
                        $from = trim((string)Admin::post('from')); $to = trim((string)Admin::post('to'));
                        $strip = fn($u) => preg_replace('~^' . preg_quote(PP::origin(), '~') . '~', '', preg_replace('~^' . preg_quote(PP::base(), '~') . '(?=/)~', '', $u));
                        $from = $strip($from); $to = preg_match('~^https?://~', $to) && !str_starts_with($to, PP::origin()) ? $to : $strip($to);
                        if ($from === '' || $to === '' || !preg_match('~^/~', $from)) Admin::flash('Scrivi l’indirizzo vecchio che inizia con / e quello nuovo.', 'error');
                        else { Redirects::add($from, $to, (int)Admin::post('code', 301)); Admin::flash('Redirect aggiunto.'); Auth::log('add redirect', $from); }
                    } elseif ($act === 'delete') {
                        DB::run('DELETE FROM pp_redirects WHERE id = ?', [(int)Admin::post('id')]);
                        Admin::flash('Redirect eliminato.');
                    }
                    $back = array_intersect_key($_GET, array_flip(['q', 'kind', 'p', 'all']));
                    PP::redirect(Admin::u('redirects') . ($back ? '?' . http_build_query($back) : ''));
                }
                $all = DB::all('SELECT * FROM pp_redirects ORDER BY id DESC');
                $q = mb_strtolower(trim((string)($_GET['q'] ?? '')));
                $kind = (string)($_GET['kind'] ?? '');
                $sel = array_values(array_filter($all, fn($r) => ($q === '' || str_contains(mb_strtolower($r['from_path'] . ' ' . $r['to_path']), $q))
                    && ($kind === '' || ($kind === 'auto' ? (int)$r['auto'] === 1 : ($kind === 'manual' ? (int)$r['auto'] === 0 : ($kind === 'unused' ? (int)$r['hits'] === 0 : (int)$r['hits'] > 0))))));
                $pg = Admin::pager($sel, 50);
                Admin::view(__DIR__ . '/admin.php', ['title' => 'Redirect', 'rows' => $pg['items'], 'total' => count($all), 'found' => count($sel), 'pager' => $pg['html'], 'q' => $q, 'kind' => $kind]);
            }],
        ]);
    },
];
