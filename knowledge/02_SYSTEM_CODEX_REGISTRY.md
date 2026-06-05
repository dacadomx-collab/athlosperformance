# 🧬 SYSTEM CODEX & REGISTRY — ATHLOS COGNITIVE ENGINE v1.0
> **Fuente de Verdad Absoluta.** Todo nombre técnico del sistema vive aquí.  
> **Responsable de Escritura:** IA Ejecutora (Agente Autónomo) — bajo Mandamiento 18.  
> **Última actualización:** 2026-05-27 — Fase A completada (webhook + conexión PDO + .env)

> **NOTA DE FUNDACIÓN:** Todas las conexiones a la base de datos deben realizarse obligatoriamente a través de `api/conexion.php`, leyendo `DB_HOST`, `DB_NAME`, `DB_USER` y `DB_PASS` del `.env`. Cero conexiones directas o hardcodeadas.

---

## 📊 MAPEO DE VARIABLES VALIDADAS (FRONT VS BACK)

| Concepto | DB / Backend (`snake_case`) | Frontend (`camelCase`) | Tipo de Dato |
| :--- | :--- | :--- | :--- |
| ID de Lead/Prospecto | `id_lead` | `leadId` | INT |
| Nombre Completo | `nombre_completo` | `nombreCompleto` | String |
| Teléfono (normalizado) | `telefono` | `telefono` | String |
| Canal de Origen | `canal_origen` | `canalOrigen` | String (ENUM) |
| Perfil Detectado por NLP | `perfil_detectado` | `perfilDetectado` | String (ENUM) |
| Objetivo Declarado (raw) | `objetivo_declarado` | `objetivoDeclarado` | String |
| Status del Consent Gate | `consent_gate_status` | `consentGateStatus` | String (ENUM) |
| Timestamp de Consentimiento | `consent_timestamp` | `consentTimestamp` | DateTime|NULL |
| Entidades Extraídas por NLP | `nlp_entidades_json` | `nlpEntidadesJson` | JSON |
| Puntuación de Confianza NLP | `confianza_nlp` | `confianzaNlp` | Float (0.00–1.00) |
| Estatus del Lead | `estatus_lead` | `estatusLead` | String (ENUM) |
| ID de Atleta/Cliente | `id_atleta` | `atletaId` | INT |
| Fecha de Nacimiento | `fecha_nacimiento` | `fechaNacimiento` | Date |
| Deporte Principal | `deporte_principal` | `deportePrincipal` | String |
| Tipo de Membresía | `tipo_membresia` | `tipoMembresia` | String (ENUM) |
| Estatus del Atleta | `estatus` | `estatus` | String (ENUM) |
| Antecedentes de Lesión (raw) | `antecedentes_lesion` | `antecedentesLesion` | Text |
| Lesión Normalizada (ICD-10) | `antecedentes_lesion_normalizado` | `antecedentesLesionNormalizado` | JSON |
| Fuente del Historial | `fuente_historial` | `fuenteHistorial` | String (ENUM) |
| ID de Servicio | `id_servicio` | `servicioId` | INT |
| Nombre del Servicio | `nombre_servicio` | `nombreServicio` | String |
| Descripción Técnica | `descripcion_tecnica` | `descripcionTecnica` | Text |
| Precio Base | `precio_base` | `precioBase` | Float (DECIMAL) |
| Duración en Minutos | `duracion_minutos` | `duracionMinutos` | INT |
| Tipo de Servicio | `tipo_servicio` | `tipoServicio` | String (ENUM) |
| ID de Cita | `id_cita` | `citaId` | INT |
| Fecha de la Cita | `fecha_cita` | `fechaCita` | Date |
| Hora de Inicio | `hora_inicio` | `horaInicio` | Time |
| Hora de Fin | `hora_fin` | `horaFin` | Time |
| Cupo Máximo por Hora | `cupo_maximo_hora` | `cupoMaximoHora` | INT |
| Estatus de la Cita | `estatus_cita` | `estatusCita` | String (ENUM) |
| Confirmación Enviada | `confirmacion_enviada` | `confirmacionEnviada` | Bool (TINYINT) |
| Recordatorio Enviado | `recordatorio_enviado` | `recordatorioEnviado` | Bool (TINYINT) |
| ID de Staff | `id_staff` | `staffId` | INT |
| Especialidad del Staff | `especialidad` | `especialidad` | String |
| ID del Audit Log | `id_log` | `logId` | INT |
| Fragmento de Conversación | `fragmento_conversacion` | `fragmentoConversacion` | Text |
| Términos Médicos Detectados | `terminos_medicos_detectados` | `terminosMedicosDetectados` | JSON |
| Nivel de Confianza IA | `nivel_confianza` | `nivelConfianza` | Float (0.00–1.00) |
| Capa Anti-Alucinación Activada | `capa_activada` | `capaActivada` | String (ENUM) |
| Requiere Revisión Humana | `requiere_revision` | `requiereRevision` | Bool (TINYINT) |
| Revisado por (Staff) | `revisado_por` | `revisadoPor` | String|NULL |
| Fecha de Revisión | `fecha_revision` | `fechaRevision` | DateTime|NULL |
| Activo/Inactivo (flag) | `activo` | `activo` | Bool (TINYINT) |

---

## 🗄️ ESTRUCTURA DE TABLAS (SCHEMA) — Base de datos: `athlos_engine_db`

---

### Tabla: `leads_prospectos`
> Registros capturados por el AI FrontDesk 24/7 vía NLP. Un lead existe antes del Consent Gate.

| Columna | Tipo | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id_lead` | INT UNSIGNED | PK, AUTO_INCREMENT | Identificador único del prospecto |
| `nombre_completo` | VARCHAR(150) | NOT NULL | Nombre extraído por el NLP |
| `telefono` | VARCHAR(20) | NOT NULL, UNIQUE | Teléfono normalizado (con código de país, sin espacios). Clave de deduplicación. |
| `email` | VARCHAR(150) | NULL | Email si fue provisto en la conversación |
| `canal_origen` | ENUM('whatsapp','instagram','facebook') | NOT NULL | Canal donde se capturó el lead |
| `perfil_detectado` | ENUM('atleta_competitivo','rehabilitacion','composicion_corporal','sin_clasificar') | NOT NULL, DEFAULT 'sin_clasificar' | Clasificación del intent por el motor NLP |
| `objetivo_declarado` | TEXT | NULL | Texto raw del objetivo expresado por el usuario |
| `consent_gate_status` | ENUM('pendiente','aceptado','rechazado') | NOT NULL, DEFAULT 'pendiente' | Estado del consentimiento de privacidad |
| `consent_timestamp` | DATETIME | NULL | Timestamp exacto cuando el usuario aceptó el Consent Gate |
| `nlp_entidades_json` | JSON | NULL | Objeto JSON con todas las entidades extraídas por el NLP |
| `confianza_nlp` | DECIMAL(3,2) | NULL | Puntuación de confianza del clasificador (0.00–1.00) |
| `estatus_lead` | ENUM('nuevo','en_conversacion','agendado','convertido','descartado') | NOT NULL, DEFAULT 'nuevo' | Etapa del lead en el pipeline de captación |
| `churn_score` | DECIMAL(3,2) | NULL | Puntuación de riesgo de abandono (0.00–1.00). Calculado por Churn Radar. |
| `fecha_captura` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Timestamp de primer contacto |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Timestamp de última modificación |

---

### Tabla: `atletas`
> Base maestra de clientes activos e inactivos. Incluye historiales migrados de Excel.

| Columna | Tipo | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id_atleta` | INT UNSIGNED | PK, AUTO_INCREMENT | Identificador único del atleta |
| `id_lead` | INT UNSIGNED | FK → `leads_prospectos.id_lead`, NULL | Lead de origen si fue convertido desde el AI FrontDesk |
| `nombre_completo` | VARCHAR(150) | NOT NULL | Nombre legal completo |
| `fecha_nacimiento` | DATE | NULL | Para calcular edad y cruzar percentiles de referencia |
| `sexo` | ENUM('masculino','femenino','no_especificado') | NOT NULL, DEFAULT 'no_especificado' | Para cruce con percentiles poblacionales |
| `telefono` | VARCHAR(20) | NOT NULL | Teléfono normalizado |
| `email` | VARCHAR(150) | NULL | Email de contacto |
| `deporte_principal` | VARCHAR(100) | NULL | Deporte base del atleta |
| `tipo_membresia` | ENUM('sesion_unica','mensual','trimestral','semestral','anual') | NOT NULL | Tipo de contrato activo |
| `estatus` | ENUM('activo','inactivo','suspendido') | NOT NULL, DEFAULT 'activo' | Estado operativo del atleta en el lab |
| `antecedentes_lesion` | TEXT | NULL | Texto raw de lesiones (preservado para auditoría) |
| `antecedentes_lesion_normalizado` | JSON | NULL | Lesiones mapeadas a vocabulario controlado ICD-10/deportivo |
| `fuente_historial` | ENUM('nuevo','migracion_excel','manual') | NOT NULL, DEFAULT 'nuevo' | Origen del registro |
| `fecha_ingreso` | DATE | NOT NULL | Fecha de primera visita o ingreso al sistema |
| `fecha_ultimo_contacto` | DATE | NULL | Última sesión o interacción registrada. Usado por Churn Radar. |
| `created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Timestamp de creación del registro |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Timestamp de última modificación |

---

### Tabla: `catalogo_servicios`
> Catálogo que la IA usa para cotizar y describir servicios de forma autónoma.

| Columna | Tipo | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id_servicio` | INT UNSIGNED | PK, AUTO_INCREMENT | Identificador único del servicio |
| `nombre_servicio` | VARCHAR(200) | NOT NULL, UNIQUE | Nombre comercial del servicio |
| `descripcion_tecnica` | TEXT | NOT NULL | Descripción técnica completa (la IA la usará como contexto en el RAG) |
| `precio_base` | DECIMAL(10,2) | NOT NULL | Precio base en MXN |
| `duracion_minutos` | INT UNSIGNED | NOT NULL | Duración de la sesión en minutos |
| `tipo_servicio` | ENUM('evaluacion_inicial','entrenamiento','rehabilitacion','nutricion','paquete','asesoría') | NOT NULL | Categoría del servicio |
| `activo` | TINYINT(1) | NOT NULL, DEFAULT 1 | 1 = visible para la IA y cotizable. 0 = descatalogado. |
| `created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Timestamp de creación |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Timestamp de última modificación |

---

### Tabla: `staff`
> Personal del laboratorio. Controla quién puede recibir citas.

| Columna | Tipo | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id_staff` | INT UNSIGNED | PK, AUTO_INCREMENT | Identificador único del especialista |
| `nombre_completo` | VARCHAR(150) | NOT NULL | Nombre del especialista |
| `especialidad` | VARCHAR(100) | NOT NULL | Área de expertise (ej: "Nutrición Deportiva", "Fuerza y Acondicionamiento") |
| `telefono` | VARCHAR(20) | NULL | Teléfono interno |
| `email` | VARCHAR(150) | NOT NULL, UNIQUE | Email corporativo |
| `activo` | TINYINT(1) | NOT NULL, DEFAULT 1 | 1 = recibe citas. 0 = fuera de agenda. |
| `created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Timestamp de creación |

---

### Tabla: `disponibilidad_agenda`
> Corazón del Sistema Autónomo de Agenda. Registra tanto disponibilidad como citas concretas.

| Columna | Tipo | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id_cita` | INT UNSIGNED | PK, AUTO_INCREMENT | Identificador único del bloque/cita |
| `id_atleta` | INT UNSIGNED | FK → `atletas.id_atleta`, NULL | Atleta agendado (NULL si el bloque está disponible o es para un lead) |
| `id_lead` | INT UNSIGNED | FK → `leads_prospectos.id_lead`, NULL | Lead agendado antes de convertirse en atleta |
| `id_staff` | INT UNSIGNED | FK → `staff.id_staff`, NOT NULL | Especialista asignado a la sesión |
| `id_servicio` | INT UNSIGNED | FK → `catalogo_servicios.id_servicio`, NOT NULL | Servicio que se prestará en la sesión |
| `fecha_cita` | DATE | NOT NULL | Fecha de la sesión |
| `hora_inicio` | TIME | NOT NULL | Hora de inicio del bloque |
| `hora_fin` | TIME | NOT NULL | Hora de fin del bloque |
| `cupo_maximo_hora` | INT UNSIGNED | NOT NULL, DEFAULT 1 | Máximo de atletas simultáneos en esa franja horaria en el lab |
| `estatus_cita` | ENUM('disponible','reservada','confirmada','cancelada','completada','no_show') | NOT NULL, DEFAULT 'disponible' | Estado del bloque |
| `notas_previas` | TEXT | NULL | Contexto capturado por la IA durante la conversación de agendado |
| `confirmacion_enviada` | TINYINT(1) | NOT NULL, DEFAULT 0 | 1 = se envió WA de confirmación |
| `recordatorio_enviado` | TINYINT(1) | NOT NULL, DEFAULT 0 | 1 = se envió WA de recordatorio 24h antes |
| `created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Timestamp de creación |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Timestamp de última modificación |

---

### Tabla: `audit_log_medico`
> Registro inmutable de toda conversación donde la IA activó alguna capa del protocolo Anti-Alucinación. Propósito: revisión del staff, mejora continua y defensa legal.

| Columna | Tipo | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id_log` | INT UNSIGNED | PK, AUTO_INCREMENT | Identificador único del evento |
| `id_lead` | INT UNSIGNED | FK → `leads_prospectos.id_lead`, NULL | Lead involucrado (si aplica) |
| `id_atleta` | INT UNSIGNED | FK → `atletas.id_atleta`, NULL | Atleta involucrado (si aplica) |
| `canal` | ENUM('whatsapp','instagram','facebook') | NOT NULL | Canal donde ocurrió la conversación |
| `fragmento_conversacion` | TEXT | NOT NULL | Fragmento exacto del intercambio que activó el protocolo |
| `terminos_medicos_detectados` | JSON | NOT NULL | Array de términos clínicos identificados en el fragmento |
| `nivel_confianza` | DECIMAL(3,2) | NOT NULL | Puntuación de confianza de la respuesta generada |
| `capa_activada` | ENUM('constitution','rag','confidence_gate','disclaimer','escalation') | NOT NULL | Qué capa del protocolo de 5 niveles se activó |
| `requiere_revision` | TINYINT(1) | NOT NULL, DEFAULT 1 | 1 = pendiente de revisión por staff médico |
| `revisado_por` | VARCHAR(100) | NULL | Nombre del especialista que revisó el log |
| `fecha_revision` | DATETIME | NULL | Timestamp de la revisión |
| `created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Timestamp del evento |

---

## 🧠 REGISTRO SEMÁNTICO (VOCABULARIO CONTROLADO)

### Dominio: Lesiones Musculo-Esqueléticas
| Término Normalizado (Canónico) | Sinónimos Aceptados en Ingesta | Código ICD-10 Ref. |
| :--- | :--- | :--- |
| `lca` | "LCA", "ligamento cruzado anterior", "lig. cruzado", "ligamento ant.", "cruzado anterior" | M23.6 |
| `lcp` | "LCP", "ligamento cruzado posterior", "cruzado posterior" | M23.6 |
| `tendinitis_rotuliana` | "tendinitis rotuliana", "rodilla del saltador", "tendinitis patelar" | M76.5 |
| `esguince_tobillo` | "esguince de tobillo", "torcedura de tobillo", "tobillo torcido" | S93.4 |
| `contractura_lumbar` | "dolor de espalda", "lumbalgia", "contractura lumbar", "espalda baja" | M54.5 |
| `rotura_manguito` | "manguito rotador", "rotura de manguito", "hombro roto" | M75.1 |

### Perfiles de Usuario (Vocabulario del Clasificador)
- ✅ **Términos canónicos permitidos:** `atleta_competitivo`, `rehabilitacion`, `composicion_corporal`, `sin_clasificar`
- ❌ **Variantes prohibidas:** "deportista", "lesionado", "quiero bajar de peso", "en recuperación" — estos son inputs del usuario, no valores de DB.

### Canales (Vocabulario del Sistema)
- ✅ **Términos canónicos permitidos:** `whatsapp`, `instagram`, `facebook`
- ❌ **Variantes prohibidas:** "WA", "IG", "FB", "Messenger", "WhatsApp" (con mayúsculas internas)

---

## 📁 REGISTRO DE ARTEFACTOS DE CÓDIGO (FASE A — COMPLETADA)

| Archivo | Ruta | Tipo | Estado | Descripción |
| :--- | :--- | :--- | :--- | :--- |
| `.env.example` | `.env.example` | Config | ✅ Producción | Plantilla de variables de entorno. El `.env` real nunca se sube a Git. |
| `conexion.php` | `api/conexion.php` | Backend / Singleton | ✅ Producción | Cargador de `.env` + singleton PDO. Único punto de acceso a DB en todo el sistema. |
| `webhook_mensajeria.php` | `api/webhook_mensajeria.php` | Backend / Endpoint | ✅ Producción | Webhook omnicanal. Incluye: validación firma Meta, interceptor riesgo clínico, Consent Gate middleware, clasificador NLP stub, deduplicación de lead, audit log, mensajería Meta API. |
| `clasificador_intenciones.txt` | `config/prompts/clasificador_intenciones.txt` | Config / Prompt | ✅ Producción | System prompt desacoplado del PHP para el clasificador NLP. Fase B lo inyecta en la llamada LLM. |

### Constantes y Enums de Código (Fase A)

| Constante / Enum | Archivo | Valor | Descripción |
| :--- | :--- | :--- | :--- |
| `CONFIDENCE_THRESHOLD` | `webhook_mensajeria.php` | `0.75` (desde `.env`) | Umbral mínimo de confianza NLP. Por debajo → human handoff. |
| `IS_LOCAL` | `webhook_mensajeria.php` | `bool` | Modo desarrollo: omite validación firma y simula envíos Meta API. |
| `CLINICAL_RISK_TRIGGERS` | `webhook_mensajeria.php` | `array<string>` (30 triggers) | Lista negra de disparadores de riesgo clínico. Detiene inferencia IA inmediatamente. |
| `CONSENT_GATE_MSG` | `webhook_mensajeria.php` | `string` | Mensaje legal del Consent Gate enviado al usuario. |
| `HUMAN_HANDOFF_MSG` | `webhook_mensajeria.php` | `string` | Mensaje de escalación enviado ante riesgo clínico detectado. |

### Funciones Públicas Registradas (api/webhook_mensajeria.php)

| Función | Firma | Responsabilidad |
| :--- | :--- | :--- |
| `handle_verification()` | `(): void` | GET — verificación inicial del webhook con Meta Developers |
| `handle_incoming_message()` | `(): void` | POST — orquestador principal del flujo de mensajes |
| `validate_meta_signature()` | `(string, string): bool` | Valida `X-Hub-Signature-256` contra `META_APP_SECRET` |
| `detect_clinical_risk()` | `(string): bool` | Escanea texto contra `CLINICAL_RISK_TRIGGERS` con normalización de acentos |
| `normalize_for_matching()` | `(string): string` | Minúsculas + transliteración de acentos + trim |
| `normalize_phone()` | `(string): ?string` | Extrae dígitos, agrega `52` si es MX local, valida longitud E.164 |
| `extract_message_data()` | `(array): ?array` | Extrae phone/text/canal/name del payload Meta API |
| `parse_consent_response()` | `(string): ?string` | Detecta si el mensaje es respuesta afirmativa/negativa al Consent Gate |
| `register_consent()` | `(int, string): void` | `UPDATE` idempotente del `consent_gate_status` en DB |
| `upsert_lead()` | `(string, string, string): array` | `INSERT` o `UPDATE` en `leads_prospectos` por teléfono normalizado |
| `classify_intent_stub()` | `(string): array` | Clasificador léxico stub — Fase B reemplaza con llamada LLM real |
| `update_lead_from_nlp()` | `(int, array, string): void` | Persiste resultado NLP en `leads_prospectos` |
| `build_intent_response()` | `(string, string): string` | Construye respuesta conversacional según perfil detectado |
| `build_low_confidence_response()` | `(string): string` | Respuesta de human handoff por confianza insuficiente |
| `send_platform_message()` | `(string, string, string): bool` | Envía mensaje saliente vía Meta API (o simula en local) |
| `log_audit_event()` | `(?int, string, string, string, float): void` | `INSERT` append-only en `audit_log_medico` |
| `notify_staff_emergency()` | `(string, string, string): void` | Alerta de emergencia clínica al staff (Fase B: email/WA interno) |
| `respond()` | `(int, string, string, array): never` | Emite JSON estándar y termina la ejecución |

### Función Pública Registrada (api/conexion.php)

| Función | Firma | Responsabilidad |
| :--- | :--- | :--- |
| `getDB()` | `(): PDO` | Singleton PDO. Lanza `RuntimeException` si `DB_NAME` no está configurado. |

---

## 🧩 REGISTRO DE COMPONENTES FRONTEND

| Componente | Ruta | Tipo | Estado | Props Principales |
| :--- | :--- | :--- | :--- | :--- |
| `PanelLeads` | `views/crm/panel_leads.html` | Page | Pendiente | `leads[]`, `filtroEstatus` |
| `TarjetaAtleta` | `views/crm/tarjeta_atleta.html` | UI | Pendiente | `atletaId`, `nombreCompleto`, `estatusCita` |
| `ModalConsentGate` | `components/modal_consent.html` | UI/Logic | Pendiente | `leadId`, `onAceptar()`, `onRechazar()` |
| `AgendaCalendario` | `views/agenda/calendario.html` | Page | Pendiente | `staffId`, `fechaActual`, `bloques[]` |
| `ReporteRendimiento` | `views/diagnostico/reporte.html` | Page | Pendiente | `atletaId`, `metricas{}`, `percentiles{}` |
| `AuditLogViewer` | `views/admin/audit_log.html` | Page | Pendiente | `logs[]`, `filtroCapaActivada` |
| `FormIngestaExcel` | `components/form_ingesta.html` | UI/Logic | Pendiente | `onUpload()`, `resultadoNormalizacion{}` |

**Reglas de Interfaz Aplicadas:**
- `ModalConsentGate`: Bloquea cualquier navegación ulterior en la sesión si `consentGateStatus !== 'aceptado'`. No puede cerrarse sin decisión explícita del usuario.
- `ReporteRendimiento`: Nunca renderiza métricas sin el objeto `percentiles{}`. Si el backend no los devuelve, muestra estado de error específico, no datos vacíos.
- `AuditLogViewer`: Solo accesible para usuarios con rol `admin_medico`. Registros marcados con `requiereRevision: true` se destacan visualmente con indicador rojo.
