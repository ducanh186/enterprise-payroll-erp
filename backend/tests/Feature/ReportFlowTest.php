<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportFlowTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function authHeaders(string $username = 'admin01'): array
    {
        $login = $this->postJson('/api/auth/login', [
            'username' => $username,
            'password' => 'password',
        ]);
        $login->assertOk();

        return ['Authorization' => 'Bearer ' . $login->json('data.token')];
    }

    public function test_list_report_templates_returns_only_fujimart_bfd_reports(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->getJson('/api/reports/templates');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertCount(3, $data);

        $this->assertSame([
            'FUJIMART_ATTENDANCE_REPORT',
            'FUJIMART_PAYROLL_REPORT',
            'FUJIMART_PAYROLL_SLIP',
        ], array_column($data, 'code'));
    }

    public function test_preview_and_export_fujimart_reports(): void
    {
        $headers = $this->authHeaders();

        foreach ([
            'FUJIMART_ATTENDANCE_REPORT',
            'FUJIMART_PAYROLL_REPORT',
            'FUJIMART_PAYROLL_SLIP',
        ] as $code) {
            $response = $this->withHeaders($headers)->postJson("/api/reports/{$code}/preview", [
                'date_from' => '2026-01-01',
                'date_to' => '2026-01-31',
                'employee_code' => 'NV001',
                'format' => 'xlsx',
            ]);

            $response->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.report_code', $code);

            $export = $this->withHeaders($headers)->postJson("/api/reports/{$code}/export", [
                'date_from' => '2026-01-01',
                'date_to' => '2026-01-31',
                'employee_code' => 'NV001',
                'format' => 'xlsx',
            ]);

            $export->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.report_code', $code)
                ->assertJsonPath('data.format', 'xlsx');

            $this->assertStringEndsWith('.xlsx', $export->json('data.file_name'));
            $this->assertGreaterThan(0, (int) $export->json('data.file_size'));
        }
    }

    public function test_fujimart_payroll_export_fills_the_template_sheet(): void
    {
        $headers = $this->authHeaders();

        $export = $this->withHeaders($headers)->postJson('/api/reports/FUJIMART_PAYROLL_REPORT/export', [
            'date_from' => '2026-01-01',
            'date_to' => '2026-01-31',
            'format' => 'xlsx',
        ]);

        $export->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.report_code', 'FUJIMART_PAYROLL_REPORT');

        $path = storage_path('app/public/reports/' . $export->json('data.file_name'));
        $sheets = $this->readWorkbookSheets($path);

        $this->assertArrayHasKey('TÍNH LƯƠNG', $sheets);

        $templateSheetText = implode('|', array_filter($sheets['TÍNH LƯƠNG']));
        $this->assertStringNotContainsString('{=@_Month}', $templateSheetText);
        $this->assertStringNotContainsString('{=@_Year}', $templateSheetText);
        $this->assertNotEmpty($sheets['TÍNH LƯƠNG']['A10'] ?? null);

        $allOtherSheetText = collect($sheets)
            ->except('TÍNH LƯƠNG')
            ->flatMap(fn (array $cells) => array_values($cells))
            ->implode('|');

        $this->assertStringNotContainsString('Fujimart HRM|Bảng thanh toán lương theo phòng ban|Generated at', $allOtherSheetText);
    }

    public function test_fujimart_payslip_export_updates_template_header(): void
    {
        $headers = $this->authHeaders();

        $export = $this->withHeaders($headers)->postJson('/api/reports/FUJIMART_PAYROLL_SLIP/export', [
            'date_from' => '2026-03-01',
            'date_to' => '2026-03-31',
            'format' => 'xlsx',
        ]);

        $export->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.report_code', 'FUJIMART_PAYROLL_SLIP');

        $path = storage_path('app/public/reports/' . $export->json('data.file_name'));
        $sheets = $this->readWorkbookSheets($path);

        $this->assertArrayHasKey('Phiếu lương cá nhân', $sheets);
        $this->assertSame('Tháng 3/2026', $sheets['Phiếu lương cá nhân']['A2'] ?? null);
    }

    public function test_send_fujimart_payroll_slip_email_uses_customer_procedure_contract(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->postJson('/api/payroll/payslips/email', [
            'doc_date' => '2026-05-05',
            'employee_code' => '',
            'department_code' => '',
            'branch_code' => 'A01,A02',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.report_code', 'FUJIMART_PAYROLL_SLIP')
            ->assertJsonPath('data.procedure', 'dbo.usp_PayrollSlip')
            ->assertJsonPath('data.parameters.@_DocDate1', '2026-05-05')
            ->assertJsonPath('data.parameters.@_EmployeeCode', '')
            ->assertJsonPath('data.parameters.@_DeptCode', '')
            ->assertJsonPath('data.parameters.@_BranchCode', 'A01,A02')
            ->assertJsonPath('data.parameters.@_SendEmail', 1)
            ->assertJsonPath('data.parameters.@_MailProfile', '');
    }

    public function test_preview_payslip_report(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->postJson('/api/reports/RPT_PAYSLIP/preview', [
            'month' => 2,
            'year' => 2026,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertSame('RPT_PAYSLIP', $data['report_code']);
        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('items', $data);
        $this->assertNotEmpty($data['summary']);
        $this->assertNotEmpty($data['items']);
    }

    public function test_preview_attendance_report(): void
    {
        $headers = $this->authHeaders();

        // RPT_ATTENDANCE_DAILY uses a date parameter; resolveDate falls back
        // to the latest attendance_daily work_date if no date is provided
        $response = $this->withHeaders($headers)->postJson('/api/reports/RPT_ATTENDANCE_DAILY/preview', [
            'date' => '2026-02-02',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertSame('RPT_ATTENDANCE_DAILY', $data['report_code']);
        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('generated_at', $data);
    }

    public function test_export_report(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->postJson('/api/reports/RPT_PAYSLIP/export', [
            'month' => 2,
            'year' => 2026,
            'format' => 'xlsx',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertArrayHasKey('report_code', $data);
        $this->assertSame('RPT_PAYSLIP', $data['report_code']);
        $this->assertArrayHasKey('format', $data);
        $this->assertSame('xlsx', $data['format']);
        $this->assertArrayHasKey('file_name', $data);
        $this->assertArrayHasKey('file_url', $data);
        $this->assertArrayHasKey('generated_at', $data);
    }

    public function test_all_reference_endpoints_return_data(): void
    {
        $headers = $this->authHeaders();

        $endpoints = [
            '/api/reference/shifts',
            '/api/reference/holidays',
            '/api/reference/contract-types',
            '/api/reference/payroll-types',
            '/api/reference/payroll-parameters',
            '/api/reference/late-early-rules',
            '/api/reference/departments',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->withHeaders($headers)->getJson($endpoint);

            $response->assertOk()
                ->assertJsonPath('success', true);

            $data = $response->json('data');
            $this->assertIsArray($data, "Expected data array for {$endpoint}");
            $this->assertNotEmpty($data, "Expected non-empty data for {$endpoint}");
        }
    }

    public function test_reference_shifts_contain_expected_fields(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->getJson('/api/reference/shifts');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $first = $data[0];
        $this->assertArrayHasKey('code', $first);
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('working_hours', $first);
        $this->assertArrayHasKey('start_time', $first);
        $this->assertArrayHasKey('end_time', $first);
        $this->assertArrayHasKey('is_night_shift', $first);
    }

    public function test_reference_departments_contain_expected_fields(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->getJson('/api/reference/departments');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $first = $data[0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('code', $first);
        $this->assertArrayHasKey('employee_count', $first);
        $this->assertArrayHasKey('is_active', $first);
    }

    public function test_salary_scales_expose_scale_grade_and_grade_detail_structure(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->getJson('/api/reference/salary-scales');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);

        $first = $data[0];
        $this->assertArrayHasKey('code', $first);
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('grades', $first);
        $this->assertNotEmpty($first['grades']);

        $grade = $first['grades'][0];
        $this->assertArrayHasKey('id', $grade);
        $this->assertArrayHasKey('salary_level', $grade);
        $this->assertArrayHasKey('details', $grade);
        $this->assertNotEmpty($grade['details']);
        $this->assertArrayHasKey('amount', $grade['details'][0]);
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function readWorkbookSheets(string $path): array
    {
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path) === true, "Cannot open workbook {$path}.");

        try {
            $sharedStrings = [];
            $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
            if ($sharedStringsXml !== false) {
                $sharedStringsDom = new \DOMDocument();
                $sharedStringsDom->loadXML($sharedStringsXml);
                $sharedStringsXpath = new \DOMXPath($sharedStringsDom);
                $sharedStringsXpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                foreach ($sharedStringsXpath->query('//x:si') ?: [] as $item) {
                    $texts = [];
                    foreach ($sharedStringsXpath->query('.//x:t', $item) ?: [] as $textNode) {
                        $texts[] = $textNode->textContent;
                    }
                    $sharedStrings[] = implode('', $texts);
                }
            }

            $workbook = simplexml_load_string($zip->getFromName('xl/workbook.xml'));
            $relationships = simplexml_load_string($zip->getFromName('xl/_rels/workbook.xml.rels'));
            $relationshipTargets = [];
            foreach ($relationships->Relationship as $relationship) {
                $relationshipTargets[(string) $relationship['Id']] = (string) $relationship['Target'];
            }

            $sheets = [];
            foreach ($workbook->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main')->sheets->sheet as $sheet) {
                $attributes = $sheet->attributes();
                $relAttributes = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                $name = (string) $attributes['name'];
                $target = $relationshipTargets[(string) $relAttributes['id']] ?? 'worksheets/sheet1.xml';
                $sheetPath = str_starts_with($target, 'xl/') ? $target : 'xl/' . ltrim($target, '/');
                $sheetDom = new \DOMDocument();
                $sheetDom->loadXML($zip->getFromName($sheetPath));
                $sheetXpath = new \DOMXPath($sheetDom);
                $sheetXpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                $cells = [];

                foreach ($sheetXpath->query('//x:c') ?: [] as $cell) {
                    if (!$cell instanceof \DOMElement) {
                        continue;
                    }

                    $ref = $cell->getAttribute('r');
                    $type = $cell->getAttribute('t');

                    if ($type === 's') {
                        $value = $sheetXpath->query('x:v', $cell)?->item(0)?->textContent ?? '';
                        $cells[$ref] = $sharedStrings[(int) $value] ?? '';
                    } elseif ($type === 'inlineStr') {
                        $inlineText = '';
                        foreach ($sheetXpath->query('.//x:t', $cell) ?: [] as $textNode) {
                            $inlineText .= $textNode->textContent;
                        }
                        $cells[$ref] = $inlineText;
                    } else {
                        $cells[$ref] = $sheetXpath->query('x:v', $cell)?->item(0)?->textContent ?? '';
                    }
                }

                $sheets[$name] = $cells;
            }

            return $sheets;
        } finally {
            $zip->close();
        }
    }
}
