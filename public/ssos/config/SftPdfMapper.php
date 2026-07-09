<?php
declare(strict_types=1);

/**
 * ATHLOS SSOS v1.0 — MAPEADOR DE PDF DE "FICHA EVALUACIÓN ADULTO MAYOR" A sft_form.php
 *
 * Igual que HistorialPdfMapper: regex sobre texto libre, nunca se guarda
 * directo en BD — el resultado se entrega como prefill editable en
 * sft_form.php y el coach confirma con "Guardar y Calcular Semáforo".
 *
 * Particularidad de esta plantilla: Chair Sit-&-Reach y Back Scratch se
 * registran por lado (izquierda/derecha) y Time Up-&-Go trae dos valores,
 * pero sft_form.php sólo tiene UN campo numérico por prueba (no hay columna
 * "lado" en evaluaciones_sft). Elegir un lado automáticamente sería inventar
 * un dato clínico que alimenta el semáforo — en vez de eso, esos 3 valores se
 * regresan en 'informativos' (sólo texto, para que el coach decida y
 * capture el número correcto a mano) y NO se prellenan en el formulario.
 */
final class SftPdfMapper
{
    /**
     * @return array{campos: array<string, string|float|null>, demograficos: array<string, string>, informativos: array<string, string>}
     */
    public static function mapear(string $texto): array
    {
        $texto = preg_replace('/\s+/', ' ', $texto) ?? $texto;

        $campos = [
            'chair_stand_reps' => self::numeroTrasEtiqueta($texto, '/Chair Stand\s+/'),
            'arm_curl_reps' => self::numeroTrasEtiqueta($texto, '/Arm Curl\s+/'),
            'two_min_step_pasos' => self::numeroTrasEtiqueta($texto, '/2-Min Step\s+/'),
            'functional_reach_cm' => self::numeroTrasEtiqueta($texto, '/Functional reach\s+/'),
            'time_up_go_cognitivo_seg' => self::numeroTrasEtiqueta($texto, '/Time Up-&-Go Cognitivo\s+/'),
            'observaciones' => self::entre($texto, '/Observaciones:\s*/', '/Chair Sit-&-Reach/'),
        ];

        foreach ($campos as $clave => $valor) {
            if (is_string($valor)) {
                $valor = trim($valor, " \t\n\r\0\x0B_-.");
                $campos[$clave] = $valor !== '' ? $valor : null;
            }
        }

        // Valores con lado/duplicado — no se asignan a un campo único del
        // formulario, sólo se muestran para que el coach capture a mano.
        $informativos = array_filter([
            'Chair Sit-&-Reach (izq / der)' => self::extraerParIzqDer($texto, '/Chair Sit-&-Reach\s+/', 'izquierda', 'derecha'),
            'Back Scratch (der / izq)' => self::extraerParIzqDer($texto, '/Back Scratch\s+/', 'derecha', 'izquierda'),
            'Time Up-&-Go (valores detectados)' => self::extraerNumerosTrasEtiqueta($texto, '/Time Up-&-Go\s+/', '/Time Up-&-Go Cognitivo/'),
        ]);

        $demograficos = array_filter([
            'nombre' => self::valorEtiqueta($texto, '/Nombre:\s*_*\s*/', '/_{3,}|Edad|$/'),
            'edad' => self::valorEtiqueta($texto, '/Edad:_*\s*/', '/_{2,}|Fecha|$/'),
            'fecha' => self::valorEtiqueta($texto, '/Fecha:_*\s*/', '/_{2,}|$/'),
        ]);

        return ['campos' => $campos, 'demograficos' => $demograficos, 'informativos' => $informativos];
    }

    private static function entre(string $texto, string $inicioRegex, string $finRegex): ?string
    {
        if (!preg_match($inicioRegex, $texto, $mi, PREG_OFFSET_CAPTURE)) {
            return null;
        }
        $inicio = $mi[0][1] + strlen($mi[0][0]);
        $resto = substr($texto, $inicio);
        if (preg_match($finRegex, $resto, $mf, PREG_OFFSET_CAPTURE)) {
            return substr($resto, 0, $mf[0][1]);
        }
        return $resto;
    }

    private static function numeroTrasEtiqueta(string $texto, string $etiquetaRegex): ?float
    {
        if (!preg_match($etiquetaRegex, $texto, $m, PREG_OFFSET_CAPTURE)) {
            return null;
        }
        $resto = substr($texto, $m[0][1] + strlen($m[0][0]));
        if (preg_match('/\s*(\d+(?:\.\d+)?)/', $resto, $mn)) {
            return (float) $mn[1];
        }
        return null;
    }

    /**
     * Extrae los valores de izquierda/derecha sin asumir un orden fijo — la
     * plantilla no es consistente ("14 izquierda" en Sit-&-Reach vs.
     * "derecha arriba 21" en Back Scratch).
     */
    private static function extraerParIzqDer(string $texto, string $etiquetaRegex, string $ladoA, string $ladoB): ?string
    {
        if (!preg_match($etiquetaRegex, $texto, $m, PREG_OFFSET_CAPTURE)) {
            return null;
        }
        $resto = substr($texto, $m[0][1] + strlen($m[0][0]), 80);

        // Recorre "número lado" y "lado número" en el orden real en que
        // aparecen en el texto, para no cruzar el número de un lado con la
        // etiqueta del otro (ej. "derecha arriba 21, izquierda arriba 33":
        // buscar "izquierda" con \D{0,15} hacia atrás agarraría el 21 ajeno).
        preg_match_all(
            '/(-?\d+(?:\.\d+)?)\s*(izquierda|derecha)|(izquierda|derecha)\D{0,15}?(-?\d+(?:\.\d+)?)/i',
            $resto,
            $coincidencias,
            PREG_SET_ORDER
        );

        $valores = [];
        foreach ($coincidencias as $c) {
            if ($c[2] !== '' && $c[2] !== null) {
                $valores[strtolower($c[2])] ??= $c[1];
            } elseif (($c[3] ?? '') !== '') {
                $valores[strtolower($c[3])] ??= $c[4];
            }
        }

        if (!isset($valores[$ladoA]) && !isset($valores[$ladoB])) {
            return null;
        }

        return "{$ladoA}: " . ($valores[$ladoA] ?? '—') . " cm / {$ladoB}: " . ($valores[$ladoB] ?? '—') . ' cm';
    }

    private static function extraerNumerosTrasEtiqueta(string $texto, string $etiquetaRegex, string $finRegex): ?string
    {
        $segmento = self::entre($texto, $etiquetaRegex, $finRegex);
        if ($segmento === null) {
            return null;
        }
        if (preg_match_all('/\d+(?:\.\d+)?/', $segmento, $mn)) {
            return implode(' / ', $mn[0]);
        }
        return null;
    }

    private static function valorEtiqueta(string $texto, string $etiquetaRegex, string $finRegex): ?string
    {
        $valor = self::entre($texto, $etiquetaRegex, $finRegex);
        if ($valor === null) {
            return null;
        }
        $valor = trim($valor, " \t\n\r\0\x0B_-.:");
        return $valor !== '' ? $valor : null;
    }
}
