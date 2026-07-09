<?php
declare(strict_types=1);

/**
 * ATHLOS SSOS v1.0 — EXTRACTOR NATIVO DE TEXTO DE PDF (SIN DEPENDENCIAS)
 *
 * Igual que XlsxReader.php: el hosting compartido de producción no garantiza
 * Composer/paquetes externos (smalot/pdfparser, etc.), así que este extractor
 * lee el PDF a mano usando sólo `zlib` (streams FlateDecode) y regex sobre
 * los operadores de los content streams.
 *
 * Sólo soporta el caso real que necesitamos: PDFs "impresos" desde
 * Chrome/Android (Producer "Skia"), con fuentes Type0/CIDFontType2 e
 * Identity-H — es decir, cada carácter mostrado es un código de 2 bytes que
 * sólo cobra sentido a través del CMap /ToUnicode embebido en la fuente. No
 * es un reemplazo general de un parser PDF (no soporta ObjStm, cifrado,
 * fuentes simples con /Differences, etc.) — validado únicamente contra los 3
 * PDFs reales de historial/ficha de evaluación de Athlos.
 */
final class PdfTextExtractor
{
    public static function extraerTexto(string $path): string
    {
        $data = file_get_contents($path);
        if ($data === false) {
            throw new \RuntimeException('No se pudo leer el archivo PDF.');
        }

        $objetos = self::extraerObjetos($data);
        $cacheToUnicode = [];
        $texto = '';

        foreach ($objetos as $numero => $objeto) {
            if ($objeto['stream'] === null || !str_contains($objeto['stream'], 'BT')) {
                continue;
            }

            $mapaFuentes = self::resolverFuentesDePagina($objetos, $numero);
            $texto .= self::extraerTextoDeStream($objeto['stream'], $mapaFuentes, $objetos, $cacheToUnicode);
        }

        return trim($texto);
    }

    /** @return array<int, array{dict: string, stream: ?string}> */
    private static function extraerObjetos(string $data): array
    {
        $objetos = [];
        if (!preg_match_all('/(\d+)\s+(\d+)\s+obj\b/', $data, $m, PREG_OFFSET_CAPTURE)) {
            return $objetos;
        }

        $total = count($m[0]);
        for ($i = 0; $i < $total; $i++) {
            $numero = (int) $m[1][$i][0];
            $inicio = $m[0][$i][1] + strlen($m[0][$i][0]);
            $fin = strpos($data, 'endobj', $inicio);
            if ($fin === false) {
                continue;
            }
            $cuerpo = substr($data, $inicio, $fin - $inicio);

            $stream = null;
            if (preg_match('/stream\r?\n/', $cuerpo, $sm, PREG_OFFSET_CAPTURE)) {
                $inicioStream = $sm[0][1] + strlen($sm[0][0]);
                $finStream = strpos($cuerpo, 'endstream', $inicioStream);
                if ($finStream !== false) {
                    $crudo = rtrim(substr($cuerpo, $inicioStream, $finStream - $inicioStream), "\r\n");
                    $dict = substr($cuerpo, 0, $sm[0][1]);
                    $stream = str_contains($dict, '/FlateDecode') ? self::inflar($crudo) : $crudo;
                    $cuerpo = $dict;
                }
            }

            $objetos[$numero] = ['dict' => $cuerpo, 'stream' => $stream];
        }

        return $objetos;
    }

    private static function inflar(string $crudo): string
    {
        $resultado = @gzuncompress($crudo);
        if ($resultado !== false) {
            return $resultado;
        }
        $resultado = @gzinflate(substr($crudo, 2));
        return $resultado !== false ? $resultado : '';
    }

    /** @return array<string, int> Nombre de recurso (ej. "/F1") -> número de objeto de la fuente. */
    private static function resolverFuentesDePagina(array $objetos, int $numeroContenido): array
    {
        foreach ($objetos as $pagina) {
            if (preg_match('/\/Contents\s+' . $numeroContenido . '\s+0\s+R/', $pagina['dict'])
                && preg_match('/\/Font\s*<<(.*?)>>/s', $pagina['dict'], $fm)
            ) {
                return self::parearReferenciasFuente($fm[1]);
            }
        }

        // Alternativa: el content stream puede no tener una página propia
        // detectable (ej. XObject) — se usa el primer bloque /Font que exista.
        foreach ($objetos as $obj) {
            if (preg_match('/\/Font\s*<<(.*?)>>/s', $obj['dict'], $fm)) {
                return self::parearReferenciasFuente($fm[1]);
            }
        }

        return [];
    }

    /** @return array<string, int> */
    private static function parearReferenciasFuente(string $bloqueFont): array
    {
        $mapa = [];
        if (preg_match_all('/\/(\w+)\s+(\d+)\s+0\s+R/', $bloqueFont, $pares, PREG_SET_ORDER)) {
            foreach ($pares as $par) {
                $mapa['/' . $par[1]] = (int) $par[2];
            }
        }
        return $mapa;
    }

    /** @return array<string, string> Código hex (4 dígitos) -> carácter Unicode. */
    private static function obtenerMapaToUnicode(array $objetos, int $numeroFuente, array &$cache): array
    {
        if (isset($cache[$numeroFuente])) {
            return $cache[$numeroFuente];
        }

        $mapa = [];
        $dictFuente = $objetos[$numeroFuente]['dict'] ?? '';
        if (preg_match('/\/ToUnicode\s+(\d+)\s+\d+\s+R/', $dictFuente, $mm)) {
            $numeroCmap = (int) $mm[1];
            if (isset($objetos[$numeroCmap]['stream'])) {
                $mapa = self::parsearCMap($objetos[$numeroCmap]['stream']);
            }
        }

        $cache[$numeroFuente] = $mapa;
        return $mapa;
    }

    /** @return array<string, string> */
    private static function parsearCMap(string $cmapData): array
    {
        $mapa = [];

        if (preg_match_all('/beginbfchar(.*?)endbfchar/s', $cmapData, $bloques)) {
            foreach ($bloques[1] as $bloque) {
                if (preg_match_all('/<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>/', $bloque, $pares, PREG_SET_ORDER)) {
                    foreach ($pares as $p) {
                        $mapa[strtoupper($p[1])] = self::hexAUnicode($p[2]);
                    }
                }
            }
        }

        if (preg_match_all('/beginbfrange(.*?)endbfrange/s', $cmapData, $bloques)) {
            foreach ($bloques[1] as $bloque) {
                if (preg_match_all('/<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>/', $bloque, $rangos, PREG_SET_ORDER)) {
                    foreach ($rangos as $r) {
                        $lo = hexdec($r[1]);
                        $hi = hexdec($r[2]);
                        $destinoInicial = hexdec($r[3]);
                        $ancho = strlen($r[1]);
                        for ($c = $lo; $c <= $hi; $c++) {
                            $src = strtoupper(str_pad(dechex($c), $ancho, '0', STR_PAD_LEFT));
                            $mapa[$src] = self::hexAUnicode(str_pad(dechex($destinoInicial + ($c - $lo)), 4, '0', STR_PAD_LEFT));
                        }
                    }
                }
                if (preg_match_all('/<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>\s*\[(.*?)\]/s', $bloque, $rangos2, PREG_SET_ORDER)) {
                    foreach ($rangos2 as $r) {
                        $lo = hexdec($r[1]);
                        $ancho = strlen($r[1]);
                        preg_match_all('/<([0-9A-Fa-f]+)>/', $r[3], $destinos);
                        foreach ($destinos[1] as $idx => $destino) {
                            $src = strtoupper(str_pad(dechex($lo + $idx), $ancho, '0', STR_PAD_LEFT));
                            $mapa[$src] = self::hexAUnicode($destino);
                        }
                    }
                }
            }
        }

        return $mapa;
    }

    private static function hexAUnicode(string $hex): string
    {
        $hex = strlen($hex) % 2 === 1 ? $hex . '0' : $hex;
        $bytes = hex2bin($hex);
        if ($bytes === false || $bytes === '') {
            return '';
        }
        return mb_convert_encoding($bytes, 'UTF-8', 'UTF-16BE');
    }

    /** @param array<string, int> $mapaFuentes */
    private static function extraerTextoDeStream(string $stream, array $mapaFuentes, array $objetos, array &$cacheToUnicode): string
    {
        $texto = '';
        $mapaActual = [];
        $ultimaY = null;

        $strLit = '\((?:\\\\.|[^\\\\()])*\)';
        $strHex = '<[0-9A-Fa-f\s]*>';
        $str = '(?:' . $strLit . '|' . $strHex . ')';

        preg_match_all(
            '/\/(\w+)\s+[\d.]+\s+Tf|[\d.\-]+ [\d.\-]+ [\d.\-]+ [\d.\-]+ [\d.\-]+ ([\d.\-]+)\s+Tm|' . $str . '\s*Tj|\[(?:' . $str . '|[^\[\]])*\]\s*TJ|BT|ET/',
            $stream,
            $tokens
        );

        foreach ($tokens[0] as $tok) {
            if (preg_match('/^\/(\w+)\s+[\d.]+\s+Tf$/', $tok, $fm)) {
                $nombreFuente = '/' . $fm[1];
                $mapaActual = isset($mapaFuentes[$nombreFuente])
                    ? self::obtenerMapaToUnicode($objetos, $mapaFuentes[$nombreFuente], $cacheToUnicode)
                    : [];
                continue;
            }
            if (preg_match('/([\d.\-]+)\s+Tm$/', $tok, $ym)) {
                $y = (float) $ym[1];
                if ($ultimaY !== null && abs($y - $ultimaY) > 2.0) {
                    $texto .= "\n";
                }
                $ultimaY = $y;
                continue;
            }
            if ($tok === 'BT' || $tok === 'ET') {
                continue;
            }
            if (preg_match('/^(' . $strLit . '|' . $strHex . ')\s*Tj$/s', $tok, $sm)) {
                $texto .= self::decodificarCadena($sm[1], $mapaActual);
            } elseif (preg_match('/^\[(.*)\]\s*TJ$/s', $tok, $am)) {
                if (preg_match_all('/' . $str . '/', $am[1], $cadenas)) {
                    foreach ($cadenas[0] as $cadena) {
                        $texto .= self::decodificarCadena($cadena, $mapaActual);
                    }
                }
            }
        }

        return $texto;
    }

    /** @param array<string, string> $mapa */
    private static function decodificarCadena(string $token, array $mapa): string
    {
        if ($token[0] === '<') {
            $hex = preg_replace('/\s+/', '', substr($token, 1, -1)) ?? '';
            $bytes = hex2bin(strlen($hex) % 2 === 1 ? $hex . '0' : $hex) ?: '';
        } else {
            $literal = substr($token, 1, -1);
            $bytes = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $literal);
        }

        $out = '';
        $len = strlen($bytes);
        for ($i = 0; $i + 1 < $len; $i += 2) {
            $codigo = strtoupper(sprintf('%02X%02X', ord($bytes[$i]), ord($bytes[$i + 1])));
            $out .= $mapa[$codigo] ?? '';
        }
        return $out;
    }
}
