<?php
declare(strict_types=1);

/**
 * ATHLOS SSOS v1.0 — CONEXIÓN PDO CENTRALIZADA DEL BACKOFFICE
 *
 * Punto único de acceso a la base de datos para toda la app /ssos.
 * ÚNICA fuente de verdad de variables de entorno del proyecto: `core/.env`
 * (ya NO existe un .env separado dentro de public/ssos/ — unificado por
 * directriz del 2026-07-08). En producción, ese `core/.env` se coloca
 * manualmente en el servidor (fuera de git), igual que ya se documentó para
 * `api/conexion.php`.
 *
 * CERTIFICACIÓN DE CONEXIÓN REMOTA: `DB_HOST` en `core/.env` es "localhost"
 * — el valor CORRECTO para cuando este código se ejecuta en el propio
 * servidor de producción (hosting compartido: sólo acepta conexiones a su
 * propia base de datos vía socket/localhost, es la config más segura y de
 * menor latencia). Para poder desarrollar/probar esta misma app localmente
 * SIN mantener un segundo archivo .env (prohibido por la directriz de
 * unificación), `ssos_db()` intenta primero `DB_HOST` tal cual; si falla Y
 * el valor era "localhost", reintenta automáticamente contra el host público
 * derivado de `APP_URL` (ej. `athlosperformance.tourfindy.com`) — verificado
 * manualmente que ese host acepta conexiones remotas en el puerto 3306 con
 * estas credenciales. Así el mismo `core/.env`, sin modificar, conecta
 * correctamente tanto en el servidor real (usa "localhost" directo) como en
 * una máquina de desarrollo (cae al host público automáticamente).
 */

// ─── Cargador de .env (formato INI) — ÚNICA fuente: core/.env ─────────────

(static function (): void {
    $env_path = __DIR__ . '/../../../core/.env';

    if (!file_exists($env_path)) {
        http_response_code(500);
        die('Error de configuración del servidor: falta core/.env (única fuente de verdad de variables de entorno).');
    }

    $config = parse_ini_file($env_path, true, INI_SCANNER_TYPED);

    if ($config === false) {
        http_response_code(500);
        die('Error de configuración del servidor: core/.env ilegible.');
    }

    foreach ($config as $section) {
        foreach ($section as $key => $value) {
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = (string) $value;
            }
        }
    }
})();

// ─── Singleton PDO con certificación de conexión ───────────────────────────

/**
 * Devuelve la instancia singleton de PDO, certificando que la comunicación
 * con la base de datos real del servidor (`tourfindycom_athlosp_db`) esté
 * activa antes de devolverla. Si ni el host primario (localhost) ni el
 * fallback remoto responden, lanza RuntimeException con un mensaje claro —
 * nunca deja al caller con una conexión a medias o un error ambiguo.
 */
function ssos_db(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $port    = $_ENV['DB_PORT'] ?? '3306';
    $db_name = $_ENV['DB_NAME'] ?? '';
    $user    = $_ENV['DB_USER'] ?? 'root';
    $pass    = $_ENV['DB_PASS'] ?? '';

    if (empty($db_name)) {
        throw new \RuntimeException('DB_NAME no configurado en core/.env');
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 5,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];

    $hostPrimario = $_ENV['DB_HOST'] ?? 'localhost';
    $intentos = [$hostPrimario];

    if ($hostPrimario === 'localhost') {
        $hostPublico = parse_url($_ENV['APP_URL'] ?? '', PHP_URL_HOST);
        if ($hostPublico) {
            $intentos[] = $hostPublico;
        }
    }

    $ultimoError = null;

    foreach (array_unique($intentos) as $host) {
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$db_name};charset=utf8mb4";
            $candidato = new PDO($dsn, $user, $pass, $options);

            // Certificación: la conexión abierta debe poder ejecutar una query real.
            $candidato->query('SELECT 1');

            $pdo = $candidato;
            return $pdo;
        } catch (\Throwable $e) {
            $ultimoError = $e;
        }
    }

    throw new \RuntimeException(
        'No se pudo establecer ni certificar la conexión con la base de datos real '
        . "({$db_name}) en ninguno de los hosts probados (" . implode(', ', array_unique($intentos)) . '). '
        . 'Último error: ' . $ultimoError?->getMessage()
    );
}
