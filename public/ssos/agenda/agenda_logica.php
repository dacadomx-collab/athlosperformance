<?php
declare(strict_types=1);

/**
 * ATHLOS PERFORMANCE — LÓGICA DEL MÓDULO DE AGENDA (datos + POST handlers)
 *
 * Separada de agenda/index.php para poder reutilizarse tal cual desde
 * dashboard/index.php (el Calendario es la pestaña de aterrizaje del
 * Dashboard, Fase 23) sin duplicar ~300 líneas de queries y validaciones.
 * Este archivo NUNCA hace `require partials/header.php` — el caller decide
 * si envuelve el resultado en la página standalone (agenda/index.php) o lo
 * embebe dentro de un tab-pane (dashboard/index.php). Tampoco hace el chequeo
 * de rol (`require_role()`) — cada caller es responsable de eso, porque
 * dashboard/index.php ya tiene su propia lógica de visibilidad por pestaña.
 *
 * Deja listas todas las variables que consume agenda_vista.php: $lunes,
 * $diasSemana, $horasMatriz, $citasPorCelda, $ocupacionPorCelda,
 * $coloresStaff, $clientesDelMes, $staffList, $servicios, $atletasActivos,
 * $errores, $mensajeOk, etc.
 */

require_once __DIR__ . '/../config/AthlosBusinessRules.php';
require_once __DIR__ . '/../config/AgendaBusinessRules.php';

const AGENDA_CANCELACION_HORAS_MINIMO = 3;

$db = ssos_db();

$fecha = (string) ($_GET['fecha'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $fecha = date('Y-m-d');
}

$lunes = AgendaBusinessRules::lunesDeLaSemana($fecha);

// Config dinámica (Panel de Configuración, Fase 24) resuelta UNA vez por
// request — todo lo demás en este archivo y en agenda_vista.php recibe estos
// valores ya resueltos como parámetro, en vez de volver a consultar la BD.
$diasOperativos = AgendaBusinessRules::diasOperativos($db);
$cupoMaximoFranja = AgendaBusinessRules::cupoMaximoFranja($db);
$horasMatriz = AgendaBusinessRules::horasMatriz($diasOperativos);
$bloqueosSemana = AgendaBusinessRules::bloqueosEnRango($db, $lunes->format('Y-m-d'), $lunes->modify('+5 days')->format('Y-m-d'));

$diasSemana = [];
foreach ($diasOperativos as $diaIso => $config) {
    $fechaDia = $lunes->modify('+' . ($diaIso - 1) . ' days');
    $diasSemana[] = [
        'dia_iso' => $diaIso,
        'fecha' => $fechaDia->format('Y-m-d'),
        'label' => $config['label'],
        'dia_mes' => (int) $fechaDia->format('j'),
        'mes_label' => AgendaBusinessRules::mesEnEspanolCorto($fechaDia),
        'apertura' => $config['apertura'],
        'cierre' => $config['cierre'],
    ];
}

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
                $bloqueoDelDia = AgendaBusinessRules::bloqueosEnRango($db, $fechaCita, $fechaCita);
                $motivoBloqueo = AgendaBusinessRules::franjaBloqueada($bloqueoDelDia, $fechaCita, $horaCita . ':00', $idStaffCita);

                $stmtCupo = $db->prepare(
                    "SELECT COUNT(*) FROM disponibilidad_agenda
                     WHERE fecha_cita = :fecha AND hora_inicio = :hora
                       AND estatus_cita IN ('reservada','confirmada')"
                );
                $stmtCupo->execute(['fecha' => $fechaCita, 'hora' => $horaCita . ':00']);
                $ocupadas = (int) $stmtCupo->fetchColumn();

                if ($motivoBloqueo !== null) {
                    $errores[] = "Ese horario está bloqueado ({$motivoBloqueo}). Elige otro horario o especialista.";
                } elseif ($ocupadas >= $cupoMaximoFranja) {
                    $errores[] = "Cupo lleno para las {$horaCita} (" . $cupoMaximoFranja . '/' . $cupoMaximoFranja . '). Elige otro horario.';
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
                        'cupo' => $cupoMaximoFranja,
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
            } elseif ($cita['estatus_cita'] === 'pendiente_aprobacion' && $nuevoEstatus === 'confirmada') {
                // Aprobar una solicitud pública (Misión 3) — revalida el cupo AHORA,
                // porque una solicitud pendiente nunca contó contra el aforo mientras
                // esperaba aprobación (varias solicitudes pueden competir por la misma franja).
                $stmtCupoAprobar = $db->prepare(
                    "SELECT COUNT(*) FROM disponibilidad_agenda
                     WHERE fecha_cita = :fecha AND hora_inicio = :hora AND estatus_cita IN ('reservada','confirmada')"
                );
                $stmtCupoAprobar->execute(['fecha' => $cita['fecha_cita'], 'hora' => $cita['hora_inicio']]);
                if ((int) $stmtCupoAprobar->fetchColumn() >= $cupoMaximoFranja) {
                    $errores[] = 'No se pudo aprobar: la franja ya se llenó con otras citas mientras tanto. Reagenda al solicitante en otro horario.';
                } else {
                    $db->prepare('UPDATE disponibilidad_agenda SET estatus_cita = \'confirmada\' WHERE id_cita = :id')->execute(['id' => $idCita]);
                    $mensajeOk = 'Solicitud aprobada y confirmada.';
                }
            } elseif ($cita['estatus_cita'] === 'pendiente_aprobacion' && $nuevoEstatus === 'cancelada') {
                // Rechazar una solicitud pública — nunca ocupó cupo real, así que no aplica la regla de 3h.
                $db->prepare('UPDATE disponibilidad_agenda SET estatus_cita = \'cancelada\' WHERE id_cita = :id')->execute(['id' => $idCita]);
                $mensajeOk = 'Solicitud rechazada.';
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
    if (!AgendaBusinessRules::franjaEsOperativa($diasOperativos, $diaIso, $nuevaHora)) {
        echo json_encode(['ok' => false, 'error' => 'Esa franja está fuera del horario operativo.']);
        exit;
    }

    try {
        $stmtStaffCita = $db->prepare('SELECT id_staff FROM disponibilidad_agenda WHERE id_cita = :id');
        $stmtStaffCita->execute(['id' => $idCita]);
        $idStaffCitaMovida = (int) ($stmtStaffCita->fetchColumn() ?: 0);
        $bloqueoDestino = AgendaBusinessRules::bloqueosEnRango($db, $nuevaFecha, $nuevaFecha);
        $motivoBloqueo = AgendaBusinessRules::franjaBloqueada($bloqueoDestino, $nuevaFecha, $nuevaHora . ':00', $idStaffCitaMovida ?: null);
        if ($motivoBloqueo !== null) {
            echo json_encode(['ok' => false, 'error' => "Esa franja está bloqueada ({$motivoBloqueo})."]);
            exit;
        }

        // Cupo del destino, EXCLUYENDO la propia cita que se está moviendo (si ya estaba en esa franja).
        $stmtCupo = $db->prepare(
            "SELECT COUNT(*) FROM disponibilidad_agenda
             WHERE fecha_cita = :fecha AND hora_inicio = :hora
               AND estatus_cita IN ('reservada','confirmada')
               AND id_cita != :id_cita"
        );
        $stmtCupo->execute(['fecha' => $nuevaFecha, 'hora' => $nuevaHora . ':00', 'id_cita' => $idCita]);
        $ocupadas = (int) $stmtCupo->fetchColumn();

        if ($ocupadas >= $cupoMaximoFranja) {
            echo json_encode(['ok' => false, 'error' => 'La franja destino ya está llena (' . $cupoMaximoFranja . '/' . $cupoMaximoFranja . ').']);
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
//
// REGLA DE DISEÑO (misma que AgendaBusinessRules — "config dinámica con
// fallback determinístico, nunca un error fatal"): las columnas
// solicitante_* y los valores de estatus 'pendiente_aprobacion' /
// 'cancelada_por_cliente' vienen de la migración
// 07_schema_configuracion_agenda_publica.sql. Si esa migración aún no se
// aplicó en el entorno (columna/tabla inexistente → PDOException), la
// agenda debe seguir renderizando con esas listas vacías en vez de un 500.

try {
    // Solicitudes públicas pendientes de aprobación (Misión 3) — se listan TODAS
    // las futuras, no sólo las de la semana visible, porque un prospecto puede
    // pedir una fecha fuera de la semana que el coach tiene abierta ahora mismo.
    $solicitudesPendientes = $db->query(
        "SELECT da.id_cita, da.fecha_cita, da.hora_inicio, da.solicitante_nombre, da.solicitante_telefono, da.solicitante_email,
                s.nombre_completo AS staff_nombre, cs.nombre_servicio
         FROM disponibilidad_agenda da
         INNER JOIN staff s ON s.id_staff = da.id_staff
         INNER JOIN catalogo_servicios cs ON cs.id_servicio = da.id_servicio
         WHERE da.estatus_cita = 'pendiente_aprobacion' AND da.fecha_cita >= CURDATE()
         ORDER BY da.fecha_cita, da.hora_inicio"
    )->fetchAll();
} catch (\Throwable $e) {
    error_log('[SSOS agenda solicitudesPendientes] ' . $e->getMessage());
    $solicitudesPendientes = [];
}

try {
    // Cancelaciones autónomas del cliente (Misión 5) sin atender todavía — "alerta
    // visual" al coach/admin: se resuelve con la misma tabla, sin infraestructura
    // de notificaciones nueva (no hay SMTP/WhatsApp activado en este entorno).
    $cancelacionesClienteRecientes = $db->query(
        "SELECT da.id_cita, da.fecha_cita, da.hora_inicio, a.nombre_completo AS atleta_nombre, s.nombre_completo AS staff_nombre
         FROM disponibilidad_agenda da
         LEFT JOIN atletas a ON a.id_atleta = da.id_atleta
         INNER JOIN staff s ON s.id_staff = da.id_staff
         WHERE da.estatus_cita = 'cancelada_por_cliente' AND da.updated_at >= (NOW() - INTERVAL 7 DAY)
         ORDER BY da.updated_at DESC"
    )->fetchAll();
} catch (\Throwable $e) {
    error_log('[SSOS agenda cancelacionesClienteRecientes] ' . $e->getMessage());
    $cancelacionesClienteRecientes = [];
}

try {
    $staffList = $db->query('SELECT id_staff, nombre_completo FROM staff WHERE activo = 1 ORDER BY nombre_completo')->fetchAll();
} catch (\Throwable $e) {
    error_log('[SSOS agenda staffList] ' . $e->getMessage());
    $staffList = [];
}

try {
    $servicios = $db->query("SELECT id_servicio, nombre_servicio FROM catalogo_servicios WHERE activo = 1 ORDER BY nombre_servicio")->fetchAll();
} catch (\Throwable $e) {
    error_log('[SSOS agenda servicios] ' . $e->getMessage());
    $servicios = [];
}

try {
    $atletasActivos = $db->query("SELECT id_atleta, nombre_completo FROM atletas WHERE estatus = 'activo' ORDER BY nombre_completo")->fetchAll();
} catch (\Throwable $e) {
    error_log('[SSOS agenda atletasActivos] ' . $e->getMessage());
    $atletasActivos = [];
}

$coloresStaff = AgendaBusinessRules::coloresParaStaffList($db, array_column($staffList, 'id_staff'));

$sabado = $lunes->modify('+5 days')->format('Y-m-d');
try {
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
} catch (\Throwable $e) {
    error_log('[SSOS agenda citasSemana] ' . $e->getMessage());
    $citasSemana = [];
}

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
        if (!AgendaBusinessRules::franjaEsOperativa($diasOperativos, $dia['dia_iso'], $hora)) {
            continue;
        }
        $franjasOperativasTotales++;
        $franjasOcupadasTotales += min($ocupacionPorCelda[$dia['fecha']][$hora] ?? 0, $cupoMaximoFranja);
    }
}
foreach ($citasSemana as $c) {
    if (in_array($c['estatus_cita'], ['reservada', 'confirmada'], true)) {
        $citasPorStaffSemana[$c['id_staff']] = ($citasPorStaffSemana[$c['id_staff']] ?? 0) + 1;
    }
}
$pctOcupacionSemana = $franjasOperativasTotales > 0
    ? (int) round(($franjasOcupadasTotales / ($franjasOperativasTotales * $cupoMaximoFranja)) * 100)
    : 0;

// Sidebar izquierdo: clientes con membresía activa este mes, priorizando los que están por agotarse.
// Un cliente puede tener más de una membresía activa simultánea (ej. paquete de
// entrenamiento + paquete de nutrición) — se muestra una fila POR MEMBRESÍA, no
// por cliente, con el nombre del servicio para no repetir el nombre del cliente
// sin contexto (verificado contra datos reales: varios atletas tienen 2 filas).
try {
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
} catch (\Throwable $e) {
    error_log('[SSOS agenda clientesDelMes] ' . $e->getMessage());
    $clientesDelMes = [];
}
$alertasSesionesBajas = count(array_filter($clientesDelMes, static fn ($c) => (int) $c['sesiones_restantes'] <= 2));

$etiquetasEstatus = [
    'reservada' => ['label' => 'Reservada', 'badge' => 'secondary'],
    'confirmada' => ['label' => 'Confirmada', 'badge' => 'info'],
    'completada' => ['label' => 'Completada', 'badge' => 'success'],
    'cancelada' => ['label' => 'Cancelada', 'badge' => 'dark'],
    'no_show' => ['label' => 'No-Show', 'badge' => 'danger'],
];
