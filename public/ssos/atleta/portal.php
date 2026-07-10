<?php
declare(strict_types=1);

/**
 * ATHLOS PERFORMANCE — PORTAL DEL ATLETA (Fase 24, Misión 5)
 *
 * Vista privada exclusiva del rol `atleta`: sólo ve sus propias citas
 * (filtro por `usuarios.id_atleta` de la sesión, nunca por parámetro de URL).
 * Botón de Cancelación Autónoma: cancela con >= 3h de anticipación, la cita
 * pasa a `cancelada_por_cliente` y el slot se libera automáticamente (la
 * matriz administrativa y la Disponibilidad Pública leen el mismo campo
 * `estatus_cita`, así que quedan sincronizadas sin trabajo extra). El equipo
 * ve la alerta visual de la cancelación en agenda/index.php
 * ($cancelacionesClienteRecientes, últimos 7 días).
 *
 * Especificación agnóstica: knowledge/MODULO_CALENDARIO_GENERICO.md
 */

require_once __DIR__ . '/../config/helpers.php';

require_role('atleta');

const PORTAL_CANCELACION_HORAS_MINIMO = 3;

$db = ssos_db();
$idAtletaSesion = $_SESSION['id_atleta'] ?? null;

if (!$idAtletaSesion) {
    http_response_code(403);
    die('Tu cuenta no está vinculada a ningún expediente de atleta. Contacta al equipo de Athlos Performance.');
}

$errores = [];
$mensajeOk = null;

// ── Cancelación autónoma de cita propia ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cancelar_cita_propia') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } else {
        $idCita = filter_input(INPUT_POST, 'id_cita', FILTER_VALIDATE_INT);
        if (!$idCita) {
            $errores[] = 'Cita no válida.';
        } else {
            $stmt = $db->prepare('SELECT * FROM disponibilidad_agenda WHERE id_cita = :id AND id_atleta = :id_atleta');
            $stmt->execute(['id' => $idCita, 'id_atleta' => $idAtletaSesion]);
            $cita = $stmt->fetch();

            if (!$cita) {
                $errores[] = 'Esa cita no pertenece a tu expediente.';
            } elseif (!in_array($cita['estatus_cita'], ['reservada', 'confirmada'], true)) {
                $errores[] = 'Esa cita ya no se puede cancelar (estatus actual: ' . $cita['estatus_cita'] . ').';
            } else {
                $citaDatetime = new DateTimeImmutable("{$cita['fecha_cita']} {$cita['hora_inicio']}");
                $horasRestantes = ($citaDatetime->getTimestamp() - time()) / 3600;
                if ($horasRestantes < PORTAL_CANCELACION_HORAS_MINIMO) {
                    $errores[] = 'No se puede cancelar con menos de ' . PORTAL_CANCELACION_HORAS_MINIMO . ' horas de anticipación. Contacta directamente al laboratorio.';
                } else {
                    $db->prepare("UPDATE disponibilidad_agenda SET estatus_cita = 'cancelada_por_cliente' WHERE id_cita = :id")
                        ->execute(['id' => $idCita]);
                    $mensajeOk = 'Tu cita fue cancelada. El espacio ya quedó disponible para otros atletas.';
                }
            }
        }
    }
}

$stmtAtleta = $db->prepare('SELECT nombre_completo FROM atletas WHERE id_atleta = :id');
$stmtAtleta->execute(['id' => $idAtletaSesion]);
$nombreAtleta = (string) ($stmtAtleta->fetchColumn() ?: $_SESSION['nombre_completo']);

$stmtCitas = $db->prepare(
    "SELECT da.id_cita, da.fecha_cita, da.hora_inicio, da.estatus_cita,
            s.nombre_completo AS staff_nombre, cs.nombre_servicio
     FROM disponibilidad_agenda da
     INNER JOIN staff s ON s.id_staff = da.id_staff
     INNER JOIN catalogo_servicios cs ON cs.id_servicio = da.id_servicio
     WHERE da.id_atleta = :id_atleta
     ORDER BY da.fecha_cita DESC, da.hora_inicio DESC"
);
$stmtCitas->execute(['id_atleta' => $idAtletaSesion]);
$misCitas = $stmtCitas->fetchAll();

$ahora = new DateTimeImmutable();

$ssos_page_title = 'Mi Agenda';
$ssos_active_nav = 'atleta_portal';
require __DIR__ . '/../partials/header.php';
?>

<h2 class="mb-1">Hola, <?= e($nombreAtleta) ?> 👋</h2>
<p class="text-body-secondary">Estas son tus citas en Athlos Performance. Puedes cancelar con al menos <?= PORTAL_CANCELACION_HORAS_MINIMO ?> horas de anticipación.</p>

<?php if ($mensajeOk): ?>
    <div class="alert alert-success ssos-alert" role="alert"><?= e($mensajeOk) ?></div>
<?php endif; ?>
<?php foreach ($errores as $error): ?>
    <div class="alert alert-danger ssos-alert" role="alert"><?= e($error) ?></div>
<?php endforeach; ?>

<?php if (empty($misCitas)): ?>
    <p class="text-body-secondary">Aún no tienes citas registradas.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Fecha</th><th>Hora</th><th>Especialista</th><th>Servicio</th><th>Estatus</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($misCitas as $cita):
                    $citaDatetime = new DateTimeImmutable("{$cita['fecha_cita']} {$cita['hora_inicio']}");
                    $horasRestantes = ($citaDatetime->getTimestamp() - $ahora->getTimestamp()) / 3600;
                    $sePuedeCancelar = in_array($cita['estatus_cita'], ['reservada', 'confirmada'], true) && $horasRestantes >= PORTAL_CANCELACION_HORAS_MINIMO;
                    $etiquetaEstatus = [
                        'reservada' => 'Reservada',
                        'confirmada' => 'Confirmada',
                        'completada' => 'Completada',
                        'cancelada' => 'Cancelada',
                        'no_show' => 'No asistió',
                        'pendiente_aprobacion' => 'Pendiente de aprobación',
                        'cancelada_por_cliente' => 'Cancelada por ti',
                    ][$cita['estatus_cita']] ?? $cita['estatus_cita'];
                    ?>
                    <tr>
                        <td><?= e($cita['fecha_cita']) ?></td>
                        <td><?= e(substr($cita['hora_inicio'], 0, 5)) ?></td>
                        <td><?= e($cita['staff_nombre']) ?></td>
                        <td><?= e($cita['nombre_servicio']) ?></td>
                        <td><?= e($etiquetaEstatus) ?></td>
                        <td>
                            <?php if ($sePuedeCancelar): ?>
                                <form method="post" onsubmit="return confirm('¿Cancelar esta cita?');">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="accion" value="cancelar_cita_propia">
                                    <input type="hidden" name="id_cita" value="<?= (int) $cita['id_cita'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Cancelar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
