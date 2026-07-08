<?php
declare(strict_types=1);

/**
 * ATHLOS SSOS v1.0 — CONEXIÓN PDO CENTRALIZADA DEL BACKOFFICE
 *
 * Punto único de acceso a la base de datos para toda la app /ssos.
 * Lee credenciales exclusivamente de ".env" (formato INI) en esta carpeta —
 * nunca hardcodeadas. En producción, ese ".env" se coloca manualmente en el
 * servidor (fuera de git, igual que api/conexion.php) tras cada despliegue.
 */

// ─── Cargador de .env (formato INI) ────────────────────────────────────────

(static function (): void {
    $env_path = __DIR__ . '/../.env';

    if (!file_exists($env_path)) {
        http_response_code(500);
        die('Error de configuración del servidor: falta el archivo .env de /ssos.');
    }

    $config = parse_ini_file($env_path, true, INI_SCANNER_TYPED);

    if ($config === false) {
        http_response_code(500);
        die('Error de configuración del servidor: .env de /ssos ilegible.');
    }

    foreach ($config as $section) {
        foreach ($section as $key => $value) {
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = (string) $value;
            }
        }
    }
})();

// ─── Singleton PDO ──────────────────────────────────────────────────────────

/**
 * Devuelve la instancia singleton de PDO.
 * Lanza PDOException si la conexión falla — el caller debe manejarla.
 */
function ssos_db(): PDO
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
        throw new \RuntimeException('DB_NAME no configurado en /ssos/.env');
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$db_name};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);

    return $pdo;
}
