-- =============================================================================
-- ATHLOS COGNITIVE ENGINE v1.0 — SSOS (Sport Science Operating System)
-- 05_schema_alertas_membresias.sql
-- Semaforización de consumo de membresías (Fase 5 — Motor de Reglas de Negocio).
-- Se dispara desde AthlosBusinessRules::deducirSesionAtleta() cada vez que un
-- Coach guarda una evaluación/sesión desde Pie de Cancha.
-- Base de datos: athlos_engine_db (MySQL 8.x / InnoDB / utf8mb4_unicode_ci)
-- Depende de: atletas, membresias (02_schema_clientes_membresias.sql)
-- =============================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- -----------------------------------------------------------------------------
-- Tabla: alertas_renovacion
-- Una fila por membresía+umbral cruzado. `UNIQUE(id_membresia, tipo_alerta)` +
-- `ON DUPLICATE KEY UPDATE` evita duplicar la misma alerta en cada sesión
-- posterior; en su lugar refresca el conteo y el timestamp.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS alertas_renovacion (
    id_alerta INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_atleta INT UNSIGNED NOT NULL,
    id_membresia INT UNSIGNED NOT NULL,
    tipo_alerta ENUM('amarillo','rojo') NOT NULL COMMENT 'amarillo = quedan 2 sesiones, rojo = 0 sesiones (sin sesiones)',
    sesiones_restantes_momento SMALLINT UNSIGNED NOT NULL,
    atendida TINYINT(1) NOT NULL DEFAULT 0,
    atendida_por INT UNSIGNED NULL,
    fecha_atendida DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_alerta),
    UNIQUE KEY uq_alerta_membresia_tipo (id_membresia, tipo_alerta),
    KEY idx_alertas_atleta (id_atleta),
    CONSTRAINT fk_alertas_atleta FOREIGN KEY (id_atleta) REFERENCES atletas (id_atleta)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_alertas_membresia FOREIGN KEY (id_membresia) REFERENCES membresias (id_membresia)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_alertas_usuario FOREIGN KEY (atendida_por) REFERENCES usuarios (id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
