<?php
declare(strict_types=1);

/**
 * ATHLOS SSOS v1.0 — DASHBOARD ÚNICO Y DINÁMICO POR ROL (NAVEGACIÓN POR PESTAÑAS)
 *
 * Reemplaza los dashboards fragmentados (super_admin.php/admin.php/coach.php,
 * eliminados en la Fase 7). El contenido se organiza en 4 pestañas Bootstrap,
 * cada una visible sólo si el rol de la sesión la tiene autorizada:
 *   - Dirección y Control (super_admin)
 *   - Clientes y Membresías (admin + super_admin)
 *   - Pie de Cancha (coach + admin + super_admin)
 *   - Herramientas & API (super_admin)
 */

require_once __DIR__ . '/../config/helpers.php';

require_login();

$db = ssos_db();
$rol = $_SESSION['clave_rol'];

$verClientes = in_array($rol, ['admin', 'super_admin'], true);
$verPieDeCancha = in_array($rol, ['coach', 'admin', 'super_admin'], true);
$verControl = $rol === 'super_admin';
$verHerramientas = $rol === 'super_admin';

// ── Alta de usuarios del staff (Coach/Administración), sólo Dirección ───────
$erroresUsuarioNuevo = [];
$usuarioNuevoCreado = false;

if ($verControl && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear_usuario') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $erroresUsuarioNuevo[] = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } else {
        $nombreNuevo = trim((string) ($_POST['nombre_completo'] ?? ''));
        $emailNuevo = trim((string) ($_POST['email'] ?? ''));
        $rolNuevo = (string) ($_POST['rol_nuevo'] ?? '');
        $passwordNuevo = (string) ($_POST['password'] ?? '');
        $especialidadNueva = trim((string) ($_POST['especialidad'] ?? ''));

        if ($nombreNuevo === '' || mb_strlen($nombreNuevo) > 150) {
            $erroresUsuarioNuevo[] = 'El nombre es obligatorio (máximo 150 caracteres).';
        }
        if (!filter_var($emailNuevo, FILTER_VALIDATE_EMAIL)) {
            $erroresUsuarioNuevo[] = 'El correo no es válido.';
        }
        if (!in_array($rolNuevo, ['coach', 'admin'], true)) {
            $erroresUsuarioNuevo[] = 'Selecciona un rol válido (Coach o Administración).';
        }
        if (mb_strlen($passwordNuevo) < 8) {
            $erroresUsuarioNuevo[] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if ($rolNuevo === 'coach' && $especialidadNueva === '') {
            $erroresUsuarioNuevo[] = 'La especialidad es obligatoria para cuentas de Coach.';
        }

        if (empty($erroresUsuarioNuevo)) {
            try {
                $db->beginTransaction();

                $stmt = $db->prepare('SELECT id_usuario FROM usuarios WHERE email = :email LIMIT 1');
                $stmt->execute(['email' => $emailNuevo]);
                if ($stmt->fetch()) {
                    throw new \RuntimeException('Ya existe un usuario con ese correo.');
                }

                $idStaffNuevo = null;
                if ($rolNuevo === 'coach') {
                    $stmt = $db->prepare(
                        'INSERT INTO staff (nombre_completo, especialidad, email, activo) VALUES (:nombre, :especialidad, :email, 1)'
                    );
                    $stmt->execute(['nombre' => $nombreNuevo, 'especialidad' => $especialidadNueva, 'email' => $emailNuevo]);
                    $idStaffNuevo = (int) $db->lastInsertId();
                }

                $stmt = $db->prepare('SELECT id_rol FROM roles WHERE clave_rol = :clave');
                $stmt->execute(['clave' => $rolNuevo]);
                $idRolNuevo = $stmt->fetchColumn();

                $stmt = $db->prepare(
                    'INSERT INTO usuarios (id_rol, id_staff, nombre_completo, email, password_hash, activo, requiere_cambio_password)
                     VALUES (:id_rol, :id_staff, :nombre, :email, :hash, 1, 1)'
                );
                $stmt->execute([
                    'id_rol' => $idRolNuevo,
                    'id_staff' => $idStaffNuevo,
                    'nombre' => $nombreNuevo,
                    'email' => $emailNuevo,
                    'hash' => password_hash($passwordNuevo, PASSWORD_DEFAULT),
                ]);

                $db->commit();
                $usuarioNuevoCreado = true;
            } catch (\Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                if ($e->getMessage() === 'Ya existe un usuario con ese correo.') {
                    $erroresUsuarioNuevo[] = $e->getMessage();
                } else {
                    $erroresUsuarioNuevo[] = 'No se pudo crear el usuario. Detalle técnico registrado en el log del servidor.';
                    error_log('[SSOS dashboard crear_usuario] ' . $e->getMessage());
                }
            }
        }
    }
}

// ── Tab: Dirección y Control (sólo Dirección de Laboratorio) ────────────────
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

// ── Tab: Clientes y Membresías (Admin + Dirección) ──────────────────────────
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

    // Defensivo: 05_schema_alertas_membresias.sql pudo no haberse aplicado aún
    // en este servidor. Ante tabla faltante, mostramos un mensaje amigable en
    // vez de tumbar todo el tab con un error 500 por una migración pendiente.
    $alertas_disponibles = true;
    try {
        $alertas_renovacion_activas = (int) $db->query(
            "SELECT COUNT(*) FROM alertas_renovacion WHERE atendida = 0"
        )->fetchColumn();
    } catch (\Throwable) {
        $alertas_disponibles = false;
        $alertas_renovacion_activas = 0;
    }

    $ultimos_clientes = $db->query(
        "SELECT id_atleta, nombre_completo, telefono, estatus, tipo_membresia, fecha_ingreso
         FROM atletas
         ORDER BY created_at DESC
         LIMIT 10"
    )->fetchAll();
}

// ── Tab: Pie de Cancha (Coach + Admin + Dirección) ──────────────────────────
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

// ── Tab: Herramientas & API (sólo Dirección de Laboratorio) ─────────────────
if ($verHerramientas) {
    try {
        $db_host_actual = (string) $db->query('SELECT @@hostname')->fetchColumn();
    } catch (\Throwable) {
        $db_host_actual = 'No disponible';
    }
}

$etiquetasRol = [
    'super_admin' => 'Dirección de Laboratorio',
    'admin' => 'Administración / Recepción',
    'coach' => 'Coach Especialista',
];

// ── Orden de pestañas visibles + cuál queda activa por defecto ──────────────
$tabsDisponibles = [];
if ($verControl) {
    $tabsDisponibles[] = ['id' => 'control', 'icono' => '📊', 'label' => 'Dirección y Control'];
}
if ($verClientes) {
    $tabsDisponibles[] = ['id' => 'clientes', 'icono' => '👥', 'label' => 'Clientes y Membresías'];
}
if ($verPieDeCancha) {
    $tabsDisponibles[] = ['id' => 'pie-de-cancha', 'icono' => '🏋️‍♂️', 'label' => 'Sesiones del Día'];
}
if ($verHerramientas) {
    $tabsDisponibles[] = ['id' => 'herramientas', 'icono' => '🛠️', 'label' => 'Herramientas & API'];
}
$tabActivaPorDefecto = $tabsDisponibles[0]['id'] ?? 'pie-de-cancha';

$ssos_page_title = 'Dashboard';
$ssos_active_nav = 'dashboard';
require __DIR__ . '/../partials/header.php';
?>

<span class="ssos-role-badge"><?= e($etiquetasRol[$rol] ?? $rol) ?></span>
<h2 class="mt-3">Bienvenido, <?= e($_SESSION['nombre_completo']) ?></h2>

<ul class="nav nav-tabs ssos-tabs" id="ssosTabList" role="tablist">
    <?php foreach ($tabsDisponibles as $tab): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $tab['id'] === $tabActivaPorDefecto ? 'active' : '' ?>"
                    id="tab-btn-<?= e($tab['id']) ?>" data-bs-toggle="tab"
                    data-bs-target="#pane-<?= e($tab['id']) ?>" type="button" role="tab"
                    aria-controls="pane-<?= e($tab['id']) ?>"
                    aria-selected="<?= $tab['id'] === $tabActivaPorDefecto ? 'true' : 'false' ?>">
                <?= $tab['icono'] ?> <?= e($tab['label']) ?>
            </button>
        </li>
    <?php endforeach; ?>
</ul>

<div class="tab-content" id="ssosTabContent">

<?php if ($verControl): ?>
<div class="tab-pane fade ssos-tab-pane <?= 'control' === $tabActivaPorDefecto ? 'show active' : '' ?>"
     id="pane-control" role="tabpanel" aria-labelledby="tab-btn-control">

    <p class="text-body-secondary">
        Control absoluto de base de datos, usuarios del sistema y auditoría de seguridad.
    </p>

    <?php if ($usuarioNuevoCreado): ?>
        <div class="alert alert-success ssos-alert" role="alert">Usuario creado exitosamente.</div>
    <?php endif; ?>
    <?php foreach ($erroresUsuarioNuevo as $errorUsuario): ?>
        <div class="alert alert-danger ssos-alert" role="alert"><?= e($errorUsuario) ?></div>
    <?php endforeach; ?>

    <div class="d-flex flex-wrap gap-2 mb-4">
        <button type="button" class="btn btn-ssos-turquesa" data-bs-toggle="modal" data-bs-target="#modalNuevoUsuario">
            + Nuevo Usuario del Staff
        </button>
    </div>

    <div class="ssos-widget-grid">
        <div class="ssos-widget card shadow-sm border-0">
            <div class="card-body">
                <div class="ssos-widget-value"><?= $total_usuarios ?></div>
                <div class="ssos-widget-label">Usuarios del BackOffice</div>
            </div>
        </div>
        <div class="ssos-widget card shadow-sm border-0">
            <div class="card-body">
                <div class="ssos-widget-value"><?= $total_atletas_control ?></div>
                <div class="ssos-widget-label">Atletas Registrados</div>
            </div>
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

    <div class="modal fade" id="modalNuevoUsuario" tabindex="-1" aria-labelledby="modalNuevoUsuarioLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="accion" value="crear_usuario">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNuevoUsuarioLabel">Nuevo Usuario del Staff</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nuevo_nombre" class="form-label">Nombre completo</label>
                        <input type="text" class="form-control" id="nuevo_nombre" name="nombre_completo" maxlength="150" required>
                    </div>
                    <div class="mb-3">
                        <label for="nuevo_email" class="form-label">Correo</label>
                        <input type="email" class="form-control" id="nuevo_email" name="email" maxlength="150" required>
                    </div>
                    <div class="mb-3">
                        <label for="nuevo_rol" class="form-label">Rol</label>
                        <select class="form-select" id="nuevo_rol" name="rol_nuevo" required>
                            <option value="coach">Coach Especialista</option>
                            <option value="admin">Administración / Recepción</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="nuevo_especialidad" class="form-label">Especialidad (sólo Coach)</label>
                        <input type="text" class="form-control" id="nuevo_especialidad" name="especialidad" maxlength="100" placeholder="Ej. Fuerza y Acondicionamiento">
                    </div>
                    <div class="mb-3">
                        <label for="nuevo_password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="nuevo_password" name="password" minlength="8" required>
                        <div class="form-text">Mínimo 8 caracteres.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-ssos-turquesa">Crear Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($verClientes): ?>
<div class="tab-pane fade ssos-tab-pane <?= 'clientes' === $tabActivaPorDefecto ? 'show active' : '' ?>"
     id="pane-clientes" role="tabpanel" aria-labelledby="tab-btn-clientes">

    <p class="text-body-secondary">
        Gestión comercial de clientes, cobros y catálogo de paquetes/membresías.
    </p>

    <div class="ssos-widget-grid">
        <div class="ssos-widget card shadow-sm border-0">
            <div class="card-body">
                <div class="ssos-widget-value"><?= (int) $clientes_activos ?></div>
                <div class="ssos-widget-label">Clientes Activos</div>
            </div>
        </div>
        <div class="ssos-widget card shadow-sm border-0">
            <div class="card-body">
                <div class="ssos-widget-value"><?= (int) $evaluaciones_pendientes ?></div>
                <div class="ssos-widget-label">Evaluaciones Pendientes</div>
            </div>
        </div>
        <div class="ssos-widget card shadow-sm border-0">
            <div class="card-body">
                <div class="ssos-widget-value"><?= (int) $membresias_por_vencer ?></div>
                <div class="ssos-widget-label">Membresías por Vencer (7 días)</div>
            </div>
        </div>
        <div class="ssos-widget card shadow-sm border-0">
            <div class="card-body">
                <?php if ($alertas_disponibles): ?>
                    <div class="ssos-widget-value"><?= (int) $alertas_renovacion_activas ?></div>
                    <div class="ssos-widget-label">Alertas de Renovación Activas</div>
                <?php else: ?>
                    <div class="ssos-widget-value ssos-widget-value--text">No disponible aún</div>
                    <div class="ssos-widget-label">Alertas de Renovación (falta aplicar migración 05)</div>
                <?php endif; ?>
            </div>
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
</div>
<?php endif; ?>

<?php if ($verPieDeCancha): ?>
<div class="tab-pane fade ssos-tab-pane <?= 'pie-de-cancha' === $tabActivaPorDefecto ? 'show active' : '' ?>"
     id="pane-pie-de-cancha" role="tabpanel" aria-labelledby="tab-btn-pie-de-cancha">

    <h4>Sesiones del Día — Atletas y Pacientes</h4>
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
</div>
<?php endif; ?>

<?php if ($verHerramientas): ?>
<div class="tab-pane fade ssos-tab-pane <?= 'herramientas' === $tabActivaPorDefecto ? 'show active' : '' ?>"
     id="pane-herramientas" role="tabpanel" aria-labelledby="tab-btn-herramientas">

    <p class="text-body-secondary">
        Migración de datos históricos y estado de la integración con sistemas externos (Next.js, bots).
    </p>

    <div class="ssos-table-card mb-4">
        <h5 class="mb-2">Migración de Datos Históricos</h5>
        <p class="text-body-secondary mb-3">
            Vuelca <code>Clientes.xlsx</code> (catálogo de cobranza legacy) a las tablas
            <code>atletas</code>/<code>membresias</code>/<code>pagos_asistencia</code>. Es seguro
            ejecutarla más de una vez con el mismo archivo — los pagos ya importados se detectan
            y se omiten automáticamente.
        </p>
        <a href="<?= e(ssos_base_url()) ?>/admin/migrar_excel.php" class="btn btn-ssos-primary btn-lg">
            📥 Ejecutar Migración Inicial de Clientes.xlsx
        </a>
    </div>

    <div class="ssos-table-card">
        <h5 class="mb-3">Estado de Integración API</h5>
        <table class="table table-hover align-middle mb-0">
            <tbody>
                <tr>
                    <th scope="row">API_WEBHOOK_SECRET</th>
                    <td><code><?= e(ssos_mask_secret($_ENV['API_WEBHOOK_SECRET'] ?? null)) ?></code></td>
                </tr>
                <tr>
                    <th scope="row">HMAC_SECRET (reportes)</th>
                    <td><code><?= e(ssos_mask_secret($_ENV['HMAC_SECRET'] ?? null)) ?></code></td>
                </tr>
                <tr>
                    <th scope="row">Orígenes permitidos (CORS)</th>
                    <td><code><?= e($_ENV['ALLOWED_ORIGINS'] ?? 'No configurado') ?></code></td>
                </tr>
                <tr>
                    <th scope="row">Servidor de base de datos conectado</th>
                    <td><code><?= e($db_host_actual) ?></code></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

</div>

<?php if ($verControl && !empty($erroresUsuarioNuevo)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new bootstrap.Modal(document.getElementById('modalNuevoUsuario')).show();
        });
    </script>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
