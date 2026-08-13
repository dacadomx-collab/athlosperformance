<?php
declare(strict_types=1);

/**
 * ATHLOS SSOS v1.0 — API PÚBLICA DE TESTIMONIOS (Casos de Éxito)
 *
 * GET /ssos/api/testimonios_publicos.php
 * Sin autenticación: devuelve únicamente los testimonios con estatus
 * 'activo', para alimentar el carrusel de la Landing Page (Next.js, sitio
 * 100% estático — ver lib/ssos-client.ts para el mismo criterio de acceso
 * sin API key). Es lectura pública por diseño (reseñas ya publicadas en el
 * sitio), así que no aplica `api_require_key_or_allowed_origin()` — sólo
 * CORS abierto vía `api_apply_cors()` para que cualquier origen del
 * navegador pueda leerlo.
 */

require_once __DIR__ . '/../config/helpers.php';

api_apply_cors();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    api_respond(405, 'error', ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'Usa GET.']);
}

$db = ssos_db();

$testimonios = $db->query(
    'SELECT id_testimonio, nombre_cliente, comentario, foto_ruta, fecha_testimonio
     FROM testimonios_clientes
     WHERE estatus = "activo"
     ORDER BY orden_visualizacion ASC, fecha_testimonio DESC'
)->fetchAll();

$data = array_map(static function (array $t): array {
    return [
        'id_testimonio' => (int) $t['id_testimonio'],
        'nombre_cliente' => $t['nombre_cliente'],
        'comentario' => $t['comentario'],
        'foto_url' => $t['foto_ruta'] ? ssos_asset_media($t['foto_ruta']) : null,
        'fecha_testimonio' => substr($t['fecha_testimonio'], 0, 10),
    ];
}, $testimonios);

api_respond(200, 'success', ['testimonios' => $data]);
