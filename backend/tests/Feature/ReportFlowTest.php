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

    public function test_list_report_templates(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->getJson('/api/reports/templates');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertCount(6, $data);

        // First template should have code and name
        $first = $data[0];
        $this->assertArrayHasKey('code', $first);
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('description', $first);
        $this->assertSame('RPT_ATTENDANCE_DAILY', $first['code']);
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
}
