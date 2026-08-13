<?php
declare(strict_types=1);

/**
 * ATHLOS SSOS v1.0 — CASOS DE ÉXITO / TESTIMONIOS DE CLIENTES
 *
 * CRUD de reseñas con foto para Admin/Coach. El "Eliminar" es un soft delete
 * (estatus -> 'inactivo') — nunca se hace DELETE físico, para conservar el
 * historial de auditoría (mismo criterio que el resto del BackOffice: ver
 * `estatus` en `catalogo_servicios`/`membresias`). Sólo los testimonios con
 * estatus 'activo' se exponen en el carrusel público
 * (api/testimonios_publicos.php).
 */

require_once __DIR__ . '/../config/helpers.php';

require_role('admin', 'super_admin', 'coach');

$db = ssos_db();

const TESTIMONIO_MAX_BYTES = 2 * 1024 * 1024; // 2MB
const TESTIMONIO_MIME_EXT = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];
const TESTIMONIO_DIR_FS = __DIR__ . '/../../media/testimonios/';
const TESTIMONIO_DIR_PUBLICO = 'testimonios/';

$errores = [];
$mensajeOk = null;

/**
 * Valida y persiste la foto subida en `public/media/testimonios/` con un
 * nombre de archivo hasheado (nunca el nombre original del usuario) — evita
 * colisiones, sobreescrituras y cualquier intento de inyección vía nombre de
 * archivo (ej. "../../shell.php"). El MIME real se certifica con `finfo`
 * sobre el contenido del archivo, no con la extensión ni el Content-Type que
 * envía el navegador (ambos falsificables).
 *
 * @return string|null Ruta relativa pública (ej. "testimonios/ab12....jpg"), o null si no se subió archivo.
 * @throws \RuntimeException si se subió un archivo pero es inválido.
 */
function guardar_foto_testimonio(array $archivo): ?string
{
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $mensajes = [
            UPLOAD_ERR_INI_SIZE => 'La foto excede el tamaño máximo permitido por el servidor.',
            UPLOAD_ERR_FORM_SIZE => 'La foto excede el tamaño máximo definido en el formulario.',
            UPLOAD_ERR_PARTIAL => 'La foto se subió sólo parcialmente. Intenta de nuevo.',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal del servidor.',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo temporal en disco.',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la carga del archivo.',
        ];
        throw new \RuntimeException($mensajes[$archivo['error']] ?? 'Error de carga desconocido.');
    }

    if (!is_uploaded_file($archivo['tmp_name'])) {
        throw new \RuntimeException('Carga de archivo inválida.');
    }

    if ($archivo['size'] > TESTIMONIO_MAX_BYTES) {
        throw new \RuntimeException('La foto no puede pesar más de 2MB.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeReal = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);

    if (!isset(TESTIMONIO_MIME_EXT[$mimeReal])) {
        throw new \RuntimeException('Formato de imagen no permitido. Usa JPG, PNG o WEBP.');
    }

    if (!is_dir(TESTIMONIO_DIR_FS) && !mkdir(TESTIMONIO_DIR_FS, 0755, true) && !is_dir(TESTIMONIO_DIR_FS)) {
        throw new \RuntimeException('No se pudo preparar el directorio de fotos en el servidor.');
    }

    $nombreArchivo = bin2hex(random_bytes(16)) . '.' . TESTIMONIO_MIME_EXT[$mimeReal];
    $rutaDestino = TESTIMONIO_DIR_FS . $nombreArchivo;

    if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        throw new \RuntimeException('No se pudo guardar la foto en el servidor.');
    }

    return TESTIMONIO_DIR_PUBLICO . $nombreArchivo;
}

/** Elimina el archivo físico de una foto de testimonio (ruta relativa "testimonios/xxx.ext"). */
function eliminar_foto_testimonio(?string $rutaRelativa): void
{
    if ($rutaRelativa === null || $rutaRelativa === '') {
        return;
    }
    $rutaFs = TESTIMONIO_DIR_FS . basename($rutaRelativa);
    if (is_file($rutaFs)) {
        @unlink($rutaFs);
    }
}

// ── Alta de testimonio ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear_testimonio') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } else {
        $nombreCliente = trim((string) ($_POST['nombre_cliente'] ?? ''));
        $comentario = trim((string) ($_POST['comentario'] ?? ''));
        $fechaTestimonio = (string) ($_POST['fecha_testimonio'] ?? '');
        $ordenVisualizacion = filter_input(INPUT_POST, 'orden_visualizacion', FILTER_VALIDATE_INT) ?: 0;

        if ($nombreCliente === '' || mb_strlen($nombreCliente) > 150) {
            $errores[] = 'El nombre del cliente es obligatorio (máximo 150 caracteres).';
        }
        if ($comentario === '') {
            $errores[] = 'El comentario/reseña es obligatorio.';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaTestimonio)) {
            $errores[] = 'Fecha del testimonio inválida.';
        }

        $rutaFoto = null;
        if (empty($errores)) {
            try {
                $rutaFoto = guardar_foto_testimonio($_FILES['foto'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
            } catch (\RuntimeException $e) {
                $errores[] = $e->getMessage();
            }
        }

        if (empty($errores)) {
            $db->prepare(
                'INSERT INTO testimonios_clientes (nombre_cliente, comentario, foto_ruta, fecha_testimonio, orden_visualizacion, created_by)
                 VALUES (:nombre, :comentario, :foto, :fecha, :orden, :creado_por)'
            )->execute([
                'nombre' => $nombreCliente,
                'comentario' => $comentario,
                'foto' => $rutaFoto,
                'fecha' => $fechaTestimonio,
                'orden' => $ordenVisualizacion,
                'creado_por' => $_SESSION['id_usuario'],
            ]);
            $mensajeOk = 'Testimonio registrado. Ya está disponible para mostrarse en el carrusel público.';
        }
    }
}

// ── Edición de testimonio ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'editar_testimonio') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } else {
        $idTestimonio = filter_input(INPUT_POST, 'id_testimonio', FILTER_VALIDATE_INT);
        $nombreCliente = trim((string) ($_POST['nombre_cliente'] ?? ''));
        $comentario = trim((string) ($_POST['comentario'] ?? ''));
        $fechaTestimonio = (string) ($_POST['fecha_testimonio'] ?? '');
        $ordenVisualizacion = filter_input(INPUT_POST, 'orden_visualizacion', FILTER_VALIDATE_INT) ?: 0;

        if (!$idTestimonio) {
            $errores[] = 'Testimonio inválido.';
        }
        if ($nombreCliente === '' || mb_strlen($nombreCliente) > 150) {
            $errores[] = 'El nombre del cliente es obligatorio (máximo 150 caracteres).';
        }
        if ($comentario === '') {
            $errores[] = 'El comentario/reseña es obligatorio.';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaTestimonio)) {
            $errores[] = 'Fecha del testimonio inválida.';
        }

        $testimonioExistente = null;
        if (empty($errores)) {
            $stmt = $db->prepare('SELECT foto_ruta FROM testimonios_clientes WHERE id_testimonio = :id');
            $stmt->execute(['id' => $idTestimonio]);
            $testimonioExistente = $stmt->fetch();
            if (!$testimonioExistente) {
                $errores[] = 'El testimonio ya no existe.';
            }
        }

        $rutaFotoNueva = null;
        if (empty($errores)) {
            try {
                $rutaFotoNueva = guardar_foto_testimonio($_FILES['foto'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
            } catch (\RuntimeException $e) {
                $errores[] = $e->getMessage();
            }
        }

        if (empty($errores)) {
            $rutaFotoFinal = $rutaFotoNueva ?? $testimonioExistente['foto_ruta'];

            $db->prepare(
                'UPDATE testimonios_clientes
                 SET nombre_cliente = :nombre, comentario = :comentario, foto_ruta = :foto,
                     fecha_testimonio = :fecha, orden_visualizacion = :orden
                 WHERE id_testimonio = :id'
            )->execute([
                'nombre' => $nombreCliente,
                'comentario' => $comentario,
                'foto' => $rutaFotoFinal,
                'fecha' => $fechaTestimonio,
                'orden' => $ordenVisualizacion,
                'id' => $idTestimonio,
            ]);

            // La foto vieja sólo se borra tras confirmar el UPDATE, y sólo si se reemplazó por una nueva.
            if ($rutaFotoNueva !== null && $testimonioExistente['foto_ruta'] !== null) {
                eliminar_foto_testimonio($testimonioExistente['foto_ruta']);
            }

            $mensajeOk = 'Testimonio actualizado.';
        }
    }
}

// ── Cambio de estatus (soft delete / reactivar) ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cambiar_estatus_testimonio') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } else {
        $idTestimonio = filter_input(INPUT_POST, 'id_testimonio', FILTER_VALIDATE_INT);
        $nuevoEstatus = (string) ($_POST['nuevo_estatus'] ?? '');

        if ($idTestimonio && in_array($nuevoEstatus, ['activo', 'inactivo'], true)) {
            $db->prepare('UPDATE testimonios_clientes SET estatus = :estatus WHERE id_testimonio = :id')
                ->execute(['estatus' => $nuevoEstatus, 'id' => $idTestimonio]);
            $mensajeOk = $nuevoEstatus === 'activo' ? 'Testimonio reactivado.' : 'Testimonio desactivado (ya no se muestra en la web pública).';
        } else {
            $errores[] = 'Solicitud de cambio de estatus inválida.';
        }
    }
}

// ── Datos para la vista ──────────────────────────────────────────────────
$testimonios = $db->query(
    'SELECT t.id_testimonio, t.nombre_cliente, t.comentario, t.foto_ruta, t.fecha_testimonio,
            t.estatus, t.orden_visualizacion, u.nombre_completo AS creado_por_nombre
     FROM testimonios_clientes t
     LEFT JOIN usuarios u ON u.id_usuario = t.created_by
     ORDER BY t.estatus = "activo" DESC, t.orden_visualizacion ASC, t.fecha_testimonio DESC'
)->fetchAll();

$ssos_page_title = 'Casos de Éxito';
$ssos_active_nav = 'testimonios';
require __DIR__ . '/../partials/header.php';
?>

<span class="ssos-role-badge">Casos de Éxito</span>
<h2 class="mt-3">Testimonios de Clientes</h2>
<p class="text-body-secondary">
    Reseñas que alimentan el carrusel de la página pública. Sólo los testimonios <strong>activos</strong> se muestran ahí.
</p>

<?php if ($mensajeOk): ?>
    <div class="alert alert-success ssos-alert" role="alert"><?= e($mensajeOk) ?></div>
<?php endif; ?>
<?php foreach ($errores as $error): ?>
    <div class="alert alert-danger ssos-alert" role="alert"><?= e($error) ?></div>
<?php endforeach; ?>

<div class="d-flex flex-wrap gap-2 mb-4">
    <button type="button" class="btn btn-ssos-turquesa" data-bs-toggle="modal" data-bs-target="#modalNuevoTestimonio">
        + Nuevo Testimonio
    </button>
</div>

<div class="ssos-table-card mb-4">
    <?php if (empty($testimonios)): ?>
        <p class="text-body-secondary mb-0">Aún no hay testimonios registrados.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Cliente</th>
                        <th>Reseña</th>
                        <th>Fecha</th>
                        <th>Orden</th>
                        <th>Estatus</th>
                        <th>Capturado por</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($testimonios as $t): ?>
                        <tr>
                            <td>
                                <?php if ($t['foto_ruta']): ?>
                                    <img src="<?= e(ssos_asset_media($t['foto_ruta'])) ?>" alt="Foto de <?= e($t['nombre_cliente']) ?>" class="ssos-testimonio-thumb">
                                <?php else: ?>
                                    <span class="ssos-testimonio-thumb ssos-testimonio-thumb--placeholder"><?= e(mb_substr($t['nombre_cliente'], 0, 1)) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($t['nombre_cliente']) ?></td>
                            <td class="ssos-testimonio-resena"><?= e($t['comentario']) ?></td>
                            <td><?= e(substr($t['fecha_testimonio'], 0, 10)) ?></td>
                            <td><?= (int) $t['orden_visualizacion'] ?></td>
                            <td>
                                <?php if ($t['estatus'] === 'activo'): ?>
                                    <span class="badge text-bg-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($t['creado_por_nombre'] ?? '—') ?></td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end flex-wrap">
                                    <button type="button" class="btn btn-sm btn-ssos-outline"
                                            data-bs-toggle="modal" data-bs-target="#modalEditarTestimonio"
                                            data-id="<?= (int) $t['id_testimonio'] ?>"
                                            data-nombre="<?= e($t['nombre_cliente']) ?>"
                                            data-comentario="<?= e($t['comentario']) ?>"
                                            data-fecha="<?= e(substr($t['fecha_testimonio'], 0, 10)) ?>"
                                            data-orden="<?= (int) $t['orden_visualizacion'] ?>">
                                        Editar
                                    </button>
                                    <form method="post" onsubmit="return confirm('<?= $t['estatus'] === 'activo' ? '¿Desactivar este testimonio? Dejará de mostrarse en la web pública.' : '¿Reactivar este testimonio?' ?>');">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="accion" value="cambiar_estatus_testimonio">
                                        <input type="hidden" name="id_testimonio" value="<?= (int) $t['id_testimonio'] ?>">
                                        <input type="hidden" name="nuevo_estatus" value="<?= $t['estatus'] === 'activo' ? 'inactivo' : 'activo' ?>">
                                        <button type="submit" class="btn btn-sm <?= $t['estatus'] === 'activo' ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                            <?= $t['estatus'] === 'activo' ? 'Eliminar' : 'Reactivar' ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de alta -->
<div class="modal fade" id="modalNuevoTestimonio" tabindex="-1" aria-labelledby="modalNuevoTestimonioLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" enctype="multipart/form-data" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="accion" value="crear_testimonio">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevoTestimonioLabel">Nuevo Testimonio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="nuevo_nombre_cliente" class="form-label">Nombre del cliente</label>
                    <input type="text" class="form-control" id="nuevo_nombre_cliente" name="nombre_cliente" maxlength="150" required>
                </div>
                <div class="mb-3">
                    <label for="nuevo_comentario" class="form-label">Comentario / Reseña</label>
                    <textarea class="form-control" id="nuevo_comentario" name="comentario" rows="4" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="nuevo_fecha_testimonio" class="form-label">Fecha del testimonio</label>
                    <input type="date" class="form-control" id="nuevo_fecha_testimonio" name="fecha_testimonio" value="<?= e(date('Y-m-d')) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="nuevo_orden" class="form-label">Orden en el carrusel</label>
                    <input type="number" class="form-control" id="nuevo_orden" name="orden_visualizacion" min="0" value="0">
                </div>
                <div class="mb-3">
                    <label for="nuevo_foto" class="form-label">Foto del cliente (opcional)</label>
                    <input type="file" class="form-control" id="nuevo_foto" name="foto" accept="image/jpeg,image/png,image/webp" data-ssos-preview-nombre-archivo="nuevo_foto_nombre">
                    <div class="form-text">JPG, PNG o WEBP. Máximo 2MB.</div>
                    <div class="form-text ssos-archivo-seleccionado" id="nuevo_foto_nombre"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-ssos-turquesa">Guardar Testimonio</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de edición — compartido, se rellena vía JS (main.js) desde los data-* del botón "Editar" -->
<div class="modal fade" id="modalEditarTestimonio" tabindex="-1" aria-labelledby="modalEditarTestimonioLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" enctype="multipart/form-data" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="accion" value="editar_testimonio">
            <input type="hidden" name="id_testimonio" id="testimonioEditar_id" value="">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarTestimonioLabel">Editar Testimonio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="testimonioEditar_nombre" class="form-label">Nombre del cliente</label>
                    <input type="text" class="form-control" id="testimonioEditar_nombre" name="nombre_cliente" maxlength="150" required>
                </div>
                <div class="mb-3">
                    <label for="testimonioEditar_comentario" class="form-label">Comentario / Reseña</label>
                    <textarea class="form-control" id="testimonioEditar_comentario" name="comentario" rows="4" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="testimonioEditar_fecha" class="form-label">Fecha del testimonio</label>
                    <input type="date" class="form-control" id="testimonioEditar_fecha" name="fecha_testimonio" required>
                </div>
                <div class="mb-3">
                    <label for="testimonioEditar_orden" class="form-label">Orden en el carrusel</label>
                    <input type="number" class="form-control" id="testimonioEditar_orden" name="orden_visualizacion" min="0">
                </div>
                <div class="mb-3">
                    <label for="testimonioEditar_foto" class="form-label">Reemplazar foto (opcional)</label>
                    <input type="file" class="form-control" id="testimonioEditar_foto" name="foto" accept="image/jpeg,image/png,image/webp" data-ssos-preview-nombre-archivo="testimonioEditar_foto_nombre">
                    <div class="form-text">JPG, PNG o WEBP. Máximo 2MB. Deja en blanco para conservar la foto actual.</div>
                    <div class="form-text ssos-archivo-seleccionado" id="testimonioEditar_foto_nombre"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-ssos-turquesa">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
