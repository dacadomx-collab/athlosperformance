<?php
declare(strict_types=1);

/**
 * ATHLOS SSOS v1.0 — MIGRACIÓN HISTÓRICA (Clientes.xlsx -> MariaDB)
 *
 * Herramienta de un solo uso para el Super Admin: sube el Excel de cobranza
 * legacy (columnas: Cliente, Nombre, Programa, Pago, Fecha) y lo vuelca a
 * `atletas` / `catalogo_servicios` / `membresias` / `pagos_asistencia`.
 *
 * IDEMPOTENTE: puede ejecutarse varias veces con el mismo archivo sin
 * duplicar datos — ver `yaImportado()` más abajo.
 *
 * NOTA IMPORTANTE (documentada también en RESUMEN_EJECUCION_SISTEMA.md):
 * la hoja fuente NO tiene columna de teléfono. `atletas.telefono` es
 * NOT NULL en el schema, así que se asigna un placeholder
 * "SIN-TEL-<id_excel>" que el Admin debe completar manualmente después.
 * El número de sesiones del paquete se infiere del primer número que
 * aparece en el texto de "Programa" (ej. "Funcional 8" -> 8); si no hay
 * ningún número (ej. "Promo familia especial") se asume 1 sesión — ambas
 * heurísticas quedan marcadas en el resumen para revisión humana.
 */

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/XlsxReader.php';

require_role('super_admin');

$db = ssos_db();
$errores = [];
$resumen = null;

/** Normaliza un nombre para comparación de duplicados (espacios/mayúsculas). */
function normalizar_nombre(string $nombre): string
{
    return mb_strtolower(trim(preg_replace('/\s+/', ' ', $nombre) ?? ''));
}

/** Convierte un serial de fecha de Excel (base 1899-12-30) a Y-m-d. */
function fecha_excel_a_ymd(string $serialRaw): string
{
    $serial = (int) (float) $serialRaw;
    $fecha = (new DateTimeImmutable('1899-12-30'))->modify("+{$serial} days");
    return $fecha->format('Y-m-d');
}

/** Extrae el primer número entero encontrado en el texto del programa (sesiones incluidas). */
function extraer_sesiones(string $programa): int
{
    if (preg_match('/(\d+)/', $programa, $m) === 1) {
        return max(1, (int) $m[1]);
    }
    return 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } elseif (empty($_FILES['archivo_excel']) || $_FILES['archivo_excel']['error'] !== UPLOAD_ERR_OK) {
        $errores[] = 'Debes subir un archivo .xlsx válido.';
    } else {
        $tmpPath = $_FILES['archivo_excel']['tmp_name'];
        $nombreArchivo = (string) $_FILES['archivo_excel']['name'];

        if (!str_ends_with(strtolower($nombreArchivo), '.xlsx')) {
            $errores[] = 'El archivo debe tener extensión .xlsx.';
        } elseif (!is_uploaded_file($tmpPath)) {
            $errores[] = 'Carga de archivo inválida.';
        } else {
            try {
                $sharedStrings = XlsxReader::readSharedStrings($tmpPath);
                $filas = XlsxReader::readSheetRows($tmpPath, 'xl/worksheets/sheet1.xml', $sharedStrings);

                $conteo = [
                    'atletas_creados' => 0,
                    'atletas_reutilizados' => 0,
                    'pagos_importados' => 0,
                    'pagos_ya_existentes' => 0,
                    'filas_omitidas' => 0,
                    'sesiones_asumidas_por_defecto' => 0,
                    'telefonos_pendientes' => 0,
                ];
                $detalle = [];
                $cacheAtletasPorNombre = [];

                $db->beginTransaction();

                foreach ($filas as $numFila => $fila) {
                    if ($numFila === 1) {
                        continue; // encabezado
                    }

                    $nombre = trim($fila['B'] ?? '');
                    $programa = trim($fila['C'] ?? '');
                    $pagoRaw = trim($fila['D'] ?? '');
                    $fechaRaw = trim($fila['E'] ?? '');
                    $idExcel = trim($fila['A'] ?? '');

                    if ($nombre === '' || $pagoRaw === '' || $fechaRaw === '' || !is_numeric($pagoRaw)) {
                        $conteo['filas_omitidas']++;
                        continue;
                    }

                    $monto = round((float) $pagoRaw, 2);
                    $fechaPago = fecha_excel_a_ymd($fechaRaw);
                    $nombreNormalizado = normalizar_nombre($nombre);

                    // ── Find-or-create atleta (dedup por nombre normalizado) ──
                    if (isset($cacheAtletasPorNombre[$nombreNormalizado])) {
                        $idAtleta = $cacheAtletasPorNombre[$nombreNormalizado];
                    } else {
                        $stmt = $db->prepare(
                            'SELECT id_atleta FROM atletas WHERE LOWER(TRIM(nombre_completo)) = :nombre LIMIT 1'
                        );
                        $stmt->execute(['nombre' => $nombreNormalizado]);
                        $existente = $stmt->fetch();

                        if ($existente) {
                            $idAtleta = (int) $existente['id_atleta'];
                            $conteo['atletas_reutilizados']++;
                        } else {
                            $stmt = $db->prepare(
                                'INSERT INTO atletas
                                    (nombre_completo, telefono, tipo_membresia, estatus, fuente_historial, fecha_ingreso)
                                 VALUES (:nombre, :telefono, \'sesion_unica\', \'activo\', \'migracion_excel\', :fecha_ingreso)'
                            );
                            $telefonoPlaceholder = 'SIN-TEL-' . ($idExcel !== '' ? (int) (float) $idExcel : $numFila);
                            $stmt->execute([
                                'nombre' => $nombre,
                                'telefono' => $telefonoPlaceholder,
                                'fecha_ingreso' => $fechaPago,
                            ]);
                            $idAtleta = (int) $db->lastInsertId();
                            $conteo['atletas_creados']++;
                            $conteo['telefonos_pendientes']++;
                        }

                        $cacheAtletasPorNombre[$nombreNormalizado] = $idAtleta;
                    }

                    // ── Idempotencia: ¿este pago ya fue importado antes? ──
                    $stmt = $db->prepare(
                        'SELECT id_pago FROM pagos_asistencia
                         WHERE id_atleta = :id_atleta AND monto = :monto AND fecha_pago = :fecha AND concepto_pago = :concepto
                         LIMIT 1'
                    );
                    $stmt->execute([
                        'id_atleta' => $idAtleta,
                        'monto' => $monto,
                        'fecha' => $fechaPago,
                        'concepto' => $programa,
                    ]);

                    if ($stmt->fetch()) {
                        $conteo['pagos_ya_existentes']++;
                        $detalle[] = "Fila {$numFila}: {$nombre} — ya importado, omitido.";
                        continue;
                    }

                    // ── Find-or-create catálogo del servicio/paquete ──
                    $sesiones = extraer_sesiones($programa);
                    if (preg_match('/\d+/', $programa) !== 1) {
                        $conteo['sesiones_asumidas_por_defecto']++;
                    }

                    $stmt = $db->prepare('SELECT id_servicio FROM catalogo_servicios WHERE nombre_servicio = :nombre LIMIT 1');
                    $stmt->execute(['nombre' => $programa]);
                    $servicioExistente = $stmt->fetch();

                    if ($servicioExistente) {
                        $idServicio = (int) $servicioExistente['id_servicio'];
                    } else {
                        $stmt = $db->prepare(
                            'INSERT INTO catalogo_servicios
                                (nombre_servicio, descripcion_tecnica, precio_base, duracion_minutos, tipo_servicio, numero_sesiones_incluidas)
                             VALUES (:nombre, :descripcion, :precio, 60, \'paquete\', :sesiones)'
                        );
                        $stmt->execute([
                            'nombre' => $programa,
                            'descripcion' => 'Migrado automáticamente desde Clientes.xlsx (clientes_cobranza).',
                            'precio' => $monto,
                            'sesiones' => $sesiones,
                        ]);
                        $idServicio = (int) $db->lastInsertId();
                    }

                    // ── Membresía (se asume saldo completo: sin historial de consumo en el Excel) ──
                    $stmt = $db->prepare(
                        'INSERT INTO membresias
                            (id_atleta, id_servicio, fecha_inicio, sesiones_totales, sesiones_restantes, precio_pagado, estatus, notas)
                         VALUES (:id_atleta, :id_servicio, :fecha, :sesiones_totales, :sesiones_restantes, :monto, \'activa\', \'Migrado desde Clientes.xlsx\')'
                    );
                    $stmt->execute([
                        'id_atleta' => $idAtleta,
                        'id_servicio' => $idServicio,
                        'fecha' => $fechaPago,
                        'sesiones_totales' => $sesiones,
                        'sesiones_restantes' => $sesiones,
                        'monto' => $monto,
                    ]);
                    $idMembresia = (int) $db->lastInsertId();

                    $stmt = $db->prepare(
                        'INSERT INTO pagos_asistencia (id_atleta, id_membresia, concepto_pago, monto, metodo_pago, fecha_pago, registrado_por)
                         VALUES (:id_atleta, :id_membresia, :concepto, :monto, \'efectivo\', :fecha, :usuario)'
                    );
                    $stmt->execute([
                        'id_atleta' => $idAtleta,
                        'id_membresia' => $idMembresia,
                        'concepto' => $programa,
                        'monto' => $monto,
                        'fecha' => $fechaPago,
                        'usuario' => $_SESSION['id_usuario'],
                    ]);

                    $conteo['pagos_importados']++;
                    $detalle[] = "Fila {$numFila}: {$nombre} — {$programa} (\${$monto}, {$sesiones} sesiones) importado.";
                }

                $db->commit();
                $resumen = ['conteo' => $conteo, 'detalle' => $detalle];
            } catch (\Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $errores[] = 'No se pudo procesar el archivo. Detalle técnico registrado en el log del servidor.';
                error_log('[SSOS migrar_excel] ' . $e->getMessage());
            }
        }
    }
}

$ssos_page_title = 'Migración de Clientes.xlsx';
$ssos_active_nav = 'dashboard';
require __DIR__ . '/../partials/header.php';
?>

<span class="ssos-role-badge">Super Admin · AXON_DCD</span>
<h2 class="mt-3">Migración Histórica — Clientes.xlsx</h2>
<p class="text-body-secondary">
    Sube el archivo <code>Clientes.xlsx</code> (hoja "Clientes": Cliente, Nombre, Programa, Pago, Fecha).
    Es seguro ejecutar esta herramienta más de una vez con el mismo archivo — los pagos ya
    importados se detectan y se omiten automáticamente.
</p>

<?php foreach ($errores as $error): ?>
    <div class="alert alert-danger ssos-alert" role="alert"><?= e($error) ?></div>
<?php endforeach; ?>

<?php if ($resumen): ?>
    <div class="ssos-widget-grid">
        <div class="ssos-widget">
            <div class="ssos-widget-value"><?= (int) $resumen['conteo']['atletas_creados'] ?></div>
            <div class="ssos-widget-label">Atletas Creados</div>
        </div>
        <div class="ssos-widget">
            <div class="ssos-widget-value"><?= (int) $resumen['conteo']['atletas_reutilizados'] ?></div>
            <div class="ssos-widget-label">Atletas Reutilizados</div>
        </div>
        <div class="ssos-widget">
            <div class="ssos-widget-value"><?= (int) $resumen['conteo']['pagos_importados'] ?></div>
            <div class="ssos-widget-label">Pagos Importados</div>
        </div>
        <div class="ssos-widget">
            <div class="ssos-widget-value"><?= (int) $resumen['conteo']['pagos_ya_existentes'] ?></div>
            <div class="ssos-widget-label">Pagos Ya Existentes (omitidos)</div>
        </div>
    </div>

    <?php if ($resumen['conteo']['telefonos_pendientes'] > 0): ?>
        <div class="alert alert-warning ssos-alert" role="alert">
            <strong><?= (int) $resumen['conteo']['telefonos_pendientes'] ?> atleta(s)</strong> se crearon con
            teléfono placeholder (<code>SIN-TEL-*</code>) porque el Excel de origen no incluye ese dato.
            Complétalo manualmente cuando tengas el número real.
        </div>
    <?php endif; ?>

    <?php if ($resumen['conteo']['sesiones_asumidas_por_defecto'] > 0): ?>
        <div class="alert alert-warning ssos-alert" role="alert">
            <strong><?= (int) $resumen['conteo']['sesiones_asumidas_por_defecto'] ?> paquete(s)</strong> no
            tenían un número de sesiones detectable en el texto de "Programa" — se asumió 1 sesión por defecto.
        </div>
    <?php endif; ?>

    <div class="ssos-table-card">
        <h5 class="mb-3">Detalle por fila</h5>
        <ul class="mb-0">
            <?php foreach ($resumen['detalle'] as $linea): ?>
                <li><?= e($linea) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <br>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="ssos-table-card">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <div class="mb-3">
        <label for="archivo_excel" class="form-label fw-bold">Archivo Clientes.xlsx</label>
        <input type="file" class="form-control" id="archivo_excel" name="archivo_excel" accept=".xlsx" required>
    </div>
    <button type="submit" class="btn btn-ssos-primary">Procesar Migración</button>
</form>

<?php require __DIR__ . '/../partials/footer.php'; ?>
