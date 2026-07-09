# Módulo de Calendario / Agenda de Citas — Arquitectura Genérica

> Este documento describe un módulo de calendario reutilizable, agnóstico de marca y de dominio de
> negocio. No contiene nombres de producto, empresa ni cliente — está escrito para poder copiarse a
> cualquier proyecto nuevo (clínica, salón, consultoría, gimnasio, taller) sin adaptar nomenclatura.
> Usa términos genéricos: "especialista" (quien atiende), "cliente" (quien es atendido), "servicio"
> (lo que se agenda), "franja" (bloque de una hora en la matriz).

---

## 1. Esquema de Base de Datos

Motor de referencia: MySQL/MariaDB, InnoDB, `utf8mb4_unicode_ci`. Los nombres de tabla asumen que ya
existen entidades base `clientes` y `especialistas` (o equivalentes) con llave primaria entera —
ajustar los `FOREIGN KEY` a los nombres reales del proyecto destino.

### 1.1 Tabla: `citas`

Una fila por cita agendada (pasada, presente o futura). Es el corazón del módulo — todo lo demás
(matriz visual, sincronización externa, alertas) se deriva de esta tabla.

```sql
CREATE TABLE IF NOT EXISTS citas (
    id_cita INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_cliente INT UNSIGNED NULL COMMENT 'NULL si es un prospecto sin ficha todavía',
    id_especialista INT UNSIGNED NOT NULL,
    id_servicio INT UNSIGNED NOT NULL,
    fecha_cita DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    cupo_maximo_franja TINYINT UNSIGNED NOT NULL DEFAULT 4 COMMENT 'Límite de citas simultáneas en esa franja horaria, para el semáforo de disponibilidad',
    estatus_cita ENUM('disponible','reservada','confirmada','cancelada','completada','no_show') NOT NULL DEFAULT 'reservada',
    notas_previas TEXT NULL,
    origen ENUM('manual','google_calendar','apple_calendar','api_externa') NOT NULL DEFAULT 'manual' COMMENT 'De dónde vino la cita — evita que un webhook externo pise una cita creada localmente',
    id_evento_externo VARCHAR(255) NULL COMMENT 'ID del evento en el proveedor externo (Google eventId / UID de .ics), para poder actualizarlo/borrarlo en sync bidireccional',
    confirmacion_enviada TINYINT(1) NOT NULL DEFAULT 0,
    recordatorio_enviado TINYINT(1) NOT NULL DEFAULT 0,
    creado_por INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_cita),
    KEY idx_citas_fecha_hora (fecha_cita, hora_inicio),
    KEY idx_citas_especialista (id_especialista),
    KEY idx_citas_cliente (id_cliente),
    KEY idx_citas_evento_externo (id_evento_externo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Por qué `cupo_maximo_franja` vive en la fila y no sólo en una constante de PHP:** permite que el
límite cambie por época (ej. temporada alta vs. baja) sin tocar código, y que una franja ya creada
conserve el cupo que tenía al momento de agendarse aunque la política cambie después.

### 1.2 Tabla: `disponibilidad`

Define QUÉ horarios existen para agendar, separado de las citas ya tomadas. Sin esta tabla, cualquier
cambio de horario operativo (ej. "los sábados ahora cerramos a las 14:00") requeriría tocar código;
con ella, es una fila de configuración.

```sql
CREATE TABLE IF NOT EXISTS disponibilidad (
    id_disponibilidad INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_especialista INT UNSIGNED NULL COMMENT 'NULL = regla general aplicable a todos los especialistas',
    dia_semana TINYINT UNSIGNED NOT NULL COMMENT '1=Lunes ... 7=Domingo (ISO-8601)',
    hora_apertura TIME NOT NULL,
    hora_cierre TIME NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_disponibilidad),
    KEY idx_disponibilidad_dia (dia_semana)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ejemplo de seed: Lunes-Viernes 06:00-22:00, Sábado 07:00-15:00, Domingo cerrado (sin fila = cerrado)
INSERT IGNORE INTO disponibilidad (dia_semana, hora_apertura, hora_cierre) VALUES
(1, '06:00:00', '22:00:00'),
(2, '06:00:00', '22:00:00'),
(3, '06:00:00', '22:00:00'),
(4, '06:00:00', '22:00:00'),
(5, '06:00:00', '22:00:00'),
(6, '07:00:00', '15:00:00');
```

**Regla de diseño:** un día de la semana SIN fila en esta tabla se interpreta como cerrado. Así,
"ocultar el domingo" es simplemente "no insertar la fila del domingo" — cero lógica condicional
especial en el código de la matriz visual.

### 1.3 Tabla: `especialistas_colores`

Separada de la tabla base de especialistas a propósito: es un dato puramente de presentación (no de
negocio), así que vive en su propia tabla 1:1 en vez de ensuciar la entidad principal con una columna
que sólo le importa a la UI del calendario.

```sql
CREATE TABLE IF NOT EXISTS especialistas_colores (
    id_especialista INT UNSIGNED NOT NULL,
    color_hex CHAR(7) NOT NULL COMMENT 'Formato #RRGGBB, validado en capa de aplicación',
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_especialista)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Paleta sugerida (contraste AA sobre fondo blanco y sobre fondo oscuro): asignar por rotación de un
arreglo fijo de 8-10 colores predefinidos al dar de alta un especialista, en vez de dejar un color
picker completamente libre — evita colisiones de color entre especialistas activos y mantiene
consistencia visual.

### 1.4 Tabla: `sincronizacion_tokens`

Almacena las credenciales/tokens necesarios para sincronizar con calendarios externos. Diseñada para
soportar múltiples proveedores por especialista (uno puede tener Google Y Apple simultáneamente).

```sql
CREATE TABLE IF NOT EXISTS sincronizacion_tokens (
    id_token INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_especialista INT UNSIGNED NOT NULL,
    proveedor ENUM('google_calendar','apple_calendar') NOT NULL,
    access_token TEXT NULL COMMENT 'Sólo Google (OAuth2) — cifrado a nivel de aplicación antes de guardar, nunca en texto plano',
    refresh_token TEXT NULL COMMENT 'Sólo Google — permite renovar access_token sin re-consentimiento',
    token_expira DATETIME NULL COMMENT 'Sólo Google',
    calendario_externo_id VARCHAR(255) NULL COMMENT 'ID del calendario en el proveedor (Google calendarId)',
    webhook_channel_id VARCHAR(255) NULL COMMENT 'Sólo Google — ID del canal de notificaciones push (expira, se renueva)',
    webhook_channel_expira DATETIME NULL,
    webcal_uid VARCHAR(64) NULL COMMENT 'Sólo Apple/webcal — token opaco impredecible en la URL pública del feed .ics (ver §3.2)',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_token),
    UNIQUE KEY uq_sync_especialista_proveedor (id_especialista, proveedor),
    KEY idx_sync_webcal_uid (webcal_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Regla de seguridad no negociable:** `access_token`/`refresh_token` nunca se guardan en texto plano
— se cifran con una llave simétrica (AES-256-GCM) almacenada en variables de entorno, nunca en el
repositorio. `webcal_uid` no es secreto per se (es una URL "difícil de adivinar", no autenticación
real), pero igual debe generarse con un generador criptográficamente seguro (mínimo 32 bytes
aleatorios en base62), nunca un incremental ni un hash predecible del id del especialista.

---

## 2. Arquitectura de Vistas

### 2.1 Desktop — matriz 80% + sidebars 20%

```
┌─────────────────────────────────────────────────────────────────────┐
│  Header: navegación de semana (◀ Semana anterior | JUL 2026 | ▶)     │
├───────────────┬─────────────────────────────────────────┬───────────┤
│               │                                           │           │
│  Sidebar      │        Matriz central (≈80% ancho)        │  Sidebar  │
│  Izquierdo    │                                           │  Derecho  │
│  (≈10-12%)    │   Lun   Mar   Mié   Jue   Vie   Sáb        │  (≈10-12%)│
│               │  ┌────┬────┬────┬────┬────┬────┐         │           │
│  Clientes     │  │    │    │    │    │    │    │  06:00  │  Especia- │
│  del mes,     │  ├────┼────┼────┼────┼────┼────┤         │  listas   │
│  barra de     │  │    │    │    │    │    │    │  07:00  │  activos, │
│  progreso     │  ├────┼────┼────┼────┼────┼────┤   ...   │  color    │
│  de sesiones  │  │    │    │    │    │    │    │         │  asignado │
│               │  └────┴────┴────┴────┴────┴────┘  22:00  │           │
└───────────────┴─────────────────────────────────────────┴───────────┘
```

- **Semana continua, no "mes en cuadrícula":** se navega semana por semana (flechas ◀▶), y cuando una
  semana cruza de un mes a otro, el header de cada columna de día muestra explícitamente
  "Lun 29 Jun" / "Mar 30 Jun" / "Mié 1 Jul" — la transición es una etiqueta de fecha completa, no un
  salto visual brusco. El título superior muestra ambos meses cuando aplica (ej. "Jun – Jul 2026").
- **Domingo excluido de la matriz por completo** (ni siquiera se renderiza una columna vacía) —
  consecuencia directa de que `disponibilidad` no tiene fila para `dia_semana = 7` (§1.2), la consulta
  que arma las columnas de la semana simplemente nunca produce esa columna.
- **Celdas de franja no operativa** (ej. sábado después de las 15:00) se renderizan deshabilitadas
  (gris, sin click, sin drop) en vez de ocultarse — mantiene la matriz rectangular y visualmente
  predecible en lugar de un layout irregular por día.
- **Semáforo de ocupación por franja:** color de fondo de la celda según citas activas vs. cupo
  máximo (ej. 1-2 = verde, 3 = amarillo, 4 = rojo/bloqueada) — visualmente separado del color de cada
  cita individual (que es el color del especialista asignado, §1.3), para no mezclar dos sistemas de
  color en la misma superficie.

### 2.2 Sidebar izquierdo — clientes del mes

Lista de clientes con actividad en el mes visible, cada uno con:
- Nombre.
- Barra de progreso: `sesiones_consumidas / sesiones_totales` de su membresía/paquete activo.
- Alerta visual (ámbar) cuando `sesiones_restantes <= 2` — mismo umbral que un motor de alertas de
  renovación si el proyecto ya tiene uno; si no, es un cálculo directo `totales - restantes`.

### 2.3 Sidebar derecho — especialistas activos

Lista de especialistas activos, cada uno con su swatch de color (`especialistas_colores`, §1.3) y un
checkbox de "mostrar/ocultar en la matriz" (filtro client-side, no recarga el servidor) — permite al
usuario aislar visualmente la agenda de un solo especialista sin perder el resto de la semana cargada.

### 2.4 Móvil — pantalla completa táctil

- La matriz de semana NO se intenta comprimir en móvil (7 columnas serían ilegibles) — se reemplaza
  por una vista de un solo día con navegación ◀ día anterior / día siguiente ▶, franjas horarias
  apiladas verticalmente.
- Contenedor raíz con `height: 100dvh` (no `100vh`) — `dvh` (dynamic viewport height) evita que la
  barra de direcciones del navegador móvil, al aparecer/desaparecer durante el scroll, cause saltos de
  layout o un "scroll fantasma" de la página completa. El scroll queda contenido *dentro* de la lista
  de franjas del día, nunca en el `<body>`.
  ```css
  .calendario-vista-movil {
      height: 100dvh;
      overflow-y: auto;
      overscroll-behavior: contain; /* evita que el scroll "se fugue" al body/PWA shell */
  }
  ```
- Los sidebars de desktop (clientes del mes, especialistas) se colapsan a paneles deslizables
  (`<details>`/offcanvas) accesibles por botón, nunca visibles simultáneamente con la matriz en una
  pantalla angosta.

---

## 3. Lógica de Sincronización Externa

### 3.1 Google Calendar (OAuth2 + Webhooks push)

**Flujo de autorización (una vez por especialista):**
1. El especialista hace clic en "Conectar Google Calendar" → redirect a la pantalla de consentimiento
   OAuth2 de Google (`https://accounts.google.com/o/oauth2/v2/auth`) con `scope=https://www.googleapis.com/auth/calendar.events`.
2. Google redirige de vuelta con un `code` de un solo uso → el servidor lo intercambia por
   `access_token` + `refresh_token` (`POST https://oauth2.googleapis.com/token`).
3. Ambos tokens se cifran y se guardan en `sincronizacion_tokens` (§1.4), nunca se exponen al
   frontend.

**Sincronización saliente (app → Google), en cada alta/edición/cancelación de una fila en `citas`:**
- Si `citas.origen != 'google_calendar'` (para no re-enviar un evento que ya vino de Google), llamar
  `POST/PATCH/DELETE https://www.googleapis.com/calendar/v3/calendars/{calendarId}/events` con el
  `access_token` vigente (renovar con `refresh_token` si expiró) y guardar el `eventId` devuelto en
  `citas.id_evento_externo`.

**Sincronización entrante (Google → app), vía Webhook push:**
1. Al conectar, la app registra un canal de notificaciones:
   `POST https://www.googleapis.com/calendar/v3/calendars/{calendarId}/events/watch` con una URL
   pública `https://tu-dominio/calendario/webhook_google.php` como receptor. Google devuelve un
   `channel.id` y una fecha de expiración (máx. 30 días) — guardados en
   `sincronizacion_tokens.webhook_channel_id`/`webhook_channel_expira`.
2. Cada vez que algo cambia en el calendario de Google, éste hace un `POST` vacío (sólo headers) al
   endpoint registrado — la notificación NO trae el cambio en sí, es sólo un "algo cambió, ve a
   revisar". El endpoint responde `200` de inmediato y encola una sincronización asíncrona que llama
   `GET .../events?syncToken=...` (sync incremental, no una descarga completa cada vez).
3. Una tarea programada (cron, cada 25-29 días) renueva los canales próximos a expirar re-llamando a
   `.../watch`.

**Reglas anti-conflicto:**
- `citas.origen` decide quién "manda" en caso de edición simultánea — un evento que llegó por webhook
  con `origen = 'google_calendar'` no se vuelve a reenviar a Google al guardarlo localmente (evitaría
  un loop infinito app→Google→app→Google...).
- `id_evento_externo` es la clave de correlación en ambas direcciones — sin ella, no hay forma
  confiable de saber que una fila local y un evento remoto son "la misma cita".

### 3.2 Apple Calendar (Webcal — feed `.ics` dinámico, sin OAuth)

Apple Calendar (y cualquier cliente que soporte el estándar `webcal://`) no requiere OAuth para
**suscribirse en modo lectura** a un calendario — sólo una URL pública que sirva un archivo `.ics`
válido (RFC 5545) y que el cliente vuelva a consultar periódicamente (Apple re-descarga cada
15 min - 24 h, no es configurable por el servidor).

**Endpoint:** `GET /calendario/feed.ics?token={webcal_uid}` (el `token` es el `webcal_uid` de
`sincronizacion_tokens`, §1.4 — actúa como autenticación de "posesión de URL", no como sesión).

**Generación del feed** (pseudocódigo, sin dependencias externas — un archivo `.ics` es texto plano
con un formato estricto de líneas `CLAVE:valor`):

```
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//tu-proyecto//Calendario//ES
CALSCALE:GREGORIAN
METHOD:PUBLISH
X-WR-CALNAME:Agenda de {nombre del especialista}
REFRESH-INTERVAL;VALUE=DURATION:PT15M

BEGIN:VEVENT
UID:{id_cita}@tu-dominio
DTSTAMP:{timestamp UTC actual, formato YYYYMMDDTHHMMSSZ}
DTSTART:{fecha_cita + hora_inicio, formato YYYYMMDDTHHMMSSZ}
DTEND:{fecha_cita + hora_fin, formato YYYYMMDDTHHMMSSZ}
SUMMARY:{servicio} — {nombre del cliente}
STATUS:{CONFIRMED si estatus_cita=confirmada, TENTATIVE si reservada, CANCELLED si cancelada}
END:VEVENT

[... un VEVENT por cada cita futura del especialista ...]

END:VCALENDAR
```

**Reglas de formato no negociables (RFC 5545):**
- Fin de línea `\r\n` (CRLF), no sólo `\n` — clientes estrictos (incluido Apple) rechazan o corrompen
  el parseo si falta el `\r`.
- Líneas de más de 75 octetos deben "plegarse" (continuar en la siguiente línea con un espacio inicial)
  — aplica sobre todo a `SUMMARY`/`DESCRIPTION` largos.
- Caracteres especiales (`,`, `;`, `\`) dentro de un valor deben escaparse con `\` — un `SUMMARY` con
  una coma sin escapar rompe el parseo en algunos clientes.
- `Content-Type: text/calendar; charset=utf-8` en la respuesta HTTP, nunca `text/html` ni `text/plain`.

**Por qué no requiere OAuth:** es un modelo de sólo-lectura por diseño — el usuario de Apple Calendar
nunca escribe de vuelta al sistema, sólo consulta. Si en el futuro se necesita que Apple Calendar
también pueda crear/editar citas que se reflejen en la app, eso requeriría CalDAV (protocolo distinto,
con autenticación propia) — fuera del alcance de un feed webcal de sólo lectura.

---

## 4. Protocolo de Implementación Paso a Paso (para duplicar este módulo en un proyecto nuevo)

1. **Confirmar entidades base existentes.** El proyecto destino debe tener ya una tabla de "quien
   agenda" (clientes) y "quien atiende" (especialistas) con llave primaria entera. Si no existen,
   crearlas primero — este módulo no las reemplaza.
2. **Aplicar el esquema SQL de la Sección 1**, ajustando los nombres de las FK
   (`id_cliente`/`id_especialista`) a los nombres reales de las tablas base del proyecto destino.
3. **Sembrar `disponibilidad`** con los horarios operativos reales del negocio (§1.2) — este paso
   define completamente qué días/horas aparecen en la matriz, sin tocar código después.
4. **Asignar colores** a los especialistas existentes en `especialistas_colores` (§1.3) — por rotación
   de una paleta fija, no manualmente uno por uno.
5. **Construir el query de la matriz semanal:** dado un `lunes_de_la_semana` (fecha), generar las
   columnas de día iterando `disponibilidad` agrupada por `dia_semana` (nunca hardcodear "Lunes a
   Sábado" en el código — debe derivarse de qué días tienen fila en `disponibilidad`), y las filas de
   hora iterando el rango `MIN(hora_apertura)` a `MAX(hora_cierre)` de toda la semana en pasos de 1h.
6. **Construir el endpoint de alta de cita** con verificación de cupo atómica: la validación
   "¿hay cupo en esta franja?" y el `INSERT` deben ocurrir dentro de la misma transacción (o con un
   `SELECT ... FOR UPDATE`) para evitar una condición de carrera si dos usuarios agendan la misma
   franja al mismo tiempo.
7. **Construir el endpoint de mover cita (drag-and-drop):** recibe `id_cita` + nueva
   fecha/hora/especialista, revalida el cupo de la franja DESTINO (nunca confiar en la validación que
   ya hizo el JS del navegador), y sólo entonces actualiza la fila.
8. **(Opcional) Conectar Google Calendar** siguiendo el flujo OAuth de §3.1 — requiere crear un
   proyecto en Google Cloud Console, habilitar la API de Calendar, y configurar credenciales OAuth2
   con el dominio real de producción en los orígenes autorizados.
9. **(Opcional) Publicar el feed webcal** de §3.2 — no requiere credenciales externas, funciona en
   cuanto el endpoint `.ics` esté desplegado y sea alcanzable públicamente por HTTPS.
10. **Probar el protocolo de aceptación** antes de dar por cerrado el módulo:
    - Crear una cita en una franja vacía → aparece en la matriz con el color del especialista.
    - Llenar una franja hasta el cupo máximo → la franja se pinta en rojo y deja de aceptar nuevas
      citas (verificar también server-side, no sólo que el botón se deshabilite en el navegador).
    - Arrastrar una cita a otra franja con cupo disponible → se mueve y persiste tras recargar.
    - Arrastrar una cita a una franja llena → la operación se rechaza y la cita vuelve a su posición
      original.
    - Verificar que el domingo nunca aparece en la matriz, en ninguna semana.
    - Si se conectó Google Calendar: crear una cita en la app → aparece en Google Calendar en menos de
      un minuto; crear un evento en Google Calendar → aparece en la app tras el siguiente webhook.
    - Si se publicó el feed webcal: suscribir la URL en Apple Calendar → las citas aparecen (permitir
      hasta 15-24h de retraso, es el comportamiento normal de Apple, no un bug).
