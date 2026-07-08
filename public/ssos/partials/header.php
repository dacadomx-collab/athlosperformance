<?php
declare(strict_types=1);

/**
 * Header compartido de la app autenticada SSOS (Dashboard Único).
 * Requiere que el caller haya hecho require_login() antes de incluir esto y
 * que exista $ssos_page_title (string) y opcionalmente $ssos_active_nav (string).
 */

$ssos_page_title = $ssos_page_title ?? 'Athlos SSOS';
$ssos_active_nav = $ssos_active_nav ?? '';
$ssos_rol = $_SESSION['clave_rol'] ?? '';
$ssos_nombre = $_SESSION['nombre_completo'] ?? '';

$ssos_rol_label = match ($ssos_rol) {
    'super_admin' => 'Dirección de Laboratorio',
    'admin'       => 'Administración / Recepción',
    'coach'       => 'Coach Especialista',
    default       => '',
};

$ssos_dashboard_href = ssos_base_url() . '/dashboard/index.php';
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Athlos SSOS — <?= e($ssos_page_title) ?></title>
    <link rel="icon" type="image/x-icon" href="<?= e(ssos_base_url()) ?>/img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(ssos_base_url()) ?>/css/main.css" rel="stylesheet">
</head>
<body class="ssos-app-body">

<nav class="navbar ssos-navbar">
    <div class="container-fluid ssos-navbar-row">
        <a class="navbar-brand" href="<?= e($ssos_dashboard_href) ?>">
            <img src="<?= e(ssos_base_url()) ?>/img/logo.jpg" alt="Athlos Performance">
            <span>Athlos SSOS</span>
        </a>
        <div class="ssos-navbar-actions">
            <button type="button" class="ssos-theme-toggle ssos-theme-toggle--inline" data-ssos-theme-toggle aria-label="Cambiar modo día/noche">🌙</button>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#ssosOffcanvasNav" aria-controls="ssosOffcanvasNav" aria-label="Abrir menú">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </div>
</nav>

<div class="offcanvas offcanvas-end ssos-offcanvas" tabindex="-1" id="ssosOffcanvasNav">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Menú</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <?php if ($ssos_rol_label !== ''): ?>
            <span class="ssos-role-badge"><?= e($ssos_rol_label) ?></span>
            <p class="mb-3"><?= e($ssos_nombre) ?></p>
        <?php endif; ?>

        <nav class="nav nav-pills flex-column mb-auto">
            <a class="nav-link <?= $ssos_active_nav === 'dashboard' ? 'active' : '' ?>" href="<?= e($ssos_dashboard_href) ?>">Dashboard</a>
            <?php if ($ssos_rol === 'super_admin'): ?>
                <a class="nav-link" href="<?= e($ssos_dashboard_href) ?>#control">Control</a>
            <?php endif; ?>
            <?php if (in_array($ssos_rol, ['admin', 'super_admin'], true)): ?>
                <a class="nav-link" href="<?= e($ssos_dashboard_href) ?>#clientes">Clientes y Membresías</a>
            <?php endif; ?>
            <?php if (in_array($ssos_rol, ['coach', 'admin', 'super_admin'], true)): ?>
                <a class="nav-link <?= $ssos_active_nav === 'pie_de_cancha' ? 'active' : '' ?>" href="<?= e($ssos_dashboard_href) ?>#pie-de-cancha">Pie de Cancha</a>
            <?php endif; ?>
        </nav>

        <a href="<?= e(ssos_base_url()) ?>/logout.php" class="btn btn-outline-secondary btn-sm mt-3">Cerrar sesión</a>
    </div>
</div>

<main class="ssos-main">
