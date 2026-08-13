<?php
declare(strict_types=1);

/**
 * ATHLOS SSOS v1.0 — DASHBOARD ÚNICO Y DINÁMICO POR ROL (NAVEGACIÓN POR PESTAÑAS)
 *
 * Reemplaza los dashboards fragmentados (super_admin.php/admin.php/coach.php,
 * eliminados en la Fase 7). El contenido se organiza en 4 pestañas Bootstrap,
 * cada una visible sólo si el rol de la sesión la tiene autorizada:
 *   - Dirección y Control (super_admin)
 *   - Clientes y Membresías (admin + super_admin)
 *   - Pie de Cancha (coach + admin + super_admin)
 *   - Herramientas & API (super_admin)
 */

require_once __DIR__ . '/../config/helpers.php';

require_login();

$db = ssos_db();
$rol = $_SESSION['clave_rol'];

$verCalendario = in_array($rol, ['coach', 'admin', 'super_admin'], true);
$verClientes = in_array($rol, ['admin', 'super_admin'], true);
$verPieDeCancha = in_array($rol, ['coach', 'admin', 'super_admin'], true);
$verControl = $rol === 'super_admin';
$verEquipo = in_array($rol, ['admin', 'super_admin'], true);
$verHerramientas = $rol === 'super_admin';

// Alta de staff: Dirección (super_admin) puede crear cualquier rol de staff
// (candado ya existente más abajo excluye super_admin como rol asignable
// incluso para ella misma vía este formulario — ver nota en editar_miembro_equipo);
// Administración/Recepción (admin) puede dar de alta cuentas de Coach o Admin
// — nunca super_admin, para que un puesto de recepción no pueda auto-escalar
// ni crear pares con el máximo privilegio.
$puedeCrearUsuarios = in_array($rol, ['admin', 'super_admin'], true);
$rolesCreablesPorRol = $rol === 'super_admin' ? ['coach', 'admin', 'atleta'] : ['coach', 'admin'];

// Candado de seguridad del módulo Equipo (Punto 3): admin jamás puede ver,
// editar, resetear contraseña ni cambiar estatus de una cuenta super_admin,
// ni asignar el rol super_admin a nadie — reforzado en cada handler de abajo,
// nunca sólo en la UI (Mandamiento 2: seguridad militar, cero confianza en el cliente).
$rolesEquipoAsignables = ['coach', 'admin']; // NUNCA incluye super_admin, sin importar el actor

// El Calendario es la pestaña de aterrizaje del Dashboard (Fase 23) — misma
// lógica de datos/POST que la página standalone agenda/index.php, extraída a
// agenda_logica.php para no duplicarla. Debe ejecutarse ANTES de cualquier
// salida HTML: el handler AJAX de "mover cita" (drag-and-drop) responde JSON
// y hace `exit` a mitad de este archivo si la petición es esa.
if ($verCalendario) {
    require_once __DIR__ . '/../agenda/agenda_logica.php';
}

// ── Alta de usuarios del staff (Coach/Administración), sólo Dirección ───────
$erroresUsuarioNuevo = [];
$usuarioNuevoCreado = false;

if ($puedeCrearUsuarios && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear_usuario') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $erroresUsuarioNuevo[] = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } else {
        $nombreNuevo = trim((string) ($_POST['nombre_completo'] ?? ''));
        $emailNuevo = trim((string) ($_POST['email'] ?? ''));
        $rolNuevo = (string) ($_POST['rol_nuevo'] ?? '');
        $passwordNuevo = (string) ($_POST['password'] ?? '');
        $especialidadNueva = trim((string) ($_POST['especialidad'] ?? ''));
        $idAtletaVinculado = filter_input(INPUT_POST, 'id_atleta_vinculado', FILTER_VALIDATE_INT) ?: null;

        if ($nombreNuevo === '' || mb_strlen($nombreNuevo) > 150) {
            $erroresUsuarioNuevo[] = 'El nombre es obligatorio (máximo 150 caracteres).';
        }
        if (!filter_var($emailNuevo, FILTER_VALIDATE_EMAIL)) {
            $erroresUsuarioNuevo[] = 'El correo no es válido.';
        }
        if (!in_array($rolNuevo, $rolesCreablesPorRol, true)) {
            $erroresUsuarioNuevo[] = 'Selecciona un rol válido.';
        }
        if (mb_strlen($passwordNuevo) < 8) {
            $erroresUsuarioNuevo[] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if ($rolNuevo === 'coach' && $especialidadNueva === '') {
            $erroresUsuarioNuevo[] = 'La especialidad es obligatoria para cuentas de Coach.';
        }
        if ($rolNuevo === 'atleta' && !$idAtletaVinculado) {
            $erroresUsuarioNuevo[] = 'Selecciona el atleta al que se vinculará este acceso.';
        }

        if (empty($erroresUsuarioNuevo)) {
            try {
                $db->beginTransaction();

                $stmt = $db->prepare('SELECT id_usuario FROM usuarios WHERE email = :email LIMIT 1');
                $stmt->execute(['email' => $emailNuevo]);
                if ($stmt->fetch()) {
                    throw new \RuntimeException('Ya existe un usuario con ese correo.');
                }

                $idStaffNuevo = null;
                if ($rolNuevo === 'coach') {
                    $stmt = $db->prepare(
                        'INSERT INTO staff (nombre_completo, especialidad, email, activo) VALUES (:nombre, :especialidad, :email, 1)'
                    );
                    $stmt->execute(['nombre' => $nombreNuevo, 'especialidad' => $especialidadNueva, 'email' => $emailNuevo]);
                    $idStaffNuevo = (int) $db->lastInsertId();
                }

                $stmt = $db->prepare('SELECT id_rol FROM roles WHERE clave_rol = :clave');
                $stmt->execute(['clave' => $rolNuevo]);
                $idRolNuevo = $stmt->fetchColumn();

                $stmt = $db->prepare(
                    'INSERT INTO usuarios (id_rol, id_staff, id_atleta, nombre_completo, email, password_hash, activo, requiere_cambio_password)
                     VALUES (:id_rol, :id_staff, :id_atleta, :nombre, :email, :hash, 1, 1)'
                );
                $stmt->execute([
                    'id_rol' => $idRolNuevo,
                    'id_staff' => $idStaffNuevo,
                    'id_atleta' => $rolNuevo === 'atleta' ? $idAtletaVinculado : null,
                    'nombre' => $nombreNuevo,
                    'email' => $emailNuevo,
                    'hash' => password_hash($passwordNuevo, PASSWORD_DEFAULT),
                ]);

                $db->commit();
                $usuarioNuevoCreado = true;
            } catch (\Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                if ($e->getMessage() === 'Ya existe un usuario con ese correo.') {
                    $erroresUsuarioNuevo[] = $e->getMessage();
                } else {
                    $erroresUsuarioNuevo[] = 'No se pudo crear el usuario. Detalle técnico registrado en el log del servidor.';
                    error_log('[SSOS dashboard crear_usuario] ' . $e->getMessage());
                }
            }
        }
    }
}

/**
 * Devuelve la clave_rol actual de un usuario, o null si no existe.
 * Usada por los 3 handlers de Equipo para reforzar el candado de seguridad
 * ANTES de tocar la fila — nunca se confía en un rol/estatus que el
 * formulario/cliente haya podido enviar.
 */
function obtenerClaveRolUsuario(PDO $db, int $idUsuario): ?string
{
    $stmt = $db->prepare(
        'SELECT r.clave_rol FROM usuarios u INNER JOIN roles r ON r.id_rol = u.id_rol WHERE u.id_usuario = :id'
    );
    $stmt->execute(['id' => $idUsuario]);
    $clave = $stmt->fetchColumn();
    return $clave !== false ? (string) $clave : null;
}

// ── Equipo: edición de datos + rol (Admin + Dirección) ──────────────────────
$erroresEquipo = [];
$equipoOk = null;

if ($verEquipo && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'editar_miembro_equipo') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $erroresEquipo[] = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } else {
        $idMiembro = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
        $nombreMiembro = trim((string) ($_POST['nombre_completo'] ?? ''));
        $emailMiembro = trim((string) ($_POST['email'] ?? ''));
        $rolMiembroNuevo = (string) ($_POST['rol'] ?? '');
        $especialidadMiembro = trim((string) ($_POST['especialidad'] ?? ''));

        $rolActualMiembro = $idMiembro ? obtenerClaveRolUsuario($db, $idMiembro) : null;

        if (!$idMiembro || $rolActualMiembro === null) {
            $erroresEquipo[] = 'Usuario inválido.';
        } elseif ($idMiembro === (int) $_SESSION['id_usuario']) {
            $erroresEquipo[] = 'No puedes editar tu propia cuenta desde este panel.';
        } elseif ($rolActualMiembro === 'super_admin' || !in_array($rolMiembroNuevo, $rolesEquipoAsignables, true)) {
            // Candado de seguridad: bloquea tanto tocar a un super_admin existente
            // como asignar super_admin (que ni siquiera aparece en $rolesEquipoAsignables).
            $erroresEquipo[] = 'No tienes permiso para modificar esta cuenta o asignar ese rol.';
        } else {
            if ($nombreMiembro === '' || mb_strlen($nombreMiembro) > 150) {
                $erroresEquipo[] = 'El nombre es obligatorio (máximo 150 caracteres).';
            }
            if (!filter_var($emailMiembro, FILTER_VALIDATE_EMAIL)) {
                $erroresEquipo[] = 'El correo no es válido.';
            }
            if ($rolMiembroNuevo === 'coach' && $especialidadMiembro === '') {
                $erroresEquipo[] = 'La especialidad es obligatoria para cuentas de Coach.';
            }

            if (empty($erroresEquipo)) {
                try {
                    $db->beginTransaction();

                    $stmt = $db->prepare('SELECT id_usuario FROM usuarios WHERE email = :email AND id_usuario != :id LIMIT 1');
                    $stmt->execute(['email' => $emailMiembro, 'id' => $idMiembro]);
                    if ($stmt->fetch()) {
                        throw new \RuntimeException('Ya existe otro usuario con ese correo.');
                    }

                    $stmt = $db->prepare('SELECT id_staff FROM usuarios WHERE id_usuario = :id');
                    $stmt->execute(['id' => $idMiembro]);
                    $idStaffMiembro = $stmt->fetchColumn();
                    $idStaffMiembro = $idStaffMiembro !== false ? (int) $idStaffMiembro : null;

                    // admin -> coach sin ficha de staff previa (ej. cuenta creada
                    // directo como admin): se crea la ficha ahora. En cualquier
                    // otro caso la ficha de staff (si existe) se conserva intacta
                    // — cambiar de rol no borra el historial operativo del coach.
                    if ($rolMiembroNuevo === 'coach' && $idStaffMiembro === null) {
                        $stmt = $db->prepare(
                            'INSERT INTO staff (nombre_completo, especialidad, email, activo) VALUES (:nombre, :especialidad, :email, 1)'
                        );
                        $stmt->execute(['nombre' => $nombreMiembro, 'especialidad' => $especialidadMiembro, 'email' => $emailMiembro]);
                        $idStaffMiembro = (int) $db->lastInsertId();
                    } elseif ($idStaffMiembro !== null) {
                        $stmt = $db->prepare('UPDATE staff SET nombre_completo = :nombre, especialidad = :especialidad, email = :email WHERE id_staff = :id');
                        $stmt->execute(['nombre' => $nombreMiembro, 'especialidad' => $especialidadMiembro, 'email' => $emailMiembro, 'id' => $idStaffMiembro]);
                    }

                    $stmt = $db->prepare('SELECT id_rol FROM roles WHERE clave_rol = :clave');
                    $stmt->execute(['clave' => $rolMiembroNuevo]);
                    $idRolMiembroNuevo = $stmt->fetchColumn();

                    $stmt = $db->prepare(
                        'UPDATE usuarios SET nombre_completo = :nombre, email = :email, id_rol = :id_rol, id_staff = :id_staff WHERE id_usuario = :id'
                    );
                    $stmt->execute([
                        'nombre' => $nombreMiembro,
                        'email' => $emailMiembro,
                        'id_rol' => $idRolMiembroNuevo,
                        'id_staff' => $idStaffMiembro,
                        'id' => $idMiembro,
                    ]);

                    $db->commit();
                    $equipoOk = 'Miembro del equipo actualizado.';
                } catch (\Throwable $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    if ($e->getMessage() === 'Ya existe otro usuario con ese correo.') {
                        $erroresEquipo[] = $e->getMessage();
                    } else {
                        $erroresEquipo[] = 'No se pudo actualizar el miembro. Detalle técnico registrado en el log del servidor.';
                        error_log('[SSOS dashboard editar_miembro_equipo] ' . $e->getMessage());
                    }
                }
            }
        }
    }
}

// ── Equipo: resetear contraseña (Admin + Dirección) ─────────────────────────
if ($verEquipo && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'resetear_password_equipo') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $erroresEquipo[] = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } else {
        $idMiembro = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
        $passwordNuevaEquipo = (string) ($_POST['password_nueva'] ?? '');
        $rolActualMiembro = $idMiembro ? obtenerClaveRolUsuario($db, $idMiembro) : null;

        if (!$idMiembro || $rolActualMiembro === null) {
            $erroresEquipo[] = 'Usuario inválido.';
        } elseif ($idMiembro === (int) $_SESSION['id_usuario']) {
            $erroresEquipo[] = 'No puedes resetear tu propia contraseña desde este panel.';
        } elseif ($rolActualMiembro === 'super_admin') {
            $erroresEquipo[] = 'No tienes permiso para modificar esta cuenta.';
        } elseif (mb_strlen($passwordNuevaEquipo) < 8) {
            $erroresEquipo[] = 'La contraseña debe tener al menos 8 caracteres.';
        } else {
            $stmt = $db->prepare(
                'UPDATE usuarios SET password_hash = :hash, requiere_cambio_password = 1 WHERE id_usuario = :id'
            );
            $stmt->execute(['hash' => password_hash($passwordNuevaEquipo, PASSWORD_DEFAULT), 'id' => $idMiembro]);
            $equipoOk = 'Contraseña reseteada. El usuario deberá cambiarla en su próximo inicio de sesión.';
        }
    }
}

// ── Equipo: activar/desactivar cuenta — soft delete (Admin + Dirección) ─────
if ($verEquipo && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cambiar_estatus_equipo') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $erroresEquipo[] = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } else {
        $idMiembro = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
        $nuevoActivoEquipo = filter_input(INPUT_POST, 'nuevo_activo', FILTER_VALIDATE_INT);
        $rolActualMiembro = $idMiembro ? obtenerClaveRolUsuario($db, $idMiembro) : null;

        if (!$idMiembro || $rolActualMiembro === null || !in_array($nuevoActivoEquipo, [0, 1], true)) {
            $erroresEquipo[] = 'Solicitud inválida.';
        } elseif ($idMiembro === (int) $_SESSION['id_usuario']) {
            $erroresEquipo[] = 'No puedes desactivar tu propia cuenta.';
        } elseif ($rolActualMiembro === 'super_admin') {
            $erroresEquipo[] = 'No tienes permiso para modificar esta cuenta.';
        } else {
            $stmt = $db->prepare('UPDATE usuarios SET activo = :activo WHERE id_usuario = :id');
            $stmt->execute(['activo' => $nuevoActivoEquipo, 'id' => $idMiembro]);
            $equipoOk = $nuevoActivoEquipo === 1 ? 'Cuenta reactivada.' : 'Cuenta desactivada (soft delete) — el acceso queda bloqueado, el historial se conserva.';
        }
    }
}

// ── Edición de ficha de atleta (Admin + Dirección) ──────────────────────────
$erroresEditarAtleta = [];
$atletaEditadoOk = false;

if ($verClientes && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'editar_atleta') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $erroresEditarAtleta[] = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } else {
        $idAtletaEditar = filter_input(INPUT_POST, 'id_atleta', FILTER_VALIDATE_INT);
        $nombreEditar = trim((string) ($_POST['nombre_completo'] ?? ''));
        $telefonoEditar = trim((string) ($_POST['telefono'] ?? ''));
        $emailEditar = trim((string) ($_POST['email'] ?? ''));
        $fechaNacimientoEditar = trim((string) ($_POST['fecha_nacimiento'] ?? ''));

        if (!$idAtletaEditar) {
            $erroresEditarAtleta[] = 'Atleta inválido.';
        }
        if ($nombreEditar === '' || mb_strlen($nombreEditar) > 150) {
            $erroresEditarAtleta[] = 'El nombre es obligatorio (máximo 150 caracteres).';
        }
        if ($telefonoEditar === '' || mb_strlen($telefonoEditar) > 20) {
            $erroresEditarAtleta[] = 'El teléfono es obligatorio (máximo 20 caracteres).';
        }
        if ($emailEditar !== '' && !filter_var($emailEditar, FILTER_VALIDATE_EMAIL)) {
            $erroresEditarAtleta[] = 'El correo no es válido.';
        }
        if ($fechaNacimientoEditar !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaNacimientoEditar)) {
            $erroresEditarAtleta[] = 'La fecha de nacimiento no es válida.';
        }

        if (empty($erroresEditarAtleta)) {
            try {
                $stmt = $db->prepare(
                    'UPDATE atletas SET nombre_completo = :nombre, telefono = :telefono, email = :email,
                        fecha_nacimiento = :fecha_nacimiento
                     WHERE id_atleta = :id'
                );
                $stmt->execute([
                    'nombre' => $nombreEditar,
                    'telefono' => $telefonoEditar,
                    'email' => $emailEditar !== '' ? $emailEditar : null,
                    'fecha_nacimiento' => $fechaNacimientoEditar !== '' ? $fechaNacimientoEditar : null,
                    'id' => $idAtletaEditar,
                ]);
                $atletaEditadoOk = true;
            } catch (\Throwable $e) {
                $erroresEditarAtleta[] = 'No se pudo actualizar el atleta. Detalle técnico registrado en el log del servidor.';
                error_log('[SSOS dashboard editar_atleta] ' . $e->getMessage());
            }
        }
    }
}

// ── Cambio de estatus de atleta (Admin + Dirección) ─────────────────────────
if ($verClientes && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cambiar_estatus') {
    if (csrf_validate($_POST['csrf_token'] ?? null)) {
        $idAtletaEstatus = filter_input(INPUT_POST, 'id_atleta', FILTER_VALIDATE_INT);
        $nuevoEstatus = (string) ($_POST['nuevo_estatus'] ?? '');

        if ($idAtletaEstatus && in_array($nuevoEstatus, ['activo', 'inactivo', 'suspendido'], true)) {
            try {
                $stmt = $db->prepare('UPDATE atletas SET estatus = :estatus WHERE id_atleta = :id');
                $stmt->execute(['estatus' => $nuevoEstatus, 'id' => $idAtletaEstatus]);
            } catch (\Throwable $e) {
                error_log('[SSOS dashboard cambiar_estatus] ' . $e->getMessage());
            }
        }
    }
}

// ── Tab: Dirección y Control (sólo Dirección de Laboratorio) ────────────────
if ($verControl) {
    $total_usuarios = (int) $db->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    $total_atletas_control = (int) $db->query('SELECT COUNT(*) FROM atletas')->fetchColumn();
    $eventos_recientes = $db->query(
        'SELECT tipo_evento, email_intento, ip_origen, created_at
         FROM sesiones_log ORDER BY id_log_sesion DESC LIMIT 10'
    )->fetchAll();
}

// ── Tab: Equipo del Laboratorio (Admin + Dirección) ─────────────────────────
// El listado de staff/administración vivía duplicado a medias entre esta
// pantalla (sólo Dirección, sólo lectura + alta) y la pestaña Clientes (sólo
// Admin, sólo alta de Coach, sin listado). Se consolida aquí: un único CRUD
// completo, visible para ambos roles, con el candado de super_admin aplicado
// tanto en la consulta (admin JAMÁS recibe esas filas del servidor, no sólo
// las tiene ocultas en la UI) como en los 3 handlers de arriba.
if ($verEquipo) {
    $filtroRolEquipo = $rol === 'admin' ? "AND r.clave_rol != 'super_admin'" : '';
    $miembrosEquipo = $db->query(
        "SELECT u.id_usuario, u.nombre_completo, u.email, u.activo, u.ultimo_login,
                r.clave_rol, s.especialidad
         FROM usuarios u
         INNER JOIN roles r ON r.id_rol = u.id_rol
         LEFT JOIN staff s ON s.id_staff = u.id_staff
         WHERE r.clave_rol IN ('coach', 'admin', 'super_admin') {$filtroRolEquipo}
         ORDER BY u.activo DESC, u.nombre_completo ASC"
    )->fetchAll();
}

// ── Tab: Clientes y Membresías (Admin + Dirección) ──────────────────────────
if ($verClientes) {
    $clientes_activos = (int) $db->query(
        "SELECT COUNT(*) FROM atletas WHERE estatus = 'activo'"
    )->fetchColumn();

    $evaluaciones_pendientes = (int) $db->query(
        "SELECT COUNT(*) FROM atletas a
         WHERE a.estatus = 'activo'
           AND NOT EXISTS (
               SELECT 1 FROM evaluaciones_antropometria ea
               WHERE ea.id_atleta = a.id_atleta
                 AND ea.fecha_antropometria >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
           )"
    )->fetchColumn();

    $membresias_por_vencer = (int) $db->query(
        "SELECT COUNT(*) FROM membresias
         WHERE estatus = 'activa'
           AND fecha_fin IS NOT NULL
           AND fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)"
    )->fetchColumn();

    // Defensivo: 05_schema_alertas_membresias.sql pudo no haberse aplicado aún
    // en este servidor. Ante tabla faltante, mostramos un mensaje amigable en
    // vez de tumbar todo el tab con un error 500 por una migración pendiente.
    $alertas_disponibles = true;
    try {
        $alertas_renovacion_activas = (int) $db->query(
            "SELECT COUNT(*) FROM alertas_renovacion WHERE atendida = 0"
        )->fetchColumn();
    } catch (\Throwable) {
        $alertas_disponibles = false;
        $alertas_renovacion_activas = 0;
    }

    $ultimos_clientes = $db->query(
        "SELECT id_atleta, nombre_completo, telefono, email, fecha_nacimiento, estatus, tipo_membresia, fecha_ingreso
         FROM atletas
         ORDER BY created_at DESC
         LIMIT 500"
    )->fetchAll();
}

// ── Tab: Pie de Cancha (Coach + Admin + Dirección) ──────────────────────────
if ($verPieDeCancha) {
    $id_staff = $_SESSION['id_staff'] ?? null;
    $filtro_staff_sql = $id_staff !== null && $rol === 'coach' ? 'AND da.id_staff = :id_staff' : '';

    $stmt = $db->prepare(
        "SELECT da.id_cita, da.hora_inicio, a.id_atleta, a.nombre_completo,
                (SELECT es.semaforo_general FROM evaluaciones_sft es
                 WHERE es.id_atleta = a.id_atleta
                 ORDER BY es.fecha_evaluacion DESC LIMIT 1) AS semaforo
         FROM disponibilidad_agenda da
         INNER JOIN atletas a ON a.id_atleta = da.id_atleta
         WHERE da.fecha_cita = CURDATE()
           AND da.estatus_cita IN ('reservada','confirmada')
           {$filtro_staff_sql}
         ORDER BY da.hora_inicio ASC"
    );
    $stmt->execute($id_staff !== null && $rol === 'coach' ? ['id_staff' => $id_staff] : []);
    $atletas_del_dia = $stmt->fetchAll();
}

// ── Tab: Herramientas & API (sólo Dirección de Laboratorio) ─────────────────
if ($verHerramientas) {
    try {
        $db_host_actual = (string) $db->query('SELECT @@hostname')->fetchColumn();
    } catch (\Throwable) {
        $db_host_actual = 'No disponible';
    }
}

$etiquetasRol = [
    'super_admin' => 'Dirección de Laboratorio',
    'admin' => 'Administración / Recepción',
    'coach' => 'Coach Especialista',
    'atleta' => 'Atleta / Cliente',
];

// ── Orden de pestañas visibles + cuál queda activa por defecto ──────────────
// El Calendario va PRIMERO a propósito: al ser tabsDisponibles[0], queda
// automáticamente como pestaña de aterrizaje (REGLA de la Fase 23) sin
// necesitar una bandera "es la pestaña por defecto" separada y sin riesgo de
// que se desincronice si el orden cambia en el futuro.
$tabsDisponibles = [];
if ($verCalendario) {
    $tabsDisponibles[] = ['id' => 'calendario', 'icono' => '📅', 'label' => 'Calendario'];
}
if ($verControl) {
    $tabsDisponibles[] = ['id' => 'control', 'icono' => '📊', 'label' => 'Dirección y Control'];
}
if ($verClientes) {
    $tabsDisponibles[] = ['id' => 'clientes', 'icono' => '👥', 'label' => 'Clientes y Membresías'];
}
if ($verEquipo) {
    $tabsDisponibles[] = ['id' => 'equipo', 'icono' => '🧑‍💼', 'label' => 'Equipo del Laboratorio'];
}
if ($verPieDeCancha) {
    $tabsDisponibles[] = ['id' => 'pie-de-cancha', 'icono' => '🏋️‍♂️', 'label' => 'Sesiones del Día'];
}
if ($verHerramientas) {
    $tabsDisponibles[] = ['id' => 'herramientas', 'icono' => '🛠️', 'label' => 'Herramientas & API'];
}

// Retención de pestaña activa tras un POST (auditoría UX): sin esto, CUALQUIER
// guardado (crear/editar equipo, atleta, etc.) volvía a mostrar siempre la
// PRIMERA pestaña de $tabsDisponibles, sin importar en cuál trabajaba el
// usuario — perdía su contexto en cada guardado. `tab_origen` lo inyecta
// main.js automáticamente en cualquier <form method="post"> justo antes de
// enviarlo, leyendo la pestaña visualmente activa en ese momento — cero
// campos ocultos manuales por formulario (ver initRetencionPestanaActiva()).
// `?tab=` (GET) es el mismo mecanismo para navegación por enlace directo.
// Nunca se confía en el valor recibido sin validarlo contra la lista real de
// pestañas disponibles para ESTE rol — evita que alguien fuerce un id de
// pestaña que no debería poder ver.
$tabSolicitada = (string) ($_POST['tab_origen'] ?? $_GET['tab'] ?? '');
$idsTabsDisponibles = array_column($tabsDisponibles, 'id');
$tabActivaPorDefecto = in_array($tabSolicitada, $idsTabsDisponibles, true)
    ? $tabSolicitada
    : ($tabsDisponibles[0]['id'] ?? 'calendario');

$ssos_page_title = 'Dashboard';
$ssos_active_nav = 'dashboard';
require __DIR__ . '/../partials/header.php';
?>

<span class="ssos-role-badge"><?= e($etiquetasRol[$rol] ?? $rol) ?></span>
<h2 class="mt-3">Bienvenido, <?= e($_SESSION['nombre_completo']) ?></h2>

<ul class="nav nav-tabs ssos-tabs" id="ssosTabList" role="tablist">
    <?php foreach ($tabsDisponibles as $tab): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $tab['id'] === $tabActivaPorDefecto ? 'active' : '' ?>"
                    id="tab-btn-<?= e($tab['id']) ?>" data-bs-toggle="tab"
                    data-bs-target="#pane-<?= e($tab['id']) ?>" type="button" role="tab"
                    aria-controls="pane-<?= e($tab['id']) ?>"
                    aria-selected="<?= $tab['id'] === $tabActivaPorDefecto ? 'true' : 'false' ?>">
                <?= $tab['icono'] ?> <?= e($tab['label']) ?>
            </button>
        </li>
    <?php endforeach; ?>
</ul>

<div class="tab-content" id="ssosTabContent">

<?php if ($verCalendario): ?>
<div class="tab-pane fade ssos-tab-pane <?= 'calendario' === $tabActivaPorDefecto ? 'show active' : '' ?>"
     id="pane-calendario" role="tabpanel" aria-labelledby="tab-btn-calendario">
    <?php $ssos_agenda_embebida = true; require __DIR__ . '/../agenda/agenda_vista.php'; ?>
</div>
<?php endif; ?>

<?php if ($verControl): ?>
<div class="tab-pane fade ssos-tab-pane <?= 'control' === $tabActivaPorDefecto ? 'show active' : '' ?>"
     id="pane-control" role="tabpanel" aria-labelledby="tab-btn-control">

    <p class="text-body-secondary">
        Control absoluto de base de datos y auditoría de seguridad. La gestión de personal
        (alta, edición, roles, contraseñas) vive ahora en la pestaña
        <strong>🧑‍💼 Equipo del Laboratorio</strong>.
    </p>

    <div class="ssos-widget-grid">
        <div class="ssos-widget card shadow-sm border-0">
            <div class="card-body">
                <div class="ssos-widget-value"><?= $total_usuarios ?></div>
                <div class="ssos-widget-label">Usuarios del BackOffice</div>
            </div>
        </div>
        <div class="ssos-widget card shadow-sm border-0">
            <div class="card-body">
                <div class="ssos-widget-value"><?= $total_atletas_control ?></div>
                <div class="ssos-widget-label">Atletas Registrados</div>
            </div>
        </div>
    </div>

    <div class="ssos-table-card">
        <h5 class="mb-3">Bitácora de sesiones reciente</h5>
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Evento</th>
                    <th>Email</th>
                    <th>IP</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($eventos_recientes)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-body-secondary py-4">Sin actividad registrada.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($eventos_recientes as $evento): ?>
                    <tr>
                        <td><?= e($evento['tipo_evento']) ?></td>
                        <td><?= e($evento['email_intento']) ?></td>
                        <td><?= e($evento['ip_origen']) ?></td>
                        <td><?= e($evento['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($verClientes): ?>
<div class="tab-pane fade ssos-tab-pane <?= 'clientes' === $tabActivaPorDefecto ? 'show active' : '' ?>"
     id="pane-clientes" role="tabpanel" aria-labelledby="tab-btn-clientes">

    <p class="text-body-secondary">
        Gestión comercial de clientes, cobros y catálogo de paquetes/membresías. El alta de
        Coaches/Administración vive en la pestaña <strong>🧑‍💼 Equipo del Laboratorio</strong>.
    </p>

    <div class="ssos-widget-grid">
        <div class="ssos-widget card shadow-sm border-0">
            <div class="card-body">
                <div class="ssos-widget-value"><?= (int) $clientes_activos ?></div>
                <div class="ssos-widget-label">Clientes Activos</div>
            </div>
        </div>
        <div class="ssos-widget card shadow-sm border-0">
            <div class="card-body">
                <div class="ssos-widget-value"><?= (int) $evaluaciones_pendientes ?></div>
                <div class="ssos-widget-label">Evaluaciones Pendientes</div>
            </div>
        </div>
        <div class="ssos-widget card shadow-sm border-0">
            <div class="card-body">
                <div class="ssos-widget-value"><?= (int) $membresias_por_vencer ?></div>
                <div class="ssos-widget-label">Membresías por Vencer (7 días)</div>
            </div>
        </div>
        <div class="ssos-widget card shadow-sm border-0">
            <div class="card-body">
                <?php if ($alertas_disponibles): ?>
                    <div class="ssos-widget-value"><?= (int) $alertas_renovacion_activas ?></div>
                    <div class="ssos-widget-label">Alertas de Renovación Activas</div>
                <?php else: ?>
                    <div class="ssos-widget-value ssos-widget-value--text">No disponible aún</div>
                    <div class="ssos-widget-label">Alertas de Renovación (falta aplicar migración 05)</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($atletaEditadoOk): ?>
        <div class="alert alert-success ssos-alert" role="alert">Atleta actualizado exitosamente.</div>
    <?php endif; ?>
    <?php foreach ($erroresEditarAtleta as $errorEditar): ?>
        <div class="alert alert-danger ssos-alert" role="alert"><?= e($errorEditar) ?></div>
    <?php endforeach; ?>

    <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" role="switch" id="ssosOcultarSuspendidos" data-ssos-ocultar-suspendidos>
        <label class="form-check-label" for="ssosOcultarSuspendidos">Ocultar atletas suspendidos/inactivos</label>
    </div>

    <div class="ssos-table-card">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Teléfono</th>
                    <th>Membresía</th>
                    <th>Estatus</th>
                    <th>Ingreso</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="ssosTablaClientes">
                <?php if (empty($ultimos_clientes)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-body-secondary py-4">
                            Aún no hay clientes registrados.
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($ultimos_clientes as $cliente): ?>
                    <tr data-estatus="<?= e($cliente['estatus']) ?>">
                        <td><?= e($cliente['nombre_completo']) ?></td>
                        <td><?= e($cliente['telefono']) ?></td>
                        <td><?= e($cliente['tipo_membresia']) ?></td>
                        <td>
                            <form method="post" class="ssos-estatus-form">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="accion" value="cambiar_estatus">
                                <input type="hidden" name="id_atleta" value="<?= (int) $cliente['id_atleta'] ?>">
                                <select name="nuevo_estatus" class="form-select form-select-sm ssos-estatus-select ssos-estatus-select--<?= e($cliente['estatus']) ?>" onchange="this.form.submit()" aria-label="Cambiar estatus">
                                    <option value="activo" <?= $cliente['estatus'] === 'activo' ? 'selected' : '' ?>>🟢 Activo</option>
                                    <option value="inactivo" <?= $cliente['estatus'] === 'inactivo' ? 'selected' : '' ?>>⚪ Inactivo</option>
                                    <option value="suspendido" <?= $cliente['estatus'] === 'suspendido' ? 'selected' : '' ?>>🔴 Suspendido</option>
                                </select>
                            </form>
                        </td>
                        <td><?= e($cliente['fecha_ingreso']) ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal" data-bs-target="#modalEditarAtleta"
                                        data-id="<?= (int) $cliente['id_atleta'] ?>"
                                        data-nombre="<?= e($cliente['nombre_completo']) ?>"
                                        data-telefono="<?= e($cliente['telefono']) ?>"
                                        data-email="<?= e($cliente['email'] ?? '') ?>"
                                        data-fecha-nacimiento="<?= e($cliente['fecha_nacimiento'] ?? '') ?>"
                                        title="Editar">✏️ Editar</button>
                                <a class="btn btn-sm btn-ssos-turquesa"
                                   href="<?= e(ssos_base_url()) ?>/atleta/expediente.php?id_atleta=<?= (int) $cliente['id_atleta'] ?>"
                                   title="Expediente Clínico">📂 Expediente</a>
                                <a class="btn btn-sm btn-outline-secondary"
                                   href="<?= e(ssos_base_url()) ?>/atleta/reporte.php?token=<?= e(ssos_generate_share_token((int) $cliente['id_atleta'])) ?>"
                                   target="_blank" rel="noopener noreferrer" title="Ver Reporte">📄 Reporte</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="modal fade" id="modalEditarAtleta" tabindex="-1" aria-labelledby="modalEditarAtletaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="accion" value="editar_atleta">
                <input type="hidden" name="id_atleta" id="editar_id_atleta" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarAtletaLabel">Editar Atleta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editar_nombre" class="form-label">Nombre completo</label>
                        <input type="text" class="form-control" id="editar_nombre" name="nombre_completo" maxlength="150" required>
                    </div>
                    <div class="mb-3">
                        <label for="editar_telefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="editar_telefono" name="telefono" maxlength="20" required>
                        <div class="form-text">Reemplaza los placeholders <code>SIN-TEL-*</code> con el número real.</div>
                    </div>
                    <div class="mb-3">
                        <label for="editar_email" class="form-label">Correo</label>
                        <input type="email" class="form-control" id="editar_email" name="email" maxlength="150">
                    </div>
                    <div class="mb-3">
                        <label for="editar_fecha_nacimiento" class="form-label">Fecha de nacimiento</label>
                        <input type="date" class="form-control" id="editar_fecha_nacimiento" name="fecha_nacimiento">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-ssos-turquesa">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($verEquipo): ?>
<div class="tab-pane fade ssos-tab-pane <?= 'equipo' === $tabActivaPorDefecto ? 'show active' : '' ?>"
     id="pane-equipo" role="tabpanel" aria-labelledby="tab-btn-equipo">

    <p class="text-body-secondary">
        Alta, edición, contraseñas y rol (Coach ⇄ Administración) del personal del laboratorio.
        <?php if ($rol === 'admin'): ?>Las cuentas de Dirección de Laboratorio no son visibles ni editables desde aquí.<?php endif; ?>
    </p>

    <?php if ($equipoOk): ?>
        <div class="alert alert-success ssos-alert" role="alert"><?= e($equipoOk) ?></div>
    <?php endif; ?>
    <?php if ($usuarioNuevoCreado): ?>
        <div class="alert alert-success ssos-alert" role="alert">Miembro del equipo creado exitosamente.</div>
    <?php endif; ?>
    <?php foreach (array_merge($erroresUsuarioNuevo, $erroresEquipo) as $errorEquipo): ?>
        <div class="alert alert-danger ssos-alert" role="alert"><?= e($errorEquipo) ?></div>
    <?php endforeach; ?>

    <div class="d-flex flex-wrap gap-2 mb-4">
        <button type="button" class="btn btn-ssos-turquesa" data-bs-toggle="modal" data-bs-target="#modalEquipoNuevo">
            + Nuevo Miembro del Equipo
        </button>
    </div>

    <div class="ssos-table-card">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Especialidad</th>
                    <th>Estatus</th>
                    <th>Último acceso</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($miembrosEquipo)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-body-secondary py-4">Aún no hay miembros de equipo registrados.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($miembrosEquipo as $miembro): ?>
                    <?php $esSuperAdmin = $miembro['clave_rol'] === 'super_admin'; ?>
                    <tr>
                        <td><?= e($miembro['nombre_completo']) ?></td>
                        <td><?= e($miembro['email']) ?></td>
                        <td><?= e($etiquetasRol[$miembro['clave_rol']] ?? $miembro['clave_rol']) ?></td>
                        <td><?= e($miembro['especialidad'] ?? '—') ?></td>
                        <td><?= $miembro['activo'] ? '🟢 Activo' : '⚪ Inactivo' ?></td>
                        <td><?= e($miembro['ultimo_login'] ?? '—') ?></td>
                        <td>
                            <?php if ($esSuperAdmin || (int) $miembro['id_usuario'] === (int) $_SESSION['id_usuario']): ?>
                                <span class="text-body-secondary small">Sin acciones</span>
                            <?php else: ?>
                                <div class="d-flex flex-wrap gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal" data-bs-target="#modalEquipoEditar"
                                            data-id="<?= (int) $miembro['id_usuario'] ?>"
                                            data-nombre="<?= e($miembro['nombre_completo']) ?>"
                                            data-email="<?= e($miembro['email']) ?>"
                                            data-rol="<?= e($miembro['clave_rol']) ?>"
                                            data-especialidad="<?= e($miembro['especialidad'] ?? '') ?>"
                                            title="Editar">✏️ Editar</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal" data-bs-target="#modalEquipoResetPassword"
                                            data-id="<?= (int) $miembro['id_usuario'] ?>"
                                            data-nombre="<?= e($miembro['nombre_completo']) ?>"
                                            title="Resetear contraseña">🔑 Contraseña</button>
                                    <form method="post" onsubmit="return confirm('<?= $miembro['activo'] ? '¿Desactivar esta cuenta? El acceso quedará bloqueado de inmediato.' : '¿Reactivar esta cuenta?' ?>');">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="accion" value="cambiar_estatus_equipo">
                                        <input type="hidden" name="id_usuario" value="<?= (int) $miembro['id_usuario'] ?>">
                                        <input type="hidden" name="nuevo_activo" value="<?= $miembro['activo'] ? '0' : '1' ?>">
                                        <button type="submit" class="btn btn-sm <?= $miembro['activo'] ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                            <?= $miembro['activo'] ? '🗑️ Eliminar' : '♻️ Reactivar' ?>
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Alta de miembro — reusa el handler crear_usuario ya existente -->
    <div class="modal fade" id="modalEquipoNuevo" tabindex="-1" aria-labelledby="modalEquipoNuevoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="accion" value="crear_usuario">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEquipoNuevoLabel">Nuevo Miembro del Equipo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="equipoNuevo_nombre" class="form-label">Nombre completo</label>
                        <input type="text" class="form-control" id="equipoNuevo_nombre" name="nombre_completo" maxlength="150" required>
                    </div>
                    <div class="mb-3">
                        <label for="equipoNuevo_email" class="form-label">Correo</label>
                        <input type="email" class="form-control" id="equipoNuevo_email" name="email" maxlength="150" required>
                    </div>
                    <div class="mb-3">
                        <label for="equipoNuevo_rol" class="form-label">Rol</label>
                        <select class="form-select" id="equipoNuevo_rol" name="rol_nuevo" required>
                            <?php foreach ($rolesCreablesPorRol as $rolOpcion): ?>
                                <option value="<?= e($rolOpcion) ?>"><?= e($etiquetasRol[$rolOpcion] ?? $rolOpcion) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="equipoNuevo_especialidad" class="form-label">Especialidad (sólo Coach)</label>
                        <input type="text" class="form-control" id="equipoNuevo_especialidad" name="especialidad" maxlength="100" placeholder="Ej. Fuerza y Acondicionamiento">
                    </div>
                    <?php if (in_array('atleta', $rolesCreablesPorRol, true)): ?>
                        <div class="mb-3">
                            <label for="equipoNuevo_atleta_vinculado" class="form-label">Atleta a vincular (sólo Atleta/Cliente)</label>
                            <select class="form-select" id="equipoNuevo_atleta_vinculado" name="id_atleta_vinculado">
                                <option value="">— Ninguno —</option>
                                <?php foreach ($atletasActivos as $atletaOpcion): ?>
                                    <option value="<?= (int) $atletaOpcion['id_atleta'] ?>"><?= e($atletaOpcion['nombre_completo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="equipoNuevo_password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="equipoNuevo_password" name="password" minlength="8" required>
                        <div class="form-text">Mínimo 8 caracteres.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-ssos-turquesa">Crear Miembro</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Editar miembro — modal compartido, se rellena vía JS (main.js) desde los data-* del botón -->
    <div class="modal fade" id="modalEquipoEditar" tabindex="-1" aria-labelledby="modalEquipoEditarLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="accion" value="editar_miembro_equipo">
                <input type="hidden" name="id_usuario" id="equipoEditar_id" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEquipoEditarLabel">Editar Miembro del Equipo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="equipoEditar_nombre" class="form-label">Nombre completo</label>
                        <input type="text" class="form-control" id="equipoEditar_nombre" name="nombre_completo" maxlength="150" required>
                    </div>
                    <div class="mb-3">
                        <label for="equipoEditar_email" class="form-label">Correo</label>
                        <input type="email" class="form-control" id="equipoEditar_email" name="email" maxlength="150" required>
                    </div>
                    <div class="mb-3">
                        <label for="equipoEditar_rol" class="form-label">Rol</label>
                        <select class="form-select" id="equipoEditar_rol" name="rol" required>
                            <option value="coach">Coach Especialista</option>
                            <option value="admin">Administración / Recepción</option>
                        </select>
                        <div class="form-text">El rol Dirección de Laboratorio no es asignable desde este panel.</div>
                    </div>
                    <div class="mb-3">
                        <label for="equipoEditar_especialidad" class="form-label">Especialidad (sólo Coach)</label>
                        <input type="text" class="form-control" id="equipoEditar_especialidad" name="especialidad" maxlength="100" placeholder="Ej. Fuerza y Acondicionamiento">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-ssos-turquesa">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resetear contraseña — modal compartido, mismo patrón que modalEquipoEditar -->
    <div class="modal fade" id="modalEquipoResetPassword" tabindex="-1" aria-labelledby="modalEquipoResetPasswordLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="accion" value="resetear_password_equipo">
                <input type="hidden" name="id_usuario" id="equipoResetPassword_id" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEquipoResetPasswordLabel">Resetear Contraseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p>Nueva contraseña para <strong id="equipoResetPassword_nombre"></strong>:</p>
                    <div class="mb-3">
                        <label for="equipoResetPassword_password" class="form-label">Contraseña nueva</label>
                        <input type="password" class="form-control" id="equipoResetPassword_password" name="password_nueva" minlength="8" required>
                        <div class="form-text">Mínimo 8 caracteres. El usuario deberá cambiarla en su próximo inicio de sesión.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-ssos-turquesa">Resetear Contraseña</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($verPieDeCancha): ?>
<div class="tab-pane fade ssos-tab-pane <?= 'pie-de-cancha' === $tabActivaPorDefecto ? 'show active' : '' ?>"
     id="pane-pie-de-cancha" role="tabpanel" aria-labelledby="tab-btn-pie-de-cancha">

    <h4>Sesiones del Día — Atletas y Pacientes</h4>
    <p class="text-body-secondary">
        Toca <strong>Iniciar Sesión</strong> sobre un atleta para capturar RPE y el checklist
        de Sentadilla Overhead en menos de 30 segundos.
    </p>
    <a href="<?= e(ssos_base_url()) ?>/agenda/index.php" class="btn btn-ssos-primary mb-3">📅 Ver Agenda Completa</a>

    <div class="pdc-grid mt-4">
        <?php if (empty($atletas_del_dia)): ?>
            <div class="ssos-table-card text-center text-body-secondary">
                No hay citas confirmadas para hoy<?= ($rol === 'coach' && $id_staff) ? ' asignadas a ti' : '' ?>.
            </div>
        <?php endif; ?>

        <?php foreach ($atletas_del_dia as $cita):
            $semaforo = $cita['semaforo'] ?? 'sin_dato';
        ?>
            <div class="pdc-athlete-card">
                <div>
                    <span class="ssos-semaforo ssos-semaforo--<?= e($semaforo) ?>"></span>
                    <span class="pdc-athlete-name"><?= e($cita['nombre_completo']) ?></span>
                </div>
                <div class="pdc-athlete-meta">Hora: <?= e(substr((string) $cita['hora_inicio'], 0, 5)) ?></div>
                <a class="pdc-start-btn text-decoration-none d-flex align-items-center justify-content-center"
                   href="coach_evaluacion.php?id_atleta=<?= (int) $cita['id_atleta'] ?>&id_cita=<?= (int) $cita['id_cita'] ?>">
                    Iniciar Sesión
                </a>
                <a class="btn btn-sm btn-outline-secondary text-center"
                   href="<?= e(ssos_base_url()) ?>/atleta/reporte.php?token=<?= e(ssos_generate_share_token((int) $cita['id_atleta'])) ?>"
                   target="_blank" rel="noopener noreferrer">Ver Reporte Athlos Score™</a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($verHerramientas): ?>
<div class="tab-pane fade ssos-tab-pane <?= 'herramientas' === $tabActivaPorDefecto ? 'show active' : '' ?>"
     id="pane-herramientas" role="tabpanel" aria-labelledby="tab-btn-herramientas">

    <p class="text-body-secondary">
        Migración de datos históricos y estado de la integración con sistemas externos (Next.js, bots).
    </p>

    <div class="ssos-table-card mb-4">
        <h5 class="mb-2">Migración de Datos Históricos</h5>
        <p class="text-body-secondary mb-3">
            Vuelca <code>Clientes.xlsx</code> (catálogo de cobranza legacy) a las tablas
            <code>atletas</code>/<code>membresias</code>/<code>pagos_asistencia</code>. Es seguro
            ejecutarla más de una vez con el mismo archivo — los pagos ya importados se detectan
            y se omiten automáticamente.
        </p>
        <a href="<?= e(ssos_base_url()) ?>/admin/migrar_excel.php" class="btn btn-ssos-primary btn-lg">
            📥 Ejecutar Migración Inicial de Clientes.xlsx
        </a>
    </div>

    <div class="ssos-table-card">
        <h5 class="mb-3">Estado de Integración API</h5>
        <table class="table table-hover align-middle mb-0">
            <tbody>
                <tr>
                    <th scope="row">API_WEBHOOK_SECRET</th>
                    <td><code><?= e(ssos_mask_secret($_ENV['API_WEBHOOK_SECRET'] ?? null)) ?></code></td>
                </tr>
                <tr>
                    <th scope="row">HMAC_SECRET (reportes)</th>
                    <td><code><?= e(ssos_mask_secret($_ENV['HMAC_SECRET'] ?? null)) ?></code></td>
                </tr>
                <tr>
                    <th scope="row">Orígenes permitidos (CORS)</th>
                    <td><code><?= e($_ENV['ALLOWED_ORIGINS'] ?? 'No configurado') ?></code></td>
                </tr>
                <tr>
                    <th scope="row">Servidor de base de datos conectado</th>
                    <td><code><?= e($db_host_actual) ?></code></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

</div>

<?php if ($verEquipo && !empty($erroresUsuarioNuevo)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new bootstrap.Modal(document.getElementById('modalEquipoNuevo')).show();
        });
    </script>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
