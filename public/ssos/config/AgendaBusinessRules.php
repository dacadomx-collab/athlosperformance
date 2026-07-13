<?php
declare(strict_types=1);

/**
 * ATHLOS SSOS v1.0 — REGLAS DE NEGOCIO DE LA AGENDA / CALENDARIO
 *
 * Días operativos, horarios, aforo, bloqueos y color por coach. Ver
 * arquitectura genérica y agnóstica de este módulo en
 * knowledge/MODULO_CALENDARIO_GENERICO.md — esta clase es la adaptación
 * concreta a los nombres reales de entidad de este proyecto (`staff`).
 *
 * REGLA DE DISEÑO — "config dinámica con fallback determinístico, nunca un
 * error fatal": `diasOperativos()`, `cupoMaximoFranja()` y
 * `colorParaStaff()` intentan leer su configuración real de la BD
 * (tablas `agenda_disponibilidad`, `agenda_configuracion`, `staff_colores`
 * — migración 07_schema_configuracion_agenda_publica.sql) y, si la tabla
 * no existe todavía o está vacía, caen a una constante hardcodeada
 * equivalente al comportamiento pre-configuración-dinámica (Fase 20-22).
 * Así el módulo nunca se rompe por una migración pendiente — sólo pierde
 * la capacidad de personalizarse hasta que se aplique.
 *
 * Los métodos que antes eran "casi puros" (`horasMatriz`, `franjaEsOperativa`,
 * `semaforoFranja`) ahora reciben `$diasOperativos`/`$cupoMaximo` ya resueltos
 * como parámetro, en vez de volver a consultar la BD en cada llamada — el
 * caller (`agenda_logica.php`) los resuelve UNA vez por request y los pasa.
 */
final class AgendaBusinessRules
{
    private const CUPO_MAXIMO_FRANJA_DEFAULT = 4;

    /** @var array<int, array{label: string, apertura: string, cierre: string}> Lunes(1) a Sábado(6) — domingo(7) deliberadamente ausente. */
    private const DIAS_OPERATIVOS_DEFAULT = [
        1 => ['label' => 'Lunes', 'apertura' => '06:00', 'cierre' => '22:00'],
        2 => ['label' => 'Martes', 'apertura' => '06:00', 'cierre' => '22:00'],
        3 => ['label' => 'Miércoles', 'apertura' => '06:00', 'cierre' => '22:00'],
        4 => ['label' => 'Jueves', 'apertura' => '06:00', 'cierre' => '22:00'],
        5 => ['label' => 'Viernes', 'apertura' => '06:00', 'cierre' => '22:00'],
        6 => ['label' => 'Sábado', 'apertura' => '07:00', 'cierre' => '15:00'],
    ];

    private const DIAS_LABEL = [
        1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo',
    ];

    private const MESES_ES = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio',
        7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    private const MESES_ES_CORTO = [
        1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
        7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
    ];

    /** Paleta fija de alto contraste (AA sobre fondo claro y oscuro) — evita colisiones entre coaches. */
    private const PALETA_COLORES = [
        '#0E3A5D', '#00B8C9', '#C0392B', '#8E44AD',
        '#D68910', '#16A085', '#2C3E50', '#B03A2E',
    ];

    public static function mesEnEspanol(DateTimeInterface $fecha): string
    {
        return self::MESES_ES[(int) $fecha->format('n')];
    }

    public static function mesEnEspanolCorto(DateTimeInterface $fecha): string
    {
        return self::MESES_ES_CORTO[(int) $fecha->format('n')];
    }

    /**
     * Título dinámico del encabezado de la matriz: "Semana del 13 al 18 ·
     * Julio 2026" (mismo mes) o "Semana del 29 Jun al 4 Jul · Junio – Julio
     * 2026" (cruza de mes) — se ajusta solo al navegar semana a semana.
     */
    public static function tituloSemana(DateTimeImmutable $lunes, DateTimeImmutable $sabadoVisible): string
    {
        $mismoMes = $lunes->format('Y-m') === $sabadoVisible->format('Y-m');

        if ($mismoMes) {
            $rango = "{$lunes->format('j')} al {$sabadoVisible->format('j')}";
            $mesAnio = ucfirst(self::mesEnEspanol($lunes)) . ' ' . $lunes->format('Y');
        } else {
            $rango = "{$lunes->format('j')} " . self::mesEnEspanolCorto($lunes)
                . " al {$sabadoVisible->format('j')} " . self::mesEnEspanolCorto($sabadoVisible);
            $mesAnio = $lunes->format('Y') === $sabadoVisible->format('Y')
                ? ucfirst(self::mesEnEspanol($lunes)) . ' – ' . ucfirst(self::mesEnEspanol($sabadoVisible)) . ' ' . $sabadoVisible->format('Y')
                : ucfirst(self::mesEnEspanol($lunes)) . ' ' . $lunes->format('Y') . ' – ' . ucfirst(self::mesEnEspanol($sabadoVisible)) . ' ' . $sabadoVisible->format('Y');
        }

        return "Semana del {$rango} · {$mesAnio}";
    }

    /**
     * Días operativos reales — lee `agenda_disponibilidad` (Panel de
     * Configuración, Fase 24); si la tabla no existe o está vacía, usa el
     * horario por defecto Lun-Vie 06:00-22:00 / Sáb 07:00-15:00.
     *
     * @return array<int, array{label: string, apertura: string, cierre: string}>
     */
    public static function diasOperativos(PDO $db): array
    {
        try {
            $filas = $db->query('SELECT dia_semana, hora_apertura, hora_cierre FROM agenda_disponibilidad WHERE activo = 1 ORDER BY dia_semana')->fetchAll();
            if ($filas) {
                $out = [];
                foreach ($filas as $f) {
                    $dia = (int) $f['dia_semana'];
                    $out[$dia] = [
                        'label' => self::DIAS_LABEL[$dia] ?? "Día {$dia}",
                        'apertura' => substr((string) $f['hora_apertura'], 0, 5),
                        'cierre' => substr((string) $f['hora_cierre'], 0, 5),
                    ];
                }
                ksort($out);
                return $out;
            }
        } catch (\Throwable $e) {
            // Tabla agenda_disponibilidad todavía no existe (migración 07_ pendiente) — cae al horario por defecto.
        }

        return self::DIAS_OPERATIVOS_DEFAULT;
    }

    public static function cupoMaximoFranja(PDO $db): int
    {
        try {
            $stmt = $db->query("SELECT valor FROM agenda_configuracion WHERE clave = 'cupo_maximo_franja'");
            $valor = $stmt->fetchColumn();
            if ($valor !== false && (int) $valor > 0) {
                return (int) $valor;
            }
        } catch (\Throwable $e) {
            // Tabla agenda_configuracion todavía no existe — cae al valor por defecto.
        }

        return self::CUPO_MAXIMO_FRANJA_DEFAULT;
    }

    /**
     * Unión de franjas de 1h de todos los días operativos — cada día luego
     * marca como no-operativas las franjas fuera de su propio rango, sin
     * cambiar el alto de la matriz.
     *
     * @param array<int, array{label: string, apertura: string, cierre: string}> $diasOperativos
     * @return array<int, string>
     */
    public static function horasMatriz(array $diasOperativos): array
    {
        if (empty($diasOperativos)) {
            return [];
        }
        $minApertura = min(array_column($diasOperativos, 'apertura'));
        $maxCierre = max(array_column($diasOperativos, 'cierre'));

        $horas = [];
        $actual = strtotime($minApertura);
        $limite = strtotime($maxCierre);
        while ($actual < $limite) {
            $horas[] = date('H:i', $actual);
            $actual += 3600;
        }
        return $horas;
    }

    /** @param array<int, array{label: string, apertura: string, cierre: string}> $diasOperativos */
    public static function franjaEsOperativa(array $diasOperativos, int $diaSemanaIso, string $hora): bool
    {
        if (!isset($diasOperativos[$diaSemanaIso])) {
            return false;
        }
        return $hora >= $diasOperativos[$diaSemanaIso]['apertura'] && $hora < $diasOperativos[$diaSemanaIso]['cierre'];
    }

    /** Verde = espacio disponible, Amarillo = último lugar, Rojo = franja llena/bloqueada. */
    public static function semaforoFranja(int $ocupadas, int $cupoMaximo): string
    {
        if ($ocupadas >= $cupoMaximo) {
            return 'rojo';
        }
        if ($ocupadas === $cupoMaximo - 1) {
            return 'amarillo';
        }
        return 'verde';
    }

    /**
     * Bloqueos (vacaciones/festivos/inasistencias, Fase 24) que se solapan
     * con el rango de fechas visible — se resuelven UNA vez por request en
     * agenda_logica.php y se cruzan contra cada franja con `franjaBloqueada()`.
     *
     * @return array<int, array{id_staff: ?int, fecha_inicio: string, fecha_fin: string, motivo: ?string}>
     */
    public static function bloqueosEnRango(PDO $db, string $fechaInicio, string $fechaFin): array
    {
        try {
            $stmt = $db->prepare(
                'SELECT id_staff, fecha_inicio, fecha_fin, motivo FROM agenda_bloqueos
                 WHERE fecha_inicio <= :fin AND fecha_fin >= :inicio'
            );
            $stmt->execute(['inicio' => $fechaInicio . ' 00:00:00', 'fin' => $fechaFin . ' 23:59:59']);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            // Tabla agenda_bloqueos todavía no existe — sin bloqueos que aplicar.
            return [];
        }
    }

    /**
     * @param array<int, array{id_staff: ?int, fecha_inicio: string, fecha_fin: string, motivo: ?string}> $bloqueos
     */
    public static function franjaBloqueada(array $bloqueos, string $fecha, string $hora, ?int $idStaff): ?string
    {
        $momento = strtotime("{$fecha} {$hora}");
        foreach ($bloqueos as $b) {
            if ($b['id_staff'] !== null && (int) $b['id_staff'] !== $idStaff) {
                continue; // bloqueo específico de OTRO coach, no aplica a esta franja/coach
            }
            if ($momento >= strtotime((string) $b['fecha_inicio']) && $momento <= strtotime((string) $b['fecha_fin'])) {
                return $b['motivo'] ?: 'Bloqueado';
            }
        }
        return null;
    }

    public static function colorParaStaff(PDO $db, int|string $idStaff): string
    {
        $idStaff = (int) $idStaff;

        try {
            $stmt = $db->prepare('SELECT color_hex FROM staff_colores WHERE id_staff = :id');
            $stmt->execute(['id' => $idStaff]);
            $color = $stmt->fetchColumn();
            if ($color) {
                return (string) $color;
            }

            // Sin fila todavía: se siembra el color determinístico UNA vez, para
            // que a partir de ahora sí se esté leyendo realmente de la tabla
            // (y quede editable ahí sin volver a tocar código).
            $colorAsignado = self::PALETA_COLORES[$idStaff % count(self::PALETA_COLORES)];
            $db->prepare('INSERT INTO staff_colores (id_staff, color_hex) VALUES (:id, :color)')
                ->execute(['id' => $idStaff, 'color' => $colorAsignado]);
            return $colorAsignado;
        } catch (\Throwable $e) {
            // Tabla staff_colores todavía no existe (migración 06_ pendiente) — cae al fallback determinístico sin persistir.
        }

        return self::PALETA_COLORES[$idStaff % count(self::PALETA_COLORES)];
    }

    /** @return array<int, string> id_staff => color_hex, para precargar toda la lista de coaches de una sola vez. */
    public static function coloresParaStaffList(PDO $db, array $idsStaff): array
    {
        $colores = [];
        foreach ($idsStaff as $id) {
            $idStaff = (int) $id;
            $colores[$idStaff] = self::colorParaStaff($db, $idStaff);
        }
        return $colores;
    }

    /** Lunes (ISO) de la semana que contiene $fecha. */
    public static function lunesDeLaSemana(string $fecha): DateTimeImmutable
    {
        $dt = new DateTimeImmutable($fecha);
        $diaIso = (int) $dt->format('N'); // 1=lunes ... 7=domingo
        return $dt->modify('-' . ($diaIso - 1) . ' days');
    }
}
