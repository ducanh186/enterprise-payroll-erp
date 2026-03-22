<?php

namespace Tests\Feature;

use App\Services\AttendanceService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceBackendGroup2Test extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_report_templates_come_from_database(): void
    {
        $templates = app(ReportService::class)->getTemplates();

        $this->assertCount(6, $templates);
        $this->assertSame('RPT_ATTENDANCE_DAILY', $templates[0]['code']);
        $this->assertSame('RPT_PAYROLL_SUMMARY', $templates[2]['code']);
    }

    public function test_attendance_checkin_logs_keep_paginated_shape(): void
    {
        $result = app(AttendanceService::class)->getCheckinLogs(['per_page' => 1]);

        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('per_page', $result);
        $this->assertArrayHasKey('current_page', $result);
        $this->assertSame(1, $result['per_page']);
    }

    public function test_report_preview_uses_real_payslip_data(): void
    {
        $result = app(ReportService::class)->previewReport('RPT_PAYSLIP', [
            'month' => 2,
            'year' => 2026,
        ]);

        $this->assertSame('RPT_PAYSLIP', $result['report_code']);
        $this->assertNotEmpty($result['summary']);
        $this->assertNotEmpty($result['items']);
    }
}
