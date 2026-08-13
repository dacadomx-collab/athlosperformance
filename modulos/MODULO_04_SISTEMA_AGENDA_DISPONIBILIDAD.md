# 📄 MODULO_04 — SISTEMA DE AGENDA Y DISPONIBILIDAD

**Clasificación:** Módulo Genérico de Arquitectura y Diseño Técnico | **Versión:** 1.0
**Alcance:** Documento agnóstico, reutilizable por cualquier proyecto — ningún nombre de cliente,
dominio o credencial real debe aparecer aquí. Ningún nombre de columna se traduce libremente: el
Codex del proyecto consumidor es la única fuente de verdad para el mapeo final `{{PLACEHOLDER}}` →
nombre real.
**Compatibilidad:** Cualquier lenguaje/stack con transacciones ACID (PHP, Go, Node, Python, C#,
etc.) y cualquier motor relacional con locking a nivel de fila (MySQL/MariaDB InnoDB, PostgreSQL,
SQL Server). No cubre motores sin transacciones reales (ej. MySQL/MyISAM) — la Sección 2.2 depende
de ellas de forma no negociable.

> 🛑 **Mandamiento de Mantenimiento Continuo:** este documento es un **ente vivo**. Cada vez que un
> proyecto consumidor cierre un hito de agenda, descubra un edge-case nuevo (una zona horaria que
> rompió un cálculo, una condición de carrera no cubierta aquí, un patrón de UI que resultó mejor
> en producción), es obligación autónoma de la IA Ejecutora de ese proyecto actualizar esta
> Sección correspondiente — con fecha y motivo — antes de cerrar el hito. Un módulo de agenda
> nuevo que descubre una lección no documentada aquí y no la sube a este archivo es una lección
> perdida para el siguiente proyecto.

> 📎 **Módulos relacionados:** [`MODULO_01_LOGIN_Y_ACCESO.md`](MODULO_01_LOGIN_Y_ACCESO.md) resuelve
> quién es el actor que reserva (`{{TABLE_PREFIX}}usuarios`, roles, sesión) — este módulo asume que
> ese problema ya está resuelto y solo declara las FKs necesarias. `{{TABLE_PREFIX}}usuarios` es un
> dato **de entrada** para el motor de agenda, no algo que este módulo redefina.
> [`MODULO_02_REPORTES_Y_AUDITORIAS.md`](MODULO_02_REPORTES_Y_AUDITORIAS.md) aplica sin cambios a
> cualquier reporte de ocupación/no-shows/ingresos derivado de las tablas de este módulo — misma
> regla de oro: ninguna cifra de ocupación se publica sin una consulta real detrás.

### 🔤 Glosario de Placeholders

| Placeholder | Significado | Ejemplo de valor real |
| :--- | :--- | :--- |
| `{{PROJECT_NAME}}` | Nombre del proyecto consumidor | `MiProyecto` |
| `{{TABLE_PREFIX}}` | Prefijo de tablas del proyecto consumidor | `mp_` o `` (vacío) |
| `{{ZONA_HORARIA_NEGOCIO}}` | Zona horaria IANA del negocio (nunca un offset fijo tipo `UTC-7`, ver Sección 2.3) | `America/Mexico_City` |
| `{{MAX_CUPO_HORA}}` | Cupo máximo concurrente por defecto de un bloque | `4` |
| `{{GRANULARIDAD_SLOT_MINUTOS}}` | Tamaño del bloque atómico que genera el motor de disponibilidad | `30` o `60` |
| `{{VENTANA_RESERVA_MINIMA_MINUTOS}}` | Con cuánta anticipación mínima se puede reservar un slot | `120` (2 horas) |
| `{{VENTANA_RESERVA_MAXIMA_DIAS}}` | Hasta cuántos días a futuro se puede reservar | `30` |
| `{{VENTANA_CANCELACION_HORAS}}` | Horas mínimas de anticipación para cancelar sin penalización | `3` |

---

## 1. 🎯 OBJETIVO DEL MÓDULO

Definir un motor de agenda/disponibilidad de nivel Enterprise — genérico para cualquier dominio
que reserve un **recurso** (una persona, una sala, un equipo, un vehículo, una cancha) contra
**bloques de tiempo con cupo finito** — que resuelva de forma determinística y verificable:

1. Qué horarios existen y quién los define (recurrente + excepciones/bloqueos).
2. Cómo se calculan los "huecos libres" cruzando horario, bloqueos, cupo y reservas existentes.
3. Cómo se garantiza que **dos usuarios nunca ganan el mismo último lugar** bajo alta concurrencia.
4. Cómo se evita que la ambigüedad de zonas horarias produzca una reserva en la hora equivocada.
5. Cómo se presenta todo esto en una UI móvil que no se rompe con un calendario tipo `<table>`.

Este módulo **no** resuelve pagos, notificaciones (WhatsApp/email/SMS) ni videollamadas — esos son
módulos propios que se enganchan a los estados definidos aquí (Sección 2.5), no se mezclan en este
documento.

---

## 2. 🏗️ ARQUITECTURA / ESPECIFICACIÓN TÉCNICA

### 2.1 Schema Maestro (SQL primero)

**Decisión de diseño central:** se separan explícitamente **"el hueco con cupo" (`bloques_disponibilidad`)** de **"la reserva de una persona contra ese hueco" (`citas`)**. Esta separación es lo que permite resolver la condición de carrera de la Sección 2.2 con una sola sentencia `UPDATE` atómica, sin `SELECT ... FOR UPDATE` manual ni bloqueos explícitos — el motor de base de datos hace el trabajo pesado.

```sql
-- Recurso reservable: una persona (staff), una sala, un equipo, un vehículo, una cancha.
-- El proyecto consumidor define su propio vocabulario en la columna `tipo`.
CREATE TABLE `{{TABLE_PREFIX}}recursos` (
    `id`             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nombre`         VARCHAR(150)  NOT NULL,
    `tipo`           VARCHAR(60)   NOT NULL DEFAULT 'staff' COMMENT 'staff | sala | equipo | vehiculo — vocabulario libre del proyecto consumidor',
    `zona_horaria`   VARCHAR(60)   NOT NULL DEFAULT '{{ZONA_HORARIA_NEGOCIO}}' COMMENT 'IANA tz (ej. America/Mexico_City) — permite recursos en sucursales/zonas distintas bajo el mismo sistema',
    `activo`         TINYINT(1)    NOT NULL DEFAULT 1,
    `creado_en`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```sql
-- Horario recurrente semanal. `id_recurso = NULL` es el horario GLOBAL por defecto,
-- heredado por cualquier recurso sin fila propia — evita tener que sembrar 7 filas
-- por cada recurso nuevo si todos abren igual.
CREATE TABLE `{{TABLE_PREFIX}}horarios_recurrentes` (
    `id`             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `id_recurso`     BIGINT UNSIGNED NULL COMMENT 'NULL = horario global por defecto',
    `dia_semana`     TINYINT UNSIGNED NOT NULL COMMENT '1=Lunes ... 7=Domingo (ISO-8601) — NUNCA 0=Domingo, evita el clásico bug de off-by-one entre convenciones cron/JS/ISO',
    `hora_apertura`  TIME NOT NULL,
    `hora_cierre`    TIME NOT NULL,
    `activo`         TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY `uq_horario_recurso_dia` (`id_recurso`, `dia_semana`),
    CONSTRAINT `fk_horario_recurso` FOREIGN KEY (`id_recurso`) REFERENCES `{{TABLE_PREFIX}}recursos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```sql
-- Excepciones al horario recurrente: feriados, vacaciones, mantenimiento.
-- `id_recurso = NULL` bloquea TODO el sistema (ej. feriado general).
CREATE TABLE `{{TABLE_PREFIX}}bloqueos` (
    `id`             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `id_recurso`     BIGINT UNSIGNED NULL COMMENT 'NULL = bloqueo global (feriado, cierre total)',
    `inicio_utc`     DATETIME NOT NULL COMMENT 'SIEMPRE UTC — ver Sección 2.3, regla de oro',
    `fin_utc`        DATETIME NOT NULL COMMENT 'SIEMPRE UTC',
    `motivo`         VARCHAR(255) NULL,
    `creado_por`     BIGINT UNSIGNED NULL COMMENT 'FK opcional a {{TABLE_PREFIX}}usuarios (MODULO_01)',
    `creado_en`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_bloqueo_recurso_rango` (`id_recurso`, `inicio_utc`, `fin_utc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```sql
-- Catálogo de servicios/tipos de cita. `cupo_maximo_concurrente = 1` es el caso
-- "el recurso queda 100% ocupado" (ej. corte de cabello); >1 es el caso "clase
-- grupal / sala compartida" (ej. clase de spinning con 15 lugares).
CREATE TABLE `{{TABLE_PREFIX}}servicios` (
    `id`                        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nombre`                    VARCHAR(150) NOT NULL,
    `duracion_minutos`          SMALLINT UNSIGNED NOT NULL,
    `cupo_maximo_concurrente`   SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `activo`                    TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```sql
-- EL HUECO CON CUPO. Se materializa (se inserta la fila) la primera vez que el
-- motor de disponibilidad calcula ese slot para ese recurso — nunca se
-- pre-generan meses de filas vacías por adelantado (desperdicio de espacio y
-- de escritura); tampoco se calcula "cupo ocupado" con un COUNT(*) en caliente
-- sobre `citas` en cada reserva (contención bajo carga) — `cupo_disponible` es
-- la fuente de verdad, mantenida por la operación atómica de la Sección 2.2.
CREATE TABLE `{{TABLE_PREFIX}}bloques_disponibilidad` (
    `id`                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `id_recurso`         BIGINT UNSIGNED NOT NULL,
    `id_servicio`        BIGINT UNSIGNED NOT NULL,
    `inicio_utc`         DATETIME NOT NULL COMMENT 'SIEMPRE UTC',
    `fin_utc`            DATETIME NOT NULL COMMENT 'SIEMPRE UTC',
    `cupo_maximo`        SMALLINT UNSIGNED NOT NULL,
    `cupo_disponible`    SMALLINT UNSIGNED NOT NULL COMMENT 'Decrementado/incrementado atómicamente — nunca recalculado con COUNT(*) en el camino caliente de reserva',
    `creado_en`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_bloque_recurso_servicio_inicio` (`id_recurso`, `id_servicio`, `inicio_utc`),
    CONSTRAINT `fk_bloque_recurso` FOREIGN KEY (`id_recurso`) REFERENCES `{{TABLE_PREFIX}}recursos`(`id`),
    CONSTRAINT `fk_bloque_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `{{TABLE_PREFIX}}servicios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```sql
-- LA RESERVA. Un bloque puede tener 1..N citas activas simultáneas (hasta su
-- cupo_maximo). Soporta reservante con cuenta (`id_cliente`) o invitado sin
-- cuenta (`contacto_*`) — patrón común en agenda pública self-service.
CREATE TABLE `{{TABLE_PREFIX}}citas` (
    `id`                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `id_bloque`          BIGINT UNSIGNED NOT NULL,
    `id_cliente`         BIGINT UNSIGNED NULL COMMENT 'FK a {{TABLE_PREFIX}}usuarios (MODULO_01) si el reservante tiene cuenta',
    `contacto_nombre`    VARCHAR(150) NULL COMMENT 'Obligatorio si id_cliente es NULL (reserva de invitado)',
    `contacto_telefono`  VARCHAR(20)  NULL,
    `contacto_email`     VARCHAR(150) NULL,
    `estatus`            ENUM('pendiente_aprobacion','reservada','confirmada','cancelada','completada','no_show') NOT NULL DEFAULT 'reservada',
    `notas`              TEXT NULL,
    `creado_en`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_citas_bloque` (`id_bloque`),
    KEY `idx_citas_cliente` (`id_cliente`),
    CONSTRAINT `fk_citas_bloque` FOREIGN KEY (`id_bloque`) REFERENCES `{{TABLE_PREFIX}}bloques_disponibilidad`(`id`),
    CONSTRAINT `chk_citas_contacto` CHECK (`id_cliente` IS NOT NULL OR `contacto_nombre` IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```sql
-- Configuración dinámica (fila-única por clave) — nunca hardcodear
-- {{MAX_CUPO_HORA}}, {{VENTANA_CANCELACION_HORAS}}, etc. en el código de
-- negocio. Mismo patrón que MODULO_01 §1.3 (configuracion_seguridad).
CREATE TABLE `{{TABLE_PREFIX}}configuracion_agenda` (
    `clave`          VARCHAR(100) NOT NULL PRIMARY KEY,
    `valor`          TEXT NULL,
    `actualizado_en` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `{{TABLE_PREFIX}}configuracion_agenda` (`clave`, `valor`) VALUES
    ('cupo_maximo_hora_default', '{{MAX_CUPO_HORA}}'),
    ('granularidad_slot_minutos', '{{GRANULARIDAD_SLOT_MINUTOS}}'),
    ('ventana_reserva_minima_minutos', '{{VENTANA_RESERVA_MINIMA_MINUTOS}}'),
    ('ventana_reserva_maxima_dias', '{{VENTANA_RESERVA_MAXIMA_DIAS}}'),
    ('ventana_cancelacion_horas', '{{VENTANA_CANCELACION_HORAS}}')
ON DUPLICATE KEY UPDATE `clave` = `clave`;
```

**Notas de diseño:**
- `bloques_disponibilidad.cupo_disponible` es una **columna de estado mutable**, no una vista calculada — esto es deliberado y es lo que habilita la Sección 2.2. La alternativa "sin estado" (`COUNT(*) de citas activas` en cada request) es más simple de razonar pero **no** resuelve la condición de carrera sin un `SELECT ... FOR UPDATE` explícito adicional — ver Sección 2.2, opción B, para cuándo sí conviene ese camino.
- `UNIQUE KEY uq_bloque_recurso_servicio_inicio` impide que el motor de disponibilidad materialice el mismo bloque dos veces por una doble ejecución concurrente del cálculo — un segundo intento de `INSERT` falla por duplicado y el código simplemente relee la fila existente.
- Ninguna tabla usa `DELETE` como mecanismo de cancelación — `citas.estatus` es la única fuente de verdad del ciclo de vida (Sección 2.5), preservando el historial para reportes (MODULO_02).

### 2.2 Concurrencia: la última silla no se le da a dos personas

**Anti-patrón prohibido (Time-Of-Check to Time-Of-Use / TOCTOU):**

```
1. SELECT cupo_disponible FROM bloques_disponibilidad WHERE id = X   -- lee "1"
2. (el código de aplicación decide: "hay cupo, procedo")
3. INSERT INTO citas (...)                                            -- inserta
4. UPDATE bloques_disponibilidad SET cupo_disponible = cupo_disponible - 1
```

Este patrón parece correcto en pruebas manuales (una sola petición a la vez) y **falla
silenciosamente bajo carga real**: dos requests pueden ejecutar el paso 1 al mismo tiempo, ambos
leen "1", ambos deciden "hay cupo" y ambos insertan — el bloque queda con `cupo_disponible = -1`
en la práctica (dos reservas contra un solo lugar). Ningún `try/catch` corrige esto después,
porque para cuando se detecta, ya se vendió el mismo lugar dos veces.

**Patrón correcto — `UPDATE` condicional atómico (recomendado por defecto):**

```php
// Pseudocódigo agnóstico — el patrón es portable a cualquier stack con transacciones ACID.
// {{DB}} = conexión con soporte de transacciones reales (nunca autocommit implícito sin control).
try {
    {{DB}}->beginTransaction();

    // Paso 1 — el ÚNICO paso que decide si hay cupo, y lo decide de forma
    // atómica: el WHERE se evalúa como parte de la misma operación de
    // escritura, no como un SELECT previo separado. InnoDB toma un lock de
    // fila implícito en el UPDATE; dos transacciones concurrentes sobre el
    // mismo id_bloque se serializan automáticamente en el motor — una gana,
    // la otra ve rowCount() = 0. Esto es válido incluso bajo READ COMMITTED
    // (el nivel por defecto de MySQL/InnoDB) — NO requiere SERIALIZABLE ni
    // SELECT ... FOR UPDATE adicional.
    $stmt = {{DB}}->prepare(
        "UPDATE {{TABLE_PREFIX}}bloques_disponibilidad
         SET cupo_disponible = cupo_disponible - 1
         WHERE id = :id_bloque AND cupo_disponible > 0"
    );
    $stmt->execute(['id_bloque' => $idBloque]);

    if ($stmt->rowCount() === 0) {
        {{DB}}->rollBack();
        // 409 Conflict — respuesta ESPERADA bajo alta concurrencia, no un
        // error de sistema. El cupo se agotó entre el GET de disponibilidad
        // del cliente y su POST de reserva. El frontend debe refrescar la
        // grilla de slots (Sección 4.3), nunca reintentar el mismo id_bloque
        // a ciegas.
        return respond(409, 'SLOT_NO_DISPONIBLE');
    }

    // Paso 2 — solo se llega aquí si el Paso 1 afectó exactamente 1 fila.
    $stmt = {{DB}}->prepare(
        "INSERT INTO {{TABLE_PREFIX}}citas (id_bloque, id_cliente, contacto_nombre, contacto_telefono, contacto_email, estatus)
         VALUES (:id_bloque, :id_cliente, :nombre, :telefono, :email, :estatus_inicial)"
    );
    $stmt->execute([...]);

    {{DB}}->commit();
    return respond(201, 'RESERVA_CONFIRMADA', ['id_cita' => {{DB}}->lastInsertId()]);
} catch (Throwable $e) {
    {{DB}}->rollBack();
    throw $e; // Capa 6 del patrón de endpoints — MODULO_01 §2, try/catch global de infraestructura
}
```

**Liberar el cupo al cancelar (misma disciplina, en reversa):**

```sql
-- Ambas sentencias en la MISMA transacción. El WHERE estatus NOT IN (...) en
-- la 2ª sentencia hace la cancelación idempotente: si el usuario hace doble
-- clic en "Cancelar" (o el request se reintenta por timeout de red), la
-- segunda ejecución afecta 0 filas y NO libera cupo dos veces.
UPDATE {{TABLE_PREFIX}}citas
SET estatus = 'cancelada'
WHERE id = :id_cita AND estatus NOT IN ('cancelada', 'completada', 'no_show');

UPDATE {{TABLE_PREFIX}}bloques_disponibilidad
SET cupo_disponible = cupo_disponible + 1
WHERE id = :id_bloque AND cupo_disponible < cupo_maximo;
```

**Matriz de decisión — cuándo usar cada estrategia de concurrencia:**

| Estrategia | Cuándo usarla | Ventaja | Costo |
| :--- | :--- | :--- | :--- |
| **`UPDATE` condicional atómico** (`WHERE cupo_disponible > 0`) | **Default de este módulo.** Cualquier cupo (1 o N). | Cero locks explícitos, portable entre motores, funciona bajo `READ COMMITTED`. | Requiere que `bloques_disponibilidad` ya exista como fila materializada (Sección 2.4). |
| `SELECT ... FOR UPDATE` + lógica en aplicación | Cuando la decisión de "hay cupo" depende de una regla compleja que no cabe en un solo `WHERE` (ej. cruzar reglas de negocio externas antes de decrementar). | Flexible para lógica arbitraria. | Requiere abrir transacción y mantener el lock de fila abierto durante la lógica extra — más superficie para contención/deadlocks si la lógica es lenta. |
| `UNIQUE KEY` como única defensa (sin columna de cupo) | Solo cuando `cupo_maximo_concurrente = 1` siempre (nunca clases grupales). Un `INSERT` directo en `citas` con `UNIQUE(id_bloque)` que falla por duplicado. | Simplicidad máxima para el caso 1-a-1. | No expresa "cupo > 1" — no generaliza a salas compartidas sin rediseño. |
| Locking optimista (columna `version`, `UPDATE ... WHERE version = :v`, reintento en la app si falla) | Sistemas con escrituras poco frecuentes y necesidad de evitar cualquier lock, incluso implícito. | Sin locks en absoluto. | Requiere lógica de reintento en la aplicación; peor UX bajo alta contención real (reintentos visibles al usuario). |

### 2.3 Zonas Horarias: la regla de oro

> **Regla de oro, no negociable:** toda columna de fecha/hora relacionada con disponibilidad se
> almacena en **UTC**, siempre. La conversión a la zona horaria del negocio o del visitante ocurre
> **únicamente en la capa de presentación** (render), nunca se persiste un valor pre-convertido.

- **Por qué `DATETIME` y no `TIMESTAMP` (en MySQL/MariaDB):** el tipo `TIMESTAMP` se
  auto-convierte según la `time_zone` de la **sesión** de la conexión activa — un valor idéntico
  en disco puede leerse distinto si una conexión (ej. un script de mantenimiento, un ORM con
  configuración distinta) tiene una `time_zone` de sesión diferente a la que asumió el código que
  escribió el dato. `DATETIME` no hace ninguna conversión implícita: lo que se escribe es
  literalmente lo que se lee — la única forma correcta de garantizar "esto siempre es UTC" es que
  **nunca** haya conversión automática de por medio. Explícito mejor que implícito, mismo
  principio que `strict_types=1` en PHP o `"use strict"` en JS.
- **`{{ZONA_HORARIA_NEGOCIO}}` es siempre un identificador IANA** (`America/Mexico_City`,
  `Europe/Madrid`), **nunca** un offset fijo (`UTC-7`). Un offset fijo no sabe cuándo aplica
  horario de verano; un identificador IANA sí, y la librería de fechas del lenguaje que se use
  resuelve el offset correcto para cada fecha específica automáticamente.
- **Trampa real de horario de verano (DST):** un generador de slots ingenuo que arranca a las
  `06:00` hora local y va sumando `+{{GRANULARIDAD_SLOT_MINUTOS}} minutos` en un bucle **en hora
  local** se desalinea el día exacto en que cambia el horario de verano (ese día tiene 23 o 25
  horas, no 24). La forma correcta: construir cada instante del slot con una librería de fechas
  con soporte de zona horaria real (`DateTimeImmutable` + `DateTimeZone` en PHP,
  `Intl.DateTimeFormat`/`Temporal` en JS, `zoneinfo` en Python) que resuelva el offset correcto
  **por cada instante**, nunca sumando un offset fijo capturado una sola vez al inicio del bucle.
- **Trampa real de "fecha derivada":** nunca se agrega una columna `fecha DATE` calculada
  truncando un `DATETIME` en UTC. Ejemplo concreto: un negocio en `America/Mexico_City` (UTC-6) con
  un slot a las `23:30` hora local del día 12 se guarda como `05:30 UTC del día 13` — si algo
  deriva "la fecha" truncando ese UTC directamente, muestra el día 13 a un usuario que reservó
  claramente "el día 12 en la noche". La fecha calendario que ve el humano **siempre** se calcula
  convirtiendo primero a `{{ZONA_HORARIA_NEGOCIO}}` (o a la zona horaria del recurso específico,
  columna `recursos.zona_horaria`) y truncando después — nunca al revés.
- **Mostrar la zona horaria al usuario cuando pueda haber ambigüedad:** si el sistema permite
  reservas remotas (el visitante puede estar en una zona horaria distinta a la del negocio),
  la UI debe mostrar explícitamente la abreviatura/nombre de zona junto a cada hora (ej. "10:00
  AM (hora de Ciudad de México)") — nunca asumir que el visitante interpreta la hora igual que el negocio.

### 2.4 Motor de Cálculo de Disponibilidad

Algoritmo para responder "¿qué huecos hay libres para `{{ID_RECURSO}}` el `{{FECHA}}`?" — la
lógica es portable a cualquier stack, se describe aquí en pasos:

```
FUNCIÓN calcularDisponibilidad(id_recurso, fecha_solicitada, id_servicio):

  1. Resolver horario del día:
     - Buscar en horarios_recurrentes una fila con (id_recurso, dia_semana(fecha_solicitada)).
     - Si no existe, usar la fila con id_recurso = NULL (horario global) para ese dia_semana.
     - Si ninguna existe o activo = 0 → devolver lista vacía (recurso cerrado ese día).

  2. Restar bloqueos:
     - Traer bloqueos donde (id_recurso = X OR id_recurso IS NULL)
       Y el rango [inicio_utc, fin_utc] intersecta la ventana del horario del paso 1
       (convertida a UTC primero — Sección 2.3).
     - Recortar la ventana abierta del paso 1 con cada bloqueo (un bloqueo puede
       partir la ventana en 2 sub-ventanas, ej. cierre de 13:00-14:00 para comida).

  3. Generar slots atómicos:
     - Sobre cada sub-ventana resultante del paso 2, generar cortes cada
       {{GRANULARIDAD_SLOT_MINUTOS}} (o duracion_minutos del servicio si el
       proyecto consumidor prefiere slots del tamaño exacto del servicio).
     - Cada slot candidato = [inicio_utc, fin_utc) — intervalo semiabierto,
       ver Sección 2.4.1 sobre por qué el fin es EXCLUSIVO.

  4. Materializar (upsert) en bloques_disponibilidad:
     - Para cada slot candidato, INSERT ... ON DUPLICATE KEY UPDATE (o el
       equivalente del motor: INSERT ... ON CONFLICT DO NOTHING en Postgres)
       usando la UNIQUE KEY (id_recurso, id_servicio, inicio_utc) — si el
       bloque ya existía (otra petición lo materializó primero), la operación
       es un no-op seguro, nunca duplica ni resetea cupo_disponible ya
       decrementado por reservas previas.

  5. Filtrar por reglas de ventana de reserva:
     - Descartar slots cuyo inicio_utc < NOW_UTC + {{VENTANA_RESERVA_MINIMA_MINUTOS}}.
     - Descartar slots cuyo inicio_utc > NOW_UTC + {{VENTANA_RESERVA_MAXIMA_DIAS}} días.

  6. Devolver únicamente slots con cupo_disponible > 0, convertidos a la zona
     horaria de presentación (Sección 2.3) SOLO en la respuesta — el registro
     en BD permanece en UTC.
```

#### 2.4.1 Detección de empalmes (overlaps) — la fórmula exacta

Cuando la granularidad del slot es igual a la duración del servicio (en vez de una grilla fija),
o cuando se valida un bloqueo contra el horario, la pregunta recurrente es "¿estos dos intervalos
se traslapan?". La fórmula correcta, sin casos especiales:

```
existe_traslape = (existente.inicio < nuevo.fin) AND (existente.fin > nuevo.inicio)
```

- **El intervalo es semiabierto `[inicio, fin)`:** un slot que termina exactamente cuando otro
  empieza (`09:00-10:00` seguido de `10:00-11:00`) **no** se traslapa — son consecutivos y ambos
  deben poder coexistir. Usar `<=`/`>=` en vez de `<`/`>` en la fórmula de arriba es el bug de
  boundary más común en motores de agenda: rechaza citas consecutivas legítimas creyendo que
  chocan en el instante exacto de la frontera.
- Esta fórmula es la que decide si un bloqueo recorta una ventana de horario (Sección 2.4, paso
  2) y es la misma que usaría cualquier validación adicional de "el recurso no puede tener dos
  servicios de duración distinta traslapados" si el proyecto consumidor lo necesita.

### 2.5 Estados de la Cita y Cancelaciones

**Máquina de estados (transiciones válidas únicamente):**

| Desde | Hacia | Quién puede | Efecto en `cupo_disponible` |
| :--- | :--- | :--- | :--- |
| *(nueva)* | `pendiente_aprobacion` | Reserva pública sin auto-confirmación (el negocio revisa antes de comprometer el cupo) | **No** decrementa hasta aprobarse — o decrementa igual y se revierte si se rechaza (decisión de producto del proyecto consumidor, documentar cuál se eligió) |
| *(nueva)* | `reservada` | Reserva directa auto-confirmada | Decrementa (Sección 2.2) |
| `pendiente_aprobacion` | `reservada` / `confirmada` | Staff/admin (`{{TABLE_PREFIX}}usuarios`, ver MODULO_01 roles) | Decrementa si no lo había hecho ya |
| `pendiente_aprobacion` | `cancelada` | Staff/admin (rechazo) | No aplica (nunca decrementó) |
| `reservada` | `confirmada` | Staff/sistema (recordatorio confirmado) | Sin cambio |
| `reservada` / `confirmada` | `cancelada` | El propio cliente (dentro de `{{VENTANA_CANCELACION_HORAS}}`) o staff/admin (sin ventana) | Incrementa (libera cupo) |
| `reservada` / `confirmada` | `completada` | Staff (al finalizar el servicio) | Sin cambio |
| `reservada` / `confirmada` | `no_show` | Staff (el cliente no se presentó) | Sin cambio — **no** se libera cupo automáticamente; es responsabilidad de negocio decidir si un no-show libera el lugar a otro cliente en tiempo real |
| `cancelada` / `completada` / `no_show` | *cualquiera* | **Nadie — prohibido.** | — |

- **Regla de cancelación por ventana:** el cliente solo puede cancelar si
  `bloque.inicio_utc > NOW_UTC + {{VENTANA_CANCELACION_HORAS}} horas`. Fuera de esa ventana, la
  cancelación requiere intervención de staff/admin (decisión de negocio: cobrar penalización,
  contactar al cliente, etc. — fuera del alcance de este módulo).
- **Nunca `DELETE`** sobre una fila de `citas` — el estado `cancelada` preserva el historial
  completo para reportes de ocupación y no-shows (MODULO_02). El soft-delete vía `estatus` es la
  única forma de "borrar" en este módulo.
- Toda transición de estado se ejecuta dentro de una transacción junto con el ajuste
  correspondiente de `cupo_disponible` (Sección 2.2) — nunca como dos operaciones HTTP separadas
  que puedan quedar a medias si la segunda falla.

---

## 3. 🛠️ CHECKLIST PASO A PASO (TODO LIST)

### Fase 1 — Schema y configuración
- [ ] Crear las 6 tablas de la Sección 2.1, en orden (respetando FKs):
      `recursos` → `horarios_recurrentes` → `bloqueos` → `servicios` →
      `bloques_disponibilidad` → `citas` → `configuracion_agenda`.
- [ ] Sembrar `configuracion_agenda` con los valores reales de
      `{{MAX_CUPO_HORA}}`, `{{GRANULARIDAD_SLOT_MINUTOS}}`,
      `{{VENTANA_RESERVA_MINIMA_MINUTOS}}`, `{{VENTANA_RESERVA_MAXIMA_DIAS}}`,
      `{{VENTANA_CANCELACION_HORAS}}` del proyecto consumidor — nunca dejar el
      placeholder literal en producción.
- [ ] Confirmar que el motor de BD destino soporta transacciones reales
      (InnoDB, no MyISAM; Postgres nativo) — la Sección 2.2 no funciona sin esto.
- [ ] Registrar las 6 tablas y sus columnas en el Codex/Registry del proyecto
      consumidor (nombres reales, no placeholders) antes de escribir un solo
      endpoint — mismo mandamiento de "SQL antes que código" de MODULO_01.

### Fase 2 — Motor de disponibilidad (lectura)
- [ ] Implementar `calcularDisponibilidad()` (Sección 2.4) como función pura,
      testeable sin HTTP — recibe `id_recurso`, `fecha`, `id_servicio`, devuelve
      slots libres.
- [ ] Verificar la fórmula de traslape (Sección 2.4.1) con un test explícito de
      boundary: dos slots consecutivos (`fin` de uno == `inicio` del siguiente)
      **no** deben marcarse como traslapados.
- [ ] Verificar el cálculo cruzando al menos: horario recurrente + 1 bloqueo
      parcial (ej. hora de comida) + 1 bloqueo total (feriado) + cupo > 1 con
      reservas parciales existentes.
- [ ] Endpoint público de solo lectura (`GET`) que expone el resultado — sin
      autenticación si la reserva es self-service pública (patrón de invitado,
      Sección 2.1), con el Contrato de Respuesta de MODULO_01 §2.1.

### Fase 3 — Motor de reserva (escritura, concurrencia)
- [ ] Implementar el flujo de la Sección 2.2 (opción recomendada: `UPDATE`
      condicional atómico) dentro de una transacción real — no autocommit.
- [ ] Verificar bajo prueba de concurrencia real (no solo lectura del código):
      disparar 2+ requests simultáneos contra el **mismo** `id_bloque` con
      `cupo_disponible = 1` y confirmar que exactamente 1 recibe `201` y el
      resto recibe `409 SLOT_NO_DISPONIBLE` — nunca 2 éxitos, nunca 0 éxitos.
- [ ] Implementar cancelación (liberación de cupo) con el guard de idempotencia
      (`WHERE estatus NOT IN (...)`, Sección 2.2) — verificar que cancelar la
      misma cita dos veces seguidas no libera cupo dos veces.
- [ ] Implementar la máquina de estados completa (Sección 2.5) con validación
      explícita de transición — un intento de transición no listada en la
      tabla responde error, nunca se ejecuta silenciosamente.

### Fase 4 — Zonas horarias
- [ ] Confirmar que **toda** columna de fecha/hora de este módulo es `DATETIME`
      en UTC (nunca `TIMESTAMP` con conversión implícita, Sección 2.3).
- [ ] Confirmar que la conversión a `{{ZONA_HORARIA_NEGOCIO}}` (o
      `recursos.zona_horaria`) ocurre solo en la capa de presentación — grep
      del código en busca de cualquier `+ N horas` hardcodeado y eliminarlo.
- [ ] Test explícito cruzando el cambio de horario de verano del país del
      proyecto consumidor (si aplica) — generar slots del día exacto del
      cambio y confirmar que no faltan ni sobran horas.
- [ ] Test explícito de "fecha derivada" cerca de medianoche en la zona del
      negocio (Sección 2.3) — un slot a las 23:30 local debe agruparse bajo la
      fecha local correcta, no bajo el día UTC.

### Fase 5 — Frontend Mobile-First (ver Sección 4 completa)
- [ ] Vista de agenda en móvil: lista vertical de slots dentro de un selector
      de fecha horizontal — nunca un `<table>` de calendario con scroll
      horizontal forzado.
- [ ] Slots como botones táctiles ≥44×44px, no enlaces de texto pequeño.
- [ ] Botón "Reservar" se deshabilita inmediatamente al primer tap (previene
      doble-submit por doble-tap) y solo se re-habilita tras respuesta del
      servidor (éxito o error).
- [ ] Manejo explícito de `409 SLOT_NO_DISPONIBLE`: mensaje claro + refresco
      automático de la grilla de slots — nunca un error genérico ni un slot
      "fantasma" que sigue mostrándose como disponible.

### Fase 6 — Validación final
- [ ] Pasar el checklist completo de la Sección 4 (UI/UX y Zero Trust).
- [ ] Pasar la Definición de Hecho de la Sección 5.
- [ ] Actualizar este documento (Mandamiento de Mantenimiento Continuo, ver
      encabezado) con cualquier edge-case descubierto durante la
      implementación real que no esté ya cubierto arriba.

---

## 4. 🎨 REQUISITOS DE UI/UX Y SEGURIDAD (ZERO TRUST)

### 4.1 Mobile-First — anti-patrón vs. patrón correcto

- **Prohibido:** un calendario tipo grilla `<table>` semanal/mensual como vista por defecto en
  móvil — obliga a scroll horizontal, celdas ilegibles en pantallas <400px, y objetivos táctiles
  por debajo del mínimo de 44×44px.
- **Patrón correcto (progressive enhancement):**
  - **Móvil (`<`~`42rem`):** vista de **agenda por día** — un selector de fecha horizontal
    scrolleable (chips de fecha, no un `<input type="date">` nativo escondido) arriba, y debajo
    una lista **vertical** de slots del día seleccionado como botones grandes
    (`hora_inicio` – `hora_fin`, cupo restante si es >1).
  - **Escritorio (`>=`~`62rem`):** puede promoverse a una vista de grilla semanal, ya con espacio
    suficiente para columnas por día sin comprometer legibilidad — nunca al revés (nunca diseñar
    primero la grilla y "comprimirla" para móvil).
- **Ancho fluido siempre:** contenedor de la agenda con `width: 100%` + `max-width` en variable de
  diseño del proyecto consumidor — cero `px` fijos en el contenedor principal.

### 4.2 Slots como controles táctiles accesibles

```html
<div class="agenda-slots" role="radiogroup" aria-label="Horarios disponibles">
  <button type="button" class="agenda-slot" role="radio" aria-checked="false" data-id-bloque="{{ID_BLOQUE}}">
    <span class="agenda-slot__hora">{{HORA_INICIO}} – {{HORA_FIN}}</span>
    <span class="agenda-slot__cupo">{{CUPO_DISPONIBLE}} lugares</span>
  </button>
  <!-- ...un botón por slot -->
</div>
```

- `role="radiogroup"`/`role="radio"` (o `role="grid"`/`gridcell` si la vista es una grilla real)
  comunica a lectores de pantalla que es una selección única entre alternativas — nunca una lista
  de enlaces sin semántica de selección.
- Área táctil mínima 44×44px vía `padding`, nunca `width`/`height` fijos (mismo mandamiento que
  MODULO_01 §4.2/§5.3).
- El slot seleccionado usa `aria-checked="true"` + estado visual — nunca solo color (contraste
  insuficiente para usuarios con baja visión si es la única señal).

### 4.3 Prevención de errores en frontend (camino caliente de reserva)

```javascript
function initReservaSlot() {
    let enviando = false; // candado local — independiente del estado del servidor

    document.querySelectorAll('[data-id-bloque]').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            if (enviando) {
                return; // ignora dobles-tap mientras una reserva ya está en vuelo
            }
            enviando = true;
            btn.disabled = true; // deshabilitado visual inmediato, no solo lógico

            try {
                const respuesta = await fetch('{{ENDPOINT_RESERVAR}}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_bloque: btn.dataset.idBloque, /* ... */ }),
                });
                const data = await respuesta.json();

                if (respuesta.status === 409) {
                    mostrarError('Ese horario ya no está disponible. Actualizando la lista...');
                    await refrescarGrillaDeSlots(); // re-consulta disponibilidad real, nunca asume
                    return;
                }

                if (!respuesta.ok || data.status !== 'success') {
                    mostrarError(data.message || 'No se pudo completar la reserva.');
                    return;
                }

                mostrarConfirmacion(data.data);
            } catch {
                mostrarError('No se pudo conectar con el servidor. Intenta de nuevo.');
            } finally {
                enviando = false;
                btn.disabled = false;
            }
        });
    });
}
```

- El candado `enviando` + `btn.disabled` es **doble**: el flag JS previene una segunda llamada
  antes de que el navegador procese el `disabled` visual, y `disabled` previene el tap físico
  repetido — ninguno de los dos solo es suficiente en dispositivos táctiles con latencia de
  render.
- El `409` tiene un manejo **distinto y explícito** de cualquier otro error — es la señal directa
  de que el backend resolvió la condición de carrera (Sección 2.2) a favor de otro usuario; la UI
  debe reflejar la realidad (refrescar), nunca reintentar ciegamente el mismo `id_bloque`.
- Mismo contrato de "invariabilidad ante fallos" que MODULO_01 §4.5: el mensaje de error vive en
  un contenedor persistente, nunca en un toast que desaparece antes de que el usuario lo lea en
  un dispositivo móvil con latencia de atención variable.

### 4.4 Seguridad Zero Trust del endpoint de reserva

- [ ] Todo endpoint de escritura (`reservar`, `cancelar`, `confirmar`) exige el patrón de 6 capas
      de MODULO_01 §2 — CORS estricto, auth/rol si aplica, método HTTP restringido, sanitización
      estricta de payload, PDO/ORM con prepared statements, try/catch global.
- [ ] El endpoint de reserva pública (sin cuenta, patrón invitado) sigue estando sujeto a
      rate limiting por IP/device (mismo mecanismo de MODULO_01 §2.2) — una agenda pública sin
      límite de intentos es un vector de saturación de cupo por bots.
- [ ] `id_bloque` recibido del cliente se valida contra la BD en cada request — nunca se confía en
      `cupo_disponible` ni en `inicio_utc`/`fin_utc` que el frontend "recuerde" de una consulta
      anterior; el servidor es la única fuente de verdad de si el slot sigue siendo válido.
- [ ] Datos de contacto de invitado (`contacto_nombre`, `contacto_telefono`, `contacto_email`) se
      sanitizan y validan con el mismo rigor que cualquier input de formulario público (MODULO_01
      §2, Capa 4) — es una superficie de inyección/spam si se acepta sin validar.

---

## 5. 📋 DEFINICIÓN DE HECHO (DEFINITION OF DONE - DOD)

1. Las 6 tablas de la Sección 2.1 existen con sus FKs y `UNIQUE KEY`s tal cual, en el proyecto
   consumidor con nombres reales (sin placeholders literales) y registradas en su Codex.
2. Una prueba de concurrencia real (no solo lectura del código) contra un `bloque` con
   `cupo_disponible = 1` produce exactamente 1 reserva exitosa y el resto `409`, sin excepción.
3. Todo timestamp de disponibilidad se almacena en UTC; la conversión a zona horaria local ocurre
   únicamente en la capa de presentación, verificado explícitamente contra el cambio de horario de
   verano si el país del proyecto consumidor lo tiene.
4. El motor de disponibilidad (Sección 2.4) cruza correctamente horario recurrente + bloqueos +
   cupo + reservas existentes, con la fórmula de traslape de la Sección 2.4.1 verificada en un
   caso de boundary (slots consecutivos sin falso traslape).
5. La vista móvil de la agenda pasa el checklist de la Sección 4 — sin `<table>` de calendario
   forzando scroll horizontal, slots táctiles ≥44×44px, manejo explícito de `409` en el frontend.
6. Ninguna cancelación ni no-show ejecuta un `DELETE` físico — el historial completo de `citas`
   sobrevive para reportes (MODULO_02).
7. Este documento fue actualizado (Mandamiento de Mantenimiento Continuo) si la implementación
   real descubrió algún edge-case no cubierto por la versión existente al momento de empezar.
