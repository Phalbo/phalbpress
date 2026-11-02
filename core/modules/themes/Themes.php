<?php
final class Themes
{
    public static function admin(): void
    {
        $themes = PP::themes();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = Admin::post('action');
            $slug = (string)Admin::post('theme');
            if ($act === 'activate' && isset($themes[$slug])) {
                Settings::set('theme', $slug);
                $t = $themes[$slug];
                if (Admin::post('apply') && class_exists('Identity')) {
                    Identity::apply($t['palette'] ?? [], $t['fonts']['display'] ?? null, $t['fonts']['text'] ?? null, $t['buttons'] ?? null);
                    Admin::flash('Applicati anche colori, font e pulsanti suggeriti. Quelli di prima si possono ripristinare qui sotto.');
                }
                Auth::log('activate theme', $slug);
                Admin::changed('theme');
                Admin::flash('Tema “' . ($t['name'] ?? $slug) . '” attivo. I contenuti non cambiano.');
            } elseif ($act === 'proofs' && Auth::isAdmin()) {
                $only = array_values(array_intersect(array_keys($themes), (array)($_POST['only'] ?? [])));
                $r = Proofs::build(Admin::post('colors') !== 'site', $only);
                Auth::log('proofs', count($r['themes']) . ' temi');
                Admin::flash('Provini pronti: ' . count($r['themes']) . ' temi, ' . $r['files'] . ' pagine in ' . $r['seconds'] . ' secondi.');
            } elseif ($act === 'proofs_clear' && Auth::isAdmin()) {
                Proofs::clear();
                Admin::flash('Provini cancellati.');
            } elseif ($act === 'restore_identity' && ($prev = Settings::get('identity_previous'))) {
                Identity::apply($prev['colors'] ?? [], $prev['font_display'] ?? null, $prev['font_text'] ?? null, $prev['buttons'] ?? null);
                Settings::set('identity_previous', null);
                Admin::changed('identity');
                Admin::flash('Colori e font di prima ripristinati.');
            }
            PP::redirect(Admin::u('themes'));
        }
        Admin::view(__DIR__ . '/admin.php', ['title' => 'Temi', 'themes' => $themes, 'active' => PP::theme(), 'fonts' => class_exists('Identity') ? Identity::fonts() : [],
            'prev' => Settings::get('identity_previous'), 'blocks' => (int)DB::val("SELECT COUNT(*) FROM pp_entries WHERE type = 'block'"), 'proofs' => Proofs::info()]);
    }
}
