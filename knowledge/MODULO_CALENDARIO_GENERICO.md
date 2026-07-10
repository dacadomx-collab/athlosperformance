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

**Por qué `cupo_maximo_franja` vive en la fila y no sólo en una constante de código:** permite que el
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
    webcal_uid VARCHAR(64) NULL COMMENT 'Sólo Apple/webcal — token opaco impredecible en la URL pública del feed .ics (ver §5.2)',
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

### 1.5 Tabla: `bloqueos_disponibilidad` (opcional — vacaciones, festivos, mantenimiento)

Separada de `disponibilidad` (que define el horario RECURRENTE semanal) porque un bloqueo es una
excepción puntual — un festivo, una vacación de un especialista, una sala en mantenimiento. Mezclar
ambos conceptos en una sola tabla obligaría a "borrar y recrear" filas recurrentes cada vez que hay
una excepción de un solo día, en vez de simplemente agregar una fila de bloqueo que se consulta junto
con la disponibilidad al calcular slots (ver §3, Motor de Disponibilidad).

```sql
CREATE TABLE IF NOT EXISTS bloqueos_disponibilidad (
    id_bloqueo INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_especialista INT UNSIGNED NULL COMMENT 'NULL = bloqueo general (festivo, cierre total)',
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    motivo VARCHAR(255) NULL COMMENT 'Ej. "Vacaciones", "Día festivo", "Mantenimiento de sala"',
    creado_por INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_bloqueo),
    KEY idx_bloqueos_especialista_fecha (id_especialista, fecha_inicio, fecha_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 1.6 Tabla: `eventos_calendario` (bitácora de eventos de dominio — ver §2)

Registro append-only (nunca se actualiza ni se borra una fila) de cada evento de dominio disparado
por el módulo. No es opcional si se implementa la arquitectura orientada a eventos de §2 — es lo que
hace posible auditar "qué pasó y en qué orden" ante una disputa o un bug de sincronización.

```sql
CREATE TABLE IF NOT EXISTS eventos_calendario (
    id_evento BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tipo_evento VARCHAR(50) NOT NULL COMMENT 'Ej. AppointmentCreated, AppointmentMoved, ConflictDetected — ver catálogo en §2.1',
    id_cita INT UNSIGNED NULL,
    payload JSON NOT NULL COMMENT 'Estado relevante en el momento del evento — nunca se muta después de escrito',
    origen VARCHAR(50) NOT NULL COMMENT 'Qué disparó el evento: actor humano, proveedor externo, motor interno',
    procesado TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Si ya fue consumido por sus suscriptores (sync, notificaciones)',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_evento),
    KEY idx_eventos_cita (id_cita),
    KEY idx_eventos_tipo_procesado (tipo_evento, procesado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 2. Arquitectura de Eventos de Dominio (Event-Driven)

En vez de tratar cada alta/edición como una simple actualización de fila, cada acción relevante
**emite un evento** que otros componentes consumen de forma desacoplada. Esto separa "qué pasó" de
"quién reacciona a ello" — agregar un nuevo consumidor (por ejemplo, notificaciones por WhatsApp)
nunca requiere tocar el código que crea la cita, sólo suscribirse al evento ya existente.

### 2.1 Catálogo de eventos

| Evento | Se dispara cuando... | Consumidores típicos |
| :--- | :--- | :--- |
| `AppointmentCreated` | Se confirma una nueva reserva | Motor de disponibilidad (recalcular capacidad), sincronización externa, notificaciones |
| `AppointmentMoved` | Cambia fecha/hora/recurso de una cita existente | Mismos que arriba + detección de conflicto |
| `AppointmentCancelled` | Se cancela una cita | Liberar cupo, notificar lista de espera, sincronización externa |
| `AppointmentCompleted` | Se marca como atendida | Motor de facturación/consumo de sesiones (si aplica), reportes |
| `NoShowRecorded` | El cliente no se presentó | Reportes, políticas de penalización (si aplica) |
| `CapacityUpdated` | Cambia la ocupación de una franja | Recalcular semáforo visual, disparar alerta si se llena |
| `ConflictDetected` | Dos fuentes modificaron la misma cita en la ventana de sincronización | Cola de revisión humana — nunca se autoresuelve (ver §5.3) |
| `SyncRequested` / `SyncCompleted` | Se necesita/se logró propagar un cambio a un proveedor externo | Reintentos, alertas de sincronización fallida |
| `NotificationQueued` | Se necesita avisar al cliente/especialista de algo | Motor de notificaciones (WhatsApp/email/SMS) |

### 2.2 Cadena de un evento típico

Crear una cita dispara una cadena, no una sola escritura:

```
AppointmentCreated
    → CapacityUpdated (recalcula ocupación de la franja)
    → SyncRequested (Google)
    → SyncRequested (Apple — regenera el feed, no hay push real)
    → NotificationQueued (confirmación al cliente)
```

Mover una cita agrega un paso de verificación antes de propagar:

```
AppointmentMoved
    → CapacityUpdated (franja origen Y franja destino)
    → ConflictDetection (¿alguien más tocó esta cita desde la última versión conocida?)
    → [si no hay conflicto] SyncRequested × proveedores
    → NotificationQueued
```

**Regla de auditoría no negociable:** cada evento se persiste en `eventos_calendario` (§1.6) ANTES de
notificar a los consumidores, con su `payload` completo — nunca se sobreescribe una fila de evento ya
escrita. Si algo sale mal a mitad de la cadena, el registro de eventos permite reconstruir exactamente
qué pasó y reintentar sólo el paso que falló, en vez de repetir toda la operación desde cero.

---

## 3. Motor de Disponibilidad

**Regla central: nunca calcular disponibilidad consultando directamente las citas ya existentes en
tiempo real desde la vista.** Ese cálculo debe vivir en un componente dedicado (el "motor") que toma
varias fuentes como entrada y produce una lista de slots como salida — así, agregar una nueva regla
(por ejemplo, "bloquear 15 minutos después de cada cita para traslado") es un cambio en un solo lugar,
no una búsqueda de todos los puntos del código que hoy calculan disponibilidad a mano.

### 3.1 Entradas del algoritmo

1. **Horario laboral recurrente** — tabla `disponibilidad` (§1.2).
2. **Bloqueos puntuales** — tabla `bloqueos_disponibilidad` (§1.5): vacaciones, festivos, mantenimiento.
3. **Capacidad máxima por franja** — `citas.cupo_maximo_franja` (§1.1).
4. **Ocupación actual** — conteo de `citas` con estatus activo en esa franja.
5. **Duración del servicio** — algunos servicios ocupan más de una franja de 1h; el algoritmo debe
   verificar que TODAS las franjas que abarca el servicio tengan cupo, no sólo la primera.
6. **Buffers** (opcional) — tiempo mínimo obligatorio entre el fin de una cita y el inicio de la
   siguiente para el mismo recurso (limpieza, traslado, preparación).
7. **Ventana de cancelación** — una cita cancelada fuera de la ventana mínima de anticipación no
   libera el cupo automáticamente (queda como "penalizada", decisión de negocio configurable).

### 3.2 Algoritmo (pseudocódigo)

```
función calcularSlotsDisponibles(fecha, recurso):
    horarioBase = consultar disponibilidad para el día de la semana de `fecha`
    si no hay horarioBase: devolver [] (día cerrado)

    bloqueos = consultar bloqueos_disponibilidad que se solapen con `fecha` y `recurso`
    slotsBase = generar franjas de 1h entre horarioBase.apertura y horarioBase.cierre

    para cada slot en slotsBase:
        si slot se solapa con algún bloqueo: marcar slot como NO_DISPONIBLE, continuar

        ocupacion = contar citas activas en (fecha, slot, recurso)
        capacidad = cupo_maximo_franja vigente para ese slot

        slot.disponibles = capacidad - ocupacion
        slot.semaforo = semaforoDesdeOcupacion(ocupacion, capacidad)

    devolver slotsBase
```

### 3.3 Ejemplo de salida

```
Lunes 06:00 → capacidad 4, ocupados 3, disponibles 1 → 🟡
Lunes 07:00 → capacidad 4, ocupados 4, disponibles 0 → 🔴 (no acepta nuevas reservas)
Lunes 08:00 → capacidad 4, ocupados 0, disponibles 4 → 🟢
```

---

## 4. Arquitectura de Vistas

### 4.1 Desktop — matriz 80% + sidebars 20%

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

### 4.2 Sidebar izquierdo — clientes del mes

Lista de clientes con actividad en el mes visible, cada uno con:
- Nombre.
- Barra de progreso: `sesiones_consumidas / sesiones_totales` de su membresía/paquete activo.
- Alerta visual (ámbar) cuando `sesiones_restantes <= 2` — mismo umbral que un motor de alertas de
  renovación si el proyecto ya tiene uno; si no, es un cálculo directo `totales - restantes`.

### 4.3 Sidebar derecho — especialistas activos

Lista de especialistas activos, cada uno con su swatch de color (`especialistas_colores`, §1.3) y un
checkbox de "mostrar/ocultar en la matriz" (filtro client-side, no recarga el servidor) — permite al
usuario aislar visualmente la agenda de un solo especialista sin perder el resto de la semana cargada.

### 4.4 Móvil — pantalla completa táctil

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

## 5. Lógica de Sincronización Externa

### 5.1 Google Calendar (OAuth2 + Webhooks push)

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
   pública `https://tu-dominio/calendario/webhook-google` como receptor. Google devuelve un
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

### 5.2 Apple Calendar (Webcal — feed `.ics` dinámico, sin OAuth)

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

## 6. Resolución de Conflictos

Un conflicto ocurre cuando dos fuentes distintas modifican la misma cita antes de que la primera
modificación termine de propagarse a todas las demás — típicamente, alguien mueve una cita desde la
matriz interna al mismo tiempo que el titular la mueve desde su calendario externo.

### 6.1 Por qué NO usar "el último que escribe gana"

"Last write wins" (sobrescribir siempre con el cambio más reciente) es la solución más simple de
implementar y la más peligrosa de operar: pierde silenciosamente la primera modificación sin que nadie
se entere, y el "más reciente" depende de relojes de sistemas distintos (servidor propio vs. servidor
del proveedor externo) que rara vez están perfectamente sincronizados — un evento que en realidad
ocurrió primero puede parecer "más reciente" por un simple desfase de reloj.

### 6.2 Modelo de prioridades

En vez de "el más reciente gana", cada fuente de cambio tiene una prioridad fija y explícita, de mayor
a menor autoridad sobre el dato:

1. **Sistema administrativo** (cambios hechos por un rol de administración/dirección)
2. **Recepción / operador** (cambios hechos por quien gestiona la agenda día a día)
3. **Especialista** (cambios hechos por quien atiende la cita)
4. **Proveedor de calendario externo con escritura** (ej. Google Calendar, si se implementó sync
   bidireccional)
5. **Proveedor de calendario externo de sólo lectura** (ej. un feed webcal — por definición nunca
   origina un conflicto, porque nunca escribe de vuelta)

Un cambio de menor prioridad NUNCA sobrescribe automáticamente uno de mayor prioridad que haya
ocurrido en la misma ventana de sincronización — se marca como conflicto pendiente (evento
`ConflictDetected`, §2.1) en vez de aplicarse.

### 6.3 Detección

Cada cita mantiene un número de versión (o un timestamp de última modificación) que se compara contra
la versión conocida por quien intenta modificarla:

```
al recibir una notificación de cambio externo para la cita X:
    versión_conocida = versión que teníamos la última vez que sincronizamos X
    versión_actual_local = versión real de X en este momento

    si versión_actual_local == versión_conocida:
        no hubo cambio local desde la última sync → aplicar el cambio externo sin fricción
    si no:
        alguien más modificó X localmente mientras tanto → emitir ConflictDetected,
        NO aplicar el cambio automáticamente
```

### 6.4 Resolución — siempre con intervención humana, nunca automática

Ante un `ConflictDetected`, el sistema nunca decide por sí mismo cuál versión es la correcta — encola
el conflicto para revisión y presenta ambas versiones lado a lado con una decisión explícita:

- **Mantener la versión del sistema interno** (descarta el cambio externo).
- **Mantener la versión del proveedor externo** (sobrescribe el registro interno).
- **Fusionar manualmente** (ej. la hora la puso bien el sistema interno, pero la nota la agregó el
  proveedor externo — combinar ambos campos a mano).

Esta pantalla de resolución es, en la práctica, la funcionalidad que más confianza genera en el
módulo: un operador que ve claramente qué pasó y decide, en vez de descubrir semanas después que una
cita real "desapareció" por una sobrescritura silenciosa.

---

## 7. Protocolo de Implementación Paso a Paso (para duplicar este módulo en un proyecto nuevo)

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
8. **(Opcional) Conectar Google Calendar** siguiendo el flujo OAuth de §5.1 — requiere crear un
   proyecto en Google Cloud Console, habilitar la API de Calendar, y configurar credenciales OAuth2
   con el dominio real de producción en los orígenes autorizados.
9. **(Opcional) Publicar el feed webcal** de §5.2 — no requiere credenciales externas, funciona en
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

---

## 8. Motor de Disponibilidad Pública y Flujo de Pre-Aprobación

Extiende el Motor de Disponibilidad (§3) con una superficie **sin autenticación** para que un
prospecto o cliente externo pueda ver horarios libres y pedir una cita, sin que esa petición reserve
nada por sí sola. La regla central de todo este flujo: **una solicitud pública nunca es una reserva
confirmada** — es una fila con un estatus intermedio que un humano del equipo debe aprobar o rechazar.

### 8.1 Estatus intermedio: `pendiente_aprobacion`

Se agrega un valor más al enum de `citas.estatus_cita` (§1.1): `pendiente_aprobacion`. Una fila en
este estatus:

- **No cuenta contra el cupo máximo de la franja.** El cálculo de ocupación (§3.1, punto 4) sólo
  considera estatus "activos" (`reservada`/`confirmada`); esto permite que varias solicitudes públicas
  compitan por la misma franja limitada sin bloquearse mutuamente mientras esperan revisión.
- **Se revalida el cupo en el momento de la aprobación, no en el de la solicitud.** Si dos prospectos
  piden la última franja libre y el equipo aprueba la primera solicitud, al intentar aprobar la
  segunda el sistema debe recontar la ocupación real en ese instante y rechazar la aprobación con un
  mensaje claro ("la franja ya se llenó mientras tanto") en vez de sobrevender el cupo silenciosamente.
- **Rechazar una solicitud pendiente nunca aplica la ventana mínima de cancelación (§10.2)** — esa
  regla protege al operador de cancelaciones tardías de citas YA confirmadas; una solicitud que nunca
  llegó a confirmarse no la necesita.

### 8.2 Datos de contacto sin cuenta de usuario

La vista pública no exige que el solicitante tenga una cuenta — la fila de la cita guarda directamente
los datos de contacto mínimos (`nombre_solicitante`, `telefono_solicitante`, `correo_solicitante`) en
vez de forzar un alta de cliente antes de poder pedir una cita. Si la solicitud se aprueba y el
prospecto no tenía ficha de cliente todavía, el alta de cliente ocurre en ese momento (aprobación),
no antes — evita ensuciar la base de clientes con fichas de gente que nunca llegó a confirmar nada.

### 8.3 Qué expone la vista pública (y qué NO)

| Expone | No expone |
| :--- | :--- |
| Días/franjas con cupo disponible (agregado, sin desglosar por especialista) | Nombres de clientes/citas ya agendadas en franjas ocupadas |
| Lista de servicios activos, para que el solicitante elija cuál pide | Notas internas, historial clínico/de consumo, datos de facturación |
| Lista de especialistas activos, como preferencia (no garantía) | Disponibilidad/agenda personal de un especialista fuera del contexto de esta reserva |
| Franjas bloqueadas de forma agregada (simplemente no aparecen como opción) | El motivo detallado de un bloqueo específico de un especialista (sólo bloqueos generales ocultan la franja para todos) |

**Regla de diseño:** la vista pública consulta el mismo motor de disponibilidad (§3) que la matriz
interna — nunca una copia paralela de la lógica de horarios/cupo/bloqueos. Si el motor central cambia
(ej. se agrega una regla de buffer entre citas), la vista pública la hereda automáticamente sin
duplicar código.

### 8.4 Flujo end-to-end

```
Prospecto abre el enlace público
    → ve únicamente franjas con cupo > 0, futuras, dentro de horario operativo, sin bloqueo activo
    → elige franja + servicio + especialista de preferencia
    → llena nombre + al menos un dato de contacto (teléfono o correo)
    → envía el formulario
        → INSERT en `citas` con estatus_cita = 'pendiente_aprobacion' (NO se valida cupo estricto aquí
          más que "no esté ya lleno de citas activas" — la validación fuerte ocurre en la aprobación)
    → el equipo ve la solicitud en su panel administrativo (badge de conteo + lista con acción)
    → equipo hace clic en "Aceptar":
        → revalida cupo real en ese instante
        → si hay cupo: estatus_cita = 'confirmada' (o el estatus activo equivalente del proyecto)
        → si ya no hay cupo: rechaza la aprobación con mensaje explícito, la fila queda pendiente para
          que el operador reagende manualmente al prospecto en otro horario
    → equipo hace clic en "Rechazar":
        → estatus_cita = 'cancelada' (o equivalente), sin aplicar ventana de cancelación
```

### 8.5 Enlace compartible con copiado de un clic

El panel administrativo interno expone un botón "Compartir disponibilidad pública" que copia al
portapapeles la URL de la vista pública (sin parámetros sensibles, sin token — es una URL genérica de
solo-consulta). Implementación sugerida sin dependencias externas: la Clipboard API del navegador
(`navigator.clipboard.writeText`), con una confirmación visual breve (toast) al completarse — nunca un
`alert()` bloqueante.

---

## 9. Matriz de Configuración Dinámica (Horarios, Aforo y Ausencias de Recurso)

Todo lo que en una primera versión del módulo suele vivir hardcodeado en código (qué días se trabaja,
qué horario tiene cada día, cuántos cupos hay por franja) se convierte en **configuración editable en
caliente por un rol administrativo**, sin despliegue de código ni reinicio del servicio. Este documento
ya definía `disponibilidad` (§1.2) como la fuente de horarios — esta sección formaliza el patrón
completo de panel de administración sobre esa y otras tablas de configuración.

### 9.1 Principio de diseño: "configuración con reserva determinística, nunca error fatal"

Cada pieza de configuración dinámica se lee así:

```
función obtenerConfiguracion(clave):
    intentar leer el valor desde la tabla de configuración
    si la tabla no existe todavía (proyecto sin migrar) o no hay fila para esa clave:
        devolver un valor por defecto hardcodeado, equivalente al comportamiento pre-configuración
    si la lectura tiene éxito:
        devolver el valor real de la base de datos
```

Esto significa que **el módulo nunca se rompe** por una migración de configuración pendiente de
aplicar — simplemente opera con los valores por defecto hasta que alguien active el panel. Es el mismo
patrón que ya usa este documento para colores de especialista (§1.3) y horario recurrente (§1.2),
extendido aquí a aforo variable y bloqueos de recurso.

### 9.2 Días y horarios de trabajo configurables

Editable directamente sobre la tabla `disponibilidad` (§1.2) desde una pantalla de administración:
por cada día de la semana, un interruptor activo/inactivo y dos campos de hora (apertura/cierre). Un
día "apagado" simplemente no tiene fila activa — la matriz visual (§4.1) y el motor de disponibilidad
(§3) ya derivan automáticamente qué días mostrar a partir de esta tabla, así que apagar un día no
requiere ningún cambio adicional en ningún otro componente.

### 9.3 Aforo máximo variable

`citas.cupo_maximo_franja` (§1.1) tiene un valor por defecto, pero el panel de configuración expone un
campo numérico editable (ej. rango razonable 1-50) que se guarda en una tabla de configuración general
de clave/valor. Al cambiar este valor:

- Las citas **ya agendadas conservan el cupo que tenían al momento de crearse** (queda grabado en su
  propia fila) — cambiar el aforo global no reescribe retroactivamente citas pasadas.
- Las franjas **futuras aún no llenas** recalculan su semáforo (§3.3) inmediatamente contra el nuevo
  valor, sin esperar a que alguien recargue una caché — el motor de disponibilidad siempre lee el valor
  vigente en el momento de la consulta.

### 9.4 Ausencias/bloqueos de recurso

Reutiliza `bloqueos_disponibilidad` (§1.5) como la tabla única tanto para "un especialista específico
no disponible" (`id_especialista` con valor) como para "cierre general del negocio" (`id_especialista`
NULL — festivo, mantenimiento). El panel de administración ofrece un formulario simple: especialista
(opcional — vacío significa bloqueo general), fecha/hora de inicio, fecha/hora de fin, motivo libre.

**Regla de cruce importante para la vista agregada (matriz que no distingue por especialista):** al
calcular si una franja debe mostrarse como bloqueada en una vista que agrega TODOS los especialistas
(en vez de mostrar la agenda de uno solo), sólo los bloqueos **generales** (`id_especialista IS NULL`)
deben ocultar la franja completa — un bloqueo del especialista A no debe ocultar la franja para el
especialista B, que sigue disponible. La función de verificación de bloqueo debe aceptar un parámetro
explícito de "¿para qué especialista estoy preguntando, o para ninguno en particular (vista agregada)?"
en vez de intentar inferirlo implícitamente.

### 9.5 Credenciales de sincronización externa, editables desde el mismo panel

El mismo panel de configuración es el lugar natural para capturar las credenciales de §5: campos para
`Client ID`/`Client Secret` de OAuth2 (Google Calendar) y la visualización de la URL del feed webcal
(Apple Calendar, §5.2, generada automáticamente — nunca editable a mano, para no romper el formato).

**Regla de seguridad no negociable:** el `Client Secret` (y cualquier credencial simétrica equivalente)
se cifra antes de guardarse en base de datos con una llave derivada de una variable de entorno del
servidor (nunca hardcodeada en el repositorio) — nunca se persiste en texto plano. Al mostrar el
formulario de nuevo, el campo del secreto se presenta siempre enmascarado (nunca se descifra hacia el
HTML); dejar el campo vacío al guardar significa "conservar el valor actual sin cambiarlo", no "borrar
la credencial" — evita que un simple guardado accidental del formulario invalide la sincronización ya
funcionando.

---

## 10. Protocolo de Cancelación Autónoma del Cliente y Reapertura Automática de Slot

Habilita que la propia persona que reservó una cita pueda cancelarla desde su vista privada, sin
depender de que el equipo administrativo lo haga por ella — con una ventana mínima de anticipación
para proteger al recurso de cancelaciones de último minuto.

### 10.1 Vista privada del cliente: alcance estrictamente propio

El rol "cliente" (a diferencia de los roles de staff/administración) sólo puede ver **sus propias**
citas — el filtro por identidad del cliente en sesión debe aplicarse siempre server-side en la consulta
misma (`WHERE id_cliente = :id_cliente_de_la_sesion`), nunca confiando en un parámetro de la URL ni en
un filtro que ocurra sólo en el frontend. Ningún endpoint de este portal debe aceptar un
`id_cliente`/`id_cita` arbitrario sin verificar antes que la cita pertenece efectivamente al cliente en
sesión — de lo contrario, un cliente autenticado podría cancelar la cita de otro cambiando un número en
la petición.

### 10.2 Estatus dedicado: `cancelada_por_cliente`

Se agrega un valor más al enum de `citas.estatus_cita`: `cancelada_por_cliente`, distinto de una
cancelación hecha por el equipo (`cancelada` genérico). Mantener ambos estatus separados permite que
los reportes y las alertas distingan "el negocio canceló esta cita" de "el cliente decidió no venir" —
son dos señales de negocio muy distintas (la segunda puede alimentar, por ejemplo, una métrica de tasa
de cancelación de clientes).

### 10.3 Regla de ventana mínima de anticipación

```
al recibir una solicitud de cancelación del propio cliente:
    verificar que la cita pertenece al cliente en sesión (§10.1) — si no, rechazar sin más detalle
    verificar que la cita está en un estatus cancelable (activo: reservada/confirmada)
    horas_restantes = (fecha_hora_de_la_cita - ahora) en horas
    si horas_restantes < ventana_minima_configurada (ej. 3 horas):
        rechazar con mensaje explícito, invitando a contactar directamente al negocio
    si no:
        estatus_cita = 'cancelada_por_cliente'
        continuar con §10.4
```

La ventana mínima (ej. 3 horas) es un valor configurable de negocio, no una constante rígida — mismo
principio de "configuración con reserva determinística" de §9.1.

### 10.4 Efectos automáticos de la cancelación (sin intervención manual)

1. **Liberación de cupo:** al cambiar el estatus fuera de los estatus "activos" que cuentan para el
   cálculo de ocupación (§3.1, punto 4), la siguiente consulta de disponibilidad —tanto en la matriz
   interna como en la vista pública (§8)— refleja automáticamente el cupo liberado. No existe un paso
   manual de "recalcular disponibilidad"; es una consecuencia directa de que ambas vistas siempre
   consultan el estado actual de las citas en vivo, nunca una caché desincronizable.
2. **Alerta visual al equipo:** se emite una notificación visible (banner o widget, no sólo un registro
   silencioso en base de datos) hacia los roles responsables de esa franja/recurso — típicamente
   visible durante una ventana razonable después del evento (ej. últimos 7 días), para que el equipo
   note incluso cancelaciones ocurridas mientras nadie miraba la pantalla activamente.
3. **Reapertura en la Disponibilidad Pública (§8):** dado que la vista pública consulta el mismo motor
   de disponibilidad en vivo, la franja recién liberada por el cliente vuelve a aparecer como
   disponible para nuevas solicitudes públicas sin ningún paso adicional — es el mismo efecto que
   liberar cupo internamente, simplemente visible también desde la superficie pública.

### 10.5 Qué NO hace este protocolo (alcance deliberadamente limitado)

- No reprograma automáticamente al cliente en otro horario — sólo libera el slot; reagendar es una
  acción nueva y explícita del cliente o del equipo.
- No aplica penalizaciones ni políticas de cobro por cancelación — si el negocio destino necesita eso,
  es una capa adicional sobre este protocolo, no parte de él.
- No notifica automáticamente a otros clientes en lista de espera para ese slot — eso pertenece al
  catálogo de eventos de dominio de §2 (`AppointmentCancelled` → consumidor de lista de espera, si el
  proyecto destino implementa una), no a este protocolo específico de cancelación de cliente.
