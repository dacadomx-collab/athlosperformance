-- =============================================================================
-- ATHLOS COGNITIVE ENGINE v1.0 — SSOS (Sport Science Operating System)
-- 08_schema_testimonios.sql
-- Módulo de Casos de Éxito / Testimonios: reseñas de clientes con foto,
-- capturadas desde el BackOffice (Admin/Coach) y expuestas en un carrusel
-- público en la Landing Page (Next.js) vía api/testimonios_publicos.php.
-- Base de datos: athlos_engine_db (MySQL 8.x / InnoDB / utf8mb4_unicode_ci)
-- Depende de: usuarios (01_schema_usuarios_rbac.sql)
-- =============================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- -----------------------------------------------------------------------------
-- Tabla: testimonios_clientes
-- Un registro por reseña. `foto_ruta` guarda la ruta relativa pública
-- (ej. "media/testimonios/<hash>.webp") — nunca la ruta absoluta de disco —
-- generada por el backend con nombre de archivo hasheado (ver
-- public/ssos/testimonios/index.php::guardar_foto_testimonio()) para evitar
-- colisiones/sobreescrituras e inyección de scripts vía nombre de archivo.
-- `estatus` controla la visibilidad pública: sólo 'activo' se devuelve en el
-- endpoint público — el soft delete es el mecanismo de borrado (Mandamiento 2:
-- nunca se expone al público un registro que el staff marcó como retirado,
-- y el historial se conserva para auditoría en vez de un DELETE físico).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS testimonios_clientes (
    id_testimonio INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre_cliente VARCHAR(150) NOT NULL,
    comentario TEXT NOT NULL,
    foto_ruta VARCHAR(255) NULL COMMENT 'Ruta relativa pública, ej. media/testimonios/<hash>.webp',
    fecha_testimonio DATE NOT NULL,
    estatus ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    orden_visualizacion SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Orden manual en el carrusel público (ascendente); empate resuelto por fecha_testimonio DESC',
    created_by INT UNSIGNED NULL COMMENT 'Staff que capturó la reseña',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_testimonio),
    KEY idx_testimonios_estatus_orden (estatus, orden_visualizacion),
    CONSTRAINT fk_testimonios_usuario FOREIGN KEY (created_by) REFERENCES usuarios (id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Seed: 3 testimonios de ejemplo (mock data) para pruebas visuales del
-- carrusel — sin foto (foto_ruta NULL, el componente cae a un avatar con
-- iniciales) y sin created_by (capturados por el sistema, no por un staff
-- real) para no inventar un id_usuario que no exista en el entorno de destino.
-- -----------------------------------------------------------------------------
INSERT INTO testimonios_clientes (nombre_cliente, comentario, foto_ruta, fecha_testimonio, estatus, orden_visualizacion) VALUES
('Fernanda Higuera', 'Llegué con una lesión de rodilla que arrastraba meses y en Athlos me hicieron una evaluación real, con datos, no solo estiramientos. Hoy entreno sin dolor y entiendo por qué cada ejercicio está en mi plan.', NULL, '2026-05-12', 'activo', 1),
('Jorge Amador', 'La diferencia está en la ciencia detrás de cada sesión: antropometría, fuerza, movilidad. Es el único lugar en La Paz donde sentí que mi entrenamiento estaba diseñado para mí y no copiado de una plantilla.', NULL, '2026-06-03', 'activo', 2),
('Cecilia Rendón', 'A mis 63 años pensé que ya no podía mejorar mi condición física. El equipo de Athlos me demostró lo contrario con un programa seguro, medido y adaptado a mi historial clínico.', NULL, '2026-06-21', 'activo', 3);
