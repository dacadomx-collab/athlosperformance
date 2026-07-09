<?php
declare(strict_types=1);

/**
 * ATHLOS PERFORMANCE — HISTORIAL CLÍNICO UNIFICADO (alta/edición)
 * Un registro por atleta (UNIQUE id_atleta) — este formulario hace upsert.
 *
 * Presentado como wizard de 8 pasos (navegación 100% client-side vía
 * data-ssos-wizard-*, ver js/main.js) pero sigue siendo UN SOLO <form> con UN
 * SOLO POST — no hay estado de wizard en el servidor ni envíos parciales.
 * Esto evita el riesgo real de un wizard multi-request (guardar un paso y
 * perder los demás si el coach cierra la pestaña a medias) a cambio de nada:
 * la BD sólo necesita la fila completa al final.
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
// nunca pisa con NULL una columna que este formulario no muestra.
$campos = [
    'tipo_historial',
    'telefono_personal', 'correo_electronico',
    'nombre_medico', 'telefono_medico', 'contacto_emergencia_nombre', 'contacto_emergencia_telefono',
    'actividades_ejercicio_actual', 'dias_ejercicio_moderado_semana',
    'objetivo_perdida_peso', 'objetivo_masa_muscular', 'objetivo_rendimiento_deportivo', 'objetivo_mejorar_salud',
    'dieta_saludable_score', 'sigue_dieta_actual', 'consumo_sal', 'consumo_azucar', 'consumo_grasas',
    'control_antojos_score', 'bebidas_alcoholicas_semana', 'consumo_cafeina',
    'sueno_adecuado', 'nivel_estres_score', 'tecnicas_manejo_estres', 'fuma_o_vapea',
    'ocupacion', 'trabajo_sedentario_detalle', 'trabajo_movimientos_repetitivos_detalle', 'trabajo_calzado_tacon',
    'actividad_recreativa_detalle', 'otro_pasatiempo_detalle',
    'cirugias_previas', 'rehabilitacion_adecuada_autorizacion', 'condicion_cronica', 'medicamentos_actuales',
    'autorizacion_medica_ejercicio',
    'notas_adicionales',
];
$checkboxes = ['autorizacion_medica_ejercicio', 'trabajo_calzado_tacon'];

$errores = [];
$exito = false;

$stmt = $db->prepare('SELECT * FROM historial_clinico WHERE id_atleta = :id');
$stmt->execute(['id' => $id_atleta]);
$actual = $stmt->fetch() ?: [];
$habiaHistorialEnBD = !empty($actual);

// Prefill de un solo uso desde importar_pdf_historial.php — sólo aplica si
// todavía no existe un registro real en BD (nunca pisa una captura guardada).
if (empty($actual) && !empty($_SESSION['ssos_prefill_historial'][$id_atleta])) {
    $actual = $_SESSION['ssos_prefill_historial'][$id_atleta];
    unset($_SESSION['ssos_prefill_historial'][$id_atleta]);
}

// Prefill demográfico (edad/género/altura/peso) — normalizado desde las claves
// crudas de HistorialPdfMapper (genero: "M"/"F" libre, altura en metros) a lo
// que esperan los inputs "atleta_*" de abajo. Sólo informativo/editable aquí;
// el guardado real hacia `atletas`/`evaluaciones_antropometria` respeta su
// propio criterio de "sólo si está vacío" (ver bloque POST más abajo).
$prefillDemografico = [];
if (!empty($_SESSION['ssos_prefill_historial_demografico'][$id_atleta])) {
    $demo = $_SESSION['ssos_prefill_historial_demografico'][$id_atleta];
    unset($_SESSION['ssos_prefill_historial_demografico'][$id_atleta]);

    $prefillDemografico['edad'] = is_numeric($demo['edad'] ?? null) ? (string) (int) $demo['edad'] : '';
    $generoCrudo = mb_strtolower(trim((string) ($demo['genero'] ?? '')));
    $prefillDemografico['sexo'] = match (true) {
        str_starts_with($generoCrudo, 'm') => 'masculino',
        str_starts_with($generoCrudo, 'f') => 'femenino',
        default => '',
    };
    $prefillDemografico['altura_cm'] = is_numeric($demo['altura'] ?? null)
        ? (string) round(((float) $demo['altura']) * ((float) $demo['altura'] < 3 ? 100 : 1), 1)
        : '';
    $prefillDemografico['peso_kg'] = is_numeric($demo['peso'] ?? null) ? (string) $demo['peso'] : '';
}

// REGLA-01 (candado cognitivo): si el PDF que se acaba de importar detectó
// una edad >= 65 y este historial todavía no existía, el tipo se fija y
// bloquea a mayor_65 — el módulo "Evaluación (SFT)" en expediente.php sólo
// aparece cuando tipo_historial === 'mayor_65', así que este candado es lo
// que efectivamente activa el flujo secuencial. No se bloquea en ediciones
// posteriores (sin un PDF nuevo en esta misma carga) para no atrapar al
// coach si de verdad necesita corregir una clasificación ya guardada.
$tipoHistorialBloqueado = !$habiaHistorialEnBD
    && $prefillDemografico !== []
    && (int) ($prefillDemografico['edad'] ?? 0) >= 65;
if ($tipoHistorialBloqueado) {
    $actual['tipo_historial'] = 'mayor_65';
}

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
                if (in_array($campo, $checkboxes, true)) {
                    continue;
                }
                $valores[$campo] = trim((string) ($_POST[$campo] ?? '')) !== '' ? trim((string) $_POST[$campo]) : null;
            }
            // checkboxes: valor NULL/0 si no vienen marcados
            foreach ($checkboxes as $checkbox) {
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

                // Sincronización a atletas/evaluaciones_antropometria — "sólo si
                // está vacío", nunca sobreescribe un dato real ya capturado por
                // otro flujo (antropometria_form.php es el dueño natural de
                // peso/estatura; aquí sólo se completa el hueco si nunca se
                // llenó). Falla aislada de la del historial: si esto truena, el
                // historial ya se guardó y no se pierde ese trabajo.
                try {
                    ssos_sincronizar_datos_atleta_desde_historial($db, $id_atleta, $atleta, $habiaHistorialEnBD);
                } catch (\Throwable $eSync) {
                    $errores[] = 'El historial se guardó, pero no se pudo sincronizar edad/sexo/antropometría: ' . $eSync->getMessage();
                    error_log('[SSOS historial_form sync] ' . $eSync->getMessage());
                }
            } catch (\Throwable $e) {
                $errores[] = 'No se pudo guardar el historial clínico: ' . $e->getMessage();
                error_log('[SSOS historial_form] ' . $e->getMessage());
            }
        }
    }
}

/**
 * Completa atletas.fecha_nacimiento/sexo (si están vacíos) y crea la primera
 * evaluaciones_antropometria (sólo si el historial se está creando por primera
 * vez) a partir de los 4 campos "atleta_*" del wizard. Nunca sobreescribe un
 * valor ya presente — REGLA explícita: peso/estatura/edad reales capturados en
 * otro momento (antropometria_form.php, alta manual del atleta) tienen
 * prioridad sobre lo que traiga este formulario.
 */
function ssos_sincronizar_datos_atleta_desde_historial(PDO $db, int $id_atleta, array $atleta, bool $habiaHistorialEnBD): void
{
    $edad = filter_input(INPUT_POST, 'atleta_edad', FILTER_VALIDATE_INT) ?: null;
    $sexo = (string) ($_POST['atleta_sexo'] ?? '');
    $alturaCm = filter_input(INPUT_POST, 'atleta_altura_cm', FILTER_VALIDATE_FLOAT) ?: null;
    $pesoKg = filter_input(INPUT_POST, 'atleta_peso_kg', FILTER_VALIDATE_FLOAT) ?: null;

    $sets = [];
    $valores = ['id' => $id_atleta];

    if (empty($atleta['fecha_nacimiento']) && $edad !== null && $edad > 0) {
        // Aproximación deliberada: el PDF sólo trae "Edad", no una fecha de
        // nacimiento real. 1 de enero del año correspondiente — no es la
        // fecha real, es un relleno para no dejar el campo NULL. Corregible
        // a mano en el perfil del atleta si se necesita precisión.
        $sets[] = 'fecha_nacimiento = :fecha_nacimiento';
        $valores['fecha_nacimiento'] = ((int) date('Y') - $edad) . '-01-01';
    }

    if (in_array($sexo, ['masculino', 'femenino'], true) && (empty($atleta['sexo']) || $atleta['sexo'] === 'no_especificado')) {
        $sets[] = 'sexo = :sexo';
        $valores['sexo'] = $sexo;
    }

    if ($sets !== []) {
        $stmt = $db->prepare('UPDATE atletas SET ' . implode(', ', $sets) . ' WHERE id_atleta = :id');
        $stmt->execute($valores);
    }

    // La antropometría es histórico acumulativo (una fila por evaluación) —
    // sólo se siembra la primera vez que este atleta recibe un historial
    // clínico, para no generar una fila nueva cada vez que se edita/resave
    // el historial.
    if ($habiaHistorialEnBD || $alturaCm === null || $pesoKg === null || $alturaCm <= 0 || $pesoKg <= 0) {
        return;
    }

    $stmtExiste = $db->prepare('SELECT COUNT(*) FROM evaluaciones_antropometria WHERE id_atleta = :id');
    $stmtExiste->execute(['id' => $id_atleta]);
    if ((int) $stmtExiste->fetchColumn() > 0) {
        return; // ya existe al menos una evaluación real — no se siembra una sintética encima.
    }

    $alturaM = $alturaCm / 100;
    $imc = round($pesoKg / ($alturaM ** 2), 2);

    $stmt = $db->prepare(
        'INSERT INTO evaluaciones_antropometria
            (id_atleta, fecha_antropometria, asesor, peso_kg, estatura_cm, imc, clasificacion_imc, indice_ponderal, capturado_por)
         VALUES (:id_atleta, :fecha, :asesor, :peso_kg, :estatura_cm, :imc, :clasificacion_imc, :indice_ponderal, :capturado_por)'
    );
    $stmt->execute([
        'id_atleta' => $id_atleta,
        'fecha' => date('Y-m-d'),
        'asesor' => 'Wizard de Historial Clínico',
        'peso_kg' => $pesoKg,
        'estatura_cm' => $alturaCm,
        'imc' => $imc,
        'clasificacion_imc' => ssos_clasificar_imc($imc),
        'indice_ponderal' => round($pesoKg / ($alturaM ** 3), 3),
        'capturado_por' => $_SESSION['id_usuario'],
    ]);
}

/** @return string Atributo value="" ya escapado, para inputs de texto/número. */
function historial_valor(array $actual, string $campo): string
{
    return e((string) ($actual[$campo] ?? ''));
}

$pasos = [
    1 => 'Información del Cliente',
    2 => 'Ejercicio',
    3 => 'Dieta y Nutrición',
    4 => 'Estilo de Vida',
    5 => 'Ocupación',
    6 => 'Recreación',
    7 => 'Historial Médico',
    8 => 'Notas Adicionales',
];

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

<div class="ssos-wizard" data-ssos-wizard>
    <div class="ssos-wizard-header">
        <span class="ssos-wizard-step-label" data-ssos-wizard-step-label>Paso 1 de 8</span>
        <span class="ssos-wizard-module-label" data-ssos-wizard-module-label><?= e($pasos[1]) ?></span>
    </div>
    <div class="progress ssos-wizard-progress" role="progressbar" aria-label="Progreso del wizard" aria-valuemin="0" aria-valuemax="100">
        <div class="progress-bar ssos-wizard-progress-bar" data-ssos-wizard-progress-bar style="width: 12.5%"></div>
    </div>

    <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="ssos-wizard-step" data-ssos-wizard-step="1" data-ssos-wizard-module="Información del Cliente">
            <div class="ssos-table-card mb-3">
                <label class="form-label fw-bold">Tipo de historial</label>
                <?php if ($tipoHistorialBloqueado): ?>
                    <div class="alert alert-info ssos-alert py-2 mb-2">
                        🔒 Fijado automáticamente a <strong>Adulto Mayor (65+)</strong> — el PDF importado
                        indica <?= (int) $prefillDemografico['edad'] ?> años. Al guardar, se habilitará el
                        módulo "Evaluación (SFT)".
                    </div>
                    <input type="hidden" name="tipo_historial" value="mayor_65">
                    <select class="form-select" disabled>
                        <option selected>Adulto Mayor (65+) — bloqueado por edad detectada en PDF</option>
                    </select>
                <?php else: ?>
                    <select name="tipo_historial" class="form-select" required>
                        <option value="menor_65" <?= ($actual['tipo_historial'] ?? '') === 'menor_65' ? 'selected' : '' ?>>Menor de 65 años</option>
                        <option value="mayor_65" <?= ($actual['tipo_historial'] ?? '') === 'mayor_65' ? 'selected' : '' ?>>Adulto Mayor (65+)</option>
                    </select>
                <?php endif; ?>
            </div>
            <div class="ssos-table-card mb-3">
                <h5>Datos del atleta</h5>
                <p class="text-body-secondary small mb-2">
                    Estos 4 campos no viven en el historial clínico — al guardar, se usan para
                    completar el perfil del atleta (<code>atletas</code>) y para registrar una primera
                    evaluación de antropometría (<code>evaluaciones_antropometria</code>), pero
                    <strong>sólo si esos datos todavía no existen</strong> (nunca se sobreescribe una
                    fecha de nacimiento, sexo o evaluación ya capturados). La fecha de nacimiento se
                    estima a partir de la edad (1 de enero del año correspondiente) — es aproximada,
                    corrígela en el perfil del atleta si necesitas precisión exacta.
                </p>
                <div class="row">
                    <div class="col-sm-3 mb-2">
                        <label class="form-label">Edad</label>
                        <input type="number" name="atleta_edad" class="form-control" min="0" max="120" value="<?= historial_valor($prefillDemografico, 'edad') ?>">
                    </div>
                    <div class="col-sm-3 mb-2">
                        <label class="form-label">Género</label>
                        <select name="atleta_sexo" class="form-select">
                            <option value="">—</option>
                            <option value="masculino" <?= ($prefillDemografico['sexo'] ?? '') === 'masculino' ? 'selected' : '' ?>>Masculino</option>
                            <option value="femenino" <?= ($prefillDemografico['sexo'] ?? '') === 'femenino' ? 'selected' : '' ?>>Femenino</option>
                        </select>
                    </div>
                    <div class="col-sm-3 mb-2">
                        <label class="form-label">Altura (cm)</label>
                        <input type="number" step="0.1" name="atleta_altura_cm" class="form-control" value="<?= historial_valor($prefillDemografico, 'altura_cm') ?>">
                    </div>
                    <div class="col-sm-3 mb-2">
                        <label class="form-label">Peso (kg)</label>
                        <input type="number" step="0.1" name="atleta_peso_kg" class="form-control" value="<?= historial_valor($prefillDemografico, 'peso_kg') ?>">
                    </div>
                </div>
            </div>
            <div class="ssos-table-card mb-3">
                <h5>Contacto directo (Menor de 65)</h5>
                <div class="row">
                    <div class="col-sm-6 mb-2">
                        <label class="form-label">Teléfono personal</label>
                        <input type="text" name="telefono_personal" class="form-control" value="<?= historial_valor($actual, 'telefono_personal') ?>">
                    </div>
                    <div class="col-sm-6 mb-2">
                        <label class="form-label">Correo electrónico</label>
                        <input type="email" name="correo_electronico" class="form-control" value="<?= historial_valor($actual, 'correo_electronico') ?>">
                    </div>
                </div>
            </div>
            <div class="ssos-table-card mb-3">
                <h5>Médico y contacto de emergencia (Adulto Mayor)</h5>
                <div class="row">
                    <div class="col-sm-6 mb-2">
                        <label class="form-label">Nombre del médico</label>
                        <input type="text" name="nombre_medico" class="form-control" value="<?= historial_valor($actual, 'nombre_medico') ?>">
                    </div>
                    <div class="col-sm-6 mb-2">
                        <label class="form-label">Teléfono del médico</label>
                        <input type="text" name="telefono_medico" class="form-control" value="<?= historial_valor($actual, 'telefono_medico') ?>">
                    </div>
                    <div class="col-sm-6 mb-2">
                        <label class="form-label">Contacto de emergencia</label>
                        <input type="text" name="contacto_emergencia_nombre" class="form-control" value="<?= historial_valor($actual, 'contacto_emergencia_nombre') ?>">
                    </div>
                    <div class="col-sm-6 mb-2">
                        <label class="form-label">Teléfono de emergencia</label>
                        <input type="text" name="contacto_emergencia_telefono" class="form-control" value="<?= historial_valor($actual, 'contacto_emergencia_telefono') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="ssos-wizard-step" data-ssos-wizard-step="2" data-ssos-wizard-module="Ejercicio" hidden>
            <div class="ssos-table-card mb-3">
                <h5>Ejercicio</h5>
                <div class="mb-2">
                    <label class="form-label">Actividades de ejercicio actuales</label>
                    <textarea name="actividades_ejercicio_actual" class="form-control" rows="2"><?= e($actual['actividades_ejercicio_actual'] ?? '') ?></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label">Días de ejercicio moderado por semana</label>
                    <input type="number" name="dias_ejercicio_moderado_semana" class="form-control" min="0" max="7" value="<?= historial_valor($actual, 'dias_ejercicio_moderado_semana') ?>">
                </div>
                <div class="row">
                    <div class="col-sm-3 mb-2">
                        <label class="form-label">Objetivo: Pérdida de peso (0-10)</label>
                        <input type="number" name="objetivo_perdida_peso" class="form-control" min="0" max="10" value="<?= historial_valor($actual, 'objetivo_perdida_peso') ?>">
                    </div>
                    <div class="col-sm-3 mb-2">
                        <label class="form-label">Objetivo: Masa muscular (0-10)</label>
                        <input type="number" name="objetivo_masa_muscular" class="form-control" min="0" max="10" value="<?= historial_valor($actual, 'objetivo_masa_muscular') ?>">
                    </div>
                    <div class="col-sm-3 mb-2">
                        <label class="form-label">Objetivo: Rendimiento (0-10)</label>
                        <input type="number" name="objetivo_rendimiento_deportivo" class="form-control" min="0" max="10" value="<?= historial_valor($actual, 'objetivo_rendimiento_deportivo') ?>">
                    </div>
                    <div class="col-sm-3 mb-2">
                        <label class="form-label">Objetivo: Mejorar salud (0-10)</label>
                        <input type="number" name="objetivo_mejorar_salud" class="form-control" min="0" max="10" value="<?= historial_valor($actual, 'objetivo_mejorar_salud') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="ssos-wizard-step" data-ssos-wizard-step="3" data-ssos-wizard-module="Dieta y Nutrición" hidden>
            <div class="ssos-table-card mb-3">
                <h5>Dieta y Nutrición</h5>
                <div class="row">
                    <div class="col-sm-4 mb-2">
                        <label class="form-label">Dieta saludable (0-10)</label>
                        <input type="number" name="dieta_saludable_score" class="form-control" min="0" max="10" value="<?= historial_valor($actual, 'dieta_saludable_score') ?>">
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
                        <label class="form-label">Control de antojos (0-10)</label>
                        <input type="number" name="control_antojos_score" class="form-control" min="0" max="10" value="<?= historial_valor($actual, 'control_antojos_score') ?>">
                    </div>
                    <div class="col-sm-4 mb-2">
                        <label class="form-label">Bebidas alcohólicas / semana</label>
                        <input type="number" name="bebidas_alcoholicas_semana" class="form-control" min="0" value="<?= historial_valor($actual, 'bebidas_alcoholicas_semana') ?>">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label">¿Sigue alguna dieta actualmente?</label>
                    <textarea name="sigue_dieta_actual" class="form-control" rows="2"><?= e($actual['sigue_dieta_actual'] ?? '') ?></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label">Consumo de cafeína (café, té, refrescos, bebidas energéticas) y frecuencia</label>
                    <input type="text" name="consumo_cafeina" class="form-control" value="<?= historial_valor($actual, 'consumo_cafeina') ?>">
                </div>
            </div>
        </div>

        <div class="ssos-wizard-step" data-ssos-wizard-step="4" data-ssos-wizard-module="Estilo de Vida" hidden>
            <div class="ssos-table-card mb-3">
                <h5>Estilo de vida</h5>
                <div class="mb-2">
                    <label class="form-label">¿Duerme lo suficiente?</label>
                    <textarea name="sueno_adecuado" class="form-control" rows="2"><?= e($actual['sueno_adecuado'] ?? '') ?></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label">Nivel de estrés (0-10)</label>
                    <input type="number" name="nivel_estres_score" class="form-control" min="0" max="10" value="<?= historial_valor($actual, 'nivel_estres_score') ?>">
                </div>
                <div class="mb-2">
                    <label class="form-label">Técnicas de manejo de estrés</label>
                    <textarea name="tecnicas_manejo_estres" class="form-control" rows="2"><?= e($actual['tecnicas_manejo_estres'] ?? '') ?></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label">¿Fuma o vapea?</label>
                    <textarea name="fuma_o_vapea" class="form-control" rows="1"><?= e($actual['fuma_o_vapea'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="ssos-wizard-step" data-ssos-wizard-step="5" data-ssos-wizard-module="Ocupación" hidden>
            <div class="ssos-table-card mb-3">
                <h5>Ocupación</h5>
                <div class="mb-2">
                    <label class="form-label">Ocupación</label>
                    <input type="text" name="ocupacion" class="form-control" value="<?= historial_valor($actual, 'ocupacion') ?>">
                </div>
                <div class="mb-2">
                    <label class="form-label">¿Su trabajo requiere largos periodos sentado? Detalle</label>
                    <textarea name="trabajo_sedentario_detalle" class="form-control" rows="2"><?= e($actual['trabajo_sedentario_detalle'] ?? '') ?></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label">¿Su trabajo requiere movimientos repetitivos? Detalle</label>
                    <textarea name="trabajo_movimientos_repetitivos_detalle" class="form-control" rows="2"><?= e($actual['trabajo_movimientos_repetitivos_detalle'] ?? '') ?></textarea>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input" name="trabajo_calzado_tacon" id="calzado_tacon" value="1" <?= !empty($actual['trabajo_calzado_tacon']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="calzado_tacon">Su trabajo requiere calzado con tacón (zapatos de vestir, botas de trabajo)</label>
                </div>
            </div>
        </div>

        <div class="ssos-wizard-step" data-ssos-wizard-step="6" data-ssos-wizard-module="Recreación" hidden>
            <div class="ssos-table-card mb-3">
                <h5>Recreación</h5>
                <div class="mb-2">
                    <label class="form-label">Actividades físicas recreativas (golf, esquí, etc.)</label>
                    <textarea name="actividad_recreativa_detalle" class="form-control" rows="2"><?= e($actual['actividad_recreativa_detalle'] ?? '') ?></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label">Otro pasatiempo (jardinería, dominó, pesca, música, etc.)</label>
                    <textarea name="otro_pasatiempo_detalle" class="form-control" rows="2"><?= e($actual['otro_pasatiempo_detalle'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="ssos-wizard-step" data-ssos-wizard-step="7" data-ssos-wizard-module="Historial Médico" hidden>
            <div class="ssos-table-card mb-3">
                <h5>Historial Médico</h5>
                <div class="mb-2">
                    <label class="form-label">Lesiones / Cirugías previas</label>
                    <textarea name="cirugias_previas" class="form-control" rows="2"><?= e($actual['cirugias_previas'] ?? '') ?></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label">¿Rehabilitación adecuada y autorización médica para volver a actividad física?</label>
                    <textarea name="rehabilitacion_adecuada_autorizacion" class="form-control" rows="2"><?= e($actual['rehabilitacion_adecuada_autorizacion'] ?? '') ?></textarea>
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
            </div>
        </div>

        <div class="ssos-wizard-step" data-ssos-wizard-step="8" data-ssos-wizard-module="Notas Adicionales" hidden>
            <div class="ssos-table-card mb-3">
                <label class="form-label">Notas adicionales</label>
                <textarea name="notas_adicionales" class="form-control" rows="3"><?= e($actual['notas_adicionales'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="ssos-wizard-nav">
            <a href="expediente.php?id_atleta=<?= $id_atleta ?>" class="btn btn-outline-secondary btn-lg">Cancelar</a>
            <button type="button" class="btn btn-outline-secondary btn-lg" data-ssos-wizard-prev hidden>⬅️ Anterior</button>
            <button type="button" class="btn btn-ssos-primary btn-lg" data-ssos-wizard-next>Siguiente ➡️</button>
            <button type="submit" class="btn btn-ssos-turquesa btn-lg" data-ssos-wizard-submit hidden>💾 Guardar Historial Clínico Completo</button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
