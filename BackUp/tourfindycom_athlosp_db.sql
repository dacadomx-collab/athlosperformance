-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 20-08-2026 a las 20:43:33
-- Versión del servidor: 11.4.12-MariaDB
-- Versión de PHP: 8.4.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `tourfindycom_athlosp_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `acadep_vocacional_leads`
--

CREATE TABLE `acadep_vocacional_leads` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `whatsapp` varchar(20) NOT NULL,
  `score_tecnico` int(11) NOT NULL DEFAULT 0,
  `score_comercial` int(11) NOT NULL DEFAULT 0,
  `score_creativo` int(11) NOT NULL DEFAULT 0,
  `score_humano` int(11) NOT NULL DEFAULT 0,
  `perfil_resultado` varchar(100) NOT NULL,
  `tiempo_total_seg` float NOT NULL,
  `pregunta_mas_rapida` int(11) NOT NULL,
  `tiempo_mas_rapido_seg` float NOT NULL,
  `pregunta_mas_lenta` int(11) NOT NULL,
  `tiempo_mas_lento_seg` float NOT NULL,
  `telemetria_json` text NOT NULL,
  `ai_insight_manifesto` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `acadep_vocacional_leads`
--

INSERT INTO `acadep_vocacional_leads` (`id`, `nombre`, `email`, `whatsapp`, `score_tecnico`, `score_comercial`, `score_creativo`, `score_humano`, `perfil_resultado`, `tiempo_total_seg`, `pregunta_mas_rapida`, `tiempo_mas_rapido_seg`, `pregunta_mas_lenta`, `tiempo_mas_lento_seg`, `telemetria_json`, `ai_insight_manifesto`, `created_at`) VALUES
(2, 'David Cabrera', 'dacadomx@yahoo.com', '5562065829', 5, 7, 6, 2, 'Estratega de Valor', 28.7344, 6, 1.0481, 1, 4.3668, '[{\"index\":0,\"ms\":4366.799999952316},{\"index\":1,\"ms\":1215.800000011921},{\"index\":2,\"ms\":1360.1000000238419},{\"index\":3,\"ms\":1400.199999988079},{\"index\":4,\"ms\":1391.800000011921},{\"index\":5,\"ms\":1048.0999999642372},{\"index\":6,\"ms\":1264},{\"index\":7,\"ms\":1063.800000011921},{\"index\":8,\"ms\":1399.800000011921},{\"index\":9,\"ms\":1232.4000000357628},{\"index\":10,\"ms\":1096},{\"index\":11,\"ms\":1271.699999988079},{\"index\":12,\"ms\":1280},{\"index\":13,\"ms\":1616.0999999642372},{\"index\":14,\"ms\":1432.5},{\"index\":15,\"ms\":1471.4000000357628},{\"index\":16,\"ms\":1232.0999999642372},{\"index\":17,\"ms\":1207.9000000357628},{\"index\":18,\"ms\":1256.300000011921},{\"index\":19,\"ms\":1127.5999999642372}]', 'David, el análisis vocacional de ACADEP confirma una afinidad dominante hacia el perfil Estratega de Valor. La distribución de tu pensamiento se concentra en 5/20 puntos técnicos, 7/20 comerciales, 6/20 creativos y 2/20 humanos, un patrón consistente y medible.', '2026-06-19 23:45:37'),
(3, 'Gibran Morales', 'gibran.morales@hotmail.com', '6121051782', 16, 1, 1, 2, 'Arquitecto Invisible', 1278.18, 19, 8.8047, 6, 361.44, '[{\"index\":0,\"ms\":350395.10000000894},{\"index\":1,\"ms\":44787.79999999702},{\"index\":2,\"ms\":30458.70000000298},{\"index\":3,\"ms\":71944.09999999404},{\"index\":4,\"ms\":15026.10000000894},{\"index\":5,\"ms\":361440.299999997},{\"index\":6,\"ms\":18718},{\"index\":7,\"ms\":40109.70000000298},{\"index\":8,\"ms\":15296.90000000596},{\"index\":9,\"ms\":15569.10000000894},{\"index\":10,\"ms\":37672.09999999404},{\"index\":11,\"ms\":93901.29999999702},{\"index\":12,\"ms\":29722.79999999702},{\"index\":13,\"ms\":46404},{\"index\":14,\"ms\":16368.70000000298},{\"index\":15,\"ms\":9547.10000000894},{\"index\":16,\"ms\":41574.29999999702},{\"index\":17,\"ms\":19510.59999999404},{\"index\":18,\"ms\":8804.699999988079},{\"index\":19,\"ms\":10930.90000000596}]', 'Gibran, el análisis vocacional de ACADEP confirma una afinidad dominante hacia el perfil Arquitecto Invisible. La distribución de tu pensamiento se concentra en 16/20 puntos técnicos, 1/20 comerciales, 1/20 creativos y 2/20 humanos, un patrón consistente y medible.', '2026-06-20 00:17:28'),
(4, 'Germán Caleb Lage Castillo', 'caleblage1@gmail.com', '6122058933', 0, 5, 8, 7, 'Intérprete Estético', 519.949, 10, 13.2245, 14, 48.492, '[{\"index\":0,\"ms\":38887.2999997139},{\"index\":1,\"ms\":19529.89999961853},{\"index\":2,\"ms\":28781.200000286102},{\"index\":3,\"ms\":44517},{\"index\":4,\"ms\":32066.099999904633},{\"index\":5,\"ms\":17883.599999904633},{\"index\":6,\"ms\":23282.89999961853},{\"index\":7,\"ms\":35451.40000009537},{\"index\":8,\"ms\":19578.599999904633},{\"index\":9,\"ms\":13224.5},{\"index\":10,\"ms\":31694.699999809265},{\"index\":11,\"ms\":24199.900000095367},{\"index\":12,\"ms\":18572},{\"index\":13,\"ms\":48492},{\"index\":14,\"ms\":18887.900000095367},{\"index\":15,\"ms\":17913.799999713898},{\"index\":16,\"ms\":28098.700000286102},{\"index\":17,\"ms\":17045.199999809265},{\"index\":18,\"ms\":15222.700000286102},{\"index\":19,\"ms\":26619.799999713898}]', 'Germán, el análisis vocacional de ACADEP confirma una afinidad dominante hacia el perfil Intérprete Estético. La distribución de tu pensamiento se concentra en 0/20 puntos técnicos, 5/20 comerciales, 8/20 creativos y 7/20 humanos, un patrón consistente y medible.', '2026-06-20 00:20:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `agenda_bloqueos`
--

CREATE TABLE `agenda_bloqueos` (
  `id_bloqueo` int(10) UNSIGNED NOT NULL,
  `id_staff` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL = bloqueo general (festivo, cierre total del laboratorio)',
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime NOT NULL,
  `motivo` varchar(255) DEFAULT NULL COMMENT 'Ej. "Vacaciones", "Incapacidad", "Día festivo"',
  `creado_por` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `agenda_configuracion`
--

CREATE TABLE `agenda_configuracion` (
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `agenda_configuracion`
--

INSERT INTO `agenda_configuracion` (`clave`, `valor`, `updated_at`) VALUES
('cupo_maximo_franja', '4', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `agenda_disponibilidad`
--

CREATE TABLE `agenda_disponibilidad` (
  `id_disponibilidad` int(10) UNSIGNED NOT NULL,
  `dia_semana` tinyint(3) UNSIGNED NOT NULL COMMENT '1=Lunes ... 7=Domingo (ISO-8601)',
  `hora_apertura` time NOT NULL,
  `hora_cierre` time NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `agenda_disponibilidad`
--

INSERT INTO `agenda_disponibilidad` (`id_disponibilidad`, `dia_semana`, `hora_apertura`, `hora_cierre`, `activo`, `updated_at`) VALUES
(1, 1, '06:00:00', '22:00:00', 1, NULL),
(2, 2, '06:00:00', '22:00:00', 1, NULL),
(3, 3, '06:00:00', '22:00:00', 1, NULL),
(4, 4, '06:00:00', '22:00:00', 1, NULL),
(5, 5, '06:00:00', '22:00:00', 1, NULL),
(6, 6, '07:00:00', '15:00:00', 1, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alertas_renovacion`
--

CREATE TABLE `alertas_renovacion` (
  `id_alerta` int(10) UNSIGNED NOT NULL,
  `id_atleta` int(10) UNSIGNED NOT NULL,
  `id_membresia` int(10) UNSIGNED NOT NULL,
  `tipo_alerta` enum('amarillo','rojo') NOT NULL COMMENT 'amarillo = quedan 2 sesiones, rojo = 0 sesiones (sin sesiones)',
  `sesiones_restantes_momento` smallint(5) UNSIGNED NOT NULL,
  `atendida` tinyint(1) NOT NULL DEFAULT 0,
  `atendida_por` int(10) UNSIGNED DEFAULT NULL,
  `fecha_atendida` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencias`
--

CREATE TABLE `asistencias` (
  `id_asistencia` int(10) UNSIGNED NOT NULL,
  `id_atleta` int(10) UNSIGNED NOT NULL,
  `id_cita` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK lógica -> disponibilidad_agenda.id_cita (definida en 04_schema_agenda_sesiones.sql)',
  `id_membresia` int(10) UNSIGNED DEFAULT NULL,
  `fecha_hora_checkin` datetime NOT NULL DEFAULT current_timestamp(),
  `registrado_por` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `atletas`
--

CREATE TABLE `atletas` (
  `id_atleta` int(10) UNSIGNED NOT NULL,
  `id_lead` int(10) UNSIGNED DEFAULT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `sexo` enum('masculino','femenino','no_especificado') NOT NULL DEFAULT 'no_especificado',
  `telefono` varchar(20) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `deporte_principal` varchar(100) DEFAULT NULL,
  `tipo_membresia` enum('sesion_unica','mensual','trimestral','semestral','anual') NOT NULL,
  `estatus` enum('activo','inactivo','suspendido') NOT NULL DEFAULT 'activo',
  `antecedentes_lesion` text DEFAULT NULL,
  `antecedentes_lesion_normalizado` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`antecedentes_lesion_normalizado`)),
  `fuente_historial` enum('nuevo','migracion_excel','manual') NOT NULL DEFAULT 'nuevo',
  `fecha_ingreso` date NOT NULL,
  `fecha_ultimo_contacto` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `atletas`
--

INSERT INTO `atletas` (`id_atleta`, `id_lead`, `nombre_completo`, `fecha_nacimiento`, `sexo`, `telefono`, `email`, `deporte_principal`, `tipo_membresia`, `estatus`, `antecedentes_lesion`, `antecedentes_lesion_normalizado`, `fuente_historial`, `fecha_ingreso`, `fecha_ultimo_contacto`, `created_at`, `updated_at`) VALUES
(8, NULL, 'Mario Nicolás Almada', NULL, 'no_especificado', 'SIN-TEL-1', NULL, NULL, 'sesion_unica', 'suspendido', NULL, NULL, 'migracion_excel', '2026-05-04', NULL, '2026-07-08 20:12:24', '2026-08-11 18:49:35'),
(9, NULL, 'Regina Meza Osuna', '2010-09-24', 'no_especificado', '6121577326', NULL, NULL, 'sesion_unica', 'activo', NULL, NULL, 'migracion_excel', '2026-05-04', NULL, '2026-07-08 20:12:25', '2026-08-11 18:51:12'),
(10, NULL, 'Enrique Guzmán Quezada', '1941-04-10', 'masculino', '6121591065', NULL, NULL, 'sesion_unica', 'activo', NULL, NULL, 'migracion_excel', '2026-05-04', NULL, '2026-07-08 20:12:26', '2026-08-11 18:58:41'),
(11, NULL, 'Guillermo Lobo', '1967-06-23', 'no_especificado', '6121703774', NULL, NULL, 'sesion_unica', 'activo', NULL, NULL, 'migracion_excel', '2026-05-04', NULL, '2026-07-08 20:12:27', '2026-08-11 18:52:31'),
(12, NULL, 'Regina Lobo', NULL, 'no_especificado', '9848764755', NULL, NULL, 'sesion_unica', 'activo', NULL, NULL, 'migracion_excel', '2026-05-04', NULL, '2026-07-08 20:12:28', '2026-08-11 19:06:04'),
(13, NULL, 'Gabriela Barrera', NULL, 'no_especificado', '6121203050', NULL, NULL, 'sesion_unica', 'activo', NULL, NULL, 'migracion_excel', '2026-05-04', NULL, '2026-07-08 20:12:29', '2026-08-11 19:08:11'),
(14, NULL, 'Victor Ramirez', NULL, 'no_especificado', 'SIN-TEL-7', NULL, NULL, 'sesion_unica', 'suspendido', NULL, NULL, 'migracion_excel', '2026-05-06', NULL, '2026-07-08 20:12:30', '2026-08-11 19:05:20'),
(15, NULL, 'Raúl Hirales', NULL, 'no_especificado', 'SIN-TEL-8', NULL, NULL, 'sesion_unica', 'inactivo', NULL, NULL, 'migracion_excel', '2026-05-07', NULL, '2026-07-08 20:12:31', '2026-08-11 19:05:11'),
(16, NULL, 'Alejandra Torres', NULL, 'no_especificado', 'SIN-TEL-9', NULL, NULL, 'sesion_unica', 'suspendido', NULL, NULL, 'migracion_excel', '2026-05-07', NULL, '2026-07-08 20:12:32', '2026-08-11 19:07:23'),
(17, NULL, 'David Perpuly', NULL, 'no_especificado', '6151133284', NULL, NULL, 'sesion_unica', 'activo', NULL, NULL, 'migracion_excel', '2026-05-11', NULL, '2026-07-08 20:12:33', '2026-08-11 19:03:07'),
(18, NULL, 'Mai', NULL, 'no_especificado', 'SIN-TEL-11', 'maialyschild@me.com', NULL, 'sesion_unica', 'activo', NULL, NULL, 'migracion_excel', '2026-06-03', NULL, '2026-07-08 20:12:34', '2026-08-11 19:13:32'),
(19, NULL, 'Virginia Lobo', NULL, 'no_especificado', '9981107775', NULL, NULL, 'sesion_unica', 'activo', NULL, NULL, 'migracion_excel', '2026-06-03', NULL, '2026-07-08 20:12:35', '2026-08-11 19:04:04'),
(20, NULL, 'Marco', NULL, 'no_especificado', 'SIN-TEL-13', NULL, NULL, 'sesion_unica', 'suspendido', NULL, NULL, 'migracion_excel', '2026-06-04', NULL, '2026-07-08 20:12:36', '2026-08-11 19:06:31'),
(21, NULL, 'Nikolai Puhlmann', '2014-12-14', 'no_especificado', '5554356349', NULL, NULL, 'sesion_unica', 'activo', NULL, NULL, 'migracion_excel', '2026-06-04', NULL, '2026-07-08 20:12:37', '2026-08-11 19:00:45'),
(22, NULL, 'Marisol Zarate Bravo', '1984-03-19', 'no_especificado', '5554356349', NULL, NULL, 'sesion_unica', 'activo', NULL, NULL, 'migracion_excel', '2026-06-04', NULL, '2026-07-08 20:12:38', '2026-08-11 18:59:39'),
(23, NULL, 'Frida Domínguez Escalera', NULL, 'no_especificado', '6241694267', NULL, NULL, 'sesion_unica', 'activo', NULL, NULL, 'migracion_excel', '2026-06-05', NULL, '2026-07-08 20:12:41', '2026-08-11 19:02:23'),
(24, NULL, 'Maribel Escalera Gomez', '1977-11-13', 'no_especificado', '6241694267', NULL, NULL, 'sesion_unica', 'activo', NULL, NULL, 'migracion_excel', '2026-06-05', NULL, '2026-07-08 20:12:42', '2026-08-12 00:09:26'),
(25, NULL, 'Ivone', NULL, 'femenino', 'SIN-TEL-25', NULL, NULL, 'sesion_unica', 'activo', NULL, NULL, 'manual', '2026-07-09', NULL, '2026-07-09 04:03:08', '2026-08-14 17:06:24'),
(26, NULL, 'Ivonne Cervera Ruiz', '1988-01-01', 'femenino', '5513554840', 'ivone.crs@gmail.com', NULL, 'sesion_unica', 'activo', NULL, NULL, 'manual', '2026-05-16', NULL, '2026-07-09 04:17:40', '2026-08-11 19:32:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `audit_log_medico`
--

CREATE TABLE `audit_log_medico` (
  `id_log` int(10) UNSIGNED NOT NULL,
  `id_lead` int(10) UNSIGNED DEFAULT NULL,
  `id_atleta` int(10) UNSIGNED DEFAULT NULL,
  `canal` enum('whatsapp','instagram','facebook') NOT NULL,
  `fragmento_conversacion` text NOT NULL,
  `terminos_medicos_detectados` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`terminos_medicos_detectados`)),
  `nivel_confianza` decimal(3,2) NOT NULL,
  `capa_activada` enum('constitution','rag','confidence_gate','disclaimer','escalation') NOT NULL,
  `requiere_revision` tinyint(1) NOT NULL DEFAULT 1,
  `revisado_por` varchar(100) DEFAULT NULL,
  `fecha_revision` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `catalogo_servicios`
--

CREATE TABLE `catalogo_servicios` (
  `id_servicio` int(10) UNSIGNED NOT NULL,
  `nombre_servicio` varchar(200) NOT NULL,
  `descripcion_tecnica` text NOT NULL,
  `precio_base` decimal(10,2) NOT NULL,
  `duracion_minutos` int(10) UNSIGNED NOT NULL,
  `tipo_servicio` enum('evaluacion_inicial','entrenamiento','rehabilitacion','nutricion','paquete','asesoría') NOT NULL,
  `numero_sesiones_incluidas` smallint(5) UNSIGNED DEFAULT NULL COMMENT 'Aplica cuando tipo_servicio = paquete (ej. "Performance 12 sesiones" -> 12)',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `catalogo_servicios`
--

INSERT INTO `catalogo_servicios` (`id_servicio`, `nombre_servicio`, `descripcion_tecnica`, `precio_base`, `duracion_minutos`, `tipo_servicio`, `numero_sesiones_incluidas`, `activo`, `created_at`, `updated_at`) VALUES
(8, 'Promo performance 12 sesiones', 'Migrado automáticamente desde Clientes.xlsx (clientes_cobranza).', 3500.00, 60, 'paquete', 12, 1, '2026-07-08 20:12:25', NULL),
(9, 'Promo especial performance 12', 'Migrado automáticamente desde Clientes.xlsx (clientes_cobranza).', 3000.00, 60, 'paquete', 12, 1, '2026-07-08 20:12:26', NULL),
(10, 'Promo familia especial', 'Migrado automáticamente desde Clientes.xlsx (clientes_cobranza).', 500.00, 60, 'paquete', 1, 1, '2026-07-08 20:12:28', NULL),
(11, 'Promo kids 8', 'Migrado automáticamente desde Clientes.xlsx (clientes_cobranza).', 1150.00, 60, 'paquete', 8, 1, '2026-07-08 20:12:30', NULL),
(12, 'Funcional 8', 'Migrado automáticamente desde Clientes.xlsx (clientes_cobranza).', 1400.00, 60, 'paquete', 8, 1, '2026-07-08 20:12:32', NULL),
(13, 'Senior 12 sesiones', 'Migrado automáticamente desde Clientes.xlsx (clientes_cobranza).', 3450.00, 60, 'paquete', 12, 1, '2026-07-08 20:12:34', NULL),
(14, 'Performance 12 sesiones', 'Migrado automáticamente desde Clientes.xlsx (clientes_cobranza).', 3450.00, 60, 'paquete', 12, 1, '2026-07-08 20:12:35', NULL),
(15, '2 sesiones funcional', 'Migrado automáticamente desde Clientes.xlsx (clientes_cobranza).', 500.00, 60, 'paquete', 2, 1, '2026-07-08 20:12:36', NULL),
(16, 'kids 8 sesiones familiar 25%', 'Migrado automáticamente desde Clientes.xlsx (clientes_cobranza).', 1087.00, 60, 'paquete', 8, 1, '2026-07-08 20:12:37', NULL),
(17, 'Funcional 8 sesiones familiar 25%', 'Migrado automáticamente desde Clientes.xlsx (clientes_cobranza).', 1550.00, 60, 'paquete', 8, 1, '2026-07-08 20:12:38', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_ejercicio`
--

CREATE TABLE `detalles_ejercicio` (
  `id_detalle` int(10) UNSIGNED NOT NULL,
  `id_sesion` int(10) UNSIGNED NOT NULL,
  `bloque` enum('masaje','movilidad','activacion','calentamiento','activacion_cadera','estiramiento_dinamico','integracion_movimiento','activacion_cognitiva','pliometria','parte_medular','vuelta_calma') NOT NULL,
  `orden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `nombre_ejercicio` varchar(200) NOT NULL,
  `sets` varchar(20) DEFAULT NULL COMMENT 'Texto libre para admitir rangos (ej. "2-4")',
  `reps` varchar(20) DEFAULT NULL,
  `intensidad` varchar(50) DEFAULT NULL,
  `descanso` varchar(50) DEFAULT NULL,
  `notas` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `disponibilidad_agenda`
--

CREATE TABLE `disponibilidad_agenda` (
  `id_cita` int(10) UNSIGNED NOT NULL,
  `id_atleta` int(10) UNSIGNED DEFAULT NULL,
  `id_lead` int(10) UNSIGNED DEFAULT NULL,
  `id_staff` int(10) UNSIGNED NOT NULL,
  `id_servicio` int(10) UNSIGNED NOT NULL,
  `fecha_cita` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `cupo_maximo_hora` int(10) UNSIGNED NOT NULL DEFAULT 4,
  `estatus_cita` enum('disponible','reservada','confirmada','cancelada','completada','no_show','pendiente_aprobacion','cancelada_por_cliente') NOT NULL DEFAULT 'disponible',
  `notas_previas` text DEFAULT NULL,
  `confirmacion_enviada` tinyint(1) NOT NULL DEFAULT 0,
  `recordatorio_enviado` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `solicitante_nombre` varchar(150) DEFAULT NULL COMMENT 'Sólo solicitudes públicas (Fase 24) — nombre de contacto del prospecto',
  `solicitante_telefono` varchar(20) DEFAULT NULL COMMENT 'Sólo solicitudes públicas',
  `solicitante_email` varchar(150) DEFAULT NULL COMMENT 'Sólo solicitudes públicas'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `disponibilidad_agenda`
--

INSERT INTO `disponibilidad_agenda` (`id_cita`, `id_atleta`, `id_lead`, `id_staff`, `id_servicio`, `fecha_cita`, `hora_inicio`, `hora_fin`, `cupo_maximo_hora`, `estatus_cita`, `notas_previas`, `confirmacion_enviada`, `recordatorio_enviado`, `created_at`, `updated_at`, `solicitante_nombre`, `solicitante_telefono`, `solicitante_email`) VALUES
(5, NULL, NULL, 2, 12, '2026-07-09', '11:00:00', '12:00:00', 4, 'reservada', 'Prospecto (sin ficha): David Cabrera', 0, 0, '2026-07-09 17:28:43', NULL, NULL, NULL, NULL),
(6, 26, NULL, 2, 16, '2026-07-09', '11:00:00', '12:00:00', 4, 'reservada', NULL, 0, 0, '2026-07-09 17:29:09', NULL, NULL, NULL, NULL),
(7, 10, NULL, 2, 17, '2026-07-10', '09:00:00', '10:00:00', 4, 'reservada', NULL, 0, 0, '2026-07-09 18:20:46', NULL, NULL, NULL, NULL),
(8, 10, NULL, 2, 17, '2026-07-10', '09:00:00', '10:00:00', 4, 'reservada', NULL, 0, 0, '2026-07-09 18:20:50', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evaluaciones_antropometria`
--

CREATE TABLE `evaluaciones_antropometria` (
  `id_evaluacion` int(10) UNSIGNED NOT NULL,
  `id_atleta` int(10) UNSIGNED NOT NULL,
  `fecha_antropometria` date NOT NULL,
  `asesor` varchar(150) DEFAULT NULL,
  `edad_evaluacion` tinyint(3) UNSIGNED DEFAULT NULL,
  `peso_kg` decimal(5,2) NOT NULL,
  `estatura_cm` decimal(5,2) NOT NULL,
  `imc` decimal(5,2) DEFAULT NULL,
  `clasificacion_imc` enum('bajo_peso','normal','sobrepeso','obesidad','obesidad_severa','obesidad_morbida') DEFAULT NULL,
  `indice_ponderal` decimal(6,3) DEFAULT NULL,
  `pliegue_tricipital` decimal(5,2) DEFAULT NULL,
  `pliegue_bicipital` decimal(5,2) DEFAULT NULL,
  `pliegue_subescapular` decimal(5,2) DEFAULT NULL,
  `pliegue_abdominal` decimal(5,2) DEFAULT NULL,
  `pliegue_ileocrestal` decimal(5,2) DEFAULT NULL,
  `pliegue_supraespinal` decimal(5,2) DEFAULT NULL,
  `pliegue_muslo` decimal(5,2) DEFAULT NULL,
  `pliegue_pierna` decimal(5,2) DEFAULT NULL,
  `sumatoria_pliegues` decimal(6,2) DEFAULT NULL,
  `perimetro_brazo_relajado_der` decimal(5,2) DEFAULT NULL,
  `perimetro_brazo_relajado_izq` decimal(5,2) DEFAULT NULL,
  `perimetro_brazo_contraido_der` decimal(5,2) DEFAULT NULL,
  `perimetro_brazo_contraido_izq` decimal(5,2) DEFAULT NULL,
  `perimetro_muneca_der` decimal(5,2) DEFAULT NULL,
  `perimetro_muneca_izq` decimal(5,2) DEFAULT NULL,
  `perimetro_cintura_minima` decimal(5,2) DEFAULT NULL,
  `perimetro_cadera_maxima` decimal(5,2) DEFAULT NULL,
  `perimetro_muslo_der` decimal(5,2) DEFAULT NULL,
  `perimetro_muslo_izq` decimal(5,2) DEFAULT NULL,
  `perimetro_pierna_relajada_der` decimal(5,2) DEFAULT NULL,
  `perimetro_pierna_relajada_izq` decimal(5,2) DEFAULT NULL,
  `perimetro_pierna_contraida_der` decimal(5,2) DEFAULT NULL,
  `perimetro_pierna_contraida_izq` decimal(5,2) DEFAULT NULL,
  `diametro_humeral` decimal(5,2) DEFAULT NULL,
  `diametro_femoral` decimal(5,2) DEFAULT NULL,
  `diametro_estiloideo` decimal(5,2) DEFAULT NULL,
  `diametro_biacromial` decimal(5,2) DEFAULT NULL,
  `diametro_biiliocrestal` decimal(5,2) DEFAULT NULL,
  `densidad_corporal` decimal(6,4) DEFAULT NULL,
  `porcentaje_grasa_siri` decimal(5,2) DEFAULT NULL,
  `masa_grasa_siri_kg` decimal(5,2) DEFAULT NULL,
  `porcentaje_grasa_rocha` decimal(5,2) DEFAULT NULL,
  `masa_osea_rocha_kg` decimal(5,2) DEFAULT NULL,
  `masa_muscular_matiegka_kg` decimal(5,2) DEFAULT NULL,
  `masa_residual_wurch_kg` decimal(5,2) DEFAULT NULL,
  `clasificacion_grasa` enum('grasa_esencial','atletas','fitness','aceptable','sobregraso_moderado','sobregraso_riesgo','obeso','obeso_riesgo','obeso_morbido') DEFAULT NULL,
  `endomorfia` decimal(4,2) DEFAULT NULL,
  `mesomorfia` decimal(4,2) DEFAULT NULL,
  `ectomorfia` decimal(4,2) DEFAULT NULL,
  `indice_cintura_cadera` decimal(4,3) DEFAULT NULL,
  `clasificacion_riesgo_cintura` enum('sin_riesgo','sin_peligro','peligro_metabolico') DEFAULT NULL,
  `actividad_ejercicio_actual` varchar(255) DEFAULT NULL,
  `frecuencia_ejercicio` varchar(100) DEFAULT NULL,
  `duracion_por_sesion` varchar(100) DEFAULT NULL,
  `intensidad_ejercicio` varchar(100) DEFAULT NULL,
  `capturado_por` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `evaluaciones_antropometria`
--

INSERT INTO `evaluaciones_antropometria` (`id_evaluacion`, `id_atleta`, `fecha_antropometria`, `asesor`, `edad_evaluacion`, `peso_kg`, `estatura_cm`, `imc`, `clasificacion_imc`, `indice_ponderal`, `pliegue_tricipital`, `pliegue_bicipital`, `pliegue_subescapular`, `pliegue_abdominal`, `pliegue_ileocrestal`, `pliegue_supraespinal`, `pliegue_muslo`, `pliegue_pierna`, `sumatoria_pliegues`, `perimetro_brazo_relajado_der`, `perimetro_brazo_relajado_izq`, `perimetro_brazo_contraido_der`, `perimetro_brazo_contraido_izq`, `perimetro_muneca_der`, `perimetro_muneca_izq`, `perimetro_cintura_minima`, `perimetro_cadera_maxima`, `perimetro_muslo_der`, `perimetro_muslo_izq`, `perimetro_pierna_relajada_der`, `perimetro_pierna_relajada_izq`, `perimetro_pierna_contraida_der`, `perimetro_pierna_contraida_izq`, `diametro_humeral`, `diametro_femoral`, `diametro_estiloideo`, `diametro_biacromial`, `diametro_biiliocrestal`, `densidad_corporal`, `porcentaje_grasa_siri`, `masa_grasa_siri_kg`, `porcentaje_grasa_rocha`, `masa_osea_rocha_kg`, `masa_muscular_matiegka_kg`, `masa_residual_wurch_kg`, `clasificacion_grasa`, `endomorfia`, `mesomorfia`, `ectomorfia`, `indice_cintura_cadera`, `clasificacion_riesgo_cintura`, `actividad_ejercicio_actual`, `frecuencia_ejercicio`, `duracion_por_sesion`, `intensidad_ejercicio`, `capturado_por`, `created_at`) VALUES
(1, 10, '2026-06-22', 'Importado desde PDF de Historial Clínico', 85, 85.00, 170.00, 29.41, 'sobrepeso', 17.301, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-09 04:17:30'),
(2, 26, '2026-05-16', 'Importado desde PDF de Historial Clínico', 38, 99.00, 164.00, 36.81, 'obesidad_severa', 22.446, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-09 04:17:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evaluaciones_biomecanica`
--

CREATE TABLE `evaluaciones_biomecanica` (
  `id_evaluacion_biomecanica` int(10) UNSIGNED NOT NULL,
  `id_atleta` int(10) UNSIGNED NOT NULL,
  `fecha_evaluacion` date NOT NULL,
  `feet_flatten` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'El arco del pie se aplana y prona',
  `feet_turn_out` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Sentadilla con pies rotados externamente',
  `heel_rises` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'El peso se desplaza adelante y el talón se levanta',
  `knees_move_inward` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Valgo de rodilla',
  `excessive_forward_lean` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'El tronco cae hacia adelante',
  `lower_back_arches` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Hiperextensión lumbar',
  `lower_back_rounds` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Flexión lumbar / retroversión pélvica',
  `arms_fall_forward` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Los brazos caen delante de las orejas',
  `observaciones` text DEFAULT NULL,
  `evaluado_por` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evaluaciones_sft`
--

CREATE TABLE `evaluaciones_sft` (
  `id_evaluacion_sft` int(10) UNSIGNED NOT NULL,
  `id_atleta` int(10) UNSIGNED NOT NULL,
  `fecha_evaluacion` date NOT NULL,
  `edad_evaluacion` tinyint(3) UNSIGNED NOT NULL,
  `sexo` enum('masculino','femenino') NOT NULL,
  `chair_sit_reach_cm` decimal(5,2) DEFAULT NULL,
  `back_scratch_cm` decimal(5,2) DEFAULT NULL,
  `functional_reach_cm` decimal(5,2) DEFAULT NULL,
  `chair_stand_reps` tinyint(3) UNSIGNED DEFAULT NULL,
  `arm_curl_reps` tinyint(3) UNSIGNED DEFAULT NULL,
  `time_up_go_seg` decimal(4,2) DEFAULT NULL,
  `time_up_go_cognitivo_seg` decimal(4,2) DEFAULT NULL,
  `two_min_step_pasos` smallint(5) UNSIGNED DEFAULT NULL,
  `semaforo_chair_sit_reach` enum('verde','amarillo','rojo') DEFAULT NULL,
  `semaforo_back_scratch` enum('verde','amarillo','rojo') DEFAULT NULL,
  `semaforo_chair_stand` enum('verde','amarillo','rojo') DEFAULT NULL,
  `semaforo_arm_curl` enum('verde','amarillo','rojo') DEFAULT NULL,
  `semaforo_time_up_go` enum('verde','amarillo','rojo') DEFAULT NULL,
  `semaforo_two_min_step` enum('verde','amarillo','rojo') DEFAULT NULL,
  `semaforo_general` enum('verde','amarillo','rojo') DEFAULT NULL COMMENT 'Peor semáforo individual (regla de agregación conservadora)',
  `observaciones` text DEFAULT NULL,
  `evaluado_por` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `evaluaciones_sft`
--

INSERT INTO `evaluaciones_sft` (`id_evaluacion_sft`, `id_atleta`, `fecha_evaluacion`, `edad_evaluacion`, `sexo`, `chair_sit_reach_cm`, `back_scratch_cm`, `functional_reach_cm`, `chair_stand_reps`, `arm_curl_reps`, `time_up_go_seg`, `time_up_go_cognitivo_seg`, `two_min_step_pasos`, `semaforo_chair_sit_reach`, `semaforo_back_scratch`, `semaforo_chair_stand`, `semaforo_arm_curl`, `semaforo_time_up_go`, `semaforo_two_min_step`, `semaforo_general`, `observaciones`, `evaluado_por`, `created_at`) VALUES
(2, 10, '2026-06-22', 85, 'masculino', 14.00, 21.00, 19.00, 9, 15, 19.70, 21.64, 61, 'verde', 'verde', 'verde', 'verde', 'rojo', 'verde', 'rojo', 'faltó profundidad, pies hacia afuera, brazos hacia enfrente ligero', NULL, '2026-07-09 04:17:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_clinico`
--

CREATE TABLE `historial_clinico` (
  `id_historial` int(10) UNSIGNED NOT NULL,
  `id_atleta` int(10) UNSIGNED NOT NULL,
  `tipo_historial` enum('mayor_65','menor_65') NOT NULL,
  `actividades_ejercicio_actual` text DEFAULT NULL,
  `dias_ejercicio_moderado_semana` tinyint(3) UNSIGNED DEFAULT NULL,
  `objetivo_perdida_peso` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'Escala 0-10 (mayor_65) / 0-5 (menor_65), ver REGLA de escala en capa PHP',
  `objetivo_masa_muscular` tinyint(3) UNSIGNED DEFAULT NULL,
  `objetivo_rendimiento_deportivo` tinyint(3) UNSIGNED DEFAULT NULL,
  `objetivo_mejorar_salud` tinyint(3) UNSIGNED DEFAULT NULL,
  `dieta_saludable_score` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'Escala 0-10',
  `sigue_dieta_actual` text DEFAULT NULL,
  `consumo_sal` enum('bajo','medio','alto') DEFAULT NULL,
  `consumo_azucar` enum('bajo','medio','alto') DEFAULT NULL,
  `consumo_grasas` enum('bajo','medio','alto') DEFAULT NULL,
  `control_antojos_score` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'Escala 0-10, sólo mayor_65',
  `bebidas_alcoholicas_semana` smallint(5) UNSIGNED DEFAULT NULL,
  `consumo_cafeina` text DEFAULT NULL COMMENT 'Sólo mayor_65',
  `sueno_adecuado` text DEFAULT NULL,
  `nivel_estres_score` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'Escala 0-10, sólo mayor_65',
  `tecnicas_manejo_estres` text DEFAULT NULL COMMENT 'Sólo mayor_65',
  `fuma_o_vapea` text DEFAULT NULL,
  `ocupacion` varchar(150) DEFAULT NULL,
  `trabajo_sedentario_detalle` text DEFAULT NULL,
  `trabajo_movimientos_repetitivos_detalle` text DEFAULT NULL,
  `trabajo_calzado_tacon` tinyint(1) DEFAULT NULL,
  `actividad_recreativa_detalle` text DEFAULT NULL,
  `otro_pasatiempo_detalle` text DEFAULT NULL,
  `cirugias_previas` text DEFAULT NULL,
  `rehabilitacion_adecuada_autorizacion` text DEFAULT NULL,
  `condicion_cronica` text DEFAULT NULL,
  `medicamentos_actuales` text DEFAULT NULL,
  `autorizacion_medica_ejercicio` tinyint(1) DEFAULT NULL,
  `nombre_medico` varchar(150) DEFAULT NULL,
  `telefono_medico` varchar(20) DEFAULT NULL,
  `contacto_emergencia_nombre` varchar(150) DEFAULT NULL,
  `contacto_emergencia_telefono` varchar(20) DEFAULT NULL,
  `telefono_personal` varchar(20) DEFAULT NULL,
  `correo_electronico` varchar(150) DEFAULT NULL,
  `notas_adicionales` text DEFAULT NULL,
  `fecha_captura` date NOT NULL,
  `capturado_por` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `historial_clinico`
--

INSERT INTO `historial_clinico` (`id_historial`, `id_atleta`, `tipo_historial`, `actividades_ejercicio_actual`, `dias_ejercicio_moderado_semana`, `objetivo_perdida_peso`, `objetivo_masa_muscular`, `objetivo_rendimiento_deportivo`, `objetivo_mejorar_salud`, `dieta_saludable_score`, `sigue_dieta_actual`, `consumo_sal`, `consumo_azucar`, `consumo_grasas`, `control_antojos_score`, `bebidas_alcoholicas_semana`, `consumo_cafeina`, `sueno_adecuado`, `nivel_estres_score`, `tecnicas_manejo_estres`, `fuma_o_vapea`, `ocupacion`, `trabajo_sedentario_detalle`, `trabajo_movimientos_repetitivos_detalle`, `trabajo_calzado_tacon`, `actividad_recreativa_detalle`, `otro_pasatiempo_detalle`, `cirugias_previas`, `rehabilitacion_adecuada_autorizacion`, `condicion_cronica`, `medicamentos_actuales`, `autorizacion_medica_ejercicio`, `nombre_medico`, `telefono_medico`, `contacto_emergencia_nombre`, `contacto_emergencia_telefono`, `telefono_personal`, `correo_electronico`, `notas_adicionales`, `fecha_captura`, `capturado_por`, `created_at`, `updated_at`) VALUES
(2, 10, 'mayor_65', '2 años sin hacer ejercicio', 0, 0, 5, 0, 5, 8, 'no', 'medio', 'bajo', 'bajo', 1, 2, 'café, 1 taza al día', 'Si', 0, NULL, 'no', 'retirado', NULL, NULL, NULL, 'no', 'domino', 'Lesiones previas: No\nCirugías previas: porstata, apendice,', NULL, 'No', 'Dilatrend,_Idaptan, icoplavix. estatina', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-22', NULL, '2026-07-09 04:17:30', NULL),
(3, 26, 'menor_65', 'Nada', 0, 4, 5, 2, 5, 10, 'Si, hipertrigliceridemia y resistencia a la insulina', NULL, NULL, NULL, NULL, NULL, NULL, 'a veces si descansada normalmente más cansada', NULL, NULL, 'No', NULL, NULL, NULL, NULL, NULL, NULL, 'Lesiones previas: No, dolor de hombro derecho cuando hacemos flexión\nCirugías previas: No', NULL, NULL, 'Si, medicamentos para hipertrigliceridemia y resistencia a insulina', NULL, NULL, NULL, NULL, NULL, '5513554840', 'ivone.crs@gmail.com', 'No podemos trabajar con alta intensidad ahorita por su enfermedad', '2026-05-16', NULL, '2026-07-09 04:17:40', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `leads_prospectos`
--

CREATE TABLE `leads_prospectos` (
  `id_lead` int(10) UNSIGNED NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `canal_origen` enum('whatsapp','instagram','facebook') NOT NULL,
  `perfil_detectado` enum('atleta_competitivo','rehabilitacion','composicion_corporal','sin_clasificar') NOT NULL DEFAULT 'sin_clasificar',
  `objetivo_declarado` text DEFAULT NULL,
  `consent_gate_status` enum('pendiente','aceptado','rechazado') NOT NULL DEFAULT 'pendiente',
  `consent_timestamp` datetime DEFAULT NULL,
  `nlp_entidades_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`nlp_entidades_json`)),
  `confianza_nlp` decimal(3,2) DEFAULT NULL,
  `estatus_lead` enum('nuevo','en_conversacion','agendado','convertido','descartado') NOT NULL DEFAULT 'nuevo',
  `churn_score` decimal(3,2) DEFAULT NULL,
  `fecha_captura` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `membresias`
--

CREATE TABLE `membresias` (
  `id_membresia` int(10) UNSIGNED NOT NULL,
  `id_atleta` int(10) UNSIGNED NOT NULL,
  `id_servicio` int(10) UNSIGNED NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL COMMENT 'NULL para membresías tipo sesion_unica sin vigencia calendario',
  `sesiones_totales` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sesiones_restantes` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `precio_pagado` decimal(10,2) NOT NULL,
  `estatus` enum('activa','agotada','vencida','cancelada') NOT NULL DEFAULT 'activa',
  `notas` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `membresias`
--

INSERT INTO `membresias` (`id_membresia`, `id_atleta`, `id_servicio`, `fecha_inicio`, `fecha_fin`, `sesiones_totales`, `sesiones_restantes`, `precio_pagado`, `estatus`, `notas`, `created_at`, `updated_at`) VALUES
(8, 8, 8, '2026-05-04', NULL, 12, 12, 3500.00, 'activa', 'Migrado desde Clientes.xlsx', '2026-07-08 20:12:25', NULL),
(9, 9, 9, '2026-05-04', NULL, 12, 12, 3000.00, 'activa', 'Migrado desde Clientes.xlsx', '2026-07-08 20:12:26', NULL),
(10, 10, 8, '2026-05-04', NULL, 12, 12, 3400.00, 'activa', 'Migrado desde Clientes.xlsx', '2026-07-08 20:12:27', NULL),
(11, 11, 10, '2026-05-04', NULL, 1, 1, 500.00, 'activa', 'Migrado desde Clientes.xlsx', '2026-07-08 20:12:28', NULL),
(12, 12, 10, '2026-05-04', NULL, 1, 1, 500.00, 'activa', 'Migrado desde Clientes.xlsx', '2026-07-08 20:12:29', NULL),
(13, 13, 10, '2026-05-04', NULL, 1, 1, 500.00, 'activa', 'Migrado desde Clientes.xlsx', '2026-07-08 20:12:30', NULL),
(14, 14, 11, '2026-05-06', NULL, 8, 8, 1150.00, 'activa', 'Migrado desde Clientes.xlsx', '2026-07-08 20:12:31', NULL),
(15, 15, 8, '2026-05-07', NULL, 12, 12, 3650.00, 'activa', 'Migrado desde Clientes.xlsx', '2026-07-08 20:12:31', NULL),
(16, 16, 12, '2026-05-07', NULL, 8, 8, 1400.00, 'activa', 'Migrado desde Clientes.xlsx', '2026-07-08 20:12:32', NULL),
(17, 17, 10, '2026-05-11', NULL, 1, 1, 1250.00, 'activa', 'Migrado desde Clientes.xlsx', '2026-07-08 20:12:33', NULL),
(18, 18, 13, '2026-06-03', NULL, 12, 12, 3450.00, 'activa', 'Migrado desde Clientes.xlsx', '2026-07-08 20:12:34', NULL),
(19, 19, 14, '2026-06-03', NULL, 12, 12, 3450.00, 'activa', 'Migrado desde Clientes.xlsx', '2026-07-08 20:12:35', NULL),
(20, 20, 15, '2026-06-04', NULL, 2, 2, 500.00, 'activa', 'Migrado desde Clientes.xlsx', '2026-07-08 20:12:36', NULL),
(21, 21, 16, '2026-06-04', NULL, 8, 8, 1087.00, 'activa', 'Migrado desde Clientes.xlsx', '2026-07-08 20:12:37', NULL),
(22, 22, 17, '2026-06-04', NULL, 8, 8, 1550.00, 'activa', 'Migrado desde Clientes.xlsx', '2026-07-08 20:12:38', NULL),
(23, 11, 10, '2026-06-04', NULL, 1, 1, 500.00, 'activa', 'Migrado desde Clientes.xlsx', '2026-07-08 20:12:39', NULL),
(24, 13, 10, '2026-06-04', NULL, 1, 1, 500.00, 'activa', 'Migrado desde Clientes.xlsx', '2026-07-08 20:12:40', NULL),
(25, 12, 10, '2026-06-04', NULL, 1, 1, 500.00, 'activa', 'Migrado desde Clientes.xlsx', '2026-07-08 20:12:40', NULL),
(26, 17, 10, '2026-06-04', NULL, 1, 1, 500.00, 'activa', 'Migrado desde Clientes.xlsx', '2026-07-08 20:12:41', NULL),
(27, 23, 16, '2026-06-05', NULL, 8, 8, 1250.00, 'activa', 'Migrado desde Clientes.xlsx', '2026-07-08 20:12:42', NULL),
(28, 24, 17, '2026-06-05', NULL, 8, 8, 1250.00, 'activa', 'Migrado desde Clientes.xlsx', '2026-07-08 20:12:42', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_asistencia`
--

CREATE TABLE `pagos_asistencia` (
  `id_pago` int(10) UNSIGNED NOT NULL,
  `id_atleta` int(10) UNSIGNED NOT NULL,
  `id_membresia` int(10) UNSIGNED DEFAULT NULL,
  `concepto_pago` varchar(200) NOT NULL COMMENT 'Ej. "Promo performance 12 sesiones" (columna Programa del Excel legacy)',
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` enum('efectivo','tarjeta','transferencia','otro') NOT NULL DEFAULT 'efectivo',
  `fecha_pago` date NOT NULL,
  `registrado_por` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pagos_asistencia`
--

INSERT INTO `pagos_asistencia` (`id_pago`, `id_atleta`, `id_membresia`, `concepto_pago`, `monto`, `metodo_pago`, `fecha_pago`, `registrado_por`, `created_at`) VALUES
(8, 8, 8, 'Promo performance 12 sesiones', 3500.00, 'efectivo', '2026-05-04', NULL, '2026-07-08 20:12:25'),
(9, 9, 9, 'Promo especial performance 12', 3000.00, 'efectivo', '2026-05-04', NULL, '2026-07-08 20:12:26'),
(10, 10, 10, 'Promo performance 12 sesiones', 3400.00, 'efectivo', '2026-05-04', NULL, '2026-07-08 20:12:27'),
(11, 11, 11, 'Promo familia especial', 500.00, 'efectivo', '2026-05-04', NULL, '2026-07-08 20:12:28'),
(12, 12, 12, 'Promo familia especial', 500.00, 'efectivo', '2026-05-04', NULL, '2026-07-08 20:12:29'),
(13, 13, 13, 'Promo familia especial', 500.00, 'efectivo', '2026-05-04', NULL, '2026-07-08 20:12:30'),
(14, 14, 14, 'Promo kids 8', 1150.00, 'efectivo', '2026-05-06', NULL, '2026-07-08 20:12:31'),
(15, 15, 15, 'Promo performance 12 sesiones', 3650.00, 'efectivo', '2026-05-07', NULL, '2026-07-08 20:12:32'),
(16, 16, 16, 'Funcional 8', 1400.00, 'efectivo', '2026-05-07', NULL, '2026-07-08 20:12:33'),
(17, 17, 17, 'Promo familia especial', 1250.00, 'efectivo', '2026-05-11', NULL, '2026-07-08 20:12:33'),
(18, 18, 18, 'Senior 12 sesiones', 3450.00, 'efectivo', '2026-06-03', NULL, '2026-07-08 20:12:35'),
(19, 19, 19, 'Performance 12 sesiones', 3450.00, 'efectivo', '2026-06-03', NULL, '2026-07-08 20:12:36'),
(20, 20, 20, '2 sesiones funcional', 500.00, 'efectivo', '2026-06-04', NULL, '2026-07-08 20:12:37'),
(21, 21, 21, 'kids 8 sesiones familiar 25%', 1087.00, 'efectivo', '2026-06-04', NULL, '2026-07-08 20:12:38'),
(22, 22, 22, 'Funcional 8 sesiones familiar 25%', 1550.00, 'efectivo', '2026-06-04', NULL, '2026-07-08 20:12:39'),
(23, 11, 23, 'Promo familia especial', 500.00, 'efectivo', '2026-06-04', NULL, '2026-07-08 20:12:39'),
(24, 13, 24, 'Promo familia especial', 500.00, 'efectivo', '2026-06-04', NULL, '2026-07-08 20:12:40'),
(25, 12, 25, 'Promo familia especial', 500.00, 'efectivo', '2026-06-04', NULL, '2026-07-08 20:12:40'),
(26, 17, 26, 'Promo familia especial', 500.00, 'efectivo', '2026-06-04', NULL, '2026-07-08 20:12:41'),
(27, 23, 27, 'kids 8 sesiones familiar 25%', 1250.00, 'efectivo', '2026-06-05', NULL, '2026-07-08 20:12:42'),
(28, 24, 28, 'Funcional 8 sesiones familiar 25%', 1250.00, 'efectivo', '2026-06-05', NULL, '2026-07-08 20:12:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `percentiles_sft_referencia`
--

CREATE TABLE `percentiles_sft_referencia` (
  `id_percentil` smallint(5) UNSIGNED NOT NULL,
  `sexo` enum('masculino','femenino') NOT NULL,
  `edad_min` tinyint(3) UNSIGNED NOT NULL,
  `edad_max` tinyint(3) UNSIGNED NOT NULL,
  `variable` enum('chair_sit_reach','back_scratch','chair_stand','arm_curl','time_up_go','two_min_step') NOT NULL,
  `valor_min` decimal(6,2) NOT NULL,
  `valor_max` decimal(6,2) NOT NULL,
  `unidad` varchar(20) NOT NULL COMMENT 'cm, reps, segundos, pasos'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `percentiles_sft_referencia`
--

INSERT INTO `percentiles_sft_referencia` (`id_percentil`, `sexo`, `edad_min`, `edad_max`, `variable`, `valor_min`, `valor_max`, `unidad`) VALUES
(1, 'masculino', 60, 64, 'chair_sit_reach', -6.35, 10.16, 'cm'),
(2, 'masculino', 65, 69, 'chair_sit_reach', -7.62, 7.62, 'cm'),
(3, 'masculino', 70, 74, 'chair_sit_reach', -8.89, 6.35, 'cm'),
(4, 'masculino', 75, 79, 'chair_sit_reach', -10.16, 5.08, 'cm'),
(5, 'masculino', 80, 84, 'chair_sit_reach', -13.97, 3.81, 'cm'),
(6, 'masculino', 85, 89, 'chair_sit_reach', -13.97, 1.27, 'cm'),
(7, 'masculino', 90, 94, 'chair_sit_reach', -16.51, -1.27, 'cm'),
(8, 'masculino', 60, 64, 'back_scratch', -16.51, 0.00, 'cm'),
(9, 'masculino', 65, 69, 'back_scratch', -19.05, -2.54, 'cm'),
(10, 'masculino', 70, 74, 'back_scratch', -20.34, -2.54, 'cm'),
(11, 'masculino', 75, 79, 'back_scratch', -22.86, -5.08, 'cm'),
(12, 'masculino', 80, 84, 'back_scratch', -24.13, -5.08, 'cm'),
(13, 'masculino', 85, 89, 'back_scratch', -25.40, -7.62, 'cm'),
(14, 'masculino', 90, 94, 'back_scratch', -26.67, -10.16, 'cm'),
(15, 'masculino', 60, 64, 'chair_stand', 14.00, 19.00, 'reps'),
(16, 'masculino', 65, 69, 'chair_stand', 12.00, 18.00, 'reps'),
(17, 'masculino', 70, 74, 'chair_stand', 12.00, 17.00, 'reps'),
(18, 'masculino', 75, 79, 'chair_stand', 11.00, 17.00, 'reps'),
(19, 'masculino', 80, 84, 'chair_stand', 10.00, 15.00, 'reps'),
(20, 'masculino', 85, 89, 'chair_stand', 8.00, 14.00, 'reps'),
(21, 'masculino', 90, 94, 'chair_stand', 7.00, 12.00, 'reps'),
(22, 'masculino', 60, 64, 'arm_curl', 16.00, 22.00, 'reps'),
(23, 'masculino', 65, 69, 'arm_curl', 15.00, 21.00, 'reps'),
(24, 'masculino', 70, 74, 'arm_curl', 14.00, 21.00, 'reps'),
(25, 'masculino', 75, 79, 'arm_curl', 13.00, 19.00, 'reps'),
(26, 'masculino', 80, 84, 'arm_curl', 13.00, 19.00, 'reps'),
(27, 'masculino', 85, 89, 'arm_curl', 11.00, 17.00, 'reps'),
(28, 'masculino', 90, 94, 'arm_curl', 10.00, 14.00, 'reps'),
(29, 'masculino', 60, 64, 'time_up_go', 3.80, 5.60, 'segundos'),
(30, 'masculino', 65, 69, 'time_up_go', 4.30, 5.70, 'segundos'),
(31, 'masculino', 70, 74, 'time_up_go', 4.20, 6.40, 'segundos'),
(32, 'masculino', 75, 79, 'time_up_go', 4.60, 7.20, 'segundos'),
(33, 'masculino', 80, 84, 'time_up_go', 5.20, 7.60, 'segundos'),
(34, 'masculino', 85, 89, 'time_up_go', 5.30, 8.90, 'segundos'),
(35, 'masculino', 90, 94, 'time_up_go', 6.20, 10.00, 'segundos'),
(36, 'masculino', 60, 64, 'two_min_step', 87.00, 115.00, 'pasos'),
(37, 'masculino', 65, 69, 'two_min_step', 86.00, 116.00, 'pasos'),
(38, 'masculino', 70, 74, 'two_min_step', 80.00, 110.00, 'pasos'),
(39, 'masculino', 75, 79, 'two_min_step', 73.00, 109.00, 'pasos'),
(40, 'masculino', 80, 84, 'two_min_step', 71.00, 103.00, 'pasos'),
(41, 'masculino', 85, 89, 'two_min_step', 59.00, 91.00, 'pasos'),
(42, 'masculino', 90, 94, 'two_min_step', 52.00, 86.00, 'pasos'),
(43, 'femenino', 60, 64, 'chair_sit_reach', -1.27, 12.70, 'cm'),
(44, 'femenino', 65, 69, 'chair_sit_reach', -1.27, 11.43, 'cm'),
(45, 'femenino', 70, 74, 'chair_sit_reach', -2.54, 10.16, 'cm'),
(46, 'femenino', 75, 79, 'chair_sit_reach', -3.81, 8.89, 'cm'),
(47, 'femenino', 80, 84, 'chair_sit_reach', -5.08, 7.62, 'cm'),
(48, 'femenino', 85, 89, 'chair_sit_reach', -6.35, 6.35, 'cm'),
(49, 'femenino', 90, 94, 'chair_sit_reach', -11.43, 2.54, 'cm'),
(50, 'femenino', 60, 64, 'back_scratch', -7.62, 3.81, 'cm'),
(51, 'femenino', 65, 69, 'back_scratch', -8.89, 3.81, 'cm'),
(52, 'femenino', 70, 74, 'back_scratch', -10.16, 2.54, 'cm'),
(53, 'femenino', 75, 79, 'back_scratch', -12.70, 1.27, 'cm'),
(54, 'femenino', 80, 84, 'back_scratch', -13.97, 0.00, 'cm'),
(55, 'femenino', 85, 89, 'back_scratch', -17.78, -1.27, 'cm'),
(56, 'femenino', 90, 94, 'back_scratch', -20.32, -2.54, 'cm'),
(57, 'femenino', 60, 64, 'chair_stand', 12.00, 17.00, 'reps'),
(58, 'femenino', 65, 69, 'chair_stand', 11.00, 16.00, 'reps'),
(59, 'femenino', 70, 74, 'chair_stand', 10.00, 15.00, 'reps'),
(60, 'femenino', 75, 79, 'chair_stand', 10.00, 15.00, 'reps'),
(61, 'femenino', 80, 84, 'chair_stand', 9.00, 14.00, 'reps'),
(62, 'femenino', 85, 89, 'chair_stand', 8.00, 13.00, 'reps'),
(63, 'femenino', 90, 94, 'chair_stand', 4.00, 11.00, 'reps'),
(64, 'femenino', 60, 64, 'arm_curl', 13.00, 19.00, 'reps'),
(65, 'femenino', 65, 69, 'arm_curl', 12.00, 18.00, 'reps'),
(66, 'femenino', 70, 74, 'arm_curl', 12.00, 17.00, 'reps'),
(67, 'femenino', 75, 79, 'arm_curl', 11.00, 17.00, 'reps'),
(68, 'femenino', 80, 84, 'arm_curl', 10.00, 16.00, 'reps'),
(69, 'femenino', 85, 89, 'arm_curl', 10.00, 15.00, 'reps'),
(70, 'femenino', 90, 94, 'arm_curl', 8.00, 13.00, 'reps'),
(71, 'femenino', 60, 64, 'time_up_go', 4.40, 6.00, 'segundos'),
(72, 'femenino', 65, 69, 'time_up_go', 4.80, 6.40, 'segundos'),
(73, 'femenino', 70, 74, 'time_up_go', 4.90, 7.10, 'segundos'),
(74, 'femenino', 75, 79, 'time_up_go', 5.20, 7.40, 'segundos'),
(75, 'femenino', 80, 84, 'time_up_go', 5.70, 8.70, 'segundos'),
(76, 'femenino', 85, 89, 'time_up_go', 6.20, 9.60, 'segundos'),
(77, 'femenino', 90, 94, 'time_up_go', 7.00, 11.50, 'segundos'),
(78, 'femenino', 60, 64, 'two_min_step', 75.00, 107.00, 'pasos'),
(79, 'femenino', 65, 69, 'two_min_step', 73.00, 107.00, 'pasos'),
(80, 'femenino', 70, 74, 'two_min_step', 68.00, 101.00, 'pasos'),
(81, 'femenino', 75, 79, 'two_min_step', 68.00, 100.00, 'pasos'),
(82, 'femenino', 80, 84, 'two_min_step', 60.00, 91.00, 'pasos'),
(83, 'femenino', 85, 89, 'two_min_step', 55.00, 85.00, 'pasos'),
(84, 'femenino', 90, 94, 'two_min_step', 44.00, 72.00, 'pasos');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `id_permiso` smallint(5) UNSIGNED NOT NULL,
  `clave_permiso` varchar(100) NOT NULL,
  `modulo` varchar(50) NOT NULL,
  `descripcion` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `permisos`
--

INSERT INTO `permisos` (`id_permiso`, `clave_permiso`, `modulo`, `descripcion`) VALUES
(1, 'sistema.configurar_ia', 'sistema', 'Configurar credenciales API y motor cognitivo de IA'),
(2, 'sistema.ver_auditoria', 'sistema', 'Ver logs de auditoría de seguridad y sesiones'),
(3, 'sistema.gestionar_usuarios', 'sistema', 'Crear, editar y desactivar usuarios y roles'),
(4, 'clientes.ver', 'clientes', 'Ver ficha de clientes/atletas'),
(5, 'clientes.editar', 'clientes', 'Editar ficha de clientes/atletas'),
(6, 'clientes.ver_financiero', 'clientes', 'Ver saldos, cobros y datos financieros de clientes'),
(7, 'agenda.ver', 'agenda', 'Ver agenda de citas'),
(8, 'agenda.gestionar', 'agenda', 'Crear, mover y cancelar citas (máx. 4 personas/hora)'),
(9, 'evaluaciones.capturar', 'evaluaciones', 'Capturar antropometría, SFT y biomecánica (Pie de Cancha)'),
(10, 'evaluaciones.ver_todas', 'evaluaciones', 'Ver evaluaciones históricas de todos los atletas'),
(11, 'sesiones.capturar', 'sesiones', 'Registrar sesión de entrenamiento y RPE/cargas'),
(12, 'cobranza.gestionar', 'cobranza', 'Registrar pagos y gestionar membresías/paquetes');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planes_macrociclo`
--

CREATE TABLE `planes_macrociclo` (
  `id_macro` int(10) UNSIGNED NOT NULL,
  `id_atleta` int(10) UNSIGNED NOT NULL,
  `temporada` varchar(100) DEFAULT NULL,
  `mesociclo` enum('prep_general','prep_especifica','competitiva','transitorio') NOT NULL,
  `mes` enum('enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre') NOT NULL,
  `tipo_microciclo` enum('ajuste','activacion','carga','competicion','impacto','recuperacion') DEFAULT NULL,
  `volumen` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'Escala 0-10 de énfasis del atributo en el periodo',
  `velocidad` tinyint(3) UNSIGNED DEFAULT NULL,
  `fuerza` tinyint(3) UNSIGNED DEFAULT NULL,
  `resistencia` tinyint(3) UNSIGNED DEFAULT NULL,
  `flexibilidad` tinyint(3) UNSIGNED DEFAULT NULL,
  `tecnica` tinyint(3) UNSIGNED DEFAULT NULL,
  `agilidad` tinyint(3) UNSIGNED DEFAULT NULL,
  `total_horas` decimal(5,2) DEFAULT NULL,
  `dias_microciclo` tinyint(3) UNSIGNED DEFAULT NULL,
  `creado_por` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` tinyint(3) UNSIGNED NOT NULL,
  `clave_rol` enum('super_admin','admin','coach','atleta') NOT NULL,
  `nombre_rol` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `clave_rol`, `nombre_rol`, `descripcion`, `activo`, `created_at`) VALUES
(1, 'super_admin', 'Super Admin (AXON_DCD)', 'Control absoluto de base de datos, credenciales API, logs de auditoría y configuración del motor cognitivo de IA.', 1, '2026-07-08 01:52:48'),
(2, 'admin', 'Admin (Recepción / FrontDesk)', 'Gestión comercial de clientes, agenda, cobros, catálogo de paquetes/membresías y alertas de vencimiento. Sin acceso a configuración del motor IA.', 1, '2026-07-08 01:52:48'),
(3, 'coach', 'Coach / Especialista', 'Interfaz Pie de Cancha: captura táctil de antropometría, SFT, biomecánica y sesiones. Aislado de datos financieros y cobros globales.', 1, '2026-07-08 01:52:48'),
(4, 'atleta', 'Portal del Cliente', 'Acceso de sólo lectura a sus propias citas, con la facultad de cancelarlas hasta 3 horas antes.', 1, '2026-07-10 16:17:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol_permisos`
--

CREATE TABLE `rol_permisos` (
  `id_rol` tinyint(3) UNSIGNED NOT NULL,
  `id_permiso` smallint(5) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `rol_permisos`
--

INSERT INTO `rol_permisos` (`id_rol`, `id_permiso`) VALUES
(1, 1),
(1, 2),
(2, 2),
(1, 3),
(1, 4),
(2, 4),
(3, 4),
(1, 5),
(2, 5),
(1, 6),
(2, 6),
(1, 7),
(2, 7),
(3, 7),
(1, 8),
(2, 8),
(1, 9),
(2, 9),
(3, 9),
(1, 10),
(2, 10),
(3, 10),
(1, 11),
(2, 11),
(3, 11),
(1, 12),
(2, 12);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones_entrenamiento`
--

CREATE TABLE `sesiones_entrenamiento` (
  `id_sesion` int(10) UNSIGNED NOT NULL,
  `id_atleta` int(10) UNSIGNED NOT NULL,
  `id_cita` int(10) UNSIGNED DEFAULT NULL,
  `id_staff` int(10) UNSIGNED NOT NULL,
  `id_macro` int(10) UNSIGNED DEFAULT NULL,
  `fecha_sesion` date NOT NULL,
  `numero_sesion` smallint(5) UNSIGNED DEFAULT NULL COMMENT 'Consecutivo de sesión dentro del microciclo/paquete',
  `enfoque` varchar(150) DEFAULT NULL,
  `fase` enum('prep_general','prep_especifica','competitiva','transitorio') DEFAULT NULL,
  `rpe_sesion` decimal(3,1) UNSIGNED DEFAULT NULL COMMENT 'Escala 1-10 (Pie de Cancha slider)',
  `notas_entrenador` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones_log`
--

CREATE TABLE `sesiones_log` (
  `id_log_sesion` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL si el intento de login falló antes de resolver el usuario (email inexistente)',
  `email_intento` varchar(150) NOT NULL,
  `tipo_evento` enum('login_exitoso','login_fallido','logout','bloqueo_temporal','cambio_password','token_csrf_invalido') NOT NULL,
  `ip_origen` varchar(45) NOT NULL COMMENT 'Soporta IPv4 e IPv6',
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sesiones_log`
--

INSERT INTO `sesiones_log` (`id_log_sesion`, `id_usuario`, `email_intento`, `tipo_evento`, `ip_origen`, `user_agent`, `created_at`) VALUES
(3, NULL, 'direccion.test@athlos.local', 'login_fallido', '::1', 'curl/8.17.0', '2026-07-08 19:12:17'),
(4, NULL, 'direccion.test@athlos.local', 'login_fallido', '::1', 'curl/8.17.0', '2026-07-08 19:12:55'),
(5, NULL, 'direccion.test@athlos.local', 'login_fallido', '::1', 'curl/8.17.0', '2026-07-08 19:13:41'),
(6, NULL, 'dacadomx@gmail.com', 'login_fallido', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-08 23:11:51'),
(7, NULL, 'dacadomx@gmail.com', 'login_fallido', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-08 23:12:00'),
(8, NULL, 'dacadomx@gmail.com', 'login_fallido', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-08 23:40:30'),
(9, NULL, 'dacadomx@gmail.com', 'login_fallido', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-08 23:41:45'),
(10, NULL, 'dacadomx@gmail.com', 'login_fallido', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-08 23:41:58'),
(11, NULL, 'dacadomx@gmail.com', 'login_fallido', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-08 23:42:04'),
(12, NULL, 'dacadomx@gmail.com', 'login_fallido', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-08 23:43:31'),
(13, 2, 'dacadomx@gmail.com', 'cambio_password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-08 23:47:00'),
(14, 2, 'dacadomx@gmail.com', 'login_exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-08 23:47:13'),
(15, 2, 'David Cabrera', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-09 16:52:34'),
(16, 2, 'dacadomx@gmail.com', 'login_exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-09 16:52:44'),
(17, 2, 'David Cabrera', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-09 17:17:35'),
(18, 3, 'dacadomx@yahoo.com', 'login_exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-09 17:17:48'),
(19, 3, 'Luis Moctezuma', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-09 18:03:27'),
(20, 2, 'dacadomx@gmail.com', 'login_exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-09 18:03:37'),
(21, 2, 'dacadomx@gmail.com', 'login_exitoso', '187.223.133.236', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-13 20:25:10'),
(22, 2, 'David Cabrera', 'logout', '187.223.133.236', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-13 21:27:15'),
(23, 5, 'athlos.performance01@gmail.com', 'login_exitoso', '187.223.133.236', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-13 21:27:26'),
(24, 5, 'Admin Athlos Performance', 'logout', '187.223.133.236', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-13 21:27:53'),
(25, 5, 'athlos.performance01@gmail.com', 'login_exitoso', '187.223.133.236', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-13 21:37:50'),
(26, 5, 'athlos.performance01@gmail.com', 'login_exitoso', '187.194.10.254', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-21 01:28:38'),
(27, 5, 'athlos.performance01@gmail.com', 'login_exitoso', '187.194.30.27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-11 18:23:34'),
(28, 6, 'ber.lobo.b01@gmail.com', 'login_exitoso', '187.194.30.27', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-11 18:29:18'),
(29, 5, 'athlos.performance01@gmail.com', 'login_exitoso', '187.194.30.27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-11 20:36:08'),
(30, 5, 'athlos.performance01@gmail.com', 'login_exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 20:52:47'),
(31, 5, 'athlos.performance01@gmail.com', 'login_exitoso', '187.223.227.110', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 20:53:55'),
(32, 5, 'athlos.performance01@gmail.com', 'login_exitoso', '187.194.30.27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 21:05:29'),
(33, 5, 'athlos.performance01@gmail.com', 'login_exitoso', '187.194.30.27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 21:12:01'),
(34, 5, 'athlos.performance01@gmail.com', 'login_exitoso', '187.194.30.27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 00:06:40'),
(35, 2, 'dacadomx@gmail.com', 'login_exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 21:10:14'),
(36, 2, 'David Cabrera', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 22:07:23'),
(37, NULL, 'davidcabrera@mediahubbcs.com', 'login_fallido', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 22:16:48'),
(38, 2, 'dacadomx@gmail.com', 'login_fallido', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 22:17:01'),
(39, 2, 'dacadomx@gmail.com', 'login_exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 22:17:18'),
(40, 5, 'athlos.performance01@gmail.com', 'login_exitoso', '187.223.227.110', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 04:16:56'),
(41, 5, 'athlos.performance01@gmail.com', 'login_exitoso', '187.194.30.27', 'Mozilla/5.0 (iPad; CPU OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.6 Mobile/15E148 Safari/604.1', '2026-08-14 17:05:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sincronizacion_tokens`
--

CREATE TABLE `sincronizacion_tokens` (
  `id_token` int(10) UNSIGNED NOT NULL,
  `id_staff` int(10) UNSIGNED NOT NULL,
  `proveedor` enum('google_calendar','apple_calendar') NOT NULL,
  `access_token` text DEFAULT NULL COMMENT 'Sólo Google — cifrado en capa de aplicación',
  `refresh_token` text DEFAULT NULL COMMENT 'Sólo Google',
  `token_expira` datetime DEFAULT NULL COMMENT 'Sólo Google',
  `calendario_externo_id` varchar(255) DEFAULT NULL COMMENT 'Sólo Google — calendarId',
  `webhook_channel_id` varchar(255) DEFAULT NULL COMMENT 'Sólo Google — canal de notificaciones push, expira máx. 30 días',
  `webhook_channel_expira` datetime DEFAULT NULL,
  `webcal_uid` varchar(64) DEFAULT NULL COMMENT 'Sólo Apple/webcal — token opaco impredecible en la URL pública del feed .ics',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sincronizacion_tokens`
--

INSERT INTO `sincronizacion_tokens` (`id_token`, `id_staff`, `proveedor`, `access_token`, `refresh_token`, `token_expira`, `calendario_externo_id`, `webhook_channel_id`, `webhook_channel_expira`, `webcal_uid`, `activo`, `created_at`, `updated_at`) VALUES
(1, 1, 'apple_calendar', NULL, NULL, NULL, NULL, NULL, NULL, '5ad1bdbb8942312a4464be35eafc966cf6a89ff856ad247c33e35a31fa3a42c9', 1, '2026-07-09 16:38:31', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `staff`
--

CREATE TABLE `staff` (
  `id_staff` int(10) UNSIGNED NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `especialidad` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `staff`
--

INSERT INTO `staff` (`id_staff`, `nombre_completo`, `especialidad`, `telefono`, `email`, `activo`, `created_at`) VALUES
(1, 'Coach de Prueba (QA Calendario)', 'Prueba de Sistema', NULL, 'coach.prueba.calendario@athlos.local', 0, '2026-07-09 16:37:30'),
(2, 'Luis Moctezuma', 'Fuerza', NULL, 'dacadomx@yahoo.com', 1, '2026-07-09 17:15:04'),
(3, 'Bernardo Lobo Barrera', 'Performance y adultos mayores', NULL, 'ber.lobo.b01@gmail.com', 1, '2026-08-11 18:27:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `staff_colores`
--

CREATE TABLE `staff_colores` (
  `id_staff` int(10) UNSIGNED NOT NULL,
  `color_hex` char(7) NOT NULL COMMENT 'Formato #RRGGBB',
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `staff_colores`
--

INSERT INTO `staff_colores` (`id_staff`, `color_hex`, `updated_at`) VALUES
(1, '#00B8C9', NULL),
(2, '#C0392B', NULL),
(3, '#8E44AD', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `testimonios_clientes`
--

CREATE TABLE `testimonios_clientes` (
  `id_testimonio` int(10) UNSIGNED NOT NULL,
  `nombre_cliente` varchar(150) NOT NULL,
  `comentario` text NOT NULL,
  `foto_ruta` varchar(255) DEFAULT NULL COMMENT 'Ruta relativa pública, ej. media/testimonios/<hash>.webp',
  `fecha_testimonio` date NOT NULL,
  `estatus` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `orden_visualizacion` smallint(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Orden manual en el carrusel público (ascendente); empate resuelto por fecha_testimonio DESC',
  `created_by` int(10) UNSIGNED DEFAULT NULL COMMENT 'Staff que capturó la reseña',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `testimonios_clientes`
--

INSERT INTO `testimonios_clientes` (`id_testimonio`, `nombre_cliente`, `comentario`, `foto_ruta`, `fecha_testimonio`, `estatus`, `orden_visualizacion`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Fernanda Higuera', 'Llegué con una lesión de rodilla que arrastraba meses y en Athlos me hicieron una evaluación real, con datos, no solo estiramientos. Hoy entreno sin dolor y entiendo por qué cada ejercicio está en mi plan.', 'testimonios/e4abca52d5b9a8f7a12bd15d962191ab.webp', '2026-05-12', 'activo', 1, NULL, '2026-08-12 22:03:13', '2026-08-13 00:24:50'),
(2, 'Jorge Amador', 'La diferencia está en la ciencia detrás de cada sesión: antropometría, fuerza, movilidad. Es el único lugar en La Paz donde sentí que mi entrenamiento estaba diseñado para mí y no copiado de una plantilla.', NULL, '2026-06-03', 'activo', 2, NULL, '2026-08-12 22:03:13', NULL),
(3, 'Cecilia Rendón', 'A mis 63 años pensé que ya no podía mejorar mi condición física. El equipo de Athlos me demostró lo contrario con un programa seguro, medido y adaptado a mi historial clínico.', NULL, '2026-06-21', 'activo', 3, NULL, '2026-08-12 22:03:13', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `id_rol` tinyint(3) UNSIGNED NOT NULL,
  `id_staff` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK lógica -> staff.id_staff (staff se crea en 04_schema_agenda_sesiones.sql; FK se agrega en ese script)',
  `id_atleta` int(10) UNSIGNED DEFAULT NULL COMMENT 'Sólo rol atleta — vínculo al expediente del cliente',
  `nombre_completo` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL COMMENT 'Generado con password_hash() PHP (bcrypt/argon2), nunca texto plano',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `requiere_cambio_password` tinyint(1) NOT NULL DEFAULT 1,
  `ultimo_login` datetime DEFAULT NULL,
  `intentos_fallidos` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `bloqueado_hasta` datetime DEFAULT NULL COMMENT 'Bloqueo temporal tras exceder intentos fallidos',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `id_rol`, `id_staff`, `id_atleta`, `nombre_completo`, `email`, `password_hash`, `activo`, `requiere_cambio_password`, `ultimo_login`, `intentos_fallidos`, `bloqueado_hasta`, `created_at`, `updated_at`) VALUES
(2, 1, NULL, NULL, 'David Cabrera', 'dacadomx@gmail.com', '$2y$10$AMT/bD05KbBnZy1QH7i4luEsI4XoCdQHZBLYtLJvsreSOV9bFdM2e', 1, 0, '2026-08-12 22:17:18', 0, NULL, '2026-07-08 23:47:00', '2026-08-12 22:17:18'),
(3, 2, 2, NULL, 'Luis Moctezuma', 'dacadomx@yahoo.com', '$2y$10$uo4/BSIwcJ8Bd.608JOESe8tbn.0mfzTnrN2uvkjjGHTCBgoHfc4q', 1, 1, '2026-07-09 17:17:48', 0, NULL, '2026-07-09 17:15:04', '2026-08-12 23:56:13'),
(5, 2, NULL, NULL, 'Admin Athlos Performance', 'athlos.performance01@gmail.com', '$2y$10$UryKD4tvGOYiMygSIcADJOA9wU5JD8dR1jATG2EoaPlFdmSJT2vHK', 1, 1, '2026-08-14 17:05:58', 0, NULL, '2026-07-13 21:27:11', '2026-08-14 17:05:58'),
(6, 2, 3, NULL, 'Bernardo Lobo Barrera', 'ber.lobo.b01@gmail.com', '$2y$10$ob1KPFzuhqJiMveN9VQHY..8Z.pqIabfrBL2AE39QMCqXtZIG03Bi', 1, 1, '2026-08-11 18:29:18', 0, NULL, '2026-08-11 18:27:26', '2026-08-12 23:55:11');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `acadep_vocacional_leads`
--
ALTER TABLE `acadep_vocacional_leads`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `agenda_bloqueos`
--
ALTER TABLE `agenda_bloqueos`
  ADD PRIMARY KEY (`id_bloqueo`),
  ADD KEY `idx_bloqueos_staff_fecha` (`id_staff`,`fecha_inicio`,`fecha_fin`),
  ADD KEY `fk_agendabloqueos_usuario` (`creado_por`);

--
-- Indices de la tabla `agenda_configuracion`
--
ALTER TABLE `agenda_configuracion`
  ADD PRIMARY KEY (`clave`);

--
-- Indices de la tabla `agenda_disponibilidad`
--
ALTER TABLE `agenda_disponibilidad`
  ADD PRIMARY KEY (`id_disponibilidad`),
  ADD UNIQUE KEY `uq_disponibilidad_dia` (`dia_semana`);

--
-- Indices de la tabla `alertas_renovacion`
--
ALTER TABLE `alertas_renovacion`
  ADD PRIMARY KEY (`id_alerta`),
  ADD UNIQUE KEY `uq_alerta_membresia_tipo` (`id_membresia`,`tipo_alerta`),
  ADD KEY `idx_alertas_atleta` (`id_atleta`),
  ADD KEY `fk_alertas_usuario` (`atendida_por`);

--
-- Indices de la tabla `asistencias`
--
ALTER TABLE `asistencias`
  ADD PRIMARY KEY (`id_asistencia`),
  ADD KEY `idx_asistencias_atleta` (`id_atleta`),
  ADD KEY `idx_asistencias_membresia` (`id_membresia`),
  ADD KEY `fk_asistencias_usuario` (`registrado_por`),
  ADD KEY `fk_asistencias_cita` (`id_cita`);

--
-- Indices de la tabla `atletas`
--
ALTER TABLE `atletas`
  ADD PRIMARY KEY (`id_atleta`),
  ADD KEY `idx_atletas_lead` (`id_lead`);

--
-- Indices de la tabla `audit_log_medico`
--
ALTER TABLE `audit_log_medico`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `idx_auditlog_lead` (`id_lead`),
  ADD KEY `idx_auditlog_atleta` (`id_atleta`);

--
-- Indices de la tabla `catalogo_servicios`
--
ALTER TABLE `catalogo_servicios`
  ADD PRIMARY KEY (`id_servicio`),
  ADD UNIQUE KEY `uq_catalogoservicios_nombre` (`nombre_servicio`);

--
-- Indices de la tabla `detalles_ejercicio`
--
ALTER TABLE `detalles_ejercicio`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `idx_detalles_sesion` (`id_sesion`);

--
-- Indices de la tabla `disponibilidad_agenda`
--
ALTER TABLE `disponibilidad_agenda`
  ADD PRIMARY KEY (`id_cita`),
  ADD KEY `idx_agenda_fecha_hora` (`fecha_cita`,`hora_inicio`),
  ADD KEY `idx_agenda_atleta` (`id_atleta`),
  ADD KEY `idx_agenda_lead` (`id_lead`),
  ADD KEY `idx_agenda_staff` (`id_staff`),
  ADD KEY `fk_agenda_servicio` (`id_servicio`);

--
-- Indices de la tabla `evaluaciones_antropometria`
--
ALTER TABLE `evaluaciones_antropometria`
  ADD PRIMARY KEY (`id_evaluacion`),
  ADD KEY `idx_antropometria_atleta` (`id_atleta`),
  ADD KEY `idx_antropometria_fecha` (`fecha_antropometria`),
  ADD KEY `fk_antropometria_usuario` (`capturado_por`);

--
-- Indices de la tabla `evaluaciones_biomecanica`
--
ALTER TABLE `evaluaciones_biomecanica`
  ADD PRIMARY KEY (`id_evaluacion_biomecanica`),
  ADD KEY `idx_biomecanica_atleta` (`id_atleta`),
  ADD KEY `idx_biomecanica_fecha` (`fecha_evaluacion`),
  ADD KEY `fk_biomecanica_usuario` (`evaluado_por`);

--
-- Indices de la tabla `evaluaciones_sft`
--
ALTER TABLE `evaluaciones_sft`
  ADD PRIMARY KEY (`id_evaluacion_sft`),
  ADD KEY `idx_sft_atleta` (`id_atleta`),
  ADD KEY `idx_sft_fecha` (`fecha_evaluacion`),
  ADD KEY `fk_sft_usuario` (`evaluado_por`);

--
-- Indices de la tabla `historial_clinico`
--
ALTER TABLE `historial_clinico`
  ADD PRIMARY KEY (`id_historial`),
  ADD UNIQUE KEY `uq_historial_atleta` (`id_atleta`),
  ADD KEY `fk_historial_usuario` (`capturado_por`);

--
-- Indices de la tabla `leads_prospectos`
--
ALTER TABLE `leads_prospectos`
  ADD PRIMARY KEY (`id_lead`),
  ADD UNIQUE KEY `uq_leads_telefono` (`telefono`);

--
-- Indices de la tabla `membresias`
--
ALTER TABLE `membresias`
  ADD PRIMARY KEY (`id_membresia`),
  ADD KEY `idx_membresias_atleta` (`id_atleta`),
  ADD KEY `idx_membresias_servicio` (`id_servicio`),
  ADD KEY `idx_membresias_estatus` (`estatus`);

--
-- Indices de la tabla `pagos_asistencia`
--
ALTER TABLE `pagos_asistencia`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `idx_pagos_atleta` (`id_atleta`),
  ADD KEY `idx_pagos_membresia` (`id_membresia`),
  ADD KEY `idx_pagos_fecha` (`fecha_pago`),
  ADD KEY `fk_pagos_usuario` (`registrado_por`);

--
-- Indices de la tabla `percentiles_sft_referencia`
--
ALTER TABLE `percentiles_sft_referencia`
  ADD PRIMARY KEY (`id_percentil`),
  ADD UNIQUE KEY `uq_percentil_rango` (`sexo`,`edad_min`,`edad_max`,`variable`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id_permiso`),
  ADD UNIQUE KEY `uq_permisos_clave` (`clave_permiso`);

--
-- Indices de la tabla `planes_macrociclo`
--
ALTER TABLE `planes_macrociclo`
  ADD PRIMARY KEY (`id_macro`),
  ADD KEY `idx_macro_atleta` (`id_atleta`),
  ADD KEY `fk_macro_usuario` (`creado_por`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`),
  ADD UNIQUE KEY `uq_roles_clave_rol` (`clave_rol`);

--
-- Indices de la tabla `rol_permisos`
--
ALTER TABLE `rol_permisos`
  ADD PRIMARY KEY (`id_rol`,`id_permiso`),
  ADD KEY `fk_rolpermisos_permiso` (`id_permiso`);

--
-- Indices de la tabla `sesiones_entrenamiento`
--
ALTER TABLE `sesiones_entrenamiento`
  ADD PRIMARY KEY (`id_sesion`),
  ADD KEY `idx_sesiones_atleta` (`id_atleta`),
  ADD KEY `idx_sesiones_fecha` (`fecha_sesion`),
  ADD KEY `idx_sesiones_staff` (`id_staff`),
  ADD KEY `fk_sesiones_cita` (`id_cita`),
  ADD KEY `fk_sesiones_macro` (`id_macro`),
  ADD KEY `fk_sesiones_usuario` (`created_by`);

--
-- Indices de la tabla `sesiones_log`
--
ALTER TABLE `sesiones_log`
  ADD PRIMARY KEY (`id_log_sesion`),
  ADD KEY `idx_sesioneslog_usuario` (`id_usuario`),
  ADD KEY `idx_sesioneslog_fecha` (`created_at`);

--
-- Indices de la tabla `sincronizacion_tokens`
--
ALTER TABLE `sincronizacion_tokens`
  ADD PRIMARY KEY (`id_token`),
  ADD UNIQUE KEY `uq_sync_staff_proveedor` (`id_staff`,`proveedor`),
  ADD KEY `idx_sync_webcal_uid` (`webcal_uid`);

--
-- Indices de la tabla `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id_staff`),
  ADD UNIQUE KEY `uq_staff_email` (`email`);

--
-- Indices de la tabla `staff_colores`
--
ALTER TABLE `staff_colores`
  ADD PRIMARY KEY (`id_staff`);

--
-- Indices de la tabla `testimonios_clientes`
--
ALTER TABLE `testimonios_clientes`
  ADD PRIMARY KEY (`id_testimonio`),
  ADD KEY `idx_testimonios_estatus_orden` (`estatus`,`orden_visualizacion`),
  ADD KEY `fk_testimonios_usuario` (`created_by`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `uq_usuarios_email` (`email`),
  ADD KEY `idx_usuarios_rol` (`id_rol`),
  ADD KEY `fk_usuarios_staff` (`id_staff`),
  ADD KEY `fk_usuarios_atleta` (`id_atleta`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `acadep_vocacional_leads`
--
ALTER TABLE `acadep_vocacional_leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `agenda_bloqueos`
--
ALTER TABLE `agenda_bloqueos`
  MODIFY `id_bloqueo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `agenda_disponibilidad`
--
ALTER TABLE `agenda_disponibilidad`
  MODIFY `id_disponibilidad` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `alertas_renovacion`
--
ALTER TABLE `alertas_renovacion`
  MODIFY `id_alerta` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `asistencias`
--
ALTER TABLE `asistencias`
  MODIFY `id_asistencia` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `atletas`
--
ALTER TABLE `atletas`
  MODIFY `id_atleta` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `audit_log_medico`
--
ALTER TABLE `audit_log_medico`
  MODIFY `id_log` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `catalogo_servicios`
--
ALTER TABLE `catalogo_servicios`
  MODIFY `id_servicio` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `detalles_ejercicio`
--
ALTER TABLE `detalles_ejercicio`
  MODIFY `id_detalle` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `disponibilidad_agenda`
--
ALTER TABLE `disponibilidad_agenda`
  MODIFY `id_cita` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `evaluaciones_antropometria`
--
ALTER TABLE `evaluaciones_antropometria`
  MODIFY `id_evaluacion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `evaluaciones_biomecanica`
--
ALTER TABLE `evaluaciones_biomecanica`
  MODIFY `id_evaluacion_biomecanica` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `evaluaciones_sft`
--
ALTER TABLE `evaluaciones_sft`
  MODIFY `id_evaluacion_sft` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `historial_clinico`
--
ALTER TABLE `historial_clinico`
  MODIFY `id_historial` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `leads_prospectos`
--
ALTER TABLE `leads_prospectos`
  MODIFY `id_lead` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `membresias`
--
ALTER TABLE `membresias`
  MODIFY `id_membresia` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `pagos_asistencia`
--
ALTER TABLE `pagos_asistencia`
  MODIFY `id_pago` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `percentiles_sft_referencia`
--
ALTER TABLE `percentiles_sft_referencia`
  MODIFY `id_percentil` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=128;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id_permiso` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `planes_macrociclo`
--
ALTER TABLE `planes_macrociclo`
  MODIFY `id_macro` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `sesiones_entrenamiento`
--
ALTER TABLE `sesiones_entrenamiento`
  MODIFY `id_sesion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sesiones_log`
--
ALTER TABLE `sesiones_log`
  MODIFY `id_log_sesion` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT de la tabla `sincronizacion_tokens`
--
ALTER TABLE `sincronizacion_tokens`
  MODIFY `id_token` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `staff`
--
ALTER TABLE `staff`
  MODIFY `id_staff` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `testimonios_clientes`
--
ALTER TABLE `testimonios_clientes`
  MODIFY `id_testimonio` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `agenda_bloqueos`
--
ALTER TABLE `agenda_bloqueos`
  ADD CONSTRAINT `fk_agendabloqueos_staff` FOREIGN KEY (`id_staff`) REFERENCES `staff` (`id_staff`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_agendabloqueos_usuario` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `alertas_renovacion`
--
ALTER TABLE `alertas_renovacion`
  ADD CONSTRAINT `fk_alertas_atleta` FOREIGN KEY (`id_atleta`) REFERENCES `atletas` (`id_atleta`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_alertas_membresia` FOREIGN KEY (`id_membresia`) REFERENCES `membresias` (`id_membresia`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_alertas_usuario` FOREIGN KEY (`atendida_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `asistencias`
--
ALTER TABLE `asistencias`
  ADD CONSTRAINT `fk_asistencias_atleta` FOREIGN KEY (`id_atleta`) REFERENCES `atletas` (`id_atleta`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_asistencias_cita` FOREIGN KEY (`id_cita`) REFERENCES `disponibilidad_agenda` (`id_cita`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_asistencias_membresia` FOREIGN KEY (`id_membresia`) REFERENCES `membresias` (`id_membresia`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_asistencias_usuario` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `atletas`
--
ALTER TABLE `atletas`
  ADD CONSTRAINT `fk_atletas_lead` FOREIGN KEY (`id_lead`) REFERENCES `leads_prospectos` (`id_lead`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `audit_log_medico`
--
ALTER TABLE `audit_log_medico`
  ADD CONSTRAINT `fk_auditlog_atleta` FOREIGN KEY (`id_atleta`) REFERENCES `atletas` (`id_atleta`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_auditlog_lead` FOREIGN KEY (`id_lead`) REFERENCES `leads_prospectos` (`id_lead`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalles_ejercicio`
--
ALTER TABLE `detalles_ejercicio`
  ADD CONSTRAINT `fk_detalles_sesion` FOREIGN KEY (`id_sesion`) REFERENCES `sesiones_entrenamiento` (`id_sesion`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `disponibilidad_agenda`
--
ALTER TABLE `disponibilidad_agenda`
  ADD CONSTRAINT `fk_agenda_atleta` FOREIGN KEY (`id_atleta`) REFERENCES `atletas` (`id_atleta`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_agenda_lead` FOREIGN KEY (`id_lead`) REFERENCES `leads_prospectos` (`id_lead`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_agenda_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `catalogo_servicios` (`id_servicio`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_agenda_staff` FOREIGN KEY (`id_staff`) REFERENCES `staff` (`id_staff`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `evaluaciones_antropometria`
--
ALTER TABLE `evaluaciones_antropometria`
  ADD CONSTRAINT `fk_antropometria_atleta` FOREIGN KEY (`id_atleta`) REFERENCES `atletas` (`id_atleta`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_antropometria_usuario` FOREIGN KEY (`capturado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `evaluaciones_biomecanica`
--
ALTER TABLE `evaluaciones_biomecanica`
  ADD CONSTRAINT `fk_biomecanica_atleta` FOREIGN KEY (`id_atleta`) REFERENCES `atletas` (`id_atleta`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_biomecanica_usuario` FOREIGN KEY (`evaluado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `evaluaciones_sft`
--
ALTER TABLE `evaluaciones_sft`
  ADD CONSTRAINT `fk_sft_atleta` FOREIGN KEY (`id_atleta`) REFERENCES `atletas` (`id_atleta`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sft_usuario` FOREIGN KEY (`evaluado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `historial_clinico`
--
ALTER TABLE `historial_clinico`
  ADD CONSTRAINT `fk_historial_atleta` FOREIGN KEY (`id_atleta`) REFERENCES `atletas` (`id_atleta`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_historial_usuario` FOREIGN KEY (`capturado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `membresias`
--
ALTER TABLE `membresias`
  ADD CONSTRAINT `fk_membresias_atleta` FOREIGN KEY (`id_atleta`) REFERENCES `atletas` (`id_atleta`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_membresias_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `catalogo_servicios` (`id_servicio`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pagos_asistencia`
--
ALTER TABLE `pagos_asistencia`
  ADD CONSTRAINT `fk_pagos_atleta` FOREIGN KEY (`id_atleta`) REFERENCES `atletas` (`id_atleta`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pagos_membresia` FOREIGN KEY (`id_membresia`) REFERENCES `membresias` (`id_membresia`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pagos_usuario` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `planes_macrociclo`
--
ALTER TABLE `planes_macrociclo`
  ADD CONSTRAINT `fk_macro_atleta` FOREIGN KEY (`id_atleta`) REFERENCES `atletas` (`id_atleta`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_macro_usuario` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `rol_permisos`
--
ALTER TABLE `rol_permisos`
  ADD CONSTRAINT `fk_rolpermisos_permiso` FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id_permiso`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rolpermisos_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `sesiones_entrenamiento`
--
ALTER TABLE `sesiones_entrenamiento`
  ADD CONSTRAINT `fk_sesiones_atleta` FOREIGN KEY (`id_atleta`) REFERENCES `atletas` (`id_atleta`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sesiones_cita` FOREIGN KEY (`id_cita`) REFERENCES `disponibilidad_agenda` (`id_cita`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sesiones_macro` FOREIGN KEY (`id_macro`) REFERENCES `planes_macrociclo` (`id_macro`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sesiones_staff` FOREIGN KEY (`id_staff`) REFERENCES `staff` (`id_staff`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sesiones_usuario` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `sesiones_log`
--
ALTER TABLE `sesiones_log`
  ADD CONSTRAINT `fk_sesioneslog_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `sincronizacion_tokens`
--
ALTER TABLE `sincronizacion_tokens`
  ADD CONSTRAINT `fk_synctokens_staff` FOREIGN KEY (`id_staff`) REFERENCES `staff` (`id_staff`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `staff_colores`
--
ALTER TABLE `staff_colores`
  ADD CONSTRAINT `fk_staffcolores_staff` FOREIGN KEY (`id_staff`) REFERENCES `staff` (`id_staff`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `testimonios_clientes`
--
ALTER TABLE `testimonios_clientes`
  ADD CONSTRAINT `fk_testimonios_usuario` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_atleta` FOREIGN KEY (`id_atleta`) REFERENCES `atletas` (`id_atleta`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuarios_staff` FOREIGN KEY (`id_staff`) REFERENCES `staff` (`id_staff`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
