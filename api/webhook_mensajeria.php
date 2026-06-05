<?php
declare(strict_types=1);

/**
 * ATHLOS COGNITIVE ENGINE v1.0 — WEBHOOK OMNICANAL
 *
 * Punto de entrada único para todos los eventos de mensajería:
 * WhatsApp Business API, Instagram DM y Facebook Messenger (Fase B).
 *
 * Flujo de ejecución (en orden estricto):
 *   1. Validación de firma Meta (X-Hub-Signature-256)
 *   2. Extracción y sanitización del payload
 *   3. Normalización de teléfono (Piedra-03)
 *   4. ── INTERCEPTOR DE RIESGO CLÍNICO ──  (P0 — detiene todo lo demás)
 *   5. Upsert del lead en DB (deduplicación omnicanal)
 *   6. ── MIDDLEWARE CONSENT GATE ──  (P0 — congela la IA libre si no hay consentimiento)
 *   7. Clasificación de intención por NLP (stub → Fase B reemplaza con LLM real)
 *   8. Construcción y envío de respuesta
 */

require_once __DIR__ . '/conexion.php';

// ─── Headers ──────────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ─── Constantes de operación ──────────────────────────────────────────────────

define('CONFIDENCE_THRESHOLD', (float)($_ENV['LLM_CONFIDENCE_THRESHOLD'] ?? 0.75));
define('IS_LOCAL', ($_ENV['APP_ENV'] ?? 'production') === 'local');

/**
 * Lista negra de disparadores de riesgo clínico.
 * Ante cualquier coincidencia: se congela la inferencia IA y se activa human handoff + audit log.
 * Directriz del Roadmap: P0 Crítico / Motor de Riesgo Clínico.
 */
const CLINICAL_RISK_TRIGGERS = [
    // Cardiovascular / respiratorio
    'dolor de pecho',       'dolor en el pecho',    'presion en el pecho',
    'presión en el pecho',  'dolor en el corazon',  'dolor en el corazón',
    'palpitaciones fuertes','falta de aire',         'no puedo respirar',
    'dificultad para respirar', 'me ahogo',          'ahogo',
    'latidos irregulares',  'taquicardia',

    // Neurológico
    'mareos',               'mareado',               'mareada',
    'desmayo',              'me desmaye',            'me desmayé',
    'perdida del conocimiento', 'pérdida del conocimiento',
    'pérdida de conciencia','convulsion',             'convulsión',
    'vision borrosa',       'visión borrosa',        'hormigueo severo',

    // Traumatológico grave
    'fractura',             'hueso roto',            'sangrado abundante',
    'herida abierta',       'dolor insoportable',    'dolor agudo severo',
    'inflamacion severa',   'inflamación severa',    'hinchazón severa',
    'paralisis',            'parálisis',             'no puedo mover',
    'no siento el brazo',   'no siento la pierna',

    // Emergencia general
    'emergencia',           'urgencia medica',       'urgencia médica',
    'necesito ambulancia',  'llama a emergencias',

    // Psicológico / crisis
    'me quiero morir',      'no quiero vivir',       'suicidio',
    'pensamientos suicidas',
];

const CONSENT_GATE_MSG =
    "¡Hola! 👋 Soy el asistente de *Athlos Performance*.\n\n" .
    "Para brindarte atención personalizada y guardar tu información de forma segura, " .
    "necesito tu autorización.\n\n" .
    "📋 *¿Aceptas nuestro Aviso de Privacidad y Términos de Uso?*\n\n" .
    "Responde *SÍ* para continuar con atención personalizada.\n" .
    "Responde *NO* si prefieres no compartir tus datos.\n\n" .
    "_Tu información es confidencial y jamás será compartida con terceros._";

const HUMAN_HANDOFF_MSG =
    "⚠️ He detectado que tu consulta requiere atención inmediata de nuestro equipo médico especializado.\n\n" .
    "*Un miembro de nuestro staff te contactará en los próximos minutos.*\n\n" .
    "Si estás ante una emergencia médica, llama al 🚨 *911* de inmediato.";

// ─── Router principal ─────────────────────────────────────────────────────────

match ($_SERVER['REQUEST_METHOD']) {
    'GET'  => handle_verification(),
    'POST' => handle_incoming_message(),
    default => respond(405, 'error', 'Método no permitido.'),
};

// ═══════════════════════════════════════════════════════════════════════════════
// HANDLERS
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * GET — Verificación inicial del webhook con Meta Developers.
 * Meta envía: ?hub.mode=subscribe&hub.verify_token=X&hub.challenge=Y
 */
function handle_verification(): void
{
    $mode      = $_GET['hub_mode']         ?? $_GET['hub.mode']         ?? '';
    $token     = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';
    $challenge = $_GET['hub_challenge']    ?? $_GET['hub.challenge']    ?? '';

    $expected_token = $_ENV['META_VERIFY_TOKEN'] ?? '';

    if ($mode === 'subscribe' && hash_equals($expected_token, $token)) {
        http_response_code(200);
        echo htmlspecialchars($challenge, ENT_QUOTES, 'UTF-8');
        exit;
    }

    respond(403, 'error', 'Verificación fallida. Token inválido.');
}

/**
 * POST — Procesamiento de mensajes entrantes.
 */
function handle_incoming_message(): void
{
    // ── 1. Leer body crudo ────────────────────────────────────────────────────
    $raw_body = (string)file_get_contents('php://input');

    if (empty($raw_body)) {
        respond(400, 'error', 'Payload vacío.');
    }

    // ── 2. Validar firma Meta ─────────────────────────────────────────────────
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    if (!validate_meta_signature($raw_body, $signature)) {
        respond(403, 'error', 'Firma inválida. Acceso denegado.');
    }

    // ── 3. Decodificar JSON ───────────────────────────────────────────────────
    $payload = json_decode($raw_body, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($payload)) {
        respond(422, 'error', 'JSON malformado o estructura inválida.');
    }

    // ── 4. Extraer datos del mensaje ──────────────────────────────────────────
    $msg = extract_message_data($payload);

    if ($msg === null) {
        // Evento no-mensaje (status, read receipt): Meta requiere HTTP 200
        respond(200, 'success', 'Evento de estado ignorado.');
    }

    $raw_phone    = $msg['phone'];
    $message_text = $msg['text'];
    $canal        = $msg['canal'];
    $sender_name  = $msg['name'];

    // ── 5. Normalizar teléfono (Piedra-03) ────────────────────────────────────
    $telefono = normalize_phone($raw_phone);

    if ($telefono === null) {
        respond(422, 'error', 'Número de teléfono inválido o irreconocible.');
    }

    // ══════════════════════════════════════════════════════════════════════════
    // P0 — INTERCEPTOR DE RIESGO CLÍNICO
    // Ejecuta ANTES de cualquier operación de DB o inferencia IA.
    // ══════════════════════════════════════════════════════════════════════════
    if (detect_clinical_risk($message_text)) {
        log_audit_event(
            id_lead: null,
            canal: $canal,
            fragmento: $message_text,
            capa: 'escalation',
            confianza: 0.00
        );
        send_platform_message($canal, $raw_phone, HUMAN_HANDOFF_MSG);
        notify_staff_emergency($telefono, $sender_name, $message_text);

        respond(200, 'success', 'Riesgo clínico interceptado. Human handoff activado.', [
            'riesgo_clinico' => true,
            'accion'         => 'human_handoff_emergencia',
        ]);
    }

    // ── 6. Upsert lead (deduplicación por teléfono) ───────────────────────────
    $lead = upsert_lead($telefono, $canal, $sender_name);

    // ══════════════════════════════════════════════════════════════════════════
    // P0 — MIDDLEWARE CONSENT GATE
    // Si no hay consentimiento: congelar IA libre, mostrar solo flujo legal.
    // ══════════════════════════════════════════════════════════════════════════
    if ($lead['consent_gate_status'] !== 'aceptado') {
        $decision = parse_consent_response($message_text);

        if ($decision !== null) {
            // El usuario acaba de responder al Consent Gate
            register_consent($lead['id_lead'], $decision);

            $reply = ($decision === 'aceptado')
                ? "✅ ¡Perfecto! Tu consentimiento ha sido registrado de forma segura.\n\n" .
                  "¿En qué podemos ayudarte hoy? Cuéntanos tu objetivo:\n\n" .
                  "• 🏆 *Rendimiento deportivo*\n" .
                  "• 🔧 *Rehabilitación / lesión*\n" .
                  "• ⚖️ *Composición corporal*"
                : "Entendemos tu decisión, *{$sender_name}*. No guardaremos ningún dato tuyo. " .
                  "Si en algún momento cambias de opinión, aquí estaremos. 🙏";

            send_platform_message($canal, $raw_phone, $reply);

            respond(200, 'success', 'Decisión de Consent Gate registrada.', [
                'leadId'            => $lead['id_lead'],
                'consentGateStatus' => $decision,
            ]);
        }

        // Todavía no hay respuesta al Consent Gate → enviar y congelar
        send_platform_message($canal, $raw_phone, CONSENT_GATE_MSG);

        respond(200, 'success', 'Consent Gate activado. IA libre congelada hasta respuesta.', [
            'leadId'            => $lead['id_lead'],
            'consentGateStatus' => 'pendiente',
            'accion'            => 'consent_gate_enviado',
        ]);
    }

    // ── 7. Clasificar intención (NLP stub — Fase B reemplaza con LLM real) ────
    $nlp = classify_intent_stub($message_text);
    update_lead_from_nlp($lead['id_lead'], $nlp, $message_text);

    // Confianza baja → human handoff (Confidence Gate — Piedra-06)
    if ($nlp['confianza_nlp'] < CONFIDENCE_THRESHOLD) {
        log_audit_event(
            id_lead: (int)$lead['id_lead'],
            canal: $canal,
            fragmento: $message_text,
            capa: 'confidence_gate',
            confianza: $nlp['confianza_nlp']
        );
        $reply = build_low_confidence_response($sender_name);
        send_platform_message($canal, $raw_phone, $reply);

        respond(200, 'success', 'Confianza NLP insuficiente. Human handoff activado.', [
            'leadId'          => $lead['id_lead'],
            'confianzaNlp'    => $nlp['confianza_nlp'],
            'accion'          => 'human_handoff_confianza_baja',
        ]);
    }

    // ── 8. Construir y enviar respuesta ───────────────────────────────────────
    $reply = build_intent_response($nlp['perfil_detectado'], $sender_name);
    send_platform_message($canal, $raw_phone, $reply);

    respond(200, 'success', 'Mensaje procesado. Respuesta automática enviada.', [
        'leadId'          => $lead['id_lead'],
        'perfilDetectado' => $nlp['perfil_detectado'],
        'confianzaNlp'    => $nlp['confianza_nlp'],
        'accion'          => 'respuesta_automatica',
    ]);
}


// ═══════════════════════════════════════════════════════════════════════════════
// SEGURIDAD — VALIDACIÓN DE FIRMA META
// ═══════════════════════════════════════════════════════════════════════════════

function validate_meta_signature(string $body, string $signature): bool
{
    $app_secret = $_ENV['META_APP_SECRET'] ?? '';

    // En local sin app_secret configurado: bypass (solo modo desarrollo)
    if (empty($app_secret)) {
        return IS_LOCAL;
    }

    if (empty($signature)) {
        return false;
    }

    $expected = 'sha256=' . hash_hmac('sha256', $body, $app_secret);
    return hash_equals($expected, $signature);
}


// ═══════════════════════════════════════════════════════════════════════════════
// INTERCEPTOR DE RIESGO CLÍNICO
// ═══════════════════════════════════════════════════════════════════════════════

function detect_clinical_risk(string $text): bool
{
    $normalized = normalize_for_matching($text);

    foreach (CLINICAL_RISK_TRIGGERS as $trigger) {
        if (str_contains($normalized, normalize_for_matching($trigger))) {
            return true;
        }
    }

    return false;
}

/**
 * Normaliza un string para comparación: minúsculas, sin acentos, sin signos de puntuación extra.
 */
function normalize_for_matching(string $text): string
{
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = strtr($text, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
        'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
        'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
        'ñ' => 'n',
    ]);
    return $text;
}


// ═══════════════════════════════════════════════════════════════════════════════
// NORMALIZACIÓN DE TELÉFONO (PIEDRA-03)
// ═══════════════════════════════════════════════════════════════════════════════

function normalize_phone(string $raw): ?string
{
    $digits = preg_replace('/[^0-9]/', '', $raw);

    // Número local MX de 10 dígitos → agregar código de país
    if (strlen($digits) === 10) {
        $digits = '52' . $digits;
    }

    // Validación de longitud E.164 (7 a 15 dígitos incluyendo código de país)
    if (strlen($digits) < 7 || strlen($digits) > 15) {
        return null;
    }

    return $digits;
}


// ═══════════════════════════════════════════════════════════════════════════════
// EXTRACCIÓN DE PAYLOAD META
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Extrae los datos relevantes del payload de Meta API.
 * Devuelve null si el evento no es un mensaje de texto (ej: status update).
 *
 * @return array{phone:string, text:string, canal:string, name:string}|null
 */
function extract_message_data(array $payload): ?array
{
    $entry    = $payload['entry'][0]    ?? null;
    $changes  = $entry['changes'][0]    ?? null;
    $value    = $changes['value']       ?? null;
    $message  = $value['messages'][0]   ?? null;

    // No es un mensaje (puede ser status/delivery/read)
    if ($message === null) {
        return null;
    }

    // Solo procesamos mensajes de texto por ahora (Fase B: extender a audio/imagen)
    if (($message['type'] ?? '') !== 'text') {
        return null;
    }

    $text = trim($message['text']['body'] ?? '');

    if ($text === '') {
        return null;
    }

    return [
        'phone' => (string)($message['from'] ?? ''),
        'text'  => $text,
        'canal' => 'whatsapp',
        'name'  => (string)($value['contacts'][0]['profile']['name'] ?? 'Usuario'),
    ];
}


// ═══════════════════════════════════════════════════════════════════════════════
// CONSENT GATE
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Interpreta la respuesta del usuario al Consent Gate.
 * Devuelve 'aceptado', 'rechazado', o null si el mensaje no es una respuesta al gate.
 */
function parse_consent_response(string $text): ?string
{
    $n = normalize_for_matching($text);

    $affirmative = ['si', 'sí', 'acepto', 'ok', 'yes', 'dale', 'claro', 'de acuerdo',
                    'aceptar', 'confirmo', 'confirmar', 'acepto los terminos',
                    'acepto los términos', 'estoy de acuerdo', '👍', '✅'];

    $negative    = ['no', 'nope', 'rechazar', 'no acepto', 'no quiero', 'cancelar',
                    'no me interesa', '❌', '🚫'];

    foreach ($affirmative as $term) {
        if ($n === normalize_for_matching($term) || str_contains($n, normalize_for_matching($term))) {
            return 'aceptado';
        }
    }
    foreach ($negative as $term) {
        if ($n === normalize_for_matching($term) || str_contains($n, normalize_for_matching($term))) {
            return 'rechazado';
        }
    }

    return null;
}

function register_consent(int $lead_id, string $decision): void
{
    $pdo = getDB();

    // Verificar que no haya sido procesado ya (evitar doble ejecución)
    $check = $pdo->prepare("SELECT consent_gate_status FROM leads_prospectos WHERE id_lead = ? LIMIT 1");
    $check->execute([$lead_id]);
    $current = $check->fetchColumn();

    if ($current !== 'pendiente') {
        return; // Ya procesado — idempotente
    }

    $pdo->prepare(
        "UPDATE leads_prospectos
         SET consent_gate_status = ?,
             consent_timestamp   = NOW(),
             updated_at          = NOW()
         WHERE id_lead = ?"
    )->execute([$decision, $lead_id]);

    if ($decision === 'rechazado') {
        $pdo->prepare(
            "UPDATE leads_prospectos
             SET estatus_lead = 'descartado', updated_at = NOW()
             WHERE id_lead = ?"
        )->execute([$lead_id]);
    }
}


// ═══════════════════════════════════════════════════════════════════════════════
// CRM — UPSERT DE LEAD (DEDUPLICACIÓN OMNICANAL)
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Crea o actualiza el lead en leads_prospectos usando el teléfono normalizado como clave.
 *
 * @return array<string, mixed>  Registro completo del lead (fila de DB)
 */
function upsert_lead(string $telefono, string $canal, string $nombre): array
{
    $pdo = getDB();

    $stmt = $pdo->prepare("SELECT * FROM leads_prospectos WHERE telefono = ? LIMIT 1");
    $stmt->execute([$telefono]);
    $existing = $stmt->fetch();

    if ($existing !== false) {
        // Actualizar canal si el usuario contactó por uno diferente
        if ($existing['canal_origen'] !== $canal) {
            $pdo->prepare(
                "UPDATE leads_prospectos
                 SET canal_origen = ?, updated_at = NOW()
                 WHERE id_lead = ?"
            )->execute([$canal, $existing['id_lead']]);
        }
        return $existing;
    }

    // Nuevo lead — INSERT
    $nombre_clean = mb_substr(
        filter_var($nombre, FILTER_SANITIZE_SPECIAL_CHARS),
        0,
        150,
        'UTF-8'
    );

    $insert = $pdo->prepare(
        "INSERT INTO leads_prospectos
             (nombre_completo, telefono, canal_origen,
              consent_gate_status, estatus_lead, fecha_captura)
         VALUES (?, ?, ?, 'pendiente', 'nuevo', NOW())"
    );
    $insert->execute([$nombre_clean, $telefono, $canal]);

    $new_id = (int)$pdo->lastInsertId();
    $fetch  = $pdo->prepare("SELECT * FROM leads_prospectos WHERE id_lead = ?");
    $fetch->execute([$new_id]);

    return $fetch->fetch();
}


// ═══════════════════════════════════════════════════════════════════════════════
// NLP — CLASIFICADOR DE INTENCIONES (STUB — reemplazar en Fase B con LLM real)
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Clasificador léxico determinístico para desarrollo local.
 * Fase B: este método será reemplazado por una llamada real a la LLM API,
 * consumiendo el prompt desde config/prompts/clasificador_intenciones.txt
 * (Directriz del Roadmap: desacoplamiento absoluto de prompts).
 *
 * @return array{perfil_detectado:string, confianza_nlp:float}
 */
function classify_intent_stub(string $text): array
{
    $n = normalize_for_matching($text);

    $profiles = [
        'atleta_competitivo' => [
            'keywords' => ['competencia','rendimiento','velocidad','fuerza','vo2','potencia',
                           'entrenamiento','atletismo','clasificar','maraton','triatlón','triatlon',
                           'periodizacion','periodización','resistencia'],
            'score'    => 0.72,
        ],
        'rehabilitacion' => [
            'keywords' => ['lesion','lesión','rehabilitacion','rehabilitación','recuperacion',
                           'recuperación','cirugia','cirugía','operacion','operación','fisioterapia',
                           'tendinitis','esguince','contractura','inflamacion','inflamación'],
            'score'    => 0.70,
        ],
        'composicion_corporal' => [
            'keywords' => ['bajar de peso','grasa','composicion','composición','nutricion',
                           'nutrición','dieta','adelgazar','tono','musculo','músculo',
                           'perdida de peso','pérdida de peso','imc','masa muscular'],
            'score'    => 0.68,
        ],
    ];

    foreach ($profiles as $perfil => $config) {
        foreach ($config['keywords'] as $kw) {
            if (str_contains($n, normalize_for_matching($kw))) {
                return [
                    'perfil_detectado' => $perfil,
                    'confianza_nlp'    => $config['score'],
                ];
            }
        }
    }

    return [
        'perfil_detectado' => 'sin_clasificar',
        'confianza_nlp'    => 0.45,
    ];
}

function update_lead_from_nlp(int $lead_id, array $nlp, string $raw_text): void
{
    $pdo = getDB();

    $objetivo_clean = mb_substr(
        filter_var($raw_text, FILTER_SANITIZE_SPECIAL_CHARS),
        0, 500, 'UTF-8'
    );

    $pdo->prepare(
        "UPDATE leads_prospectos
         SET perfil_detectado  = ?,
             confianza_nlp     = ?,
             objetivo_declarado = ?,
             estatus_lead      = 'en_conversacion',
             updated_at        = NOW()
         WHERE id_lead = ?"
    )->execute([
        $nlp['perfil_detectado'],
        $nlp['confianza_nlp'],
        $objetivo_clean,
        $lead_id,
    ]);
}


// ═══════════════════════════════════════════════════════════════════════════════
// CONSTRUCTORES DE RESPUESTA
// ═══════════════════════════════════════════════════════════════════════════════

function build_intent_response(string $perfil, string $nombre): string
{
    // Directriz del Roadmap (P0): lenguaje de probabilidad y humildad epistemológica.
    return match ($perfil) {
        'atleta_competitivo' =>
            "¡Excelente, *{$nombre}*! 🏆\n\n" .
            "Todo apunta a que tu objetivo es maximizar el rendimiento deportivo. " .
            "En Athlos contamos con evaluaciones de VO2Max, análisis de potencia y " .
            "protocolos de periodización basados en ciencias del deporte.\n\n" .
            "¿Te gustaría agendar una *Evaluación de Rendimiento Integral*? " .
            "Dime qué días tienes disponibles y revisamos los horarios. 📅",

        'rehabilitacion' =>
            "Entiendo, *{$nombre}*. La recuperación es el primer paso hacia el rendimiento. 💪\n\n" .
            "Nuestro equipo trabaja con protocolos de rehabilitación basados en evidencia y " .
            "biomecánica funcional.\n\n" .
            "_Nota: esta orientación es de carácter general. Para tu caso específico, " .
            "nuestro especialista realizará una valoración funcional personalizada._\n\n" .
            "¿Cuándo podríamos agendar tu primera valoración? 📅",

        'composicion_corporal' =>
            "¡Hola, *{$nombre}*! 🎯 Tu objetivo de composición corporal es totalmente " .
            "alcanzable con el enfoque científico correcto.\n\n" .
            "Trabajamos con análisis de composición (bioimpedancia y pliegues cutáneos), " .
            "nutrición deportiva y entrenamiento metabólico de fuerza.\n\n" .
            "¿Quieres que te enviemos los detalles de nuestro *Programa de Composición Corporal*? 📋",

        default =>
            "¡Bienvenido/a a *Athlos Performance*, *{$nombre}*! 🏟️\n\n" .
            "Somos un laboratorio de ciencias del deporte especializado en:\n" .
            "• 🏆 Alto rendimiento deportivo\n" .
            "• 🔧 Rehabilitación y prevención de lesiones\n" .
            "• ⚖️ Composición corporal y nutrición\n\n" .
            "¿Cuál es tu objetivo principal? Cuéntanos con tus palabras.",
    };
}

function build_low_confidence_response(string $nombre): string
{
    return "¡Gracias por escribirnos, *{$nombre}*! 🙌\n\n" .
           "Quiero asegurarme de darte la orientación más precisa para tu caso. " .
           "Un especialista de nuestro equipo te contactará en breve para resolver " .
           "tu consulta de forma personalizada.";
}


// ═══════════════════════════════════════════════════════════════════════════════
// MENSAJERÍA SALIENTE — META API
// ═══════════════════════════════════════════════════════════════════════════════

function send_platform_message(string $canal, string $to, string $text): bool
{
    $page_token      = $_ENV['META_PAGE_TOKEN']      ?? '';
    $phone_number_id = $_ENV['META_PHONE_NUMBER_ID'] ?? '';

    // Modo local / sin tokens configurados → simular envío con log
    if (IS_LOCAL || empty($page_token) || empty($phone_number_id)) {
        error_log(sprintf(
            '[ATHLOS_WH][SIMULATED] → %s | %s | %.80s',
            strtoupper($canal),
            $to,
            $text
        ));
        return true;
    }

    $url     = "https://graph.facebook.com/v19.0/{$phone_number_id}/messages";
    $body    = json_encode([
        'messaging_product' => 'whatsapp',
        'to'                => $to,
        'type'              => 'text',
        'text'              => ['body' => $text],
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            "Authorization: Bearer {$page_token}",
        ],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response  = curl_exec($ch);
    $http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        error_log("[ATHLOS_WH] Error Meta API ({$http_code}): {$response}");
        return false;
    }

    return true;
}


// ═══════════════════════════════════════════════════════════════════════════════
// AUDIT LOG MÉDICO (APPEND-ONLY — Piedra-07)
// ═══════════════════════════════════════════════════════════════════════════════

function log_audit_event(
    ?int   $id_lead,
    string $canal,
    string $fragmento,
    string $capa,
    float  $confianza
): void {
    try {
        $pdo = getDB();

        $fragmento_clean = mb_substr(
            filter_var($fragmento, FILTER_SANITIZE_SPECIAL_CHARS),
            0, 1000, 'UTF-8'
        );

        $pdo->prepare(
            "INSERT INTO audit_log_medico
                 (id_lead, canal, fragmento_conversacion, terminos_medicos_detectados,
                  nivel_confianza, capa_activada, requiere_revision, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 1, NOW())"
        )->execute([
            $id_lead,
            $canal,
            $fragmento_clean,
            json_encode(['fragmento_original' => mb_substr($fragmento, 0, 500, 'UTF-8')]),
            $confianza,
            $capa,
        ]);
    } catch (\Throwable $e) {
        // El audit log NUNCA debe romper el flujo principal — falla silenciosa con log de sistema
        error_log('[ATHLOS_AUDIT][ERROR] ' . $e->getMessage());
    }
}


// ═══════════════════════════════════════════════════════════════════════════════
// NOTIFICACIÓN DE EMERGENCIA AL STAFF
// ═══════════════════════════════════════════════════════════════════════════════

function notify_staff_emergency(string $telefono, string $nombre, string $mensaje): void
{
    // Fase B: reemplazar con integración SendGrid o notificación WhatsApp interno
    $staff_email = $_ENV['STAFF_ALERT_EMAIL'] ?? 'staff@athlosperformance.com';

    error_log(sprintf(
        '[ATHLOS_EMERGENCY] 🚨 RIESGO CLÍNICO | Teléfono: %s | Nombre: %s | Canal: WA | Msg: %.200s',
        $telefono,
        $nombre,
        $mensaje
    ));

    // TODO Fase B: enviar email urgente a $staff_email con los datos del evento
}


// ═══════════════════════════════════════════════════════════════════════════════
// HELPER — RESPUESTA JSON ESTANDARIZADA
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Emite la respuesta JSON estándar y termina la ejecución.
 *
 * @param  array<string, mixed>  $data
 * @return never
 */
function respond(int $code, string $status, string $message, array $data = []): never
{
    http_response_code($code);
    echo json_encode(
        ['status' => $status, 'message' => $message, 'data' => $data],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}
