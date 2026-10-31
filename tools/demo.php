<?php
/**
 * Sito dimostrativo per provare i temi con contenuti e foto credibili.
 *   php tools/demo.php           copia tools/demo/site in site/ (se site/ non c'è) e crea site/config.php su SQLite
 *   php tools/demo.php --force   sostituisce site/, data/ e uploads/ (cancella il sito di prova che c'era)
 * Poi: php -S 127.0.0.1:8080 index.php → /install.php
 */
if (PHP_SAPI !== 'cli') exit;
$root = dirname(__DIR__);
$force = in_array('--force', $argv, true);

$copy = function (string $from, string $to) use (&$copy): void {
    if (is_dir($from)) {
        @mkdir($to, 0775, true);
        foreach (scandir($from) as $f) if ($f !== '.' && $f !== '..') $copy("$from/$f", "$to/$f");
    } else copy($from, $to);
};
$wipe = function (string $dir) use (&$wipe): void {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        is_dir("$dir/$f") && !is_link("$dir/$f") ? $wipe("$dir/$f") : unlink("$dir/$f");
    }
    rmdir($dir);
};

if (is_dir("$root/site")) {
    if (!$force) exit("site/ c'è già: usa --force per sostituirla con il sito dimostrativo.\n");
    foreach (['site', 'data', 'uploads'] as $d) $wipe("$root/$d");
}
$copy(__DIR__ . '/demo/site', "$root/site");
$copy("$root/skeleton/site/modules", "$root/site/modules");
$copy("$root/skeleton/data", "$root/data");
$copy("$root/skeleton/uploads", "$root/uploads");
copy("$root/site/config.sample.php", "$root/site/config.php");
echo "Sito dimostrativo pronto. Avvia: php -S 127.0.0.1:8080 index.php e apri /install.php\n";
