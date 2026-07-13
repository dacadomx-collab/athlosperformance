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
 * Días/horarios/aforo son configurables desde el Panel de Configuración
 * (Fase 24, `dashboard/configuracion_agenda.php`) — `AgendaBusinessRules`
 * los resuelve desde la BD con fallback determinístico si la migración
 * `07_schema_configuracion_agenda_publica.sql` aún no se aplicó. Arquitectura
 * genérica y agnóstica de este módulo documentada en
 * knowledge/MODULO_CALENDARIO_GENERICO.md.
 */

require_once __DIR__ . '/../config/helpers.php';

require_role('coach', 'admin', 'super_admin');

// CAPTURA DE DIAGNÓSTICO TEMPORAL (2026-07-13) — display_errors sigue
// desactivado en php.ini de producción (correcto: no debe filtrar rutas/DSN
// a una petición no autenticada). A partir de AQUÍ el usuario ya pasó
// require_role(), así que mostrar el detalle del error en pantalla sólo
// expone información interna a un coach/admin/super_admin ya autenticado —
// mismo perímetro de confianza que ya protege esta página. El error completo
// también se registra siempre en el log de PHP del servidor. Quitar este
// bloque una vez confirmado en producción cuál es el error real.
try {
    require_once __DIR__ . '/agenda_logica.php';

    $ssos_page_title = 'Agenda';
    $ssos_active_nav = 'agenda';
    require __DIR__ . '/../partials/header.php';
    require __DIR__ . '/agenda_vista.php';
    require __DIR__ . '/../partials/footer.php';
} catch (\Throwable $e) {
    error_log('[SSOS agenda FATAL] ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo '<pre style="padding:2rem;font-family:monospace;white-space:pre-wrap;background:#fff3f3;color:#900;border:1px solid #900;margin:2rem;">'
        . "Error interno al cargar la Agenda (diagnóstico temporal):\n\n"
        . htmlspecialchars(get_class($e) . ': ' . $e->getMessage()) . "\n"
        . htmlspecialchars($e->getFile() . ':' . $e->getLine())
        . '</pre>';
}
