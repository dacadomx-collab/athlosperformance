-- =============================================================================
-- ATHLOS COGNITIVE ENGINE v1.0 — SSOS (Sport Science Operating System)
-- 03_schema_evaluaciones_clinicas.sql
-- Historial clínico, antropometría, Senior Fitness Test (SFT) y biomecánica
-- (Sentadilla Overhead). Fuentes de ingesta:
--   knowledge/Mayores_65/Mayor_65_01 Historial clínico adultos mayores Español.docx
--   knowledge/Mayores_65/Mayor_65_02 Ficha Evaluación adulto mayor.docx
--   knowledge/Menores_65/Menor_65_01 Historial clínico.docx
--   knowledge/Menores_65/Menor_65_02 DATOS ANTROMPOMETRÍA ATHLOS.xlsx
-- Base de datos: athlos_engine_db (MySQL 8.x / InnoDB / utf8mb4_unicode_ci)
-- Depende de: atletas, usuarios (02_..., 01_...)
-- =============================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- -----------------------------------------------------------------------------
-- Tabla: historial_clinico
-- Un registro por atleta (histórico se conserva vía updated_at + versión en
-- audit_log_medico si aplica). Mismos campos para Mayores_65 y Menores_65; el
-- formulario de mayores agrega datos de médico/contacto de emergencia, por
-- eso esas columnas son NULL-ables y sólo obligatorias en la capa PHP para
-- `tipo_historial = 'mayor_65'`.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS historial_clinico (
    id_historial INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_atleta INT UNSIGNED NOT NULL,
    tipo_historial ENUM('mayor_65','menor_65') NOT NULL,

    -- Ejercicio
    actividades_ejercicio_actual TEXT NULL,
    dias_ejercicio_moderado_semana TINYINT UNSIGNED NULL,
    objetivo_perdida_peso TINYINT UNSIGNED NULL COMMENT 'Escala 0-10 (mayor_65) / 0-5 (menor_65), ver REGLA de escala en capa PHP',
    objetivo_masa_muscular TINYINT UNSIGNED NULL,
    objetivo_rendimiento_deportivo TINYINT UNSIGNED NULL,
    objetivo_mejorar_salud TINYINT UNSIGNED NULL,

    -- Dieta
    dieta_saludable_score TINYINT UNSIGNED NULL COMMENT 'Escala 0-10',
    sigue_dieta_actual TEXT NULL,
    consumo_sal ENUM('bajo','medio','alto') NULL,
    consumo_azucar ENUM('bajo','medio','alto') NULL,
    consumo_grasas ENUM('bajo','medio','alto') NULL,
    control_antojos_score TINYINT UNSIGNED NULL COMMENT 'Escala 0-10, sólo mayor_65',
    bebidas_alcoholicas_semana SMALLINT UNSIGNED NULL,
    consumo_cafeina TEXT NULL COMMENT 'Sólo mayor_65',

    -- Estilo de vida
    sueno_adecuado TEXT NULL,
    nivel_estres_score TINYINT UNSIGNED NULL COMMENT 'Escala 0-10, sólo mayor_65',
    tecnicas_manejo_estres TEXT NULL COMMENT 'Sólo mayor_65',
    fuma_o_vapea TEXT NULL,

    -- Ocupación (sólo mayor_65)
    ocupacion VARCHAR(150) NULL,
    trabajo_sedentario_detalle TEXT NULL,
    trabajo_movimientos_repetitivos_detalle TEXT NULL,
    trabajo_calzado_tacon TINYINT(1) NULL,

    -- Recreación (sólo mayor_65)
    actividad_recreativa_detalle TEXT NULL,
    otro_pasatiempo_detalle TEXT NULL,

    -- Médico (antecedentes_lesion raw/normalizado ya viven en atletas — REGLA-02)
    cirugias_previas TEXT NULL,
    rehabilitacion_adecuada_autorizacion TEXT NULL,
    condicion_cronica TEXT NULL,
    medicamentos_actuales TEXT NULL,
    autorizacion_medica_ejercicio TINYINT(1) NULL,

    -- Contacto (sólo mayor_65)
    nombre_medico VARCHAR(150) NULL,
    telefono_medico VARCHAR(20) NULL,
    contacto_emergencia_nombre VARCHAR(150) NULL,
    contacto_emergencia_telefono VARCHAR(20) NULL,

    -- Contacto directo (sólo menor_65, la ficha no pide médico/emergencia)
    telefono_personal VARCHAR(20) NULL,
    correo_electronico VARCHAR(150) NULL,

    notas_adicionales TEXT NULL,
    fecha_captura DATE NOT NULL,
    capturado_por INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_historial),
    UNIQUE KEY uq_historial_atleta (id_atleta),
    CONSTRAINT fk_historial_atleta FOREIGN KEY (id_atleta) REFERENCES atletas (id_atleta)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_historial_usuario FOREIGN KEY (capturado_por) REFERENCES usuarios (id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Tabla: evaluaciones_antropometria
-- Una fila por evaluación (histórico completo, no se sobreescribe). Campos
-- derivados de Menor_65_02 DATOS ANTROMPOMETRÍA ATHLOS.xlsx (hojas ANTRO
-- MASCU / ANTRO FEME, mismo layout). Fórmulas: Siri, Rocha, Durnin & Womersley,
-- Matiegka, Wurch — ver REGLA-05: nunca interpretar sin cruzar percentiles.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS evaluaciones_antropometria (
    id_evaluacion INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_atleta INT UNSIGNED NOT NULL,
    fecha_antropometria DATE NOT NULL,
    asesor VARCHAR(150) NULL,
    edad_evaluacion TINYINT UNSIGNED NULL,

    -- Datos base
    peso_kg DECIMAL(5,2) NOT NULL,
    estatura_cm DECIMAL(5,2) NOT NULL,
    imc DECIMAL(5,2) NULL,
    clasificacion_imc ENUM('bajo_peso','normal','sobrepeso','obesidad','obesidad_severa','obesidad_morbida') NULL,
    indice_ponderal DECIMAL(6,3) NULL,

    -- Pliegues cutáneos (mm) — Durnin & Womersley / Siri
    pliegue_tricipital DECIMAL(5,2) NULL,
    pliegue_bicipital DECIMAL(5,2) NULL,
    pliegue_subescapular DECIMAL(5,2) NULL,
    pliegue_abdominal DECIMAL(5,2) NULL,
    pliegue_ileocrestal DECIMAL(5,2) NULL,
    pliegue_supraespinal DECIMAL(5,2) NULL,
    pliegue_muslo DECIMAL(5,2) NULL,
    pliegue_pierna DECIMAL(5,2) NULL,
    sumatoria_pliegues DECIMAL(6,2) NULL,

    -- Perímetros (cm) — el layout fuente distingue lado derecho/izquierdo
    perimetro_brazo_relajado_der DECIMAL(5,2) NULL,
    perimetro_brazo_relajado_izq DECIMAL(5,2) NULL,
    perimetro_brazo_contraido_der DECIMAL(5,2) NULL,
    perimetro_brazo_contraido_izq DECIMAL(5,2) NULL,
    perimetro_muneca_der DECIMAL(5,2) NULL,
    perimetro_muneca_izq DECIMAL(5,2) NULL,
    perimetro_cintura_minima DECIMAL(5,2) NULL,
    perimetro_cadera_maxima DECIMAL(5,2) NULL,
    perimetro_muslo_der DECIMAL(5,2) NULL,
    perimetro_muslo_izq DECIMAL(5,2) NULL,
    perimetro_pierna_relajada_der DECIMAL(5,2) NULL,
    perimetro_pierna_relajada_izq DECIMAL(5,2) NULL,
    perimetro_pierna_contraida_der DECIMAL(5,2) NULL,
    perimetro_pierna_contraida_izq DECIMAL(5,2) NULL,

    -- Diámetros óseos (cm)
    diametro_humeral DECIMAL(5,2) NULL,
    diametro_femoral DECIMAL(5,2) NULL,
    diametro_estiloideo DECIMAL(5,2) NULL,
    diametro_biacromial DECIMAL(5,2) NULL,
    diametro_biiliocrestal DECIMAL(5,2) NULL,

    -- Composición corporal (resultados de fórmulas, calculadas en capa PHP)
    densidad_corporal DECIMAL(6,4) NULL,
    porcentaje_grasa_siri DECIMAL(5,2) NULL,
    masa_grasa_siri_kg DECIMAL(5,2) NULL,
    porcentaje_grasa_rocha DECIMAL(5,2) NULL,
    masa_osea_rocha_kg DECIMAL(5,2) NULL,
    masa_muscular_matiegka_kg DECIMAL(5,2) NULL,
    masa_residual_wurch_kg DECIMAL(5,2) NULL,
    clasificacion_grasa ENUM('grasa_esencial','atletas','fitness','aceptable','sobregraso_moderado','sobregraso_riesgo','obeso','obeso_riesgo','obeso_morbido') NULL,

    -- Somatotipo (Heath-Carter)
    endomorfia DECIMAL(4,2) NULL,
    mesomorfia DECIMAL(4,2) NULL,
    ectomorfia DECIMAL(4,2) NULL,

    -- Índices de riesgo
    indice_cintura_cadera DECIMAL(4,3) NULL,
    clasificacion_riesgo_cintura ENUM('sin_riesgo','sin_peligro','peligro_metabolico') NULL,

    actividad_ejercicio_actual VARCHAR(255) NULL,
    frecuencia_ejercicio VARCHAR(100) NULL,
    duracion_por_sesion VARCHAR(100) NULL,
    intensidad_ejercicio VARCHAR(100) NULL,

    capturado_por INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_evaluacion),
    KEY idx_antropometria_atleta (id_atleta),
    KEY idx_antropometria_fecha (fecha_antropometria),
    CONSTRAINT fk_antropometria_atleta FOREIGN KEY (id_atleta) REFERENCES atletas (id_atleta)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_antropometria_usuario FOREIGN KEY (capturado_por) REFERENCES usuarios (id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Tabla: percentiles_sft_referencia
-- Tablas normativas del Senior Fitness Test (Rikli & Jones), por sexo y rango
-- de edad (60-64 ... 90-94). Se persisten en DB para que el Wizard SFT
-- semaforice automáticamente SIN consultar tablas externas (Checklist Fase 4).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS percentiles_sft_referencia (
    id_percentil SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    sexo ENUM('masculino','femenino') NOT NULL,
    edad_min TINYINT UNSIGNED NOT NULL,
    edad_max TINYINT UNSIGNED NOT NULL,
    variable ENUM('chair_sit_reach','back_scratch','chair_stand','arm_curl','time_up_go','two_min_step') NOT NULL,
    valor_min DECIMAL(6,2) NOT NULL,
    valor_max DECIMAL(6,2) NOT NULL,
    unidad VARCHAR(20) NOT NULL COMMENT 'cm, reps, segundos, pasos',
    PRIMARY KEY (id_percentil),
    UNIQUE KEY uq_percentil_rango (sexo, edad_min, edad_max, variable)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed: SFT Norms Hombres (Mayor_65_02 Ficha Evaluación adulto mayor.docx)
INSERT INTO percentiles_sft_referencia (sexo, edad_min, edad_max, variable, valor_min, valor_max, unidad) VALUES
('masculino',60,64,'chair_sit_reach',-6.35,10.16,'cm'), ('masculino',65,69,'chair_sit_reach',-7.62,7.62,'cm'), ('masculino',70,74,'chair_sit_reach',-8.89,6.35,'cm'), ('masculino',75,79,'chair_sit_reach',-10.16,5.08,'cm'), ('masculino',80,84,'chair_sit_reach',-13.97,3.81,'cm'), ('masculino',85,89,'chair_sit_reach',-13.97,1.27,'cm'), ('masculino',90,94,'chair_sit_reach',-16.51,-1.27,'cm'),
('masculino',60,64,'back_scratch',-16.51,0.0,'cm'), ('masculino',65,69,'back_scratch',-19.05,-2.54,'cm'), ('masculino',70,74,'back_scratch',-20.34,-2.54,'cm'), ('masculino',75,79,'back_scratch',-22.86,-5.08,'cm'), ('masculino',80,84,'back_scratch',-24.13,-5.08,'cm'), ('masculino',85,89,'back_scratch',-25.4,-7.62,'cm'), ('masculino',90,94,'back_scratch',-26.67,-10.16,'cm'),
('masculino',60,64,'chair_stand',14,19,'reps'), ('masculino',65,69,'chair_stand',12,18,'reps'), ('masculino',70,74,'chair_stand',12,17,'reps'), ('masculino',75,79,'chair_stand',11,17,'reps'), ('masculino',80,84,'chair_stand',10,15,'reps'), ('masculino',85,89,'chair_stand',8,14,'reps'), ('masculino',90,94,'chair_stand',7,12,'reps'),
('masculino',60,64,'arm_curl',16,22,'reps'), ('masculino',65,69,'arm_curl',15,21,'reps'), ('masculino',70,74,'arm_curl',14,21,'reps'), ('masculino',75,79,'arm_curl',13,19,'reps'), ('masculino',80,84,'arm_curl',13,19,'reps'), ('masculino',85,89,'arm_curl',11,17,'reps'), ('masculino',90,94,'arm_curl',10,14,'reps'),
('masculino',60,64,'time_up_go',3.8,5.6,'segundos'), ('masculino',65,69,'time_up_go',4.3,5.7,'segundos'), ('masculino',70,74,'time_up_go',4.2,6.4,'segundos'), ('masculino',75,79,'time_up_go',4.6,7.2,'segundos'), ('masculino',80,84,'time_up_go',5.2,7.6,'segundos'), ('masculino',85,89,'time_up_go',5.3,8.9,'segundos'), ('masculino',90,94,'time_up_go',6.2,10.0,'segundos'),
('masculino',60,64,'two_min_step',87,115,'pasos'), ('masculino',65,69,'two_min_step',86,116,'pasos'), ('masculino',70,74,'two_min_step',80,110,'pasos'), ('masculino',75,79,'two_min_step',73,109,'pasos'), ('masculino',80,84,'two_min_step',71,103,'pasos'), ('masculino',85,89,'two_min_step',59,91,'pasos'), ('masculino',90,94,'two_min_step',52,86,'pasos');

-- Seed: SFT Norms Mujeres (Mayor_65_02 Ficha Evaluación adulto mayor.docx)
INSERT INTO percentiles_sft_referencia (sexo, edad_min, edad_max, variable, valor_min, valor_max, unidad) VALUES
('femenino',60,64,'chair_sit_reach',-1.27,12.7,'cm'), ('femenino',65,69,'chair_sit_reach',-1.27,11.43,'cm'), ('femenino',70,74,'chair_sit_reach',-2.54,10.16,'cm'), ('femenino',75,79,'chair_sit_reach',-3.81,8.89,'cm'), ('femenino',80,84,'chair_sit_reach',-5.08,7.62,'cm'), ('femenino',85,89,'chair_sit_reach',-6.35,6.35,'cm'), ('femenino',90,94,'chair_sit_reach',-11.43,2.54,'cm'),
('femenino',60,64,'back_scratch',-7.62,3.81,'cm'), ('femenino',65,69,'back_scratch',-8.89,3.81,'cm'), ('femenino',70,74,'back_scratch',-10.16,2.54,'cm'), ('femenino',75,79,'back_scratch',-12.7,1.27,'cm'), ('femenino',80,84,'back_scratch',-13.97,0.0,'cm'), ('femenino',85,89,'back_scratch',-17.78,-1.27,'cm'), ('femenino',90,94,'back_scratch',-20.32,-2.54,'cm'),
('femenino',60,64,'chair_stand',12,17,'reps'), ('femenino',65,69,'chair_stand',11,16,'reps'), ('femenino',70,74,'chair_stand',10,15,'reps'), ('femenino',75,79,'chair_stand',10,15,'reps'), ('femenino',80,84,'chair_stand',9,14,'reps'), ('femenino',85,89,'chair_stand',8,13,'reps'), ('femenino',90,94,'chair_stand',4,11,'reps'),
('femenino',60,64,'arm_curl',13,19,'reps'), ('femenino',65,69,'arm_curl',12,18,'reps'), ('femenino',70,74,'arm_curl',12,17,'reps'), ('femenino',75,79,'arm_curl',11,17,'reps'), ('femenino',80,84,'arm_curl',10,16,'reps'), ('femenino',85,89,'arm_curl',10,15,'reps'), ('femenino',90,94,'arm_curl',8,13,'reps'),
('femenino',60,64,'time_up_go',4.4,6.0,'segundos'), ('femenino',65,69,'time_up_go',4.8,6.4,'segundos'), ('femenino',70,74,'time_up_go',4.9,7.1,'segundos'), ('femenino',75,79,'time_up_go',5.2,7.4,'segundos'), ('femenino',80,84,'time_up_go',5.7,8.7,'segundos'), ('femenino',85,89,'time_up_go',6.2,9.6,'segundos'), ('femenino',90,94,'time_up_go',7.0,11.5,'segundos'),
('femenino',60,64,'two_min_step',75,107,'pasos'), ('femenino',65,69,'two_min_step',73,107,'pasos'), ('femenino',70,74,'two_min_step',68,101,'pasos'), ('femenino',75,79,'two_min_step',68,100,'pasos'), ('femenino',80,84,'two_min_step',60,91,'pasos'), ('femenino',85,89,'two_min_step',55,85,'pasos'), ('femenino',90,94,'two_min_step',44,72,'pasos');

-- -----------------------------------------------------------------------------
-- Tabla: evaluaciones_sft
-- Resultados crudos del Senior Fitness Test por atleta/fecha. El semáforo
-- (verde/amarillo/rojo) se calcula en PHP cruzando estos valores contra
-- `percentiles_sft_referencia` (REGLA-05: nunca sin contexto normativo) y se
-- persiste ya resuelto para no recalcular en cada lectura del dashboard.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS evaluaciones_sft (
    id_evaluacion_sft INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_atleta INT UNSIGNED NOT NULL,
    fecha_evaluacion DATE NOT NULL,
    edad_evaluacion TINYINT UNSIGNED NOT NULL,
    sexo ENUM('masculino','femenino') NOT NULL,

    chair_sit_reach_cm DECIMAL(5,2) NULL,
    back_scratch_cm DECIMAL(5,2) NULL,
    functional_reach_cm DECIMAL(5,2) NULL,
    chair_stand_reps TINYINT UNSIGNED NULL,
    arm_curl_reps TINYINT UNSIGNED NULL,
    time_up_go_seg DECIMAL(4,2) NULL,
    time_up_go_cognitivo_seg DECIMAL(4,2) NULL,
    two_min_step_pasos SMALLINT UNSIGNED NULL,

    semaforo_chair_sit_reach ENUM('verde','amarillo','rojo') NULL,
    semaforo_back_scratch ENUM('verde','amarillo','rojo') NULL,
    semaforo_chair_stand ENUM('verde','amarillo','rojo') NULL,
    semaforo_arm_curl ENUM('verde','amarillo','rojo') NULL,
    semaforo_time_up_go ENUM('verde','amarillo','rojo') NULL,
    semaforo_two_min_step ENUM('verde','amarillo','rojo') NULL,
    semaforo_general ENUM('verde','amarillo','rojo') NULL COMMENT 'Peor semáforo individual (regla de agregación conservadora)',

    observaciones TEXT NULL,
    evaluado_por INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_evaluacion_sft),
    KEY idx_sft_atleta (id_atleta),
    KEY idx_sft_fecha (fecha_evaluacion),
    CONSTRAINT fk_sft_atleta FOREIGN KEY (id_atleta) REFERENCES atletas (id_atleta)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_sft_usuario FOREIGN KEY (evaluado_por) REFERENCES usuarios (id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Tabla: evaluaciones_biomecanica
-- Checklist de compensaciones posturales en Sentadilla Overhead (Ficha
-- Evaluación adulto mayor.docx, sección "Análisis"). Captura táctil rápida
-- Pie de Cancha: cada compensación es un flag booleano.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS evaluaciones_biomecanica (
    id_evaluacion_biomecanica INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_atleta INT UNSIGNED NOT NULL,
    fecha_evaluacion DATE NOT NULL,
    feet_flatten TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'El arco del pie se aplana y prona',
    feet_turn_out TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Sentadilla con pies rotados externamente',
    heel_rises TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'El peso se desplaza adelante y el talón se levanta',
    knees_move_inward TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Valgo de rodilla',
    excessive_forward_lean TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'El tronco cae hacia adelante',
    lower_back_arches TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Hiperextensión lumbar',
    lower_back_rounds TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Flexión lumbar / retroversión pélvica',
    arms_fall_forward TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Los brazos caen delante de las orejas',
    observaciones TEXT NULL,
    evaluado_por INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_evaluacion_biomecanica),
    KEY idx_biomecanica_atleta (id_atleta),
    KEY idx_biomecanica_fecha (fecha_evaluacion),
    CONSTRAINT fk_biomecanica_atleta FOREIGN KEY (id_atleta) REFERENCES atletas (id_atleta)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_biomecanica_usuario FOREIGN KEY (evaluado_por) REFERENCES usuarios (id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
