<?php
/** Registro delle attività: chi ha fatto cosa e quando (accessi, salvataggi, eliminazioni, traduzioni). */
return [
    'name' => 'activity',
    'label' => 'Registro attività',
    'version' => '1.0.0',
    'boot' => function () {
        Hooks::on('admin.pages', fn($p) => $p + [
            'log' => ['label' => 'Registro attività', 'group' => 'Account', 'perm' => 'admin', 'handler' => function ($seg) {
                $user = (string)($_GET['user'] ?? '');
                $act = (string)($_GET['action'] ?? '');
                $page = max(1, (int)($_GET['p'] ?? 1));
                $per = 100;
                $where = []; $args = [];
                if ($user !== '') { $where[] = 'user_email = ?'; $args[] = $user; }
                if ($act !== '') { $where[] = 'action = ?'; $args[] = $act; }
                $w = $where ? ' WHERE ' . implode(' AND ', $where) : '';
                $total = (int)DB::val('SELECT COUNT(*) FROM pp_log' . $w, $args);
                $rows = DB::all('SELECT * FROM pp_log' . $w . ' ORDER BY id DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per), $args);
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && Admin::post('action') === 'purge') {
                    DB::run('DELETE FROM pp_log WHERE at < ?', [date('Y-m-d H:i:s', strtotime('-24 months'))]);
                    Admin::flash('Voci più vecchie di 24 mesi eliminate.');
                    PP::redirect(Admin::u('log'));
                }
                Admin::view(__DIR__ . '/admin.php', ['title' => 'Registro attività', 'rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => (int)ceil($total / $per),
                    'users' => array_column(DB::all('SELECT DISTINCT user_email FROM pp_log ORDER BY user_email'), 'user_email'),
                    'actions' => array_column(DB::all('SELECT DISTINCT action FROM pp_log ORDER BY action'), 'action'), 'user' => $user, 'act' => $act]);
            }],
        ]);
    },
];
