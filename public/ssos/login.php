<?php
declare(strict_types=1);

require_once __DIR__ . '/config/helpers.php';

const MAX_INTENTOS_FALLIDOS = 5;
const BLOQUEO_MINUTOS = 15;

$db = ssos_db();
$error = null;

if (!empty($_SESSION['id_usuario']) && !empty($_SESSION['clave_rol'])) {
    redirect_post_login($_SESSION['clave_rol']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $stmt = $db->prepare(
            'SELECT u.id_usuario, u.nombre_completo, u.email, u.password_hash, u.activo,
                    u.intentos_fallidos, u.bloqueado_hasta, u.id_staff, r.clave_rol
             FROM usuarios u
             INNER JOIN roles r ON r.id_rol = u.id_rol
             WHERE u.email = :email
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch();

        $ahora = new DateTimeImmutable();
        $bloqueado = $usuario && !empty($usuario['bloqueado_hasta'])
            && new DateTimeImmutable($usuario['bloqueado_hasta']) > $ahora;

        if (!$usuario || !$usuario['activo']) {
            log_sesion_evento(null, $email, 'login_fallido');
            $error = 'Credenciales inválidas.';
        } elseif ($bloqueado) {
            log_sesion_evento((int) $usuario['id_usuario'], $email, 'login_fallido');
            $error = 'Cuenta bloqueada temporalmente por múltiples intentos fallidos. Intenta más tarde.';
        } elseif (!password_verify($password, $usuario['password_hash'])) {
            $intentos = (int) $usuario['intentos_fallidos'] + 1;
            $bloqueado_hasta = null;
            if ($intentos >= MAX_INTENTOS_FALLIDOS) {
                $bloqueado_hasta = $ahora->modify('+' . BLOQUEO_MINUTOS . ' minutes')->format('Y-m-d H:i:s');
            }
            $upd = $db->prepare('UPDATE usuarios SET intentos_fallidos = :intentos, bloqueado_hasta = :bloqueo WHERE id_usuario = :id');
            $upd->execute(['intentos' => $intentos, 'bloqueo' => $bloqueado_hasta, 'id' => $usuario['id_usuario']]);

            log_sesion_evento((int) $usuario['id_usuario'], $email, 'login_fallido');
            $error = 'Credenciales inválidas.';
        } else {
            $upd = $db->prepare(
                'UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_login = NOW() WHERE id_usuario = :id'
            );
            $upd->execute(['id' => $usuario['id_usuario']]);

            session_regenerate_id(true);
            $_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
            $_SESSION['nombre_completo'] = $usuario['nombre_completo'];
            $_SESSION['email'] = $usuario['email'];
            $_SESSION['clave_rol'] = $usuario['clave_rol'];
            $_SESSION['id_staff'] = $usuario['id_staff'] !== null ? (int) $usuario['id_staff'] : null;

            log_sesion_evento((int) $usuario['id_usuario'], $email, 'login_exitoso');
            redirect_post_login($usuario['clave_rol']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Athlos Performance — Sistema de Control Deportivo | Iniciar sesión</title>
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
            <small>Sistema de Control Deportivo</small>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger ssos-alert" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div class="mb-3">
                <label for="email" class="form-label">Correo</label>
                <input type="email" class="form-control" id="email" name="email"
                       value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Contraseña</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password" required>
                    <button type="button" class="btn btn-outline-secondary" data-ssos-toggle-password="password" aria-label="Mostrar contraseña">👁️</button>
                </div>
            </div>

            <button type="submit" class="btn btn-ssos-turquesa w-100">Iniciar sesión</button>
        </form>
    </div>

    <script src="js/main.js"></script>
</body>
</html>
