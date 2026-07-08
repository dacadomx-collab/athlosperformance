<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';

require_role('coach', 'super_admin');

$db = ssos_db();
$id_staff = $_SESSION['id_staff'] ?? null;

$filtro_staff_sql = $id_staff !== null ? 'AND da.id_staff = :id_staff' : '';

$stmt = $db->prepare(
    "SELECT da.id_cita, da.hora_inicio, a.id_atleta, a.nombre_completo,
            (SELECT es.semaforo_general FROM evaluaciones_sft es
             WHERE es.id_atleta = a.id_atleta
             ORDER BY es.fecha_evaluacion DESC LIMIT 1) AS semaforo
     FROM disponibilidad_agenda da
     INNER JOIN atletas a ON a.id_atleta = da.id_atleta
     WHERE da.fecha_cita = CURDATE()
       AND da.estatus_cita IN ('reservada','confirmada')
       {$filtro_staff_sql}
     ORDER BY da.hora_inicio ASC"
);
$stmt->execute($id_staff !== null ? ['id_staff' => $id_staff] : []);
$atletas_del_dia = $stmt->fetchAll();

$ssos_page_title = 'Pie de Cancha';
$ssos_active_nav = 'pie_de_cancha';
require __DIR__ . '/../partials/header.php';
?>

<span class="ssos-role-badge">Coach · Pie de Cancha</span>
<h2 class="mt-3">Atletas del Día</h2>
<p class="text-body-secondary">
    Toca <strong>Iniciar Sesión</strong> sobre un atleta para capturar RPE y el checklist
    de Sentadilla Overhead en menos de 30 segundos.
</p>

<div class="pdc-grid mt-4">
    <?php if (empty($atletas_del_dia)): ?>
        <div class="ssos-table-card text-center text-body-secondary">
            No hay citas confirmadas para hoy<?= $id_staff ? ' asignadas a ti' : '' ?>.
        </div>
    <?php endif; ?>

    <?php foreach ($atletas_del_dia as $cita):
        $semaforo = $cita['semaforo'] ?? 'sin_dato';
    ?>
        <div class="pdc-athlete-card">
            <div>
                <span class="ssos-semaforo ssos-semaforo--<?= e($semaforo) ?>"></span>
                <span class="pdc-athlete-name"><?= e($cita['nombre_completo']) ?></span>
            </div>
            <div class="pdc-athlete-meta">Hora: <?= e(substr((string) $cita['hora_inicio'], 0, 5)) ?></div>
            <a class="pdc-start-btn text-decoration-none d-flex align-items-center justify-content-center"
               href="coach_evaluacion.php?id_atleta=<?= (int) $cita['id_atleta'] ?>&id_cita=<?= (int) $cita['id_cita'] ?>">
                Iniciar Sesión
            </a>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
