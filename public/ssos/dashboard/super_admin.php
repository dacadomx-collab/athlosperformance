<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';

require_role('super_admin');

$db = ssos_db();
$total_usuarios = (int) $db->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
$total_atletas = (int) $db->query('SELECT COUNT(*) FROM atletas')->fetchColumn();
$eventos_recientes = $db->query(
    'SELECT tipo_evento, email_intento, ip_origen, created_at
     FROM sesiones_log ORDER BY id_log_sesion DESC LIMIT 10'
)->fetchAll();

$ssos_page_title = 'Panel Super Admin';
$ssos_active_nav = 'dashboard';
require __DIR__ . '/../partials/header.php';
?>

<span class="ssos-role-badge">Super Admin · AXON_DCD</span>
<h2 class="mt-3">Bienvenido, <?= e($_SESSION['nombre_completo']) ?></h2>
<p class="text-body-secondary">
    Control absoluto de base de datos, credenciales API, logs de auditoría de seguridad
    y configuración del motor cognitivo de IA.
</p>

<div class="ssos-widget-grid">
    <div class="ssos-widget">
        <div class="ssos-widget-value"><?= $total_usuarios ?></div>
        <div class="ssos-widget-label">Usuarios del BackOffice</div>
    </div>
    <div class="ssos-widget">
        <div class="ssos-widget-value"><?= $total_atletas ?></div>
        <div class="ssos-widget-label">Atletas Registrados</div>
    </div>
</div>

<div class="ssos-table-card">
    <h5 class="mb-3">Bitácora de sesiones reciente</h5>
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Evento</th>
                <th>Email</th>
                <th>IP</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($eventos_recientes)): ?>
                <tr>
                    <td colspan="4" class="text-center text-body-secondary py-4">Sin actividad registrada.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($eventos_recientes as $evento): ?>
                <tr>
                    <td><?= e($evento['tipo_evento']) ?></td>
                    <td><?= e($evento['email_intento']) ?></td>
                    <td><?= e($evento['ip_origen']) ?></td>
                    <td><?= e($evento['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<p class="text-body-secondary fst-italic mt-4">
    Gestión de usuarios/roles, auditoría médica y configuración del motor IA pendientes
    de implementación en una siguiente fase.
</p>

<?php require __DIR__ . '/../partials/footer.php'; ?>
