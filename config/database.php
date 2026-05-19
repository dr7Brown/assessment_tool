<?php
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if (str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        putenv(trim($k) . '=' . trim($v));
    }
}
define('DB_HOST',     getenv('DB_HOST')     ?: 'localhost');
define('DB_PORT',     getenv('DB_PORT')     ?: '3306');
define('DB_NAME',     getenv('DB_NAME')     ?: 'royayfxh_aceict');
define('DB_USER',     getenv('DB_USER')     ?: 'royayfxh_aceict');
define('DB_PASS',     getenv('DB_PASS')     ?: 'mucGe0L7H21_');
define('DB_CHARSET',  'utf8mb4');
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'change_this_secret_minimum_32_chars');
define('JWT_EXPIRY', 60 * 60 * 24 * 7);
define('APP_ENV',    getenv('APP_ENV')    ?: 'production');
define('API_VERSION','v1');
define('CORS_ORIGIN','*');
define('ANTHROPIC_API_KEY', getenv('ANTHROPIC_API_KEY') ?: '');

class DB {
    private static ?PDO $pdo = null;
    public static function connect(): PDO {
        if (self::$pdo) return self::$pdo;
        $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=".DB_CHARSET;
        try {
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(503);
            die(json_encode(['success'=>false,'error'=>'DB failed: '.$e->getMessage()]));
        }
        return self::$pdo;
    }
    public static function query(string $sql, array $p=[]): PDOStatement { $s=self::connect()->prepare($sql); $s->execute($p); return $s; }
    public static function fetchOne(string $sql, array $p=[]): ?array { $r=self::query($sql,$p)->fetch(); return $r?:null; }
    public static function fetchAll(string $sql, array $p=[]): array { return self::query($sql,$p)->fetchAll(); }
    public static function insert(string $sql, array $p=[]): int { self::query($sql,$p); return (int)self::connect()->lastInsertId(); }
    public static function execute(string $sql, array $p=[]): int { return self::query($sql,$p)->rowCount(); }
    public static function beginTransaction(): void { self::connect()->beginTransaction(); }
    public static function commit(): void { self::connect()->commit(); }
    public static function rollback(): void { self::connect()->rollBack(); }
}
