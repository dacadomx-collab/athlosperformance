# 🧬 SYSTEM CODEX & REGISTRY — ATHLOS COGNITIVE ENGINE v1.0
> **Fuente de Verdad Absoluta.** Todo nombre técnico del sistema vive aquí.  
> **Responsable de Escritura:** IA Ejecutora (Agente Autónomo) — bajo Mandamiento 18.  
> **Última actualización:** 2026-07-09 — Detalle columna por columna de las 18 tablas SSOS (`01_` a `05_`) verificado contra el volcado real de producción

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

## 🗄️ EXTENSIÓN DE SCHEMA — MÓDULO BACKOFFICE SSOS v1.0 (RBAC + CLÍNICO + AGENDA)

> **Origen:** Directriz "Athlos SSOS v1.0" (Arquitecto Gemini). **Reconciliación:** el Super Admin (AXON_DCD)
> confirmó extender el schema existente en vez de crear un sistema paralelo. `atletas` sigue siendo la
> ÚNICA entidad de cliente/atleta — no existe tabla `clientes`. Scripts DDL versionados en `knowledge/sql/`,
> validados por ejecución real contra MariaDB 10.4.32 (XAMPP local) el 2026-07-07.

| Script | Tablas que crea | Reproduce (sin cambios) | Nuevo |
| :--- | :--- | :--- | :--- |
| `01_schema_usuarios_rbac.sql` | `roles`, `permisos`, `rol_permisos`, `usuarios`, `sesiones_log` | — | Motor RBAC completo. 3 roles seed: `super_admin` (AXON_DCD), `admin` (FrontDesk), `coach` (Pie de Cancha, aislado de datos financieros). |
| `02_schema_clientes_membresias.sql` | `leads_prospectos`, `atletas`, `catalogo_servicios`, `membresias`, `pagos_asistencia`, `asistencias` | `leads_prospectos`, `atletas`, `catalogo_servicios` | `membresias` (saldo de sesiones por paquete), `pagos_asistencia` (espejo de `Clientes.xlsx`), `asistencias` (check-in). |
| `03_schema_evaluaciones_clinicas.sql` | `historial_clinico`, `evaluaciones_antropometria`, `percentiles_sft_referencia`, `evaluaciones_sft`, `evaluaciones_biomecanica` | — | Ficha clínica unificada (mayor_65/menor_65), antropometría completa (pliegues/perímetros/diámetros/somatotipo), normativas SFT semaforizadas en DB (sin tablas externas), checklist Sentadilla Overhead. |
| `04_schema_agenda_sesiones.sql` | `staff`, `disponibilidad_agenda`, `audit_log_medico`, `planes_macrociclo`, `sesiones_entrenamiento`, `detalles_ejercicio` | `staff`, `disponibilidad_agenda`, `audit_log_medico` | `planes_macrociclo` (periodización anual), `sesiones_entrenamiento` + `detalles_ejercicio` (Ficha plan de sesión: calentamiento → parte medular → vuelta a la calma). Cierra FKs diferidos: `usuarios.id_staff → staff`, `asistencias.id_cita → disponibilidad_agenda`. |
| `05_schema_alertas_membresias.sql` | `alertas_renovacion` | — | Semaforización de consumo de membresías (amarillo = 2 sesiones restantes, rojo = 0), disparada desde `AthlosBusinessRules::deducirSesionAtleta()`. `UNIQUE(id_membresia, tipo_alerta)` evita duplicar la misma alerta en cada sesión posterior. |

**Reglas de diseño aplicadas:**
- `catalogo_servicios.tipo_servicio = 'paquete'` cubre los "paquetes/promos" del directriz original (ej. "Performance 12 sesiones") — no se creó tabla `paquetes` separada para evitar duplicar el catálogo existente.
- `historial_clinico` es una tabla única para mayores y menores de 65 (`tipo_historial` ENUM) porque los formularios comparten ~80% de las preguntas; las columnas exclusivas de cada ficha quedan NULL-ables.
- `percentiles_sft_referencia` persiste las tablas normativas Rikli & Jones completas (hombres/mujeres, 60-94 años) para que el Wizard SFT semaforice sin consultar fuentes externas (Checklist Fase 4).
- Todas las tablas nuevas referencian `usuarios.id_usuario` (no `staff.id_staff`) en columnas `*_por`/`created_by`, porque el registro de auditoría de "quién capturó el dato" es responsabilidad del motor RBAC, no del directorio operativo de staff.

### Detalle columna por columna — Tablas SSOS (`01_` a `05_`)

> Verificado 1:1 contra `knowledge/sql/tourfindycom_athlosp_db.sql` (volcado real de producción) el
> 2026-07-09 — sin drift respecto a los scripts DDL versionados. `leads_prospectos`, `atletas`,
> `catalogo_servicios`, `staff`, `disponibilidad_agenda` y `audit_log_medico` ya están documentadas
> arriba en "ESTRUCTURA DE TABLAS (SCHEMA)" — no se repiten aquí.

#### `01_schema_usuarios_rbac.sql`

**Tabla: `roles`**

| Columna | Tipo | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id_rol` | TINYINT UNSIGNED | PK, AUTO_INCREMENT | Identificador del rol |
| `clave_rol` | ENUM('super_admin','admin','coach') | NOT NULL, UNIQUE | Clave técnica usada por `require_role()` |
| `nombre_rol` | VARCHAR(100) | NOT NULL | Nombre visible (ej. "Dirección de Laboratorio") |
| `descripcion` | TEXT | NULL | Alcance del rol |
| `activo` | TINYINT(1) | NOT NULL, DEFAULT 1 | Rol habilitado |
| `created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | — |

**Tabla: `permisos`**

| Columna | Tipo | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id_permiso` | SMALLINT UNSIGNED | PK, AUTO_INCREMENT | — |
| `clave_permiso` | VARCHAR(100) | NOT NULL, UNIQUE | Ej. `clientes.ver_saldos` |
| `modulo` | VARCHAR(50) | NOT NULL | Agrupador (sistema/clientes/agenda/evaluaciones/sesiones/cobranza) |
| `descripcion` | VARCHAR(255) | NOT NULL | — |

**Tabla: `rol_permisos`** (pivote N:M)

| Columna | Tipo | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id_rol` | TINYINT UNSIGNED | PK compuesta, FK → `roles.id_rol` | — |
| `id_permiso` | SMALLINT UNSIGNED | PK compuesta, FK → `permisos.id_permiso` | — |

**Tabla: `usuarios`**

| Columna | Tipo | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id_usuario` | INT UNSIGNED | PK, AUTO_INCREMENT | — |
| `id_rol` | TINYINT UNSIGNED | NOT NULL, FK → `roles.id_rol` | — |
| `id_staff` | INT UNSIGNED | NULL, FK → `staff.id_staff` | Ficha operativa asociada (opcional) |
| `nombre_completo` | VARCHAR(150) | NOT NULL | — |
| `email` | VARCHAR(150) | NOT NULL, UNIQUE | Login |
| `password_hash` | VARCHAR(255) | NOT NULL | `password_hash()` PHP (bcrypt), nunca texto plano |
| `activo` | TINYINT(1) | NOT NULL, DEFAULT 1 | — |
| `requiere_cambio_password` | TINYINT(1) | NOT NULL, DEFAULT 1 | — |
| `ultimo_login` | DATETIME | NULL | — |
| `intentos_fallidos` | TINYINT UNSIGNED | NOT NULL, DEFAULT 0 | — |
| `bloqueado_hasta` | DATETIME | NULL | Bloqueo temporal (5 intentos → 15 min) |
| `created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | — |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | — |

**Tabla: `sesiones_log`**

| Columna | Tipo | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id_log_sesion` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | — |
| `id_usuario` | INT UNSIGNED | NULL, FK → `usuarios.id_usuario` | NULL si el login falló antes de resolver el usuario |
| `email_intento` | VARCHAR(150) | NOT NULL | — |
| `tipo_evento` | ENUM('login_exitoso','login_fallido','logout','bloqueo_temporal','cambio_password','token_csrf_invalido') | NOT NULL | — |
| `ip_origen` | VARCHAR(45) | NOT NULL | Soporta IPv4/IPv6 |
| `user_agent` | VARCHAR(255) | NULL | — |
| `created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | — |

#### `02_schema_clientes_membresias.sql` (tablas nuevas)

**Tabla: `membresias`**

| Columna | Tipo | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id_membresia` | INT UNSIGNED | PK, AUTO_INCREMENT | — |
| `id_atleta` | INT UNSIGNED | NOT NULL, FK → `atletas.id_atleta` | — |
| `id_servicio` | INT UNSIGNED | NOT NULL, FK → `catalogo_servicios.id_servicio` | — |
| `fecha_inicio` | DATE | NOT NULL | — |
| `fecha_fin` | DATE | NULL | NULL para `sesion_unica` sin vigencia calendario |
| `sesiones_totales` | SMALLINT UNSIGNED | NOT NULL, DEFAULT 0 | — |
| `sesiones_restantes` | SMALLINT UNSIGNED | NOT NULL, DEFAULT 0 | Decrece en cada check-in |
| `precio_pagado` | DECIMAL(10,2) | NOT NULL | — |
| `estatus` | ENUM('activa','agotada','vencida','cancelada') | NOT NULL, DEFAULT 'activa' | — |
| `notas` | VARCHAR(255) | NULL | — |
| `created_at` / `updated_at` | DATETIME | — | — |

**Tabla: `pagos_asistencia`**

| Columna | Tipo | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id_pago` | INT UNSIGNED | PK, AUTO_INCREMENT | — |
| `id_atleta` | INT UNSIGNED | NOT NULL, FK → `atletas.id_atleta` | — |
| `id_membresia` | INT UNSIGNED | NULL, FK → `membresias.id_membresia` | — |
| `concepto_pago` | VARCHAR(200) | NOT NULL | Columna "Programa" del Excel legacy |
| `monto` | DECIMAL(10,2) | NOT NULL | — |
| `metodo_pago` | ENUM('efectivo','tarjeta','transferencia','otro') | NOT NULL, DEFAULT 'efectivo' | — |
| `fecha_pago` | DATE | NOT NULL | — |
| `registrado_por` | INT UNSIGNED | NULL, FK → `usuarios.id_usuario` | — |
| `created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | — |

**Tabla: `asistencias`**

| Columna | Tipo | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id_asistencia` | INT UNSIGNED | PK, AUTO_INCREMENT | — |
| `id_atleta` | INT UNSIGNED | NOT NULL, FK → `atletas.id_atleta` | — |
| `id_cita` | INT UNSIGNED | NULL, FK → `disponibilidad_agenda.id_cita` | — |
| `id_membresia` | INT UNSIGNED | NULL, FK → `membresias.id_membresia` | — |
| `fecha_hora_checkin` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | — |
| `registrado_por` | INT UNSIGNED | NULL, FK → `usuarios.id_usuario` | — |
| `created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | — |

#### `03_schema_evaluaciones_clinicas.sql`

**Tabla: `historial_clinico`** — un registro por atleta (`UNIQUE(id_atleta)`), upsert desde
`historial_form.php` (wizard de 8 pasos, Fase 15-17).

| Columna | Tipo | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id_historial` | INT UNSIGNED | PK, AUTO_INCREMENT | — |
| `id_atleta` | INT UNSIGNED | NOT NULL, UNIQUE, FK → `atletas.id_atleta` | — |
| `tipo_historial` | ENUM('mayor_65','menor_65') | NOT NULL | Fijado por el candado cognitivo (Fase 17) si el PDF detecta edad ≥ 65 |
| `actividades_ejercicio_actual` | TEXT | NULL | — |
| `dias_ejercicio_moderado_semana` | TINYINT UNSIGNED | NULL | — |
| `objetivo_perdida_peso` / `objetivo_masa_muscular` / `objetivo_rendimiento_deportivo` / `objetivo_mejorar_salud` | TINYINT UNSIGNED | NULL | Escala 0-10 (mayor_65) / 0-5 (menor_65) |
| `dieta_saludable_score` | TINYINT UNSIGNED | NULL | Escala 0-10 |
| `sigue_dieta_actual` | TEXT | NULL | — |
| `consumo_sal` / `consumo_azucar` / `consumo_grasas` | ENUM('bajo','medio','alto') | NULL | — |
| `control_antojos_score` | TINYINT UNSIGNED | NULL | Escala 0-10, sólo mayor_65 |
| `bebidas_alcoholicas_semana` | SMALLINT UNSIGNED | NULL | — |
| `consumo_cafeina` | TEXT | NULL | Sólo mayor_65 |
| `sueno_adecuado` | TEXT | NULL | — |
| `nivel_estres_score` | TINYINT UNSIGNED | NULL | Escala 0-10, sólo mayor_65 |
| `tecnicas_manejo_estres` | TEXT | NULL | Sólo mayor_65 |
| `fuma_o_vapea` | TEXT | NULL | — |
| `ocupacion` | VARCHAR(150) | NULL | Sólo mayor_65 |
| `trabajo_sedentario_detalle` / `trabajo_movimientos_repetitivos_detalle` | TEXT | NULL | Sólo mayor_65 |
| `trabajo_calzado_tacon` | TINYINT(1) | NULL | Sólo mayor_65 |
| `actividad_recreativa_detalle` / `otro_pasatiempo_detalle` | TEXT | NULL | Sólo mayor_65 |
| `cirugias_previas` | TEXT | NULL | Combina lesión + cirugía previa (mapper las concatena) |
| `rehabilitacion_adecuada_autorizacion` | TEXT | NULL | — |
| `condicion_cronica` | TEXT | NULL | — |
| `medicamentos_actuales` | TEXT | NULL | — |
| `autorizacion_medica_ejercicio` | TINYINT(1) | NULL | Checkbox — captura manual, no viene del PDF |
| `nombre_medico` / `telefono_medico` / `contacto_emergencia_nombre` / `contacto_emergencia_telefono` | VARCHAR | NULL | Sólo mayor_65 (`telefono_medico`/`contacto_emergencia_telefono` quedan manuales — el PDF no separa nombre/teléfono) |
| `telefono_personal` / `correo_electronico` | VARCHAR | NULL | Sólo menor_65 |
| `notas_adicionales` | TEXT | NULL | — |
| `fecha_captura` | DATE | NOT NULL | — |
| `capturado_por` | INT UNSIGNED | NULL, FK → `usuarios.id_usuario` | — |
| `created_at` / `updated_at` | DATETIME | — | — |

**Tabla: `evaluaciones_antropometria`** — histórico acumulativo (una fila por evaluación, nunca se
sobreescribe).

| Columna | Tipo | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id_evaluacion` | INT UNSIGNED | PK, AUTO_INCREMENT | — |
| `id_atleta` | INT UNSIGNED | NOT NULL, FK → `atletas.id_atleta` | — |
| `fecha_antropometria` | DATE | NOT NULL | — |
| `asesor` | VARCHAR(150) | NULL | — |
| `edad_evaluacion` | TINYINT UNSIGNED | NULL | — |
| `peso_kg` / `estatura_cm` | DECIMAL(5,2) | NOT NULL | — |
| `imc` | DECIMAL(5,2) | NULL | Recalculado en PHP, nunca tomado de un Excel/PDF sin verificar |
| `clasificacion_imc` | ENUM('bajo_peso','normal','sobrepeso','obesidad','obesidad_severa','obesidad_morbida') | NULL | Vía `ssos_clasificar_imc()` |
| `indice_ponderal` | DECIMAL(6,3) | NULL | — |
| `pliegue_tricipital`, `pliegue_bicipital`, `pliegue_subescapular`, `pliegue_abdominal`, `pliegue_ileocrestal`, `pliegue_supraespinal`, `pliegue_muslo`, `pliegue_pierna` | DECIMAL(5,2) | NULL | Durnin & Womersley / Siri |
| `sumatoria_pliegues` | DECIMAL(6,2) | NULL | — |
| `perimetro_brazo_relajado_der/izq`, `perimetro_brazo_contraido_der/izq`, `perimetro_muneca_der/izq`, `perimetro_cintura_minima`, `perimetro_cadera_maxima`, `perimetro_muslo_der/izq`, `perimetro_pierna_relajada_der/izq`, `perimetro_pierna_contraida_der/izq` | DECIMAL(5,2) | NULL | Distinguen lado derecho/izquierdo |
| `diametro_humeral`, `diametro_femoral`, `diametro_estiloideo`, `diametro_biacromial`, `diametro_biiliocrestal` | DECIMAL(5,2) | NULL | — |
| `densidad_corporal` | DECIMAL(6,4) | NULL | — |
| `porcentaje_grasa_siri` / `masa_grasa_siri_kg` | DECIMAL(5,2) | NULL | — |
| `porcentaje_grasa_rocha` / `masa_osea_rocha_kg` | DECIMAL(5,2) | NULL | — |
| `masa_muscular_matiegka_kg` / `masa_residual_wurch_kg` | DECIMAL(5,2) | NULL | — |
| `clasificacion_grasa` | ENUM(9 valores) | NULL | — |
| `endomorfia` / `mesomorfia` / `ectomorfia` | DECIMAL(4,2) | NULL | Somatotipo Heath-Carter |
| `indice_cintura_cadera` | DECIMAL(4,3) | NULL | — |
| `clasificacion_riesgo_cintura` | ENUM('sin_riesgo','sin_peligro','peligro_metabolico') | NULL | — |
| `actividad_ejercicio_actual`, `frecuencia_ejercicio`, `duracion_por_sesion`, `intensidad_ejercicio` | VARCHAR | NULL | — |
| `capturado_por` | INT UNSIGNED | NULL, FK → `usuarios.id_usuario` | — |
| `created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | — |

**Tabla: `percentiles_sft_referencia`** — catálogo/seed (normas Rikli & Jones), no varía por atleta.

| Columna | Tipo | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id_percentil` | SMALLINT UNSIGNED | PK, AUTO_INCREMENT | — |
| `sexo` | ENUM('masculino','femenino') | NOT NULL | — |
| `edad_min` / `edad_max` | TINYINT UNSIGNED | NOT NULL | — |
| `variable` | ENUM('chair_sit_reach','back_scratch','chair_stand','arm_curl','time_up_go','two_min_step') | NOT NULL | — |
| `valor_min` / `valor_max` | DECIMAL(6,2) | NOT NULL | — |
| `unidad` | VARCHAR(20) | NOT NULL | cm/reps/segundos/pasos |

**Tabla: `evaluaciones_sft`** — histórico acumulativo (una fila por evaluación).

| Columna | Tipo | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id_evaluacion_sft` | INT UNSIGNED | PK, AUTO_INCREMENT | — |
| `id_atleta` | INT UNSIGNED | NOT NULL, FK → `atletas.id_atleta` | — |
| `fecha_evaluacion` | DATE | NOT NULL | — |
| `edad_evaluacion` | TINYINT UNSIGNED | NOT NULL | — |
| `sexo` | ENUM('masculino','femenino') | NOT NULL | — |
| `chair_sit_reach_cm`, `back_scratch_cm`, `functional_reach_cm` | DECIMAL(5,2) | NULL | — |
| `chair_stand_reps`, `arm_curl_reps` | TINYINT UNSIGNED | NULL | — |
| `time_up_go_seg`, `time_up_go_cognitivo_seg` | DECIMAL(4,2) | NULL | — |
| `two_min_step_pasos` | SMALLINT UNSIGNED | NULL | — |
| `semaforo_chair_sit_reach`, `semaforo_back_scratch`, `semaforo_chair_stand`, `semaforo_arm_curl`, `semaforo_time_up_go`, `semaforo_two_min_step`, `semaforo_general` | ENUM('verde','amarillo','rojo') | NULL | Calculado en PHP contra `percentiles_sft_referencia` |
| `observaciones` | TEXT | NULL | — |
| `evaluado_por` | INT UNSIGNED | NULL, FK → `usuarios.id_usuario` | — |
| `created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | — |

**Tabla: `evaluaciones_biomecanica`** — checklist Sentadilla Overhead (aún sin importador de PDF, ver
Roadmap).

| Columna | Tipo | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id_evaluacion_biomecanica` | INT UNSIGNED | PK, AUTO_INCREMENT | — |
| `id_atleta` | INT UNSIGNED | NOT NULL, FK → `atletas.id_atleta` | — |
| `fecha_evaluacion` | DATE | NOT NULL | — |
| `feet_flatten`, `feet_turn_out`, `heel_rises`, `knees_move_inward`, `excessive_forward_lean`, `lower_back_arches`, `lower_back_rounds`, `arms_fall_forward` | TINYINT(1) | NOT NULL, DEFAULT 0 | Un flag por compensación postural |
| `observaciones` | TEXT | NULL | — |
| `evaluado_por` | INT UNSIGNED | NULL, FK → `usuarios.id_usuario` | — |
| `created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | — |

#### `04_schema_agenda_sesiones.sql` (tablas nuevas)

**Tabla: `planes_macrociclo`**

| Columna | Tipo | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id_macro` | INT UNSIGNED | PK, AUTO_INCREMENT | — |
| `id_atleta` | INT UNSIGNED | NOT NULL, FK → `atletas.id_atleta` | — |
| `temporada` | VARCHAR(100) | NULL | — |
| `mesociclo` | ENUM('prep_general','prep_especifica','competitiva','transitorio') | NOT NULL | — |
| `mes` | ENUM(12 meses) | NOT NULL | — |
| `tipo_microciclo` | ENUM('ajuste','activacion','carga','competicion','impacto','recuperacion') | NULL | — |
| `volumen`, `velocidad`, `fuerza`, `resistencia`, `flexibilidad`, `tecnica`, `agilidad` | TINYINT UNSIGNED | NULL | Escala 0-10 de énfasis |
| `total_horas` | DECIMAL(5,2) | NULL | — |
| `dias_microciclo` | TINYINT UNSIGNED | NULL | — |
| `creado_por` | INT UNSIGNED | NULL, FK → `usuarios.id_usuario` | — |
| `created_at` / `updated_at` | DATETIME | — | — |

**Tabla: `sesiones_entrenamiento`**

| Columna | Tipo | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id_sesion` | INT UNSIGNED | PK, AUTO_INCREMENT | — |
| `id_atleta` | INT UNSIGNED | NOT NULL, FK → `atletas.id_atleta` | — |
| `id_cita` | INT UNSIGNED | NULL, FK → `disponibilidad_agenda.id_cita` | — |
| `id_staff` | INT UNSIGNED | NOT NULL, FK → `staff.id_staff` | — |
| `id_macro` | INT UNSIGNED | NULL, FK → `planes_macrociclo.id_macro` | — |
| `fecha_sesion` | DATE | NOT NULL | — |
| `numero_sesion` | SMALLINT UNSIGNED | NULL | Consecutivo dentro del microciclo/paquete |
| `enfoque` | VARCHAR(150) | NULL | — |
| `fase` | ENUM('prep_general','prep_especifica','competitiva','transitorio') | NULL | — |
| `rpe_sesion` | DECIMAL(3,1) UNSIGNED | NULL | Escala 1-10 (slider Pie de Cancha) |
| `notas_entrenador` | TEXT | NULL | — |
| `created_by` | INT UNSIGNED | NULL, FK → `usuarios.id_usuario` | — |
| `created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | — |

**Tabla: `detalles_ejercicio`**

| Columna | Tipo | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id_detalle` | INT UNSIGNED | PK, AUTO_INCREMENT | — |
| `id_sesion` | INT UNSIGNED | NOT NULL, FK → `sesiones_entrenamiento.id_sesion` | — |
| `bloque` | ENUM('masaje','movilidad','activacion','calentamiento','activacion_cadera','estiramiento_dinamico','integracion_movimiento','activacion_cognitiva','pliometria','parte_medular','vuelta_calma') | NOT NULL | — |
| `orden` | SMALLINT UNSIGNED | NOT NULL, DEFAULT 0 | — |
| `nombre_ejercicio` | VARCHAR(200) | NOT NULL | — |
| `sets` / `reps` | VARCHAR(20) | NULL | Texto libre, admite rangos ("2-4") |
| `intensidad` / `descanso` | VARCHAR(50) | NULL | — |
| `notas` | VARCHAR(255) | NULL | — |

#### `05_schema_alertas_membresias.sql`

**Tabla: `alertas_renovacion`**

| Columna | Tipo | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id_alerta` | INT UNSIGNED | PK, AUTO_INCREMENT | — |
| `id_atleta` | INT UNSIGNED | NOT NULL, FK → `atletas.id_atleta` | — |
| `id_membresia` | INT UNSIGNED | NOT NULL, FK → `membresias.id_membresia` | — |
| `tipo_alerta` | ENUM('amarillo','rojo') | NOT NULL | amarillo = 2 sesiones restantes, rojo = 0 |
| `sesiones_restantes_momento` | SMALLINT UNSIGNED | NOT NULL | — |
| `atendida` | TINYINT(1) | NOT NULL, DEFAULT 0 | — |
| `atendida_por` | INT UNSIGNED | NULL, FK → `usuarios.id_usuario` | — |
| `fecha_atendida` | DATETIME | NULL | — |
| `created_at` / `updated_at` | DATETIME | — | — |
| — | — | UNIQUE(`id_membresia`, `tipo_alerta`) | Evita duplicar la misma alerta |

#### Fuera de alcance de Athlos SSOS

`acadep_vocacional_leads` existe en el volcado real (`tourfindycom_athlosp_db.sql`) pero **no
pertenece a este proyecto** — es una tabla de un test vocacional/quiz distinto que comparte el mismo
servidor de base de datos. No se documenta aquí a detalle porque ningún archivo de `public/ssos/` la
referencia.

---

## 🖥️ BACKOFFICE SSOS v1.0 — APP STANDALONE PHP/BOOTSTRAP (`public/ssos/`)

> Se coloca dentro de `public/` deliberadamente: el pipeline `deploy.yml` existente
> (`next build` → `/out` → FTP) copia `public/*` verbatim al export estático, así que
> `/ssos` se despliega automáticamente en cada push a `main` **sin ningún paso manual
> de FTP nuevo**. No usa Bootstrap/ARF-Grid en el sitio público (Next.js) — es una app
> aislada, cero acoplamiento con los componentes React.

| Archivo | Ruta | Estado | Descripción |
| :--- | :--- | :--- | :--- |
| `.env.example` | `public/ssos/.env.example` | Producción | Plantilla INI versionable. `.env` real (local o producción) NUNCA se commitea — se coloca manualmente en el servidor, igual que la raíz. |
| `conexion.php` | `public/ssos/config/conexion.php` | Producción | Singleton PDO independiente de `api/conexion.php` (ese archivo vive fuera de `public/` y no se despliega hoy vía CI). Mismo patrón: cero credenciales hardcodeadas, prepared statements. |
| `helpers.php` | `public/ssos/config/helpers.php` | Producción | Sesión seguras (`httponly`, `samesite=Lax`, `secure` fuera de local), CSRF (`csrf_token()`/`csrf_validate()`), `e()` escape XSS, `require_login()`/`require_role()`, `redirect_to_dashboard()`, `log_sesion_evento()`. |
| `setup_admin.php` | `public/ssos/setup_admin.php` | Producción | Instalador one-click del primer Super Admin. Se autobloquea permanentemente en cuanto existe un usuario con rol `super_admin` (verificado en navegador real: 2do acceso muestra "Instalación completada..."). |
| `login.php` | `public/ssos/login.php` | Producción | Login con `password_verify`, bloqueo temporal tras 5 intentos fallidos (15 min), bitácora en `sesiones_log`, redirección por rol. |
| `logout.php` | `public/ssos/logout.php` | Producción | Destruye sesión + registra evento `logout`. |
| `dashboard/super_admin.php` / `admin.php` / `coach.php` | `public/ssos/dashboard/` | Stub | Landing por rol protegida con `require_role()`. Contenido operativo (gestión de usuarios, CRM, captura Pie de Cancha) pendiente — fuera del alcance de esta entrega. |
| `dashboard/_shell.php` | `public/ssos/dashboard/_shell.php` | Producción | Header/footer HTML compartido por los 3 dashboards (evita duplicar boilerplate Bootstrap). |
| `ssos-auth.css` | `public/ssos/css/ssos-auth.css` | Producción | Colores institucionales, ARF-Grid, modo día/noche vía `[data-theme]`. Cero estilos en línea, cero `!important`. |
| `ssos-theme.js` | `public/ssos/js/ssos-theme.js` | Producción | Toggle día/noche persistido en `localStorage` (`athlos_ssos_theme`), respeta `prefers-color-scheme` como default. |

**Verificado en navegador real (vía curl + cookie jar, flujo completo):** instalación del primer Super Admin → auto-bloqueo del instalador → login fallido rechazado → login correcto con `session_regenerate_id()` → acceso al dashboard por rol → logout → acceso denegado y redirigido a `login.php` tras cerrar sesión. Bug encontrado y corregido en esta misma pasada: `require_login()`/`redirect_to_dashboard()` usaban rutas relativas que se rompían al llamarse desde `dashboard/` (resolvían a `dashboard/login.php`, inexistente) — ahora usan `ssos_base_url()` (ruta absoluta desde `APP_URL`).

**⚠️ No confundir con `localhost:3000`:** `/ssos` es PHP puro, ejecutado por Apache (XAMPP). El servidor de desarrollo de Next.js (`pnpm dev`, puerto 3000) no tiene runtime PHP — sólo sirve estáticos. En local, `/ssos` se visita en `http://localhost/Athlos_Performance/public/ssos/` (vía XAMPP Apache); en producción, en `https://athlosperformance.tourfindy.com/ssos/`.

### Fase 4 — Layout base y Dashboards (2026-07-07)

| Archivo | Ruta | Estado | Descripción |
| :--- | :--- | :--- | :--- |
| `header.php` / `footer.php` | `public/ssos/partials/` | Producción | Layout compartido de la app autenticada: navbar + menú hamburguesa (Offcanvas de Bootstrap), toggle día/noche, botón flotante "Volver arriba". Reemplaza el antiguo `dashboard/_shell.php` (eliminado). |
| `main.css` | `public/ssos/css/main.css` | Producción | Colores institucionales, ARF-Grid, widgets, tabla responsive, semáforo verde/amarillo/rojo/sin_dato, componentes Pie de Cancha (`pdc-*`: tarjetas grandes, botón táctil, slider RPE, checklist tipo toggle). Cero `!important`, cero estilos en línea. |
| `main.js` | `public/ssos/js/main.js` | Producción | Consolida el toggle día/noche (reemplaza `js/ssos-theme.js`, eliminado) + botón volver arriba + sincronización visual del checklist Sentadilla Overhead + valor numérico grande del slider RPE. |
| `dashboard/admin.php` | `public/ssos/dashboard/admin.php` | Producción | Widgets reales (Clientes Activos, Evaluaciones Pendientes ≥90 días sin antropometría, Membresías por Vencer en 7 días) + tabla de últimos 10 clientes. Verificado con datos reales de prueba. |
| `dashboard/coach.php` | `public/ssos/dashboard/coach.php` | Producción | "Atletas del Día": citas de `disponibilidad_agenda` para `CURDATE()` filtradas por `id_staff` del coach en sesión, semáforo tomado de la última `evaluaciones_sft.semaforo_general` (`sin_dato` si no hay evaluación previa), botón táctil grande "Iniciar Sesión" por atleta. |
| `dashboard/coach_evaluacion.php` | `public/ssos/dashboard/coach_evaluacion.php` | Producción | Wizard de captura Pie de Cancha: slider RPE 1-10 con valor grande, checklist Sentadilla Overhead (8 botones táctiles tipo toggle, sin texto pequeño). Guarda en `evaluaciones_biomecanica` siempre; en `sesiones_entrenamiento` sólo si el coach tiene `id_staff` ligado (columna `NOT NULL` por diseño). |
| `dashboard/super_admin.php` | `public/ssos/dashboard/super_admin.php` | Producción | Migrado al layout compartido. Widgets (usuarios del BackOffice, atletas registrados) + últimos 10 eventos de `sesiones_log`. |
| `login.php` (actualizado) | `public/ssos/login.php` | Producción | Logo institucional, botón primario turquesa (`btn-ssos-turquesa`), sesión ahora también persiste `id_staff` para el filtro de "Atletas del Día". |

**Bug encontrado y corregido durante la verificación de Fase 4:** la consulta de `dashboard/coach.php` reutilizaba el mismo placeholder nombrado (`:id_staff`) dos veces en la misma sentencia (`WHERE (:id_staff IS NULL OR da.id_staff = :id_staff)`). Con `PDO::ATTR_EMULATE_PREPARES => false` (prepared statements reales, ya configurado en `conexion.php` desde la Fase 3) MySQL/MariaDB no permite repetir un placeholder nombrado — lanzaba `PDOException: SQLSTATE[HY093] Invalid parameter number`. Solución: la condición de filtro se arma condicionalmente en PHP antes de preparar la sentencia, con un solo placeholder.

**Verificado en navegador real con datos de prueba (staff + coach + admin + atleta + cita del día, creados y eliminados en esta misma sesión):** dashboard admin con conteos correctos, dashboard coach mostrando la tarjeta del atleta del día con semáforo `sin_dato`, envío del wizard con RPE=7 y 2 compensaciones marcadas → filas confirmadas en `evaluaciones_biomecanica` y `sesiones_entrenamiento`, aislamiento de rol confirmado (admin recibe 403 al intentar `dashboard/coach.php`).

### Fase 5 — API FrontDesk, Motor de Reglas de Negocio y Athlos Score™ (2026-07-08)

| Archivo | Ruta | Estado | Descripción |
| :--- | :--- | :--- | :--- |
| `05_schema_alertas_membresias.sql` | `knowledge/sql/` | Producción | Tabla `alertas_renovacion` (semáforo de consumo de sesiones). `UNIQUE(id_membresia, tipo_alerta)` + `ON DUPLICATE KEY UPDATE` evita duplicar alertas en evaluaciones sucesivas. |
| `leads_webhook.php` | `public/ssos/api/leads_webhook.php` | Producción | `POST` — ingesta de leads desde Next.js/motor conversacional. Auth por `X-Athlos-Api-Key` (no sesión). Consent Gate (403 si `consentimiento_legal !== true`), deduplicación por teléfono normalizado, upsert en `leads_prospectos`. |
| `athlos_score.php` | `public/ssos/api/athlos_score.php` | Producción | `GET ?id_atleta=` — devuelve el Athlos Score™ (0-100) + payload de radar. Auth doble: sesión BackOffice (admin/coach/super_admin) o `X-Athlos-Api-Key`. |
| `AthlosBusinessRules.php` | `public/ssos/config/AthlosBusinessRules.php` | Producción | Clase con `deducirSesionAtleta()` (descuento FIFO de sesiones + alertas amarillo/rojo) y `generarAthlosScore()` (30% Fuerza/SFT, 30% Movilidad/Biomecánica, 40% Composición/Grasa, con renormalización de pesos si falta alguna dimensión). |
| `helpers.php` (ampliado) | `public/ssos/config/helpers.php` | Producción | Nuevos: `ssos_normalize_phone()`, `api_apply_cors()` (usa `ALLOWED_ORIGINS` del `.env`), `api_require_key()`, `api_json_input()`, `api_respond()`. |
| `coach_evaluacion.php` (ampliado) | `public/ssos/dashboard/coach_evaluacion.php` | Producción | Tras guardar la evaluación, invoca `AthlosBusinessRules::deducirSesionAtleta()` y muestra el resultado (sesiones restantes / alerta) en la misma pantalla de éxito. |

**Bug encontrado y corregido en Fase 5:** ninguno nuevo de PDO (la lección de placeholders repetidos de Fase 4 ya se aplicó desde el inicio en estas queries). Verificado en navegador real: Consent Gate (403), auth por API key (401 sin ella), deduplicación por teléfono con distintos formatos de entrada, ciclo completo de deducción de 3→2 (alerta amarillo)→1→0 (membresía `agotada`, alerta rojo), y Athlos Score con matemática confirmada (`0.30×60 + 0.30×100 + 0.40×75 = 78`) vía sesión y vía API key.

### Fase 6 — Integración Next.js, Migración Histórica y Reporte Athlos Score™ (2026-07-08)

| Archivo | Ruta | Estado | Descripción |
| :--- | :--- | :--- | :--- |
| `ssos-client.ts` | `lib/ssos-client.ts` | Producción | Cliente TS del webhook, sin API key (sitio 100% estático, `output: "export"` — ver justificación en `RESUMEN_EJECUCION_SISTEMA.md` §7.1). Usa `NEXT_PUBLIC_SSOS_API_BASE` (default `/ssos`, mismo origen en producción). |
| `ConsentLeadDialog.tsx` (conectado) | `components/athlos/ConsentLeadDialog.tsx` | Producción | `handleLeadSubmit` ahora llama `submitLead()` y maneja éxito/error real (antes: payload en memoria sin red). Nuevo estado `isSubmitting`/`submitError`. |
| `.consent-gate__error` | `styles/athlos-theme.css` | Producción | Estilo de error del modal, sigue la convención del archivo (dark = default, `:root[data-theme="light"]` overridea). |
| `api_require_key_or_allowed_origin()` | `public/ssos/config/helpers.php` | Producción | Segundo modo de auth de `leads_webhook.php`: API key (servidor-a-servidor) O `Origin` en `ALLOWED_ORIGINS` (navegador). |
| `XlsxReader.php` | `public/ssos/config/XlsxReader.php` | Producción | Lector ZIP/XLSX puro en PHP (sin ext-zip, sólo `zlib`+`SimpleXML`) — la extensión `zip` no está garantizada en el hosting. Sólo soporta lo necesario (sharedStrings + una hoja), no reemplaza PhpSpreadsheet en general. |
| `migrar_excel.php` | `public/ssos/admin/migrar_excel.php` | Producción | Formulario de carga (no ruta fija) `require_role('super_admin')`. Idempotente: dedup de atletas por nombre normalizado, dedup de pagos por `(id_atleta, monto, fecha_pago, concepto_pago)`. Verificado con el archivo real: 17 atletas / 21 pagos en la 1ª corrida, 0/0 nuevos en la 2ª. |
| `ssos_generate_share_token()` / `ssos_verify_share_token()` | `public/ssos/config/helpers.php` | Producción | Token HMAC-SHA256 sin estado en DB (72h de vigencia) para el reporte público — evita URLs adivinables tipo `?id_atleta=4`. |
| `reporte.php` | `public/ssos/atleta/reporte.php` | Producción | Vista pública sin login (protegida por token), radar Chart.js (Fuerza/Movilidad/Composición), botón imprimir/exportar PDF vía `@media print`. Calcula el score invocando `AthlosBusinessRules` directamente (no vía HTTP) para no abrir una segunda ruta pública desprotegida. |
| `reporte.css` | `public/ssos/css/reporte.css` | Producción | Estilos del reporte + `@media print` (oculta nav/botones, fuerza fondo claro, `break-inside: avoid` en tarjetas). Cero `!important`. |
| `dashboard/admin.php` / `dashboard/coach.php` (ampliados) | `public/ssos/dashboard/` | Producción | Botón/enlace "Ver Reporte" por atleta, generando el token server-side en el momento de renderizar. |

**Bug encontrado y corregido en Fase 6:** mismo patrón que Fases 4/5 — placeholder PDO `:sesiones` repetido en el `INSERT` de `membresias` dentro de `migrar_excel.php`. Corregido con nombres distintos (`:sesiones_totales`/`:sesiones_restantes`).

### Fase 7 — Restructuración Total: `.env` único, Dashboard Unificado y UI (2026-07-08)

| Cambio | Archivos | Estado | Descripción |
| :--- | :--- | :--- | :--- |
| `.env` único | `core/.env`(.example), `public/ssos/config/conexion.php` | Producción | `public/ssos/.env`(.example) **eliminados**. `conexion.php` lee sólo `core/.env`. Certificación de conexión con fallback automático `localhost` → host público de `APP_URL`; `ssos_db()` lanza `RuntimeException` claro si ninguno responde. Verificado read-only contra la BD real de producción (23 tablas); escritura de prueba en prod bloqueada por el clasificador de seguridad — decisión del Super Admin: verificar en vivo él mismo (ver `RESUMEN_EJECUCION_SISTEMA.md` §8.1). |
| Dashboard Único | `public/ssos/dashboard/index.php` (nuevo); `admin.php`/`coach.php`/`super_admin.php` (eliminados) | Producción | Una sola vista, secciones `#control`/`#clientes`/`#pie-de-cancha` condicionadas por rol. `redirect_to_dashboard()` y `partials/header.php` actualizados. Verificado con las 3 cuentas de rol: cada una ve exactamente sus secciones. |
| Navbar corregido | `public/ssos/css/main.css`, `partials/header.php` | Producción | Bug real: `.ssos-theme-toggle` (`position:fixed` esquina superior derecha) se superponía con `.navbar-toggler` de Bootstrap (misma esquina). Corregido: toggle ahora vive dentro de `.ssos-navbar-actions` (flex, `gap:1rem`), sin `position:fixed`. |
| Limpieza de marca | Toda la interfaz + seed de `roles` en `01_schema_usuarios_rbac.sql` | Producción | "(AXON_DCD)" eliminado; badges ahora "Dirección de Laboratorio" / "Administración / Recepción" / "Coach Especialista". Menciones históricas en registros fechados de este documento se conservan intactas (bitácora, no interfaz). |

### Fase 25 — Estabilización de Producción v1.0: migración 07, corrección de tipado y Alta de Coaches por Administración (2026-07-13)

| Archivo | Ruta | Estado | Descripción |
| :--- | :--- | :--- | :--- |
| `07_schema_configuracion_agenda_publica.sql` (aplicada) | `knowledge/sql/` | Producción | Migración corrida en phpMyAdmin sobre la BD real de cPanel — confirmadas físicamente las columnas `solicitante_nombre/telefono/email` y el ENUM extendido (`pendiente_aprobacion`, `cancelada_por_cliente`) en `disponibilidad_agenda`. |
| `agenda_logica.php` (blindaje defensivo) | `public/ssos/agenda/agenda_logica.php` | Producción | Las 7 consultas de datos de la vista (`solicitudesPendientes`, `cancelacionesClienteRecientes`, `staffList`, `servicios`, `atletasActivos`, `citasSemana`, `clientesDelMes`) ahora degradan a array vacío ante `PDOException` en vez de HTTP 500 — mismo patrón "config dinámica, nunca error fatal" ya usado en `AgendaBusinessRules`. |
| `AgendaBusinessRules::colorParaStaff()` / `coloresParaStaffList()` (corrección de tipado) | `public/ssos/config/AgendaBusinessRules.php` | Producción | **Bug real de producción, no reproducible en XAMPP local:** el hosting cPanel usa `PDO_MYSQL` sin `mysqlnd`, que devuelve TODAS las columnas como `string` (incluidas `int(10) unsigned`). `colorParaStaff(int $idStaff)` con `strict_types=1` lanzaba `TypeError` al recibir el `id_staff` string de `array_column($staffList, 'id_staff')`. Firma ampliada a `int\|string` + cast `(int)` interno en ambos métodos. Verificado forzando IDs string contra una BD en memoria. |
| Alta de Coaches por `admin` | `public/ssos/dashboard/index.php` | Producción | El alta de usuarios del staff (antes exclusiva de `super_admin`) se extendió a `admin`, con candado server-side `$rolesCreablesPorRol` — `admin` sólo puede crear rol `coach` (nunca `admin` ni `super_admin`, incluso con POST manipulado); `super_admin` conserva su alcance previo (`coach`/`admin`/`atleta`, nunca `super_admin`). UI propia (modal con rol fijo, sin `<select>`) agregada en la pestaña "Clientes y Membresías" — deliberadamente sin exponer a `admin` la pestaña completa "Dirección y Control" (lista de todos los usuarios + bitácora de sesiones). |

**Estado declarado: PRODUCCIÓN STABLE v1.0.** Ver bitácora completa en `RESUMEN_EJECUCION_SISTEMA.md` §30 — este es el punto de referencia operativo vigente del proyecto.

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
| `AthlosHeader` | `components/athlos/AthlosHeader.tsx` | UI / Navigation | Produccion | Sin props. Renderiza marca, nav principal, CTA, menu movil y toggle de tema. |
| `ThemeToggle` | `components/athlos/ThemeToggle.tsx` | UI / Theme | Produccion | Sin props. Persiste `athlos_theme` en `localStorage` y actualiza `data-theme`. |
| `AthlosFooter` | `components/athlos/AthlosFooter.tsx` | UI / Layout | Produccion | Sin props. Renderiza contacto institucional y enlaces externos seguros. |
| `CtaButton` | `components/athlos/CtaButton.tsx` | UI / Action | Produccion | `href`, `children`, `variant`, atributos anchor. |
| `HeroSection` | `components/athlos/HeroSection.tsx` | UI / Landing | Produccion | Sin props. Hero mobile-first con copy oficial, CTAs y panel visual cientifico. |
| `ServiceCard` | `components/athlos/ServiceCard.tsx` | UI / Card | Produccion | `eyebrow`, `title`, `description`, `metric`. |
| `DifferentiationSection` | `components/athlos/DifferentiationSection.tsx` | UI / Landing | Produccion | Sin props. Copy oficial de diferenciacion + grid de pilares con iconografia minimalista + bloque `evidence-spotlight` con iframe social lazy. |
| `MethodologyTimeline` | `components/athlos/MethodologyTimeline.tsx` | UI / Landing | Produccion | Sin props. Timeline de 4 fases (`#metodologia`). Cada fase renderiza media propia: `AthlosVideoPlayer` (fases 01 y 03) o retrato de coach con tinte azul (fases 02 y 04), mas icono SVG flotante. |
| `EvaluationSplitSection` | `components/athlos/EvaluationSplitSection.tsx` | UI / Landing (Client) | Produccion | Sin props. Seccion `#evaluacion` con switcher de tabs accesible (`role="tablist"`) entre perfil Atletas/Longevidad. Mantiene estado `activeSegment` local. |
| `SegmentedSolutions` | `components/athlos/SegmentedSolutions.tsx` | UI / Landing | Produccion | `segment: AthlosSegment`. Renderiza ficha de evaluacion clinica, bloque de solucion segmentada, card de evidencia social lazy y CTA hacia `#consent-gate`. |
| `AthlosVideoPlayer` | `components/athlos/AthlosVideoPlayer.tsx` | UI / Media (Client) | Produccion | `src`, `poster`, `label`. Player custom: poster + boton play overlay, controles nativos solo tras interaccion del usuario, tinte azul `mix-blend-mode: color` permanente y filtro `grayscale/saturate` para neutralizar fondos de gimnasio convencional en el material grabado. |
| `TeamSection` | `components/athlos/TeamSection.tsx` | UI / Landing | Produccion | Sin props. Seccion `#staff` con grid de 3 coaches (1 col movil, 3 col `>=42rem`). Foto cuadrada `border-radius: 8px` sin filtro, pie de foto limpio (nombre + cargo) y expertise a 2 lineas (`line-clamp`). |
| `EvidenceSection` | `components/athlos/EvidenceSection.tsx` | UI / Landing | Produccion | Sin props. Seccion `#autoridad` (ancla ya reservada en `ATHLOS_NAV_ITEMS`). Copy oficial de autoridad cientifica + `certification-row` (ISAK/McKenzie/Mulligan) + `evidence-grid` con 3 cards `.media-card` (reuso de estilo, sin duplicar CSS) enlazando a Instagram/Facebook, `target="_blank"` + `rel="noopener noreferrer"`, sin embeds pesados. |
| `ConsentLeadDialog` | `components/athlos/ConsentLeadDialog.tsx` | UI / Modal (Client) | Produccion | Sin props (consume `useConsentGate()`). Modal con 3 pasos: `consent` (aviso de privacidad + checkbox obligatorio, REGLA-01), `form` (captura `nombreCompleto`, `telefono`, `objetivoDeclarado`) y `success`. Bloquea el avance al formulario hasta que el checkbox de consentimiento sea aceptado. `Escape` y clic en backdrop cierran el modal; `body[data-dialog="open"]` bloquea scroll. Focus trap manual verificado en navegador real (Módulo 7). **Actualizado en Fase 6 (2026-07-08):** `handleLeadSubmit` ahora envía el payload real a `POST /ssos/api/leads_webhook.php` vía `lib/ssos-client.ts` (`submitLead()`), con estados `isSubmitting`/`submitError` y manejo de los 4 casos de respuesta (éxito, Consent Gate 403, validación 422, origen no permitido 401). El endpoint correcto ya no es `api/webhook_mensajeria.php` (ese sigue siendo exclusivo de Meta/WhatsApp) sino el nuevo webhook FrontDesk del BackOffice SSOS. |
| `FinalCtaSection` | `components/athlos/FinalCtaSection.tsx` | UI / Landing | Produccion | Sin props. Seccion `.final-cta` (Section 7 del handoff), fondo fijo `#0E3A5D` independiente del tema claro/oscuro. CTA primario abre `ConsentLeadDialog`; CTA secundario abre WhatsApp directo del staff (`wa.me/52` + `ATHLOS_CONTACT.phone`). |
| `CtaButton` | `components/athlos/CtaButton.tsx` | UI / Action (Client) | Produccion | `href`, `children`, `variant: "primary" \| "secondary"`, atributos anchor. **Cambio de contrato:** si `href === "#consent-gate"`, renderiza un `<button>` que invoca `useConsentGate().openConsentGate()` en vez de navegar — convención ya usada por Hero, SegmentedSolutions y FinalCtaSection. Cualquier otro `href` se comporta como antes (`next/link`). Variant `"ghost"` retirado en Módulo 7 (nunca se usó). |
| `ConsentGateProvider` / `useConsentGate` | `lib/consentGateContext.tsx` | Context (Client) | Produccion | Estado global `isOpen` + `openConsentGate()` / `closeConsentGate()`. Montado una sola vez en `app/layout.tsx` envolviendo `children` y `ConsentLeadDialog`. |
| `--athlos-turquoise-text` | `styles/athlos-theme.css` | CSS Variable | Produccion | Variable de accesibilidad (Módulo 7): igual a `--athlos-turquoise` en modo oscuro (~6.4:1), remapeada a `#00707D` en modo claro (~5.5:1) para cumplir WCAG 2.1 AA en texto. Reemplaza todo uso de `color: var(--athlos-turquoise)` en 13 selectores; los usos como `background`/`border`/gradiente de botones no cambiaron (ya cumplian). |

### Artefactos Frontend Landing Athlos (Modulo 7.1 — Refinamiento post-auditoria visual humana)

| Archivo | Ruta | Tipo | Estado | Descripcion |
| :--- | :--- | :--- | :--- | :--- |
| `--body-gradient-end` | `styles/athlos-theme.css` | CSS Variable | Produccion | **Fix critico de contraste.** El degradado de `body` mezclaba `var(--page-bg)` (cambia por tema) con `var(--athlos-deep-blue)` (fijo, siempre oscuro). En paginas largas, esto generaba zonas oscuras inesperadas en modo claro (footer, certificaciones) donde el texto de modo claro (oscuro por diseño) se volvia casi invisible. Verificado y corregido: en modo oscuro `--body-gradient-end: var(--athlos-deep-blue)` (sin cambio visual); en modo claro `--body-gradient-end: #e7eff4` (tono claro). Confirmado en navegador real antes/despues del fix. |
| `.section-heading p:not(.section-kicker)` / `.site-footer p:not(.footer-kicker)` | `styles/athlos-theme.css` | CSS Fix | Produccion | Bug de especificidad CSS preexistente (detectado en Modulo 7, no corregido entonces): anulaba el color turquesa de TODOS los kickers de sección y el footer-kicker desde su creación. Corregido con `:not()`. |
| `SocialEmbedFacade` | `components/athlos/SocialEmbedFacade.tsx` | UI / Media (Client) | Produccion | `provider: "Instagram" \| "Facebook"`, `label`, `href`. Patron facade: muestra una card con boton play; al hacer clic monta un `<iframe loading="lazy" title="Video de Athlos Performance">` real (Instagram `/embed`, Facebook Video Plugin) via `getSocialEmbedSrc()`. Usado en `EvidenceSection` (Evidencia en Movimiento) reemplazando los enlaces externos simples. Verificado en navegador: el iframe solo existe en el DOM tras el clic. |
| `BackToTop` | `components/athlos/BackToTop.tsx` | UI / Navigation (Client) | Produccion | Sin props. FAB fijo inferior-derecha (`right/bottom: max(1rem, env(safe-area-inset-*))`), aparece tras `window.scrollY > 600`, `window.scrollTo({top:0, behavior:"smooth"})` al hacer clic. Montado globalmente en `app/layout.tsx`. Objetivo tactil 3.25rem (52px), cumple minimo recomendado de 44px. |
| `ContactSection` | `components/athlos/ContactSection.tsx` | UI / Landing | Produccion | Sin props. Seccion `#contacto` antes del Footer: dirección con enlace a Google Maps (`ATHLOS_MAPS_HREF`) y CTA de WhatsApp (`ATHLOS_WHATSAPP_HREF`). |
| `ATHLOS_WHATSAPP_HREF` / `ATHLOS_MAPS_HREF` | `lib/athlosContent.ts` | Content Registry | Produccion | Derivados de `ATHLOS_CONTACT.phone`/`.address`. `ATHLOS_WHATSAPP_HREF` centraliza el patron `wa.me/52<telefono>` (antes duplicado localmente en `FinalCtaSection`). `ATHLOS_MAPS_HREF` usa el esquema oficial de Google Maps URLs (`google.com/maps/search/?api=1&query=`). |
| `AthlosFooter` (actualizado) | `components/athlos/AthlosFooter.tsx` | UI / Layout | Produccion | El enlace "Contacto" cambio de `mailto:` a `href="#contacto"` (ancla suave a `ContactSection`), resolviendo el enlace huerfano reportado. |

**Hallazgo del Modulo 7.1 resuelto en Modulo 7.2:** el ancla huerfana `#programas` fue eliminada del nav (ver Modulo 7.2 abajo).

### Artefactos Frontend Landing Athlos (Modulo 7.2 — Refinamiento UX/UI y auditoria de flujo)

| Archivo | Ruta | Tipo | Estado | Descripcion |
| :--- | :--- | :--- | :--- | :--- |
| `ATHLOS_NAV_ITEMS` (reestructurado) | `lib/athlosContent.ts` | Content Registry | Produccion | Nav reescrito siguiendo el viaje organico del cliente: Metodología(`#metodologia`) → Evaluación Médica(`#evaluacion`) → Nuestro Equipo(`#staff`) → Evidencia(`#autoridad`) → Contacto(`#contacto`). Eliminado `{ label: "Programas", href: "#programas" }` (ancla huerfana, sin sección con ese id). Los 5 hrefs verificados en navegador: los 5 resuelven a un elemento real (`!!document.querySelector(href) === true`). |
| `COACHES` (4to integrante) | `lib/athlosContent.ts` | Content Registry | Produccion | Agregado `roberto-lopez` (`Coach Athlos Performance` — seguimiento de cargas y periodización). `TeamSection` ahora renderiza 4 coaches, verificado en navegador (`cardCount: 4`). |
| `TeamSection` (actualizado) | `components/athlos/TeamSection.tsx` | UI / Landing (Client) | Produccion | Pasa a `"use client"`. Cada `team-card` es ahora un `<button>` que abre `CoachLightbox` con la foto ampliada. `team-card__photo` cambia de `aspect-ratio: 1/1` a `4/5` + `object-position: center 18%` para no recortar rostros. Grid: 1 col movil, 2 col tablet (`42rem`), 4 col escritorio (`62rem`). |
| `CoachLightbox` | `components/athlos/CoachLightbox.tsx` | UI / Modal (Client) | Produccion | `photo`, `name`, `role`, `onClose`. Modal simple (no multi-paso): foto ampliada + nombre + rol. `Escape` cierra, clic en backdrop cierra, reusa `.consent-gate__close` para el boton de cierre. Verificado en navegador: abre con el nombre correcto, cierra con `Escape`. |
| `MethodologyTimeline` (fases 02/04 corregidas) | `components/athlos/MethodologyTimeline.tsx` | UI / Landing | Produccion | **Fix de narrativa:** las fases 02 y 04 mostraban fotos de coaches (`bernardo-lobo`, `luis-moctezuma`), rompiendo la narrativa de metodologia cientifica. Fase 02 ahora usa `.phase-data-panel` (panel abstracto CSS: grid + barras de datos, sin fotografia humana). Fase 04 ahora usa `AthlosVideoPlayer` con `ATHLOS_LOCAL_VIDEOS.seguimiento`. Verificado: `coachPhotosInTimeline: 0`, `videoPlayersCount: 3`. |
| `.phase-data-panel` | `styles/athlos-theme.css` | CSS | Produccion | Panel abstracto reutilizando el lenguaje visual del laboratorio (grid + radial turquesa) con 4 barras CSS de alturas variables, sin imagenes ni fotografia. |
| `ATHLOS_LOCAL_VIDEOS.seguimiento` / `ATHLOS_HERO_VIDEO` | `lib/athlosContent.ts` | Content Registry | Produccion | 3er video local (`entrenamiento-fuerza-controlada-laboratorio-athlos.mp4`) copiado a `public/media/` + poster extraido con `ffmpeg`. Reutilizado en dos contextos distintos: fondo ambiental del Hero (loop automatico) y `AthlosVideoPlayer` de la Fase 04 (clic para reproducir). |
| `HeroBackgroundVideo` | `components/athlos/HeroBackgroundVideo.tsx` | UI / Media (Client) | Produccion | `src`, `poster`. Reemplaza el panel abstracto (`hero-visual__grid` + `hero-visual__body-map`, ahora eliminados) por un video real en loop. `play()` solo se invoca si `prefers-reduced-motion` no esta activo (sin atributo `autoPlay` en el JSX, control 100% imperativo) — cumple el fallback estatico ya exigido en Modulo 2. Tinte `--athlos-deep-blue` al 50% (`mix-blend-mode: color`) para legibilidad de los chips flotantes (`z-index: 2`). Verificado en navegador: `paused: false`, `muted: true`, `loop: true`. |
| `media-portrait` / `hero-visual__grid` / `hero-visual__body-map` | `styles/athlos-theme.css` | CSS (eliminado) | Removido | Dead code tras los cambios anteriores: ya no existe ningun componente que renderice fotos de coach en `MethodologyTimeline` ni el panel abstracto original del Hero. |

Todo lo anterior verificado en navegador real (Chrome headless via CDP): overflow en 7 anchos (incluyendo 992px/1100px, zona de presion del nav de 5 items) sin regresion, foco del modal de Consent Gate sin regresion, capturas de pantalla de Hero/Equipo/Lightbox/Metodologia confirmando el resultado visual.

### Artefactos Frontend Landing Athlos (Modulo 7.3 — Ingesta de Contacto y Configuracion CI/CD)

| Archivo | Ruta | Tipo | Estado | Descripcion |
| :--- | :--- | :--- | :--- | :--- |
| `ATHLOS_CONTACT.address` (actualizado) | `lib/athlosContent.ts` | Content Registry | Produccion | Agregado codigo postal: `"...Baja California Sur 23000"`. |
| `ATHLOS_MAPS_HREF` (actualizado) | `lib/athlosContent.ts` | Content Registry | Produccion | Reemplazado el query de busqueda generico por el enlace oficial corto provisto: `https://maps.app.goo.gl/jxgaXbnfth5a8xmc9`. |
| `ContactIcons` | `components/athlos/ContactIcons.tsx` | UI / Icons | Produccion | 5 iconos SVG minimalistas (`PinIcon`, `WhatsappIcon`, `MailIcon`, `InstagramIcon`, `FacebookIcon`), `stroke="currentColor"` consistente con el resto del sistema de iconos del proyecto. |
| `ContactSection` (enriquecido) | `components/athlos/ContactSection.tsx` | UI / Landing | Produccion | Nueva lista `.contact-channels` con los 5 canales oficiales (direccion+Maps, WhatsApp, email, Instagram, Facebook), cada uno con icono + texto + enlace real. Verificado en navegador: 5 canales, 5 iconos, los 5 `href` exactos solicitados. |
| `AthlosFooter` (enriquecido) | `components/athlos/AthlosFooter.tsx` | UI / Layout | Produccion | Agregados iconos + enlaces directos a WhatsApp y Email (antes solo Instagram/Facebook/Contacto en texto plano). Verificado: 4 iconos, hrefs correctos. |
| `.contact-channels` / `.footer-links` (actualizado) | `styles/athlos-theme.css` | CSS | Produccion | Texto en `var(--text-secondary)`, iconos en `var(--athlos-turquoise-text)` — mismas variables ya auditadas en Modulo 7 para contraste WCAG AA en ambos temas. Verificado en navegador en modo claro: `rgb(54,85,108)` (texto) / `rgb(0,112,125)` (icono), valores ya validados. |
| `CtaButton` (contrato extendido) | `components/athlos/CtaButton.tsx` | UI / Action (Client) | Produccion | La rama `href === "#consent-gate"` ahora tambien reenvia el `onClick` recibido (con cast a `ReactMouseEvent<HTMLAnchorElement>`) antes de llamar `openConsentGate()`. Necesario para que el boton del menu movil pueda cerrar el menu Y abrir el modal en la misma interaccion. |
| `AthlosHeader` (CTA unificado) | `components/athlos/AthlosHeader.tsx` | UI / Navigation (Client) | Produccion | Los botones "Agendar" (desktop) y "Agendar Evaluación Inicial" (movil) cambiaron `href="#onboarding"` → `href="#consent-gate"`. Todo el trafico de conversion pasa por el mismo `ConsentLeadDialog`. Verificado en navegador: el boton del header se renderiza como `<button>` (no `<a>`) y abre el modal; en movil, cierra el menu Y abre el modal en el mismo clic. |
| `id="onboarding"` (eliminado) | `components/athlos/HeroSection.tsx` | Dead code | Removido | Quedo huerfano tras unificar los CTAs del header al Consent Gate — ningun enlace lo referenciaba ya. |
| `.github/workflows/deploy.yml` (reescrito) | `.github/workflows/deploy.yml` | CI/CD | Produccion | Pipeline reescrito de cero: `actions/checkout` → `pnpm/action-setup@v4` (version `11.5.1`, igual al `packageManager` de `package.json`) → `actions/setup-node@v4` (Node 20, cache pnpm) → `pnpm install --frozen-lockfile` → `pnpm build` (genera `/out` por `output: "export"`) → `SamKirkland/FTP-Deploy-Action@v4.3.5` subiendo `./out/` a `ftp.tourfindy.com:21`, usuario `ftp_user@athlosperformance.tourfindy.com`, directorio `/home/tourfindycom/public_html/athlosperformance/`. **Contraseña NUNCA hardcodeada** — usa `${{ secrets.FTP_PASSWORD }}`. Verificado localmente: `pnpm build` genera `/out` con `index.html`, `_next/` y `media/` (los 3 videos) presentes. |

**Accion pendiente del Arquitecto (obligatoria antes del primer push a `main` con este workflow):** crear el secreto `FTP_PASSWORD` en GitHub → Settings → Secrets and variables → Actions → New repository secret, con la contraseña real de `ftp_user@athlosperformance.tourfindy.com`. Sin este secreto, el paso de despliegue FTP fallara (el workflow de build SI se ejecutara correctamente).

### Artefactos Frontend Landing Athlos (Modulo 7.4 — Auditoria Estructural de Despliegue)

| Archivo | Ruta | Tipo | Estado | Descripcion |
| :--- | :--- | :--- | :--- | :--- |
| `pnpm-workspace.yaml` | `pnpm-workspace.yaml` | Config / pnpm | Produccion | **Correccion de raiz, no ubicacion original solicitada.** Se intento declarar `pnpm.onlyBuiltDependencies` dentro de `package.json` (instruccion original); pnpm 11.5.1 emite `[WARN] The "pnpm" field in package.json is no longer read by pnpm` — esa clave se ignora silenciosamente en esta version. La ubicacion real y funcional es `pnpm-workspace.yaml` (ver `https://pnpm.io/settings`). Contenido final: `onlyBuiltDependencies: [sharp]` + `allowBuilds: { sharp: true }` (esta segunda clave la escribe automaticamente `pnpm approve-builds`, no se edito a mano). |
| `package.json` (revertido) | `package.json` | Config | Produccion | El campo `"pnpm": { "onlyBuiltDependencies": [...] }` fue removido tras confirmar que pnpm 11.5.1 no lo lee — dejarlo habria sido config muerta y enganosa (Mandamiento 8). |
| Aprobacion de build de `sharp` | N/A (estado en `pnpm-workspace.yaml`) | Supply-chain security | Produccion | `onlyBuiltDependencies` por si solo NO desbloquea el script de instalacion ya bloqueado de una corrida previa — se requirio ejecutar `pnpm approve-builds sharp` (no interactivo, sin prompts) para registrar la aprobacion real. Verificado con reinstalacion limpia (`rm -rf node_modules && pnpm install --frozen-lockfile`): cero advertencias, cero `ERR_PNPM_IGNORED_BUILDS`. Binario nativo confirmado en `node_modules/.pnpm/@img+sharp-win32-x64@0.34.5/.../sharp-win32-x64.node`, `sharp` cargado y funcional (`vips 8.17.3`). |

**Diagnostico final:** `pnpm build` genera `/out` con integridad total — `index.html`, `_next/`, y los 3 videos `.mp4` + sus posters en `media/`. La arquitectura de dependencias esta blindada localmente (lockfile + `pnpm-workspace.yaml` versionados) y lista para que el pipeline de GitHub Actions ejecute `pnpm install --frozen-lockfile` sin bloqueos de supply-chain ni pasos de terminal improvisados.

### Artefactos Frontend Landing Athlos (Modulos 1-2)

| Archivo | Ruta | Tipo | Estado | Descripcion |
| :--- | :--- | :--- | :--- | :--- |
| `package.json` | `package.json` | Config / Next.js | Produccion | Scripts `dev`, `build`, `out`, `clean`; dependencias Next.js 16, React 19 y pnpm. |
| `next.config.mjs` | `next.config.mjs` | Config / Next.js | Produccion | Static export habilitado con imagenes no optimizadas para hosting estatico. |
| `tsconfig.json` | `tsconfig.json` | Config / TypeScript | Produccion | TypeScript estricto, path alias `@/*` y configuracion requerida por Next. |
| `RootLayout` | `app/layout.tsx` | Layout / App Router | Produccion | Metadata SEO base, viewport y carga global de `athlos-theme.css`. |
| `HomePage` | `app/page.tsx` | Page / Landing | Produccion | Ensambla header, hero, diferenciacion, metodologia, cards base y footer. |
| `athlos-theme.css` | `styles/athlos-theme.css` | Design System | Produccion | Variables CSS corporativas, light/dark mode, layout mobile-first, microinteracciones y `prefers-reduced-motion` reforzado en panel visual del hero. |
| `athlosContent` | `lib/athlosContent.ts` | Content Registry | Produccion | Navegacion, contacto institucional, pilares de diferenciacion y fases de metodologia, sin credenciales sensibles. |

### Artefactos Frontend Landing Athlos (Modulo 3)

| Archivo | Ruta | Tipo | Estado | Descripcion |
| :--- | :--- | :--- | :--- | :--- |
| `DIFFERENTIATION_PILLARS` | `lib/athlosContent.ts` | Content Registry | Produccion | Array de 3 pilares (titulo + descripcion) derivados del copy oficial de diferenciacion. |
| `METHODOLOGY_PHASES` | `lib/athlosContent.ts` | Content Registry | Produccion | Array de 4 fases (`step`, `icon`, `title`, `description`) con el copy oficial del handoff. |

### Artefactos Frontend Landing Athlos (Modulo 5)

| Archivo | Ruta | Tipo | Estado | Descripcion |
| :--- | :--- | :--- | :--- | :--- |
| `EVIDENCE_LINKS` | `lib/athlosContent.ts` | Content Registry | Produccion | Los 3 enlaces restantes de `knowledge/Links_Redes_socailes.txt` (de 6 totales: 3 ya asignados en Modulos 3-4). Agotan la totalidad de enlaces disponibles en el archivo fuente. |
| `CERTIFICATIONS` | `lib/athlosContent.ts` | Content Registry | Produccion | Array `{ name, description }` para ISAK / McKenzie / Mulligan, copy generico de cada certificacion (no atribuida a un coach especifico, evita credenciales no verificadas). |

### Artefactos Frontend Landing Athlos (Modulo 4)

| Archivo | Ruta | Tipo | Estado | Descripcion |
| :--- | :--- | :--- | :--- | :--- |
| `SEGMENT_CONTENT` | `lib/athlosContent.ts` | Content Registry | Produccion | Objeto `atletas` / `longevidad` con copy oficial de evaluacion clinica (Section 4) y solucion segmentada (Section 5), terminologia real de periodizacion (`Mesociclo`, `Microciclo`, `Sesion`) extraida de `knowledge/Menores_65/Menor_65_03 Ficha plan de sesion.xlsx` y metricas clinicas SFT/TUG de `knowledge/Mayores_65/Mayor_65_02 Ficha Evaluación adulto mayor.docx`. |
| `SOCIAL_EVIDENCE_LINKS` | `lib/athlosContent.ts` | Content Registry | Produccion | 2 de los 6 enlaces de `knowledge/Links_Redes_socailes.txt` asignados a Atletas (Instagram) y Longevidad (Facebook). |
| `AthlosSegment` | `lib/athlosContent.ts` | Type | Produccion | `"atletas" \| "longevidad"`. Union derivada de `SEGMENT_CONTENT`. |
| `DIFFERENTIATION_SPOTLIGHT` | `lib/athlosContent.ts` | Content Registry | Produccion | 3er enlace de `Links_Redes_socailes.txt` (Instagram Reel 2), usado en el iframe lazy de `DifferentiationSection`. |
| `ATHLOS_LOCAL_VIDEOS` | `lib/athlosContent.ts` | Content Registry | Produccion | Rutas publicas (`/media/*.mp4` + poster `.jpg`) de los 2 videos locales consumidos por `AthlosVideoPlayer` en `MethodologyTimeline`. El 3er video local (`entrenamiento-fuerza-controlada-laboratorio-athlos.mp4`) queda sin asignar. |
| `getSocialEmbedSrc()` | `lib/socialEmbed.ts` | Util | Produccion | `(provider: "Instagram" \| "Facebook", href: string): string`. Construye URL de iframe embed oficial por proveedor (Facebook Video Plugin / Instagram `/embed`). |
| `public/media/*` | `public/media/` | Static Assets | Produccion | Copias de los 2 videos locales + frames extraidos con `ffmpeg` como poster. Excepcion explicita `!public/media/*.mp4` agregada en `.gitignore` (decision del Arquitecto 2026-06-17): estos 2 archivos SI se versionan en Git para que el pipeline `deploy.yml` (basado en `actions/checkout`) los incluya automaticamente. La regla global `*.mp4` se mantiene para cualquier otro video fuera de esta carpeta. |
| `COACHES` | `lib/athlosContent.ts` | Content Registry | Produccion | Array de 3 coaches (`slug`, `name`, `role`, `expertise`) para `TeamSection`. Rol de Arturo Naranjo (`Asesor Athlos Performance`) tomado literalmente de `knowledge/Menores_65/Menor_65_02 DATOS ANTROMPOMETRÍA ATHLOS.xlsx` (celda `ASESOR: Arturo Naranjo`). |

**Reglas de Interfaz Aplicadas:**
- `ModalConsentGate`: Bloquea cualquier navegación ulterior en la sesión si `consentGateStatus !== 'aceptado'`. No puede cerrarse sin decisión explícita del usuario.
- `ReporteRendimiento`: Nunca renderiza métricas sin el objeto `percentiles{}`. Si el backend no los devuelve, muestra estado de error específico, no datos vacíos.
- `AuditLogViewer`: Solo accesible para usuarios con rol `admin_medico`. Registros marcados con `requiereRevision: true` se destacan visualmente con indicador rojo.
