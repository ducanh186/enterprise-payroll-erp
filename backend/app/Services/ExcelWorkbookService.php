<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
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

    /**
     * Fill rows into the first worksheet of an existing template while keeping
     * layout, styles, merged cells, and other workbook sheets intact.
     *
     * @param array<int, array<int, mixed>> $rows
     * @param array<string, mixed> $replacements
     * @param array<string, mixed> $cellValues
     */
    public function writeTemplateRows(
        string $path,
        array $rows,
        array $replacements,
        string $templatePath,
        int $startRow,
        array $cellValues = []
    ): void {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Cannot create directory {$directory}.");
        }

        copy($templatePath, $path);
        $zip = $this->openZip($path);

        try {
            $sharedStrings = $this->readSharedStrings($zip);
            $sheetPath = $this->firstWorkbookSheetPath($zip);
            $xml = $zip->getFromName($sheetPath);

            if ($xml === false) {
                throw new RuntimeException("Worksheet not found in {$path}.");
            }

            $dom = $this->loadDom($xml);
            $xpath = $this->worksheetXpath($dom);
            $this->replaceTemplateVariables($dom, $xpath, $sharedStrings, $replacements);
            $this->applyTemplateCellValues($dom, $xpath, $cellValues);

            if ($rows !== []) {
                $this->fillTemplateRows($dom, $xpath, $rows, $startRow);
            }

            $zip->deleteName($sheetPath);
            $zip->addFromString($sheetPath, $dom->saveXML());
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

    private function firstWorkbookSheetPath(ZipArchive $zip): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relsXml === false) {
            return $this->firstWorksheetPath($zip);
        }

        $workbook = $this->loadXml($workbookXml);
        $workbook->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $workbook->registerXPathNamespace('rel', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $firstSheet = ($workbook->xpath('//main:sheets/main:sheet') ?: [])[0] ?? null;

        if (!$firstSheet) {
            return $this->firstWorksheetPath($zip);
        }

        $relAttributes = $firstSheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $relationshipId = (string) ($relAttributes['id'] ?? '');
        $rels = $this->loadXml($relsXml);
        $rels->registerXPathNamespace('pkg', 'http://schemas.openxmlformats.org/package/2006/relationships');

        foreach (($rels->xpath('//pkg:Relationship') ?: []) as $relationship) {
            if ((string) $relationship['Id'] !== $relationshipId) {
                continue;
            }

            $target = ltrim((string) $relationship['Target'], '/');

            return str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
        }

        return $this->firstWorksheetPath($zip);
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

    private function loadDom(string $xml): DOMDocument
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            throw new RuntimeException('Invalid workbook XML.');
        }

        return $dom;
    }

    private function worksheetXpath(DOMDocument $dom): DOMXPath
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        return $xpath;
    }

    /**
     * @param array<int, string> $sharedStrings
     * @param array<string, mixed> $replacements
     */
    private function replaceTemplateVariables(DOMDocument $dom, DOMXPath $xpath, array $sharedStrings, array $replacements): void
    {
        foreach ($xpath->query('//x:c') ?: [] as $cell) {
            if (!$cell instanceof DOMElement) {
                continue;
            }

            $text = $this->domCellValue($xpath, $cell, $sharedStrings);
            if (!str_contains($text, '{=')) {
                continue;
            }

            $updated = preg_replace_callback('/\{=([^}]+)\}/', function (array $match) use ($replacements) {
                $token = trim($match[1]);

                return (string) ($replacements[$token] ?? $replacements[ltrim($token, '@_')] ?? '');
            }, $text);

            $this->setDomCellValue($dom, $cell, $updated);
        }
    }

    /**
     * @param array<string, mixed> $cellValues
     */
    private function applyTemplateCellValues(DOMDocument $dom, DOMXPath $xpath, array $cellValues): void
    {
        foreach ($cellValues as $ref => $value) {
            if (!preg_match('/^([A-Z]+)([0-9]+)$/', strtoupper($ref), $matches)) {
                continue;
            }

            $rowNumber = (int) $matches[2];
            $columnIndex = $this->columnIndexFromRef($matches[1]);
            $sheetData = $xpath->query('//x:sheetData')->item(0);
            if (!$sheetData instanceof DOMElement) {
                continue;
            }

            $row = $this->findDomRow($xpath, $rowNumber) ?? $this->createDomRow($dom, $sheetData, $rowNumber);
            $cell = $this->ensureDomCell($dom, $row, $columnIndex, $rowNumber);
            $this->setDomCellValue($dom, $cell, $value);
        }
    }

    /**
     * @param array<int, string> $sharedStrings
     */
    private function domCellValue(DOMXPath $xpath, DOMElement $cell, array $sharedStrings): string
    {
        $type = $cell->getAttribute('t');

        if ($type === 's') {
            $value = $xpath->query('x:v', $cell)?->item(0)?->textContent;

            return $sharedStrings[(int) $value] ?? '';
        }

        if ($type === 'inlineStr') {
            return $xpath->query('x:is//x:t', $cell)?->item(0)?->textContent ?? '';
        }

        return $xpath->query('x:v', $cell)?->item(0)?->textContent ?? '';
    }

    /**
     * @param array<int, array<int, mixed>> $rows
     */
    private function fillTemplateRows(DOMDocument $dom, DOMXPath $xpath, array $rows, int $startRow): void
    {
        $sheetData = $xpath->query('//x:sheetData')->item(0);
        if (!$sheetData instanceof DOMElement) {
            throw new RuntimeException('Worksheet sheetData not found.');
        }

        $templateRow = $this->findDomRow($xpath, $startRow) ?? $this->createDomRow($dom, $sheetData, $startRow);
        $templateClone = $templateRow->cloneNode(true);
        $extraRows = max(0, count($rows) - 1);

        if ($extraRows > 0) {
            $this->shiftRowsAfter($xpath, $startRow, $extraRows);
            $this->shiftMergedCells($xpath, $startRow + 1, $extraRows);
        }

        foreach ($rows as $offset => $values) {
            $rowNumber = $startRow + $offset;
            $row = $offset === 0
                ? $this->findDomRow($xpath, $rowNumber)
                : $templateClone->cloneNode(true);

            if (!$row instanceof DOMElement) {
                $row = $this->createDomRow($dom, $sheetData, $rowNumber);
            }

            $this->setDomRowNumber($row, $rowNumber);

            if ($offset > 0) {
                $this->insertRowInOrder($sheetData, $row);
            }

            foreach (array_values($values) as $columnIndex => $value) {
                $cell = $this->ensureDomCell($dom, $row, $columnIndex, $rowNumber);
                $this->setDomCellValue($dom, $cell, $value);
            }
        }

        $this->updateDimension($xpath);
    }

    private function findDomRow(DOMXPath $xpath, int $rowNumber): ?DOMElement
    {
        $row = $xpath->query(sprintf('//x:sheetData/x:row[@r="%d"]', $rowNumber))->item(0);

        return $row instanceof DOMElement ? $row : null;
    }

    private function createDomRow(DOMDocument $dom, DOMElement $sheetData, int $rowNumber): DOMElement
    {
        $row = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'row');
        $row->setAttribute('r', (string) $rowNumber);
        $this->insertRowInOrder($sheetData, $row);

        return $row;
    }

    private function insertRowInOrder(DOMElement $sheetData, DOMElement $row): void
    {
        $rowNumber = (int) $row->getAttribute('r');
        foreach ($sheetData->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === 'row' && (int) $child->getAttribute('r') > $rowNumber) {
                $sheetData->insertBefore($row, $child);

                return;
            }
        }

        $sheetData->appendChild($row);
    }

    private function shiftRowsAfter(DOMXPath $xpath, int $startRow, int $offset): void
    {
        $rows = iterator_to_array($xpath->query('//x:sheetData/x:row') ?: []);
        usort($rows, fn (DOMElement $a, DOMElement $b) => (int) $b->getAttribute('r') <=> (int) $a->getAttribute('r'));

        foreach ($rows as $row) {
            if (!$row instanceof DOMElement || (int) $row->getAttribute('r') <= $startRow) {
                continue;
            }

            $this->setDomRowNumber($row, (int) $row->getAttribute('r') + $offset);
        }
    }

    private function setDomRowNumber(DOMElement $row, int $rowNumber): void
    {
        $row->setAttribute('r', (string) $rowNumber);
        foreach ($row->getElementsByTagName('c') as $cell) {
            if ($cell instanceof DOMElement) {
                $cell->setAttribute('r', $this->columnLettersFromRef($cell->getAttribute('r')) . $rowNumber);
            }
        }
    }

    private function ensureDomCell(DOMDocument $dom, DOMElement $row, int $columnIndex, int $rowNumber): DOMElement
    {
        $ref = $this->columnName($columnIndex) . $rowNumber;
        foreach ($row->getElementsByTagName('c') as $cell) {
            if ($cell instanceof DOMElement && $cell->getAttribute('r') === $ref) {
                return $cell;
            }
        }

        $cell = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'c');
        $cell->setAttribute('r', $ref);

        foreach ($row->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === 'c') {
                $childColumnIndex = $this->columnIndexFromRef($child->getAttribute('r'));
                if ($childColumnIndex > $columnIndex) {
                    $row->insertBefore($cell, $child);

                    return $cell;
                }
            }
        }

        $row->appendChild($cell);

        return $cell;
    }

    private function setDomCellValue(DOMDocument $dom, DOMElement $cell, mixed $value): void
    {
        foreach (iterator_to_array($cell->childNodes) as $child) {
            $cell->removeChild($child);
        }

        if ($value === null || $value === '') {
            $cell->removeAttribute('t');

            return;
        }

        if (is_int($value) || is_float($value)) {
            $cell->removeAttribute('t');
            $cell->appendChild($dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'v', (string) $value));

            return;
        }

        $text = (string) $value;
        if (is_numeric($text) && !preg_match('/^0\d+$/', $text)) {
            $cell->removeAttribute('t');
            $cell->appendChild($dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'v', $text));

            return;
        }

        $cell->setAttribute('t', 'inlineStr');
        $inlineString = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'is');
        $textNode = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 't');
        $textNode->appendChild($dom->createTextNode($text));
        $inlineString->appendChild($textNode);
        $cell->appendChild($inlineString);
    }

    private function shiftMergedCells(DOMXPath $xpath, int $fromRow, int $offset): void
    {
        foreach ($xpath->query('//x:mergeCell') ?: [] as $mergeCell) {
            if (!$mergeCell instanceof DOMElement) {
                continue;
            }

            $mergeCell->setAttribute('ref', preg_replace_callback(
                '/([A-Z]+)(\d+)/',
                fn (array $match) => $match[1] . (((int) $match[2] >= $fromRow) ? ((int) $match[2] + $offset) : (int) $match[2]),
                $mergeCell->getAttribute('ref')
            ));
        }
    }

    private function updateDimension(DOMXPath $xpath): void
    {
        $maxRow = 1;
        $maxColumn = 0;

        foreach ($xpath->query('//x:c') ?: [] as $cell) {
            if (!$cell instanceof DOMElement) {
                continue;
            }

            $ref = $cell->getAttribute('r');
            $maxRow = max($maxRow, (int) preg_replace('/[^0-9]/', '', $ref));
            $maxColumn = max($maxColumn, $this->columnIndexFromRef($ref));
        }

        $dimension = $xpath->query('//x:dimension')->item(0);
        if ($dimension instanceof DOMElement) {
            $dimension->setAttribute('ref', 'A1:' . $this->columnName($maxColumn) . $maxRow);
        }
    }

    private function columnLettersFromRef(string $ref): string
    {
        return preg_replace('/[^A-Z]/', '', strtoupper($ref)) ?: 'A';
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
