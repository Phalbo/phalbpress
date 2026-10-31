<?php
final class Legal
{
    /** Slug delle pagine legali in una lingua (config legal_slugs: stringa o array per lingua). */
    public static function slugs(?string $lang = null): array
    {
        $lang = $lang ?: I18n::$current;
        $def = [
            'privacy' => ['en' => 'privacy', 'it' => 'privacy', 'es' => 'privacidad', 'sl' => 'zasebnost', 'sr' => 'privatnost', 'sq' => 'privatesia', 'fr' => 'confidentialite', 'de' => 'datenschutz', 'bg' => 'poveritelnost'],
            'cookies' => ['en' => 'cookie-policy'],
            'accessibility' => ['en' => 'accessibility', 'it' => 'accessibilita', 'es' => 'accesibilidad', 'sl' => 'dostopnost', 'sr' => 'pristupacnost', 'sq' => 'aksesueshmeria', 'fr' => 'accessibilite', 'de' => 'barrierefreiheit', 'bg' => 'dostapnost'],
        ];
        $cfg = PP::$config['legal_slugs'] ?? [];
        $out = [];
        foreach ($def as $k => $v) {
            $c = $cfg[$k] ?? $v;
            $out[$k] = is_array($c) ? ($c[$lang] ?? $c['en'] ?? reset($c)) : $c;
        }
        return $out;
    }

    public static function slug(string $key, ?string $lang = null): string { return self::slugs($lang)[$key] ?? $key; }

    public static function items(): array
    {
        $items = [];
        foreach (['privacy', 'cookies', 'accessibility'] as $k) {
            $p = [];
            foreach (I18n::codes() as $l) $p[$l] = self::slug($k, $l);
            $items[] = ['kind' => 'legal', 'paths' => $p];
        }
        return $items;
    }

    public static function cfg(): array
    {
        $c = PP::$config['legal'] ?? [];
        $todo = '[da completare in Gestione → Privacy e cookie]';
        return array_merge([
            'controller' => $todo, 'address' => $todo, 'email' => PP::$config['contact_email'] ?? $todo, 'dpo' => '',
            'hosting' => 'SiteGround', 'updated' => date('Y-m-d'),
            'a11y_status' => 'partial', 'a11y_date' => date('Y-m-d'), 'a11y_email' => PP::$config['contact_email'] ?? '',
        ], array_filter($c, fn($v) => $v !== '' && $v !== null), array_filter(Settings::get('legal', []), fn($v) => $v !== '' && $v !== null));
    }

    /* ---------- servizi esterni che usano cookie ---------- */

    public static function services(): array
    {
        return Hooks::filter('cookie_services', [
            'youtube' => ['name' => 'YouTube', 'provider' => 'Google Ireland Ltd', 'policy' => 'https://policies.google.com/privacy',
                'cookies' => 'YSC, VISITOR_INFO1_LIVE, PREF, VISITOR_PRIVACY_METADATA (fino a 6 mesi)', 'purpose' => 'video',
                'detect' => ['~\[video:[A-Za-z0-9_-]+\]~', '~(youtube\.com|youtu\.be|youtube-nocookie\.com)~i']],
            'vimeo' => ['name' => 'Vimeo', 'provider' => 'Vimeo.com Inc.', 'policy' => 'https://vimeo.com/privacy',
                'cookies' => 'vuid, player (fino a 2 anni)', 'purpose' => 'video', 'detect' => ['~\[vimeo:\d+\]~']],
        ]);
    }

    /** Cerca nei contenuti (tutte le lingue) i servizi esterni incorporati. */
    public static function scan(): array
    {
        $found = [];
        $svc = self::services();
        $texts = [];
        foreach (DB::all('SELECT e.id, e.type, e.meta, t.lang, t.body FROM pp_entries e LEFT JOIN pp_entry_tr t ON t.entry_id = e.id') as $r) {
            $texts['entry:' . $r['id']][] = (string)$r['body'] . ' ' . (string)$r['meta'];
        }
        foreach (DB::all('SELECT skey, value FROM pp_strings') as $r) $texts['testo:' . $r['skey']][] = (string)$r['value'];
        foreach ($texts as $ref => $chunks) {
            $all = implode(' ', $chunks);
            foreach ($svc as $k => $s) foreach ($s['detect'] as $re) {
                if (preg_match($re, $all)) { $found[$k][] = $ref; break; }
            }
        }
        // video nel campo "video" delle storie
        foreach (DB::all("SELECT id, meta FROM pp_entries") as $r) {
            $m = json_decode((string)$r['meta'], true);
            if (!empty($m['video'])) $found['youtube'][] = 'entry:' . $r['id'];
        }
        $found = array_map(fn($a) => array_values(array_unique($a)), $found);
        foreach ((PP::$config['cookie_services_always'] ?? []) as $k) $found[$k] = $found[$k] ?? ['configurazione'];
        $res = ['at' => Content::now(), 'found' => $found];
        Settings::set('cookie_scan', $res);
        return $res;
    }

    public static function found(): array
    {
        $s = Settings::get('cookie_scan');
        if (!$s) $s = self::scan();
        return array_intersect_key(self::services(), $s['found'] ?? []);
    }

    /* ---------- pagine ---------- */

    public static function route(string $lang, array $rest): ?array
    {
        if (count($rest) !== 1) return null;
        $key = array_search($rest[0], self::slugs($lang), true);
        if ($key === false) return null;
        $page = self::page($key, $lang);
        return ['name' => 'legal', 'params' => [$key], 'tpl' => 'page', 'vars' => ['page' => $page]];
    }

    public static function page(string $key, string $lang): array
    {
        $c = self::cfg();
        $site = class_exists('Identity') ? Identity::name() : PP::$config['site_name'];
        $titles = ['privacy' => 'legal_privacy_title', 'cookies' => 'legal_cookies_title', 'accessibility' => 'legal_a11y_title'];
        $bodies = ['privacy' => 'legal_privacy_body', 'cookies' => 'legal_cookies_body', 'accessibility' => 'legal_a11y_body'];
        $statusKey = ['full' => 'a11y_full', 'partial' => 'a11y_partial', 'none' => 'a11y_none'][$c['a11y_status']] ?? 'a11y_partial';
        $rep = [
            '{site}' => e($site), '{controller}' => e($c['controller']), '{address}' => e($c['address']),
            '{email}' => '<a href="mailto:' . e($c['email']) . '">' . e($c['email']) . '</a>',
            '{dpo}' => $c['dpo'] ? e($c['dpo']) : '—', '{hosting}' => e($c['hosting']),
            '{updated}' => fmt_date($c['updated'], $lang), '{services}' => self::servicesTable($lang),
            '{status}' => e(t($statusKey, $lang)), '{issues}' => self::issues($lang),
            '{a11y_email}' => '<a href="mailto:' . e($c['a11y_email']) . '">' . e($c['a11y_email']) . '</a>',
            '{a11y_date}' => fmt_date($c['a11y_date'], $lang),
            '{privacy_link}' => '<a href="' . e(PP::link(self::slug('privacy', $lang), $lang)) . '">' . e(t('legal_privacy_title', $lang)) . '</a>',
            '{cookie_link}' => '<a href="' . e(PP::link(self::slug('cookies', $lang), $lang)) . '">' . e(t('legal_cookies_title', $lang)) . '</a>',
            '{prefs_button}' => self::found() ? '<button type="button" class="btn btn--dark" data-pp-consent-open>' . e(t('cookie_prefs', $lang)) . '</button>' : '',
        ];
        $body = strtr(Sanitizer::html(t($bodies[$key], $lang)), $rep);
        $master = !I18n::has($bodies[$key], $lang) && $lang !== I18n::$master;
        return ['id' => 0, 'type' => 'legal', 'base_slug' => $key, 'slug' => self::slug($key, $lang), 'template' => 'default', 'title' => t($titles[$key], $lang), 'excerpt' => strtr(t('legal_updated', $lang), $rep),
            'body' => $body, 'image' => '', 'meta_arr' => [], '_lang' => $master ? I18n::$master : $lang, '_fallback' => $master];
    }

    private static function servicesTable(string $lang): string
    {
        $f = self::found();
        if (!$f) return '<p>' . e(t('cookie_none', $lang)) . '</p>';
        $h = '<table><thead><tr><th scope="col">' . e(t('cookie_th_service', $lang)) . '</th><th scope="col">' . e(t('cookie_th_purpose', $lang)) . '</th><th scope="col">Cookie</th></tr></thead><tbody>';
        foreach ($f as $s) {
            $h .= '<tr><td><strong>' . e($s['name']) . '</strong><br>' . e($s['provider']) . '<br><a href="' . e($s['policy']) . '" rel="noopener" target="_blank">Privacy policy</a></td>'
                . '<td>' . e(t('cookie_purpose_' . $s['purpose'], $lang)) . '</td><td>' . e($s['cookies']) . '</td></tr>';
        }
        return $h . '</tbody></table>';
    }

    private static function issues(string $lang): string
    {
        $items = array_filter(array_map('trim', explode("\n", t('a11y_issues', $lang))));
        $n = class_exists('Checks') ? Checks::untaggedPdfs() : 0;
        if ($n) $items[] = str_replace('{n}', (string)$n, t('a11y_pdf_issue', $lang));
        if (!$items) return '<p>' . e(t('a11y_no_issues', $lang)) . '</p>';
        return '<ul>' . implode('', array_map(fn($i) => '<li>' . e($i) . '</li>', $items)) . '</ul>';
    }

    /* ---------- banner e link nel footer ---------- */

    public static function banner(): string
    {
        $f = self::found();
        if (!$f) return '';
        $cfg = ['version' => (int)(PP::$config['cookie_consent_version'] ?? 1), 'months' => 6, 'services' => [], 'policy' => PP::link(self::slug('cookies', I18n::$current))];
        foreach ($f as $k => $s) $cfg['services'][$k] = ['name' => $s['name'], 'provider' => $s['provider']];
        $L = I18n::$current;
        $txt = [];
        foreach (['cookie_title', 'cookie_text', 'cookie_accept', 'cookie_reject', 'cookie_customize', 'cookie_save', 'cookie_close', 'video_consent', 'video_accept', 'video_external', 'cookie_policy_link'] as $k) $txt[$k] = t($k, $L);
        $cfg['t'] = $txt;
        return '<div class="pp-consent" data-pp-consent-root data-config="' . e(json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '" hidden></div>'
            . '<script src="' . e(PP::url('pp-assets/pp.js')) . '?v=' . PP::VERSION . '" defer></script>';
    }

    public static function footerLinks(): array
    {
        $L = I18n::$current;
        $links = [
            ['href' => PP::link(self::slug('privacy', $L), $L), 'label' => t('legal_privacy_title', $L)],
            ['href' => PP::link(self::slug('cookies', $L), $L), 'label' => t('legal_cookies_title', $L)],
            ['href' => PP::link(self::slug('accessibility', $L), $L), 'label' => t('legal_a11y_title', $L)],
        ];
        if (self::found()) $links[] = ['button' => true, 'label' => t('cookie_prefs', $L)];
        return $links;
    }

    /* ---------- gestione ---------- */

    public static function admin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (Admin::post('action') === 'save') {
                $keep = [];
                foreach (['controller', 'address', 'email', 'dpo', 'hosting', 'updated'] as $k) $keep[$k] = trim((string)Admin::post($k));
                Settings::set('legal', array_merge(Settings::get('legal', []), $keep));
                Admin::flash('Dati della privacy salvati.');
            }
            self::scan();
            Admin::changed('privacy');
            if (Admin::post('action') !== 'save') Admin::flash('Scansione eseguita.');
            PP::redirect(Admin::u('privacy'));
        }
        $scan = Settings::get('cookie_scan') ?: self::scan();
        Admin::view(__DIR__ . '/admin-privacy.php', ['title' => 'Privacy e cookie', 'scan' => $scan, 'services' => self::services(), 'cfg' => self::cfg()]);
    }
}
