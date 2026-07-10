<div class="ssos-agenda-publica container py-3 py-lg-4" style="max-width: 960px;">
    <div class="text-center mb-4">
        <h1 class="h3 mb-1">Disponibilidad de citas</h1>
        <p class="text-body-secondary mb-0">Elige un horario libre para solicitar tu cita. El equipo confirmará contigo antes de agendarla en definitiva.</p>
    </div>

    <?php if ($mensajeOk): ?>
        <div class="alert alert-success ssos-alert" role="alert"><?= e($mensajeOk) ?></div>
    <?php endif; ?>
    <?php foreach ($errores as $error): ?>
        <div class="alert alert-danger ssos-alert" role="alert"><?= e($error) ?></div>
    <?php endforeach; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <a class="btn btn-sm btn-ssos-outline" href="?fecha=<?= e($semanaAnteriorFecha) ?>">← Semana anterior</a>
        <h2 class="h6 mb-0 text-center"><?= e($tituloSemana) ?></h2>
        <a class="btn btn-sm btn-ssos-outline" href="?fecha=<?= e($semanaSiguienteFecha) ?>">Semana siguiente →</a>
    </div>

    <?php $hoy = date('Y-m-d'); ?>
    <div class="row g-3">
        <?php foreach ($diasSemana as $dia): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="ssos-table-card h-100">
                    <h5 class="mb-2"><?= e($dia['label']) ?> <?= (int) $dia['dia_mes'] ?></h5>
                    <div class="d-flex flex-wrap gap-2">
                        <?php
                        $hayLibres = false;
                        foreach ($horasMatriz as $hora):
                            if (!AgendaBusinessRules::franjaEsOperativa($diasOperativos, $dia['dia_iso'], $hora)) {
                                continue;
                            }
                            if ($dia['fecha'] < $hoy || ($dia['fecha'] === $hoy && $hora < date('H:i'))) {
                                continue; // franja ya pasada
                            }
                            $motivoBloqueo = AgendaBusinessRules::franjaBloqueada($bloqueosSemana, $dia['fecha'], $hora . ':00', null);
                            if ($motivoBloqueo !== null) {
                                continue;
                            }
                            $ocupadas = $ocupacionPorCelda[$dia['fecha'] . '|' . $hora] ?? 0;
                            if ($ocupadas >= $cupoMaximoFranja) {
                                continue; // lleno
                            }
                            $hayLibres = true;
                            ?>
                            <button type="button" class="btn btn-sm btn-outline-success ssos-slot-publico"
                                    data-bs-toggle="modal" data-bs-target="#ssosModalSolicitud"
                                    data-fecha="<?= e($dia['fecha']) ?>" data-hora="<?= e($hora) ?>"
                                    data-etiqueta="<?= e($dia['label'] . ' ' . $dia['dia_mes'] . ' · ' . $hora) ?>">
                                <?= e($hora) ?>
                            </button>
                        <?php endforeach; ?>
                        <?php if (!$hayLibres): ?>
                            <span class="text-body-secondary small">Sin horarios libres.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal fade" id="ssosModalSolicitud" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="accion" value="solicitar_cita_publica">
                <input type="hidden" name="fecha_cita" id="ssosSolicitudFecha">
                <input type="hidden" name="hora_inicio" id="ssosSolicitudHora">
                <div class="modal-header">
                    <h5 class="modal-title">Solicitar cita — <span id="ssosSolicitudEtiqueta"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-body-secondary small">Esta acción NO reserva tu cita automáticamente. Tu solicitud queda pendiente de aprobación y el equipo te contactará para confirmarla.</p>
                    <div class="mb-3">
                        <label class="form-label" for="ssosSolicitudNombre">Nombre completo</label>
                        <input type="text" class="form-control" id="ssosSolicitudNombre" name="solicitante_nombre" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label" for="ssosSolicitudTelefono">Teléfono</label>
                            <input type="tel" class="form-control" id="ssosSolicitudTelefono" name="solicitante_telefono">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="ssosSolicitudEmail">Correo</label>
                            <input type="email" class="form-control" id="ssosSolicitudEmail" name="solicitante_email">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="ssosSolicitudServicio">Servicio</label>
                        <select class="form-select" id="ssosSolicitudServicio" name="id_servicio" required>
                            <option value="">Selecciona un servicio…</option>
                            <?php foreach ($servicios as $s): ?>
                                <option value="<?= (int) $s['id_servicio'] ?>"><?= e($s['nombre_servicio']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label" for="ssosSolicitudStaff">Especialista de preferencia</label>
                        <select class="form-select" id="ssosSolicitudStaff" name="id_staff" required>
                            <option value="">Selecciona un especialista…</option>
                            <?php foreach ($staffList as $st): ?>
                                <option value="<?= (int) $st['id_staff'] ?>"><?= e($st['nombre_completo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-ssos-turquesa">Enviar solicitud</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.ssos-slot-publico').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.getElementById('ssosSolicitudFecha').value = btn.dataset.fecha;
        document.getElementById('ssosSolicitudHora').value = btn.dataset.hora;
        document.getElementById('ssosSolicitudEtiqueta').textContent = btn.dataset.etiqueta;
    });
});
</script>
