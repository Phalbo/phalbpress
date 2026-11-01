<?php
/**
 * Importa da WordPress: contenuti, traduzioni WPML, categorie, foto e file, menu, SEO di Yoast e redirect dagli indirizzi vecchi.
 * I file si mettono in data/wp-import/ (esportazione .xml, dump .sql facoltativo, zip di uploads facoltativo);
 * si fa la prova a secco, poi l'importazione a passi (anche da riga di comando: php cron.php wpimport).
 */
require_once __DIR__ . '/WpSource.php';
require_once __DIR__ . '/WpContent.php';
require_once __DIR__ . '/WpImport.php';

return [
    'name' => 'wpimport',
    'label' => 'Importa da WordPress',
    'version' => '1.0.0',
    'migrate' => fn() => WpImport::migrate(),
    'boot' => function () {
        Hooks::on('admin.pages', fn($p) => $p + [
            'wpimport' => ['label' => 'Importa da WordPress', 'group' => 'Sito', 'admin_only' => true, 'handler' => function ($seg) {
                $planFile = WpSource::dir() . '/plan.json';
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $act = (string)Admin::post('action');
                    try {
                        if ($act === 'plan') {
                            WpImport::reset();
                            WpSource::scan();
                            file_put_contents($planFile, json_encode(WpImport::plan(), JSON_UNESCAPED_UNICODE));
                            Admin::flash('Prova a secco fatta: controlla il resoconto prima di importare.');
                        } elseif ($act === 'run') {
                            $s = WpImport::run(20);
                            if ($s['step'] === 'done') { Admin::flash('Importazione finita.'); Auth::log('wp import', 'fine'); }
                            else { Admin::view(__DIR__ . '/admin.php', ['title' => 'Importa da WordPress', 'files' => WpSource::files(), 'state' => $s, 'plan' => null, 'running' => true]); return; }
                        } elseif ($act === 'reset') {
                            WpImport::reset(); @unlink($planFile);
                            Admin::flash('Si ricomincia da capo (i contenuti già importati restano e verranno aggiornati).');
                        } elseif ($act === 'undo') {
                            $n = WpImport::undo();
                            Admin::changed('import');
                            Admin::flash('Tolti ' . $n . ' contenuti importati e i loro file.');
                            Auth::log('wp import undo', (string)$n);
                        }
                    } catch (Throwable $e) {
                        Admin::flash($e->getMessage(), 'error');
                    }
                    PP::redirect(Admin::u('wpimport'));
                }
                $plan = is_file($planFile) ? json_decode((string)file_get_contents($planFile), true) : null;
                Admin::view(__DIR__ . '/admin.php', ['title' => 'Importa da WordPress', 'files' => WpSource::files(), 'state' => WpImport::state(), 'plan' => $plan, 'running' => false]);
            }],
        ]);
        // da riga di comando: php cron.php wpimport [plan|run|reset|undo]
        Hooks::on('cli', function (array $args) {
            if (($args[0] ?? '') !== 'wpimport') return null;
            $what = $args[1] ?? 'run';
            if ($what === 'plan') { WpImport::reset(); WpSource::scan(); echo json_encode(WpImport::plan(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), PHP_EOL; return true; }
            if ($what === 'reset') { WpImport::reset(); echo "Stato azzerato.\n"; return true; }
            if ($what === 'undo') { echo 'Tolti ' . WpImport::undo() . " contenuti.\n"; return true; }
            do {
                $s = WpImport::run(60);
                echo date('H:i:s') . ' ' . WpImport::label($s['step']) . ' ' . (int)$s['cursor'] . '/' . (int)($s['total'] ?? 0) . ' ' . json_encode($s['counts']) . PHP_EOL;
            } while ($s['step'] !== 'done');
            foreach ($s['notes'] as $k => $list) echo $k . ': ' . count($list) . PHP_EOL;
            return true;
        });
    },
];
