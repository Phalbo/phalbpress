<?php
/**
 * Statistiche delle visite, in Gestione → Privacy e cookie. Si scrive solo l'identificativo: il codice lo mette il motore.
 *  - Matomo (indirizzo del proprio Matomo + numero del sito) e Plausible (dominio): senza cookie, niente banner;
 *  - Google Analytics 4 (G-…): solo dopo il consenso, compare nel banner e nella cookie policy.
 * La CSP si apre solo ai domini del servizio scelto; privacy e cookie policy lo dicono da sole.
 */
final class Stats
{
    public static function cfg(): array
    {
        $s = Settings::get('stats', []);
        $s = is_array($s) ? $s : [];
        $ga = strtoupper(trim((string)($s['ga4'] ?? '')));
        $mu = trim((string)($s['matomo_url'] ?? ''));
        $mu = preg_match('~^https://[a-z0-9.-]+(:\d+)?(/[A-Za-z0-9._/-]*)?$~i', $mu) ? rtrim($mu, '/') . '/' : '';
        $mid = (string)(int)($s['matomo_id'] ?? 0);
        $pd = strtolower(trim((string)($s['plausible'] ?? '')));
        $ph = trim((string)($s['plausible_host'] ?? ''));
        return [
            'ga4' => preg_match('~^G-[A-Z0-9]{4,16}$~', $ga) ? $ga : '',
            'matomo_url' => $mu && $mid !== '0' ? $mu : '', 'matomo_id' => $mu && $mid !== '0' ? $mid : '',
            'plausible' => preg_match('~^[a-z0-9.-]+\.[a-z]{2,}(,[a-z0-9.-]+\.[a-z]{2,})*$~', $pd) ? $pd : '',
            'plausible_host' => preg_match('~^https://[a-z0-9.-]+(:\d+)?$~i', rtrim($ph, '/')) ? rtrim($ph, '/') : 'https://plausible.io',
        ];
    }

    public static function any(): bool { $c = self::cfg(); return $c['ga4'] || $c['matomo_url'] || $c['plausible']; }

    private static function origin(string $url): string
    {
        $u = parse_url($url);
        return ($u['scheme'] ?? 'https') . '://' . ($u['host'] ?? '') . (isset($u['port']) ? ':' . $u['port'] : '');
    }

    /** Domini da aprire nella CSP, per tipo (script, connect, img). */
    public static function csp(string $kind): array
    {
        $c = self::cfg();
        $out = [];
        if ($c['ga4']) $out = array_merge($out, match ($kind) {
            'script' => ['https://www.googletagmanager.com'],
            'connect' => ['https://*.google-analytics.com', 'https://*.analytics.google.com', 'https://www.googletagmanager.com'],
            'img' => ['https://*.google-analytics.com', 'https://www.googletagmanager.com'],
        });
        if ($c['matomo_url']) $out[] = self::origin($c['matomo_url']);
        if ($c['plausible'] && $kind !== 'img') $out[] = $c['plausible_host'];
        return $out;
    }

    /** Google Analytics come servizio col consenso (banner e cookie policy). */
    public static function services(array $svc): array
    {
        if (!self::cfg()['ga4']) return $svc;
        return $svc + ['ga4' => ['name' => 'Google Analytics', 'provider' => 'Google Ireland Ltd', 'policy' => 'https://policies.google.com/privacy',
            'cookies' => '_ga, _ga_* (fino a 2 anni)', 'purpose' => 'stats', 'detect' => []]];
    }

    /** Lo script, in fondo alla pagina. Nessun codice in linea (CSP): la configurazione è negli attributi data-. */
    public static function tag(): string
    {
        if (Content::$preview) return '';   // l'anteprima della gestione non conta come visita
        $c = self::cfg();
        $h = '';
        if ($c['plausible']) $h .= '<script defer data-domain="' . e($c['plausible']) . '" src="' . e($c['plausible_host']) . '/js/script.js"></script>';
        if ($c['matomo_url'] || $c['ga4']) {
            $h .= '<script src="' . e(PP::url('pp-assets/stats.js')) . '?v=' . PP::VERSION . '" defer id="pp-stats"'
                . ($c['matomo_url'] ? ' data-matomo="' . e($c['matomo_url']) . '" data-matomo-id="' . e($c['matomo_id']) . '"' : '')
                . ($c['ga4'] ? ' data-ga4="' . e($c['ga4']) . '"' : '') . '></script>';
        }
        return $h;
    }

    /** Paragrafo della privacy policy: quali statistiche, o nessuna. */
    public static function privacy(string $lang): string
    {
        $c = self::cfg();
        $site = class_exists('Identity') ? Identity::name() : PP::$config['site_name'];
        if (!self::any()) return strtr(Sanitizer::html(t('legal_stats_none', $lang)), ['{site}' => e($site)]);
        $tools = [];
        if ($c['matomo_url']) $tools[] = strtr(Sanitizer::inline(t('legal_stats_matomo', $lang)), ['{host}' => e(parse_url($c['matomo_url'], PHP_URL_HOST))]);
        if ($c['plausible']) $tools[] = Sanitizer::inline(t('legal_stats_plausible', $lang));
        if ($c['ga4']) $tools[] = strtr(Sanitizer::inline(t('legal_stats_ga4', $lang)), ['{cookie_link}' => '<a href="' . e(PP::link(Legal::slug('cookies', $lang), $lang)) . '">' . e(t('legal_cookies_title', $lang)) . '</a>']);
        return strtr(Sanitizer::html(t('legal_stats_on', $lang)), ['{site}' => e($site), '{tools}' => '<ul><li>' . implode('</li><li>', $tools) . '</li></ul>']);
    }

    public static function save(): void
    {
        Settings::set('stats', [
            'ga4' => trim((string)Admin::post('ga4')), 'matomo_url' => trim((string)Admin::post('matomo_url')),
            'matomo_id' => trim((string)Admin::post('matomo_id')), 'plausible' => trim((string)Admin::post('plausible')),
            'plausible_host' => trim((string)Admin::post('plausible_host')),
        ]);
    }
}
