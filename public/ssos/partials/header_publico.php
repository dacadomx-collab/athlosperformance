<?php
declare(strict_types=1);

/**
 * Header para páginas PÚBLICAS sin login (ej. agenda_publica.php).
 * Deliberadamente NO incluye menú, sesión, ni datos de rol — sólo marca y tema.
 * No confundir con partials/header.php (backoffice autenticado).
 */

$ssos_page_title = $ssos_page_title ?? 'Athlos Performance';
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($ssos_page_title) ?></title>
    <link rel="icon" type="image/png" href="<?= e(ssos_asset_repo('assets/img/logo.png')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(ssos_base_url()) ?>/css/main.css" rel="stylesheet">
</head>
<body class="ssos-app-body">

<nav class="navbar ssos-navbar">
    <div class="container-fluid ssos-navbar-row">
        <a class="navbar-brand" href="<?= e(ssos_base_url()) ?>/agenda/agenda_publica.php">
            <img src="<?= e(ssos_base_url()) ?>/img/logo.jpg" alt="Athlos Performance">
            <span>Athlos Performance</span>
        </a>
        <div class="ssos-navbar-actions">
            <button type="button" class="ssos-theme-toggle ssos-theme-toggle--inline" data-ssos-theme-toggle aria-label="Cambiar modo día/noche">🌙</button>
        </div>
    </div>
</nav>

<main class="ssos-main">
