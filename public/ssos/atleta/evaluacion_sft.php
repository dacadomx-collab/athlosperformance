<?php
declare(strict_types=1);

/**
 * ATHLOS PERFORMANCE — MÓDULO "EVALUACIÓN (SFT)" PARA ADULTO MAYOR
 *
 * Hub del Senior Fitness Test + checklist biomecánico de Sentadilla Overhead.
 * REGLA-01 (candado cognitivo): sólo se llega aquí cuando historial_clinico
 * YA existe con tipo_historial = 'mayor_65' — expediente.php no ofrece el
 * link a esta página hasta ese momento (flujo secuencial: Historial primero).
 * Esta página vuelve a validar esa condición server-side (nunca confiar sólo
 * en que la UI oculte el link — cualquiera con la URL directa debe topar con
 * el mismo candado).
 *
 * Las 4 imágenes de referencia (Figuras 5.15-5.18, protocolo Rikli & Jones)
 * son fotografías con derechos de autor del libro/plantilla original — no se
 * extraen del PDF ni se descargan de internet. Se dejan como placeholders
 * mapeados a `img/sft/figura-5-1X.jpg`; el staff coloca ahí su propia copia
 * con licencia si quiere mostrarlas.
 */

require_once __DIR__ . '/../config/helpers.php';

require_role('coach', 'admin', 'super_admin');

$db = ssos_db();

$id_atleta = filter_input(INPUT_GET, 'id_atleta', FILTER_VALIDATE_INT) ?: null;
if ($id_atleta === null) {
    http_response_code(400);
    die('Falta id_atleta.');
}

$stmt = $db->prepare('SELECT id_atleta, nombre_completo, sexo FROM atletas WHERE id_atleta = :id');
$stmt->execute(['id' => $id_atleta]);
$atleta = $stmt->fetch();

if (!$atleta) {
    http_response_code(404);
    die('Atleta no encontrado.');
}

$stmt = $db->prepare('SELECT tipo_historial FROM historial_clinico WHERE id_atleta = :id');
$stmt->execute(['id' => $id_atleta]);
$historial = $stmt->fetch();

if (!$historial || $historial['tipo_historial'] !== 'mayor_65') {
    http_response_code(403);
    die('Este módulo requiere un Historial Clínico capturado y clasificado como Adulto Mayor (65+). Captúralo primero desde el Expediente.');
}

$stmt = $db->prepare(
    'SELECT id_evaluacion_sft, fecha_evaluacion, semaforo_general
     FROM evaluaciones_sft WHERE id_atleta = :id ORDER BY fecha_evaluacion DESC'
);
$stmt->execute(['id' => $id_atleta]);
$evaluacionesPrevias = $stmt->fetchAll();

// ── Tabla de normas SFT — sólo el género del atleta (REGLA 2: "Inteligencia
// de Género"). Si el sexo del atleta aún no está definido, se muestran ambas
// tablas con una nota, en vez de adivinar cuál ocultar. ──────────────────────
$sexoAtleta = $atleta['sexo'] ?? 'no_especificado';
$mostrarAmbasTablas = !in_array($sexoAtleta, ['masculino', 'femenino'], true);

$variables = [
    'chair_sit_reach' => ['label' => 'Chair Sit-&-Reach (cm +/-)'],
    'back_scratch' => ['label' => 'Back Scratch (cm +/-)'],
    'chair_stand' => ['label' => 'Chair Stand (reps)'],
    'arm_curl' => ['label' => 'Arm Curl (reps)'],
    'time_up_go' => ['label' => 'Time Up-&-Go (segundos)'],
    'two_min_step' => ['label' => '2-Min Step (pasos)'],
];

/** @return array{rangos: array<int,string>, filas: array<string, array<string,string>>} */
function obtener_normas_sft(PDO $db, string $sexo, array $variables): array
{
    $stmt = $db->prepare(
        'SELECT variable, edad_min, edad_max, valor_min, valor_max
         FROM percentiles_sft_referencia WHERE sexo = :sexo ORDER BY variable, edad_min'
    );
    $stmt->execute(['sexo' => $sexo]);

    $rangos = [];
    $filas = [];
    foreach ($stmt->fetchAll() as $r) {
        $rango = "{$r['edad_min']}-{$r['edad_max']}";
        if (!in_array($rango, $rangos, true)) {
            $rangos[] = $rango;
        }
        $label = $variables[$r['variable']]['label'] ?? $r['variable'];
        $filas[$label][$rango] = "{$r['valor_min']}–{$r['valor_max']}";
    }

    return ['rangos' => $rangos, 'filas' => $filas];
}

$normasMasculino = ($sexoAtleta === 'masculino' || $mostrarAmbasTablas) ? obtener_normas_sft($db, 'masculino', $variables) : null;
$normasFemenino = ($sexoAtleta === 'femenino' || $mostrarAmbasTablas) ? obtener_normas_sft($db, 'femenino', $variables) : null;

$ssos_page_title = 'Evaluación (SFT) · ' . $atleta['nombre_completo'];
$ssos_breadcrumb_atleta = ['id_atleta' => $id_atleta, 'nombre' => $atleta['nombre_completo']];
require __DIR__ . '/../partials/header.php';

/** Tabla de normas SFT en el mismo layout visual que la Ficha Evaluación original. */
function render_tabla_normas(string $titulo, array $normas, array $variables): void
{
    ?>
    <div class="ssos-table-card mb-3">
        <h5 class="mb-2"><?= e($titulo) ?></h5>
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>Prueba</th>
                        <?php foreach ($normas['rangos'] as $rango): ?>
                            <th class="text-center"><?= e($rango) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($variables as $config): ?>
                        <tr>
                            <th scope="row"><?= e($config['label']) ?></th>
                            <?php foreach ($normas['rangos'] as $rango): ?>
                                <td class="text-center"><?= e($normas['filas'][$config['label']][$rango] ?? '—') ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
?>

<span class="ssos-role-badge">Módulo Evaluación (SFT)</span>
<h2 class="mt-3"><?= e($atleta['nombre_completo']) ?></h2>

<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="importar_pdf_sft.php?id_atleta=<?= $id_atleta ?>" class="btn btn-ssos-turquesa btn-lg">
        📄 Importar PDF: Ficha Evaluación SFT &amp; Sentadilla
    </a>
    <a href="sft_form.php?id_atleta=<?= $id_atleta ?>" class="btn btn-ssos-primary btn-lg">
        🏃 Nuevo Senior Fitness Test (manual)
    </a>
</div>

<div class="ssos-table-card mb-4">
    <h5 class="mb-3">Ayudas Visuales — Análisis de Sentadilla Overhead</h5>
    <p class="text-body-secondary mb-3">
        Referencia visual del protocolo (Rikli &amp; Jones) para calificar las compensaciones posturales
        durante la sentadilla. Coloca tus propias imágenes con licencia en
        <code>public/ssos/img/sft/</code> — mientras no existan, se muestra un marcador de posición.
    </p>
    <div class="row g-3">
        <?php
        $figuras = [
            'figura-5-15.jpg' => 'Figura 5.15 — Feet Flatten, Feet Turn Out, Heel Rises',
            'figura-5-16.jpg' => 'Figura 5.16 — Knees Move Inward',
            'figura-5-17.jpg' => 'Figura 5.17 — Excessive Forward Lean, Lower Back Arches/Rounds',
            'figura-5-18.jpg' => 'Figura 5.18 — Arms Fall Forward',
        ];
        foreach ($figuras as $archivo => $etiqueta):
        ?>
            <div class="col-sm-6 col-lg-3">
                <div class="ssos-sft-figura">
                    <img src="<?= e(ssos_asset('img/sft/' . $archivo)) ?>" alt="<?= e($etiqueta) ?>"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="ssos-sft-figura-placeholder">🖼️<br><?= e($etiqueta) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($mostrarAmbasTablas): ?>
    <div class="alert alert-warning ssos-alert" role="alert">
        El sexo del atleta aún no está definido en su perfil — se muestran ambas tablas de referencia.
        Complétalo en el Historial Clínico o en el perfil del atleta para que aquí sólo aparezca la que
        corresponde.
    </div>
<?php endif; ?>

<?php if ($normasMasculino): ?>
    <?php render_tabla_normas('SFT Norms — Hombres', $normasMasculino, $variables); ?>
<?php endif; ?>
<?php if ($normasFemenino): ?>
    <?php render_tabla_normas('SFT Norms — Mujeres', $normasFemenino, $variables); ?>
<?php endif; ?>

<div class="ssos-table-card">
    <h5 class="mb-3">Evaluaciones SFT previas</h5>
    <?php if (empty($evaluacionesPrevias)): ?>
        <p class="text-body-secondary mb-0">Aún no hay evaluaciones SFT registradas para este atleta.</p>
    <?php else: ?>
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Fecha</th><th>Semáforo general</th></tr></thead>
            <tbody>
                <?php foreach ($evaluacionesPrevias as $ev): ?>
                    <tr>
                        <td><?= e($ev['fecha_evaluacion']) ?></td>
                        <td><?= e($ev['semaforo_general'] ?? 'sin calcular') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
