<?php
declare(strict_types=1);

/**
 * ATHLOS COGNITIVE ENGINE v1.0 — CONEXIÓN PDO CENTRALIZADA
 *
 * Punto único de acceso a la base de datos.
 * Toda query del sistema pasa por getDB(). Cero conexiones directas fuera de este archivo.
 * Lee credenciales exclusivamente del .env — nunca hardcodeadas.
 */

// ─── Cargador de .env ─────────────────────────────────────────────────────────

(static function (): void {
    $env_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';

    if (!file_exists($env_path)) {
        // En producción un .env ausente es un error fatal de configuración.
        if (getenv('APP_ENV') !== 'local') {
            http_response_code(500);
            die(json_encode(['status' => 'error', 'message' => 'Error de configuración del servidor.']));
        }
        return;
    }

    $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        // Strip inline comments (e.g. KEY=value # comment)
        if (($comment_pos = strpos($value, ' #')) !== false) {
            $value = trim(substr($value, 0, $comment_pos));
        }
        // Strip surrounding quotes
        if (
            strlen($value) >= 2 &&
            (($value[0] === '"' && $value[-1] === '"') || ($value[0] === "'" && $value[-1] === "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
})();


// ─── Singleton PDO ────────────────────────────────────────────────────────────

/**
 * Devuelve la instancia singleton de PDO.
 * Lanza PDOException si la conexión falla — el caller debe manejarla.
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $host    = $_ENV['DB_HOST'] ?? 'localhost';
    $port    = $_ENV['DB_PORT'] ?? '3306';
    $db_name = $_ENV['DB_NAME'] ?? '';
    $user    = $_ENV['DB_USER'] ?? 'root';
    $pass    = $_ENV['DB_PASS'] ?? '';

    if (empty($db_name)) {
        throw new \RuntimeException('DB_NAME no configurado en .env');
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$db_name};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,       // Prepared statements reales, no emulados
        PDO::ATTR_PERSISTENT         => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);

    return $pdo;
}
