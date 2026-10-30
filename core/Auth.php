<?php
/**
 * Accesso all'area di gestione: sessione sicura, token CSRF, blocco dopo tentativi falliti.
 */
final class Auth
{
    private const MAX_FAIL = 5;
    private const WINDOW = 900; // 15 minuti

    public static function start(?string $path = null): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        session_name('pp_' . substr(sha1(PP_ROOT), 0, 8));
        // il cookie di sessione vale solo per l'area di gestione: il sito pubblico resta senza cookie
        session_set_cookie_params(['lifetime' => 0, 'path' => $path ?? (PP::base() . '/' . PP::adminSlug() . '/'), 'secure' => $https, 'httponly' => true, 'samesite' => 'Strict']);
        session_start();
        if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
        if (!empty($_SESSION['uid']) && ($_SESSION['seen'] ?? 0) < time() - 60 * 60 * 8) self::logout();
        $_SESSION['seen'] = time();
    }

    public static function user(): ?array
    {
        if (empty($_SESSION['uid'])) return null;
        static $u = null;
        if ($u === null) $u = DB::one('SELECT id, email, name, role, langs FROM pp_users WHERE id = ?', [$_SESSION['uid']]) ?: false;
        return $u ?: null;
    }

    public static function isAdmin(): bool { return (self::user()['role'] ?? '') === 'admin'; }

    /** Ruoli: admin (tutto), editor (contenuti e sito, non utenti e sistema), author (solo i propri contenuti, in bozza), translator (solo traduzioni delle sue lingue). */
    public const ROLES = ['admin' => 'Amministratore', 'editor' => 'Redattore', 'author' => 'Autore', 'translator' => 'Traduttore'];

    public static function role(): string { return (string)(self::user()['role'] ?? ''); }

    /**
     * Permessi: 'content' (scrivere contenuti), 'publish' (pubblicare e modificare quelli degli altri),
     * 'site' (menu, testi, redirect, media altrui, documenti), 'admin' (utenti, sistema, backup, temi).
     */
    public static function can(string $perm): bool
    {
        $r = self::role();
        return match ($perm) {
            'admin' => $r === 'admin',
            'site', 'publish' => in_array($r, ['admin', 'editor'], true),
            'content' => in_array($r, ['admin', 'editor', 'author'], true),
            'translate' => in_array($r, ['admin', 'editor', 'translator'], true),
            default => false,
        };
    }

    /** Il traduttore lavora solo sulle sue lingue (vuoto = tutte tranne l'originale). */
    public static function canLang(string $lang): bool
    {
        if (self::role() !== 'translator') return true;
        if ($lang === I18n::$master) return false;
        $l = array_filter(explode(',', (string)(self::user()['langs'] ?? '')));
        return !$l || in_array($lang, $l, true);
    }

    /** Può modificare questo contenuto? (gli autori solo i propri) */
    public static function canEdit(array $row): bool
    {
        if (self::can('publish')) return true;
        if (self::role() === 'author') return (int)($row['author_id'] ?? 0) === (int)(self::user()['id'] ?? -1) || empty($row['id']);
        return false;
    }

    public static function ip(): string { return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0'), 0, 64); }

    public static function locked(): bool
    {
        DB::run('DELETE FROM pp_login_attempts WHERE at < ?', [time() - self::WINDOW]);
        return (int)DB::val('SELECT COUNT(*) FROM pp_login_attempts WHERE ip = ?', [self::ip()]) >= self::MAX_FAIL;
    }

    /** @return string 'ok' | 'totp' (serve il codice) | 'fail' */
    public static function login(string $email, string $pass): string
    {
        if (self::locked()) return 'fail';
        $u = DB::one('SELECT * FROM pp_users WHERE email = ?', [strtolower(trim($email))]);
        if (!$u || !password_verify($pass, $u['pass_hash'])) {
            self::fail();
            return 'fail';
        }
        if (password_needs_rehash($u['pass_hash'], PASSWORD_DEFAULT)) {
            DB::update('pp_users', ['pass_hash' => password_hash($pass, PASSWORD_DEFAULT)], 'id = ?', [$u['id']]);
        }
        if (!empty($u['totp_secret'])) {
            session_regenerate_id(true);
            $_SESSION['pending_uid'] = (int)$u['id'];
            $_SESSION['pending_at'] = time();
            return 'totp';
        }
        self::finish((int)$u['id']);
        return 'ok';
    }

    /** Secondo passaggio: codice a 6 cifre dell'app di autenticazione. */
    public static function verifyTotp(string $code): bool
    {
        $uid = (int)($_SESSION['pending_uid'] ?? 0);
        if (!$uid || ($_SESSION['pending_at'] ?? 0) < time() - 300 || self::locked()) return false;
        $secret = (string)DB::val('SELECT totp_secret FROM pp_users WHERE id = ?', [$uid]);
        if (!$secret || !Totp::check($secret, $code)) { self::fail(); return false; }
        unset($_SESSION['pending_uid'], $_SESSION['pending_at']);
        self::finish($uid);
        return true;
    }

    public static function fail(): void
    {
        DB::insert('pp_login_attempts', ['ip' => self::ip(), 'at' => time()]);
        usleep(400000);
    }

    private static function finish(int $uid): void
    {
        session_regenerate_id(true);
        $_SESSION['uid'] = $uid;
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        $_SESSION['seen'] = time();
        DB::run('DELETE FROM pp_login_attempts WHERE ip = ?', [self::ip()]);
        self::log('login');
    }

    /** Accesso consentito da questo indirizzo IP? (config admin_ips vuoto = da ovunque) */
    public static function ipAllowed(): bool
    {
        $list = PP::$config['admin_ips'] ?? [];
        return !$list || in_array(self::ip(), $list, true);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) session_regenerate_id(true);
    }

    public static function csrf(): string { return $_SESSION['csrf'] ?? ''; }

    public static function checkCsrf(): void
    {
        $t = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF'] ?? '');
        if (!is_string($t) || !hash_equals(self::csrf(), $t)) {
            http_response_code(419);
            exit('Sessione scaduta: ricarica la pagina e riprova.');
        }
    }

    public static function log(string $action, string $ref = ''): void
    {
        try {
            DB::insert('pp_log', ['user_email' => self::user()['email'] ?? '', 'action' => $action, 'ref' => $ref, 'at' => Content::now()]);
        } catch (Throwable $e) {}
    }

    public static function strong(string $p): ?string
    {
        if (strlen($p) < 12) return 'La password deve avere almeno 12 caratteri';
        return null;
    }
}

/**
 * Codici a tempo (TOTP, RFC 6238) compatibili con Google Authenticator, Microsoft Authenticator, Aegis, 2FAS…
 */
final class Totp
{
    private const ALPHA = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function secret(): string
    {
        $s = '';
        $bytes = random_bytes(20);
        for ($i = 0; $i < 32; $i++) $s .= self::ALPHA[ord($bytes[$i % 20]) & 31];
        return $s;
    }

    private static function decode(string $b32): string
    {
        $b32 = strtoupper(preg_replace('~[^A-Z2-7]~i', '', $b32));
        $bits = '';
        foreach (str_split($b32) as $c) $bits .= str_pad(decbin(strpos(self::ALPHA, $c)), 5, '0', STR_PAD_LEFT);
        $out = '';
        foreach (str_split($bits, 8) as $byte) if (strlen($byte) === 8) $out .= chr(bindec($byte));
        return $out;
    }

    public static function code(string $secret, ?int $t = null): string
    {
        $counter = intdiv($t ?? time(), 30);
        $bin = pack('N*', 0) . pack('N*', $counter);
        $h = hash_hmac('sha1', $bin, self::decode($secret), true);
        $o = ord($h[19]) & 0xf;
        $n = ((ord($h[$o]) & 0x7f) << 24) | ((ord($h[$o + 1]) & 0xff) << 16) | ((ord($h[$o + 2]) & 0xff) << 8) | (ord($h[$o + 3]) & 0xff);
        return str_pad((string)($n % 1000000), 6, '0', STR_PAD_LEFT);
    }

    public static function check(string $secret, string $code): bool
    {
        $code = preg_replace('~\D~', '', $code);
        if (strlen($code) !== 6) return false;
        foreach ([-1, 0, 1] as $w) if (hash_equals(self::code($secret, time() + $w * 30), $code)) return true;
        return false;
    }

    public static function uri(string $secret, string $label, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $label) . '?secret=' . $secret . '&issuer=' . rawurlencode($issuer) . '&digits=6&period=30';
    }
}
