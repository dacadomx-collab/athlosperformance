-- =============================================================================
-- ATHLOS COGNITIVE ENGINE v1.0 — SSOS (Sport Science Operating System)
-- 01_schema_usuarios_rbac.sql
-- Motor de Seguridad RBAC: usuarios, roles, permisos, bitácora de sesiones.
-- Base de datos: athlos_engine_db (MySQL 8.x / InnoDB / utf8mb4_unicode_ci)
-- Depende de: ninguna tabla previa (es la base de la cadena de FKs del sistema).
-- Consumida por: staff.id_staff (02_..._rbac.sql amplía `staff` con id_usuario),
--                todas las columnas *_por / created_by de los scripts 02-04.
-- =============================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- -----------------------------------------------------------------------------
-- Tabla: roles
-- Catálogo cerrado de roles del sistema (RBAC). Ver Sección 3 del Documento
-- Maestro SSOS: SUPER ADMIN (AXON_DCD), ADMIN (FrontDesk), COACH (Pie de Cancha).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
    id_rol TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clave_rol ENUM('super_admin','admin','coach') NOT NULL,
    nombre_rol VARCHAR(100) NOT NULL,
    descripcion TEXT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_rol),
    UNIQUE KEY uq_roles_clave_rol (clave_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO roles (clave_rol, nombre_rol, descripcion) VALUES
    ('super_admin', 'Super Admin (AXON_DCD)', 'Control absoluto de base de datos, credenciales API, logs de auditoría y configuración del motor cognitivo de IA.'),
    ('admin', 'Admin (Recepción / FrontDesk)', 'Gestión comercial de clientes, agenda, cobros, catálogo de paquetes/membresías y alertas de vencimiento. Sin acceso a configuración del motor IA.'),
    ('coach', 'Coach / Especialista', 'Interfaz Pie de Cancha: captura táctil de antropometría, SFT, biomecánica y sesiones. Aislado de datos financieros y cobros globales.')
ON DUPLICATE KEY UPDATE nombre_rol = VALUES(nombre_rol), descripcion = VALUES(descripcion);

-- -----------------------------------------------------------------------------
-- Tabla: permisos
-- Catálogo de capacidades atómicas del sistema. Un permiso describe UNA acción
-- sobre UN recurso lógico (ej: 'clientes.ver_saldos', 'agenda.gestionar').
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS permisos (
    id_permiso SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clave_permiso VARCHAR(100) NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    PRIMARY KEY (id_permiso),
    UNIQUE KEY uq_permisos_clave (clave_permiso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permisos (clave_permiso, modulo, descripcion) VALUES
    ('sistema.configurar_ia',            'sistema',    'Configurar credenciales API y motor cognitivo de IA'),
    ('sistema.ver_auditoria',            'sistema',    'Ver logs de auditoría de seguridad y sesiones'),
    ('sistema.gestionar_usuarios',       'sistema',    'Crear, editar y desactivar usuarios y roles'),
    ('clientes.ver',                     'clientes',   'Ver ficha de clientes/atletas'),
    ('clientes.editar',                  'clientes',   'Editar ficha de clientes/atletas'),
    ('clientes.ver_financiero',          'clientes',   'Ver saldos, cobros y datos financieros de clientes'),
    ('agenda.ver',                       'agenda',     'Ver agenda de citas'),
    ('agenda.gestionar',                 'agenda',     'Crear, mover y cancelar citas (máx. 4 personas/hora)'),
    ('evaluaciones.capturar',            'evaluaciones','Capturar antropometría, SFT y biomecánica (Pie de Cancha)'),
    ('evaluaciones.ver_todas',           'evaluaciones','Ver evaluaciones históricas de todos los atletas'),
    ('sesiones.capturar',                'sesiones',   'Registrar sesión de entrenamiento y RPE/cargas'),
    ('cobranza.gestionar',               'cobranza',   'Registrar pagos y gestionar membresías/paquetes')
ON DUPLICATE KEY UPDATE modulo = VALUES(modulo), descripcion = VALUES(descripcion);

-- -----------------------------------------------------------------------------
-- Tabla: rol_permisos
-- Pivote N:M entre roles y permisos.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rol_permisos (
    id_rol TINYINT UNSIGNED NOT NULL,
    id_permiso SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (id_rol, id_permiso),
    CONSTRAINT fk_rolpermisos_rol FOREIGN KEY (id_rol) REFERENCES roles (id_rol)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_rolpermisos_permiso FOREIGN KEY (id_permiso) REFERENCES permisos (id_permiso)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- super_admin: todos los permisos
INSERT INTO rol_permisos (id_rol, id_permiso)
SELECT (SELECT id_rol FROM roles WHERE clave_rol = 'super_admin'), id_permiso FROM permisos
ON DUPLICATE KEY UPDATE id_rol = id_rol;

-- admin: todo excepto configuración del motor IA
INSERT INTO rol_permisos (id_rol, id_permiso)
SELECT (SELECT id_rol FROM roles WHERE clave_rol = 'admin'), id_permiso FROM permisos
WHERE clave_permiso NOT IN ('sistema.configurar_ia', 'sistema.gestionar_usuarios')
ON DUPLICATE KEY UPDATE id_rol = id_rol;

-- coach: aislamiento de datos financieros (REGLA de aislamiento del Módulo 3)
INSERT INTO rol_permisos (id_rol, id_permiso)
SELECT (SELECT id_rol FROM roles WHERE clave_rol = 'coach'), id_permiso FROM permisos
WHERE clave_permiso IN ('clientes.ver', 'agenda.ver', 'evaluaciones.capturar', 'evaluaciones.ver_todas', 'sesiones.capturar')
ON DUPLICATE KEY UPDATE id_rol = id_rol;

-- -----------------------------------------------------------------------------
-- Tabla: usuarios
-- Credenciales de acceso al BackOffice. Un usuario puede o no estar ligado a
-- un registro de `staff` (02_schema_agenda_sesiones.sql define `staff`).
-- El Super Admin (AXON_DCD) puede no tener ficha de staff operativo.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_rol TINYINT UNSIGNED NOT NULL,
    id_staff INT UNSIGNED NULL COMMENT 'FK lógica -> staff.id_staff (staff se crea en 04_schema_agenda_sesiones.sql; FK se agrega en ese script)',
    nombre_completo VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL COMMENT 'Generado con password_hash() PHP (bcrypt/argon2), nunca texto plano',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    requiere_cambio_password TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_login DATETIME NULL,
    intentos_fallidos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_hasta DATETIME NULL COMMENT 'Bloqueo temporal tras exceder intentos fallidos',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_usuario),
    UNIQUE KEY uq_usuarios_email (email),
    KEY idx_usuarios_rol (id_rol),
    CONSTRAINT fk_usuarios_rol FOREIGN KEY (id_rol) REFERENCES roles (id_rol)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Tabla: sesiones_log
-- Bitácora inmutable de auditoría de seguridad: login, logout, intentos
-- fallidos y acciones sensibles. Complementa (no sustituye) `audit_log_medico`
-- ya existente en 02_SYSTEM_CODEX_REGISTRY.md, que audita riesgo clínico.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sesiones_log (
    id_log_sesion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario INT UNSIGNED NULL COMMENT 'NULL si el intento de login falló antes de resolver el usuario (email inexistente)',
    email_intento VARCHAR(150) NOT NULL,
    tipo_evento ENUM('login_exitoso','login_fallido','logout','bloqueo_temporal','cambio_password','token_csrf_invalido') NOT NULL,
    ip_origen VARCHAR(45) NOT NULL COMMENT 'Soporta IPv4 e IPv6',
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_log_sesion),
    KEY idx_sesioneslog_usuario (id_usuario),
    KEY idx_sesioneslog_fecha (created_at),
    CONSTRAINT fk_sesioneslog_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
