<?php

namespace App\Services;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class ExcelWorkbookService
{
    /**
     * Read the first worksheet from a simple .xlsx workbook.
     *
     * @return array<int, array<int, mixed>>
     */
    public function readFirstSheet(string $path): array
    {
        $zip = $this->openZip($path);

        try {
            $sharedStrings = $this->readSharedStrings($zip);
            $sheetPath = $this->firstWorksheetPath($zip);
            $xml = $zip->getFromName($sheetPath);

            if ($xml === false) {
                throw new RuntimeException("Worksheet not found in {$path}.");
            }

            $sheet = $this->loadXml($xml);
            $rows = [];

            foreach ($sheet->sheetData->row as $row) {
                $rowValues = [];

                foreach ($row->c as $cell) {
                    $ref = (string) $cell['r'];
                    $index = $this->columnIndexFromRef($ref);
                    $rowValues[$index] = $this->readCellValue($cell, $sharedStrings);
                }

                if ($rowValues !== []) {
                    $max = max(array_keys($rowValues));
                    $rows[] = array_map(
                        fn (int $i) => $rowValues[$i] ?? null,
                        range(0, $max)
                    );
                }
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }

    /**
     * Write a single worksheet .xlsx. If a template is supplied, preserve the
     * workbook shell and replace the first worksheet data.
     *
     * @param array<int, array<int, mixed>> $rows
     */
    public function writeRows(string $path, array $rows, ?string $templatePath = null, string $sheetName = 'Sheet1'): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Cannot create directory {$directory}.");
        }

        if ($templatePath && is_file($templatePath)) {
            copy($templatePath, $path);
            $zip = $this->openZip($path);

            try {
                $sheetPath = $this->firstWorksheetPath($zip);
                $zip->deleteName($sheetPath);
                $zip->addFromString($sheetPath, $this->worksheetXml($rows));
            } finally {
                $zip->close();
            }

            return;
        }

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Cannot create workbook {$path}.");
        }

        try {
            $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
            $zip->addFromString('_rels/.rels', $this->rootRelsXml());
            $zip->addFromString('xl/workbook.xml', $this->workbookXml($sheetName));
            $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
            $zip->addFromString('xl/styles.xml', $this->stylesXml());
            $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheetXml($rows));
            $zip->addFromString('docProps/core.xml', $this->coreXml());
            $zip->addFromString('docProps/app.xml', $this->appXml($sheetName));
        } finally {
            $zip->close();
        }
    }

    private function openZip(string $path): ZipArchive
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException("Cannot open workbook {$path}.");
        }

        return $zip;
    }

    /**
     * @return array<int, string>
     */
    private function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $strings = [];
        foreach ($this->loadXml($xml)->si as $item) {
            if (isset($item->t)) {
                $strings[] = (string) $item->t;
                continue;
            }

            $value = '';
            foreach ($item->r as $run) {
                $value .= (string) $run->t;
            }
            $strings[] = $value;
        }

        return $strings;
    }

    private function firstWorksheetPath(ZipArchive $zip): string
    {
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($relsXml !== false) {
            $rels = $this->loadXml($relsXml);
            foreach ($rels->Relationship as $relationship) {
                if (str_contains((string) $relationship['Type'], '/worksheet')) {
                    $target = ltrim((string) $relationship['Target'], '/');
                    return str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
                }
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private function readCellValue(SimpleXMLElement $cell, array $sharedStrings): mixed
    {
        $type = (string) $cell['t'];

        if ($type === 's') {
            return $sharedStrings[(int) $cell->v] ?? '';
        }

        if ($type === 'inlineStr') {
            return (string) ($cell->is->t ?? '');
        }

        if ($type === 'b') {
            return (string) $cell->v === '1';
        }

        $value = isset($cell->v) ? (string) $cell->v : '';
        if ($value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : $value;
    }

    private function columnIndexFromRef(string $ref): int
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($ref)) ?: 'A';
        $number = 0;

        foreach (str_split($letters) as $letter) {
            $number = ($number * 26) + (ord($letter) - 64);
        }

        return $number - 1;
    }

    private function columnName(int $index): string
    {
        $name = '';
        $index++;

        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $name = chr(65 + $mod) . $name;
            $index = intdiv($index - $mod, 26);
        }

        return $name;
    }

    /**
     * @param array<int, array<int, mixed>> $rows
     */
    private function worksheetXml(array $rows): string
    {
        $rowCount = max(count($rows), 1);
        $colCount = max(array_map(fn (array $row) => max(count($row), 1), $rows ?: [[]]));
        $dimension = 'A1:' . $this->columnName($colCount - 1) . $rowCount;
        $xmlRows = [];

        foreach ($rows as $rowIndex => $row) {
            $cells = [];
            foreach (array_values($row) as $colIndex => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $ref = $this->columnName($colIndex) . ($rowIndex + 1);
                if (is_int($value) || is_float($value)) {
                    $cells[] = sprintf('<c r="%s"><v>%s</v></c>', $ref, $value);
                    continue;
                }

                $cells[] = sprintf(
                    '<c r="%s" t="inlineStr"><is><t>%s</t></is></c>',
                    $ref,
                    htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8')
                );
            }

            $xmlRows[] = sprintf('<row r="%d">%s</row>', $rowIndex + 1, implode('', $cells));
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<dimension ref="' . $dimension . '"/>'
            . '<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . '<sheetData>' . implode('', $xmlRows) . '</sheetData>'
            . '</worksheet>';
    }

    private function loadXml(string $xml): SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);
        $parsed = simplexml_load_string($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$parsed) {
            throw new RuntimeException('Invalid workbook XML.');
        }

        return $parsed;
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '</Types>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    private function workbookXml(string $sheetName): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . htmlspecialchars($sheetName, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>'
            . '</styleSheet>';
    }

    private function coreXml(): string
    {
        $now = gmdate('Y-m-d\TH:i:s\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            . 'xmlns:dc="http://purl.org/dc/elements/1.1/" '
            . 'xmlns:dcterms="http://purl.org/dc/terms/" '
            . 'xmlns:dcmitype="http://purl.org/dc/dcmitype/" '
            . 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:creator>Fujimart HRM</dc:creator>'
            . '<cp:lastModifiedBy>Fujimart HRM</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:modified>'
            . '</cp:coreProperties>';
    }

    private function appXml(string $sheetName): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" '
            . 'xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>Fujimart HRM</Application>'
            . '<TitlesOfParts><vt:vector size="1" baseType="lpstr"><vt:lpstr>'
            . htmlspecialchars($sheetName, ENT_XML1 | ENT_COMPAT, 'UTF-8')
            . '</vt:lpstr></vt:vector></TitlesOfParts>'
            . '</Properties>';
    }
}
