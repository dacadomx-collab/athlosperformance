-- =============================================================================
-- ATHLOS COGNITIVE ENGINE v1.0 — SSOS (Sport Science Operating System)
-- 02_schema_clientes_membresias.sql
-- CRM comercial: leads, atletas, catálogo de servicios/paquetes, membresías,
-- pagos y control de asistencia. Fuente de ingesta: knowledge/clientes_cobranza/Clientes.xlsx
-- Base de datos: athlos_engine_db (MySQL 8.x / InnoDB / utf8mb4_unicode_ci)
--
-- NOTA DE RECONCILIACIÓN: `leads_prospectos`, `atletas` y `catalogo_servicios`
-- ya están definidas como "fuente de verdad" en knowledge/02_SYSTEM_CODEX_REGISTRY.md
-- (Athlos Cognitive Engine, AI FrontDesk). Este script las reproduce en SQL
-- versionado (CREATE TABLE IF NOT EXISTS, cero cambios de forma) y las EXTIENDE
-- con el módulo comercial/cobranza del BackOffice SSOS. No se crean tablas
-- `clientes` duplicadas: `atletas` es la entidad única de cliente/atleta.
-- =============================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- -----------------------------------------------------------------------------
-- Tabla: leads_prospectos  (reproducción exacta — ver 02_SYSTEM_CODEX_REGISTRY.md)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS leads_prospectos (
    id_lead INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre_completo VARCHAR(150) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    email VARCHAR(150) NULL,
    canal_origen ENUM('whatsapp','instagram','facebook') NOT NULL,
    perfil_detectado ENUM('atleta_competitivo','rehabilitacion','composicion_corporal','sin_clasificar') NOT NULL DEFAULT 'sin_clasificar',
    objetivo_declarado TEXT NULL,
    consent_gate_status ENUM('pendiente','aceptado','rechazado') NOT NULL DEFAULT 'pendiente',
    consent_timestamp DATETIME NULL,
    nlp_entidades_json JSON NULL,
    confianza_nlp DECIMAL(3,2) NULL,
    estatus_lead ENUM('nuevo','en_conversacion','agendado','convertido','descartado') NOT NULL DEFAULT 'nuevo',
    churn_score DECIMAL(3,2) NULL,
    fecha_captura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_lead),
    UNIQUE KEY uq_leads_telefono (telefono)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Tabla: atletas  (reproducción exacta — entidad única de cliente/atleta)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS atletas (
    id_atleta INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_lead INT UNSIGNED NULL,
    nombre_completo VARCHAR(150) NOT NULL,
    fecha_nacimiento DATE NULL,
    sexo ENUM('masculino','femenino','no_especificado') NOT NULL DEFAULT 'no_especificado',
    telefono VARCHAR(20) NOT NULL,
    email VARCHAR(150) NULL,
    deporte_principal VARCHAR(100) NULL,
    tipo_membresia ENUM('sesion_unica','mensual','trimestral','semestral','anual') NOT NULL,
    estatus ENUM('activo','inactivo','suspendido') NOT NULL DEFAULT 'activo',
    antecedentes_lesion TEXT NULL,
    antecedentes_lesion_normalizado JSON NULL,
    fuente_historial ENUM('nuevo','migracion_excel','manual') NOT NULL DEFAULT 'nuevo',
    fecha_ingreso DATE NOT NULL,
    fecha_ultimo_contacto DATE NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_atleta),
    KEY idx_atletas_lead (id_lead),
    CONSTRAINT fk_atletas_lead FOREIGN KEY (id_lead) REFERENCES leads_prospectos (id_lead)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Tabla: catalogo_servicios  (reproducción exacta — 'paquete' cubre promos
-- tipo "Performance 12 sesiones" / "Funcional 8" observadas en Clientes.xlsx)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS catalogo_servicios (
    id_servicio INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre_servicio VARCHAR(200) NOT NULL,
    descripcion_tecnica TEXT NOT NULL,
    precio_base DECIMAL(10,2) NOT NULL,
    duracion_minutos INT UNSIGNED NOT NULL,
    tipo_servicio ENUM('evaluacion_inicial','entrenamiento','rehabilitacion','nutricion','paquete','asesoría') NOT NULL,
    numero_sesiones_incluidas SMALLINT UNSIGNED NULL COMMENT 'Aplica cuando tipo_servicio = paquete (ej. "Performance 12 sesiones" -> 12)',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_servicio),
    UNIQUE KEY uq_catalogoservicios_nombre (nombre_servicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Tabla: membresias
-- Instancia activa/histórica de un paquete contratado por un atleta. Controla
-- el saldo de sesiones consumibles (ej. "Performance 12 sesiones" -> 12
-- sesiones_totales, decrece sesiones_restantes en cada check-in).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS membresias (
    id_membresia INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_atleta INT UNSIGNED NOT NULL,
    id_servicio INT UNSIGNED NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NULL COMMENT 'NULL para membresías tipo sesion_unica sin vigencia calendario',
    sesiones_totales SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    sesiones_restantes SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    precio_pagado DECIMAL(10,2) NOT NULL,
    estatus ENUM('activa','agotada','vencida','cancelada') NOT NULL DEFAULT 'activa',
    notas VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_membresia),
    KEY idx_membresias_atleta (id_atleta),
    KEY idx_membresias_servicio (id_servicio),
    KEY idx_membresias_estatus (estatus),
    CONSTRAINT fk_membresias_atleta FOREIGN KEY (id_atleta) REFERENCES atletas (id_atleta)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_membresias_servicio FOREIGN KEY (id_servicio) REFERENCES catalogo_servicios (id_servicio)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Tabla: pagos_asistencia
-- Registro de cobranza. Espejo estructurado de knowledge/clientes_cobranza/Clientes.xlsx
-- (columnas observadas: Cliente, Nombre, Programa, Pago, Fecha).
-- `registrado_por` referencia usuarios.id_usuario (01_schema_usuarios_rbac.sql).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pagos_asistencia (
    id_pago INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_atleta INT UNSIGNED NOT NULL,
    id_membresia INT UNSIGNED NULL,
    concepto_pago VARCHAR(200) NOT NULL COMMENT 'Ej. "Promo performance 12 sesiones" (columna Programa del Excel legacy)',
    monto DECIMAL(10,2) NOT NULL,
    metodo_pago ENUM('efectivo','tarjeta','transferencia','otro') NOT NULL DEFAULT 'efectivo',
    fecha_pago DATE NOT NULL,
    registrado_por INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_pago),
    KEY idx_pagos_atleta (id_atleta),
    KEY idx_pagos_membresia (id_membresia),
    KEY idx_pagos_fecha (fecha_pago),
    CONSTRAINT fk_pagos_atleta FOREIGN KEY (id_atleta) REFERENCES atletas (id_atleta)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pagos_membresia FOREIGN KEY (id_membresia) REFERENCES membresias (id_membresia)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_pagos_usuario FOREIGN KEY (registrado_por) REFERENCES usuarios (id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Tabla: asistencias
-- Control de asistencia (check-in físico al laboratorio), independiente del
-- estatus de la cita en `disponibilidad_agenda` (04_schema_agenda_sesiones.sql).
-- Decrementa `membresias.sesiones_restantes` vía lógica de aplicación (PHP),
-- nunca vía trigger, para mantener la lógica de negocio fuera de la capa SQL.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS asistencias (
    id_asistencia INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_atleta INT UNSIGNED NOT NULL,
    id_cita INT UNSIGNED NULL COMMENT 'FK lógica -> disponibilidad_agenda.id_cita (definida en 04_schema_agenda_sesiones.sql)',
    id_membresia INT UNSIGNED NULL,
    fecha_hora_checkin DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    registrado_por INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_asistencia),
    KEY idx_asistencias_atleta (id_atleta),
    KEY idx_asistencias_membresia (id_membresia),
    CONSTRAINT fk_asistencias_atleta FOREIGN KEY (id_atleta) REFERENCES atletas (id_atleta)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_asistencias_membresia FOREIGN KEY (id_membresia) REFERENCES membresias (id_membresia)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_asistencias_usuario FOREIGN KEY (registrado_por) REFERENCES usuarios (id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
