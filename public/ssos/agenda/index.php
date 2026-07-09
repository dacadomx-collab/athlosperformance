<?php
declare(strict_types=1);

/**
 * ATHLOS PERFORMANCE — MÓDULO DE AGENDA / CALENDARIO DE CITAS (matriz semanal)
 *
 * Vista de semana continua (Lunes-Sábado, domingo deliberadamente ausente de
 * la matriz — ver AgendaBusinessRules::diasOperativos()) estilo Google
 * Calendar: 80% matriz central + sidebar de clientes del mes (progreso de
 * sesiones) + sidebar de coaches (color asignado). En móvil colapsa a una
 * vista de un solo día a pantalla completa (100dvh) — ver css `.ssos-agenda-*`
 * en main.css y la sección "Vista móvil" más abajo en este archivo.
 *
 * Cupo estricto: máximo AgendaBusinessRules::CUPO_MAXIMO_FRANJA citas activas
 * por franja de hora (semáforo verde/amarillo/rojo). Arquitectura genérica y
 * agnóstica de este módulo documentada en knowledge/MODULO_CALENDARIO_GENERICO.md.
 */

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/AthlosBusinessRules.php';
require_once __DIR__ . '/../config/AgendaBusinessRules.php';

require_role('coach', 'admin', 'super_admin');

const AGENDA_CANCELACION_HORAS_MINIMO = 3;

$db = ssos_db();

$fecha = (string) ($_GET['fecha'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $fecha = date('Y-m-d');
}

$lunes = AgendaBusinessRules::lunesDeLaSemana($fecha);
$diasOperativos = AgendaBusinessRules::diasOperativos();
$horasMatriz = AgendaBusinessRules::horasMatriz();

$diasSemana = [];
foreach ($diasOperativos as $diaIso => $config) {
    $fechaDia = $lunes->modify('+' . ($diaIso - 1) . ' days');
    $diasSemana[] = [
        'dia_iso' => $diaIso,
        'fecha' => $fechaDia->format('Y-m-d'),
        'label' => $config['label'],
        'dia_mes' => (int) $fechaDia->format('j'),
        'mes_label' => $fechaDia->format('M'),
        'apertura' => $config['apertura'],
        'cierre' => $config['cierre'],
    ];
}
$domingo = $lunes->modify('+6 days');
$tituloSemana = $lunes->format('M') === $domingo->format('M')
    ? $lunes->format('F Y')
    : $lunes->format('M') . ' – ' . $domingo->format('M Y');

$errores = [];
$mensajeOk = null;

// ── Alta de cita ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear_cita') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } else {
        $idAtletaCita = filter_input(INPUT_POST, 'id_atleta', FILTER_VALIDATE_INT) ?: null;
        $nombreProspecto = trim((string) ($_POST['nombre_prospecto'] ?? ''));
        $idStaffCita = filter_input(INPUT_POST, 'id_staff', FILTER_VALIDATE_INT);
        $idServicioCita = filter_input(INPUT_POST, 'id_servicio', FILTER_VALIDATE_INT);
        $fechaCita = (string) ($_POST['fecha_cita'] ?? '');
        $horaCita = (string) ($_POST['hora_inicio'] ?? '');

        if (!$idAtletaCita && $nombreProspecto === '') {
            $errores[] = 'Selecciona un atleta existente o escribe el nombre de un prospecto nuevo.';
        }
        if (!$idStaffCita) {
            $errores[] = 'Selecciona un especialista.';
        }
        if (!$idServicioCita) {
            $errores[] = 'Selecciona un servicio.';
        }
        if (!preg_match('/^\d{2}:\d{2}$/', $horaCita)) {
            $errores[] = 'Selecciona un horario válido.';
        }

        if (empty($errores)) {
            try {
                $stmtCupo = $db->prepare(
                    "SELECT COUNT(*) FROM disponibilidad_agenda
                     WHERE fecha_cita = :fecha AND hora_inicio = :hora
                       AND estatus_cita IN ('reservada','confirmada')"
                );
                $stmtCupo->execute(['fecha' => $fechaCita, 'hora' => $horaCita . ':00']);
                $ocupadas = (int) $stmtCupo->fetchColumn();

                if ($ocupadas >= AgendaBusinessRules::CUPO_MAXIMO_FRANJA) {
                    $errores[] = "Cupo lleno para las {$horaCita} (" . AgendaBusinessRules::CUPO_MAXIMO_FRANJA . '/' . AgendaBusinessRules::CUPO_MAXIMO_FRANJA . '). Elige otro horario.';
                } else {
                    $horaFin = date('H:i', strtotime($horaCita) + 3600);
                    $notas = $nombreProspecto !== '' && !$idAtletaCita ? "Prospecto (sin ficha): {$nombreProspecto}" : null;

                    $stmt = $db->prepare(
                        'INSERT INTO disponibilidad_agenda
                            (id_atleta, id_staff, id_servicio, fecha_cita, hora_inicio, hora_fin, cupo_maximo_hora, estatus_cita, notas_previas)
                         VALUES (:id_atleta, :id_staff, :id_servicio, :fecha, :hora_inicio, :hora_fin, :cupo, \'reservada\', :notas)'
                    );
                    $stmt->execute([
                        'id_atleta' => $idAtletaCita,
                        'id_staff' => $idStaffCita,
                        'id_servicio' => $idServicioCita,
                        'fecha' => $fechaCita,
                        'hora_inicio' => $horaCita . ':00',
                        'hora_fin' => $horaFin . ':00',
                        'cupo' => AgendaBusinessRules::CUPO_MAXIMO_FRANJA,
                        'notas' => $notas,
                    ]);
                    $mensajeOk = 'Cita creada exitosamente.';
                    $fecha = $fechaCita;
                    $lunes = AgendaBusinessRules::lunesDeLaSemana($fecha);
                }
            } catch (\Throwable $e) {
                $errores[] = 'No se pudo crear la cita: ' . $e->getMessage();
                error_log('[SSOS agenda crear_cita] ' . $e->getMessage());
            }
        }
    }
}

// ── Cambio de estatus de cita (confirmar / completar / cancelar / no-show) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cambiar_estatus_cita') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } else {
        $idCita = filter_input(INPUT_POST, 'id_cita', FILTER_VALIDATE_INT);
        $nuevoEstatus = (string) ($_POST['nuevo_estatus'] ?? '');

        if ($idCita && in_array($nuevoEstatus, ['confirmada', 'completada', 'cancelada', 'no_show'], true)) {
            $stmt = $db->prepare('SELECT * FROM disponibilidad_agenda WHERE id_cita = :id');
            $stmt->execute(['id' => $idCita]);
            $cita = $stmt->fetch();

            if (!$cita) {
                $errores[] = 'Cita no encontrada.';
            } elseif ($nuevoEstatus === 'cancelada') {
                $citaDatetime = new DateTimeImmutable("{$cita['fecha_cita']} {$cita['hora_inicio']}");
                $horasRestantes = ($citaDatetime->getTimestamp() - time()) / 3600;
                if ($horasRestantes < AGENDA_CANCELACION_HORAS_MINIMO) {
                    $errores[] = 'No se puede cancelar con menos de ' . AGENDA_CANCELACION_HORAS_MINIMO . ' horas de anticipación.';
                } else {
                    $db->prepare('UPDATE disponibilidad_agenda SET estatus_cita = \'cancelada\' WHERE id_cita = :id')->execute(['id' => $idCita]);
                    $mensajeOk = 'Cita cancelada.';
                }
            } elseif ($nuevoEstatus === 'completada') {
                try {
                    $db->prepare('UPDATE disponibilidad_agenda SET estatus_cita = \'completada\' WHERE id_cita = :id')->execute(['id' => $idCita]);
                    if ($cita['id_atleta']) {
                        $deduccion = AthlosBusinessRules::deducirSesionAtleta($db, (int) $cita['id_atleta']);
                        $mensajeOk = $deduccion['deducted']
                            ? 'Cita completada. Sesión descontada de la membresía (restantes: ' . $deduccion['sesiones_restantes'] . ').'
                            : 'Cita completada. El atleta no tiene membresía activa con saldo — no se descontó ninguna sesión.';
                    } else {
                        $mensajeOk = 'Cita completada.';
                    }
                } catch (\Throwable $e) {
                    $errores[] = 'No se pudo completar la cita: ' . $e->getMessage();
                    error_log('[SSOS agenda completar] ' . $e->getMessage());
                }
            } else {
                $db->prepare('UPDATE disponibilidad_agenda SET estatus_cita = :estatus WHERE id_cita = :id')
                    ->execute(['estatus' => $nuevoEstatus, 'id' => $idCita]);
                $mensajeOk = 'Estatus de la cita actualizado.';
            }
        }
    }
}

// ── Mover cita (drag-and-drop) — endpoint AJAX, responde JSON y termina ────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'mover_cita') {
    header('Content-Type: application/json');

    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        echo json_encode(['ok' => false, 'error' => 'Token de seguridad inválido. Recarga la página.']);
        exit;
    }

    $idCita = filter_input(INPUT_POST, 'id_cita', FILTER_VALIDATE_INT);
    $nuevaFecha = (string) ($_POST['nueva_fecha'] ?? '');
    $nuevaHora = (string) ($_POST['nueva_hora'] ?? '');

    if (!$idCita || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $nuevaFecha) || !preg_match('/^\d{2}:\d{2}$/', $nuevaHora)) {
        echo json_encode(['ok' => false, 'error' => 'Datos incompletos para mover la cita.']);
        exit;
    }

    $diaIso = (int) (new DateTimeImmutable($nuevaFecha))->format('N');
    if (!AgendaBusinessRules::franjaEsOperativa($diaIso, $nuevaHora)) {
        echo json_encode(['ok' => false, 'error' => 'Esa franja está fuera del horario operativo.']);
        exit;
    }

    try {
        // Cupo del destino, EXCLUYENDO la propia cita que se está moviendo (si ya estaba en esa franja).
        $stmtCupo = $db->prepare(
            "SELECT COUNT(*) FROM disponibilidad_agenda
             WHERE fecha_cita = :fecha AND hora_inicio = :hora
               AND estatus_cita IN ('reservada','confirmada')
               AND id_cita != :id_cita"
        );
        $stmtCupo->execute(['fecha' => $nuevaFecha, 'hora' => $nuevaHora . ':00', 'id_cita' => $idCita]);
        $ocupadas = (int) $stmtCupo->fetchColumn();

        if ($ocupadas >= AgendaBusinessRules::CUPO_MAXIMO_FRANJA) {
            echo json_encode(['ok' => false, 'error' => 'La franja destino ya está llena (' . AgendaBusinessRules::CUPO_MAXIMO_FRANJA . '/' . AgendaBusinessRules::CUPO_MAXIMO_FRANJA . ').']);
            exit;
        }

        $nuevaHoraFin = date('H:i', strtotime($nuevaHora) + 3600);
        $stmt = $db->prepare(
            'UPDATE disponibilidad_agenda SET fecha_cita = :fecha, hora_inicio = :hora_inicio, hora_fin = :hora_fin WHERE id_cita = :id'
        );
        $stmt->execute([
            'fecha' => $nuevaFecha,
            'hora_inicio' => $nuevaHora . ':00',
            'hora_fin' => $nuevaHoraFin . ':00',
            'id' => $idCita,
        ]);
        echo json_encode(['ok' => true]);
    } catch (\Throwable $e) {
        error_log('[SSOS agenda mover_cita] ' . $e->getMessage());
        echo json_encode(['ok' => false, 'error' => 'No se pudo mover la cita.']);
    }
    exit;
}

// ── Datos para la vista ──────────────────────────────────────────────────
$staffList = $db->query('SELECT id_staff, nombre_completo FROM staff WHERE activo = 1 ORDER BY nombre_completo')->fetchAll();
$servicios = $db->query("SELECT id_servicio, nombre_servicio FROM catalogo_servicios WHERE activo = 1 ORDER BY nombre_servicio")->fetchAll();
$atletasActivos = $db->query("SELECT id_atleta, nombre_completo FROM atletas WHERE estatus = 'activo' ORDER BY nombre_completo")->fetchAll();
$coloresStaff = AgendaBusinessRules::coloresParaStaffList($db, array_column($staffList, 'id_staff'));

$sabado = $lunes->modify('+5 days')->format('Y-m-d');
$stmtSemana = $db->prepare(
    "SELECT da.*, a.nombre_completo AS atleta_nombre, s.nombre_completo AS staff_nombre, cs.nombre_servicio
     FROM disponibilidad_agenda da
     LEFT JOIN atletas a ON a.id_atleta = da.id_atleta
     INNER JOIN staff s ON s.id_staff = da.id_staff
     INNER JOIN catalogo_servicios cs ON cs.id_servicio = da.id_servicio
     WHERE da.fecha_cita BETWEEN :lunes AND :sabado
     ORDER BY da.hora_inicio"
);
$stmtSemana->execute(['lunes' => $lunes->format('Y-m-d'), 'sabado' => $sabado]);
$citasSemana = $stmtSemana->fetchAll();

// citasPorCelda[fecha][hora] = array de citas ; ocupacionPorCelda[fecha][hora] = int (sólo reservada/confirmada)
$citasPorCelda = [];
$ocupacionPorCelda = [];
foreach ($citasSemana as $c) {
    $horaCorta = substr((string) $c['hora_inicio'], 0, 5);
    $citasPorCelda[$c['fecha_cita']][$horaCorta][] = $c;
    if (in_array($c['estatus_cita'], ['reservada', 'confirmada'], true)) {
        $ocupacionPorCelda[$c['fecha_cita']][$horaCorta] = ($ocupacionPorCelda[$c['fecha_cita']][$horaCorta] ?? 0) + 1;
    }
}

// Vista móvil: sólo el día puntual $fecha (no toda la semana)
$citasDelDiaMovil = $citasPorCelda[$fecha] ?? [];
ksort($citasDelDiaMovil);

// Indicador de avance de la semana: franjas operativas totales vs. ocupadas —
// da una lectura de "qué tan llena está la semana" de un vistazo, sin tener
// que contar celdas rojas en la matriz una por una.
$franjasOperativasTotales = 0;
$franjasOcupadasTotales = 0;
$citasPorStaffSemana = [];
foreach ($diasSemana as $dia) {
    foreach ($horasMatriz as $hora) {
        if (!AgendaBusinessRules::franjaEsOperativa($dia['dia_iso'], $hora)) {
            continue;
        }
        $franjasOperativasTotales++;
        $franjasOcupadasTotales += min($ocupacionPorCelda[$dia['fecha']][$hora] ?? 0, AgendaBusinessRules::CUPO_MAXIMO_FRANJA);
    }
}
foreach ($citasSemana as $c) {
    if (in_array($c['estatus_cita'], ['reservada', 'confirmada'], true)) {
        $citasPorStaffSemana[$c['id_staff']] = ($citasPorStaffSemana[$c['id_staff']] ?? 0) + 1;
    }
}
$pctOcupacionSemana = $franjasOperativasTotales > 0
    ? (int) round(($franjasOcupadasTotales / ($franjasOperativasTotales * AgendaBusinessRules::CUPO_MAXIMO_FRANJA)) * 100)
    : 0;

// Sidebar izquierdo: clientes con membresía activa este mes, priorizando los que están por agotarse.
// Un cliente puede tener más de una membresía activa simultánea (ej. paquete de
// entrenamiento + paquete de nutrición) — se muestra una fila POR MEMBRESÍA, no
// por cliente, con el nombre del servicio para no repetir el nombre del cliente
// sin contexto (verificado contra datos reales: varios atletas tienen 2 filas).
$stmtClientesMes = $db->prepare(
    "SELECT a.id_atleta, a.nombre_completo, m.id_membresia, m.sesiones_totales, m.sesiones_restantes, cs.nombre_servicio
     FROM membresias m
     INNER JOIN atletas a ON a.id_atleta = m.id_atleta
     INNER JOIN catalogo_servicios cs ON cs.id_servicio = m.id_servicio
     WHERE m.estatus = 'activa' AND m.sesiones_totales > 0
       AND m.fecha_inicio <= :fin_mes AND (m.fecha_fin IS NULL OR m.fecha_fin >= :inicio_mes)
     ORDER BY m.sesiones_restantes ASC
     LIMIT 20"
);
$stmtClientesMes->execute([
    'inicio_mes' => $lunes->format('Y-m-01'),
    'fin_mes' => $lunes->format('Y-m-t'),
]);
$clientesDelMes = $stmtClientesMes->fetchAll();
$alertasSesionesBajas = count(array_filter($clientesDelMes, static fn ($c) => (int) $c['sesiones_restantes'] <= 2));

$etiquetasEstatus = [
    'reservada' => ['label' => 'Reservada', 'badge' => 'secondary'],
    'confirmada' => ['label' => 'Confirmada', 'badge' => 'info'],
    'completada' => ['label' => 'Completada', 'badge' => 'success'],
    'cancelada' => ['label' => 'Cancelada', 'badge' => 'dark'],
    'no_show' => ['label' => 'No-Show', 'badge' => 'danger'],
];

$ssos_page_title = 'Agenda';
$ssos_active_nav = 'agenda';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/agenda_vista.php';
require __DIR__ . '/../partials/footer.php';
