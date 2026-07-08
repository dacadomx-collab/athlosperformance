<?php
declare(strict_types=1);

require_once __DIR__ . '/config/helpers.php';

if (!empty($_SESSION['id_usuario'])) {
    log_sesion_evento((int) $_SESSION['id_usuario'], $_SESSION['nombre_completo'] ?? '', 'logout');
}

$_SESSION = [];
session_destroy();

header('Location: login.php');
exit;
