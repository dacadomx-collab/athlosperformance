<?php
declare(strict_types=1);

/**
 * ATHLOS PERFORMANCE — EXPEDIENTE CLÍNICO DIGITAL DEL ATLETA
 *
 * Hub para Coach/Administración/Dirección: datos del atleta, timeline
 * cronológico de evaluaciones (antropometría, SFT, biomecánica) y accesos a
 * los formularios de captura + reporte público del Athlos Score™.
 *
 * A diferencia de reporte.php (público, protegido por token HMAC), esta vista
 * requiere sesión de staff — contiene acciones de escritura, no sólo lectura.
 */

require_once __DIR__ . '/../config/helpers.php';

require_role('coach', 'admin', 'super_admin');

$db = ssos_db();

$id_atleta = filter_input(INPUT_GET, 'id_atleta', FILTER_VALIDATE_INT) ?: null;
if ($id_atleta === null) {
    http_response_code(400);
    die('Falta id_atleta.');
}

$stmt = $db->prepare('SELECT * FROM atletas WHERE id_atleta = :id');
$stmt->execute(['id' => $id_atleta]);
$atleta = $stmt->fetch();

if (!$atleta) {
    http_response_code(404);
    die('Atleta no encontrado.');
}

$stmt = $db->prepare('SELECT * FROM historial_clinico WHERE id_atleta = :id');
$stmt->execute(['id' => $id_atleta]);
$historial = $stmt->fetch();

$evalAntropometria = $db->prepare(
    'SELECT id_evaluacion, fecha_antropometria AS fecha, peso_kg, estatura_cm, imc, clasificacion_imc
     FROM evaluaciones_antropometria WHERE id_atleta = :id ORDER BY fecha_antropometria DESC'
);
$evalAntropometria->execute(['id' => $id_atleta]);
$antropometrias = $evalAntropometria->fetchAll();

$evalSft = $db->prepare(
    'SELECT id_evaluacion_sft, fecha_evaluacion AS fecha, semaforo_general
     FROM evaluaciones_sft WHERE id_atleta = :id ORDER BY fecha_evaluacion DESC'
);
$evalSft->execute(['id' => $id_atleta]);
$sfts = $evalSft->fetchAll();

$evalBiomecanica = $db->prepare(
    'SELECT id_evaluacion_biomecanica, fecha_evaluacion AS fecha
     FROM evaluaciones_biomecanica WHERE id_atleta = :id ORDER BY fecha_evaluacion DESC'
);
$evalBiomecanica->execute(['id' => $id_atleta]);
$biomecanicas = $evalBiomecanica->fetchAll();

// ── Timeline unificado (ordenado cronológicamente, más reciente primero) ────
$timeline = [];
foreach ($antropometrias as $e) {
    $timeline[] = ['fecha' => $e['fecha'], 'tipo' => 'Antropometría', 'icono' => '📏', 'detalle' => 'IMC ' . ($e['imc'] ?? '—')];
}
foreach ($sfts as $e) {
    $timeline[] = ['fecha' => $e['fecha'], 'tipo' => 'Senior Fitness Test', 'icono' => '🏃', 'detalle' => 'Semáforo: ' . ($e['semaforo_general'] ?? 'sin calcular')];
}
foreach ($biomecanicas as $e) {
    $timeline[] = ['fecha' => $e['fecha'], 'tipo' => 'Biomecánica (Sentadilla Overhead)', 'icono' => '🦵', 'detalle' => 'Checklist registrado'];
}
usort($timeline, static fn ($a, $b) => strcmp($b['fecha'], $a['fecha']));

$esMayor65 = false;
if (!empty($atleta['fecha_nacimiento'])) {
    $edad = (new DateTimeImmutable($atleta['fecha_nacimiento']))->diff(new DateTimeImmutable())->y;
    $esMayor65 = $edad >= 65;
}

$ssos_page_title = 'Expediente · ' . $atleta['nombre_completo'];
$ssos_active_nav = 'clientes';
require __DIR__ . '/../partials/header.php';
?>

<span class="ssos-role-badge">Expediente Clínico Digital</span>
<h2 class="mt-3"><?= e($atleta['nombre_completo']) ?></h2>
<p class="text-body-secondary">
    <?= e($atleta['telefono']) ?><?= $atleta['email'] ? ' · ' . e($atleta['email']) : '' ?>
    <?php if (!empty($atleta['fecha_nacimiento'])): ?>
        · <?= (int) $edad ?> años<?= $esMayor65 ? ' (Senior — SFT aplicable)' : '' ?>
    <?php endif; ?>
</p>

<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="historial_form.php?id_atleta=<?= $id_atleta ?>" class="btn btn-ssos-primary">📋 Historial Clínico</a>
    <a href="antropometria_form.php?id_atleta=<?= $id_atleta ?>" class="btn btn-ssos-primary">📏 Nueva Antropometría</a>
    <?php if ($esMayor65): ?>
        <a href="sft_form.php?id_atleta=<?= $id_atleta ?>" class="btn btn-ssos-primary">🏃 Nuevo Senior Fitness Test</a>
    <?php endif; ?>
    <a href="<?= e(ssos_base_url()) ?>/atleta/reporte.php?token=<?= e(ssos_generate_share_token($id_atleta)) ?>"
       class="btn btn-ssos-turquesa" target="_blank" rel="noopener noreferrer">
        📄 Generar Reporte Athlos Score™ (PDF / Impresión)
    </a>
</div>

<div class="ssos-table-card mb-4">
    <h5 class="mb-3">Historial Clínico</h5>
    <?php if (!$historial): ?>
        <p class="text-body-secondary mb-0">Sin historial clínico capturado todavía. Usa el botón "Historial Clínico" arriba.</p>
    <?php else: ?>
        <div class="row">
            <div class="col-sm-6"><strong>Tipo:</strong> <?= $historial['tipo_historial'] === 'mayor_65' ? 'Adulto Mayor (65+)' : 'Menor de 65 años' ?></div>
            <div class="col-sm-6"><strong>Última captura:</strong> <?= e($historial['fecha_captura']) ?></div>
            <div class="col-sm-6"><strong>Condición crónica:</strong> <?= e($historial['condicion_cronica'] ?: 'Ninguna reportada') ?></div>
            <div class="col-sm-6"><strong>Medicamentos:</strong> <?= e($historial['medicamentos_actuales'] ?: 'Ninguno reportado') ?></div>
        </div>
    <?php endif; ?>
</div>

<div class="ssos-table-card">
    <h5 class="mb-3">Timeline de Evaluaciones</h5>
    <?php if (empty($timeline)): ?>
        <p class="text-body-secondary mb-0">Aún no hay evaluaciones registradas para este atleta.</p>
    <?php else: ?>
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($timeline as $item): ?>
                    <tr>
                        <td><?= e($item['fecha']) ?></td>
                        <td><?= $item['icono'] ?> <?= e($item['tipo']) ?></td>
                        <td><?= e($item['detalle']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
