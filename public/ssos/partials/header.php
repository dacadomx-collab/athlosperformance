<?php
declare(strict_types=1);

/**
 * Header compartido de la app autenticada SSOS (Dashboard Único).
 * Requiere que el caller haya hecho require_login() antes de incluir esto y
 * que exista $ssos_page_title (string) y opcionalmente $ssos_active_nav (string).
 */

$ssos_page_title = $ssos_page_title ?? 'Athlos Performance';
$ssos_active_nav = $ssos_active_nav ?? '';
$ssos_rol = $_SESSION['clave_rol'] ?? '';
$ssos_nombre = $_SESSION['nombre_completo'] ?? '';

$ssos_rol_label = match ($ssos_rol) {
    'super_admin' => 'Dirección de Laboratorio',
    'admin'       => 'Administración / Recepción',
    'coach'       => 'Coach Especialista',
    'atleta'      => 'Portal del Atleta',
    default       => '',
};

$ssos_dashboard_href = ssos_base_url() . '/dashboard/index.php';

// Breadcrumb "Volver al Expediente de {Nombre}": cada formulario/vista ligada
// a un atleta define $ssos_breadcrumb_atleta = ['id_atleta' => X, 'nombre' => Y]
// antes de incluir este header. El link "Volver al Dashboard" es universal —
// se oculta sólo en el propio dashboard, para no linkear una página a sí misma.
$ssos_breadcrumb_atleta = $ssos_breadcrumb_atleta ?? null;
$ssos_mostrar_breadcrumb_dashboard = $ssos_active_nav !== 'dashboard' && $ssos_rol !== 'atleta';

/**
 * Fuente única de verdad de los ítems de navegación GLOBAL (entre páginas
 * distintas) por rol — se recorre DOS veces más abajo (barra horizontal en
 * escritorio + menú Offcanvas en móvil), así que un mismo enlace nunca puede
 * faltar en una de las dos superficies.
 *
 * CORRECCIÓN (auditoría visual "triple menú"): este arreglo ya NO incluye
 * los anclajes internos del Dashboard (#control, #clientes, #equipo,
 * #pie-de-cancha, #herramientas) — ese Dashboard ya tiene su PROPIA barra de
 * pestañas nativa (`.ssos-tabs` en dashboard/index.php) para esa navegación.
 * Duplicarlos aquí producía 3 superficies de navegación simultáneas y
 * redundantes en pantalla (barra superior + pestañas del Dashboard +
 * Offcanvas) en vez de una jerarquía clara. Este menú global sólo enlaza
 * páginas VERDADERAMENTE separadas (URL propia, fuera del sistema de tabs
 * del Dashboard) — el logo (`.navbar-brand`) ya funciona como acceso directo
 * al Dashboard, así que tampoco se repite aquí como ítem de texto.
 */
$ssos_nav_items = [];
if (in_array($ssos_rol, ['coach', 'admin', 'super_admin'], true)) {
    $ssos_nav_items[] = ['activo' => $ssos_active_nav === 'agenda', 'href' => ssos_base_url() . '/agenda/index.php', 'icono' => '📅', 'label' => 'Agenda'];
    $ssos_nav_items[] = ['activo' => $ssos_active_nav === 'testimonios', 'href' => ssos_base_url() . '/testimonios/index.php', 'icono' => '🌟', 'label' => 'Casos de Éxito'];
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Athlos Performance — Sistema de Control Deportivo | <?= e($ssos_page_title) ?></title>
    <link rel="icon" type="image/png" href="<?= e(ssos_asset_repo('assets/img/logo.png')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(ssos_base_url()) ?>/css/main.css" rel="stylesheet">
</head>
<body class="ssos-app-body">

<nav class="navbar ssos-navbar">
    <div class="container-fluid ssos-navbar-row">
        <a class="navbar-brand" href="<?= e($ssos_dashboard_href) ?>">
            <img src="<?= e(ssos_base_url()) ?>/img/logo.jpg" alt="Athlos Performance">
            <span>Athlos Performance</span>
        </a>

        <!-- Barra de navegación horizontal — visible sólo en escritorio (≥992px, mismo
             breakpoint lg de Bootstrap ya usado en el resto de main.css). El menú
             hamburguesa sigue existiendo también en escritorio (da acceso a rol/nombre/
             cerrar sesión) — esto es un COMPLEMENTO, nunca reemplaza al Offcanvas. -->
        <?php if (!empty($ssos_nav_items)): ?>
            <nav class="ssos-navbar-desktop-nav" aria-label="Navegación principal">
                <?php foreach ($ssos_nav_items as $item): ?>
                    <a class="<?= $item['activo'] ? 'is-active' : '' ?>" href="<?= e($item['href']) ?>"><?= $item['icono'] ?> <?= e($item['label']) ?></a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

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
            <?php foreach ($ssos_nav_items as $item): ?>
                <a class="nav-link <?= $item['activo'] ? 'active' : '' ?>" href="<?= e($item['href']) ?>" data-bs-dismiss="offcanvas"><?= $item['icono'] ?> <?= e($item['label']) ?></a>
            <?php endforeach; ?>
        </nav>

        <a href="<?= e(ssos_base_url()) ?>/logout.php" class="btn btn-outline-secondary btn-sm mt-3">Cerrar sesión</a>
    </div>
</div>

<main class="ssos-main">

<?php if ($ssos_mostrar_breadcrumb_dashboard || $ssos_breadcrumb_atleta): ?>
    <div class="ssos-breadcrumb arf-grid mb-3<?= $ssos_active_nav === 'agenda' ? ' ssos-breadcrumb--full-viewport-movil' : '' ?>">
        <?php if ($ssos_mostrar_breadcrumb_dashboard): ?>
            <a href="<?= e($ssos_dashboard_href) ?>" class="btn btn-sm btn-ssos-outline">⬅️ Volver al Dashboard</a>
        <?php endif; ?>
        <?php if ($ssos_breadcrumb_atleta): ?>
            <a href="<?= e(ssos_base_url()) ?>/atleta/expediente.php?id_atleta=<?= (int) $ssos_breadcrumb_atleta['id_atleta'] ?>" class="btn btn-sm btn-ssos-outline">
                📂 Volver al Expediente de <?= e($ssos_breadcrumb_atleta['nombre']) ?>
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>
