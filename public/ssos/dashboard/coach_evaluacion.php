<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/AthlosBusinessRules.php';

require_role('coach', 'super_admin');

$db = ssos_db();

$id_atleta = filter_input(INPUT_GET, 'id_atleta', FILTER_VALIDATE_INT) ?: null;
$id_cita = filter_input(INPUT_GET, 'id_cita', FILTER_VALIDATE_INT) ?: null;

if ($id_atleta === null) {
    http_response_code(400);
    die('Falta id_atleta.');
}

$stmt = $db->prepare('SELECT id_atleta, nombre_completo FROM atletas WHERE id_atleta = :id');
$stmt->execute(['id' => $id_atleta]);
$atleta = $stmt->fetch();

if (!$atleta) {
    http_response_code(404);
    die('Atleta no encontrado.');
}

$errores = [];
$exito = false;
$deduccion = null;

$checklist_items = [
    'feet_flatten'           => 'Pie se aplana / prona',
    'feet_turn_out'          => 'Pies rotados hacia afuera',
    'heel_rises'             => 'Talón se levanta',
    'knees_move_inward'      => 'Rodillas hacia adentro',
    'excessive_forward_lean' => 'Tronco cae adelante',
    'lower_back_arches'      => 'Espalda baja se arquea',
    'lower_back_rounds'      => 'Espalda baja se redondea',
    'arms_fall_forward'      => 'Brazos caen adelante',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } else {
        $rpe = filter_input(INPUT_POST, 'rpe', FILTER_VALIDATE_FLOAT);
        if ($rpe === null || $rpe < 1 || $rpe > 10) {
            $errores[] = 'El RPE debe estar entre 1 y 10.';
        }

        if (empty($errores)) {
            try {
                $db->beginTransaction();

                $cols = array_keys($checklist_items);
                $placeholders = array_map(static fn ($c) => ":{$c}", $cols);
                $stmt = $db->prepare(
                    'INSERT INTO evaluaciones_biomecanica
                        (id_atleta, fecha_evaluacion, ' . implode(', ', $cols) . ', evaluado_por)
                     VALUES
                        (:id_atleta, CURDATE(), ' . implode(', ', $placeholders) . ', :evaluado_por)'
                );
                $params = ['id_atleta' => $id_atleta, 'evaluado_por' => $_SESSION['id_usuario']];
                foreach ($cols as $c) {
                    $params[$c] = isset($_POST[$c]) ? 1 : 0;
                }
                $stmt->execute($params);

                // El RPE de sesión sólo se registra si el coach tiene ficha de staff
                // ligada (sesiones_entrenamiento.id_staff es NOT NULL por diseño).
                if (!empty($_SESSION['id_staff'])) {
                    $stmt = $db->prepare(
                        'INSERT INTO sesiones_entrenamiento
                            (id_atleta, id_cita, id_staff, fecha_sesion, rpe_sesion, created_by)
                         VALUES (:id_atleta, :id_cita, :id_staff, CURDATE(), :rpe, :created_by)'
                    );
                    $stmt->execute([
                        'id_atleta'  => $id_atleta,
                        'id_cita'    => $id_cita,
                        'id_staff'   => $_SESSION['id_staff'],
                        'rpe'        => $rpe,
                        'created_by' => $_SESSION['id_usuario'],
                    ]);
                }

                $db->commit();
                $exito = true;

                // Motor de reglas de negocio: descuenta 1 sesión de la membresía
                // activa del atleta. Se ejecuta fuera de la transacción anterior
                // (ya confirmada) para que un fallo aquí no invalide la captura
                // clínica ya guardada.
                $deduccion = AthlosBusinessRules::deducirSesionAtleta($db, $id_atleta);
            } catch (\Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $errores[] = 'No se pudo guardar la evaluación. Detalle técnico registrado en el log del servidor.';
                error_log('[SSOS coach_evaluacion] ' . $e->getMessage());
            }
        }
    }
}

$ssos_page_title = 'Evaluación · ' . $atleta['nombre_completo'];
$ssos_active_nav = 'pie_de_cancha';
require __DIR__ . '/../partials/header.php';
?>

<span class="ssos-role-badge">Coach · Pie de Cancha</span>
<h2 class="mt-3"><?= e($atleta['nombre_completo']) ?></h2>

<?php foreach ($errores as $error): ?>
    <div class="alert alert-danger ssos-alert" role="alert"><?= e($error) ?></div>
<?php endforeach; ?>

<?php if ($exito): ?>
    <div class="alert alert-success ssos-alert" role="alert">
        <strong>Evaluación guardada.</strong> RPE y checklist de Sentadilla Overhead registrados.
    </div>

    <?php if ($deduccion && $deduccion['deducted']): ?>
        <div class="alert alert-info ssos-alert" role="alert">
            Sesión descontada de la membresía. Sesiones restantes:
            <strong><?= (int) $deduccion['sesiones_restantes'] ?></strong>.
            <?php if ($deduccion['alerta'] === 'amarillo'): ?>
                <span class="badge text-bg-warning">Alerta de renovación: quedan 2 sesiones</span>
            <?php elseif ($deduccion['alerta'] === 'rojo'): ?>
                <span class="badge text-bg-danger">Sin sesiones — requiere renovación</span>
            <?php endif; ?>
        </div>
    <?php elseif ($deduccion && $deduccion['reason'] === 'sin_membresia_activa_con_saldo'): ?>
        <div class="alert alert-warning ssos-alert" role="alert">
            El atleta no tiene una membresía activa con saldo de sesiones. No se descontó ninguna sesión.
        </div>
    <?php endif; ?>

    <a href="coach.php" class="btn btn-ssos-turquesa">Volver a Atletas del Día</a>
<?php else: ?>
    <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="ssos-table-card mb-4">
            <label for="rpe" class="form-label fw-bold">RPE de la sesión (1–10)</label>
            <div class="pdc-rpe-value" data-pdc-rpe-value>5</div>
            <input type="range" class="pdc-rpe-slider" id="rpe" name="rpe" min="1" max="10" step="1" value="5" data-pdc-rpe-slider>
        </div>

        <div class="ssos-table-card mb-4">
            <p class="form-label fw-bold">Sentadilla Overhead — compensaciones detectadas</p>
            <div class="pdc-checklist">
                <?php foreach ($checklist_items as $campo => $etiqueta): ?>
                    <label class="pdc-check-btn">
                        <input type="checkbox" name="<?= e($campo) ?>" value="1">
                        <?= e($etiqueta) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" class="pdc-start-btn border-0">Guardar Evaluación</button>
    </form>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
