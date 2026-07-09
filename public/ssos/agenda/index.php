<?php
declare(strict_types=1);

/**
 * ATHLOS PERFORMANCE — MÓDULO DE AGENDA / CALENDARIO DE CITAS (matriz semanal)
 * Página standalone — la misma matriz también se embebe como pestaña de
 * aterrizaje en dashboard/index.php (Fase 23). Toda la lógica de datos y los
 * POST handlers viven en agenda_logica.php para no duplicarse entre ambos
 * puntos de entrada; este archivo sólo agrega el chequeo de rol propio de
 * una página standalone y el envoltorio de header/footer.
 *
 * Vista de semana continua (Lunes-Sábado, domingo deliberadamente ausente de
 * la matriz — ver AgendaBusinessRules::diasOperativos()) estilo Google
 * Calendar: 80% matriz central + sidebar de clientes del mes (progreso de
 * sesiones) + sidebar de coaches (color asignado). En móvil colapsa a una
 * vista de un solo día a pantalla completa (100dvh) — ver css `.ssos-agenda-*`
 * en main.css.
 *
 * Cupo estricto: máximo AgendaBusinessRules::CUPO_MAXIMO_FRANJA citas activas
 * por franja de hora (semáforo verde/amarillo/rojo). Arquitectura genérica y
 * agnóstica de este módulo documentada en knowledge/MODULO_CALENDARIO_GENERICO.md.
 */

require_once __DIR__ . '/../config/helpers.php';

require_role('coach', 'admin', 'super_admin');

require_once __DIR__ . '/agenda_logica.php';

$ssos_page_title = 'Agenda';
$ssos_active_nav = 'agenda';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/agenda_vista.php';
require __DIR__ . '/../partials/footer.php';
