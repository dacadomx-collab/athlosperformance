-- =============================================================================
-- ATHLOS COGNITIVE ENGINE v1.0 — SSOS (Sport Science Operating System)
-- 07_schema_configuracion_agenda_publica.sql
-- Configuración dinámica de la Agenda (días/horarios/aforo/bloqueos),
-- disponibilidad pública con flujo de aprobación, y portal de autogestión
-- del cliente (login + cancelación autónoma). Ver arquitectura genérica y
-- agnóstica de estas 3 piezas en knowledge/MODULO_CALENDARIO_GENERICO.md.
-- Base de datos: athlos_engine_db (MySQL 8.x / InnoDB / utf8mb4_unicode_ci)
-- Depende de: staff, disponibilidad_agenda (04_...), usuarios, roles (01_...),
--             atletas (02_...)
-- =============================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- -----------------------------------------------------------------------------
-- Tabla: agenda_disponibilidad
-- Horario operativo recurrente, editable desde el Panel de Configuración —
-- reemplaza el arreglo hardcodeado que vivía en AgendaBusinessRules::
-- diasOperativos(). Un día de la semana SIN fila (o con activo=0) se
-- interpreta como cerrado — "quitar el domingo" es no tener su fila, cero
-- lógica condicional especial en el código de la matriz.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS agenda_disponibilidad (
    id_disponibilidad INT UNSIGNED NOT NULL AUTO_INCREMENT,
    dia_semana TINYINT UNSIGNED NOT NULL COMMENT '1=Lunes ... 7=Domingo (ISO-8601)',
    hora_apertura TIME NOT NULL,
    hora_cierre TIME NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_disponibilidad),
    UNIQUE KEY uq_disponibilidad_dia (dia_semana)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed: reproduce exactamente el horario que ya estaba hardcodeado (Lun-Vie
-- 06:00-22:00, Sáb 07:00-15:00, domingo sin fila = cerrado) — aplicar esta
-- migración NO cambia el comportamiento actual, sólo lo hace editable.
INSERT IGNORE INTO agenda_disponibilidad (dia_semana, hora_apertura, hora_cierre) VALUES
(1, '06:00:00', '22:00:00'),
(2, '06:00:00', '22:00:00'),
(3, '06:00:00', '22:00:00'),
(4, '06:00:00', '22:00:00'),
(5, '06:00:00', '22:00:00'),
(6, '07:00:00', '15:00:00');

-- -----------------------------------------------------------------------------
-- Tabla: agenda_configuracion
-- Clave-valor genérico para ajustes de la Agenda que no ameritan su propia
-- tabla (aforo máximo, credenciales OAuth de Google). `valor` para
-- `google_oauth_client_secret` se guarda cifrado (AES-256-CBC) desde la capa
-- de aplicación con una llave derivada de HMAC_SECRET (core/.env) — nunca en
-- texto plano, ver AgendaConfiguracion::guardarSecreto()/leerSecreto().
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS agenda_configuracion (
    clave VARCHAR(100) NOT NULL,
    valor TEXT NULL,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO agenda_configuracion (clave, valor) VALUES
('cupo_maximo_franja', '4');

-- -----------------------------------------------------------------------------
-- Tabla: agenda_bloqueos
-- Vacaciones/inasistencias de un coach específico (id_staff NOT NULL) o
-- cierre general del laboratorio (id_staff NULL — festivo, mantenimiento).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS agenda_bloqueos (
    id_bloqueo INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_staff INT UNSIGNED NULL COMMENT 'NULL = bloqueo general (festivo, cierre total del laboratorio)',
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    motivo VARCHAR(255) NULL COMMENT 'Ej. "Vacaciones", "Incapacidad", "Día festivo"',
    creado_por INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_bloqueo),
    KEY idx_bloqueos_staff_fecha (id_staff, fecha_inicio, fecha_fin),
    CONSTRAINT fk_agendabloqueos_staff FOREIGN KEY (id_staff) REFERENCES staff (id_staff)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_agendabloqueos_usuario FOREIGN KEY (creado_por) REFERENCES usuarios (id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Extensión de disponibilidad_agenda: solicitudes públicas pendientes de
-- aprobación + cancelación autónoma del cliente. Se agregan 2 valores nuevos
-- al ENUM y 3 columnas para capturar contacto de un prospecto sin ficha
-- (separadas de `notas_previas`, que queda sólo para texto libre).
-- -----------------------------------------------------------------------------
ALTER TABLE disponibilidad_agenda
    MODIFY COLUMN estatus_cita ENUM('disponible','reservada','confirmada','cancelada','completada','no_show','pendiente_aprobacion','cancelada_por_cliente') NOT NULL DEFAULT 'disponible';

ALTER TABLE disponibilidad_agenda
    ADD COLUMN IF NOT EXISTS solicitante_nombre VARCHAR(150) NULL COMMENT 'Sólo solicitudes públicas (Fase 24) — nombre de contacto del prospecto',
    ADD COLUMN IF NOT EXISTS solicitante_telefono VARCHAR(20) NULL COMMENT 'Sólo solicitudes públicas',
    ADD COLUMN IF NOT EXISTS solicitante_email VARCHAR(150) NULL COMMENT 'Sólo solicitudes públicas';

-- -----------------------------------------------------------------------------
-- Portal del cliente: nuevo rol `atleta` + vínculo opcional usuarios→atletas.
-- Un `usuarios` ahora puede ligarse a `staff` (roles operativos) O a
-- `atletas` (rol atleta), nunca ambos — la capa PHP decide cuál según el rol.
-- -----------------------------------------------------------------------------
ALTER TABLE roles
    MODIFY COLUMN clave_rol ENUM('super_admin','admin','coach','atleta') NOT NULL;

INSERT INTO roles (clave_rol, nombre_rol, descripcion) VALUES
    ('atleta', 'Portal del Cliente', 'Acceso de sólo lectura a sus propias citas, con la facultad de cancelarlas hasta 3 horas antes.')
ON DUPLICATE KEY UPDATE nombre_rol = VALUES(nombre_rol), descripcion = VALUES(descripcion);

ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS id_atleta INT UNSIGNED NULL COMMENT 'Sólo rol atleta — vínculo al expediente del cliente' AFTER id_staff;

ALTER TABLE usuarios
    ADD CONSTRAINT fk_usuarios_atleta FOREIGN KEY (id_atleta) REFERENCES atletas (id_atleta)
        ON DELETE CASCADE ON UPDATE CASCADE;
