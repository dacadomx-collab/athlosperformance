<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';

require_role('admin', 'super_admin');

$db = ssos_db();

$clientes_activos = (int) $db->query(
    "SELECT COUNT(*) FROM atletas WHERE estatus = 'activo'"
)->fetchColumn();

$evaluaciones_pendientes = (int) $db->query(
    "SELECT COUNT(*) FROM atletas a
     WHERE a.estatus = 'activo'
       AND NOT EXISTS (
           SELECT 1 FROM evaluaciones_antropometria ea
           WHERE ea.id_atleta = a.id_atleta
             AND ea.fecha_antropometria >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
       )"
)->fetchColumn();

$membresias_por_vencer = (int) $db->query(
    "SELECT COUNT(*) FROM membresias
     WHERE estatus = 'activa'
       AND fecha_fin IS NOT NULL
       AND fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)"
)->fetchColumn();

$ultimos_clientes = $db->query(
    "SELECT id_atleta, nombre_completo, telefono, estatus, tipo_membresia, fecha_ingreso
     FROM atletas
     ORDER BY created_at DESC
     LIMIT 10"
)->fetchAll();

$ssos_page_title = 'Panel Admin (FrontDesk)';
$ssos_active_nav = 'dashboard';
require __DIR__ . '/../partials/header.php';
?>

<span class="ssos-role-badge">Admin · Recepción</span>
<h2 class="mt-3">Bienvenido, <?= e($_SESSION['nombre_completo']) ?></h2>
<p class="text-body-secondary">
    Gestión comercial de clientes, agenda (cupo máximo 4 personas/hora), cobros y
    catálogo de paquetes/membresías.
</p>

<div class="ssos-widget-grid">
    <div class="ssos-widget">
        <div class="ssos-widget-value"><?= (int) $clientes_activos ?></div>
        <div class="ssos-widget-label">Clientes Activos</div>
    </div>
    <div class="ssos-widget">
        <div class="ssos-widget-value"><?= (int) $evaluaciones_pendientes ?></div>
        <div class="ssos-widget-label">Evaluaciones Pendientes</div>
    </div>
    <div class="ssos-widget">
        <div class="ssos-widget-value"><?= (int) $membresias_por_vencer ?></div>
        <div class="ssos-widget-label">Membresías por Vencer (7 días)</div>
    </div>
</div>

<div class="ssos-table-card">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Teléfono</th>
                <th>Membresía</th>
                <th>Estatus</th>
                <th>Ingreso</th>
                <th>Reporte</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($ultimos_clientes)): ?>
                <tr>
                    <td colspan="6" class="text-center text-body-secondary py-4">
                        Aún no hay clientes registrados.
                    </td>
                </tr>
            <?php endif; ?>
            <?php foreach ($ultimos_clientes as $cliente): ?>
                <tr>
                    <td><?= e($cliente['nombre_completo']) ?></td>
                    <td><?= e($cliente['telefono']) ?></td>
                    <td><?= e($cliente['tipo_membresia']) ?></td>
                    <td>
                        <span class="badge text-bg-<?= $cliente['estatus'] === 'activo' ? 'success' : ($cliente['estatus'] === 'suspendido' ? 'warning' : 'secondary') ?>">
                            <?= e($cliente['estatus']) ?>
                        </span>
                    </td>
                    <td><?= e($cliente['fecha_ingreso']) ?></td>
                    <td>
                        <a class="btn btn-sm btn-outline-secondary"
                           href="<?= e(ssos_base_url()) ?>/atleta/reporte.php?token=<?= e(ssos_generate_share_token((int) $cliente['id_atleta'])) ?>"
                           target="_blank" rel="noopener noreferrer">Ver Reporte</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
