<?php
declare(strict_types=1);

/**
 * ATHLOS PERFORMANCE — HISTORIAL CLÍNICO UNIFICADO (alta/edición)
 * Un registro por atleta (UNIQUE id_atleta) — este formulario hace upsert.
 */

require_once __DIR__ . '/../config/helpers.php';

require_role('coach', 'admin', 'super_admin');

$db = ssos_db();

$id_atleta = filter_input(INPUT_GET, 'id_atleta', FILTER_VALIDATE_INT) ?: null;
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

// Sólo los campos realmente renderizados en este formulario — así una edición
// nunca pisa con NULL una columna que este formulario no muestra (ej. si en el
// futuro otro flujo llena ocupacion/trabajo_* directamente en la BD).
$campos = [
    'tipo_historial', 'actividades_ejercicio_actual', 'dias_ejercicio_moderado_semana',
    'objetivo_perdida_peso', 'objetivo_masa_muscular', 'objetivo_rendimiento_deportivo', 'objetivo_mejorar_salud',
    'dieta_saludable_score', 'sigue_dieta_actual', 'consumo_sal', 'consumo_azucar', 'consumo_grasas',
    'bebidas_alcoholicas_semana',
    'sueno_adecuado', 'fuma_o_vapea',
    'cirugias_previas', 'condicion_cronica', 'medicamentos_actuales', 'autorizacion_medica_ejercicio',
    'nombre_medico', 'telefono_medico', 'contacto_emergencia_nombre', 'contacto_emergencia_telefono',
    'notas_adicionales',
];

$errores = [];
$exito = false;

$stmt = $db->prepare('SELECT * FROM historial_clinico WHERE id_atleta = :id');
$stmt->execute(['id' => $id_atleta]);
$actual = $stmt->fetch() ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } else {
        $tipoHistorial = (string) ($_POST['tipo_historial'] ?? '');
        if (!in_array($tipoHistorial, ['mayor_65', 'menor_65'], true)) {
            $errores[] = 'Selecciona el tipo de historial (Adulto Mayor o Menor de 65).';
        }

        if (empty($errores)) {
            $valores = ['id_atleta' => $id_atleta, 'fecha_captura' => date('Y-m-d'), 'capturado_por' => $_SESSION['id_usuario']];
            foreach ($campos as $campo) {
                $valores[$campo] = trim((string) ($_POST[$campo] ?? '')) !== '' ? trim((string) $_POST[$campo]) : null;
            }
            // checkboxes: valor NULL si no vienen marcados
            foreach (['autorizacion_medica_ejercicio'] as $checkbox) {
                $valores[$checkbox] = isset($_POST[$checkbox]) ? 1 : 0;
            }

            try {
                if (!empty($actual)) {
                    $sets = implode(', ', array_map(static fn ($c) => "{$c} = :{$c}", array_merge($campos, ['fecha_captura', 'capturado_por'])));
                    $stmt = $db->prepare("UPDATE historial_clinico SET {$sets} WHERE id_atleta = :id_atleta");
                } else {
                    $cols = array_merge(['id_atleta'], $campos, ['fecha_captura', 'capturado_por']);
                    $placeholders = array_map(static fn ($c) => ":{$c}", $cols);
                    $stmt = $db->prepare(
                        'INSERT INTO historial_clinico (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $placeholders) . ')'
                    );
                }
                $stmt->execute($valores);
                $exito = true;
                $actual = $valores;
            } catch (\Throwable $e) {
                $errores[] = 'No se pudo guardar el historial clínico: ' . $e->getMessage();
                error_log('[SSOS historial_form] ' . $e->getMessage());
            }
        }
    }
}

$ssos_page_title = 'Historial Clínico · ' . $atleta['nombre_completo'];
$ssos_breadcrumb_atleta = ['id_atleta' => $id_atleta, 'nombre' => $atleta['nombre_completo']];
require __DIR__ . '/../partials/header.php';
?>

<span class="ssos-role-badge">Historial Clínico Unificado</span>
<h2 class="mt-3"><?= e($atleta['nombre_completo']) ?></h2>

<?php foreach ($errores as $error): ?>
    <div class="alert alert-danger ssos-alert" role="alert"><?= e($error) ?></div>
<?php endforeach; ?>
<?php if ($exito): ?>
    <div class="alert alert-success ssos-alert" role="alert">Historial clínico guardado exitosamente.</div>
<?php endif; ?>

<form method="post" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <div class="ssos-table-card mb-3">
        <label class="form-label fw-bold">Tipo de historial</label>
        <select name="tipo_historial" class="form-select" required>
            <option value="menor_65" <?= ($actual['tipo_historial'] ?? '') === 'menor_65' ? 'selected' : '' ?>>Menor de 65 años</option>
            <option value="mayor_65" <?= ($actual['tipo_historial'] ?? '') === 'mayor_65' ? 'selected' : '' ?>>Adulto Mayor (65+)</option>
        </select>
    </div>

    <div class="ssos-table-card mb-3">
        <h5>Ejercicio</h5>
        <div class="mb-2">
            <label class="form-label">Actividades de ejercicio actuales</label>
            <textarea name="actividades_ejercicio_actual" class="form-control" rows="2"><?= e($actual['actividades_ejercicio_actual'] ?? '') ?></textarea>
        </div>
        <div class="mb-2">
            <label class="form-label">Días de ejercicio moderado por semana</label>
            <input type="number" name="dias_ejercicio_moderado_semana" class="form-control" min="0" max="7" value="<?= e((string) ($actual['dias_ejercicio_moderado_semana'] ?? '')) ?>">
        </div>
        <div class="row">
            <div class="col-sm-3 mb-2">
                <label class="form-label">Objetivo: Pérdida de peso (0-10)</label>
                <input type="number" name="objetivo_perdida_peso" class="form-control" min="0" max="10" value="<?= e((string) ($actual['objetivo_perdida_peso'] ?? '')) ?>">
            </div>
            <div class="col-sm-3 mb-2">
                <label class="form-label">Objetivo: Masa muscular (0-10)</label>
                <input type="number" name="objetivo_masa_muscular" class="form-control" min="0" max="10" value="<?= e((string) ($actual['objetivo_masa_muscular'] ?? '')) ?>">
            </div>
            <div class="col-sm-3 mb-2">
                <label class="form-label">Objetivo: Rendimiento (0-10)</label>
                <input type="number" name="objetivo_rendimiento_deportivo" class="form-control" min="0" max="10" value="<?= e((string) ($actual['objetivo_rendimiento_deportivo'] ?? '')) ?>">
            </div>
            <div class="col-sm-3 mb-2">
                <label class="form-label">Objetivo: Mejorar salud (0-10)</label>
                <input type="number" name="objetivo_mejorar_salud" class="form-control" min="0" max="10" value="<?= e((string) ($actual['objetivo_mejorar_salud'] ?? '')) ?>">
            </div>
        </div>
    </div>

    <div class="ssos-table-card mb-3">
        <h5>Dieta</h5>
        <div class="row">
            <div class="col-sm-4 mb-2">
                <label class="form-label">Dieta saludable (0-10)</label>
                <input type="number" name="dieta_saludable_score" class="form-control" min="0" max="10" value="<?= e((string) ($actual['dieta_saludable_score'] ?? '')) ?>">
            </div>
            <div class="col-sm-4 mb-2">
                <label class="form-label">Consumo de sal</label>
                <select name="consumo_sal" class="form-select">
                    <option value="">—</option>
                    <?php foreach (['bajo', 'medio', 'alto'] as $op): ?>
                        <option value="<?= $op ?>" <?= ($actual['consumo_sal'] ?? '') === $op ? 'selected' : '' ?>><?= ucfirst($op) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-4 mb-2">
                <label class="form-label">Consumo de azúcar</label>
                <select name="consumo_azucar" class="form-select">
                    <option value="">—</option>
                    <?php foreach (['bajo', 'medio', 'alto'] as $op): ?>
                        <option value="<?= $op ?>" <?= ($actual['consumo_azucar'] ?? '') === $op ? 'selected' : '' ?>><?= ucfirst($op) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-4 mb-2">
                <label class="form-label">Consumo de grasas</label>
                <select name="consumo_grasas" class="form-select">
                    <option value="">—</option>
                    <?php foreach (['bajo', 'medio', 'alto'] as $op): ?>
                        <option value="<?= $op ?>" <?= ($actual['consumo_grasas'] ?? '') === $op ? 'selected' : '' ?>><?= ucfirst($op) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-4 mb-2">
                <label class="form-label">Bebidas alcohólicas / semana</label>
                <input type="number" name="bebidas_alcoholicas_semana" class="form-control" min="0" value="<?= e((string) ($actual['bebidas_alcoholicas_semana'] ?? '')) ?>">
            </div>
        </div>
        <div class="mb-2">
            <label class="form-label">¿Sigue alguna dieta actualmente?</label>
            <textarea name="sigue_dieta_actual" class="form-control" rows="2"><?= e($actual['sigue_dieta_actual'] ?? '') ?></textarea>
        </div>
    </div>

    <div class="ssos-table-card mb-3">
        <h5>Estilo de vida</h5>
        <div class="mb-2">
            <label class="form-label">¿Duerme lo suficiente?</label>
            <textarea name="sueno_adecuado" class="form-control" rows="2"><?= e($actual['sueno_adecuado'] ?? '') ?></textarea>
        </div>
        <div class="mb-2">
            <label class="form-label">¿Fuma o vapea?</label>
            <textarea name="fuma_o_vapea" class="form-control" rows="1"><?= e($actual['fuma_o_vapea'] ?? '') ?></textarea>
        </div>
    </div>

    <div class="ssos-table-card mb-3">
        <h5>Médico</h5>
        <div class="mb-2">
            <label class="form-label">Cirugías previas</label>
            <textarea name="cirugias_previas" class="form-control" rows="2"><?= e($actual['cirugias_previas'] ?? '') ?></textarea>
        </div>
        <div class="mb-2">
            <label class="form-label">Condición crónica</label>
            <textarea name="condicion_cronica" class="form-control" rows="2"><?= e($actual['condicion_cronica'] ?? '') ?></textarea>
        </div>
        <div class="mb-2">
            <label class="form-label">Medicamentos actuales</label>
            <textarea name="medicamentos_actuales" class="form-control" rows="2"><?= e($actual['medicamentos_actuales'] ?? '') ?></textarea>
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" class="form-check-input" name="autorizacion_medica_ejercicio" id="autorizacion" value="1" <?= !empty($actual['autorizacion_medica_ejercicio']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="autorizacion">Cuenta con autorización médica para ejercicio</label>
        </div>
        <div class="row">
            <div class="col-sm-6 mb-2">
                <label class="form-label">Nombre del médico</label>
                <input type="text" name="nombre_medico" class="form-control" value="<?= e($actual['nombre_medico'] ?? '') ?>">
            </div>
            <div class="col-sm-6 mb-2">
                <label class="form-label">Teléfono del médico</label>
                <input type="text" name="telefono_medico" class="form-control" value="<?= e($actual['telefono_medico'] ?? '') ?>">
            </div>
            <div class="col-sm-6 mb-2">
                <label class="form-label">Contacto de emergencia</label>
                <input type="text" name="contacto_emergencia_nombre" class="form-control" value="<?= e($actual['contacto_emergencia_nombre'] ?? '') ?>">
            </div>
            <div class="col-sm-6 mb-2">
                <label class="form-label">Teléfono de emergencia</label>
                <input type="text" name="contacto_emergencia_telefono" class="form-control" value="<?= e($actual['contacto_emergencia_telefono'] ?? '') ?>">
            </div>
        </div>
    </div>

    <div class="ssos-table-card mb-3">
        <label class="form-label">Notas adicionales</label>
        <textarea name="notas_adicionales" class="form-control" rows="3"><?= e($actual['notas_adicionales'] ?? '') ?></textarea>
    </div>

    <button type="submit" class="btn btn-ssos-turquesa btn-lg">Guardar Historial Clínico</button>
    <a href="expediente.php?id_atleta=<?= $id_atleta ?>" class="btn btn-outline-secondary btn-lg">Cancelar</a>
</form>

<?php require __DIR__ . '/../partials/footer.php'; ?>
