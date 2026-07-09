-- =============================================================================
-- ATHLOS COGNITIVE ENGINE v1.0 — SSOS (Sport Science Operating System)
-- 06_schema_calendario_avanzado.sql
-- Extensión del módulo de Agenda: color por coach en la matriz semanal +
-- almacenamiento de credenciales/tokens para sincronización con calendarios
-- externos (Google Calendar / Apple Calendar). Ver arquitectura genérica y
-- agnóstica de este módulo en knowledge/MODULO_CALENDARIO_GENERICO.md — este
-- script es la adaptación concreta de esas 2 tablas a los nombres reales de
-- entidad de este proyecto (`staff` en vez de "especialistas" genérico).
-- Base de datos: athlos_engine_db (MySQL 8.x / InnoDB / utf8mb4_unicode_ci)
-- Depende de: staff (04_schema_agenda_sesiones.sql)
-- =============================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- -----------------------------------------------------------------------------
-- Tabla: staff_colores
-- Dato puramente de presentación (color de las citas de cada coach en la
-- matriz de la agenda) — vive separado de `staff` a propósito, en vez de
-- agregarle una columna que sólo le importa a la UI del calendario.
-- Asignación por rotación de paleta fija (AgendaBusinessRules::colorParaStaff()
-- en capa PHP), nunca un color picker completamente libre, para evitar
-- colisiones visuales entre coaches activos.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS staff_colores (
    id_staff INT UNSIGNED NOT NULL,
    color_hex CHAR(7) NOT NULL COMMENT 'Formato #RRGGBB',
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_staff),
    CONSTRAINT fk_staffcolores_staff FOREIGN KEY (id_staff) REFERENCES staff (id_staff)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Tabla: sincronizacion_tokens
-- Credenciales de sincronización externa por coach. `access_token`/
-- `refresh_token` se guardan cifrados desde la capa de aplicación (nunca
-- texto plano) — ver §1.4 y §3 de knowledge/MODULO_CALENDARIO_GENERICO.md
-- para el flujo OAuth2 (Google) y el feed webcal de sólo lectura (Apple).
-- Esta tabla se crea ahora para dejar el esquema listo; la sincronización
-- activa con Google (OAuth2 + webhooks) requiere credenciales de un proyecto
-- de Google Cloud Console que este entorno no tiene — no se activa en esta
-- entrega, sólo el feed webcal de Apple (que no requiere OAuth).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sincronizacion_tokens (
    id_token INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_staff INT UNSIGNED NOT NULL,
    proveedor ENUM('google_calendar','apple_calendar') NOT NULL,
    access_token TEXT NULL COMMENT 'Sólo Google — cifrado en capa de aplicación',
    refresh_token TEXT NULL COMMENT 'Sólo Google',
    token_expira DATETIME NULL COMMENT 'Sólo Google',
    calendario_externo_id VARCHAR(255) NULL COMMENT 'Sólo Google — calendarId',
    webhook_channel_id VARCHAR(255) NULL COMMENT 'Sólo Google — canal de notificaciones push, expira máx. 30 días',
    webhook_channel_expira DATETIME NULL,
    webcal_uid VARCHAR(64) NULL COMMENT 'Sólo Apple/webcal — token opaco impredecible en la URL pública del feed .ics',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_token),
    UNIQUE KEY uq_sync_staff_proveedor (id_staff, proveedor),
    KEY idx_sync_webcal_uid (webcal_uid),
    CONSTRAINT fk_synctokens_staff FOREIGN KEY (id_staff) REFERENCES staff (id_staff)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
