<?php
declare(strict_types=1);

/**
 * ATHLOS PERFORMANCE — FICHA ANTROPOMÉTRICA (captura de nueva evaluación)
 *
 * DECISIÓN DE ALCANCE (documentada también en RESUMEN_EJECUCION_SISTEMA.md):
 * este formulario captura las medidas crudas (pliegues, perímetros,
 * diámetros) y calcula automáticamente sólo lo que es matemáticamente
 * universal y verificable (IMC vía OMS, suma de pliegues, índice ponderal,
 * y el % de grasa de Siri *si* se provee la densidad corporal ya calculada).
 * NO se auto-calculan densidad corporal desde pliegues (regresión
 * Jackson-Pollock/Durnin-Womersley específica por sexo/edad) ni la fórmula
 * de Rocha para masa ósea — no están documentadas con certeza suficiente en
 * este proyecto para usarse en un expediente clínico real. Esos campos
 * quedan como captura manual opcional (el coach los transcribe si ya los
 * calculó con su propia calculadora/tabla de referencia).
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

/** Clasificación de IMC según umbrales estándar de la OMS. */
function clasificar_imc(float $imc): string
{
    return match (true) {
        $imc < 18.5 => 'bajo_peso',
        $imc < 25.0 => 'normal',
        $imc < 30.0 => 'sobrepeso',
        $imc < 35.0 => 'obesidad',
        $imc < 40.0 => 'obesidad_severa',
        default => 'obesidad_morbida',
    };
}

$errores = [];
$exito = false;

$pliegues = ['pliegue_tricipital', 'pliegue_bicipital', 'pliegue_subescapular', 'pliegue_abdominal', 'pliegue_ileocrestal', 'pliegue_supraespinal', 'pliegue_muslo', 'pliegue_pierna'];
$perimetrosPares = [
    'perimetro_brazo_relajado' => 'Brazo relajado', 'perimetro_brazo_contraido' => 'Brazo contraído',
    'perimetro_muneca' => 'Muñeca', 'perimetro_muslo' => 'Muslo',
    'perimetro_pierna_relajada' => 'Pierna relajada', 'perimetro_pierna_contraida' => 'Pierna contraída',
];
$perimetrosUnicos = ['perimetro_cintura_minima' => 'Cintura mínima', 'perimetro_cadera_maxima' => 'Cadera máxima'];
$diametros = ['diametro_humeral' => 'Húmero', 'diametro_femoral' => 'Fémur', 'diametro_estiloideo' => 'Estiloideo', 'diametro_biacromial' => 'Biacromial', 'diametro_biiliocrestal' => 'Bi-iliocrestal'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } else {
        $peso = filter_input(INPUT_POST, 'peso_kg', FILTER_VALIDATE_FLOAT);
        $estatura = filter_input(INPUT_POST, 'estatura_cm', FILTER_VALIDATE_FLOAT);
        $fecha = (string) ($_POST['fecha_antropometria'] ?? date('Y-m-d'));

        if (!$peso || $peso <= 0) {
            $errores[] = 'El peso es obligatorio y debe ser mayor a 0.';
        }
        if (!$estatura || $estatura <= 0) {
            $errores[] = 'La estatura es obligatoria y debe ser mayor a 0.';
        }

        if (empty($errores)) {
            $estaturaM = $estatura / 100;
            $imc = round($peso / ($estaturaM ** 2), 2);
            $clasificacionImc = clasificar_imc($imc);
            $indicePonderal = round($peso / ($estaturaM ** 3), 3);

            $sumaPliegues = 0.0;
            $huboPliegue = false;
            $valoresPliegues = [];
            foreach ($pliegues as $campo) {
                $valor = filter_input(INPUT_POST, $campo, FILTER_VALIDATE_FLOAT);
                $valoresPliegues[$campo] = $valor !== false && $valor !== null ? $valor : null;
                if ($valoresPliegues[$campo] !== null) {
                    $sumaPliegues += $valoresPliegues[$campo];
                    $huboPliegue = true;
                }
            }

            $densidadCorporal = filter_input(INPUT_POST, 'densidad_corporal', FILTER_VALIDATE_FLOAT) ?: null;
            $porcentajeGrasaSiri = null;
            $masaGrasaSiriKg = null;
            if ($densidadCorporal) {
                // Ecuación de Siri (1961), universal una vez que se conoce la densidad corporal.
                $porcentajeGrasaSiri = round((495 / $densidadCorporal) - 450, 2);
                $masaGrasaSiriKg = round($peso * ($porcentajeGrasaSiri / 100), 2);
            }

            $valores = [
                'id_atleta' => $id_atleta,
                'fecha_antropometria' => $fecha,
                'asesor' => trim((string) ($_POST['asesor'] ?? '')) ?: null,
                'edad_evaluacion' => filter_input(INPUT_POST, 'edad_evaluacion', FILTER_VALIDATE_INT) ?: null,
                'peso_kg' => $peso,
                'estatura_cm' => $estatura,
                'imc' => $imc,
                'clasificacion_imc' => $clasificacionImc,
                'indice_ponderal' => $indicePonderal,
                'sumatoria_pliegues' => $huboPliegue ? round($sumaPliegues, 2) : null,
                'densidad_corporal' => $densidadCorporal,
                'porcentaje_grasa_siri' => $porcentajeGrasaSiri,
                'masa_grasa_siri_kg' => $masaGrasaSiriKg,
                'masa_osea_rocha_kg' => filter_input(INPUT_POST, 'masa_osea_rocha_kg', FILTER_VALIDATE_FLOAT) ?: null,
                'clasificacion_grasa' => (string) ($_POST['clasificacion_grasa'] ?? '') ?: null,
                'indice_cintura_cadera' => filter_input(INPUT_POST, 'indice_cintura_cadera', FILTER_VALIDATE_FLOAT) ?: null,
                'clasificacion_riesgo_cintura' => (string) ($_POST['clasificacion_riesgo_cintura'] ?? '') ?: null,
                'capturado_por' => $_SESSION['id_usuario'],
            ];
            $valores = array_merge($valores, $valoresPliegues);

            foreach (array_keys($perimetrosPares) as $campo) {
                foreach (['der', 'izq'] as $lado) {
                    $valor = filter_input(INPUT_POST, "{$campo}_{$lado}", FILTER_VALIDATE_FLOAT);
                    $valores["{$campo}_{$lado}"] = $valor !== false && $valor !== null ? $valor : null;
                }
            }
            foreach (array_keys($perimetrosUnicos) as $campo) {
                $valor = filter_input(INPUT_POST, $campo, FILTER_VALIDATE_FLOAT);
                $valores[$campo] = $valor !== false && $valor !== null ? $valor : null;
            }
            foreach (array_keys($diametros) as $campo) {
                $valor = filter_input(INPUT_POST, $campo, FILTER_VALIDATE_FLOAT);
                $valores[$campo] = $valor !== false && $valor !== null ? $valor : null;
            }

            try {
                $columnas = array_keys($valores);
                $placeholders = array_map(static fn ($c) => ":{$c}", $columnas);
                $stmt = $db->prepare(
                    'INSERT INTO evaluaciones_antropometria (' . implode(', ', $columnas) . ')
                     VALUES (' . implode(', ', $placeholders) . ')'
                );
                $stmt->execute($valores);
                $exito = true;
            } catch (\Throwable $e) {
                $errores[] = 'No se pudo guardar la evaluación: ' . $e->getMessage();
                error_log('[SSOS antropometria_form] ' . $e->getMessage());
            }
        }
    }
}

$ssos_page_title = 'Antropometría · ' . $atleta['nombre_completo'];
$ssos_breadcrumb_atleta = ['id_atleta' => $id_atleta, 'nombre' => $atleta['nombre_completo']];
require __DIR__ . '/../partials/header.php';
?>

<span class="ssos-role-badge">Ficha Antropométrica</span>
<h2 class="mt-3"><?= e($atleta['nombre_completo']) ?></h2>

<?php foreach ($errores as $error): ?>
    <div class="alert alert-danger ssos-alert" role="alert"><?= e($error) ?></div>
<?php endforeach; ?>

<?php if ($exito): ?>
    <div class="alert alert-success ssos-alert" role="alert">
        Evaluación antropométrica guardada. IMC y suma de pliegues calculados automáticamente.
    </div>
    <a href="expediente.php?id_atleta=<?= $id_atleta ?>" class="btn btn-ssos-turquesa">Volver al Expediente</a>
<?php else: ?>
    <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="ssos-table-card mb-3">
            <h5>Datos base</h5>
            <div class="row">
                <div class="col-sm-3 mb-2">
                    <label class="form-label">Fecha</label>
                    <input type="date" name="fecha_antropometria" class="form-control" value="<?= e(date('Y-m-d')) ?>" required>
                </div>
                <div class="col-sm-3 mb-2">
                    <label class="form-label">Peso (kg)</label>
                    <input type="number" step="0.1" name="peso_kg" class="form-control" required>
                </div>
                <div class="col-sm-3 mb-2">
                    <label class="form-label">Estatura (cm)</label>
                    <input type="number" step="0.1" name="estatura_cm" class="form-control" required>
                </div>
                <div class="col-sm-3 mb-2">
                    <label class="form-label">Asesor</label>
                    <input type="text" name="asesor" class="form-control">
                </div>
                <div class="col-sm-3 mb-2">
                    <label class="form-label">Edad al evaluar</label>
                    <input type="number" name="edad_evaluacion" class="form-control" min="0" max="120">
                </div>
            </div>
            <p class="text-body-secondary mb-0">
                El IMC, su clasificación (OMS), la suma de pliegues y el índice ponderal se calculan
                automáticamente al guardar.
            </p>
        </div>

        <div class="ssos-table-card mb-3">
            <h5>Pliegues cutáneos (mm)</h5>
            <div class="row">
                <?php foreach ($pliegues as $campo): ?>
                    <div class="col-sm-3 mb-2">
                        <label class="form-label"><?= ucfirst(str_replace('pliegue_', '', $campo)) ?></label>
                        <input type="number" step="0.1" name="<?= $campo ?>" class="form-control">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="ssos-table-card mb-3">
            <h5>Perímetros (cm)</h5>
            <?php foreach ($perimetrosPares as $campo => $etiqueta): ?>
                <div class="row mb-2">
                    <div class="col-sm-4"><label class="form-label">derecho</label></div>
                    <div class="col-sm-4"><label class="form-label">izquierdo</label></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <label class="form-label fw-bold"><?= e($etiqueta) ?> (der)</label>
                        <input type="number" step="0.1" name="<?= $campo ?>_der" class="form-control">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label fw-bold"><?= e($etiqueta) ?> (izq)</label>
                        <input type="number" step="0.1" name="<?= $campo ?>_izq" class="form-control">
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="row">
                <?php foreach ($perimetrosUnicos as $campo => $etiqueta): ?>
                    <div class="col-sm-4 mb-2">
                        <label class="form-label"><?= e($etiqueta) ?></label>
                        <input type="number" step="0.1" name="<?= $campo ?>" class="form-control">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="ssos-table-card mb-3">
            <h5>Diámetros óseos (cm)</h5>
            <div class="row">
                <?php foreach ($diametros as $campo => $etiqueta): ?>
                    <div class="col-sm-3 mb-2">
                        <label class="form-label"><?= e($etiqueta) ?></label>
                        <input type="number" step="0.1" name="<?= $campo ?>" class="form-control">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="ssos-table-card mb-3">
            <h5>Composición corporal (opcional — captura manual)</h5>
            <p class="text-body-secondary">
                Si ya calculaste estos valores con tu propia herramienta/tabla de referencia,
                captúralos aquí. El sistema no los calcula automáticamente desde los pliegues para
                evitar aplicar una fórmula de regresión sin verificar (Jackson-Pollock/Durnin-Womersley
                varían por sexo, edad y protocolo). El % de grasa de Siri sí se calcula automáticamente
                <strong>si</strong> capturas la densidad corporal.
            </p>
            <div class="row">
                <div class="col-sm-4 mb-2">
                    <label class="form-label">Densidad corporal (g/cm³)</label>
                    <input type="number" step="0.0001" name="densidad_corporal" class="form-control">
                </div>
                <div class="col-sm-4 mb-2">
                    <label class="form-label">Masa ósea (Rocha, kg)</label>
                    <input type="number" step="0.1" name="masa_osea_rocha_kg" class="form-control">
                </div>
                <div class="col-sm-4 mb-2">
                    <label class="form-label">Clasificación de grasa</label>
                    <select name="clasificacion_grasa" class="form-select">
                        <option value="">—</option>
                        <?php foreach (['grasa_esencial', 'atletas', 'fitness', 'aceptable', 'sobregraso_moderado', 'sobregraso_riesgo', 'obeso', 'obeso_riesgo', 'obeso_morbido'] as $op): ?>
                            <option value="<?= $op ?>"><?= ucfirst(str_replace('_', ' ', $op)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-4 mb-2">
                    <label class="form-label">Índice cintura-cadera</label>
                    <input type="number" step="0.001" name="indice_cintura_cadera" class="form-control">
                </div>
                <div class="col-sm-4 mb-2">
                    <label class="form-label">Riesgo por cintura</label>
                    <select name="clasificacion_riesgo_cintura" class="form-select">
                        <option value="">—</option>
                        <option value="sin_riesgo">Sin riesgo</option>
                        <option value="sin_peligro">Sin peligro</option>
                        <option value="peligro_metabolico">Peligro metabólico</option>
                    </select>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-ssos-turquesa btn-lg">Guardar Evaluación</button>
        <a href="expediente.php?id_atleta=<?= $id_atleta ?>" class="btn btn-outline-secondary btn-lg">Cancelar</a>
    </form>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
