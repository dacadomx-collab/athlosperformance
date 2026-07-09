<?php
declare(strict_types=1);

/**
 * ATHLOS SSOS v1.0 — MAPEADOR DE PDF DE HISTORIAL CLÍNICO A historial_clinico
 *
 * Toma el texto plano ya extraído por PdfTextExtractor (formulario "Historial
 * clínico" / "Información del Cliente" de Athlos, exportado a PDF desde
 * Chrome/Android) y lo mapea por posición de las preguntas fijas de la
 * plantilla a los campos de historial_form.php — TODAS las columnas de
 * historial_clinico (03_schema_evaluaciones_clinicas.sql), incluidas las de
 * uso exclusivo "mayor_65" (cafeína, estrés, ocupación, recreación, médico,
 * contacto de emergencia) y las de uso exclusivo "menor_65" (teléfono
 * personal, correo electrónico) — la plantilla de cada tipo simplemente no
 * trae esas preguntas, así que el regex correspondiente no matchea y el
 * campo queda en null, sin necesidad de una rama if/else por tipo.
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
            'control_antojos_score' => self::numeroTrasEtiqueta($texto, '/antojos de comida chatarra\?\s*/'),
            'bebidas_alcoholicas_semana' => self::numeroTrasEtiqueta($texto, '/¿Cuántas bebidas alcohólicas consumes por semana\?\s*/'),
            'consumo_cafeina' => self::entre($texto, '/bebidas energéticas\?\s*¿Cuántas veces por semana\?\s*/', '/ESTILO DE VIDA/'),
            'sueno_adecuado' => self::entre($texto, '/descansado\(a\) cada día\?\s*/', '/En una escala de 0 a 10, ¿cómo calificarías|¿Fumas tabaco/'),
            'nivel_estres_score' => self::numeroTrasEtiqueta($texto, '/nivel promedio de estrés\?\s*/'),
            'tecnicas_manejo_estres' => self::entre($texto, '/manejar tus niveles de estrés\?\s*/', '/¿Fumas tabaco/'),
            'fuma_o_vapea' => self::entre($texto, '/dispositivo de vapeo\?\s*/', '/OCUPACIÓN|MÉDICO|Not as|Notas adicionales/'),
            'ocupacion' => self::entre($texto, '/¿Cuál es tu ocupación\?\s*/', '/¿Tu trabajo requiere largos periodos/'),
            'trabajo_sedentario_detalle' => self::entre($texto, '/estar sentado\? \(Si la respuesta es SÍ, explica\.\)\s*/', '/¿Tu trabajo requiere movimientos repetitivos/'),
            'trabajo_movimientos_repetitivos_detalle' => self::entre($texto, '/movimientos repetitivos\? \(Si la respuesta es SÍ, explica\.\)\s*/', '/¿Tu trabajo requiere el uso de calzado/'),
            'trabajo_calzado_tacon' => self::siNoTrasEtiqueta($texto, '/botas de trabajo\)\?\s*/', '/RECREACIÓN/'),
            'actividad_recreativa_detalle' => self::entre($texto, '/golf, esquí, etc\.\)\? \(Si la respuesta es SÍ, explica\.\)\s*/', '/¿Tienes algún otro pasatiempo/'),
            'otro_pasatiempo_detalle' => self::entre($texto, '/jardinería, pesca, música, etc\.\)\? \(Si la respuesta es SÍ, explica\.\)\s*/', '/MÉDICO/'),
            'cirugias_previas' => self::combinarLesionesYCirugias($texto),
            'rehabilitacion_adecuada_autorizacion' => self::entre($texto, '/para volver a la actividad física\?\s*/', '/¿Tienes alguna condición/'),
            'condicion_cronica' => self::entre($texto, '/o cáncer\)\? \(Si la respuesta es SÍ, explica\.\)\s*/', '/¿Tomas algún medicamento/'),
            'medicamentos_actuales' => self::entre($texto, '/para realizar actividad\s*física\?\s*/', '/Not as adicionales|Notas adicionales|$/'),
            'nombre_medico' => self::entre($texto, '/Nombre y teléfono del médico:\s*_*\s*/', '/_{3,}|Nombre y teléfono de contacto|$/'),
            'contacto_emergencia_nombre' => self::entre($texto, '/Nombre y teléfono de contacto de emergencia:\s*_*\s*/', '/_{3,}|EJERCICIO|$/'),
            'telefono_personal' => self::entre($texto, '/Teléfono personal:\s*_*\s*/', '/_{3,}|Correo electrónico|$/'),
            'correo_electronico' => self::entre($texto, '/Correo electrónico:\s*_*\s*/', '/_{3,}|EJERCICIO|$/'),
            'notas_adicionales' => self::entre($texto, '/Not[ a]*s adicionales:\s*/', '/$/'),
        ];

        foreach ($campos as $clave => $valor) {
            if (is_string($valor)) {
                $valor = trim($valor, " \t\n\r\0\x0B_-.");
                $campos[$clave] = $valor !== '' ? $valor : null;
            }
        }

        // telefono_medico/contacto_emergencia_telefono: la plantilla pide
        // "Nombre y teléfono" como una sola respuesta libre (sin separador
        // fijo entre nombre y número) — no hay forma confiable de partirlo
        // en 2 columnas por regex, así que el texto completo va a la columna
        // *_nombre y *_telefono queda para que el coach lo separe a mano.
        $campos['telefono_medico'] = null;
        $campos['contacto_emergencia_telefono'] = null;

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

    /** Campo TINYINT(1) sí/no — sólo se resuelve si la respuesta es inequívoca; en blanco o ambiguo, null. */
    private static function siNoTrasEtiqueta(string $texto, string $inicioRegex, string $finRegex): ?int
    {
        $segmento = self::entre($texto, $inicioRegex, $finRegex);
        if ($segmento === null) {
            return null;
        }
        $segmento = str_replace('_', ' ', mb_strtolower($segmento));
        $tieneSi = preg_match('/\bs[ií]\b/', $segmento) === 1;
        $tieneNo = preg_match('/\bno\b/', $segmento) === 1;
        if ($tieneSi === $tieneNo) {
            return null; // ninguna respuesta clara, o ambas (texto libre ambiguo) — no adivinar.
        }
        return $tieneSi ? 1 : 0;
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
