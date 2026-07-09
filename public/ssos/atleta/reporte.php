<?php
declare(strict_types=1);

/**
 * ATHLOS SSOS v1.0 — REPORTE PÚBLICO "ATHLOS SCORE™"
 *
 * Vista imprimible sin login, protegida por un token firmado (HMAC,
 * ssos_generate_share_token()/ssos_verify_share_token() en config/helpers.php)
 * en vez de por sesión — se comparte con el atleta por WhatsApp/email.
 * REGLA-01: nunca es adivinable (no es el id_atleta plano en la URL) ni de
 * vigencia indefinida (72h por defecto).
 *
 * El radar se calcula server-side reutilizando AthlosBusinessRules
 * directamente (mismo cálculo que expone /ssos/api/athlos_score.php) en vez
 * de hacerle una llamada HTTP interna a esa API — evita abrir un segundo
 * endpoint público sin autenticar para los mismos datos clínicos.
 */

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/AthlosBusinessRules.php';

$token = (string) ($_GET['token'] ?? '');
$id_atleta = $token !== '' ? ssos_verify_share_token($token) : null;

$db = ssos_db();
$atleta = null;

if ($id_atleta !== null) {
    $stmt = $db->prepare('SELECT id_atleta, nombre_completo FROM atletas WHERE id_atleta = :id');
    $stmt->execute(['id' => $id_atleta]);
    $atleta = $stmt->fetch();
}

$score = $atleta ? AthlosBusinessRules::generarAthlosScore($db, $id_atleta) : null;

$etiquetasDimension = [
    'fuerza' => 'Fuerza / Funcionalidad',
    'movilidad' => 'Calidad de Movimiento',
    'composicion' => 'Composición Corporal',
];
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Athlos Score™<?= $atleta ? ' — ' . e($atleta['nombre_completo']) : '' ?></title>
    <link rel="icon" type="image/png" href="<?= e(ssos_asset_repo('assets/img/logo.png')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(ssos_base_url()) ?>/css/reporte.css" rel="stylesheet">
</head>
<body class="rpt-body">

<div class="rpt-header">
    <div class="rpt-brand">
        <img src="<?= e(ssos_base_url()) ?>/img/logo.jpg" alt="Athlos Performance">
        <span>Athlos Performance — Reporte Científico</span>
    </div>
    <?php if ($atleta): ?>
        <div class="rpt-actions no-print">
            <button type="button" class="btn btn-rpt" onclick="window.print()">Exportar / Imprimir PDF</button>
            <button type="button" class="btn btn-rpt" data-ssos-copy-link="<?= e(ssos_asset('atleta/reporte.php') . '?token=' . $token) ?>">
                📲 Copiar Link de Progreso
            </button>
        </div>
    <?php endif; ?>
</div>

<main class="rpt-main">
    <?php if (!$atleta): ?>
        <div class="rpt-card text-center">
            <h2>Enlace inválido o expirado</h2>
            <p class="text-body-secondary">
                Este enlace de reporte ya no es válido. Solicita uno nuevo a tu coach o al staff de Athlos Performance.
            </p>
        </div>
    <?php else: ?>
        <div class="rpt-card">
            <h1 class="rpt-athlete-name"><?= e($atleta['nombre_completo']) ?></h1>
            <p class="rpt-athlete-meta">Reporte generado el <?= e(date('d/m/Y')) ?></p>
        </div>

        <div class="rpt-card rpt-score-hero">
            <div class="rpt-score-label">Athlos Score™</div>
            <div class="rpt-score-value">
                <?= $score['athlos_score'] !== null ? e((string) $score['athlos_score']) : '—' ?>
            </div>
            <?php if ($score['athlos_score'] === null): ?>
                <p class="rpt-dimension-empty">Aún no hay evaluaciones suficientes para calcular el índice.</p>
            <?php endif; ?>

            <div class="rpt-radar-wrap">
                <canvas id="rptRadarChart" width="400" height="400"></canvas>
            </div>
        </div>

        <div class="rpt-card">
            <div class="rpt-dimensions">
                <?php foreach ($score['dimensiones'] as $clave => $dim): ?>
                    <div class="rpt-dimension">
                        <div class="rpt-dimension-label"><?= e($etiquetasDimension[$clave]) ?></div>
                        <?php if ($dim['score'] !== null): ?>
                            <div class="rpt-dimension-value"><?= e((string) $dim['score']) ?></div>
                            <div class="rpt-athlete-meta">Evaluado: <?= e((string) $dim['fecha']) ?></div>
                        <?php else: ?>
                            <div class="rpt-dimension-empty">Sin evaluación registrada</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <p class="rpt-disclaimer">
            El Athlos Score™ es un índice de referencia interno del laboratorio, no un diagnóstico médico.
            Toda interpretación clínica debe ser realizada por el staff de Athlos Performance.
        </p>

        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
        <script>
            const radarLabels = <?= json_encode($score['radar']['labels'], JSON_UNESCAPED_UNICODE) ?>;
            const radarValores = <?= json_encode($score['radar']['valores']) ?>;

            new Chart(document.getElementById('rptRadarChart'), {
                type: 'radar',
                data: {
                    labels: radarLabels,
                    datasets: [{
                        label: 'Athlos Score™',
                        data: radarValores.map((v) => v ?? 0),
                        backgroundColor: 'rgba(0, 184, 201, 0.25)',
                        borderColor: '#00B8C9',
                        pointBackgroundColor: '#0E3A5D',
                    }],
                },
                options: {
                    scales: {
                        r: {
                            min: 0,
                            max: 100,
                            ticks: { stepSize: 20 },
                        },
                    },
                    plugins: { legend: { display: false } },
                },
            });
        </script>
    <?php endif; ?>
</main>

<?php if ($atleta): ?>
<div class="toast-container position-fixed bottom-0 end-0 p-3 rpt-toast-container no-print">
    <div id="ssosToast" class="toast align-items-center text-bg-dark border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="ssosToastBody"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        "use strict";
        document.addEventListener("click", function (event) {
            var boton = event.target.closest("[data-ssos-copy-link]");
            if (!boton) {
                return;
            }
            var url = boton.getAttribute("data-ssos-copy-link");
            var mostrarToast = function (mensaje) {
                document.getElementById("ssosToastBody").textContent = mensaje;
                new bootstrap.Toast(document.getElementById("ssosToast")).show();
            };
            (navigator.clipboard ? navigator.clipboard.writeText(url) : Promise.reject())
                .then(function () {
                    mostrarToast("¡Enlace de progreso copiado! Listo para enviar por WhatsApp al atleta.");
                })
                .catch(function () {
                    mostrarToast("No se pudo copiar automáticamente. Copia el enlace manualmente: " + url);
                });
        });
    })();
</script>
<?php endif; ?>
</body>
</html>
