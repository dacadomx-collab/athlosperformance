# 🤝 CONTRATOS DE API Y LÓGICA DE NEGOCIO — ATHLOS COGNITIVE ENGINE v1.0
> **Ley de Cero Deriva:** Todo endpoint documentado aquí tiene un validador PHP estricto.  
> **Ley de Conexión:** Sin excepción, toda query usa `api/conexion.php` (PDO + Prepared Statements).  
> **Última actualización:** 2026-05-27

---

## 📡 PROTOCOLO DE INTEGRACIÓN

| Propiedad | Valor |
| :--- | :--- |
| **Formato de intercambio** | JSON / UTF-8 |
| **Headers base** | `Content-Type: application/json`, CORS habilitado para dominio del panel admin |
| **Métodos permitidos** | `POST`, `GET`, `OPTIONS` |
| **Autenticación** | Token de sesión en header `Authorization: Bearer {token}` — obligatorio en endpoints de escritura |
| **Estructura de respuesta estándar** | `{ "status": "success\|error", "message": "string", "data": {} \| [] }` |
| **Código de error 422** | Carga JSON inválida o campos faltantes antes de tocar la DB |
| **Código de error 401** | Token ausente o inválido en endpoints de escritura |
| **Código de error 403** | Token válido pero sin permisos para el recurso solicitado |

---

## 🛠️ ENDPOINTS REGISTRADOS (CONTRATOS)

---

### Endpoint: `api/webhook_mensajeria.php`
> **Propósito:** Punto de entrada de todos los mensajes entrantes de la Red Omnicanal (WhatsApp, Instagram, Facebook). Valida el origen, pasa el mensaje por el Clasificador de Intenciones y el Extractor NLP, y crea o actualiza el registro en `leads_prospectos`.

- **Método:** `POST`
- **Autenticación:** `META_VERIFY_TOKEN` en query string `?hub.verify_token=` para verificación inicial. Para mensajes entrantes: validación de firma `X-Hub-Signature-256` en header.
- **Trigger:** Meta Business API (llamada automática, no desde el panel admin)

**Payload Entrante (de Meta API → nuestro servidor):**
```json
{
  "object": "whatsapp_business_account",
  "entry": [{
    "id": "string",
    "changes": [{
      "value": {
        "messaging_product": "whatsapp",
        "contacts": [{ "profile": { "name": "string" }, "wa_id": "string" }],
        "messages": [{
          "from": "string",
          "id": "string",
          "timestamp": "string",
          "text": { "body": "string" },
          "type": "text"
        }]
      },
      "field": "messages"
    }]
  }]
}
```

**Payload de Respuesta (nuestro servidor → Meta API / WhatsApp):**
```json
{
  "messaging_product": "whatsapp",
  "to": "string",
  "type": "text",
  "text": { "body": "string" }
}
```

**Respuesta Interna del Endpoint (para logging):**
```json
{
  "status": "success",
  "message": "Mensaje procesado. Lead actualizado.",
  "data": {
    "leadId": 42,
    "perfilDetectado": "atleta_competitivo",
    "confianzaNlp": 0.89,
    "consentGateStatus": "pendiente",
    "accion": "lead_creado | lead_actualizado"
  }
}
```

**Flujo Interno Obligatorio:**
1. Validar firma `X-Hub-Signature-256`. Si falla → `403` inmediato, sin procesar.
2. Extraer `wa_id` (teléfono) y normalizar: strip espacios/guiones, agregar código de país si falta.
3. Consultar `leads_prospectos` por `telefono` (deduplicación). Si existe → `UPDATE`. Si no → preparar `INSERT`.
4. Llamar al motor NLP (LLM API) con el texto del mensaje para clasificar intención y extraer entidades.
5. Si `confianza_nlp < 0.60` → activar Human Handoff y registrar en `audit_log_medico`.
6. Si el NLP detecta terminología médica → evaluar contra `REGLA-03` (capa Anti-Alucinación).
7. Persistir cambios en DB **solo si** `consent_gate_status = 'aceptado'` para datos de salud.
8. Devolver respuesta al usuario en el canal correspondiente vía Meta API.

---

### Endpoint: `api/guardar_consentimiento.php`
> **Propósito:** Registra la aceptación o rechazo explícito del Consent Gate legal. Activa el CRM para el lead una vez aceptado. Este endpoint es el guardián de `REGLA-01`.

- **Método:** `POST`
- **Autenticación:** `Authorization: Bearer {token}` — puede ser un token de sesión de conversación (generado por el webhook).

**Payload Requerido (Front → Back):**
```json
{
  "leadId": 42,
  "decision": "aceptado | rechazado",
  "canal": "whatsapp | instagram | facebook",
  "timestampEvento": "2026-05-27T14:30:00Z"
}
```

**Validaciones PHP (422 si falla):**
- `leadId`: INT > 0, debe existir en `leads_prospectos`.
- `decision`: Debe ser exactamente `"aceptado"` o `"rechazado"`. Cualquier otro valor → 422.
- `canal`: Debe ser uno de los tres valores del ENUM. Cualquier otro valor → 422.
- `timestampEvento`: Formato ISO 8601 válido.

**Response (Back → Front):**
```json
{
  "status": "success",
  "message": "Consentimiento registrado correctamente.",
  "data": {
    "leadId": 42,
    "consentGateStatus": "aceptado",
    "consentTimestamp": "2026-05-27T14:30:00Z",
    "crmActivado": true
  }
}
```

**Flujo Interno Obligatorio:**
1. Validar todos los campos. 422 si alguno falla.
2. Verificar que `id_lead` existe. 404 si no.
3. Verificar que `consent_gate_status` actual es `'pendiente'`. Si ya fue procesado → 409 Conflict.
4. `UPDATE leads_prospectos SET consent_gate_status = ?, consent_timestamp = ? WHERE id_lead = ?` — Prepared Statement obligatorio.
5. Si `decision = 'aceptado'`: devolver `crmActivado: true` y habilitar endpoints de escritura de salud para ese lead.
6. Si `decision = 'rechazado'`: marcar lead como `estatus_lead = 'descartado'`. No borrar, conservar para auditoría.

---

### Endpoint: `api/obtener_disponibilidad.php`
> **Propósito:** Consulta los bloques de tiempo disponibles en `disponibilidad_agenda` para que el bot pueda ofrecer y agendar citas de forma autónoma.

- **Método:** `GET`
- **Autenticación:** Token de sesión de conversación (generado por el webhook para el bot).

**Query Params Requeridos:**
```
?fecha=2026-06-15&id_servicio=3&id_staff=2
```
- `fecha`: Formato `YYYY-MM-DD`. Obligatorio.
- `id_servicio`: INT. Obligatorio. Filtra por tipo de servicio y duración.
- `id_staff`: INT. Opcional. Si se omite, devuelve disponibilidad de cualquier especialista.

**Validaciones PHP (422 si falla):**
- `fecha`: Fecha válida, no puede ser pasada (anterior a `NOW()`).
- `id_servicio`: INT > 0, debe existir en `catalogo_servicios` con `activo = 1`.
- `id_staff` (si presente): INT > 0, debe existir en `staff` con `activo = 1`.

**Response (Back → Front/Bot):**
```json
{
  "status": "success",
  "message": "Disponibilidad consultada.",
  "data": {
    "fecha": "2026-06-15",
    "bloques": [
      {
        "citaId": 101,
        "staffId": 2,
        "nombreStaff": "Dra. González",
        "especialidad": "Fuerza y Acondicionamiento",
        "horaInicio": "09:00",
        "horaFin": "10:00",
        "cupoDisponible": 1,
        "servicioId": 3,
        "nombreServicio": "Evaluación de Rendimiento Integral"
      }
    ]
  }
}
```

**Flujo Interno Obligatorio:**
1. Validar y sanitizar query params.
2. Query con `WHERE fecha_cita = ? AND estatus_cita = 'disponible'` y filtros opcionales de staff/servicio. Prepared Statement.
3. Calcular `cupo_disponible` = `cupo_maximo_hora` - COUNT de citas `'reservada'` o `'confirmada'` en ese bloque.
4. Solo devolver bloques donde `cupo_disponible > 0`.
5. Nunca exponer datos de atletas de citas ya ocupadas en el mismo bloque.

---

### Endpoint: `api/ingesta_historial.php`
> **Propósito:** Recibe un archivo Excel con historiales de atletas legacy, ejecuta el pipeline de Normalización Ontológica y crea/actualiza registros en la tabla `atletas`.

- **Método:** `POST` (multipart/form-data)
- **Autenticación:** `Authorization: Bearer {token}` — requiere rol `admin`.

**Payload Requerido (multipart):**
```
archivo_excel: [FILE .xlsx/.xls, max 10MB]
sobrescribir_existentes: "true | false"
```

**Validaciones PHP (422 si falla):**
- `archivo_excel`: Obligatorio. Extensión `.xlsx` o `.xls`. MIME type validado server-side. Máximo 10MB.
- `sobrescribir_existentes`: Bool string. Default `false` si no se envía.
- Validar token con rol `admin` antes de procesar el archivo. 403 si no.

**Response (Back → Front):**
```json
{
  "status": "success",
  "message": "Ingesta completada.",
  "data": {
    "totalFilasLeidas": 120,
    "registrosCreados": 98,
    "registrosActualizados": 18,
    "registrosOmitidos": 4,
    "advertenciasNormalizacion": [
      {
        "fila": 34,
        "campoOriginal": "antecedentes_lesion",
        "valorRaw": "rodilla mala",
        "accion": "sin_mapeo_controlado",
        "valorPersistido": "rodilla mala"
      }
    ]
  }
}
```

**Flujo Interno Obligatorio:**
1. Validar token, extensión y tamaño del archivo antes de cualquier operación de DB.
2. Parsear Excel usando librería PHP (PhpSpreadsheet).
3. Por cada fila, normalizar `telefono` y buscar en `atletas` para deduplicar.
4. Ejecutar pipeline de Normalización Ontológica sobre columnas de lesiones:
   - Recorrer diccionario controlado del `02_SYSTEM_CODEX_REGISTRY.md`.
   - Match insensible a mayúsculas y a acentos.
   - Persistir resultado en `antecedentes_lesion_normalizado` (JSON) y raw en `antecedentes_lesion`.
   - Si no hay mapeo → registrar en `advertenciasNormalizacion` pero persistir el raw sin silenciar el dato.
5. Todos los `INSERT`/`UPDATE` en bloque usando transacción PDO (`beginTransaction` / `commit` / `rollBack`).
6. `fuente_historial = 'migracion_excel'` en todos los registros creados por este endpoint.
7. Devolver informe detallado de la operación.

---

## 🧠 LÓGICA DE NEGOCIO (REGLAS DE PIEDRA)

### PIEDRA-01 — Consent Gate como Llave Maestra
Ningún endpoint de escritura puede insertar datos de salud (lesiones, métricas físicas, diagnósticos) de un usuario sin verificar primero que `consent_gate_status = 'aceptado'` en `leads_prospectos` o que el registro existe en `atletas` (conversión implica consentimiento previo). Esta validación se ejecuta al inicio del método, antes de cualquier query de escritura.

```php
// Fragmento de lógica obligatoria en endpoints de datos de salud
$stmt = $pdo->prepare("SELECT consent_gate_status FROM leads_prospectos WHERE id_lead = ? LIMIT 1");
$stmt->execute([$leadId]);
$lead = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$lead || $lead['consent_gate_status'] !== 'aceptado') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Consent Gate no aceptado. Operación bloqueada."]);
    exit;
}
```

### PIEDRA-02 — PDO Prepared Statements Absolutos
Cero interpolación de variables en queries SQL. Toda variable de usuario o externa se pasa como parámetro vinculado (`?` o `:nombre`). Violación de esta regla es un bloqueo de deploy.

```php
// CORRECTO
$stmt = $pdo->prepare("SELECT * FROM atletas WHERE telefono = ? AND estatus = ?");
$stmt->execute([$telefono, $estatus]);

// PROHIBIDO — INYECCIÓN SQL
$query = "SELECT * FROM atletas WHERE telefono = '$telefono'"; // ← BLOQUEADO
```

### PIEDRA-03 — Normalización de Teléfono (Clave de Deduplicación)
Todo teléfono que entre al sistema (desde webhook, formulario o Excel) debe ser normalizado antes de consultar o insertar:
```php
$telefono = preg_replace('/[^0-9]/', '', $telefono); // Solo dígitos
if (strlen($telefono) === 10) { $telefono = '52' . $telefono; } // Agregar MX si es local
```

### PIEDRA-04 — Audit Log Automático para Terminología Médica
Cuando el webhook recibe un mensaje y el NLP detecta términos del Registro Semántico Controlado, el sistema debe insertar automáticamente un registro en `audit_log_medico` con `requiere_revision = 1` **antes** de formular la respuesta al usuario. El log es una pre-condición de la respuesta, no una acción post-respuesta.

### PIEDRA-05 — Transacciones PDO en Operaciones Multi-tabla
Toda operación que afecte más de una tabla (ej: convertir lead en atleta, procesar ingesta Excel) debe ejecutarse dentro de una transacción PDO explícita:
```php
try {
    $pdo->beginTransaction();
    // ... múltiples statements ...
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error de transacción. Operación revertida."]);
    exit;
}
```

### PIEDRA-06 — Confidence Gate en Respuestas del Bot
Todo mensaje generado por el LLM que contenga recomendaciones, descripciones de servicios o información sobre el laboratorio debe incluir el campo `nivel_confianza` en el payload interno. Si `nivel_confianza < 0.75`, el sistema sustituye automáticamente la respuesta generada por el template de escalación, sin enviarla al usuario. Template de escalación:
> *"Quiero asegurarme de darte la mejor respuesta. Un especialista de nuestro equipo te contactará en breve para resolver tu consulta."*

### PIEDRA-07 — Inmutabilidad del Audit Log
La tabla `audit_log_medico` es append-only. Ningún endpoint puede ejecutar `UPDATE` o `DELETE` sobre ella. Solo `INSERT` y `SELECT`. La revisión del staff se registra como un nuevo `UPDATE` únicamente sobre los campos `revisado_por` y `fecha_revision`, nunca sobre el contenido del log.
