<?php
declare(strict_types=1);

/**
 * Vista de agenda.php — separada del controlador por tamaño. Recibe todas
 * las variables ya calculadas ($diasSemana, $horasMatriz, $citasPorCelda,
 * $coloresStaff, $clientesDelMes, etc.) desde index.php o desde el tab-pane
 * de dashboard/index.php vía `require`. Cuando se embebe en el Dashboard, el
 * caller define `$ssos_agenda_embebida = true` ANTES de este require, para
 * no duplicar el encabezado de página (el Dashboard ya pone el suyo propio
 * arriba de las pestañas).
 */
$ssos_agenda_embebida = $ssos_agenda_embebida ?? false;
?>

<?php if (!$ssos_agenda_embebida): ?>
    <span class="ssos-role-badge">Agenda y Calendario de Citas</span>
    <h2 class="mt-3">Semana del <?= e($lunes->format('d/m/Y')) ?></h2>
<?php endif; ?>
<p class="text-body-secondary">Cupo máximo: <strong><?= AgendaBusinessRules::CUPO_MAXIMO_FRANJA ?> personas por franja de hora</strong>. Lunes a Sábado — domingo cerrado.</p>

<?php if ($mensajeOk): ?>
    <div class="alert alert-success ssos-alert" role="alert"><?= e($mensajeOk) ?></div>
<?php endif; ?>
<?php foreach ($errores as $error): ?>
    <div class="alert alert-danger ssos-alert" role="alert"><?= e($error) ?></div>
<?php endforeach; ?>

<div class="ssos-agenda-toolbar arf-grid mb-3">
    <a href="?fecha=<?= e($lunes->modify('-7 days')->format('Y-m-d')) ?>" class="btn btn-sm btn-ssos-outline">◀ Semana anterior</a>
    <span class="ssos-agenda-titulo-semana"><?= e(ucfirst($tituloSemana)) ?></span>
    <a href="?fecha=<?= e($lunes->modify('+7 days')->format('Y-m-d')) ?>" class="btn btn-sm btn-ssos-outline">Semana siguiente ▶</a>
    <span class="ssos-agenda-indicador-avance" title="<?= $franjasOcupadasTotales ?> de <?= $franjasOperativasTotales * AgendaBusinessRules::CUPO_MAXIMO_FRANJA ?> lugares ocupados esta semana">
        📊 <?= $pctOcupacionSemana ?>% de ocupación esta semana
    </span>
    <button type="button" class="btn btn-sm btn-ssos-turquesa ms-auto" data-bs-toggle="modal" data-bs-target="#modalNuevaCita">+ Nueva Cita</button>
</div>

<!-- ══════════ VISTA DESKTOP: matriz semanal 80% + sidebars ══════════ -->
<div class="ssos-agenda-desktop d-none d-lg-flex">
    <aside class="ssos-agenda-sidebar ssos-agenda-sidebar--izq">
        <h6 class="mb-2 d-flex align-items-center justify-content-between">
            Clientes del mes
            <?php if ($alertasSesionesBajas > 0): ?>
                <span class="badge text-bg-warning" title="Clientes con 2 sesiones o menos restantes">⚠️ <?= $alertasSesionesBajas ?></span>
            <?php endif; ?>
        </h6>
        <?php if (empty($clientesDelMes)): ?>
            <p class="text-body-secondary small">Sin membresías activas este mes.</p>
        <?php endif; ?>
        <?php foreach ($clientesDelMes as $cliente): ?>
            <?php
                $totales = (int) $cliente['sesiones_totales'];
                $restantes = (int) $cliente['sesiones_restantes'];
                $consumidas = max(0, $totales - $restantes);
                $pct = $totales > 0 ? (int) round(($consumidas / $totales) * 100) : 0;
                $alerta = $restantes <= 2;
            ?>
            <a href="<?= e(ssos_base_url()) ?>/atleta/expediente.php?id_atleta=<?= (int) $cliente['id_atleta'] ?>"
               class="ssos-agenda-cliente-mes <?= $alerta ? 'ssos-agenda-cliente-mes--alerta' : '' ?>"
               title="Ver expediente de <?= e($cliente['nombre_completo']) ?>">
                <div class="ssos-agenda-cliente-mes-nombre"><?= e($cliente['nombre_completo']) ?><?= $alerta ? ' ⚠️' : '' ?></div>
                <div class="ssos-agenda-cliente-mes-servicio"><?= e($cliente['nombre_servicio']) ?></div>
                <div class="progress ssos-agenda-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $pct ?>">
                    <div class="progress-bar ssos-agenda-progress-bar" style="width: <?= $pct ?>%"></div>
                </div>
                <div class="ssos-agenda-cliente-mes-detalle"><?= $consumidas ?>/<?= $totales ?> sesiones — quedan <?= $restantes ?></div>
            </a>
        <?php endforeach; ?>
    </aside>

    <div class="ssos-agenda-matriz-wrap">
        <table class="ssos-agenda-matriz">
            <thead>
                <tr>
                    <th class="ssos-agenda-col-hora"></th>
                    <?php foreach ($diasSemana as $dia): ?>
                        <th>
                            <div class="ssos-agenda-dia-label"><?= e($dia['label']) ?></div>
                            <div class="ssos-agenda-dia-fecha"><?= (int) $dia['dia_mes'] ?> <?= e($dia['mes_label']) ?></div>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($horasMatriz as $hora): ?>
                    <tr>
                        <th class="ssos-agenda-col-hora"><?= e($hora) ?></th>
                        <?php foreach ($diasSemana as $dia): ?>
                            <?php
                                $operativa = AgendaBusinessRules::franjaEsOperativa($dia['dia_iso'], $hora);
                                $citasCelda = $citasPorCelda[$dia['fecha']][$hora] ?? [];
                                $ocupadas = $ocupacionPorCelda[$dia['fecha']][$hora] ?? 0;
                                $semaforo = $operativa ? AgendaBusinessRules::semaforoFranja($ocupadas) : null;
                            ?>
                            <?php if (!$operativa): ?>
                                <td class="ssos-agenda-celda ssos-agenda-celda--cerrada"></td>
                            <?php else: ?>
                                <td class="ssos-agenda-celda ssos-agenda-celda--<?= $semaforo ?>"
                                    data-ssos-agenda-celda
                                    data-fecha="<?= e($dia['fecha']) ?>"
                                    data-hora="<?= e($hora) ?>"
                                    data-ocupadas="<?= $ocupadas ?>">
                                    <?php foreach ($citasCelda as $cita): ?>
                                        <?php if (!in_array($cita['estatus_cita'], ['reservada', 'confirmada'], true)) continue; ?>
                                        <div class="ssos-agenda-cita"
                                             draggable="true"
                                             data-ssos-agenda-cita
                                             data-id-cita="<?= (int) $cita['id_cita'] ?>"
                                             data-id-staff="<?= (int) $cita['id_staff'] ?>"
                                             data-nombre="<?= e($cita['atleta_nombre'] ?? ($cita['notas_previas'] ?: 'Prospecto')) ?>"
                                             data-staff-nombre="<?= e($cita['staff_nombre']) ?>"
                                             data-servicio="<?= e($cita['nombre_servicio']) ?>"
                                             data-estatus="<?= e($cita['estatus_cita']) ?>"
                                             data-hora="<?= e(substr((string) $cita['hora_inicio'], 0, 5)) ?>"
                                             style="background-color: <?= e($coloresStaff[$cita['id_staff']] ?? '#0E3A5D') ?>"
                                             title="<?= e(($cita['atleta_nombre'] ?? 'Prospecto') . ' — ' . $cita['staff_nombre']) ?>">
                                            <?= e($cita['atleta_nombre'] ?? ($cita['notas_previas'] ?: 'Prospecto')) ?>
                                        </div>
                                    <?php endforeach; ?>
                                </td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <aside class="ssos-agenda-sidebar ssos-agenda-sidebar--der">
        <h6 class="mb-2">Coaches</h6>
        <?php foreach ($staffList as $s): ?>
            <label class="ssos-agenda-coach-item">
                <input type="checkbox" checked data-ssos-agenda-toggle-staff="<?= (int) $s['id_staff'] ?>">
                <span class="ssos-agenda-coach-color" style="background-color: <?= e($coloresStaff[$s['id_staff']] ?? '#0E3A5D') ?>"></span>
                <span class="flex-grow-1"><?= e($s['nombre_completo']) ?></span>
                <span class="ssos-agenda-coach-contador" title="Citas activas esta semana"><?= $citasPorStaffSemana[$s['id_staff']] ?? 0 ?></span>
            </label>
        <?php endforeach; ?>
        <?php if (empty($staffList)): ?>
            <p class="text-body-secondary small">Sin coaches activos.</p>
        <?php endif; ?>
    </aside>
</div>

<!-- ══════════ VISTA MÓVIL: un solo día, 100dvh ══════════ -->
<div class="ssos-agenda-movil d-lg-none">
    <div class="ssos-agenda-movil-nav">
        <a href="?fecha=<?= e((new DateTimeImmutable($fecha))->modify('-1 day')->format('Y-m-d')) ?>" class="btn btn-sm btn-ssos-outline">◀</a>
        <span class="fw-bold"><?= e((new DateTimeImmutable($fecha))->format('l d/m')) ?></span>
        <a href="?fecha=<?= e((new DateTimeImmutable($fecha))->modify('+1 day')->format('Y-m-d')) ?>" class="btn btn-sm btn-ssos-outline">▶</a>
    </div>
    <div class="ssos-agenda-movil-lista">
        <?php
            $diaIsoMovil = (int) (new DateTimeImmutable($fecha))->format('N');
        ?>
        <?php foreach ($horasMatriz as $hora): ?>
            <?php if (!AgendaBusinessRules::franjaEsOperativa($diaIsoMovil, $hora)) continue; ?>
            <?php $citasHora = $citasDelDiaMovil[$hora] ?? []; ?>
            <div class="ssos-agenda-movil-franja">
                <div class="ssos-agenda-movil-hora"><?= e($hora) ?></div>
                <div class="ssos-agenda-movil-citas">
                    <?php if (empty($citasHora)): ?>
                        <span class="text-body-secondary small">Disponible</span>
                    <?php endif; ?>
                    <?php foreach ($citasHora as $cita): ?>
                        <div class="ssos-agenda-movil-cita" style="border-left-color: <?= e($coloresStaff[$cita['id_staff']] ?? '#0E3A5D') ?>">
                            <div class="d-flex justify-content-between">
                                <strong><?= e($cita['atleta_nombre'] ?? ($cita['notas_previas'] ?: 'Prospecto')) ?></strong>
                                <span class="badge text-bg-<?= $etiquetasEstatus[$cita['estatus_cita']]['badge'] ?? 'secondary' ?>"><?= e($etiquetasEstatus[$cita['estatus_cita']]['label'] ?? $cita['estatus_cita']) ?></span>
                            </div>
                            <div class="text-body-secondary small"><?= e($cita['staff_nombre']) ?> · <?= e($cita['nombre_servicio']) ?></div>
                            <?php if (in_array($cita['estatus_cita'], ['reservada', 'confirmada'], true)): ?>
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    <?php if ($cita['estatus_cita'] === 'reservada'): ?>
                                        <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="accion" value="cambiar_estatus_cita"><input type="hidden" name="id_cita" value="<?= (int) $cita['id_cita'] ?>"><input type="hidden" name="nuevo_estatus" value="confirmada"><button type="submit" class="btn btn-sm btn-outline-secondary">Confirmar</button></form>
                                    <?php endif; ?>
                                    <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="accion" value="cambiar_estatus_cita"><input type="hidden" name="id_cita" value="<?= (int) $cita['id_cita'] ?>"><input type="hidden" name="nuevo_estatus" value="completada"><button type="submit" class="btn btn-sm btn-ssos-turquesa">Completar</button></form>
                                    <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="accion" value="cambiar_estatus_cita"><input type="hidden" name="id_cita" value="<?= (int) $cita['id_cita'] ?>"><input type="hidden" name="nuevo_estatus" value="no_show"><button type="submit" class="btn btn-sm btn-outline-secondary">No-Show</button></form>
                                    <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="accion" value="cambiar_estatus_cita"><input type="hidden" name="id_cita" value="<?= (int) $cita['id_cita'] ?>"><input type="hidden" name="nuevo_estatus" value="cancelada"><button type="submit" class="btn btn-sm btn-outline-danger">Cancelar</button></form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ══════════ MODAL: Nueva Cita ══════════ -->
<div class="modal fade" id="modalNuevaCita" tabindex="-1" aria-labelledby="modalNuevaCitaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="accion" value="crear_cita">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevaCitaLabel">Nueva Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Atleta existente</label>
                    <select name="id_atleta" class="form-select">
                        <option value="">— Seleccionar —</option>
                        <?php foreach ($atletasActivos as $a): ?>
                            <option value="<?= (int) $a['id_atleta'] ?>"><?= e($a['nombre_completo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">O prospecto nuevo (sin ficha aún)</label>
                    <input type="text" name="nombre_prospecto" class="form-control" placeholder="Nombre del prospecto">
                </div>
                <div class="mb-3">
                    <label class="form-label">Especialista</label>
                    <select name="id_staff" id="ssosNuevaCitaStaff" class="form-select" required>
                        <option value="">—</option>
                        <?php foreach ($staffList as $s): ?>
                            <option value="<?= (int) $s['id_staff'] ?>"><?= e($s['nombre_completo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Servicio</label>
                    <select name="id_servicio" class="form-select" required>
                        <option value="">—</option>
                        <?php foreach ($servicios as $s): ?>
                            <option value="<?= (int) $s['id_servicio'] ?>"><?= e($s['nombre_servicio']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row">
                    <div class="col-sm-6 mb-3">
                        <label class="form-label">Fecha</label>
                        <input type="date" name="fecha_cita" id="ssosNuevaCitaFecha" class="form-control" value="<?= e($fecha) ?>" required>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <label class="form-label">Hora</label>
                        <select name="hora_inicio" id="ssosNuevaCitaHora" class="form-select" required>
                            <?php foreach ($horasMatriz as $hora): ?>
                                <option value="<?= $hora ?>"><?= $hora ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-ssos-turquesa">Agendar</button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════ MODAL: Detalle de Cita (click en un bloque de la matriz) ══════════ -->
<div class="modal fade" id="modalDetalleCita" tabindex="-1" aria-labelledby="modalDetalleCitaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetalleCitaLabel">Detalle de la cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1"><strong id="ssosDetalleCitaNombre"></strong></p>
                <p class="text-body-secondary mb-1" id="ssosDetalleCitaInfo"></p>
                <p class="mb-0"><span class="badge text-bg-secondary" id="ssosDetalleCitaEstatus"></span></p>
            </div>
            <div class="modal-footer flex-wrap gap-1" id="ssosDetalleCitaAcciones">
                <!-- Botones inyectados por JS según el estatus actual (mismo patrón que el modal Editar Atleta) -->
            </div>
        </div>
    </div>
</div>

<!-- Formulario oculto reutilizado por los botones inyectados del modal de detalle -->
<form method="post" id="ssosFormEstatusCita" class="d-none">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="accion" value="cambiar_estatus_cita">
    <input type="hidden" name="id_cita" id="ssosFormEstatusCitaId">
    <input type="hidden" name="nuevo_estatus" id="ssosFormEstatusCitaNuevo">
</form>
