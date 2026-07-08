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

## 5. Próximos pasos (fuera del alcance de esta entrega)

- Middleware de autenticación ya cubre login/logout/roles; falta CRUD de usuarios para que el Super Admin cree cuentas Admin/Coach sin tocar la DB manualmente.
- Fase 4: BackOffice "Pie de Cancha" (captura ≤30s) y Wizard SFT con semaforización automática vía `percentiles_sft_referencia`.
- Notificaciones por email (SMTP) — credenciales ya disponibles en `core/.env`, sin consumir todavía.
