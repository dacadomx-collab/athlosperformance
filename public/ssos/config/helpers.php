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

// =============================================================================
// HELPERS DE API (Fase 5 — Webhook FrontDesk y endpoints JSON internos)
// =============================================================================

/**
 * Normaliza un teléfono a dígitos puros con código de país (patrón ya usado
 * en api/webhook_mensajeria.php: normalize_phone()). Antepone 52 (México) si
 * el número no trae código de país y tiene longitud de 10 dígitos.
 */
function ssos_normalize_phone(string $raw): ?string
{
    $digits = preg_replace('/\D+/', '', $raw) ?? '';

    if ($digits === '') {
        return null;
    }

    if (strlen($digits) === 10) {
        $digits = '52' . $digits;
    }

    if (strlen($digits) < 10 || strlen($digits) > 15) {
        return null;
    }

    return $digits;
}

/**
 * Aplica cabeceras CORS según ALLOWED_ORIGINS del .env y responde de
 * inmediato a peticiones OPTIONS (preflight).
 */
function api_apply_cors(): void
{
    $allowed = array_map('trim', explode(',', $_ENV['ALLOWED_ORIGINS'] ?? ''));
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if ($origin !== '' && in_array($origin, $allowed, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    }

    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Athlos-Api-Key');

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

/** Envía una respuesta JSON estandarizada y termina la ejecución. */
function api_respond(int $http_code, string $status, array $data = []): never
{
    http_response_code($http_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['status' => $status], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Exige una API key válida en la cabecera X-Athlos-Api-Key (contra
 * API_WEBHOOK_SECRET del .env). Responde 401 y termina si falta o no coincide.
 */
function api_require_key(): void
{
    $secreto = $_ENV['API_WEBHOOK_SECRET'] ?? '';
    $recibido = $_SERVER['HTTP_X_ATHLOS_API_KEY'] ?? '';

    if ($secreto === '' || $recibido === '' || !hash_equals($secreto, $recibido)) {
        api_respond(401, 'error', ['code' => 'UNAUTHORIZED', 'message' => 'API key inválida o ausente.']);
    }
}

/**
 * Autoriza la petición por API key (servidor-a-servidor) O por Origin
 * permitido (navegador). El sitio Next.js se exporta 100% estático
 * (`output: "export"`, sin runtime de servidor) — cualquier secreto
 * embebido en su bundle es público por definición (visible en el código
 * fuente descargado por cualquier visitante), así que NO puede exigirle
 * X-Athlos-Api-Key a ese cliente. Para peticiones de navegador, el propio
 * navegador ya garantiza el header Origin real (no falsificable vía JS);
 * confiamos en ALLOWED_ORIGINS como frontera de confianza para ese canal.
 * Un cliente no-navegador que falsifique el header Origin queda en el
 * mismo nivel de exposición que cualquier formulario público de contacto
 * — mitigado por el Consent Gate + validación de campos, no por secreto.
 */
function api_require_key_or_allowed_origin(): void
{
    $allowed = array_map('trim', explode(',', $_ENV['ALLOWED_ORIGINS'] ?? ''));
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if ($origin !== '' && in_array($origin, $allowed, true)) {
        return;
    }

    api_require_key();
}

/** Decodifica el body JSON de la petición actual; responde 400 si es inválido. */
function api_json_input(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        api_respond(400, 'error', ['code' => 'INVALID_JSON', 'message' => 'El body debe ser un JSON válido.']);
    }

    return $data;
}

// =============================================================================
// TOKENS DE COMPARTICIÓN (Fase 6 — Reporte público del Athlos Score™)
// =============================================================================

/**
 * Genera un token firmado (HMAC-SHA256, sin estado en DB) para compartir el
 * reporte público de un atleta sin requerir login. El reporte contiene datos
 * clínicos/composición corporal — REGLA-01 exige que nunca sea adivinable
 * (por eso no es simplemente el id_atleta en la URL) ni de vigencia indefinida.
 */
function ssos_generate_share_token(int $id_atleta, int $ttlHoras = 72): string
{
    $payload = json_encode(['id' => $id_atleta, 'exp' => time() + $ttlHoras * 3600]);
    $payloadB64 = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    $secreto = $_ENV['HMAC_SECRET'] ?? '';
    $firma = hash_hmac('sha256', $payloadB64, $secreto);

    return $payloadB64 . '.' . $firma;
}

/** Verifica un token de reporte; devuelve el id_atleta si es válido y no expiró, o null. */
function ssos_verify_share_token(string $token): ?int
{
    $partes = explode('.', $token, 2);
    if (count($partes) !== 2) {
        return null;
    }

    [$payloadB64, $firmaRecibida] = $partes;
    $secreto = $_ENV['HMAC_SECRET'] ?? '';
    $firmaEsperada = hash_hmac('sha256', $payloadB64, $secreto);

    if ($secreto === '' || !hash_equals($firmaEsperada, $firmaRecibida)) {
        return null;
    }

    $payload = json_decode(base64_decode(strtr($payloadB64, '-_', '+/')) ?: '', true);
    if (!is_array($payload) || !isset($payload['id'], $payload['exp'])) {
        return null;
    }

    if ((int) $payload['exp'] < time()) {
        return null;
    }

    return (int) $payload['id'];
}
