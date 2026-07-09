<?php
declare(strict_types=1);

require_once __DIR__ . '/config/helpers.php';

$db = ssos_db();

/** Verifica si ya existe un Super Admin activo en el sistema. */
function super_admin_ya_existe(PDO $db): bool
{
    $stmt = $db->query(
        "SELECT COUNT(*) FROM usuarios u
         INNER JOIN roles r ON r.id_rol = u.id_rol
         WHERE r.clave_rol = 'super_admin'"
    );
    return (int) $stmt->fetchColumn() > 0;
}

$ya_instalado = super_admin_ya_existe($db);
$errores = [];
$exito = false;

if (!$ya_instalado && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errores[] = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    }

    $nombre_completo = trim((string) ($_POST['nombre_completo'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($nombre_completo === '' || mb_strlen($nombre_completo) > 150) {
        $errores[] = 'El nombre es obligatorio (máximo 150 caracteres).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo electrónico no es válido.';
    }
    if (mb_strlen($password) < 8) {
        $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    }

    if (empty($errores)) {
        try {
            $db->beginTransaction();

            // Re-chequeo dentro de la transacción para blindar contra doble submit.
            if (super_admin_ya_existe($db)) {
                $db->rollBack();
                $ya_instalado = true;
            } else {
                $id_rol_super_admin = $db->query(
                    "SELECT id_rol FROM roles WHERE clave_rol = 'super_admin' LIMIT 1"
                )->fetchColumn();

                if ($id_rol_super_admin === false) {
                    throw new \RuntimeException('El rol super_admin no existe. Verifica que 01_schema_usuarios_rbac.sql fue aplicado.');
                }

                $stmt = $db->prepare(
                    'INSERT INTO usuarios (id_rol, nombre_completo, email, password_hash, activo, requiere_cambio_password)
                     VALUES (:id_rol, :nombre, :email, :hash, 1, 0)'
                );
                $stmt->execute([
                    'id_rol' => $id_rol_super_admin,
                    'nombre' => $nombre_completo,
                    'email'  => $email,
                    'hash'   => password_hash($password, PASSWORD_DEFAULT),
                ]);

                $id_usuario_nuevo = (int) $db->lastInsertId();

                $db->commit();

                log_sesion_evento($id_usuario_nuevo, $email, 'cambio_password');
                $exito = true;
            }
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $errores[] = 'No se pudo crear el Super Admin. Detalle técnico registrado en el log del servidor.';
            error_log('[SSOS setup_admin] ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Athlos Performance — Sistema de Control Deportivo | Instalación</title>
    <link rel="icon" type="image/png" href="<?= e(ssos_asset_repo('assets/img/logo.png')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/ssos-auth.css" rel="stylesheet">
</head>
<body class="ssos-auth-body">
    <button type="button" class="ssos-theme-toggle" data-ssos-theme-toggle aria-label="Cambiar modo día/noche">🌙</button>

    <div class="ssos-auth-card">
        <div class="ssos-auth-brand">
            <img src="img/logo.jpg" alt="Athlos Performance" class="ssos-auth-logo">
            <h1>Athlos Performance</h1>
            <small>Instalación inicial · Dirección de Laboratorio</small>
        </div>

        <?php if ($ya_instalado && !$exito): ?>
            <div class="alert alert-warning ssos-alert" role="alert">
                <strong>Instalación completada.</strong> Por seguridad, este archivo ya no está activo.
            </div>
            <a href="login.php" class="btn btn-ssos-turquesa d-block text-center text-decoration-none">Ir al Login</a>

        <?php elseif ($exito): ?>
            <div class="alert alert-success ssos-alert" role="alert">
                <strong>¡Super Admin creado con éxito!</strong> Ya puedes iniciar sesión con tus credenciales.
            </div>
            <a href="login.php" class="btn btn-ssos-turquesa d-block text-center text-decoration-none">Ir al Login</a>

        <?php else: ?>
            <?php foreach ($errores as $error): ?>
                <div class="alert alert-danger ssos-alert" role="alert"><?= e($error) ?></div>
            <?php endforeach; ?>

            <form method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="mb-3">
                    <label for="nombre_completo" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="nombre_completo" name="nombre_completo"
                           value="<?= e($_POST['nombre_completo'] ?? '') ?>" maxlength="150" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Correo</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= e($_POST['email'] ?? '') ?>" maxlength="150" required>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password"
                           minlength="8" required>
                    <div class="form-text">Mínimo 8 caracteres.</div>
                </div>

                <button type="submit" class="btn btn-ssos-turquesa w-100">Generar Super Admin</button>
            </form>
        <?php endif; ?>
    </div>

    <script src="js/main.js"></script>
</body>
</html>
