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
                    PP::redirect(Admin::u('redirects'));
                }
                Admin::view(__DIR__ . '/admin.php', ['title' => 'Redirect', 'rows' => DB::all('SELECT * FROM pp_redirects ORDER BY id DESC')]);
            }],
        ]);
    },
];
