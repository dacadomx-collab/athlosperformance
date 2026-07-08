<?php
declare(strict_types=1);

/**
 * ATHLOS PERFORMANCE — MAPEO DE CELDAS DEL EXCEL HISTÓRICO DE ANTROPOMETRÍA
 * (plantilla "Menor_65_02 DATOS ANTROPOMETRÍA ATHLOS.xlsx", hojas
 * "ANTRO MASCU" / "ANTRO FEME").
 *
 * IMPORTANTE — alcance y confiabilidad de este mapeo:
 * Las coordenadas de celda abajo se determinaron inspeccionando el ÚNICO
 * archivo de ejemplo disponible en knowledge/Menores_65/, y se
 * verificaron cruzando el IMC de la celda contra peso/estatura
 * (72kg / 1.83m² = 21.4996, exactamente igual al valor de la celda D27 en
 * la hoja MASCU). Esto da confianza en los campos de ENTRADA directa
 * (peso, estatura, edad, pliegues, perímetros, diámetros — literalmente lo
 * que alguien midió con cinta/plicómetro). Los campos DERIVADOS (densidad,
 * % grasa Siri/Rocha, masa ósea, somatotipo) se extraen tal cual estén en
 * el Excel, pero NO se tratan como верdad absoluta: el propio archivo de
 * ejemplo mostró inconsistencias internas (ej. la hoja FEME tiene una celda
 * de estatura en metros que no coincide con su celda de estatura en cm),
 * así que estos valores se marcan en pantalla como "extraído tal cual,
 * verificar" en vez de darlos por buenos automáticamente. El IMC y su
 * clasificación se recalculan con la misma fórmula ya usada en
 * antropometria_form.php, en vez de confiar en el valor de la celda.
 */
final class AntropometriaXlsxMapper
{
    private const MAPA_MASCULINO = [
        'sheet' => 'xl/worksheets/sheet1.xml',
        'genero' => 'D8', 'edad' => 'D10', 'estatura_cm' => 'D11', 'peso_kg' => 'D12',
        'pliegue_tricipital' => 'P15', 'pliegue_bicipital' => 'P16', 'pliegue_subescapular' => 'P17',
        'pliegue_abdominal' => 'P18', 'pliegue_ileocrestal' => 'P19', 'pliegue_supraespinal' => 'P20',
        'pliegue_muslo' => 'P21', 'pliegue_pierna' => 'P22',
        'perimetro_brazo_relajado_der' => 'P6', 'perimetro_brazo_contraido_der' => 'P7',
        'perimetro_muneca_der' => 'P8', 'perimetro_cintura_minima' => 'P9', 'perimetro_cadera_maxima' => 'P10',
        'perimetro_muslo_der' => 'P11', 'perimetro_pierna_relajada_der' => 'P13', 'perimetro_pierna_contraida_der' => 'P12',
        'diametro_humeral' => 'O25', 'diametro_femoral' => 'O26', 'diametro_estiloideo' => 'O27', 'diametro_biacromial' => 'O28',
        'densidad_corporal' => 'AD31',
        'porcentaje_grasa_siri' => 'AE36', 'masa_grasa_siri_kg' => 'AE38',
        'masa_osea_rocha_kg' => 'AE42', 'masa_muscular_matiegka_kg' => 'AE50', 'masa_residual_wurch_kg' => 'AE46',
        'endomorfia' => 'AQ6', 'mesomorfia' => 'AQ10', 'ectomorfia' => 'AO26',
        'indice_cintura_cadera' => 'N42',
        'sumatoria_pliegues' => 'Q47',
    ];

    private const MAPA_FEMENINO = [
        'sheet' => 'xl/worksheets/sheet2.xml',
        'genero' => 'C8', 'edad' => 'C10', 'estatura_cm' => 'C11', 'peso_kg' => 'C12',
        'pliegue_tricipital' => 'O15', 'pliegue_bicipital' => 'O16', 'pliegue_subescapular' => 'O17',
        'pliegue_abdominal' => 'O18', 'pliegue_ileocrestal' => 'O19', 'pliegue_supraespinal' => 'O20',
        'pliegue_muslo' => 'O21', 'pliegue_pierna' => 'O22',
        'perimetro_brazo_relajado_der' => 'O6', 'perimetro_brazo_contraido_der' => 'O7',
        'perimetro_muneca_der' => 'O8', 'perimetro_cintura_minima' => 'O9', 'perimetro_cadera_maxima' => 'O10',
        'perimetro_muslo_der' => 'O11', 'perimetro_pierna_relajada_der' => 'O13', 'perimetro_pierna_contraida_der' => 'O12',
        'diametro_humeral' => 'N25', 'diametro_femoral' => 'N26', 'diametro_estiloideo' => 'N27', 'diametro_biacromial' => 'N28',
        'densidad_corporal' => 'AC31',
        'porcentaje_grasa_siri' => 'AD36', 'masa_grasa_siri_kg' => 'AD38',
        'masa_osea_rocha_kg' => 'AD42', 'masa_muscular_matiegka_kg' => 'AD50', 'masa_residual_wurch_kg' => 'AD46',
        'endomorfia' => 'AO24', 'mesomorfia' => 'AO27', 'ectomorfia' => 'AO30',
        'indice_cintura_cadera' => 'M42',
        'sumatoria_pliegues' => 'P47',
    ];

    /**
     * Intenta ambas hojas (masculino/femenino) y usa la primera que tenga
     * peso y estatura capturados (heurística simple de "hoja con datos").
     *
     * @return array{datos: array<string, float|string|null>, hoja_usada: string}
     */
    public static function extraer(string $rutaArchivo): array
    {
        foreach (['masculino' => self::MAPA_MASCULINO, 'femenino' => self::MAPA_FEMENINO] as $genero => $mapa) {
            $indice = self::indexarPorCelda($rutaArchivo, $mapa['sheet']);

            $peso = self::valorNumerico($indice[$mapa['peso_kg']] ?? null);
            $estatura = self::valorNumerico($indice[$mapa['estatura_cm']] ?? null);

            if ($peso !== null && $estatura !== null && $peso > 0 && $estatura > 0) {
                $datos = ['genero_detectado' => $genero];
                foreach ($mapa as $campo => $celda) {
                    if ($campo === 'sheet') {
                        continue;
                    }
                    $datos[$campo] = $indice[$celda] ?? null;
                }
                return ['datos' => $datos, 'hoja_usada' => $mapa['sheet']];
            }
        }

        throw new \RuntimeException('No se encontraron datos de peso/estatura en ninguna de las hojas conocidas (ANTRO MASCU / ANTRO FEME). ¿Es el archivo correcto?');
    }

    /** @return array<string, string> Mapa "COLFILA" => valor resuelto. */
    private static function indexarPorCelda(string $rutaArchivo, string $sheetEntry): array
    {
        $strings = XlsxReader::readSharedStrings($rutaArchivo);
        $filasPorNumero = XlsxReader::readSheetRows($rutaArchivo, $sheetEntry, $strings);

        $indice = [];
        foreach ($filasPorNumero as $numFila => $columnas) {
            foreach ($columnas as $col => $valor) {
                $indice["{$col}{$numFila}"] = $valor;
            }
        }

        return $indice;
    }

    private static function valorNumerico(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        if (!is_numeric($valor)) {
            return null;
        }
        return (float) $valor;
    }
}
