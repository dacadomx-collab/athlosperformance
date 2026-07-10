<?php
declare(strict_types=1);

/**
 * ATHLOS PERFORMANCE — DISPONIBILIDAD PÚBLICA (Fase 24, Misión 3)
 *
 * Página SIN LOGIN. Muestra únicamente las franjas horarias con cupo libre
 * de la semana en curso (o la solicitada por ?fecha=), según los mismos
 * días/horarios/aforo/bloqueos configurados en el Panel de Configuración
 * (AgendaBusinessRules — misma fuente de verdad que la matriz administrativa,
 * así nunca se desincroniza). Un clic en una franja libre abre un formulario
 * mínimo; el envío NO reserva la cita — inserta un registro con estatus
 * 'pendiente_aprobacion' que el equipo confirma desde agenda/index.php.
 *
 * Especificación agnóstica de este flujo: knowledge/MODULO_CALENDARIO_GENERICO.md
 */

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/AgendaBusinessRules.php';

$db = ssos_db();

$fecha = (string) ($_GET['fecha'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $fecha = date('Y-m-d');
}

$lunes = AgendaBusinessRules::lunesDeLaSemana($fecha);
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
        'apertura' => $config['apertura'],
        'cierre' => $config['cierre'],
    ];
}

$errores = [];
$mensajeOk = null;

// ── Solicitud pública de cita (no reserva; queda pendiente_aprobacion) ──────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'solicitar_cita_publica') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } else {
        $nombre = trim((string) ($_POST['solicitante_nombre'] ?? ''));
        $telefono = trim((string) ($_POST['solicitante_telefono'] ?? ''));
        $email = trim((string) ($_POST['solicitante_email'] ?? ''));
        $idStaffCita = filter_input(INPUT_POST, 'id_staff', FILTER_VALIDATE_INT);
        $idServicioCita = filter_input(INPUT_POST, 'id_servicio', FILTER_VALIDATE_INT);
        $fechaCita = (string) ($_POST['fecha_cita'] ?? '');
        $horaCita = (string) ($_POST['hora_inicio'] ?? '');

        if ($nombre === '') {
            $errores[] = 'Escribe tu nombre completo.';
        }
        if ($telefono === '' && $email === '') {
            $errores[] = 'Deja al menos un teléfono o correo de contacto.';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo no es válido.';
        }
        if (!$idStaffCita) {
            $errores[] = 'Selecciona un especialista.';
        }
        if (!$idServicioCita) {
            $errores[] = 'Selecciona un servicio.';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaCita) || !preg_match('/^\d{2}:\d{2}$/', $horaCita)) {
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
                    $errores[] = 'Ese horario ya no está disponible. Elige otro.';
                } elseif ($ocupadas >= $cupoMaximoFranja) {
                    $errores[] = 'Ese horario acaba de llenarse. Elige otro.';
                } else {
                    $horaFin = date('H:i', strtotime($horaCita) + 3600);
                    $stmt = $db->prepare(
                        'INSERT INTO disponibilidad_agenda
                            (id_staff, id_servicio, fecha_cita, hora_inicio, hora_fin, cupo_maximo_hora, estatus_cita,
                             solicitante_nombre, solicitante_telefono, solicitante_email)
                         VALUES (:id_staff, :id_servicio, :fecha, :hora_inicio, :hora_fin, :cupo, \'pendiente_aprobacion\',
                             :nombre, :telefono, :email)'
                    );
                    $stmt->execute([
                        'id_staff' => $idStaffCita,
                        'id_servicio' => $idServicioCita,
                        'fecha' => $fechaCita,
                        'hora_inicio' => $horaCita . ':00',
                        'hora_fin' => $horaFin . ':00',
                        'cupo' => $cupoMaximoFranja,
                        'nombre' => $nombre,
                        'telefono' => $telefono !== '' ? $telefono : null,
                        'email' => $email !== '' ? $email : null,
                    ]);
                    $mensajeOk = 'Tu solicitud fue enviada. El equipo la revisará y te contactará para confirmar tu cita.';
                }
            } catch (\Throwable $e) {
                $errores[] = 'No se pudo enviar tu solicitud. Intenta de nuevo o contáctanos directamente.';
                error_log('[SSOS agenda_publica solicitar_cita_publica] ' . $e->getMessage());
            }
        }
    }
}

$staffList = $db->query('SELECT id_staff, nombre_completo FROM staff WHERE activo = 1 ORDER BY nombre_completo')->fetchAll();
$servicios = $db->query('SELECT id_servicio, nombre_servicio FROM catalogo_servicios WHERE activo = 1 ORDER BY nombre_servicio')->fetchAll();

// Ocupación por celda (fecha|hora → contador), sólo lo necesario para calcular
// disponibilidad — nunca se exponen nombres de clientes/atletas en esta vista pública.
$stmtOcupacion = $db->prepare(
    "SELECT fecha_cita, hora_inicio, COUNT(*) AS ocupadas
     FROM disponibilidad_agenda
     WHERE fecha_cita BETWEEN :inicio AND :fin
       AND estatus_cita IN ('reservada','confirmada')
     GROUP BY fecha_cita, hora_inicio"
);
$stmtOcupacion->execute(['inicio' => $lunes->format('Y-m-d'), 'fin' => $lunes->modify('+5 days')->format('Y-m-d')]);
$ocupacionPorCelda = [];
foreach ($stmtOcupacion->fetchAll() as $fila) {
    $ocupacionPorCelda[$fila['fecha_cita'] . '|' . substr($fila['hora_inicio'], 0, 5)] = (int) $fila['ocupadas'];
}

$tituloSemana = AgendaBusinessRules::tituloSemana($lunes, $lunes->modify('+5 days'));
$semanaAnteriorFecha = $lunes->modify('-7 days')->format('Y-m-d');
$semanaSiguienteFecha = $lunes->modify('+7 days')->format('Y-m-d');

$ssos_page_title = 'Disponibilidad · Athlos Performance';
require __DIR__ . '/../partials/header_publico.php';
require __DIR__ . '/agenda_publica_vista.php';
require __DIR__ . '/../partials/footer_publico.php';
