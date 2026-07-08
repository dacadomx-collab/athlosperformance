<?php
declare(strict_types=1);

/**
 * ATHLOS PERFORMANCE — IMPORTADOR DE EXCEL HISTÓRICO DE ANTROPOMETRÍA
 *
 * Sólo se ofrece desde expediente.php cuando el atleta NO tiene historial
 * clínico ni evaluaciones — condición explícita de la directriz (evita que
 * alguien "reimporte encima" de datos ya capturados manualmente).
 *
 * Alcance: procesa la plantilla "Menor_65_02 DATOS ANTROPOMETRÍA ATHLOS.xlsx"
 * (hojas ANTRO MASCU / ANTRO FEME) — ver AntropometriaXlsxMapper.php para el
 * detalle de qué se importa con confianza total (mediciones crudas) y qué se
 * marca para revisión (valores de composición corporal ya calculados en el
 * Excel, que no se pueden re-verificar con una fórmula propia).
 */

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/XlsxReader.php';
require_once __DIR__ . '/../config/AntropometriaXlsxMapper.php';

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

/** Misma clasificación OMS usada en antropometria_form.php — consistencia entre captura manual e importada. */
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
$resumenImportado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } elseif (empty($_FILES['archivo_excel']) || $_FILES['archivo_excel']['error'] !== UPLOAD_ERR_OK) {
        $codigoSubida = $_FILES['archivo_excel']['error'] ?? UPLOAD_ERR_NO_FILE;
        $mensajesSubida = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor.',
            UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo.',
        ];
        $errores[] = $mensajesSubida[$codigoSubida] ?? "Error de carga (código {$codigoSubida}).";
    } else {
        $tmpPath = $_FILES['archivo_excel']['tmp_name'];
        $nombreArchivo = (string) $_FILES['archivo_excel']['name'];

        if (!str_ends_with(strtolower($nombreArchivo), '.xlsx')) {
            $errores[] = 'El archivo debe tener extensión .xlsx.';
        } elseif (!is_uploaded_file($tmpPath)) {
            $errores[] = 'Carga de archivo inválida.';
        } else {
            try {
                $extraido = AntropometriaXlsxMapper::extraer($tmpPath);
                $datos = $extraido['datos'];

                $peso = (float) $datos['peso_kg'];
                $estatura = (float) $datos['estatura_cm'];
                $estaturaM = $estatura / 100;
                $imc = round($peso / ($estaturaM ** 2), 2);

                $camposPliegues = ['pliegue_tricipital', 'pliegue_bicipital', 'pliegue_subescapular', 'pliegue_abdominal', 'pliegue_ileocrestal', 'pliegue_supraespinal', 'pliegue_muslo', 'pliegue_pierna'];
                $sumaPliegues = 0.0;
                $huboPliegue = false;
                foreach ($camposPliegues as $c) {
                    if (is_numeric($datos[$c] ?? null)) {
                        $sumaPliegues += (float) $datos[$c];
                        $huboPliegue = true;
                    }
                }

                $valores = [
                    'id_atleta' => $id_atleta,
                    'fecha_antropometria' => date('Y-m-d'),
                    'asesor' => 'Importado desde Excel histórico',
                    'edad_evaluacion' => is_numeric($datos['edad'] ?? null) ? (int) $datos['edad'] : null,
                    'peso_kg' => $peso,
                    'estatura_cm' => $estatura,
                    'imc' => $imc,
                    'clasificacion_imc' => clasificar_imc($imc),
                    'indice_ponderal' => round($peso / ($estaturaM ** 3), 3),
                    'sumatoria_pliegues' => $huboPliegue ? round($sumaPliegues, 2) : null,
                    'densidad_corporal' => is_numeric($datos['densidad_corporal'] ?? null) ? (float) $datos['densidad_corporal'] : null,
                    'porcentaje_grasa_siri' => is_numeric($datos['porcentaje_grasa_siri'] ?? null) ? (float) $datos['porcentaje_grasa_siri'] : null,
                    'masa_grasa_siri_kg' => is_numeric($datos['masa_grasa_siri_kg'] ?? null) ? (float) $datos['masa_grasa_siri_kg'] : null,
                    'masa_osea_rocha_kg' => is_numeric($datos['masa_osea_rocha_kg'] ?? null) ? (float) $datos['masa_osea_rocha_kg'] : null,
                    'masa_muscular_matiegka_kg' => is_numeric($datos['masa_muscular_matiegka_kg'] ?? null) ? (float) $datos['masa_muscular_matiegka_kg'] : null,
                    'masa_residual_wurch_kg' => is_numeric($datos['masa_residual_wurch_kg'] ?? null) ? (float) $datos['masa_residual_wurch_kg'] : null,
                    'endomorfia' => is_numeric($datos['endomorfia'] ?? null) ? (float) $datos['endomorfia'] : null,
                    'mesomorfia' => is_numeric($datos['mesomorfia'] ?? null) ? (float) $datos['mesomorfia'] : null,
                    'ectomorfia' => is_numeric($datos['ectomorfia'] ?? null) ? (float) $datos['ectomorfia'] : null,
                    'indice_cintura_cadera' => is_numeric($datos['indice_cintura_cadera'] ?? null) ? (float) $datos['indice_cintura_cadera'] : null,
                    'capturado_por' => $_SESSION['id_usuario'],
                ];
                foreach ($camposPliegues as $c) {
                    $valores[$c] = is_numeric($datos[$c] ?? null) ? (float) $datos[$c] : null;
                }
                foreach (['perimetro_brazo_relajado_der', 'perimetro_brazo_contraido_der', 'perimetro_muneca_der', 'perimetro_cintura_minima', 'perimetro_cadera_maxima', 'perimetro_muslo_der', 'perimetro_pierna_relajada_der', 'perimetro_pierna_contraida_der'] as $c) {
                    $valores[$c] = is_numeric($datos[$c] ?? null) ? (float) $datos[$c] : null;
                }
                foreach (['diametro_humeral', 'diametro_femoral', 'diametro_estiloideo', 'diametro_biacromial'] as $c) {
                    $valores[$c] = is_numeric($datos[$c] ?? null) ? (float) $datos[$c] : null;
                }

                $columnas = array_keys($valores);
                $placeholders = array_map(static fn ($c) => ":{$c}", $columnas);
                $stmt = $db->prepare(
                    'INSERT INTO evaluaciones_antropometria (' . implode(', ', $columnas) . ')
                     VALUES (' . implode(', ', $placeholders) . ')'
                );
                $stmt->execute($valores);

                $exito = true;
                $resumenImportado = [
                    'hoja' => $extraido['hoja_usada'] === 'xl/worksheets/sheet1.xml' ? 'ANTRO MASCU' : 'ANTRO FEME',
                    'peso' => $peso, 'estatura' => $estatura, 'imc' => $imc,
                    'clasificacion' => $valores['clasificacion_imc'],
                    'composicion' => [
                        'Densidad corporal' => $valores['densidad_corporal'],
                        '% Grasa (Siri)' => $valores['porcentaje_grasa_siri'],
                        'Masa ósea (Rocha, kg)' => $valores['masa_osea_rocha_kg'],
                        'Masa muscular (Matiegka, kg)' => $valores['masa_muscular_matiegka_kg'],
                        'Masa residual (Wurch, kg)' => $valores['masa_residual_wurch_kg'],
                        'Somatotipo (Endo/Meso/Ecto)' => "{$valores['endomorfia']} / {$valores['mesomorfia']} / {$valores['ectomorfia']}",
                    ],
                ];
            } catch (\Throwable $e) {
                $errores[] = 'No se pudo procesar el archivo: ' . $e->getMessage();
                error_log('[SSOS importar_excel_historico] ' . $e->getMessage());
            }
        }
    }
}

$ssos_page_title = 'Importar Excel Histórico · ' . $atleta['nombre_completo'];
$ssos_breadcrumb_atleta = ['id_atleta' => $id_atleta, 'nombre' => $atleta['nombre_completo']];
require __DIR__ . '/../partials/header.php';
?>

<span class="ssos-role-badge">Importar Excel Histórico</span>
<h2 class="mt-3"><?= e($atleta['nombre_completo']) ?></h2>

<?php foreach ($errores as $error): ?>
    <div class="alert alert-danger ssos-alert" role="alert"><?= e($error) ?></div>
<?php endforeach; ?>

<?php if ($exito): ?>
    <div class="alert alert-success ssos-alert" role="alert">
        Evaluación antropométrica importada desde <strong><?= e($resumenImportado['hoja']) ?></strong>.
    </div>
    <div class="ssos-table-card mb-3">
        <h5>Datos base (medición directa — alta confianza)</h5>
        <p>Peso: <strong><?= e((string) $resumenImportado['peso']) ?> kg</strong> ·
           Estatura: <strong><?= e((string) $resumenImportado['estatura']) ?> cm</strong> ·
           IMC: <strong><?= e((string) $resumenImportado['imc']) ?></strong> (<?= e($resumenImportado['clasificacion']) ?>)
           — recalculado por el sistema, no tomado del Excel.</p>
    </div>
    <div class="ssos-table-card mb-3">
        <h5>Composición corporal — extraída del Excel tal cual, <span class="text-warning">verificar antes de confiar clínicamente</span></h5>
        <p class="text-body-secondary">
            Estos valores ya venían calculados en el archivo de origen. No se recalculan ni se
            validan aquí — revísalos contra el Excel original antes de usarlos en una decisión clínica.
        </p>
        <table class="table table-hover align-middle mb-0">
            <tbody>
                <?php foreach ($resumenImportado['composicion'] as $label => $valor): ?>
                    <tr><th scope="row"><?= e($label) ?></th><td><?= e((string) ($valor ?? '—')) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <a href="expediente.php?id_atleta=<?= $id_atleta ?>" class="btn btn-ssos-turquesa">Volver al Expediente</a>
<?php else: ?>
    <div class="ssos-table-card">
        <p class="text-body-secondary">
            Sube la plantilla <code>DATOS ANTROPOMETRÍA ATHLOS.xlsx</code> (hoja ANTRO MASCU o ANTRO
            FEME, detectada automáticamente). Se importan con confianza total las mediciones directas
            (peso, estatura, pliegues, perímetros, diámetros); los valores de composición corporal ya
            calculados en el Excel se importan también, pero quedan marcados para tu revisión.
        </p>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="mb-3">
                <label for="archivo_excel" class="form-label fw-bold">Archivo .xlsx</label>
                <input type="file" class="form-control" id="archivo_excel" name="archivo_excel" accept=".xlsx" required>
            </div>
            <button type="submit" class="btn btn-ssos-primary btn-lg">Procesar Importación</button>
            <a href="expediente.php?id_atleta=<?= $id_atleta ?>" class="btn btn-outline-secondary btn-lg">Cancelar</a>
        </form>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
