<?php
declare(strict_types=1);

/**
 * ATHLOS SSOS v1.0 — LECTOR NATIVO DE XLSX (SIN DEPENDENCIAS)
 *
 * Un archivo .xlsx es un ZIP de XMLs. El hosting de producción (cPanel
 * compartido) no garantiza la extensión `zip` de PHP habilitada — de hecho
 * el entorno local de desarrollo de este proyecto tampoco la tiene
 * (verificado: `extension_loaded('zip') === false`). En vez de exigir
 * PhpSpreadsheet/ext-zip como dependencia nueva (Mandamiento de "arquitectura
 * sin framework" del proyecto), esta clase implementa un lector ZIP mínimo
 * usando sólo `zlib` (gzinflate — universalmente disponible) para extraer
 * las entradas XML que necesitamos, y `SimpleXML` (siempre disponible) para
 * parsear su contenido.
 *
 * Sólo soporta lo estrictamente necesario para leer una hoja de cálculo
 * simple (shared strings + una hoja de datos) — no es un reemplazo general
 * de PhpSpreadsheet.
 */
final class XlsxReader
{
    /** @return array<int, string> Cadenas del sharedStrings.xml, indexadas por su posición original. */
    public static function readSharedStrings(string $xlsxPath): array
    {
        $xml = self::readZipEntry($xlsxPath, 'xl/sharedStrings.xml');
        if ($xml === null) {
            return [];
        }

        $sxml = self::loadXml($xml);
        $strings = [];
        foreach ($sxml->si as $si) {
            $strings[] = self::extractAllText($si);
        }

        return $strings;
    }

    /**
     * Lee una hoja y devuelve las filas como arreglos asociativos
     * columna-letra => valor ya resuelto (shared strings incluidas).
     *
     * @param array<int, string> $sharedStrings
     * @return array<int, array<string, string>> Indexado por número de fila (1-based, tal cual el XML).
     */
    public static function readSheetRows(string $xlsxPath, string $sheetEntry, array $sharedStrings): array
    {
        $xml = self::readZipEntry($xlsxPath, $sheetEntry);
        if ($xml === null) {
            throw new \RuntimeException("No se encontró la hoja '{$sheetEntry}' dentro del archivo.");
        }

        $sxml = self::loadXml($xml);
        $rows = [];

        foreach ($sxml->sheetData->row as $row) {
            $rowNum = (int) $row['r'];
            $rowData = [];

            foreach ($row->c as $cell) {
                $ref = (string) $cell['r'];
                $col = preg_replace('/\d+/', '', $ref) ?? '';
                $type = (string) $cell['t'];

                if ($type === 's') {
                    $idx = (int) $cell->v;
                    $rowData[$col] = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $rowData[$col] = self::extractAllText($cell->is);
                } elseif (isset($cell->v)) {
                    $rowData[$col] = (string) $cell->v;
                } else {
                    $rowData[$col] = '';
                }
            }

            $rows[$rowNum] = $rowData;
        }

        return $rows;
    }

    /** Concatena todo el texto <t> dentro de un nodo (soporta rich text con múltiples <r><t>). */
    private static function extractAllText(\SimpleXMLElement $node): string
    {
        $texts = $node->xpath('.//*[local-name()="t"]');
        return implode('', array_map(static fn ($t) => (string) $t, $texts ?: []));
    }

    private static function loadXml(string $xml): \SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);
        $sxml = simplexml_load_string($xml);
        libxml_use_internal_errors($previous);

        if ($sxml === false) {
            throw new \RuntimeException('No se pudo parsear el XML interno del archivo .xlsx.');
        }

        return $sxml;
    }

    /**
     * Lee una entrada de un ZIP sin depender de la extensión `zip`: localiza
     * el End Of Central Directory, recorre el directorio central para
     * encontrar el header local de la entrada solicitada, y descomprime su
     * contenido (deflate crudo vía gzinflate, o sin comprimir).
     */
    private static function readZipEntry(string $zipPath, string $entryName): ?string
    {
        $fp = fopen($zipPath, 'rb');
        if ($fp === false) {
            throw new \RuntimeException('No se pudo abrir el archivo .xlsx.');
        }

        try {
            $size = filesize($zipPath);
            if ($size === false || $size < 22) {
                throw new \RuntimeException('Archivo .xlsx inválido o corrupto.');
            }

            // Buscar la firma de End Of Central Directory (0x06054b50) desde el final.
            $searchWindow = min($size, 66000);
            fseek($fp, -$searchWindow, SEEK_END);
            $tail = fread($fp, $searchWindow);
            $eocdPos = strrpos($tail, "\x50\x4b\x05\x06");
            if ($eocdPos === false) {
                throw new \RuntimeException('No se encontró la firma ZIP (End Of Central Directory). ¿Es un .xlsx válido?');
            }

            $eocd = substr($tail, $eocdPos, 22);
            $cdEntries = unpack('vdisk/vcddisk/vdiskrecords/vtotalrecords/Vcdsize/Vcdoffset/vcommentlen', substr($eocd, 4));
            $cdOffset = $cdEntries['cdoffset'];
            $totalRecords = $cdEntries['totalrecords'];

            fseek($fp, $cdOffset);
            $centralDirectory = fread($fp, $cdEntries['cdsize']);

            $pos = 0;
            for ($i = 0; $i < $totalRecords; $i++) {
                if (substr($centralDirectory, $pos, 4) !== "\x50\x4b\x01\x02") {
                    throw new \RuntimeException('Directorio central ZIP corrupto o con formato inesperado.');
                }

                $header = unpack(
                    'vverMade/vverNeeded/vflag/vmethod/vmodtime/vmoddate/Vcrc/Vcompsize/Vuncompsize/vnamelen/vextralen/vcommentlen/vdisknum/vintattr/Vextattr/Vlocaloffset',
                    substr($centralDirectory, $pos + 4, 42)
                );

                $nameStart = $pos + 46;
                $name = substr($centralDirectory, $nameStart, $header['namelen']);

                if ($name === $entryName) {
                    return self::extractLocalEntry($fp, $header['localoffset'], $header['method'], $header['compsize']);
                }

                $pos = $nameStart + $header['namelen'] + $header['extralen'] + $header['commentlen'];
            }

            return null;
        } finally {
            fclose($fp);
        }
    }

    private static function extractLocalEntry($fp, int $localOffset, int $method, int $compSize): string
    {
        fseek($fp, $localOffset);
        $localHeader = fread($fp, 30);
        $local = unpack(
            'Vsig/vverNeeded/vflag/vmethod/vmodtime/vmoddate/Vcrc/Vcompsize/Vuncompsize/vnamelen/vextralen',
            $localHeader
        );

        $dataOffset = $localOffset + 30 + $local['namelen'] + $local['extralen'];
        fseek($fp, $dataOffset);
        $compressed = fread($fp, $compSize);

        if ($method === 0) {
            return $compressed;
        }

        if ($method === 8) {
            $inflated = @gzinflate($compressed);
            if ($inflated === false) {
                throw new \RuntimeException('No se pudo descomprimir una entrada del archivo .xlsx.');
            }
            return $inflated;
        }

        throw new \RuntimeException("Método de compresión ZIP no soportado ({$method}).");
    }
}
