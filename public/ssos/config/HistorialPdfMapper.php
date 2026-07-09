<?php
declare(strict_types=1);

/**
 * ATHLOS SSOS v1.0 — MAPEADOR DE PDF DE HISTORIAL CLÍNICO A historial_clinico
 *
 * Toma el texto plano ya extraído por PdfTextExtractor (formulario "Historial
 * clínico" / "Información del Cliente" de Athlos, exportado a PDF desde
 * Chrome/Android) y lo mapea por posición de las preguntas fijas de la
 * plantilla a los campos de historial_form.php.
 *
 * A diferencia de AntropometriaXlsxMapper (celdas numéricas de una hoja de
 * cálculo, muy confiables), esto es regex sobre texto libre — el resultado
 * SIEMPRE se entrega como prefill editable en historial_form.php, nunca se
 * guarda directo en la BD. El coach revisa y confirma cada campo antes de
 * "Guardar Historial Clínico".
 */
final class HistorialPdfMapper
{
    /**
     * @return array{campos: array<string, string|int|null>, demograficos: array<string, string>}
     */
    public static function mapear(string $texto): array
    {
        $texto = preg_replace('/\s+/', ' ', $texto) ?? $texto;

        $campos = [
            'actividades_ejercicio_actual' => self::entre($texto, '/etc\.\)\?\s*/', '/¿Cuántos días/'),
            'dias_ejercicio_moderado_semana' => self::numeroTrasEtiqueta($texto, '/intensidad moderada\?\s*/'),
            'objetivo_perdida_peso' => self::numeroTrasEtiqueta($texto, '/Pérdida de peso:\s*/'),
            'objetivo_masa_muscular' => self::numeroTrasEtiqueta($texto, '/Aumento masa muscular:\s*/'),
            'objetivo_rendimiento_deportivo' => self::numeroTrasEtiqueta($texto, '/Rendimiento deportivo:\s*/'),
            'objetivo_mejorar_salud' => self::numeroTrasEtiqueta($texto, '/Mejorar la salud:\s*/'),
            'dieta_saludable_score' => self::numeroTrasEtiqueta($texto, '/dieta general es saludable\?\s*/'),
            'sigue_dieta_actual' => self::entre($texto, '/por qué motivo\(s\)\?\s*/', '/¿Cómo clasificarías|¿Cuántas bebidas alcohólicas/'),
            'consumo_sal' => self::palabraTrasEtiqueta($texto, '/consumo diario de sal:[^?]*\?\s*/', ['bajo', 'medio', 'alto']),
            'consumo_azucar' => self::palabraTrasEtiqueta($texto, '/consumo diario de azúcar:[^?]*\?\s*/', ['bajo', 'medio', 'alto']),
            'consumo_grasas' => self::palabraTrasEtiqueta($texto, '/consumo diario de grasas:[^?]*\?\s*/', ['bajo', 'medio', 'alto']),
            'bebidas_alcoholicas_semana' => self::numeroTrasEtiqueta($texto, '/¿Cuántas bebidas alcohólicas consumes por semana\?\s*/'),
            'sueno_adecuado' => self::entre($texto, '/descansado\(a\) cada día\?\s*/', '/En una escala de 0 a 10, ¿cómo calificarías|¿Fumas tabaco/'),
            'fuma_o_vapea' => self::entre($texto, '/dispositivo de vapeo\?\s*/', '/OCUPACIÓN|MÉDICO|Not as|Notas adicionales/'),
            'cirugias_previas' => self::combinarLesionesYCirugias($texto),
            'condicion_cronica' => self::entre($texto, '/o cáncer\)\? \(Si la respuesta es SÍ, explica\.\)\s*/', '/¿Tomas algún medicamento/'),
            'medicamentos_actuales' => self::entre($texto, '/para realizar actividad\s*física\?\s*/', '/Not as adicionales|Notas adicionales|$/'),
            'notas_adicionales' => self::entre($texto, '/Not[ a]*s adicionales:\s*/', '/$/'),
        ];

        foreach ($campos as $clave => $valor) {
            if (is_string($valor)) {
                $valor = trim($valor, " \t\n\r\0\x0B_-.");
                $campos[$clave] = $valor !== '' ? $valor : null;
            }
        }

        $demograficos = [
            'nombre' => self::valorEtiqueta($texto, '/Nombre:\s*_*\s*/', '/_{3,}|Fehca|Fecha|Edad/'),
            'edad' => self::valorEtiqueta($texto, '/Edad:_*\s*/', '/_{2,}|Género|$/'),
            'genero' => self::valorEtiqueta($texto, '/Género:\s*_*\s*/', '/_{2,}|Altura|$/'),
            'altura' => self::valorEtiqueta($texto, '/Altura:\s*_*\s*/', '/_{2,}|Peso|$/'),
            'peso' => self::valorEtiqueta($texto, '/Peso:\s*_*\s*/', '/_{2,}|Teléfono|Nombre y teléfono|$/'),
            'fecha' => self::valorEtiqueta($texto, '/Fe[hc]*ca:_*\s*/', '/_{2,}|Edad|$/'),
        ];

        return ['campos' => $campos, 'demograficos' => array_filter($demograficos)];
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

    private static function numeroTrasEtiqueta(string $texto, string $etiquetaRegex): ?int
    {
        if (!preg_match($etiquetaRegex, $texto, $m, PREG_OFFSET_CAPTURE)) {
            return null;
        }
        $resto = substr($texto, $m[0][1] + strlen($m[0][0]));
        if (preg_match('/_*\s*(\d+(?:\.\d+)?)/', $resto, $mn)) {
            return (int) round((float) $mn[1]);
        }
        return null;
    }

    /** @param array<int, string> $opciones */
    private static function palabraTrasEtiqueta(string $texto, string $etiquetaRegex, array $opciones): ?string
    {
        if (!preg_match($etiquetaRegex, $texto, $m, PREG_OFFSET_CAPTURE)) {
            return null;
        }
        // Las rayas "____" de la línea en blanco cuentan como \w (guion bajo),
        // así que "\bmedio\b" no matchea dentro de "____medio____" — se
        // reemplazan por espacios antes de buscar los límites de palabra.
        $resto = str_replace('_', ' ', mb_strtolower(substr($texto, $m[0][1] + strlen($m[0][0]), 60)));

        $mejorPos = null;
        $mejorOp = null;
        foreach ($opciones as $op) {
            if (preg_match('/\b' . preg_quote($op, '/') . '\b/', $resto, $om, PREG_OFFSET_CAPTURE)
                && ($mejorPos === null || $om[0][1] < $mejorPos)
            ) {
                $mejorPos = $om[0][1];
                $mejorOp = $op;
            }
        }
        return $mejorOp;
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

    private static function combinarLesionesYCirugias(string $texto): ?string
    {
        $lesion = self::entre($texto, '/lesión musculoesquelética previa:\s*/', '/Por favor, enumera cualquier cirugía/');
        $cirugia = self::entre($texto, '/cualquier cirugía previa:\s*/', '/Si has tenido lesiones/');

        $lesion = $lesion !== null ? trim($lesion, " \t\n\r\0\x0B_-.") : '';
        $cirugia = $cirugia !== null ? trim($cirugia, " \t\n\r\0\x0B_-.") : '';

        $partes = [];
        if ($lesion !== '') {
            $partes[] = 'Lesiones previas: ' . $lesion;
        }
        if ($cirugia !== '') {
            $partes[] = 'Cirugías previas: ' . $cirugia;
        }

        return $partes !== [] ? implode("\n", $partes) : null;
    }
}
