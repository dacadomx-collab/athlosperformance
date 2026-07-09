<?php
declare(strict_types=1);

/**
 * ATHLOS PERFORMANCE — IMPORTADOR DE PDF DE HISTORIAL CLÍNICO
 *
 * A diferencia de importar_excel_historico.php (celdas numéricas de una hoja
 * de cálculo, con confianza total), esto es texto libre extraído de un PDF
 * escaneado/exportado y mapeado con regex sobre las preguntas fijas de la
 * plantilla — mucho menos confiable. Por eso NUNCA se guarda directo en la
 * BD: el resultado se deja como prefill en sesión y se redirige a
 * historial_form.php para que el coach revise, corrija y confirme cada
 * campo con el botón "Guardar Historial Clínico" de siempre.
 *
 * Sólo se ofrece cuando el atleta no tiene historial_clinico aún (mismo
 * criterio de "no reimportar encima de una captura real" que el importador
 * de Excel).
 */

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/PdfTextExtractor.php';
require_once __DIR__ . '/../config/HistorialPdfMapper.php';

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

$stmt = $db->prepare('SELECT id_atleta FROM historial_clinico WHERE id_atleta = :id');
$stmt->execute(['id' => $id_atleta]);
if ($stmt->fetch()) {
    http_response_code(409);
    die('Este atleta ya tiene historial clínico capturado — no se puede reimportar encima.');
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
                    $errores[] = 'No se pudo extraer texto de este PDF (¿es una imagen escaneada sin texto seleccionable?). Captura el historial manualmente.';
                } else {
                    $resultado = HistorialPdfMapper::mapear($texto);
                    $_SESSION['ssos_prefill_historial'][$id_atleta] = $resultado['campos'];
                    $_SESSION['ssos_prefill_historial_demografico'][$id_atleta] = $resultado['demograficos'];
                }
            } catch (\Throwable $e) {
                $errores[] = 'No se pudo procesar el archivo: ' . $e->getMessage();
                error_log('[SSOS importar_pdf_historial] ' . $e->getMessage());
            }
        }
    }
}

$ssos_page_title = 'Importar PDF de Historial · ' . $atleta['nombre_completo'];
$ssos_breadcrumb_atleta = ['id_atleta' => $id_atleta, 'nombre' => $atleta['nombre_completo']];
require __DIR__ . '/../partials/header.php';
?>

<span class="ssos-role-badge">Importar PDF de Historial Clínico</span>
<h2 class="mt-3"><?= e($atleta['nombre_completo']) ?></h2>

<?php foreach ($errores as $error): ?>
    <div class="alert alert-danger ssos-alert" role="alert"><?= e($error) ?></div>
<?php endforeach; ?>

<?php if ($resultado !== null): ?>
    <div class="alert alert-warning ssos-alert" role="alert">
        Texto extraído y prellenado en el formulario — <strong>ningún dato se guardó todavía</strong>.
        Revisa y corrige cada campo antes de presionar "Guardar Historial Clínico".
    </div>
    <?php if (!empty($resultado['demograficos'])): ?>
        <div class="ssos-table-card mb-3">
            <h5 class="mb-2">Datos demográficos detectados en el PDF (sólo informativos)</h5>
            <p class="text-body-secondary mb-2">
                Estos campos no se guardan aquí (viven en el perfil del atleta o en una evaluación de
                antropometría). Verifica que coincidan con el expediente y actualízalos manualmente si es necesario.
            </p>
            <ul class="mb-0">
                <?php foreach ($resultado['demograficos'] as $etiqueta => $valor): ?>
                    <li><strong><?= e(ucfirst($etiqueta)) ?>:</strong> <?= e((string) $valor) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php
        $etiquetasCampos = [
            'telefono_personal' => 'Teléfono personal', 'correo_electronico' => 'Correo electrónico',
            'nombre_medico' => 'Nombre del médico', 'telefono_medico' => 'Teléfono del médico',
            'contacto_emergencia_nombre' => 'Contacto de emergencia', 'contacto_emergencia_telefono' => 'Teléfono de emergencia',
            'actividades_ejercicio_actual' => 'Actividades de ejercicio', 'dias_ejercicio_moderado_semana' => 'Días de ejercicio/semana',
            'objetivo_perdida_peso' => 'Objetivo: pérdida de peso', 'objetivo_masa_muscular' => 'Objetivo: masa muscular',
            'objetivo_rendimiento_deportivo' => 'Objetivo: rendimiento', 'objetivo_mejorar_salud' => 'Objetivo: mejorar salud',
            'dieta_saludable_score' => 'Dieta saludable (score)', 'sigue_dieta_actual' => 'Dieta actual',
            'consumo_sal' => 'Consumo de sal', 'consumo_azucar' => 'Consumo de azúcar', 'consumo_grasas' => 'Consumo de grasas',
            'control_antojos_score' => 'Control de antojos', 'bebidas_alcoholicas_semana' => 'Alcohol/semana', 'consumo_cafeina' => 'Cafeína',
            'sueno_adecuado' => 'Sueño', 'nivel_estres_score' => 'Nivel de estrés', 'tecnicas_manejo_estres' => 'Técnicas de estrés',
            'fuma_o_vapea' => 'Fuma/vapea', 'ocupacion' => 'Ocupación', 'trabajo_sedentario_detalle' => 'Trabajo sedentario',
            'trabajo_movimientos_repetitivos_detalle' => 'Movimientos repetitivos', 'trabajo_calzado_tacon' => 'Calzado con tacón',
            'actividad_recreativa_detalle' => 'Actividad recreativa', 'otro_pasatiempo_detalle' => 'Otro pasatiempo',
            'cirugias_previas' => 'Lesiones/cirugías previas', 'rehabilitacion_adecuada_autorizacion' => 'Rehabilitación/autorización',
            'condicion_cronica' => 'Condición crónica', 'medicamentos_actuales' => 'Medicamentos actuales',
            'notas_adicionales' => 'Notas adicionales',
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
            <p class="text-body-secondary mb-2">
                El PDF no traía respuesta para estos <?= count($camposVacios) ?> campos. No es un error de
                extracción — revísalos en el wizard y complétalos si aplica.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($camposVacios as $etiqueta): ?>
                    <span class="badge text-bg-warning"><?= e($etiqueta) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    <a href="historial_form.php?id_atleta=<?= $id_atleta ?>&desde_pdf=1" class="btn btn-ssos-turquesa btn-lg">
        Continuar a Revisar y Guardar Historial Clínico
    </a>
<?php else: ?>
    <div class="ssos-table-card">
        <p class="text-body-secondary">
            Sube el PDF de historial clínico (formulario "Historial clínico" / "Información del
            Cliente" exportado en PDF). Se extrae el texto y se prellenan los campos del formulario de
            Historial Clínico — <strong>tú confirmas o corriges cada campo</strong> antes de guardar; nada
            se escribe en la base de datos en este paso.
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
