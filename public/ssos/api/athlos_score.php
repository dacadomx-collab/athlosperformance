<?php
declare(strict_types=1);

/**
 * ATHLOS SSOS v1.0 — API "ATHLOS SCORE™"
 *
 * Índice compuesto 0-100 (30% Fuerza/SFT, 30% Movilidad/Compensaciones,
 * 40% Composición/Grasa) listo para graficar como radar en el frontend o
 * en el propio BackOffice.
 *
 * GET /ssos/api/athlos_score.php?id_atleta=123
 * Auth: sesión activa del BackOffice (admin/coach/super_admin) O cabecera
 *       X-Athlos-Api-Key (para llamadas servidor-a-servidor desde Next.js).
 */

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/AthlosBusinessRules.php';

api_apply_cors();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    api_respond(405, 'error', ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'Usa GET.']);
}

$tiene_sesion_valida = !empty($_SESSION['id_usuario'])
    && in_array($_SESSION['clave_rol'] ?? '', ['admin', 'coach', 'super_admin'], true);

if (!$tiene_sesion_valida) {
    api_require_key();
}

$id_atleta = filter_input(INPUT_GET, 'id_atleta', FILTER_VALIDATE_INT) ?: null;
if ($id_atleta === null) {
    api_respond(422, 'error', ['code' => 'VALIDATION_ERROR', 'message' => 'id_atleta es obligatorio y debe ser numérico.']);
}

$db = ssos_db();

$stmt = $db->prepare('SELECT id_atleta, nombre_completo FROM atletas WHERE id_atleta = :id');
$stmt->execute(['id' => $id_atleta]);
$atleta = $stmt->fetch();

if (!$atleta) {
    api_respond(404, 'error', ['code' => 'NOT_FOUND', 'message' => 'Atleta no encontrado.']);
}

$score = AthlosBusinessRules::generarAthlosScore($db, $id_atleta);

api_respond(200, 'success', [
    'atleta' => ['id_atleta' => (int) $atleta['id_atleta'], 'nombre_completo' => $atleta['nombre_completo']],
    'athlos_score' => $score['athlos_score'],
    'dimensiones' => $score['dimensiones'],
    'radar' => $score['radar'],
]);
