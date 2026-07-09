<?php
declare(strict_types=1);

/**
 * ATHLOS PERFORMANCE — FEED WEBCAL (.ics) DE SÓLO LECTURA PARA APPLE CALENDAR
 *
 * Endpoint PÚBLICO (sin sesión) — la autenticación es "posesión de la URL":
 * `?token=` debe coincidir con sincronizacion_tokens.webcal_uid de un coach
 * con proveedor='apple_calendar' activo. No requiere credenciales OAuth
 * (a diferencia de Google Calendar) porque es un modelo de sólo lectura: el
 * cliente de Apple Calendar vuelve a descargar este archivo periódicamente
 * (cada 15min-24h, decisión del cliente, no configurable desde el servidor).
 *
 * Ver arquitectura completa (formato RFC 5545, reglas de plegado de línea,
 * por qué no requiere OAuth) en knowledge/MODULO_CALENDARIO_GENERICO.md §3.2.
 *
 * Depende de la tabla `sincronizacion_tokens` (knowledge/sql/06_schema_calendario_avanzado.sql,
 * migración pendiente de aplicar en producción al momento de escribir esto)
 * — si no existe, responde 503 con instrucciones en vez de un error fatal.
 */

require_once __DIR__ . '/../config/conexion.php';

$token = (string) ($_GET['token'] ?? '');
if ($token === '') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    die('Falta el parámetro token.');
}

$db = ssos_db();

try {
    $stmt = $db->prepare(
        "SELECT st.id_staff, s.nombre_completo
         FROM sincronizacion_tokens st
         INNER JOIN staff s ON s.id_staff = st.id_staff
         WHERE st.webcal_uid = :token AND st.proveedor = 'apple_calendar' AND st.activo = 1"
    );
    $stmt->execute(['token' => $token]);
    $coach = $stmt->fetch();
} catch (\Throwable $e) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    die(
        "El módulo de sincronización todavía no está activado en esta instalación.\n"
        . 'Aplica knowledge/sql/06_schema_calendario_avanzado.sql y genera un webcal_uid para el coach.'
    );
}

if (!$coach) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    die('Token de sincronización inválido o inactivo.');
}

$stmtCitas = $db->prepare(
    "SELECT da.id_cita, da.fecha_cita, da.hora_inicio, da.hora_fin, da.estatus_cita, da.updated_at, da.created_at,
            COALESCE(a.nombre_completo, da.notas_previas, 'Prospecto') AS nombre_cliente,
            cs.nombre_servicio
     FROM disponibilidad_agenda da
     LEFT JOIN atletas a ON a.id_atleta = da.id_atleta
     INNER JOIN catalogo_servicios cs ON cs.id_servicio = da.id_servicio
     WHERE da.id_staff = :id_staff
       AND da.estatus_cita IN ('reservada', 'confirmada')
       AND da.fecha_cita >= (CURDATE() - INTERVAL 7 DAY)
     ORDER BY da.fecha_cita, da.hora_inicio"
);
$stmtCitas->execute(['id_staff' => $coach['id_staff']]);
$citas = $stmtCitas->fetchAll();

/** RFC 5545 §3.3.11: escapa coma, punto y coma, backslash y saltos de línea. */
function ics_escapar(string $texto): string
{
    $texto = str_replace(['\\', ',', ';'], ['\\\\', '\\,', '\\;'], $texto);
    return str_replace(["\r\n", "\n"], '\\n', $texto);
}

/** RFC 5545 §3.1: las líneas de más de 75 octetos se pliegan con CRLF + un espacio. */
function ics_plegar(string $linea): string
{
    if (strlen($linea) <= 75) {
        return $linea;
    }
    $partes = [];
    while (strlen($linea) > 75) {
        $partes[] = substr($linea, 0, 75);
        $linea = ' ' . substr($linea, 75);
    }
    $partes[] = $linea;
    return implode("\r\n", $partes);
}

function ics_fecha_utc(string $fecha, string $hora): string
{
    $dt = new DateTimeImmutable("{$fecha} {$hora}", new DateTimeZone('America/Mazatlan'));
    return $dt->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
}

$lineas = [];
$lineas[] = 'BEGIN:VCALENDAR';
$lineas[] = 'VERSION:2.0';
$lineas[] = 'PRODID:-//Athlos Performance//Agenda//ES';
$lineas[] = 'CALSCALE:GREGORIAN';
$lineas[] = 'METHOD:PUBLISH';
$lineas[] = ics_plegar('X-WR-CALNAME:Agenda de ' . ics_escapar($coach['nombre_completo']));
$lineas[] = 'REFRESH-INTERVAL;VALUE=DURATION:PT15M';

$estatusIcs = ['confirmada' => 'CONFIRMED', 'reservada' => 'TENTATIVE'];

foreach ($citas as $cita) {
    $lineas[] = 'BEGIN:VEVENT';
    $lineas[] = 'UID:' . (int) $cita['id_cita'] . '@athlosperformance.tourfindy.com';
    $lineas[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z', strtotime((string) ($cita['updated_at'] ?? $cita['created_at'])));
    $lineas[] = 'DTSTART:' . ics_fecha_utc((string) $cita['fecha_cita'], (string) $cita['hora_inicio']);
    $lineas[] = 'DTEND:' . ics_fecha_utc((string) $cita['fecha_cita'], (string) $cita['hora_fin']);
    $lineas[] = ics_plegar('SUMMARY:' . ics_escapar($cita['nombre_servicio'] . ' — ' . $cita['nombre_cliente']));
    $lineas[] = 'STATUS:' . ($estatusIcs[$cita['estatus_cita']] ?? 'TENTATIVE');
    $lineas[] = 'END:VEVENT';
}

$lineas[] = 'END:VCALENDAR';

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="agenda.ics"');
echo implode("\r\n", $lineas) . "\r\n";
