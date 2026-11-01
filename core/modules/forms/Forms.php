<?php
final class Forms
{
    private const MAX_PER_HOUR = 5;
    private const MIN_SECONDS = 3;

    public static function strings(): array
    {
        $g = 'Modulo di contatto';
        return [
            'form_name' => ['group' => $g, 'text' => ['en' => 'Name', 'it' => 'Nome']],
            'form_email' => ['group' => $g, 'text' => ['en' => 'Email', 'it' => 'Email']],
            'form_org' => ['group' => $g, 'text' => ['en' => 'Organisation (optional)', 'it' => 'Organizzazione (facoltativo)']],
            'form_message' => ['group' => $g, 'text' => ['en' => 'Message', 'it' => 'Messaggio']],
            'form_privacy' => ['group' => $g, 'html' => true, 'note' => '{privacy} = indirizzo della privacy policy', 'text' => [
                'en' => 'I have read the <a href="{privacy}">privacy policy</a>. My data are used only to answer this message.',
                'it' => 'Ho letto la <a href="{privacy}">privacy policy</a>. I miei dati servono solo per rispondere a questo messaggio.']],
            'form_send' => ['group' => $g, 'text' => ['en' => 'Send', 'it' => 'Invia']],
            'form_required' => ['group' => $g, 'text' => ['en' => 'required', 'it' => 'obbligatorio']],
            'form_ok' => ['group' => $g, 'text' => ['en' => 'Thank you, your message has been sent. We will answer as soon as we can.', 'it' => 'Grazie, il messaggio è stato inviato. Ti risponderemo appena possibile.']],
            'form_error' => ['group' => $g, 'note' => '{email} = indirizzo di contatto', 'text' => ['en' => 'The message could not be sent. Please check the fields, or write to {email}.', 'it' => 'Non è stato possibile inviare il messaggio. Controlla i campi, oppure scrivi a {email}.']],
            'nl_title' => ['group' => 'Newsletter', 'text' => ['en' => 'Stay in the loop', 'it' => 'Resta aggiornato']],
            'nl_email' => ['group' => 'Newsletter', 'text' => ['en' => 'Your email', 'it' => 'La tua email']],
            'nl_send' => ['group' => 'Newsletter', 'text' => ['en' => 'Subscribe', 'it' => 'Iscriviti']],
            'nl_privacy' => ['group' => 'Newsletter', 'html' => true, 'note' => '{privacy} = indirizzo della privacy policy', 'text' => [
                'en' => 'I agree to receive the project newsletter and have read the <a href="{privacy}">privacy policy</a>. I can unsubscribe at any time.',
                'it' => 'Accetto di ricevere la newsletter del progetto e ho letto la <a href="{privacy}">privacy policy</a>. Posso cancellarmi quando voglio.']],
            'nl_ok' => ['group' => 'Newsletter', 'text' => ['en' => 'Thank you, you are subscribed.', 'it' => 'Grazie, sei iscritto.']],
            'nl_error' => ['group' => 'Newsletter', 'text' => ['en' => 'The subscription did not work. Please check the email and the consent box.', 'it' => 'L’iscrizione non è riuscita. Controlla l’email e la casella del consenso.']],
            'form_nojs' => ['group' => $g, 'note' => '{email} = indirizzo di contatto', 'text' => ['en' => 'This form needs JavaScript to protect against spam. You can also write to {email}.', 'it' => 'Questo modulo richiede JavaScript per difendersi dallo spam. Puoi anche scrivere a {email}.']],
        ];
    }

    private static function secret(): string
    {
        $s = (string)Settings::get('secret', '');
        if (strlen($s) < 32) { $s = bin2hex(random_bytes(32)); Settings::set('secret', $s); }
        return $s;
    }

    public static function email(): string
    {
        return (string)(Settings::get('forms_to') ?: (PP::$config['contact_email'] ?? ''));
    }

    public static function render(string $form): string
    {
        $file = $form === 'newsletter' ? (PP::themeFile('partials/form-newsletter.php') ?: __DIR__ . '/form-newsletter.php') : (PP::themeFile('partials/form.php') ?: __DIR__ . '/form.php');
        $privacy = class_exists('Legal') ? PP::link(Legal::slug('privacy')) : '#';
        $email = self::email();
        $action = PP::url('form/' . $form);
        $key = substr(hash_hmac('sha256', 'form:' . $form, self::secret()), 0, 16);
        ob_start();
        require $file;
        return (string)ob_get_clean();
    }

    /** Torna alla pagina di partenza con l'esito nell'ancora (#pp-form-ok / #pp-form-err): funziona anche con le pagine statiche. */
    private static function back(bool $ok): never
    {
        $ref = (string)($_SERVER['HTTP_REFERER'] ?? '');
        $path = parse_url($ref, PHP_URL_PATH) ?: PP::link('');
        $host = parse_url($ref, PHP_URL_HOST);
        if ($host && $host !== ($_SERVER['HTTP_HOST'] ?? '') && $host !== parse_url(PP::origin(), PHP_URL_HOST)) $path = PP::link('');
        header('Cache-Control: no-store');
        PP::redirect($path . '#pp-form-' . ($ok ? 'ok' : 'err'), 303);
    }

    public static function submit(string $form): void
    {
        $p = $_POST;
        $lang = I18n::valid((string)($p['lang'] ?? '')) ? $p['lang'] : I18n::$master;
        $key = substr(hash_hmac('sha256', 'form:' . $form, self::secret()), 0, 16);
        $ipHash = substr(hash_hmac('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? ''), self::secret()), 0, 24);

        // trappole: campo nascosto compilato, token del browser assente o sbagliato, modulo inviato troppo in fretta
        $elapsed = (int)($p['pp_e'] ?? 0);
        if (!empty($p['website']) || !hash_equals($key, (string)($p['pp_k'] ?? '')) || $elapsed < self::MIN_SECONDS) self::back(false);

        if ($form === 'newsletter') {
            $email = trim((string)($p['email'] ?? ''));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($p['privacy'])) self::back(false);
            $recent = (int)DB::val("SELECT COUNT(*) FROM pp_form_entries WHERE data LIKE ? AND at > ?", ['%"_ip":"' . $ipHash . '"%', date('Y-m-d H:i:s', time() - 3600)]);
            if ($recent >= self::MAX_PER_HOUR) self::back(false);
            // un indirizzo una volta sola: se c'è già, si risponde comunque "iscritto"
            if (!DB::val("SELECT id FROM pp_form_entries WHERE form = 'newsletter' AND data LIKE ?", ['%"email":' . json_encode(strtolower($email)) . '%'])) {
                $data = Hooks::filter('form_data', ['name' => '', 'email' => strtolower($email), 'org' => '', 'message' => 'newsletter', 'consent' => Content::now(),
                    'page' => parse_url((string)($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_PATH) ?: '', '_ip' => $ipHash], $form, $p);
                DB::insert('pp_form_entries', ['form' => 'newsletter', 'lang' => $lang, 'data' => json_encode($data, JSON_UNESCAPED_UNICODE), 'at' => Content::now(), 'is_read' => 0]);
            }
            self::back(true);
        }
        $name = mb_substr(trim(strip_tags((string)($p['name'] ?? ''))), 0, 120);
        $email = trim((string)($p['email'] ?? ''));
        $org = mb_substr(trim(strip_tags((string)($p['org'] ?? ''))), 0, 160);
        $msg = mb_substr(trim(strip_tags((string)($p['message'] ?? ''))), 0, 5000);
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($msg) < 5 || empty($p['privacy'])) self::back(false);

        $recent = (int)DB::val("SELECT COUNT(*) FROM pp_form_entries WHERE data LIKE ? AND at > ?", ['%"_ip":"' . $ipHash . '"%', date('Y-m-d H:i:s', time() - 3600)]);
        if ($recent >= self::MAX_PER_HOUR) self::back(false);

        $page = parse_url((string)($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_PATH) ?: '';
        $data = ['name' => $name, 'email' => $email, 'org' => $org, 'message' => $msg, 'page' => $page, '_ip' => $ipHash];
        $data = Hooks::filter('form_data', $data, $form, $p);
        DB::insert('pp_form_entries', ['form' => $form, 'lang' => $lang, 'data' => json_encode($data, JSON_UNESCAPED_UNICODE), 'at' => Content::now(), 'is_read' => 0]);

        $to = self::email();
        if ($to && function_exists('mail')) {
            $clean = fn($s) => str_replace(["\r", "\n"], ' ', $s);
            $site = PP::$config['site_name'] ?? 'Sito';
            $from = PP::$config['mail_from'] ?? ('noreply@' . preg_replace('~^www\.~', '', parse_url(PP::origin(), PHP_URL_HOST) ?: 'localhost'));
            $body = "Nuovo messaggio dal sito $site ($form, " . strtoupper($lang) . ")\n\n"
                . "Nome: $name\nEmail: $email\n" . ($org ? "Organizzazione: $org\n" : '') . "Pagina: $page\n\n$msg\n\n"
                . "— Tutte le richieste: " . PP::origin() . '/' . PP::adminSlug() . "/forms\n";
            $headers = 'From: ' . $clean($site) . ' <' . $clean($from) . ">\r\nReply-To: " . $clean($email) . "\r\nContent-Type: text/plain; charset=UTF-8";
            @mail($to, '=?UTF-8?B?' . base64_encode('[' . $site . '] ' . $clean($name)) . '?=', $body, $headers);
        }
        self::back(true);
    }

    public static function admin(string $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = Admin::post('action');
            if ($act === 'delete') DB::run('DELETE FROM pp_form_entries WHERE id = ?', [(int)Admin::post('id')]);
            if ($act === 'read') DB::run('UPDATE pp_form_entries SET is_read = 1 - is_read WHERE id = ?', [(int)Admin::post('id')]);
            if ($act === 'purge') { DB::run('DELETE FROM pp_form_entries WHERE at < ?', [date('Y-m-d H:i:s', strtotime('-12 months'))]); Admin::flash('Eliminate le richieste più vecchie di 12 mesi.'); }
            if ($act === 'to' && Auth::isAdmin()) {
                $to = trim((string)Admin::post('to'));
                if ($to === '' || filter_var($to, FILTER_VALIDATE_EMAIL)) { Settings::set('forms_to', $to); Admin::flash('Indirizzo salvato.'); }
                else Admin::flash('Email non valida.', 'error');
            }
            PP::redirect(Admin::u('forms'));
        }
        $rows = DB::all('SELECT * FROM pp_form_entries ORDER BY id DESC LIMIT 500');
        foreach ($rows as &$r) $r['d'] = json_decode((string)$r['data'], true) ?: [];
        unset($r);
        if ($id === 'newsletter') {
            // solo gli iscritti alla newsletter: email, lingua, data del consenso (da importare in Brevo, Mailchimp…)
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="newsletter-' . date('Y-m-d') . '.csv"');
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['email', 'lingua', 'consenso'], ';');
            foreach ($rows as $r) if ($r['form'] === 'newsletter') fputcsv($out, [$r['d']['email'] ?? '', $r['lang'], $r['d']['consent'] ?? $r['at']], ';');
            exit;
        }
        if ($id === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="richieste-' . date('Y-m-d') . '.csv"');
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['data', 'modulo', 'lingua', 'nome', 'email', 'organizzazione', 'messaggio', 'pagina'], ';');
            $safe = fn($v) => preg_match('~^[=+\-@\t\r]~', (string)$v) ? "'" . $v : (string)$v;   // niente formule in Excel
            foreach ($rows as $r) fputcsv($out, array_map($safe, [$r['at'], $r['form'], $r['lang'], $r['d']['name'] ?? '', $r['d']['email'] ?? '', $r['d']['org'] ?? '', $r['d']['message'] ?? '', $r['d']['page'] ?? '']), ';');
            exit;
        }
        Admin::view(__DIR__ . '/admin.php', ['title' => 'Richieste', 'rows' => $rows, 'to' => self::email()]);
    }
}
