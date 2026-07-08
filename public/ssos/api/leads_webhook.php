<?php
declare(strict_types=1);

/**
 * ATHLOS SSOS v1.0 — API WEBHOOK "FRONTDESK"
 *
 * Endpoint de ingesta de leads desde el frontend Next.js / motor conversacional.
 * REGLA DE PIEDRA (Consent Gate): ningún dato de salud/objetivo se persiste sin
 * `consentimiento_legal === true` explícito en el payload.
 *
 * Auth: cabecera X-Athlos-Api-Key (llamadas servidor-a-servidor) O Origin
 * dentro de ALLOWED_ORIGINS (formulario público de la landing Next.js, que
 * al ser un export estático no puede guardar secretos — ver
 * api_require_key_or_allowed_origin() en config/helpers.php).
 *
 * POST /ssos/api/leads_webhook.php
 * Headers: Content-Type: application/json[, X-Athlos-Api-Key: <secreto>]
 * Body: {
 *   "nombre_completo": "Juan Pérez",
 *   "telefono": "6121234567",
 *   "objetivo_salud": "Bajar % de grasa y mejorar rendimiento en ciclismo",
 *   "consentimiento_legal": true,
 *   "canal_origen": "whatsapp",   // opcional: whatsapp|instagram|facebook (default whatsapp)
 *   "email": "juan@correo.com"    // opcional
 * }
 */

require_once __DIR__ . '/../config/helpers.php';

api_apply_cors();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    api_respond(405, 'error', ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'Usa POST.']);
}

api_require_key_or_allowed_origin();

$data = api_json_input();

$nombre_completo = trim((string) ($data['nombre_completo'] ?? ''));
$telefono_raw = (string) ($data['telefono'] ?? '');
$objetivo_salud = trim((string) ($data['objetivo_salud'] ?? ''));
$consentimiento_legal = $data['consentimiento_legal'] ?? null;
$canal_origen = (string) ($data['canal_origen'] ?? 'whatsapp');
$email = isset($data['email']) ? trim((string) $data['email']) : null;

// ─── REGLA DE PIEDRA: Consent Gate ─────────────────────────────────────────
if ($consentimiento_legal !== true) {
    api_respond(403, 'error', [
        'code' => 'LEGAL_PRIVACY_VIOLATION',
        'message' => 'No se puede procesar el lead sin consentimiento_legal explícito (true).',
    ]);
}

// ─── Validación de campos ──────────────────────────────────────────────────
$errores = [];

if ($nombre_completo === '' || mb_strlen($nombre_completo) > 150) {
    $errores[] = 'nombre_completo es obligatorio (máximo 150 caracteres).';
}

$telefono = ssos_normalize_phone($telefono_raw);
if ($telefono === null) {
    $errores[] = 'telefono es obligatorio y debe ser un número válido.';
}

if ($email !== null && $email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errores[] = 'email no tiene un formato válido.';
}

if (!in_array($canal_origen, ['whatsapp', 'instagram', 'facebook'], true)) {
    $canal_origen = 'whatsapp';
}

if (!empty($errores)) {
    api_respond(422, 'error', ['code' => 'VALIDATION_ERROR', 'errors' => $errores]);
}

// ─── Deduplicación por teléfono + upsert ────────────────────────────────────
$db = ssos_db();

$stmt = $db->prepare('SELECT id_lead FROM leads_prospectos WHERE telefono = :telefono LIMIT 1');
$stmt->execute(['telefono' => $telefono]);
$existente = $stmt->fetch();

try {
    if ($existente) {
        $id_lead = (int) $existente['id_lead'];
        $stmt = $db->prepare(
            'UPDATE leads_prospectos
             SET objetivo_declarado = :objetivo,
                 consent_gate_status = \'aceptado\',
                 consent_timestamp = NOW(),
                 nombre_completo = :nombre,
                 email = COALESCE(:email, email),
                 canal_origen = :canal
             WHERE id_lead = :id'
        );
        $stmt->execute([
            'objetivo' => $objetivo_salud,
            'nombre'   => $nombre_completo,
            'email'    => $email,
            'canal'    => $canal_origen,
            'id'       => $id_lead,
        ]);
        $accion = 'updated';
    } else {
        $stmt = $db->prepare(
            'INSERT INTO leads_prospectos
                (nombre_completo, telefono, email, canal_origen, objetivo_declarado,
                 consent_gate_status, consent_timestamp, estatus_lead)
             VALUES
                (:nombre, :telefono, :email, :canal, :objetivo, \'aceptado\', NOW(), \'nuevo\')'
        );
        $stmt->execute([
            'nombre'    => $nombre_completo,
            'telefono'  => $telefono,
            'email'     => $email,
            'canal'     => $canal_origen,
            'objetivo'  => $objetivo_salud,
        ]);
        $id_lead = (int) $db->lastInsertId();
        $accion = 'created';
    }
} catch (\Throwable $e) {
    error_log('[SSOS leads_webhook] ' . $e->getMessage());
    api_respond(500, 'error', ['code' => 'SERVER_ERROR', 'message' => 'No se pudo procesar el lead.']);
}

api_respond($accion === 'created' ? 201 : 200, 'success', [
    'action' => $accion,
    'id_lead' => $id_lead,
    'consent_gate_status' => 'aceptado',
]);
