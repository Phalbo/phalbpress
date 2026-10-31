<?php
final class SiteSettings
{
    /** Chiavi della configurazione che si possono cambiare dalla gestione (salvate in pp_settings, chiave "site_config"). */
    public const KEYS = ['contact_email', 'mail_from', 'social', 'funder', 'translation'];

    /** Le impostazioni salvate vincono su site/config.php. Si chiama dopo la connessione al database. */
    public static function apply(): void
    {
        $s = Settings::get('site_config', []);
        if (!is_array($s)) return;
        foreach (self::KEYS as $k) {
            if (!array_key_exists($k, $s)) continue;
            $v = $s[$k];
            if ($k === 'translation') PP::$config['translation'] = array_replace(PP::$config['translation'] ?? [], array_filter((array)$v, fn($x) => $x !== '' && $x !== null && $x !== []));
            elseif ($v !== '' && $v !== null) PP::$config[$k] = $v;
        }
    }

    public static function admin(): void
    {
        $c = PP::$config;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = fn(string $k) => ($v = trim((string)Admin::post($k))) === '' || filter_var($v, FILTER_VALIDATE_EMAIL) ? $v : throw new RuntimeException("Indirizzo email non valido: $v");
            try {
                $social = [];
                foreach (preg_split('~\R~', (string)Admin::post('social')) as $line) {
                    if (trim($line) === '') continue;
                    [$name, $url] = array_map('trim', array_pad(explode('|', $line, 2), 2, ''));
                    if ($url === '' && preg_match('~^https?://~', $name)) { $url = $name; $name = ucfirst(preg_replace('~^(www\.)?([^.]+).*$~', '$2', (string)parse_url($url, PHP_URL_HOST))); }
                    if (!preg_match('~^https://~', $url)) throw new RuntimeException("Social: “$line” deve avere un indirizzo che inizia con https://");
                    $social[$name] = $url;
                }
                $provider = in_array(Admin::post('provider'), ['anthropic', 'deepl'], true) ? Admin::post('provider') : '';
                $key = trim((string)Admin::post('api_key'));
                $old = Settings::get('site_config', []);
                $glossary = array_values(array_filter(array_map('trim', preg_split('~\R~', (string)Admin::post('glossary')))));
                Settings::set('site_config', [
                    'contact_email' => $email('contact_email'),
                    'mail_from' => $email('mail_from'),
                    'social' => $social,
                    'funder' => trim(strip_tags((string)Admin::post('funder'))),
                    // la chiave non si rimostra: campo vuoto = resta quella di prima
                    'translation' => ['provider' => $provider, 'glossary' => $glossary,
                        'api_key' => Admin::post('forget_key') ? '' : ($key !== '' ? $key : (string)($old['translation']['api_key'] ?? ''))],
                ]);
                Auth::log('site settings');
                Admin::changed('settings');
                Admin::flash('Impostazioni salvate.');
            } catch (Throwable $e) { Admin::flash($e->getMessage(), 'error'); }
            PP::redirect(Admin::u('settings'));
        }
        $tr = $c['translation'] ?? [];
        Admin::view(__DIR__ . '/admin.php', ['title' => 'Impostazioni del sito', 'c' => $c, 'tr' => $tr,
            'social' => implode("\n", array_map(fn($n, $u) => (is_int($n) ? '' : $n . ' | ') . $u, array_keys($c['social'] ?? []), $c['social'] ?? []))]);
    }
}
