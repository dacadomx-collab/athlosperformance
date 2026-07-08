<?php
declare(strict_types=1);

/**
 * ATHLOS SSOS v1.0 — DASHBOARD ÚNICO Y DINÁMICO POR ROL
 *
 * Reemplaza los dashboards fragmentados (super_admin.php/admin.php/coach.php,
 * eliminados). Todos los roles entran aquí tras login(); el contenido se
 * renderiza condicionalmente según `clave_rol`:
 *   - super_admin: TODAS las secciones (Control, Clientes/Membresías, Pie de Cancha).
 *   - admin: Clientes/Membresías + Pie de Cancha.
 *   - coach: sólo Pie de Cancha.
 */

require_once __DIR__ . '/../config/helpers.php';

require_login();

$db = ssos_db();
$rol = $_SESSION['clave_rol'];

$verClientes = in_array($rol, ['admin', 'super_admin'], true);
$verPieDeCancha = in_array($rol, ['coach', 'admin', 'super_admin'], true);
$verControl = $rol === 'super_admin';

// ── Sección Control (sólo Dirección de Laboratorio) ─────────────────────────
if ($verControl) {
    $total_usuarios = (int) $db->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    $total_atletas_control = (int) $db->query('SELECT COUNT(*) FROM atletas')->fetchColumn();
    $eventos_recientes = $db->query(
        'SELECT tipo_evento, email_intento, ip_origen, created_at
         FROM sesiones_log ORDER BY id_log_sesion DESC LIMIT 10'
    )->fetchAll();
    $usuarios_sistema = $db->query(
        'SELECT u.nombre_completo, u.email, r.clave_rol, u.activo, u.ultimo_login
         FROM usuarios u INNER JOIN roles r ON r.id_rol = u.id_rol
         ORDER BY u.created_at DESC'
    )->fetchAll();
}

// ── Sección Clientes y Membresías (Admin + Dirección) ───────────────────────
if ($verClientes) {
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
}

// ── Sección Pie de Cancha (Coach + Admin + Dirección) ───────────────────────
if ($verPieDeCancha) {
    $id_staff = $_SESSION['id_staff'] ?? null;
    $filtro_staff_sql = $id_staff !== null && $rol === 'coach' ? 'AND da.id_staff = :id_staff' : '';

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
    $stmt->execute($id_staff !== null && $rol === 'coach' ? ['id_staff' => $id_staff] : []);
    $atletas_del_dia = $stmt->fetchAll();
}

$etiquetasRol = [
    'super_admin' => 'Dirección de Laboratorio',
    'admin' => 'Administración / Recepción',
    'coach' => 'Coach Especialista',
];

$ssos_page_title = 'Dashboard';
$ssos_active_nav = 'dashboard';
require __DIR__ . '/../partials/header.php';
?>

<span class="ssos-role-badge"><?= e($etiquetasRol[$rol] ?? $rol) ?></span>
<h2 class="mt-3">Bienvenido, <?= e($_SESSION['nombre_completo']) ?></h2>

<?php if ($verControl): ?>
<section id="control" class="ssos-dashboard-section mt-4">
    <h3>Control</h3>
    <p class="text-body-secondary">
        Control absoluto de base de datos, usuarios del sistema y auditoría de seguridad.
    </p>

    <div class="ssos-widget-grid">
        <div class="ssos-widget">
            <div class="ssos-widget-value"><?= $total_usuarios ?></div>
            <div class="ssos-widget-label">Usuarios del BackOffice</div>
        </div>
        <div class="ssos-widget">
            <div class="ssos-widget-value"><?= $total_atletas_control ?></div>
            <div class="ssos-widget-label">Atletas Registrados</div>
        </div>
    </div>

    <div class="ssos-table-card mb-4">
        <h5 class="mb-3">Usuarios del sistema</h5>
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Activo</th>
                    <th>Último acceso</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios_sistema as $u): ?>
                    <tr>
                        <td><?= e($u['nombre_completo']) ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td><?= e($etiquetasRol[$u['clave_rol']] ?? $u['clave_rol']) ?></td>
                        <td><?= $u['activo'] ? 'Sí' : 'No' ?></td>
                        <td><?= e($u['ultimo_login'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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
</section>
<?php endif; ?>

<?php if ($verClientes): ?>
<section id="clientes" class="ssos-dashboard-section mt-5">
    <h3>Clientes y Membresías</h3>
    <p class="text-body-secondary">
        Gestión comercial de clientes, cobros y catálogo de paquetes/membresías.
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
</section>
<?php endif; ?>

<?php if ($verPieDeCancha): ?>
<section id="pie-de-cancha" class="ssos-dashboard-section mt-5">
    <h3>Pie de Cancha — Atletas del Día</h3>
    <p class="text-body-secondary">
        Toca <strong>Iniciar Sesión</strong> sobre un atleta para capturar RPE y el checklist
        de Sentadilla Overhead en menos de 30 segundos.
    </p>

    <div class="pdc-grid mt-4">
        <?php if (empty($atletas_del_dia)): ?>
            <div class="ssos-table-card text-center text-body-secondary">
                No hay citas confirmadas para hoy<?= ($rol === 'coach' && $id_staff) ? ' asignadas a ti' : '' ?>.
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
                <a class="btn btn-sm btn-outline-secondary text-center"
                   href="<?= e(ssos_base_url()) ?>/atleta/reporte.php?token=<?= e(ssos_generate_share_token((int) $cita['id_atleta'])) ?>"
                   target="_blank" rel="noopener noreferrer">Ver Reporte Athlos Score™</a>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
