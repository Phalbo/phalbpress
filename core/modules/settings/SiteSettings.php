<?php
final class SiteSettings
{
    /** Chiavi della configurazione che si possono cambiare dalla gestione (salvate in pp_settings, chiave "site_config"). */
    public const KEYS = ['contact_email', 'mail_from', 'social', 'social_place', 'funder', 'owner', 'translation',
        'company', 'phone', 'phone2', 'pec', 'vat', 'offices', 'toolbar', 'footer_badge'];
    /** Dove vanno le icone dei social. */
    public const SOCIAL_PLACES = ['footer' => 'Nel footer', 'header' => 'Nella testata', 'both' => 'In testata e nel footer', 'toolbar' => 'Nella barra sopra la testata',
        'toolbar_footer' => 'Nella barra sopra la testata e nel footer', 'none' => 'Da nessuna parte'];

    /** Sedi: blocchi separati da una riga vuota, la prima riga è il nome (es. "Sede legale Roma"). → [['label','lines'=>[…]], …] */
    public static function offices(): array
    {
        $out = [];
        foreach (preg_split('~\R\s*\R~', trim((string)(PP::$config['offices'] ?? ''))) as $blk) {
            $lines = array_values(array_filter(array_map('trim', preg_split('~\R~', $blk)), 'strlen'));
            if ($lines) $out[] = ['label' => array_shift($lines), 'lines' => $lines];
        }
        return $out;
    }

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

    /** Chi c'è dietro il sito (dati strutturati per Google e i motori AI): organizzazione o persona. */
    private static function ownerFromPost(): array
    {
        $txt = fn(string $k, int $max = 160) => mb_substr(trim(strip_tags((string)Admin::post('owner_' . $k))), 0, $max);
        $knows = array_values(array_filter(array_map(fn($x) => mb_substr(trim(strip_tags($x)), 0, 80), preg_split('~\R~', (string)Admin::post('owner_knows_about')))));
        return ['type' => Admin::post('owner_type') === 'person' ? 'person' : 'organization',
            'name' => $txt('name'), 'job_title' => $txt('job_title'), 'works_for' => $txt('works_for'),
            'location' => $txt('location'), 'alumni_of' => $txt('alumni_of'), 'knows_about' => array_slice($knows, 0, 20),
            'image' => (string)(PP::$config['owner']['image'] ?? '')];
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
                    'social_place' => isset(self::SOCIAL_PLACES[(string)Admin::post('social_place')]) ? Admin::post('social_place') : 'footer',
                    'company' => trim(strip_tags((string)Admin::post('company'))),
                    'phone' => trim(strip_tags((string)Admin::post('phone'))),
                    'phone2' => trim(strip_tags((string)Admin::post('phone2'))),
                    'pec' => $email('pec'),
                    'vat' => trim(strip_tags((string)Admin::post('vat'))),
                    'offices' => trim(strip_tags((string)Admin::post('offices'))),
                    'toolbar' => Admin::post('toolbar') ? 1 : 0,
                    'footer_badge' => preg_match('~^(uploads|site/assets)/[A-Za-z0-9/_.-]+\.(svg|png|webp|jpe?g)$~', trim((string)Admin::post('footer_badge'))) ? trim((string)Admin::post('footer_badge')) : '',
                    'funder' => trim(strip_tags((string)Admin::post('funder'))),
                    'owner' => self::ownerFromPost(),
                    // la chiave non si rimostra: campo vuoto = resta quella di prima
                    'translation' => ['provider' => $provider, 'glossary' => $glossary,
                        'api_key' => Admin::post('forget_key') ? '' : ($key !== '' ? $key : (string)($old['translation']['api_key'] ?? ''))],
                ]);
                Auth::log('site settings');
                self::apply();   // i valori nuovi valgono già per le pagine statiche rigenerate qui sotto
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
