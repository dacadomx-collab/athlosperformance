-- =============================================================================
-- ATHLOS COGNITIVE ENGINE v1.0 — SSOS (Sport Science Operating System)
-- 04_schema_agenda_sesiones.sql
-- Staff, agenda/citas, auditoría médica (reproducción de la fuente de verdad
-- existente) + módulo nuevo de sesiones de entrenamiento y periodización.
-- Fuentes de ingesta:
--   knowledge/Mayores_65/Mayor_65_03 Ficha plan de sesion.xlsx (hojas Sesion/Macro)
--   knowledge/Menores_65/Menor_65_03 Ficha plan de sesion.xlsx (hojas Sesion/Macro, idéntico layout)
-- Base de datos: athlos_engine_db (MySQL 8.x / InnoDB / utf8mb4_unicode_ci)
-- Depende de: atletas, usuarios (01_..., 02_...)
-- =============================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- -----------------------------------------------------------------------------
-- Tabla: staff  (reproducción exacta — ver 02_SYSTEM_CODEX_REGISTRY.md)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS staff (
    id_staff INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre_completo VARCHAR(150) NOT NULL,
    especialidad VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NULL,
    email VARCHAR(150) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_staff),
    UNIQUE KEY uq_staff_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ahora que `staff` existe, se cierra el FK lógico declarado en usuarios.id_staff
-- (01_schema_usuarios_rbac.sql). Un usuario del BackOffice puede o no ligarse a
-- una ficha operativa de staff (el rol Dirección de Laboratorio suele no tenerla).
ALTER TABLE usuarios
    ADD CONSTRAINT fk_usuarios_staff FOREIGN KEY (id_staff) REFERENCES staff (id_staff)
        ON DELETE SET NULL ON UPDATE CASCADE;

-- -----------------------------------------------------------------------------
-- Tabla: disponibilidad_agenda  (reproducción exacta — actúa como "citas_agenda")
-- Cupo máximo por hora en el lab = 4 personas (Sección 2 del Documento Maestro
-- SSOS), aplicado en `cupo_maximo_hora` y validado en la capa PHP al reservar.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS disponibilidad_agenda (
    id_cita INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_atleta INT UNSIGNED NULL,
    id_lead INT UNSIGNED NULL,
    id_staff INT UNSIGNED NOT NULL,
    id_servicio INT UNSIGNED NOT NULL,
    fecha_cita DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    cupo_maximo_hora INT UNSIGNED NOT NULL DEFAULT 4,
    estatus_cita ENUM('disponible','reservada','confirmada','cancelada','completada','no_show') NOT NULL DEFAULT 'disponible',
    notas_previas TEXT NULL,
    confirmacion_enviada TINYINT(1) NOT NULL DEFAULT 0,
    recordatorio_enviado TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_cita),
    KEY idx_agenda_fecha_hora (fecha_cita, hora_inicio),
    KEY idx_agenda_atleta (id_atleta),
    KEY idx_agenda_lead (id_lead),
    KEY idx_agenda_staff (id_staff),
    CONSTRAINT fk_agenda_atleta FOREIGN KEY (id_atleta) REFERENCES atletas (id_atleta)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_agenda_lead FOREIGN KEY (id_lead) REFERENCES leads_prospectos (id_lead)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_agenda_staff FOREIGN KEY (id_staff) REFERENCES staff (id_staff)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_agenda_servicio FOREIGN KEY (id_servicio) REFERENCES catalogo_servicios (id_servicio)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cierra los FKs lógicos declarados en 02_schema_clientes_membresias.sql
ALTER TABLE asistencias
    ADD CONSTRAINT fk_asistencias_cita FOREIGN KEY (id_cita) REFERENCES disponibilidad_agenda (id_cita)
        ON DELETE SET NULL ON UPDATE CASCADE;

-- -----------------------------------------------------------------------------
-- Tabla: audit_log_medico  (reproducción exacta — ver 02_SYSTEM_CODEX_REGISTRY.md)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_log_medico (
    id_log INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_lead INT UNSIGNED NULL,
    id_atleta INT UNSIGNED NULL,
    canal ENUM('whatsapp','instagram','facebook') NOT NULL,
    fragmento_conversacion TEXT NOT NULL,
    terminos_medicos_detectados JSON NOT NULL,
    nivel_confianza DECIMAL(3,2) NOT NULL,
    capa_activada ENUM('constitution','rag','confidence_gate','disclaimer','escalation') NOT NULL,
    requiere_revision TINYINT(1) NOT NULL DEFAULT 1,
    revisado_por VARCHAR(100) NULL,
    fecha_revision DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_log),
    KEY idx_auditlog_lead (id_lead),
    KEY idx_auditlog_atleta (id_atleta),
    CONSTRAINT fk_auditlog_lead FOREIGN KEY (id_lead) REFERENCES leads_prospectos (id_lead)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_auditlog_atleta FOREIGN KEY (id_atleta) REFERENCES atletas (id_atleta)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Tabla: planes_macrociclo
-- Periodización anual por atleta (hoja "Macro" de Ficha plan de sesión).
-- Un registro por combinación temporada/mesociclo/mes.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS planes_macrociclo (
    id_macro INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_atleta INT UNSIGNED NOT NULL,
    temporada VARCHAR(100) NULL,
    mesociclo ENUM('prep_general','prep_especifica','competitiva','transitorio') NOT NULL,
    mes ENUM('enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre') NOT NULL,
    tipo_microciclo ENUM('ajuste','activacion','carga','competicion','impacto','recuperacion') NULL,
    volumen TINYINT UNSIGNED NULL COMMENT 'Escala 0-10 de énfasis del atributo en el periodo',
    velocidad TINYINT UNSIGNED NULL,
    fuerza TINYINT UNSIGNED NULL,
    resistencia TINYINT UNSIGNED NULL,
    flexibilidad TINYINT UNSIGNED NULL,
    tecnica TINYINT UNSIGNED NULL,
    agilidad TINYINT UNSIGNED NULL,
    total_horas DECIMAL(5,2) NULL,
    dias_microciclo TINYINT UNSIGNED NULL,
    creado_por INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_macro),
    KEY idx_macro_atleta (id_atleta),
    CONSTRAINT fk_macro_atleta FOREIGN KEY (id_atleta) REFERENCES atletas (id_atleta)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_macro_usuario FOREIGN KEY (creado_por) REFERENCES usuarios (id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Tabla: sesiones_entrenamiento
-- Cabecera de sesión individual (hoja "Sesion"): enfoque, fase, RPE global y
-- notas del entrenador. Un registro por sesión de entrenamiento ejecutada.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sesiones_entrenamiento (
    id_sesion INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_atleta INT UNSIGNED NOT NULL,
    id_cita INT UNSIGNED NULL,
    id_staff INT UNSIGNED NOT NULL,
    id_macro INT UNSIGNED NULL,
    fecha_sesion DATE NOT NULL,
    numero_sesion SMALLINT UNSIGNED NULL COMMENT 'Consecutivo de sesión dentro del microciclo/paquete',
    enfoque VARCHAR(150) NULL,
    fase ENUM('prep_general','prep_especifica','competitiva','transitorio') NULL,
    rpe_sesion DECIMAL(3,1) UNSIGNED NULL COMMENT 'Escala 1-10 (Pie de Cancha slider)',
    notas_entrenador TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_sesion),
    KEY idx_sesiones_atleta (id_atleta),
    KEY idx_sesiones_fecha (fecha_sesion),
    KEY idx_sesiones_staff (id_staff),
    CONSTRAINT fk_sesiones_atleta FOREIGN KEY (id_atleta) REFERENCES atletas (id_atleta)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_sesiones_cita FOREIGN KEY (id_cita) REFERENCES disponibilidad_agenda (id_cita)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_sesiones_staff FOREIGN KEY (id_staff) REFERENCES staff (id_staff)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_sesiones_macro FOREIGN KEY (id_macro) REFERENCES planes_macrociclo (id_macro)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_sesiones_usuario FOREIGN KEY (created_by) REFERENCES usuarios (id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Tabla: detalles_ejercicio
-- Detalle ejercicio-por-ejercicio de una sesión (bloques: Calentamiento,
-- Activación de cadera, Estiramiento dinámico, Integración del movimiento,
-- Activación cognitiva, Pliometría, Parte medular, Vuelta a la calma).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS detalles_ejercicio (
    id_detalle INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_sesion INT UNSIGNED NOT NULL,
    bloque ENUM('masaje','movilidad','activacion','calentamiento','activacion_cadera','estiramiento_dinamico','integracion_movimiento','activacion_cognitiva','pliometria','parte_medular','vuelta_calma') NOT NULL,
    orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    nombre_ejercicio VARCHAR(200) NOT NULL,
    sets VARCHAR(20) NULL COMMENT 'Texto libre para admitir rangos (ej. "2-4")',
    reps VARCHAR(20) NULL,
    intensidad VARCHAR(50) NULL,
    descanso VARCHAR(50) NULL,
    notas VARCHAR(255) NULL,
    PRIMARY KEY (id_detalle),
    KEY idx_detalles_sesion (id_sesion),
    CONSTRAINT fk_detalles_sesion FOREIGN KEY (id_sesion) REFERENCES sesiones_entrenamiento (id_sesion)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
