<?php
/**
 * PhalbPress — accesso al database (PDO). Supporta MySQL/MariaDB (SiteGround) e SQLite (sviluppo locale).
 */
final class DB
{
    private static ?PDO $pdo = null;
    private static string $driver = 'mysql';

    public static function connect(array $cfg): PDO
    {
        if (self::$pdo) return self::$pdo;
        self::$driver = $cfg['driver'] ?? 'mysql';
        $opt = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        if (self::$driver === 'sqlite') {
            $file = $cfg['file'] ?? (PP_ROOT . '/data/site.sqlite');
            if (!is_dir(dirname($file))) mkdir(dirname($file), 0775, true);
            self::$pdo = new PDO('sqlite:' . $file, null, null, $opt);
            self::$pdo->exec('PRAGMA foreign_keys = ON; PRAGMA journal_mode = WAL;');
        } else {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $cfg['host'] ?? 'localhost', (int)($cfg['port'] ?? 3306), $cfg['name']);
            self::$pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], $opt);
        }
        return self::$pdo;
    }

    /** Passa a un altro database per il resto della richiesta (anteprima demo dei temi). */
    public static function reconnect(array $cfg): PDO
    {
        self::$pdo = null;
        return self::connect($cfg);
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo) throw new RuntimeException('Database non connesso');
        return self::$pdo;
    }

    public static function driver(): string { return self::$driver; }

    public static function run(string $sql, array $args = []): PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($args);
        return $st;
    }

    public static function one(string $sql, array $args = []): ?array
    {
        $r = self::run($sql, $args)->fetch();
        return $r === false ? null : $r;
    }

    public static function all(string $sql, array $args = []): array
    {
        return self::run($sql, $args)->fetchAll();
    }

    public static function val(string $sql, array $args = [])
    {
        $r = self::run($sql, $args)->fetchColumn();
        return $r === false ? null : $r;
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = 'INSERT INTO ' . $table . ' (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')';
        self::run($sql, array_values($data));
        return (int)self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $args = []): void
    {
        $set = implode(',', array_map(fn($c) => "$c = ?", array_keys($data)));
        self::run("UPDATE $table SET $set WHERE $where", array_merge(array_values($data), $args));
    }

    /** INSERT o UPDATE sulla chiave indicata, compatibile MySQL e SQLite. */
    public static function upsert(string $table, array $data, array $keys): void
    {
        $cols = array_keys($data);
        $ph = implode(',', array_fill(0, count($cols), '?'));
        $upd = array_diff($cols, $keys);
        if (self::$driver === 'sqlite') {
            $set = implode(',', array_map(fn($c) => "$c = excluded.$c", $upd));
            $sql = "INSERT INTO $table (" . implode(',', $cols) . ") VALUES ($ph) ON CONFLICT(" . implode(',', $keys) . ") DO UPDATE SET $set";
        } else {
            $set = implode(',', array_map(fn($c) => "$c = VALUES($c)", $upd));
            $sql = "INSERT INTO $table (" . implode(',', $cols) . ") VALUES ($ph) ON DUPLICATE KEY UPDATE $set";
        }
        self::run($sql, array_values($data));
    }

    /** Crea le tabelle se non esistono. */
    public static function migrate(): void
    {
        $sqlite = self::$driver === 'sqlite';
        $id = $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY';
        $txt = $sqlite ? 'TEXT' : 'MEDIUMTEXT';
        $vc = fn($n) => $sqlite ? 'TEXT' : "VARCHAR($n)";
        $tail = $sqlite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        $q = [];
        $q[] = "CREATE TABLE IF NOT EXISTS pp_users (
            id $id, email {$vc(190)} NOT NULL UNIQUE, name {$vc(120)} NOT NULL DEFAULT '',
            pass_hash {$vc(255)} NOT NULL, role {$vc(20)} NOT NULL DEFAULT 'editor',
            created_at {$vc(25)} NOT NULL DEFAULT '')$tail";
        $q[] = "CREATE TABLE IF NOT EXISTS pp_login_attempts (
            id $id, ip {$vc(64)} NOT NULL, at INTEGER NOT NULL)$tail";
        $q[] = "CREATE TABLE IF NOT EXISTS pp_entries (
            id $id, type {$vc(30)} NOT NULL, slug {$vc(190)} NOT NULL, template {$vc(40)} NOT NULL DEFAULT 'default',
            menu_order INTEGER NOT NULL DEFAULT 0, in_menu INTEGER NOT NULL DEFAULT 0,
            status {$vc(20)} NOT NULL DEFAULT 'draft', pub_date {$vc(25)} NOT NULL DEFAULT '',
            image {$vc(255)} NOT NULL DEFAULT '', meta $txt,
            created_at {$vc(25)} NOT NULL DEFAULT '', updated_at {$vc(25)} NOT NULL DEFAULT '',
            UNIQUE (type, slug))$tail";
        $q[] = "CREATE TABLE IF NOT EXISTS pp_entry_tr (
            entry_id INTEGER NOT NULL, lang {$vc(10)} NOT NULL, title {$vc(255)} NOT NULL DEFAULT '',
            excerpt $txt, body $txt, src_hash {$vc(40)} NOT NULL DEFAULT '', auto INTEGER NOT NULL DEFAULT 0,
            updated_at {$vc(25)} NOT NULL DEFAULT '', PRIMARY KEY (entry_id, lang))$tail";
        $q[] = "CREATE TABLE IF NOT EXISTS pp_docs (
            id $id, code {$vc(40)} NOT NULL DEFAULT '', category {$vc(40)} NOT NULL DEFAULT 'deliverable',
            wp {$vc(20)} NOT NULL DEFAULT '', pub_date {$vc(25)} NOT NULL DEFAULT '', file {$vc(255)} NOT NULL DEFAULT '',
            status {$vc(20)} NOT NULL DEFAULT 'draft', sort INTEGER NOT NULL DEFAULT 0,
            created_at {$vc(25)} NOT NULL DEFAULT '', updated_at {$vc(25)} NOT NULL DEFAULT '')$tail";
        $q[] = "CREATE TABLE IF NOT EXISTS pp_doc_files (
            doc_id INTEGER NOT NULL, lang {$vc(10)} NOT NULL, file {$vc(255)} NOT NULL,
            PRIMARY KEY (doc_id, lang))$tail";
        $q[] = "CREATE TABLE IF NOT EXISTS pp_doc_tr (
            doc_id INTEGER NOT NULL, lang {$vc(10)} NOT NULL, title {$vc(255)} NOT NULL DEFAULT '',
            excerpt $txt, body $txt, src_hash {$vc(40)} NOT NULL DEFAULT '', auto INTEGER NOT NULL DEFAULT 0,
            updated_at {$vc(25)} NOT NULL DEFAULT '', PRIMARY KEY (doc_id, lang))$tail";
        $q[] = "CREATE TABLE IF NOT EXISTS pp_strings (
            skey {$vc(120)} NOT NULL, lang {$vc(10)} NOT NULL, value $txt,
            src_hash {$vc(40)} NOT NULL DEFAULT '', auto INTEGER NOT NULL DEFAULT 0,
            updated_at {$vc(25)} NOT NULL DEFAULT '', PRIMARY KEY (skey, lang))$tail";
        $q[] = "CREATE TABLE IF NOT EXISTS pp_log (
            id $id, user_email {$vc(190)} NOT NULL DEFAULT '', action {$vc(60)} NOT NULL,
            ref {$vc(190)} NOT NULL DEFAULT '', at {$vc(25)} NOT NULL DEFAULT '')$tail";
        $q[] = "CREATE TABLE IF NOT EXISTS pp_settings (
            k {$vc(120)} NOT NULL PRIMARY KEY, v $txt)$tail";
        $q[] = "CREATE TABLE IF NOT EXISTS pp_seo (
            ref {$vc(160)} NOT NULL, lang {$vc(10)} NOT NULL, title {$vc(255)} NOT NULL DEFAULT '',
            description {$vc(400)} NOT NULL DEFAULT '', updated_at {$vc(25)} NOT NULL DEFAULT '',
            PRIMARY KEY (ref, lang))$tail";
        foreach ($q as $sql) self::pdo()->exec($sql);
        self::addColumn('pp_users', 'totp_secret', $vc(64) . " NOT NULL DEFAULT ''");
        self::addColumn('pp_entry_tr', 'image_alt', $vc(300) . " NOT NULL DEFAULT ''");
        // 1.2: slug per lingua, campi traducibili extra, programmazione, autori, ruoli per lingua
        self::addColumn('pp_entry_tr', 'slug', $vc(190) . " NOT NULL DEFAULT ''");
        self::addColumn('pp_entry_tr', 'extra', $txt);
        self::addColumn('pp_entries', 'publish_at', $vc(25) . " NOT NULL DEFAULT ''");
        self::addColumn('pp_entries', 'unpublish_at', $vc(25) . " NOT NULL DEFAULT ''");
        self::addColumn('pp_entries', 'author_id', "INTEGER NOT NULL DEFAULT 0");
        self::addColumn('pp_docs', 'author_id', "INTEGER NOT NULL DEFAULT 0");
        self::addColumn('pp_users', 'langs', $vc(120) . " NOT NULL DEFAULT ''");
        $more = [
            "CREATE TABLE IF NOT EXISTS pp_revisions (id $id, kind {$vc(20)} NOT NULL, ref_id INTEGER NOT NULL, lang {$vc(10)} NOT NULL DEFAULT '',
                user_email {$vc(190)} NOT NULL DEFAULT '', at {$vc(25)} NOT NULL DEFAULT '', data $txt)$tail",
            "CREATE TABLE IF NOT EXISTS pp_redirects (id $id, from_path {$vc(255)} NOT NULL UNIQUE, to_path {$vc(255)} NOT NULL,
                code INTEGER NOT NULL DEFAULT 301, hits INTEGER NOT NULL DEFAULT 0, auto INTEGER NOT NULL DEFAULT 0, created_at {$vc(25)} NOT NULL DEFAULT '')$tail",
            "CREATE TABLE IF NOT EXISTS pp_tokens (id $id, user_id INTEGER NOT NULL, hash {$vc(64)} NOT NULL, expires INTEGER NOT NULL, used INTEGER NOT NULL DEFAULT 0)$tail",
            "CREATE TABLE IF NOT EXISTS pp_form_entries (id $id, form {$vc(40)} NOT NULL, lang {$vc(10)} NOT NULL DEFAULT '', data $txt,
                at {$vc(25)} NOT NULL DEFAULT '', is_read INTEGER NOT NULL DEFAULT 0)$tail",
        ];
        foreach ($more as $sql) self::pdo()->exec($sql);
    }

    /** Elenco delle colonne di una tabella. */
    public static function columns(string $table): array
    {
        if (self::$driver === 'sqlite') return array_column(self::all("PRAGMA table_info($table)"), 'name');
        return array_column(self::all("SHOW COLUMNS FROM $table"), 'Field');
    }

    /** Aggiunge una colonna se manca (usato dalle migrazioni del core e dei moduli). */
    public static function addColumn(string $table, string $col, string $def): void
    {
        if (!in_array($col, self::columns($table), true)) self::pdo()->exec("ALTER TABLE $table ADD COLUMN $col $def");
    }

    /** Tipi portabili per le migrazioni dei moduli. */
    public static function t(string $kind, int $len = 255): string
    {
        $sqlite = self::$driver === 'sqlite';
        return match ($kind) {
            'id' => $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY',
            'text' => $sqlite ? 'TEXT' : 'MEDIUMTEXT',
            'string' => $sqlite ? 'TEXT' : "VARCHAR($len)",
            'int' => 'INTEGER',
            'tail' => $sqlite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            default => 'TEXT',
        };
    }
}
