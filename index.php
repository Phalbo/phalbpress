<?php
/**
 * PhalbPress — punto di ingresso unico.
 * Sviluppo locale:  php -S localhost:8000 index.php
 */
if (PHP_SAPI === 'cli-server') {
    $p = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $f = __DIR__ . $p;
    if (preg_match('~/(core|data|site/(config|strings|seed|modules))|^/pp-themes/.+\.(php|json)$|^/pp-assets/.+\.php$|^/cron\.php~', $p)) { http_response_code(403); exit; }
    if (is_file($f)) return false;
    if (is_dir($f) && is_file(rtrim($f, '/') . '/index.html')) { header('Content-Type: text/html; charset=utf-8'); readfile(rtrim($f, '/') . '/index.html'); return true; }
}
require __DIR__ . '/core/bootstrap.php';
PP::run();
