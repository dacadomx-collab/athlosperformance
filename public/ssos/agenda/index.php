<?php
declare(strict_types=1);

/**
 * ATHLOS PERFORMANCE — MÓDULO DE AGENDA / CALENDARIO DE CITAS
 *
 * Cupo estricto: máximo AGENDA_CUPO_MAXIMO_HORA atletas/pacientes por bloque
 * de hora en el laboratorio (bloquea automáticamente la reserva al llegar al
 * límite). Nota de reconciliación: el Documento Maestro original y el schema
 * (`disponibilidad_agenda.cupo_maximo_hora` DEFAULT 4) documentaban un cupo de
 * 4; esta directriz lo revisa explícitamente a 3 — se aplica el valor más
 * reciente aquí, documentado también en RESUMEN_EJECUCION_SISTEMA.md.
 */

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/AthlosBusinessRules.php';

require_role('coach', 'admin', 'super_admin');

const AGENDA_CUPO_MAXIMO_HORA = 3;
const AGENDA_CANCELACION_HORAS_MINIMO = 3;
const AGENDA_HORAS_LABORALES = ['07:00', '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00'];

$db = ssos_db();
$rol = $_SESSION['clave_rol'];

$fecha = (string) ($_GET['fecha'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $fecha = date('Y-m-d');
}
$filtroStaff = filter_input(INPUT_GET, 'id_staff', FILTER_VALIDATE_INT) ?: null;

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
        if (!in_array($horaCita, AGENDA_HORAS_LABORALES, true)) {
            $errores[] = 'Selecciona un horario válido.';
        }

        if (empty($errores)) {
            try {
                // REGLA DE CUPO: máximo AGENDA_CUPO_MAXIMO_HORA citas activas por bloque de hora.
                $stmtCupo = $db->prepare(
                    "SELECT COUNT(*) FROM disponibilidad_agenda
                     WHERE fecha_cita = :fecha AND hora_inicio = :hora
                       AND estatus_cita IN ('reservada','confirmada')"
                );
                $stmtCupo->execute(['fecha' => $fechaCita, 'hora' => $horaCita . ':00']);
                $ocupadas = (int) $stmtCupo->fetchColumn();

                if ($ocupadas >= AGENDA_CUPO_MAXIMO_HORA) {
                    $errores[] = "Cupo lleno para las {$horaCita} (" . AGENDA_CUPO_MAXIMO_HORA . '/' . AGENDA_CUPO_MAXIMO_HORA . '). Elige otro horario.';
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
                        'cupo' => AGENDA_CUPO_MAXIMO_HORA,
                        'notas' => $notas,
                    ]);
                    $mensajeOk = 'Cita creada exitosamente.';
                    $fecha = $fechaCita;
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

// ── Datos para la vista ──────────────────────────────────────────────────
$staffList = $db->query('SELECT id_staff, nombre_completo FROM staff WHERE activo = 1 ORDER BY nombre_completo')->fetchAll();
$servicios = $db->query("SELECT id_servicio, nombre_servicio FROM catalogo_servicios WHERE activo = 1 ORDER BY nombre_servicio")->fetchAll();
$atletasActivos = $db->query("SELECT id_atleta, nombre_completo FROM atletas WHERE estatus = 'activo' ORDER BY nombre_completo")->fetchAll();

$sqlCitas = "SELECT da.*, a.nombre_completo AS atleta_nombre, s.nombre_completo AS staff_nombre, cs.nombre_servicio
             FROM disponibilidad_agenda da
             LEFT JOIN atletas a ON a.id_atleta = da.id_atleta
             INNER JOIN staff s ON s.id_staff = da.id_staff
             INNER JOIN catalogo_servicios cs ON cs.id_servicio = da.id_servicio
             WHERE da.fecha_cita = :fecha" . ($filtroStaff ? ' AND da.id_staff = :id_staff' : '') . '
             ORDER BY da.hora_inicio';
$stmt = $db->prepare($sqlCitas);
$stmt->execute($filtroStaff ? ['fecha' => $fecha, 'id_staff' => $filtroStaff] : ['fecha' => $fecha]);
$citasDelDia = $stmt->fetchAll();

$ocupacionPorHora = [];
foreach ($citasDelDia as $c) {
    if (in_array($c['estatus_cita'], ['reservada', 'confirmada'], true)) {
        $hora = substr((string) $c['hora_inicio'], 0, 5);
        $ocupacionPorHora[$hora] = ($ocupacionPorHora[$hora] ?? 0) + 1;
    }
}

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
?>

<span class="ssos-role-badge">Agenda y Calendario de Citas</span>
<h2 class="mt-3">Agenda del <?= e($fecha) ?></h2>
<p class="text-body-secondary">Cupo máximo: <strong><?= AGENDA_CUPO_MAXIMO_HORA ?> atletas/pacientes por hora</strong>.</p>

<?php if ($mensajeOk): ?>
    <div class="alert alert-success ssos-alert" role="alert"><?= e($mensajeOk) ?></div>
<?php endif; ?>
<?php foreach ($errores as $error): ?>
    <div class="alert alert-danger ssos-alert" role="alert"><?= e($error) ?></div>
<?php endforeach; ?>

<form method="get" class="ssos-table-card mb-4 d-flex flex-wrap gap-2 align-items-end">
    <div>
        <label class="form-label">Fecha</label>
        <input type="date" name="fecha" class="form-control" value="<?= e($fecha) ?>">
    </div>
    <div>
        <label class="form-label">Especialista</label>
        <select name="id_staff" class="form-select">
            <option value="">Todos (vista general del laboratorio)</option>
            <?php foreach ($staffList as $s): ?>
                <option value="<?= (int) $s['id_staff'] ?>" <?= $filtroStaff === (int) $s['id_staff'] ? 'selected' : '' ?>><?= e($s['nombre_completo']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-ssos-primary">Ver</button>
    <button type="button" class="btn btn-ssos-turquesa" data-bs-toggle="modal" data-bs-target="#modalNuevaCita">+ Nueva Cita</button>
</form>

<div class="ssos-table-card mb-4">
    <h5 class="mb-3">Disponibilidad por hora</h5>
    <div class="d-flex flex-wrap gap-2 justify-content-center">
        <?php foreach (AGENDA_HORAS_LABORALES as $hora): ?>
            <?php
                $ocupadas = $ocupacionPorHora[$hora] ?? 0;
                $semaforo = $ocupadas === 0 ? 'verde' : ($ocupadas >= AGENDA_CUPO_MAXIMO_HORA ? 'rojo' : 'amarillo');
                $iconoSemaforo = ['verde' => '🟢', 'amarillo' => '🟡', 'rojo' => '🔴'][$semaforo];
            ?>
            <div class="ssos-hora-bloque ssos-hora-bloque--<?= $semaforo ?>">
                <div class="ssos-hora-label"><?= e($hora) ?></div>
                <div class="ssos-hora-ocupacion"><?= $iconoSemaforo ?> <?= $ocupadas ?>/<?= AGENDA_CUPO_MAXIMO_HORA ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="ssos-table-card">
    <h5 class="mb-3">Citas del día</h5>
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Hora</th>
                <th>Atleta / Prospecto</th>
                <th>Especialista</th>
                <th>Servicio</th>
                <th>Estatus</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($citasDelDia)): ?>
                <tr><td colspan="6" class="text-center text-body-secondary py-4">No hay citas para este día.</td></tr>
            <?php endif; ?>
            <?php foreach ($citasDelDia as $cita): ?>
                <tr>
                    <td><?= e(substr((string) $cita['hora_inicio'], 0, 5)) ?></td>
                    <td><?= e($cita['atleta_nombre'] ?? ($cita['notas_previas'] ?: 'Prospecto sin nombre')) ?></td>
                    <td><?= e($cita['staff_nombre']) ?></td>
                    <td><?= e($cita['nombre_servicio']) ?></td>
                    <td><span class="badge text-bg-<?= $etiquetasEstatus[$cita['estatus_cita']]['badge'] ?? 'secondary' ?>"><?= e($etiquetasEstatus[$cita['estatus_cita']]['label'] ?? $cita['estatus_cita']) ?></span></td>
                    <td>
                        <?php if (in_array($cita['estatus_cita'], ['reservada', 'confirmada'], true)): ?>
                            <div class="d-flex flex-wrap gap-1">
                                <?php if ($cita['estatus_cita'] === 'reservada'): ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="accion" value="cambiar_estatus_cita">
                                        <input type="hidden" name="id_cita" value="<?= (int) $cita['id_cita'] ?>">
                                        <input type="hidden" name="nuevo_estatus" value="confirmada">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Confirmar</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="accion" value="cambiar_estatus_cita">
                                    <input type="hidden" name="id_cita" value="<?= (int) $cita['id_cita'] ?>">
                                    <input type="hidden" name="nuevo_estatus" value="completada">
                                    <button type="submit" class="btn btn-sm btn-ssos-turquesa">Completar</button>
                                </form>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="accion" value="cambiar_estatus_cita">
                                    <input type="hidden" name="id_cita" value="<?= (int) $cita['id_cita'] ?>">
                                    <input type="hidden" name="nuevo_estatus" value="no_show">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">No-Show</button>
                                </form>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="accion" value="cambiar_estatus_cita">
                                    <input type="hidden" name="id_cita" value="<?= (int) $cita['id_cita'] ?>">
                                    <input type="hidden" name="nuevo_estatus" value="cancelada">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Cancelar</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <span class="text-body-secondary">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="modalNuevaCita" tabindex="-1" aria-labelledby="modalNuevaCitaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="accion" value="crear_cita">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevaCitaLabel">Nueva Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Atleta existente</label>
                    <select name="id_atleta" class="form-select">
                        <option value="">— Seleccionar —</option>
                        <?php foreach ($atletasActivos as $a): ?>
                            <option value="<?= (int) $a['id_atleta'] ?>"><?= e($a['nombre_completo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">O prospecto nuevo (sin ficha aún)</label>
                    <input type="text" name="nombre_prospecto" class="form-control" placeholder="Nombre del prospecto">
                </div>
                <div class="mb-3">
                    <label class="form-label">Especialista</label>
                    <select name="id_staff" class="form-select" required>
                        <option value="">—</option>
                        <?php foreach ($staffList as $s): ?>
                            <option value="<?= (int) $s['id_staff'] ?>"><?= e($s['nombre_completo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Servicio</label>
                    <select name="id_servicio" class="form-select" required>
                        <option value="">—</option>
                        <?php foreach ($servicios as $s): ?>
                            <option value="<?= (int) $s['id_servicio'] ?>"><?= e($s['nombre_servicio']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row">
                    <div class="col-sm-6 mb-3">
                        <label class="form-label">Fecha</label>
                        <input type="date" name="fecha_cita" class="form-control" value="<?= e($fecha) ?>" required>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <label class="form-label">Hora</label>
                        <select name="hora_inicio" class="form-select" required>
                            <?php foreach (AGENDA_HORAS_LABORALES as $hora): ?>
                                <option value="<?= $hora ?>"><?= $hora ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-ssos-turquesa">Agendar</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
