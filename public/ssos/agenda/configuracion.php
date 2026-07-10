<?php
declare(strict_types=1);

/**
 * ATHLOS PERFORMANCE — PANEL DE CONFIGURACIÓN DINÁMICA DE AGENDA
 * (Admin + Dirección de Laboratorio, Fase 24)
 *
 * Días/horarios operativos, aforo máximo, bloqueos de coach/laboratorio y
 * credenciales de sincronización externa — todo lo que antes vivía
 * hardcodeado en AgendaBusinessRules. Ver knowledge/MODULO_CALENDARIO_GENERICO.md
 * para la especificación agnóstica de esta "matriz de configuración dinámica".
 */

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/AgendaBusinessRules.php';

require_role('admin', 'super_admin');

$db = ssos_db();

$errores = [];
$mensajeOk = null;

const DIAS_LABEL_CONFIG = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

// ── Guardar días/horarios operativos ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'guardar_horarios') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } else {
        try {
            $db->beginTransaction();
            foreach (range(1, 7) as $dia) {
                $activo = isset($_POST["dia_activo_{$dia}"]) ? 1 : 0;
                $apertura = (string) ($_POST["dia_apertura_{$dia}"] ?? '06:00');
                $cierre = (string) ($_POST["dia_cierre_{$dia}"] ?? '22:00');

                if (!preg_match('/^\d{2}:\d{2}$/', $apertura) || !preg_match('/^\d{2}:\d{2}$/', $cierre) || $apertura >= $cierre) {
                    continue; // fila inválida — se ignora en vez de guardar un horario roto
                }

                $stmt = $db->prepare(
                    'INSERT INTO agenda_disponibilidad (dia_semana, hora_apertura, hora_cierre, activo)
                     VALUES (:dia, :apertura, :cierre, :activo)
                     ON DUPLICATE KEY UPDATE hora_apertura = VALUES(hora_apertura), hora_cierre = VALUES(hora_cierre), activo = VALUES(activo)'
                );
                $stmt->execute(['dia' => $dia, 'apertura' => $apertura . ':00', 'cierre' => $cierre . ':00', 'activo' => $activo]);
            }
            $db->commit();
            $mensajeOk = 'Horarios actualizados. Los cambios ya aplican en la matriz de la Agenda.';
        } catch (\Throwable $e) {
            $db->rollBack();
            $errores[] = 'No se pudieron guardar los horarios: ' . $e->getMessage();
            error_log('[SSOS configuracion_agenda horarios] ' . $e->getMessage());
        }
    }
}

// ── Guardar aforo máximo ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'guardar_aforo') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } else {
        $aforo = filter_input(INPUT_POST, 'cupo_maximo_franja', FILTER_VALIDATE_INT);
        if (!$aforo || $aforo < 1 || $aforo > 50) {
            $errores[] = 'El aforo debe ser un número entre 1 y 50.';
        } else {
            $db->prepare('INSERT INTO agenda_configuracion (clave, valor) VALUES (\'cupo_maximo_franja\', :v) ON DUPLICATE KEY UPDATE valor = :v')
                ->execute(['v' => (string) $aforo]);
            $mensajeOk = "Aforo máximo actualizado a {$aforo} personas por franja. Los semáforos ya reflejan el nuevo límite.";
        }
    }
}

// ── Alta de bloqueo (coach o laboratorio completo) ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear_bloqueo') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } else {
        $idStaffBloqueo = filter_input(INPUT_POST, 'id_staff', FILTER_VALIDATE_INT) ?: null;
        $fechaInicioBloqueo = (string) ($_POST['fecha_inicio'] ?? '');
        $fechaFinBloqueo = (string) ($_POST['fecha_fin'] ?? '');
        $motivoBloqueo = trim((string) ($_POST['motivo'] ?? ''));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicioBloqueo) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFinBloqueo)) {
            $errores[] = 'Fechas de bloqueo inválidas.';
        } elseif ($fechaInicioBloqueo > $fechaFinBloqueo) {
            $errores[] = 'La fecha de inicio no puede ser posterior a la fecha de fin.';
        } else {
            $db->prepare(
                'INSERT INTO agenda_bloqueos (id_staff, fecha_inicio, fecha_fin, motivo, creado_por)
                 VALUES (:id_staff, :inicio, :fin, :motivo, :creado_por)'
            )->execute([
                'id_staff' => $idStaffBloqueo,
                'inicio' => $fechaInicioBloqueo . ' 00:00:00',
                'fin' => $fechaFinBloqueo . ' 23:59:59',
                'motivo' => $motivoBloqueo !== '' ? $motivoBloqueo : null,
                'creado_por' => $_SESSION['id_usuario'],
            ]);
            $mensajeOk = 'Bloqueo registrado. La matriz de la Agenda ya lo refleja.';
        }
    }
}

// ── Eliminar bloqueo ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar_bloqueo') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } else {
        $idBloqueo = filter_input(INPUT_POST, 'id_bloqueo', FILTER_VALIDATE_INT);
        if ($idBloqueo) {
            $db->prepare('DELETE FROM agenda_bloqueos WHERE id_bloqueo = :id')->execute(['id' => $idBloqueo]);
            $mensajeOk = 'Bloqueo eliminado.';
        }
    }
}

// ── Guardar credenciales de sincronización (Google OAuth) ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'guardar_sincronizacion') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } else {
        $clientId = trim((string) ($_POST['google_oauth_client_id'] ?? ''));
        $clientSecret = trim((string) ($_POST['google_oauth_client_secret'] ?? ''));

        $db->prepare('INSERT INTO agenda_configuracion (clave, valor) VALUES (\'google_oauth_client_id\', :v) ON DUPLICATE KEY UPDATE valor = :v')
            ->execute(['v' => $clientId]);

        // El secret sólo se sobreescribe si el admin escribió uno nuevo — un
        // campo vacío en el submit significa "no lo toques", nunca "bórralo",
        // porque el valor mostrado en pantalla siempre está enmascarado.
        if ($clientSecret !== '') {
            $db->prepare('INSERT INTO agenda_configuracion (clave, valor) VALUES (\'google_oauth_client_secret\', :v) ON DUPLICATE KEY UPDATE valor = :v')
                ->execute(['v' => ssos_encriptar($clientSecret)]);
        }
        $mensajeOk = 'Credenciales de sincronización guardadas.';
    }
}

// ── Datos para la vista ──────────────────────────────────────────────────
$horariosActuales = [];
foreach ($db->query('SELECT dia_semana, hora_apertura, hora_cierre, activo FROM agenda_disponibilidad')->fetchAll() as $h) {
    $horariosActuales[(int) $h['dia_semana']] = $h;
}
$cupoActual = AgendaBusinessRules::cupoMaximoFranja($db);

$staffList = $db->query('SELECT id_staff, nombre_completo FROM staff WHERE activo = 1 ORDER BY nombre_completo')->fetchAll();

$bloqueosActuales = $db->query(
    "SELECT b.id_bloqueo, b.id_staff, b.fecha_inicio, b.fecha_fin, b.motivo, s.nombre_completo AS staff_nombre
     FROM agenda_bloqueos b
     LEFT JOIN staff s ON s.id_staff = b.id_staff
     WHERE b.fecha_fin >= NOW()
     ORDER BY b.fecha_inicio"
)->fetchAll();

$configuracionActual = [];
foreach ($db->query("SELECT clave, valor FROM agenda_configuracion WHERE clave LIKE 'google_oauth%'")->fetchAll() as $c) {
    $configuracionActual[$c['clave']] = $c['valor'];
}
$googleClientIdActual = $configuracionActual['google_oauth_client_id'] ?? '';
$googleClientSecretConfigurado = !empty($configuracionActual['google_oauth_client_secret']);

// Feed webcal de ejemplo — el token real por coach se genera desde
// sincronizacion_tokens (todavía sin UI dedicada, ver Roadmap).
$urlFeedEjemplo = ssos_base_url() . '/agenda/feed.php?token={webcal_uid del coach}';

$ssos_page_title = 'Configuración de Agenda';
$ssos_active_nav = 'agenda';
require __DIR__ . '/../partials/header.php';
?>

<span class="ssos-role-badge">Configuración de Agenda</span>
<h2 class="mt-3">Panel de Configuración Dinámica</h2>
<p class="text-body-secondary">
    Cambios aquí aplican de inmediato en la matriz de la Agenda — sin migraciones ni cambios de código.
</p>

<?php if ($mensajeOk): ?>
    <div class="alert alert-success ssos-alert" role="alert"><?= e($mensajeOk) ?></div>
<?php endif; ?>
<?php foreach ($errores as $error): ?>
    <div class="alert alert-danger ssos-alert" role="alert"><?= e($error) ?></div>
<?php endforeach; ?>

<!-- ══ 1-2. Días de trabajo + horarios por día ══ -->
<div class="ssos-table-card mb-4">
    <h5 class="mb-3">Días y Horarios Operativos</h5>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="accion" value="guardar_horarios">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>Día</th><th>Activo</th><th>Apertura</th><th>Cierre</th></tr>
                </thead>
                <tbody>
                    <?php foreach (DIAS_LABEL_CONFIG as $diaIso => $label): ?>
                        <?php $h = $horariosActuales[$diaIso] ?? null; ?>
                        <tr>
                            <td><?= e($label) ?></td>
                            <td>
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" name="dia_activo_<?= $diaIso ?>" value="1" <?= ($h && (int) $h['activo'] === 1) ? 'checked' : '' ?>>
                                </div>
                            </td>
                            <td><input type="time" class="form-control" name="dia_apertura_<?= $diaIso ?>" value="<?= e($h ? substr($h['hora_apertura'], 0, 5) : '06:00') ?>"></td>
                            <td><input type="time" class="form-control" name="dia_cierre_<?= $diaIso ?>" value="<?= e($h ? substr($h['hora_cierre'], 0, 5) : '22:00') ?>"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <button type="submit" class="btn btn-ssos-primary">Guardar Horarios</button>
    </form>
</div>

<!-- ══ 3. Aforo máximo ══ -->
<div class="ssos-table-card mb-4">
    <h5 class="mb-3">Aforo Máximo por Franja</h5>
    <form method="post" class="row g-2 align-items-end">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="accion" value="guardar_aforo">
        <div class="col-sm-3">
            <label class="form-label">Personas máximas por hora</label>
            <input type="number" class="form-control" name="cupo_maximo_franja" min="1" max="50" value="<?= (int) $cupoActual ?>" required>
        </div>
        <div class="col-sm-3">
            <button type="submit" class="btn btn-ssos-primary">Guardar Aforo</button>
        </div>
    </form>
</div>

<!-- ══ 4. Bloqueos de staff / laboratorio ══ -->
<div class="ssos-table-card mb-4">
    <h5 class="mb-3">Inasistencias y Bloqueos</h5>
    <p class="text-body-secondary">Marca a un coach como no disponible (vacaciones, incapacidad) o bloquea todo el laboratorio (festivo, mantenimiento) dejando "Especialista" en "Todo el laboratorio".</p>
    <form method="post" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="accion" value="crear_bloqueo">
        <div class="col-sm-3">
            <label class="form-label">Especialista</label>
            <select name="id_staff" class="form-select">
                <option value="">Todo el laboratorio</option>
                <?php foreach ($staffList as $s): ?>
                    <option value="<?= (int) $s['id_staff'] ?>"><?= e($s['nombre_completo']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-2">
            <label class="form-label">Desde</label>
            <input type="date" class="form-control" name="fecha_inicio" required>
        </div>
        <div class="col-sm-2">
            <label class="form-label">Hasta</label>
            <input type="date" class="form-control" name="fecha_fin" required>
        </div>
        <div class="col-sm-3">
            <label class="form-label">Motivo</label>
            <input type="text" class="form-control" name="motivo" placeholder="Ej. Vacaciones">
        </div>
        <div class="col-sm-2">
            <button type="submit" class="btn btn-ssos-primary">Bloquear</button>
        </div>
    </form>

    <?php if (empty($bloqueosActuales)): ?>
        <p class="text-body-secondary mb-0">Sin bloqueos vigentes o futuros.</p>
    <?php else: ?>
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Alcance</th><th>Desde</th><th>Hasta</th><th>Motivo</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($bloqueosActuales as $b): ?>
                    <tr>
                        <td><?= $b['id_staff'] ? e($b['staff_nombre']) : '🔒 Todo el laboratorio' ?></td>
                        <td><?= e(substr($b['fecha_inicio'], 0, 10)) ?></td>
                        <td><?= e(substr($b['fecha_fin'], 0, 10)) ?></td>
                        <td><?= e($b['motivo'] ?? '—') ?></td>
                        <td>
                            <form method="post" onsubmit="return confirm('¿Eliminar este bloqueo?');">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="accion" value="eliminar_bloqueo">
                                <input type="hidden" name="id_bloqueo" value="<?= (int) $b['id_bloqueo'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- ══ 5. Credenciales de sincronización ══ -->
<div class="ssos-table-card mb-4">
    <h5 class="mb-3">Sincronización con Calendarios Externos</h5>

    <h6 class="mt-3">Google Calendar (OAuth2)</h6>
    <p class="text-body-secondary small">
        Guarda aquí las credenciales del proyecto de Google Cloud Console. <strong>Nota honesta:</strong>
        esta versión guarda las credenciales de forma segura (Client Secret cifrado en la base de datos)
        pero todavía no ejecuta el flujo OAuth ni los webhooks push — eso requiere configurar el proyecto
        real en Google Cloud con el dominio de producción, fuera del alcance de este entorno de
        desarrollo. Ver protocolo completo en <code>knowledge/MODULO_CALENDARIO_GENERICO.md</code> §5.1.
    </p>
    <form method="post" class="row g-2 align-items-end">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="accion" value="guardar_sincronizacion">
        <div class="col-sm-6">
            <label class="form-label">Client ID</label>
            <input type="text" class="form-control" name="google_oauth_client_id" value="<?= e($googleClientIdActual) ?>" placeholder="xxxxx.apps.googleusercontent.com">
        </div>
        <div class="col-sm-6">
            <label class="form-label">Client Secret</label>
            <input type="password" class="form-control" name="google_oauth_client_secret" placeholder="<?= $googleClientSecretConfigurado ? '•••••••• (ya configurado — deja en blanco para no cambiarlo)' : 'GOCSPX-...' ?>">
        </div>
        <div class="col-sm-3 mt-2">
            <button type="submit" class="btn btn-ssos-primary">Guardar Credenciales</button>
        </div>
    </form>

    <h6 class="mt-4">Apple Calendar (Webcal)</h6>
    <p class="text-body-secondary small">
        No requiere credenciales — cada coach obtiene una URL de suscripción de sólo lectura como esta
        (el token real se genera por coach; hoy no hay UI dedicada para generarlo, ver Roadmap):
    </p>
    <code class="d-block ssos-table-card"><?= e($urlFeedEjemplo) ?></code>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
