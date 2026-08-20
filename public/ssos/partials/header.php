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
 * Fuente única de verdad de TODA la navegación principal por rol — se
 * recorre DOS veces más abajo (barra horizontal única en escritorio + menú
 * Offcanvas en móvil), así que un mismo enlace nunca puede faltar en una de
 * las dos superficies.
 *
 * DECISIÓN DE ARQUITECTURA (2026-08-12, "unificación de navegación"): este
 * arreglo es ahora la ÚNICA fuente de verdad de navegación, incluidas las
 * secciones internas del Dashboard (#control, #clientes, #equipo,
 * #pie-de-cancha, #herramientas). La barra de pestañas propia de
 * dashboard/index.php (`.ssos-tabs`) sigue existiendo en el DOM — Bootstrap
 * necesita esos botones para saber qué `.tab-pane` mostrar — pero se oculta
 * visualmente (`display: none` en main.css) porque quedó redundante con esta
 * barra. Un enlace del tipo "dashboard/index.php#equipo" funciona igual
 * llegando desde otra página (recarga completa, activarTabDesdeHash() activa
 * el tab al cargar) que ya estando en el Dashboard (navegación same-document
 * por hash, dispara "hashchange", mismo listener ya activa el tab sin
 * recargar) — cero JavaScript nuevo, reutiliza el mecanismo ya existente.
 */
$ssos_nav_items = [];
if ($ssos_rol === 'super_admin') {
    $ssos_nav_items[] = ['activo' => false, 'href' => $ssos_dashboard_href . '#control', 'icono' => '📊', 'label' => 'Dirección y Control'];
}
if (in_array($ssos_rol, ['admin', 'super_admin'], true)) {
    $ssos_nav_items[] = ['activo' => false, 'href' => $ssos_dashboard_href . '#clientes', 'icono' => '👥', 'label' => 'Clientes y Membresías'];
    $ssos_nav_items[] = ['activo' => false, 'href' => $ssos_dashboard_href . '#equipo', 'icono' => '🧑‍💼', 'label' => 'Equipo del Laboratorio'];
}
if (in_array($ssos_rol, ['coach', 'admin', 'super_admin'], true)) {
    $ssos_nav_items[] = ['activo' => $ssos_active_nav === 'pie_de_cancha', 'href' => $ssos_dashboard_href . '#pie-de-cancha', 'icono' => '🏋️‍♂️', 'label' => 'Sesiones del Día'];
    $ssos_nav_items[] = ['activo' => $ssos_active_nav === 'agenda', 'href' => ssos_base_url() . '/agenda/index.php', 'icono' => '📅', 'label' => 'Agenda'];
    $ssos_nav_items[] = ['activo' => $ssos_active_nav === 'testimonios', 'href' => ssos_base_url() . '/testimonios/index.php', 'icono' => '🌟', 'label' => 'Casos de Éxito'];
}
if ($ssos_rol === 'super_admin') {
    $ssos_nav_items[] = ['activo' => false, 'href' => $ssos_dashboard_href . '#herramientas', 'icono' => '🛠️', 'label' => 'Herramientas & API'];
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
