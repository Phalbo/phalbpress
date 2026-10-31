<?php
/**
 * PhalbPress — installatore in un file solo.
 * Caricalo da solo nella cartella del sito (vuota) e aprilo nel browser: scarica l'ultima versione di PhalbPress da GitHub,
 * controlla la firma, scompatta tutto qui e ti porta all'installazione guidata (install.php).
 * Questo file nella release è generato da tools/build.php (con la chiave pubblica di firma al posto di __PUBKEY__).
 */
const PP_REPO = 'Phalbo/phalbpress';
const PP_PUBKEY = '__PUBKEY__';

if (PHP_VERSION_ID < 80100) exit('PhalbPress richiede PHP 8.1 o superiore (questo server: ' . PHP_VERSION . '). Su SiteGround: Site Tools → Devs → PHP Manager.');
$dir = __DIR__;
$self = basename(__FILE__);
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if ($base === '.') $base = '';
// già scompattato: si prosegue con l'installazione guidata
if (is_file($dir . '/install.php') && is_dir($dir . '/core')) { header('Location: ' . $base . '/install.php', true, 303); exit; }

session_name('pp_get');
session_set_cookie_params(['path' => $base . '/' . $self, 'httponly' => true, 'samesite' => 'Strict']);
session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; base-uri 'none'");

$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$http = function (string $url, bool $api = false): string {
    $headers = ['User-Agent: PhalbPress-installa'];
    if ($api) $headers[] = 'Accept: application/vnd.github+json';
    if (function_exists('curl_init')) {
        $c = curl_init($url);
        curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 5,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_TIMEOUT => 300, CURLOPT_HTTPHEADER => $headers, CURLOPT_SSL_VERIFYPEER => true]);
        $body = curl_exec($c);
        $code = (int)curl_getinfo($c, CURLINFO_RESPONSE_CODE);
        $err = curl_error($c);
        curl_close($c);
        if ($body === false || $code >= 400) throw new RuntimeException("GitHub non risponde ($code" . ($err ? ", $err" : '') . ')');
        return (string)$body;
    }
    $body = @file_get_contents($url, false, stream_context_create(['http' => ['header' => implode("\r\n", $headers), 'timeout' => 300]]));
    if ($body === false) throw new RuntimeException('GitHub non risponde');
    return $body;
};

$checks = [
    ['Zip (ZipArchive)', class_exists('ZipArchive')],
    ['Verifica della firma (sodium)', function_exists('sodium_crypto_sign_verify_detached')],
    ['Collegamento a GitHub (curl o allow_url_fopen)', function_exists('curl_init') || (bool)ini_get('allow_url_fopen')],
    ['Cartella scrivibile', is_writable($dir)],
];
$ready = !in_array(false, array_column($checks, 1), true);
$others = array_values(array_diff(scandir($dir) ?: [], ['.', '..', $self, '.well-known', 'cgi-bin', '.htaccess', 'error_log', '.user.ini', 'php.ini']));
$error = '';

if ($ready && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) throw new RuntimeException('Sessione scaduta: ricarica la pagina.');
        @set_time_limit(600);
        $rel = json_decode($http('https://api.github.com/repos/' . PP_REPO . '/releases/latest', true), true);
        $ver = ltrim((string)($rel['tag_name'] ?? ''), 'v');
        $zipUrl = $sigUrl = '';
        foreach ($rel['assets'] ?? [] as $a) {
            if (($a['name'] ?? '') === "phalbpress-$ver.zip") $zipUrl = (string)$a['browser_download_url'];
            if (($a['name'] ?? '') === "phalbpress-$ver.zip.sig") $sigUrl = (string)$a['browser_download_url'];
        }
        if (!$ver || !$zipUrl || !$sigUrl) throw new RuntimeException("L'ultima release ($ver) non ha il pacchetto completo firmato: riprova tra qualche minuto (GitHub sta ancora preparando i file).");
        $zip = $http($zipUrl);
        $sig = base64_decode(trim($http($sigUrl)), true);
        $key = base64_decode(PP_PUBKEY, true);
        if ($sig === false || $key === false || strlen($key) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES || !sodium_crypto_sign_verify_detached($sig, $zip, $key))
            throw new RuntimeException('La firma del pacchetto non è valida: non lo installo.');
        $tmp = tempnam(sys_get_temp_dir(), 'pp');
        file_put_contents($tmp, $zip);
        $z = new ZipArchive();
        if ($z->open($tmp) !== true) throw new RuntimeException('Pacchetto danneggiato.');
        $n = 0;
        for ($i = 0; $i < $z->numFiles; $i++) {
            $name = str_replace('\\', '/', (string)$z->getNameIndex($i));
            if (!str_starts_with($name, 'phalbpress/')) continue;
            $rel = substr($name, 11);
            if ($rel === '' || str_contains($rel, '..') || str_starts_with($rel, '/')) continue;
            if (str_ends_with($rel, '/')) { if (!is_dir($dir . '/' . $rel)) mkdir($dir . '/' . $rel, 0775, true); continue; }
            // un .htaccess già presente (es. creato dall'hosting) resta da parte: serve quello di PhalbPress, che protegge le cartelle interne
            if ($rel === '.htaccess' && is_file($dir . '/.htaccess')) @rename($dir . '/.htaccess', $dir . '/.htaccess-prima-di-phalbpress');
            $dest = $dir . '/' . $rel;
            if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0775, true);
            file_put_contents($dest, (string)$z->getFromIndex($i));
            $n++;
        }
        $z->close();
        @unlink($tmp);
        if (!is_file($dir . '/install.php')) throw new RuntimeException('Il pacchetto non si è scompattato: controlla i permessi della cartella.');
        @unlink(__FILE__);
        header('Location: ' . $base . '/install.php', true, 303);
        exit;
    } catch (Throwable $ex) { $error = $ex->getMessage(); }
}
?><!doctype html>
<html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Installa PhalbPress</title>
<style>
body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f3f2ee;font:16px/1.5 system-ui,-apple-system,"Segoe UI",sans-serif;color:#151515}
main{background:#fff;border:1px solid #ddd;border-radius:12px;padding:28px;width:min(520px,92vw);display:grid;gap:14px}
h1{margin:0;font-size:1.5rem} p{margin:0} ul{margin:0;padding:0;list-style:none;display:grid;gap:4px;font-size:14px}
.ok::before{content:"✓ ";color:#1f7a3a;font-weight:700} .no::before{content:"✕ ";color:#8f1d0a;font-weight:700} .no{color:#8f1d0a}
.err{background:#fde8e1;color:#6d1d06;padding:10px 12px;border-radius:8px} .warn{background:#fff4d6;padding:10px 12px;border-radius:8px;font-size:14px}
button{font:inherit;font-weight:600;background:#151515;color:#fff;border:0;border-radius:8px;padding:12px 18px;cursor:pointer} button:disabled{opacity:.4}
button:focus-visible{outline:3px solid #c63232;outline-offset:2px} small{color:#555}
</style></head>
<body><main>
  <h1>Installa PhalbPress</h1>
  <p>Scarica l’ultima versione da GitHub, controlla che sia firmata da PhalbPress, la scompatta in questa cartella e ti porta all’installazione guidata.</p>
  <ul><?php foreach ($checks as [$l, $ok]): ?><li class="<?= $ok ? 'ok' : 'no' ?>"><?= $e($l) ?></li><?php endforeach; ?></ul>
  <?php if ($others): ?><p class="warn">La cartella non è vuota (<?= $e(implode(', ', array_slice($others, 0, 6))) ?><?= count($others) > 6 ? '…' : '' ?>). I file con lo stesso nome verranno sostituiti: meglio installare in una cartella o un sottodominio vuoti.</p><?php endif; ?>
  <?php if ($error): ?><p class="err" role="alert"><?= $e($error) ?></p><?php endif; ?>
  <form method="post"><input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf']) ?>"><button<?= $ready ? '' : ' disabled' ?>>Scarica e installa</button></form>
  <small>Alla fine questo file si cancella da solo.</small>
</main></body></html>
