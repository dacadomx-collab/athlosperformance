<?php
declare(strict_types=1);

/**
 * ATHLOS PERFORMANCE — SENIOR FITNESS TEST (SFT), captura con semaforización
 * automática vía `percentiles_sft_referencia` (sembrada en la Fase 3 desde
 * la Ficha de Evaluación adulto mayor.docx — Rikli & Jones norms).
 *
 * HEURÍSTICA DE SEMÁFORO (documentada, no es una tabla de percentiles fina):
 * la fuente sólo da el rango [normativo_min, normativo_max] por edad/sexo,
 * no cortes exactos verde/amarillo/rojo. Regla aplicada: dentro o mejor que
 * el rango normativo → verde; hasta 20% del ancho del rango por debajo (o
 * por arriba, en time_up_go donde menor es mejor) → amarillo; más lejos →
 * rojo. El semáforo general es el peor de los 6 individuales (conservador).
 */

require_once __DIR__ . '/../config/helpers.php';

require_role('coach', 'admin', 'super_admin');

$db = ssos_db();

$id_atleta = filter_input(INPUT_GET, 'id_atleta', FILTER_VALIDATE_INT) ?: null;
if ($id_atleta === null) {
    http_response_code(400);
    die('Falta id_atleta.');
}

$stmt = $db->prepare('SELECT id_atleta, nombre_completo, sexo, fecha_nacimiento FROM atletas WHERE id_atleta = :id');
$stmt->execute(['id' => $id_atleta]);
$atleta = $stmt->fetch();

if (!$atleta) {
    http_response_code(404);
    die('Atleta no encontrado.');
}

$edadActual = !empty($atleta['fecha_nacimiento'])
    ? (new DateTimeImmutable($atleta['fecha_nacimiento']))->diff(new DateTimeImmutable())->y
    : null;

$pruebas = [
    'chair_sit_reach' => ['label' => 'Chair Sit-&-Reach (cm)', 'campo' => 'chair_sit_reach_cm', 'mayor_es_mejor' => true],
    'back_scratch' => ['label' => 'Back Scratch (cm)', 'campo' => 'back_scratch_cm', 'mayor_es_mejor' => true],
    'chair_stand' => ['label' => 'Chair Stand (reps)', 'campo' => 'chair_stand_reps', 'mayor_es_mejor' => true],
    'arm_curl' => ['label' => 'Arm Curl (reps)', 'campo' => 'arm_curl_reps', 'mayor_es_mejor' => true],
    'time_up_go' => ['label' => 'Time Up-&-Go (seg)', 'campo' => 'time_up_go_seg', 'mayor_es_mejor' => false],
    'two_min_step' => ['label' => '2-Min Step (pasos)', 'campo' => 'two_min_step_pasos', 'mayor_es_mejor' => true],
];

/** Calcula verde/amarillo/rojo comparando un valor contra su rango normativo. */
function calcular_semaforo(float $valor, float $min, float $max, bool $mayorEsMejor): string
{
    $anchoRango = abs($max - $min);
    $buffer = $anchoRango * 0.20;

    if ($mayorEsMejor) {
        if ($valor >= $min) {
            return 'verde';
        }
        return $valor >= ($min - $buffer) ? 'amarillo' : 'rojo';
    }

    if ($valor <= $max) {
        return 'verde';
    }
    return $valor <= ($max + $buffer) ? 'amarillo' : 'rojo';
}

$errores = [];
$exito = false;
$resultadoSemaforos = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } else {
        $sexo = (string) ($_POST['sexo'] ?? $atleta['sexo'] ?? '');
        $edad = filter_input(INPUT_POST, 'edad_evaluacion', FILTER_VALIDATE_INT) ?: $edadActual;
        $fecha = (string) ($_POST['fecha_evaluacion'] ?? date('Y-m-d'));

        if (!in_array($sexo, ['masculino', 'femenino'], true)) {
            $errores[] = 'El sexo es obligatorio para calcular el semáforo (masculino/femenino).';
        }
        if (!$edad || $edad < 60) {
            $errores[] = 'La edad es obligatoria y el SFT aplica a partir de 60 años.';
        }

        if (empty($errores)) {
            $valores = [
                'id_atleta' => $id_atleta,
                'fecha_evaluacion' => $fecha,
                'edad_evaluacion' => $edad,
                'sexo' => $sexo,
                'functional_reach_cm' => filter_input(INPUT_POST, 'functional_reach_cm', FILTER_VALIDATE_FLOAT) ?: null,
                'time_up_go_cognitivo_seg' => filter_input(INPUT_POST, 'time_up_go_cognitivo_seg', FILTER_VALIDATE_FLOAT) ?: null,
                'observaciones' => trim((string) ($_POST['observaciones'] ?? '')) ?: null,
                'evaluado_por' => $_SESSION['id_usuario'],
            ];

            $peorSemaforo = ['verde' => 0, 'amarillo' => 1, 'rojo' => 2];
            $semaforoGeneralNivel = 0;
            $huboAlMenosUnaPrueba = false;

            foreach ($pruebas as $variable => $config) {
                $valorRaw = filter_input(INPUT_POST, $config['campo'], FILTER_VALIDATE_FLOAT);
                $valores[$config['campo']] = $valorRaw !== false && $valorRaw !== null ? $valorRaw : null;

                if ($valores[$config['campo']] === null) {
                    $valores["semaforo_{$variable}"] = null;
                    continue;
                }

                $huboAlMenosUnaPrueba = true;

                $stmtRango = $db->prepare(
                    'SELECT valor_min, valor_max FROM percentiles_sft_referencia
                     WHERE sexo = :sexo AND variable = :variable AND :edad BETWEEN edad_min AND edad_max
                     LIMIT 1'
                );
                $stmtRango->execute(['sexo' => $sexo, 'variable' => $variable, 'edad' => $edad]);
                $rango = $stmtRango->fetch();

                if (!$rango) {
                    $valores["semaforo_{$variable}"] = null;
                    continue;
                }

                $semaforo = calcular_semaforo(
                    $valores[$config['campo']],
                    (float) $rango['valor_min'],
                    (float) $rango['valor_max'],
                    $config['mayor_es_mejor']
                );
                $valores["semaforo_{$variable}"] = $semaforo;
                $resultadoSemaforos[$variable] = $semaforo;
                $semaforoGeneralNivel = max($semaforoGeneralNivel, $peorSemaforo[$semaforo]);
            }

            $valores['semaforo_general'] = $huboAlMenosUnaPrueba ? array_flip($peorSemaforo)[$semaforoGeneralNivel] : null;

            if (!$huboAlMenosUnaPrueba) {
                $errores[] = 'Captura al menos una prueba para poder calcular el semáforo.';
            } else {
                try {
                    $columnas = array_keys($valores);
                    $placeholders = array_map(static fn ($c) => ":{$c}", $columnas);
                    $stmt = $db->prepare(
                        'INSERT INTO evaluaciones_sft (' . implode(', ', $columnas) . ')
                         VALUES (' . implode(', ', $placeholders) . ')'
                    );
                    $stmt->execute($valores);
                    $exito = true;
                } catch (\Throwable $e) {
                    $errores[] = 'No se pudo guardar la evaluación: ' . $e->getMessage();
                    error_log('[SSOS sft_form] ' . $e->getMessage());
                }
            }
        }
    }
}

$semaforoLabels = ['verde' => '🟢 Verde', 'amarillo' => '🟡 Amarillo', 'rojo' => '🔴 Rojo'];

$ssos_page_title = 'Senior Fitness Test · ' . $atleta['nombre_completo'];
$ssos_breadcrumb_atleta = ['id_atleta' => $id_atleta, 'nombre' => $atleta['nombre_completo']];
require __DIR__ . '/../partials/header.php';
?>

<span class="ssos-role-badge">Senior Fitness Test (SFT)</span>
<h2 class="mt-3"><?= e($atleta['nombre_completo']) ?></h2>

<?php foreach ($errores as $error): ?>
    <div class="alert alert-danger ssos-alert" role="alert"><?= e($error) ?></div>
<?php endforeach; ?>

<?php if ($exito): ?>
    <div class="alert alert-success ssos-alert" role="alert">
        <strong>Evaluación SFT guardada.</strong> Semáforo general:
        <?= $semaforoLabels[$valores['semaforo_general']] ?? '—' ?>
    </div>
    <div class="ssos-table-card mb-3">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Prueba</th><th>Semáforo</th></tr></thead>
            <tbody>
                <?php foreach ($resultadoSemaforos as $variable => $semaforo): ?>
                    <tr>
                        <td><?= e($pruebas[$variable]['label']) ?></td>
                        <td><?= $semaforoLabels[$semaforo] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <a href="expediente.php?id_atleta=<?= $id_atleta ?>" class="btn btn-ssos-turquesa">Volver al Expediente</a>
<?php else: ?>
    <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="ssos-table-card mb-3">
            <div class="row">
                <div class="col-sm-3 mb-2">
                    <label class="form-label">Fecha</label>
                    <input type="date" name="fecha_evaluacion" class="form-control" value="<?= e(date('Y-m-d')) ?>" required>
                </div>
                <div class="col-sm-3 mb-2">
                    <label class="form-label">Edad</label>
                    <input type="number" name="edad_evaluacion" class="form-control" min="60" max="120" value="<?= e((string) ($edadActual ?? '')) ?>" required>
                </div>
                <div class="col-sm-3 mb-2">
                    <label class="form-label">Sexo</label>
                    <select name="sexo" class="form-select" required>
                        <option value="masculino" <?= ($atleta['sexo'] ?? '') === 'masculino' ? 'selected' : '' ?>>Masculino</option>
                        <option value="femenino" <?= ($atleta['sexo'] ?? '') === 'femenino' ? 'selected' : '' ?>>Femenino</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="ssos-table-card mb-3">
            <h5>Pruebas</h5>
            <p class="text-body-secondary">Deja en blanco las que no se hayan realizado — el semáforo se calcula sólo con las capturadas.</p>
            <div class="row">
                <?php foreach ($pruebas as $config): ?>
                    <div class="col-sm-4 mb-3">
                        <label class="form-label fw-bold"><?= e($config['label']) ?></label>
                        <input type="number" step="0.1" name="<?= $config['campo'] ?>" class="form-control form-control-lg">
                    </div>
                <?php endforeach; ?>
                <div class="col-sm-4 mb-3">
                    <label class="form-label">Functional Reach (cm)</label>
                    <input type="number" step="0.1" name="functional_reach_cm" class="form-control">
                </div>
                <div class="col-sm-4 mb-3">
                    <label class="form-label">Time Up-&-Go Cognitivo (seg)</label>
                    <input type="number" step="0.1" name="time_up_go_cognitivo_seg" class="form-control">
                </div>
            </div>
        </div>

        <div class="ssos-table-card mb-3">
            <label class="form-label">Observaciones</label>
            <textarea name="observaciones" class="form-control" rows="3"></textarea>
        </div>

        <button type="submit" class="btn btn-ssos-turquesa btn-lg">Guardar y Calcular Semáforo</button>
        <a href="expediente.php?id_atleta=<?= $id_atleta ?>" class="btn btn-outline-secondary btn-lg">Cancelar</a>
    </form>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
