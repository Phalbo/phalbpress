<?php
/**
 * Crea i file di una release di PhalbPress in dist/:
 *   phalbpress-core-X.Y.Z.zip       il motore, per l'aggiornamento dalla gestione (Aggiornamenti)
 *   phalbpress-core-X.Y.Z.zip.sig   firma Ed25519 dello zip (base64), se c'è la chiave
 *   phalbpress-X.Y.Z.zip            pacchetto completo per un sito nuovo (motore + install.php + cartelle vuote), senza site/
 *   notes.md                        le note della versione, prese da CHANGELOG.md
 *
 * Uso:  php tools/build.php [--tag vX.Y.Z]
 * Chiave: variabile d'ambiente PP_SIGNING_KEY = seme Ed25519 di 32 byte in base64 (il secret del repo su GitHub).
 * Prima di creare gli zip controlla la sintassi di tutti i file PHP: se uno è sbagliato la release non nasce.
 */
if (PHP_SAPI !== 'cli') exit(1);
$root = dirname(__DIR__);
chdir($root);
$fail = function (string $m) { fwrite(STDERR, "ERRORE: $m\n"); exit(1); };

preg_match("~const VERSION = '(\d+\.\d+\.\d+)'~", (string)file_get_contents('core/PP.php'), $m) || $fail('versione non trovata in core/PP.php');
$ver = $m[1];
$tag = '';
foreach ($argv as $i => $a) if ($a === '--tag') $tag = (string)($argv[$i + 1] ?? '');
if ($tag !== '' && ltrim($tag, 'v') !== $ver) $fail("il tag $tag non corrisponde alla versione $ver di core/PP.php");

// note della versione da CHANGELOG.md: la sezione "## X.Y.Z"
$cl = is_file('CHANGELOG.md') ? (string)file_get_contents('CHANGELOG.md') : '';
if (!preg_match('~^## ' . preg_quote($ver, '~') . '\b[^\n]*\n(.*?)(?=^## |\z)~ms', $cl, $n)) $fail("manca la sezione \"## $ver\" in CHANGELOG.md");
$notes = trim($n[1]);

// 1. sintassi di tutti i file PHP
$items = ['core', 'pp-admin', 'pp-assets', 'pp-themes', 'index.php', 'cron.php', 'install.php', 'tools'];
$php = [];
foreach ($items as $it) {
    if (is_file($it)) { if (str_ends_with($it, '.php')) $php[] = $it; continue; }
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($it, FilesystemIterator::SKIP_DOTS)) as $f) if ($f->getExtension() === 'php') $php[] = $f->getPathname();
}
$bad = [];
foreach ($php as $f) { exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($f) . ' 2>&1', $out, $code); if ($code !== 0) $bad[] = $f; }
if ($bad) $fail("errori di sintassi in:\n  " . implode("\n  ", $bad));
echo 'Sintassi OK: ' . count($php) . " file PHP\n";

// 2. zip
@mkdir('dist');
array_map('unlink', glob('dist/*') ?: []);
$files = function (array $list): array {
    $out = [];
    foreach ($list as $it) {
        if (!file_exists($it)) continue;
        if (is_file($it)) { $out[] = $it; continue; }
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($it, FilesystemIterator::SKIP_DOTS)) as $f) $out[] = str_replace('\\', '/', $f->getPathname());
    }
    sort($out);
    return $out;
};
$core = $files(['core', 'pp-admin', 'pp-assets', 'pp-themes', 'index.php', 'cron.php', '.htaccess', 'CLAUDE.md', 'LEGGIMI.md', 'CHANGELOG.md']);
$manifest = ['name' => 'phalbpress', 'version' => $ver, 'php_min' => '8.1', 'built' => gmdate('c'),
    'files' => array_combine($core, array_map(fn($f) => hash_file('sha256', $f), $core))];

$write = function (string $zipName, array $list, array $extra = []) {
    $z = new ZipArchive();
    if ($z->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException("impossibile creare $zipName");
    foreach ($list as $f) { $z->addFile($f, $f); $z->setMtimeName($f, 315532800); }
    foreach ($extra as $name => $content) { $z->addFromString($name, $content); $z->setMtimeName($name, 315532800); }
    $z->close();
};
$coreZip = "dist/phalbpress-core-$ver.zip";
$write($coreZip, $core, ['phalbpress.json' => json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)]);
echo "Creato $coreZip (" . round(filesize($coreZip) / 1024) . " KB, " . count($core) . " file)\n";

// pacchetto completo per un sito nuovo: dentro la cartella phalbpress/
$full = new ZipArchive();
$fullZip = "dist/phalbpress-$ver.zip";
$full->open($fullZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
foreach (array_merge($core, $files(['install.php', 'skeleton'])) as $f) {
    $dest = 'phalbpress/' . (str_starts_with($f, 'skeleton/') ? substr($f, 9) : $f);
    $full->addFile($f, $dest);
}
$full->close();
echo "Creato $fullZip\n";

// 3. firma
$seed = getenv('PP_SIGNING_KEY');
if ($seed) {
    $seedRaw = base64_decode(trim($seed), true);
    if ($seedRaw === false || strlen($seedRaw) !== SODIUM_CRYPTO_SIGN_SEEDBYTES) $fail('PP_SIGNING_KEY non è un seme Ed25519 di 32 byte in base64');
    $kp = sodium_crypto_sign_seed_keypair($seedRaw);
    $pub = base64_encode(sodium_crypto_sign_publickey($kp));
    $expected = trim((string)@file_get_contents('core/modules/updater/release.pub'));
    if ($pub !== $expected) $fail("la chiave di firma non corrisponde a core/modules/updater/release.pub (atteso $expected, chiave $pub)");
    $sig = sodium_crypto_sign_detached((string)file_get_contents($coreZip), sodium_crypto_sign_secretkey($kp));
    file_put_contents("$coreZip.sig", base64_encode($sig) . "\n");
    sodium_memzero($seedRaw);
    echo "Firmato $coreZip\n";
} elseif ($tag !== '') {
    $fail('manca PP_SIGNING_KEY: una release senza firma non verrebbe accettata dai siti');
} else {
    echo "Nessuna PP_SIGNING_KEY: zip non firmato (va bene solo per provare in locale)\n";
}

file_put_contents('dist/notes.md', $notes . "\n");
echo "Note della versione $ver pronte\n";
