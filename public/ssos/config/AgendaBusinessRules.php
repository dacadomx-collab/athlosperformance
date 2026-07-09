<?php
declare(strict_types=1);

/**
 * ATHLOS SSOS v1.0 — REGLAS DE NEGOCIO DE LA AGENDA / CALENDARIO
 *
 * Días operativos, franjas horarias, semáforo de cupo y color por coach.
 * Ver arquitectura genérica y agnóstica de este módulo en
 * knowledge/MODULO_CALENDARIO_GENERICO.md — esta clase es la adaptación
 * concreta a los nombres reales de entidad de este proyecto (`staff`).
 *
 * `colorParaStaff()` NUNCA depende de que exista la tabla `staff_colores`
 * (migración 06_schema_calendario_avanzado.sql, todavía no aplicada a la BD
 * real al momento de escribir esto) — si la consulta falla porque la tabla
 * no existe, cae automáticamente a una asignación determinística por
 * paleta fija (id_staff % N). Así la matriz de la agenda funciona hoy
 * mismo con o sin la migración; en cuanto se aplique, empieza a usar
 * colores persistidos/personalizables sin cambiar una sola línea de este
 * archivo.
 */
final class AgendaBusinessRules
{
    public const CUPO_MAXIMO_FRANJA = 4;

    /** Paleta fija de alto contraste (AA sobre fondo claro y oscuro) — evita colisiones entre coaches. */
    private const PALETA_COLORES = [
        '#0E3A5D', '#00B8C9', '#C0392B', '#8E44AD',
        '#D68910', '#16A085', '#2C3E50', '#B03A2E',
    ];

    /** @return array<int, array{label: string, apertura: string, cierre: string}> Lunes(1) a Sábado(6) — domingo(7) deliberadamente ausente. */
    public static function diasOperativos(): array
    {
        return [
            1 => ['label' => 'Lunes', 'apertura' => '06:00', 'cierre' => '22:00'],
            2 => ['label' => 'Martes', 'apertura' => '06:00', 'cierre' => '22:00'],
            3 => ['label' => 'Miércoles', 'apertura' => '06:00', 'cierre' => '22:00'],
            4 => ['label' => 'Jueves', 'apertura' => '06:00', 'cierre' => '22:00'],
            5 => ['label' => 'Viernes', 'apertura' => '06:00', 'cierre' => '22:00'],
            6 => ['label' => 'Sábado', 'apertura' => '07:00', 'cierre' => '15:00'],
        ];
    }

    /**
     * Unión de franjas de 1h de todos los días operativos (06:00-21:00 dado
     * el horario actual) — cada día luego marca como no-operativas las
     * franjas fuera de su propio rango, sin cambiar el alto de la matriz.
     *
     * @return array<int, string>
     */
    public static function horasMatriz(): array
    {
        $dias = self::diasOperativos();
        $minApertura = min(array_column($dias, 'apertura'));
        $maxCierre = max(array_column($dias, 'cierre'));

        $horas = [];
        $actual = strtotime($minApertura);
        $limite = strtotime($maxCierre);
        while ($actual < $limite) {
            $horas[] = date('H:i', $actual);
            $actual += 3600;
        }
        return $horas;
    }

    public static function franjaEsOperativa(int $diaSemanaIso, string $hora): bool
    {
        $dias = self::diasOperativos();
        if (!isset($dias[$diaSemanaIso])) {
            return false;
        }
        return $hora >= $dias[$diaSemanaIso]['apertura'] && $hora < $dias[$diaSemanaIso]['cierre'];
    }

    /** Verde = espacio disponible, Amarillo = último lugar, Rojo = franja llena/bloqueada. */
    public static function semaforoFranja(int $ocupadas): string
    {
        if ($ocupadas >= self::CUPO_MAXIMO_FRANJA) {
            return 'rojo';
        }
        if ($ocupadas === self::CUPO_MAXIMO_FRANJA - 1) {
            return 'amarillo';
        }
        return 'verde';
    }

    public static function colorParaStaff(PDO $db, int $idStaff): string
    {
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
            $colores[$id] = self::colorParaStaff($db, $id);
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
