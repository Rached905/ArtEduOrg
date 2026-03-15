<?php
/**
 * Wait for MySQL to be reachable. Used by entrypoint.sh.
 * Usage: php .docker/wait-for-db.php
 * Requires DATABASE_URL in environment (e.g. mysql://user:pass@mysql:3306/dbname)
 */
$url = getenv('DATABASE_URL');
if (!$url || strpos($url, 'mysql:') !== 0) {
  exit(0); // no MySQL URL, skip wait
}
$params = parse_url($url);
$host = $params['host'] ?? 'mysql';
$port = $params['port'] ?? 3306;
$user = $params['user'] ?? 'root';
$pass = $params['pass'] ?? '';
$name = trim($params['path'] ?? '/arteduorg', '/');
$dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
try {
  new PDO($dsn, $user, $pass, [PDO::ATTR_TIMEOUT => 2]);
  exit(0);
} catch (Throwable $e) {
  exit(1);
}
