# 🧬 RESUMEN DE EJECUCIÓN — MÓDULO BACKOFFICE SSOS v1.0

> **Fecha:** 2026-07-07
> **Alcance de esta entrega:** Fase 1 (Ingesta y Análisis) + Fase 2 (DDL de Base de Datos SQL) del Checklist Maestro.
> **Estado:** DDL generado, validado por ejecución real, pendiente de aprobación del Super Admin (AXON_DCD) antes de Fase 3 (Backend PHP), por REGLA DE PIEDRA PHP/SQL.

---

## 1. Decisión de arquitectura (reconciliación)

El proyecto ya contaba con un schema en producción documentado en `02_SYSTEM_CODEX_REGISTRY.md`
("Athlos Cognitive Engine v1.0"): `leads_prospectos`, `atletas`, `catalogo_servicios`, `staff`,
`disponibilidad_agenda`, `audit_log_medico`, más un frontend Next.js/React ya desplegado (sin Bootstrap).

La directriz "SSOS v1.0" describía nombres distintos (`clientes`, `citas_agenda`, `usuarios`/`roles`/`permisos`)
y exigía Bootstrap. Se consultó al Super Admin: **se extiende el schema existente** en vez de crear uno
paralelo. `atletas` sigue siendo la única entidad de cliente/atleta. El BackOffice SSOS (nueva app) puede
usar Bootstrap sin afectar el frontend público existente.

## 2. Ingesta de fuentes (Fase 1)

| Fuente | Método de extracción | Hallazgo clave |
| :--- | :--- | :--- |
| `clientes_cobranza/Clientes.xlsx` | Descompresión OOXML + parseo de `sharedStrings.xml`/`sheet.xml` (sin Python/librerías disponibles en el entorno) | Bitácora de pagos: Cliente, Nombre, Programa (paquete), Pago, Fecha. 22 registros reales, suma $33,387 MXN. |
| `Mayores_65/Mayor_65_01...docx` | Igual método sobre `word/document.xml` | Historial clínico con secciones Ejercicio/Dieta/Estilo de vida/Ocupación/Recreación/Médico + contacto de médico y emergencia. |
| `Mayores_65/Mayor_65_02 Ficha Evaluación...docx` | Igual método | Senior Fitness Test (7 pruebas) + tablas normativas completas por sexo y edad (60-94 años) + checklist de 8 compensaciones posturales en Sentadilla Overhead. |
| `Menores_65/Menor_65_01 Historial clínico.docx` | Igual método | Versión reducida del historial (sin sección Ocupación/Recreación, sin contacto médico — sí teléfono/email directos). |
| `Menores_65/Menor_65_02 DATOS ANTROPOMETRÍA...xlsx` | Igual método | Antropometría completa: pliegues (7 sitios), perímetros por lado, diámetros óseos, fórmulas Siri/Rocha/Durnin&Womersley/Matiegka/Wurch, somatotipo Heath-Carter. |
| `Mayores_65/Mayor_65_03` y `Menores_65/Menor_65_03 Ficha plan de sesion.xlsx` | Igual método | Layout idéntico entre ambos: hoja "Sesión" (bloques de ejercicio) + hoja "Macro" (periodización: mesociclo/microciclo/atributos). |

## 3. Artefactos SQL generados (Fase 2)

Ubicación: `knowledge/sql/`. Ejecutados de punta a punta contra una base de datos temporal en el
MariaDB 10.4.32 local (XAMPP) — **22 tablas creadas sin errores**, incluyendo los FKs diferidos entre
scripts (`usuarios.id_staff → staff`, `asistencias.id_cita → disponibilidad_agenda`).

1. `01_schema_usuarios_rbac.sql` — `roles`, `permisos`, `rol_permisos`, `usuarios`, `sesiones_log`.
2. `02_schema_clientes_membresias.sql` — reproduce `leads_prospectos`/`atletas`/`catalogo_servicios` + nuevas `membresias`, `pagos_asistencia`, `asistencias`.
3. `03_schema_evaluaciones_clinicas.sql` — `historial_clinico`, `evaluaciones_antropometria`, `percentiles_sft_referencia` (con seed de normativas SFT), `evaluaciones_sft`, `evaluaciones_biomecanica`.
4. `04_schema_agenda_sesiones.sql` — reproduce `staff`/`disponibilidad_agenda`/`audit_log_medico` + nuevas `planes_macrociclo`, `sesiones_entrenamiento`, `detalles_ejercicio`.

Detalle de columnas, tipos y justificación de cada tabla: ver sección "EXTENSIÓN DE SCHEMA — MÓDULO
BACKOFFICE SSOS v1.0" en `02_SYSTEM_CODEX_REGISTRY.md`.

## 4. Fase 3 — BackOffice SSOS (aprobada y entregada 2026-07-07)

El Super Admin aprobó el DDL y confirmó stack **PHP/Bootstrap standalone** en `public/ssos/` (ver sección
"BACKOFFICE SSOS v1.0" en `02_SYSTEM_CODEX_REGISTRY.md` para el detalle de archivos). Se aplicaron los 4
scripts contra una base de datos local persistente (`athlos_engine_db`, MariaDB 10.4.32 XAMPP) y se
verificó el flujo completo en navegador real (curl + cookie jar): instalación del Super Admin →
auto-bloqueo → login → redirección por rol → logout → control de acceso. Un bug de rutas relativas en
`require_login()`/`redirect_to_dashboard()` fue encontrado y corregido durante esta verificación.

### 4.1 Credenciales de producción recibidas — estado de cada una

| Credencial | Estado | Motivo |
| :--- | :--- | :--- |
| DB (`tourfindycom_athlosp_db`) | **No aplicada aún** | `DB_HOST` de producción es `localhost` (típico de hosting compartido cPanel): sólo es alcanzable *desde el propio servidor*, no desde esta máquina de desarrollo. No hay forma de ejecutar el DDL remotamente sin acceso al servidor (phpMyAdmin/cPanel/SSH). |
| FTP (`ftp.tourfindy.com`) | **No usada** | El proyecto ya tiene un pipeline CI/CD propio (`deploy.yml`): push a `main` → GitHub Actions → build Next.js → FTP a `/out`. Hacer un FTP manual desde este agente duplicaría/desalinearía ese flujo ya establecido y validado por el equipo. `public/ssos/` se colocó deliberadamente dentro de `public/` para que el pipeline existente lo despliegue automáticamente en el próximo push — cero pasos FTP nuevos. |
| SMTP (`hola@athlosperformance.tourfindy.com`) | **No usada** | Ningún archivo de esta entrega envía correo todavía. Las credenciales ya están documentadas en `core/.env` (bóveda local) para cuando se implemente notificación por email. |
| Contraseñas reales | **Ya estaban disponibles localmente** en `core/.env` ("BÓVEDA DE SECRETOS DE PRODUCCIÓN REAL"), gitignoreadas. No se pidieron ni se pegaron en el chat — se leyeron directamente de ese archivo. |

### 4.2 Acción manual pendiente del Super Admin antes de que el sistema funcione en producción

1. **Ejecutar los 4 scripts de `knowledge/sql/` contra `tourfindycom_athlosp_db`** vía phpMyAdmin/cPanel del hosting (en orden: 01 → 02 → 03 → 04). No se puede hacer desde esta sesión de desarrollo por la restricción de `DB_HOST=localhost` explicada arriba.
2. **Copiar `core/.env`** (o un archivo `.env` equivalente con esos mismos valores de `[BASE_DE_DATOS]`) a `public_html/athlosperformance/ssos/.env` en el servidor, vía FTP/cPanel — igual que ya se documentó para el `.env` raíz que usa `api/conexion.php`. Este archivo nunca viaja por git ni por el pipeline de CI (por diseño, `.gitignore` lo excluye).
3. Hacer push a `main` (o merge del PR que se genere) para que `deploy.yml` publique `public/ssos/` automáticamente.
4. Visitar `https://athlosperformance.tourfindy.com/ssos/setup_admin.php` una sola vez para crear el Super Admin real de producción (el archivo se autobloquea después).

## 5. Fase 4 — Dashboards y Pie de Cancha (entregada 2026-07-07)

Layout compartido (`partials/header.php`/`footer.php`, `css/main.css`, `js/main.js`) con navbar +
menú hamburguesa (Offcanvas), toggle día/noche y botón "Volver arriba". Dashboard Admin con widgets
reales y tabla de clientes; Dashboard Coach "Pie de Cancha" con tarjetas grandes de "Atletas del Día"
(semáforo desde la última evaluación SFT) y wizard de captura (slider RPE + checklist Sentadilla
Overhead de 8 botones táctiles). Verificado extremo a extremo con datos de prueba reales. Bug
encontrado y corregido: reutilización inválida de un placeholder PDO nombrado en la query de
"Atletas del Día" (`PDO::ATTR_EMULATE_PREPARES => false` no permite repetir `:id_staff`).

⚠️ **`/ssos` no es accesible en `localhost:3000`** — ese puerto es el dev server de Next.js (sin
runtime PHP). En local se visita vía Apache/XAMPP: `http://localhost/Athlos_Performance/public/ssos/`.

## 6. Fase 5 — API FrontDesk, Motor de Reglas de Negocio y Athlos Score™ (entregada 2026-07-08)

Nuevo script de schema: `knowledge/sql/05_schema_alertas_membresias.sql` (tabla `alertas_renovacion`,
aplicado y validado antes de escribir PHP, por REGLA DE PIEDRA). Nuevos helpers en `config/helpers.php`:
`ssos_normalize_phone()`, `api_apply_cors()`, `api_require_key()`, `api_json_input()`, `api_respond()`.

### 6.1 Endpoint: `POST /ssos/api/leads_webhook.php`

Ingesta de leads desde el frontend Next.js / motor conversacional. Autenticado con cabecera
`X-Athlos-Api-Key` (secreto compartido en `.env` → `API_WEBHOOK_SECRET`, **no** la sesión del
BackOffice — este endpoint lo llaman sistemas, no personas logueadas). Aplica el Consent Gate
(REGLA-01) y deduplica por teléfono normalizado (REGLA-04) antes de tocar `leads_prospectos`.

**Payload que el frontend Next.js debe enviar:**
```json
{
  "nombre_completo": "Juan Pérez",
  "telefono": "6121234567",
  "objetivo_salud": "Bajar % de grasa y mejorar rendimiento en ciclismo",
  "consentimiento_legal": true,
  "canal_origen": "whatsapp",
  "email": "juan@correo.com"
}
```
`canal_origen` y `email` son opcionales (`canal_origen` cae a `"whatsapp"` si falta o no es
`whatsapp|instagram|facebook` — ese ENUM ya existe en producción y no se modificó en esta entrega).

**Respuestas:**
| Caso | HTTP | Body |
| :--- | :--- | :--- |
| Falta o no coincide `X-Athlos-Api-Key` | 401 | `{"status":"error","code":"UNAUTHORIZED",...}` |
| `consentimiento_legal` falso/ausente | 403 | `{"status":"error","code":"LEGAL_PRIVACY_VIOLATION",...}` |
| Validación de campos falla | 422 | `{"status":"error","code":"VALIDATION_ERROR","errors":[...]}` |
| Lead nuevo | 201 | `{"status":"success","action":"created","id_lead":123,"consent_gate_status":"aceptado"}` |
| Lead existente (mismo teléfono) | 200 | `{"status":"success","action":"updated","id_lead":123,"consent_gate_status":"aceptado"}` |

Verificado en navegador real (curl): rechazo sin API key, rechazo con consentimiento falso/ausente,
creación válida, y actualización por deduplicación (mismo teléfono con distinto formato de captura
`"6129998877"` vs `"612 999 8877"` → mismo `id_lead`, `objetivo_declarado` actualizado al más reciente).

### 6.2 Motor de reglas de negocio: `config/AthlosBusinessRules.php`

- **`deducirSesionAtleta(PDO $db, int $id_atleta): array`** — se invoca desde
  `dashboard/coach_evaluacion.php` después de guardar cada evaluación. Descuenta 1 sesión de la
  membresía activa más antigua (FIFO) con saldo > 0. Si el atleta no tiene membresía activa con
  saldo, no falla — devuelve `deducted: false` (estado de negocio válido, no error). Al cruzar 2
  sesiones restantes registra alerta `amarillo`; al llegar a 0, marca la membresía `agotada` y
  registra alerta `rojo`, en `alertas_renovacion` (`UNIQUE(id_membresia, tipo_alerta)` evita duplicar
  la alerta en evaluaciones posteriores — la refresca).
- Verificado con 3 evaluaciones reales sobre una membresía de 3 sesiones: 3→2 (alerta amarillo),
  2→1, 1→0 (membresía marcada `agotada`, alerta rojo). Ambas alertas confirmadas en `alertas_renovacion`.

### 6.3 Endpoint: `GET /ssos/api/athlos_score.php?id_atleta=123`

- **`generarAthlosScore(PDO $db, int $id_atleta): array`** — índice 0-100 ponderado: 30% Fuerza
  (última `evaluaciones_sft.semaforo_general`: verde=100/amarillo=60/rojo=20), 30% Movilidad
  (última `evaluaciones_biomecanica`: `(8 - compensaciones marcadas) / 8 × 100`), 40% Composición
  (última `evaluaciones_antropometria.clasificacion_grasa`, mapeada 5-100). Dimensiones sin datos se
  excluyen y el peso se renormaliza entre las disponibles — nunca bloquea el reporte por falta de
  una sola evaluación, pero tampoco inventa un valor (REGLA-05).
- Auth: sesión activa del BackOffice (`admin`/`coach`/`super_admin`) **o** `X-Athlos-Api-Key` (para
  que el frontend Next.js también pueda pedirlo directamente).
- Respuesta lista para un gráfico de radar:
```json
{
  "status": "success",
  "atleta": { "id_atleta": 2, "nombre_completo": "Atleta Score Test" },
  "athlos_score": 78,
  "dimensiones": {
    "fuerza": { "score": 60, "fuente": "evaluaciones_sft", "fecha": "2026-07-08" },
    "movilidad": { "score": 100, "fuente": "evaluaciones_biomecanica", "fecha": "2026-07-08" },
    "composicion": { "score": 75, "fuente": "evaluaciones_antropometria", "fecha": "2026-07-08" }
  },
  "radar": { "labels": ["Fuerza", "Movilidad", "Composición"], "valores": [60, 100, 75] }
}
```
Verificado en navegador real: cálculo `0.30×60 + 0.30×100 + 0.40×75 = 78` confirmado, acceso vía
sesión de coach y vía API key ambos exitosos, acceso sin ninguna credencial → 401.

### 6.4 Nueva variable de entorno

`public/ssos/.env` (local) y `.env.example` (versionable) ganan la sección `[API_INTERNA]` con
`API_WEBHOOK_SECRET` y `[SEGURIDAD_CORS]` con `ALLOWED_ORIGINS`. **Acción pendiente del Super Admin
en producción:** generar un secreto real (`php -r "echo bin2hex(random_bytes(32));"`) y agregarlo al
`.env` de producción (mismo archivo que ya se documentó en la sección 4.2) — el frontend Next.js debe
enviarlo en `X-Athlos-Api-Key` en cada llamada a `/ssos/api/*`.

## 7. Próximos pasos (fuera del alcance de esta entrega)

- CRUD de usuarios para que el Super Admin cree cuentas Admin/Coach sin tocar la DB manualmente.
- Vincular `athlos_score.php` a una vista de radar real en el frontend Next.js (Chart.js/Recharts).
- Notificaciones por email (SMTP) — credenciales ya disponibles en `core/.env`, sin consumir todavía.
- Panel de `alertas_renovacion` en el Dashboard Admin (hoy sólo se generan y persisten, no se listan).
