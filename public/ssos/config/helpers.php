<?php
declare(strict_types=1);

/**
 * ATHLOS SSOS v1.0 — HELPERS COMUNES DEL BACKOFFICE
 * Sesión segura, CSRF, escape de salida y guardas de acceso por rol.
 */

require_once __DIR__ . '/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => ($_ENV['APP_ENV'] ?? 'local') !== 'local',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** Escapa salida HTML para prevenir XSS. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Genera (o reutiliza) el token CSRF de la sesión actual. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Valida el token CSRF recibido por POST. */
function csrf_validate(?string $token): bool
{
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/** URL base de la app /ssos, sin barra final. */
function ssos_base_url(): string
{
    return rtrim($_ENV['APP_URL'] ?? '', '/');
}

/** Redirige al dashboard correspondiente según clave_rol y termina la ejecución. */
function redirect_to_dashboard(string $clave_rol): never
{
    $destino = match ($clave_rol) {
        'super_admin' => '/dashboard/super_admin.php',
        'admin'       => '/dashboard/admin.php',
        'coach'       => '/dashboard/coach.php',
        default       => '/login.php',
    };
    header('Location: ' . ssos_base_url() . $destino);
    exit;
}

/** Exige sesión activa; si no hay, redirige a login.php (ruta absoluta, válida desde cualquier subcarpeta). */
function require_login(): void
{
    if (empty($_SESSION['id_usuario'])) {
        header('Location: ' . ssos_base_url() . '/login.php');
        exit;
    }
}

/**
 * Exige que el usuario en sesión tenga uno de los roles permitidos.
 * Debe llamarse DESPUÉS de require_login().
 */
function require_role(string ...$roles_permitidos): void
{
    require_login();
    if (!in_array($_SESSION['clave_rol'] ?? '', $roles_permitidos, true)) {
        http_response_code(403);
        die('Acceso denegado: tu rol no tiene permiso para ver esta página.');
    }
}

/** Registra un evento en sesiones_log (bitácora de auditoría de seguridad). */
function log_sesion_evento(?int $id_usuario, string $email_intento, string $tipo_evento): void
{
    $stmt = ssos_db()->prepare(
        'INSERT INTO sesiones_log (id_usuario, email_intento, tipo_evento, ip_origen, user_agent)
         VALUES (:id_usuario, :email, :tipo, :ip, :ua)'
    );
    $stmt->execute([
        'id_usuario' => $id_usuario,
        'email'      => $email_intento,
        'tipo'       => $tipo_evento,
        'ip'         => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        'ua'         => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
    ]);
}
