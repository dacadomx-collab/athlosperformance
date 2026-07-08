<?php
declare(strict_types=1);

/**
 * ATHLOS PERFORMANCE — GENERADOR DE USUARIOS DE PRUEBA
 *
 * Crea (de forma idempotente) las 2 cuentas de prueba pedidas para validar
 * localmente el cambio de rol: admin.test@athlos.local / Admin123! y
 * coach.test@athlos.local / Coach123!. Sólo Super Admin puede ejecutarlo.
 *
 * ⚠️ ADVERTENCIA DE SEGURIDAD (mostrada también en pantalla): las contraseñas
 * son deliberadamente simples y públicas en este archivo — están pensadas
 * ÚNICAMENTE para pruebas de cambio de rol en un entorno de desarrollo. Este
 * script crea las cuentas en la base de datos a la que la app esté conectada
 * EN ESE MOMENTO (revisa el tab "Herramientas & API" del Dashboard para
 * confirmar cuál es antes de ejecutar esto). Si se ejecuta contra producción,
 * quedarán credenciales predecibles en un sistema real — bórralas después de
 * usarlas, o cambia sus contraseñas.
 */

require_once __DIR__ . '/../config/helpers.php';

require_role('super_admin');

$db = ssos_db();

$usuariosPrueba = [
    'admin' => ['email' => 'admin.test@athlos.local', 'password' => 'Admin123!', 'nombre' => 'Admin de Prueba'],
    'coach' => ['email' => 'coach.test@athlos.local', 'password' => 'Coach123!', 'nombre' => 'Coach de Prueba'],
];

$resultado = [];
$ejecutado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'sembrar') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $resultado[] = ['ok' => false, 'mensaje' => 'Token de seguridad inválido. Recarga la página e intenta de nuevo.'];
    } else {
        $ejecutado = true;
        foreach ($usuariosPrueba as $rolClave => $datos) {
            try {
                $stmt = $db->prepare('SELECT id_usuario FROM usuarios WHERE email = :email LIMIT 1');
                $stmt->execute(['email' => $datos['email']]);

                if ($stmt->fetch()) {
                    $resultado[] = ['ok' => true, 'mensaje' => "{$datos['email']} ya existía — no se duplicó."];
                    continue;
                }

                $idStaff = null;
                if ($rolClave === 'coach') {
                    $stmt = $db->prepare('SELECT id_staff FROM staff WHERE email = :email LIMIT 1');
                    $stmt->execute(['email' => $datos['email']]);
                    $staffExistente = $stmt->fetch();

                    if ($staffExistente) {
                        $idStaff = (int) $staffExistente['id_staff'];
                    } else {
                        $stmt = $db->prepare(
                            'INSERT INTO staff (nombre_completo, especialidad, email, activo) VALUES (:nombre, :especialidad, :email, 1)'
                        );
                        $stmt->execute(['nombre' => $datos['nombre'], 'especialidad' => 'Cuenta de prueba', 'email' => $datos['email']]);
                        $idStaff = (int) $db->lastInsertId();
                    }
                }

                $stmt = $db->prepare('SELECT id_rol FROM roles WHERE clave_rol = :clave');
                $stmt->execute(['clave' => $rolClave]);
                $idRol = $stmt->fetchColumn();

                $stmt = $db->prepare(
                    'INSERT INTO usuarios (id_rol, id_staff, nombre_completo, email, password_hash, activo, requiere_cambio_password)
                     VALUES (:id_rol, :id_staff, :nombre, :email, :hash, 1, 0)'
                );
                $stmt->execute([
                    'id_rol' => $idRol,
                    'id_staff' => $idStaff,
                    'nombre' => $datos['nombre'],
                    'email' => $datos['email'],
                    'hash' => password_hash($datos['password'], PASSWORD_DEFAULT),
                ]);

                $resultado[] = ['ok' => true, 'mensaje' => "{$datos['email']} creado exitosamente.", 'nuevo' => true];
            } catch (\Throwable $e) {
                $resultado[] = ['ok' => false, 'mensaje' => "Error creando {$datos['email']}: " . $e->getMessage()];
                error_log('[SSOS seed_test_users] ' . $e->getMessage());
            }
        }
    }
}

$ssos_page_title = 'Generador de Usuarios de Prueba';
$ssos_active_nav = 'herramientas';
require __DIR__ . '/../partials/header.php';
?>

<span class="ssos-role-badge">Generador de Usuarios de Prueba</span>
<h2 class="mt-3">Cuentas de Prueba para Cambio de Rol</h2>

<div class="alert alert-warning ssos-alert" role="alert">
    ⚠️ Las contraseñas de estas cuentas son intencionalmente simples y quedan escritas en este mismo
    archivo — sólo para pruebas locales de cambio de rol. Verifica en la pestaña
    <a href="<?= e(ssos_base_url()) ?>/dashboard/index.php#herramientas">Herramientas &amp; API</a>
    a qué base de datos está conectada la app antes de ejecutar esto. Si terminas usándolas en
    producción, bórralas o cámbiales la contraseña después.
</div>

<?php foreach ($resultado as $r): ?>
    <div class="alert alert-<?= $r['ok'] ? 'success' : 'danger' ?> ssos-alert" role="alert"><?= e($r['mensaje']) ?></div>
<?php endforeach; ?>

<div class="ssos-table-card mb-4">
    <h5 class="mb-3">Cuentas que se crearán (si no existen)</h5>
    <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Rol</th><th>Email</th><th>Contraseña</th></tr></thead>
        <tbody>
            <?php foreach ($usuariosPrueba as $rolClave => $datos): ?>
                <tr>
                    <td><?= $rolClave === 'admin' ? 'Administración / Recepción' : 'Coach Especialista' ?></td>
                    <td><code><?= e($datos['email']) ?></code></td>
                    <td><code><?= e($datos['password']) ?></code></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (!$ejecutado): ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="accion" value="sembrar">
        <button type="submit" class="btn btn-ssos-turquesa btn-lg">Generar Usuarios de Prueba</button>
    </form>
<?php else: ?>
    <a href="seed_test_users.php" class="btn btn-outline-secondary">Ejecutar de nuevo (es idempotente)</a>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
