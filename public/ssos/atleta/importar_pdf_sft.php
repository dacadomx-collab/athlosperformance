<?php
declare(strict_types=1);

/**
 * ATHLOS PERFORMANCE — IMPORTADOR DE PDF DE "FICHA EVALUACIÓN ADULTO MAYOR" (SFT)
 *
 * Mismo patrón de seguridad que importar_pdf_historial.php: el texto se
 * extrae y mapea con SftPdfMapper, pero NUNCA se guarda directo en
 * evaluaciones_sft — se deja como prefill de un solo uso en sesión y se
 * redirige a sft_form.php para que el coach revise cada número (el semáforo
 * de riesgo se calcula sobre estos valores) y confirme con
 * "Guardar y Calcular Semáforo".
 *
 * A diferencia del historial clínico, evaluaciones_sft SÍ admite múltiples
 * registros por atleta (una fila por fecha de evaluación) — por eso este
 * importador no se bloquea si ya existen evaluaciones previas.
 */

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/PdfTextExtractor.php';
require_once __DIR__ . '/../config/SftPdfMapper.php';

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

// REGLA-01 (candado cognitivo): mismo requisito que evaluacion_sft.php.
$stmt = $db->prepare('SELECT tipo_historial FROM historial_clinico WHERE id_atleta = :id');
$stmt->execute(['id' => $id_atleta]);
$historialCandado = $stmt->fetch();
if (!$historialCandado || $historialCandado['tipo_historial'] !== 'mayor_65') {
    http_response_code(403);
    die('Este módulo requiere un Historial Clínico capturado y clasificado como Adulto Mayor (65+). Captúralo primero desde el Expediente.');
}

$errores = [];
$resultado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } elseif (empty($_FILES['archivo_pdf']) || $_FILES['archivo_pdf']['error'] !== UPLOAD_ERR_OK) {
        $codigoSubida = $_FILES['archivo_pdf']['error'] ?? UPLOAD_ERR_NO_FILE;
        $mensajesSubida = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor.',
            UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo.',
        ];
        $errores[] = $mensajesSubida[$codigoSubida] ?? "Error de carga (código {$codigoSubida}).";
    } else {
        $tmpPath = $_FILES['archivo_pdf']['tmp_name'];
        $nombreArchivo = (string) $_FILES['archivo_pdf']['name'];

        if (!str_ends_with(strtolower($nombreArchivo), '.pdf')) {
            $errores[] = 'El archivo debe tener extensión .pdf.';
        } elseif (!is_uploaded_file($tmpPath)) {
            $errores[] = 'Carga de archivo inválida.';
        } else {
            try {
                $texto = PdfTextExtractor::extraerTexto($tmpPath);
                if (trim($texto) === '') {
                    $errores[] = 'No se pudo extraer texto de este PDF (¿es una imagen escaneada sin texto seleccionable?). Captura la evaluación manualmente.';
                } else {
                    $resultado = SftPdfMapper::mapear($texto);
                    $_SESSION['ssos_prefill_sft'][$id_atleta] = $resultado['campos'];
                }
            } catch (\Throwable $e) {
                $errores[] = 'No se pudo procesar el archivo: ' . $e->getMessage();
                error_log('[SSOS importar_pdf_sft] ' . $e->getMessage());
            }
        }
    }
}

$ssos_page_title = 'Importar PDF de Ficha SFT · ' . $atleta['nombre_completo'];
$ssos_breadcrumb_atleta = ['id_atleta' => $id_atleta, 'nombre' => $atleta['nombre_completo']];
require __DIR__ . '/../partials/header.php';
?>

<span class="ssos-role-badge">Importar PDF de Ficha de Evaluación (SFT)</span>
<h2 class="mt-3"><?= e($atleta['nombre_completo']) ?></h2>

<?php foreach ($errores as $error): ?>
    <div class="alert alert-danger ssos-alert" role="alert"><?= e($error) ?></div>
<?php endforeach; ?>

<?php if ($resultado !== null): ?>
    <div class="alert alert-warning ssos-alert" role="alert">
        Texto extraído y prellenado en el formulario — <strong>ningún dato se guardó todavía</strong>.
        Revisa cada número antes de presionar "Guardar y Calcular Semáforo" (el semáforo de riesgo se calcula sobre estos valores).
    </div>
    <?php if (!empty($resultado['informativos'])): ?>
        <div class="ssos-table-card mb-3">
            <h5 class="mb-2">Valores con lado izquierda/derecha — captúralos tú a mano</h5>
            <p class="text-body-secondary mb-2">
                El formulario sólo tiene un campo numérico por prueba (no distingue lado). Estos valores
                se detectaron en el PDF pero no se prellenaron para no elegir un lado por ti:
            </p>
            <ul class="mb-0">
                <?php foreach ($resultado['informativos'] as $etiqueta => $valor): ?>
                    <li><strong><?= e($etiqueta) ?>:</strong> <?= e($valor) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if (!empty($resultado['demograficos'])): ?>
        <div class="ssos-table-card mb-3">
            <h5 class="mb-2">Datos demográficos detectados en el PDF (sólo informativos)</h5>
            <ul class="mb-0">
                <?php foreach ($resultado['demograficos'] as $etiqueta => $valor): ?>
                    <li><strong><?= e(ucfirst($etiqueta)) ?>:</strong> <?= e((string) $valor) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php
        $etiquetasCampos = [
            'chair_stand_reps' => 'Chair Stand', 'arm_curl_reps' => 'Arm Curl',
            'two_min_step_pasos' => '2-Min Step', 'functional_reach_cm' => 'Functional Reach',
            'time_up_go_cognitivo_seg' => 'Time Up-&-Go Cognitivo', 'observaciones' => 'Observaciones de Sentadilla',
        ];
        $camposVacios = [];
        foreach ($resultado['campos'] as $campo => $valor) {
            if ($valor === null && isset($etiquetasCampos[$campo])) {
                $camposVacios[] = $etiquetasCampos[$campo];
            }
        }
    ?>
    <?php if (!empty($camposVacios)): ?>
        <div class="ssos-table-card mb-3 border-warning">
            <h5 class="mb-2">⚠️ Campos que el PDF dejó en blanco — captúralos a mano</h5>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($camposVacios as $etiqueta): ?>
                    <span class="badge text-bg-warning"><?= e($etiqueta) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    <a href="sft_form.php?id_atleta=<?= $id_atleta ?>&desde_pdf=1" class="btn btn-ssos-turquesa btn-lg">
        Continuar a Revisar y Guardar Evaluación SFT
    </a>
<?php else: ?>
    <div class="ssos-table-card">
        <p class="text-body-secondary">
            Sube el PDF "Ficha Evaluación adulto mayor" (Senior Fitness Test + checklist de sentadilla).
            Se extrae el texto y se prellenan los campos numéricos no ambiguos del formulario SFT —
            <strong>tú confirmas o corriges cada campo</strong> antes de guardar; nada se escribe en la
            base de datos en este paso.
        </p>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="mb-3">
                <label for="archivo_pdf" class="form-label fw-bold">Archivo .pdf</label>
                <input type="file" class="form-control" id="archivo_pdf" name="archivo_pdf" accept=".pdf" required>
            </div>
            <button type="submit" class="btn btn-ssos-primary btn-lg">Extraer y Prellenar</button>
            <a href="expediente.php?id_atleta=<?= $id_atleta ?>" class="btn btn-outline-secondary btn-lg">Cancelar</a>
        </form>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
