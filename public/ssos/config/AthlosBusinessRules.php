<?php
declare(strict_types=1);

/**
 * ATHLOS SSOS v1.0 — MOTOR DE REGLAS DE NEGOCIO
 *
 * Fase 5: deducción automática de sesiones de membresía + cálculo del
 * "Athlos Score™" (índice compuesto 0-100 para el reporte ejecutivo / radar).
 */
final class AthlosBusinessRules
{
    private const UMBRAL_AMARILLO = 2;
    private const UMBRAL_ROJO = 0;

    /**
     * Descuenta 1 sesión de la membresía activa más antigua del atleta (FIFO).
     * Se dispara cada vez que un Coach guarda una evaluación/sesión desde
     * Pie de Cancha. Si el conteo cruza los umbrales de alerta, registra (o
     * refresca) la fila correspondiente en `alertas_renovacion`.
     *
     * No lanza excepción si el atleta no tiene membresía activa con saldo —
     * eso es un estado de negocio válido (sesión suelta / sin paquete), no un
     * error técnico; se refleja en el campo `deducted` de la respuesta.
     *
     * @return array{deducted: bool, reason?: string, id_membresia?: int, sesiones_restantes?: int, alerta?: string|null}
     */
    public static function deducirSesionAtleta(PDO $db, int $id_atleta): array
    {
        $db->beginTransaction();

        try {
            $stmt = $db->prepare(
                'SELECT id_membresia, sesiones_restantes FROM membresias
                 WHERE id_atleta = :id_atleta AND estatus = \'activa\' AND sesiones_restantes > 0
                 ORDER BY fecha_inicio ASC
                 LIMIT 1
                 FOR UPDATE'
            );
            $stmt->execute(['id_atleta' => $id_atleta]);
            $membresia = $stmt->fetch();

            if (!$membresia) {
                $db->commit();
                return ['deducted' => false, 'reason' => 'sin_membresia_activa_con_saldo'];
            }

            $id_membresia = (int) $membresia['id_membresia'];
            $nuevas_restantes = (int) $membresia['sesiones_restantes'] - 1;
            $nuevo_estatus = $nuevas_restantes <= self::UMBRAL_ROJO ? 'agotada' : 'activa';

            $stmt = $db->prepare(
                'UPDATE membresias SET sesiones_restantes = :restantes, estatus = :estatus
                 WHERE id_membresia = :id'
            );
            $stmt->execute([
                'restantes' => max($nuevas_restantes, 0),
                'estatus'   => $nuevo_estatus,
                'id'        => $id_membresia,
            ]);

            $alerta = null;
            if ($nuevas_restantes <= self::UMBRAL_ROJO) {
                $alerta = 'rojo';
            } elseif ($nuevas_restantes === self::UMBRAL_AMARILLO) {
                $alerta = 'amarillo';
            }

            if ($alerta !== null) {
                $stmt = $db->prepare(
                    'INSERT INTO alertas_renovacion (id_atleta, id_membresia, tipo_alerta, sesiones_restantes_momento)
                     VALUES (:id_atleta, :id_membresia, :tipo, :restantes)
                     ON DUPLICATE KEY UPDATE
                        sesiones_restantes_momento = VALUES(sesiones_restantes_momento),
                        atendida = 0,
                        atendida_por = NULL,
                        fecha_atendida = NULL'
                );
                $stmt->execute([
                    'id_atleta'    => $id_atleta,
                    'id_membresia' => $id_membresia,
                    'tipo'         => $alerta,
                    'restantes'    => max($nuevas_restantes, 0),
                ]);
            }

            $db->commit();

            return [
                'deducted' => true,
                'id_membresia' => $id_membresia,
                'sesiones_restantes' => max($nuevas_restantes, 0),
                'alerta' => $alerta,
            ];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[AthlosBusinessRules::deducirSesionAtleta] ' . $e->getMessage());
            return ['deducted' => false, 'reason' => 'error_interno'];
        }
    }

    /**
     * Calcula el Athlos Score™: índice 0-100 ponderado (30% Fuerza/SFT,
     * 30% Movilidad/Compensaciones, 40% Composición/Grasa) a partir de la
     * evaluación más reciente de cada dimensión. Dimensiones sin datos se
     * excluyen y el peso se renormaliza entre las dimensiones disponibles
     * (REGLA-05: nunca interpretar sin contexto, pero tampoco bloquear el
     * reporte si aún falta una evaluación).
     *
     * @return array{id_atleta:int, athlos_score: float|null, dimensiones: array, radar: array}
     */
    public static function generarAthlosScore(PDO $db, int $id_atleta): array
    {
        $dimensiones = [
            'fuerza' => self::scoreFuerza($db, $id_atleta),
            'movilidad' => self::scoreMovilidad($db, $id_atleta),
            'composicion' => self::scoreComposicion($db, $id_atleta),
        ];

        $pesos = ['fuerza' => 0.30, 'movilidad' => 0.30, 'composicion' => 0.40];

        $peso_disponible = 0.0;
        $suma_ponderada = 0.0;
        foreach ($dimensiones as $clave => $dim) {
            if ($dim['score'] !== null) {
                $peso_disponible += $pesos[$clave];
                $suma_ponderada += $dim['score'] * $pesos[$clave];
            }
        }

        $athlos_score = $peso_disponible > 0 ? round($suma_ponderada / $peso_disponible, 1) : null;

        return [
            'id_atleta' => $id_atleta,
            'athlos_score' => $athlos_score,
            'dimensiones' => $dimensiones,
            'radar' => [
                'labels' => ['Fuerza', 'Movilidad', 'Composición'],
                'valores' => [
                    $dimensiones['fuerza']['score'],
                    $dimensiones['movilidad']['score'],
                    $dimensiones['composicion']['score'],
                ],
            ],
        ];
    }

    /** 30% — última evaluaciones_sft.semaforo_general. */
    private static function scoreFuerza(PDO $db, int $id_atleta): array
    {
        $stmt = $db->prepare(
            'SELECT semaforo_general, fecha_evaluacion FROM evaluaciones_sft
             WHERE id_atleta = :id ORDER BY fecha_evaluacion DESC LIMIT 1'
        );
        $stmt->execute(['id' => $id_atleta]);
        $fila = $stmt->fetch();

        if (!$fila || $fila['semaforo_general'] === null) {
            return ['score' => null, 'fuente' => 'evaluaciones_sft', 'fecha' => null];
        }

        $mapa = ['verde' => 100.0, 'amarillo' => 60.0, 'rojo' => 20.0];

        return [
            'score' => $mapa[$fila['semaforo_general']] ?? null,
            'fuente' => 'evaluaciones_sft',
            'fecha' => $fila['fecha_evaluacion'],
        ];
    }

    /** 30% — última evaluaciones_biomecanica: (8 - compensaciones detectadas) / 8 * 100. */
    private static function scoreMovilidad(PDO $db, int $id_atleta): array
    {
        $columnas = [
            'feet_flatten', 'feet_turn_out', 'heel_rises', 'knees_move_inward',
            'excessive_forward_lean', 'lower_back_arches', 'lower_back_rounds', 'arms_fall_forward',
        ];

        $stmt = $db->prepare(
            'SELECT ' . implode(', ', $columnas) . ', fecha_evaluacion FROM evaluaciones_biomecanica
             WHERE id_atleta = :id ORDER BY fecha_evaluacion DESC LIMIT 1'
        );
        $stmt->execute(['id' => $id_atleta]);
        $fila = $stmt->fetch();

        if (!$fila) {
            return ['score' => null, 'fuente' => 'evaluaciones_biomecanica', 'fecha' => null];
        }

        $compensaciones = 0;
        foreach ($columnas as $col) {
            $compensaciones += (int) $fila[$col];
        }

        return [
            'score' => round((count($columnas) - $compensaciones) / count($columnas) * 100, 1),
            'fuente' => 'evaluaciones_biomecanica',
            'fecha' => $fila['fecha_evaluacion'],
        ];
    }

    /** 40% — última evaluaciones_antropometria.clasificacion_grasa. */
    private static function scoreComposicion(PDO $db, int $id_atleta): array
    {
        $stmt = $db->prepare(
            'SELECT clasificacion_grasa, fecha_antropometria FROM evaluaciones_antropometria
             WHERE id_atleta = :id ORDER BY fecha_antropometria DESC LIMIT 1'
        );
        $stmt->execute(['id' => $id_atleta]);
        $fila = $stmt->fetch();

        if (!$fila || $fila['clasificacion_grasa'] === null) {
            return ['score' => null, 'fuente' => 'evaluaciones_antropometria', 'fecha' => null];
        }

        $mapa = [
            'grasa_esencial' => 90.0,
            'atletas' => 100.0,
            'fitness' => 90.0,
            'aceptable' => 75.0,
            'sobregraso_moderado' => 55.0,
            'sobregraso_riesgo' => 40.0,
            'obeso' => 25.0,
            'obeso_riesgo' => 15.0,
            'obeso_morbido' => 5.0,
        ];

        return [
            'score' => $mapa[$fila['clasificacion_grasa']] ?? null,
            'fuente' => 'evaluaciones_antropometria',
            'fecha' => $fila['fecha_antropometria'],
        ];
    }
}
