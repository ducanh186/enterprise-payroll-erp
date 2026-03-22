<?php

namespace Tests\Feature;

use App\Enums\AttendancePeriodStatus;
use App\Enums\PayrollRunStatus;
use App\Models\AttendancePeriod;
use App\Models\Payslip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollApiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_payroll_preview_finalize_lock_and_payslip_detail_use_persisted_db_data(): void
    {
        $headers = $this->authHeaders();

        $preview = $this->withHeaders($headers)->postJson('/api/payroll/runs/preview', [
            'month' => 2,
            'year' => 2026,
            'scope' => 'all',
        ]);

        $preview->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', PayrollRunStatus::PREVIEWED->value)
            ->assertJsonPath('data.period.period_code', '2026-02');

        $runId = (int) $preview->json('data.id');
        $this->assertGreaterThan(0, $runId);
        $this->assertDatabaseHas('payroll_runs', [
            'id' => $runId,
            'status' => PayrollRunStatus::PREVIEWED->value,
        ]);

        $payslipId = (int) Payslip::query()
            ->where('payroll_run_id', $runId)
            ->orderBy('id')
            ->value('id');
        $this->assertGreaterThan(0, $payslipId);

        $this->assertGreaterThan(
            0,
            Payslip::query()->where('payroll_run_id', $runId)->count()
        );

        $finalize = $this->withHeaders($headers)->postJson("/api/payroll/runs/{$runId}/finalize");
        $finalize->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', PayrollRunStatus::FINALIZED->value);

        $this->assertDatabaseHas('payroll_runs', [
            'id' => $runId,
            'status' => PayrollRunStatus::FINALIZED->value,
        ]);

        $lock = $this->withHeaders($headers)->postJson("/api/payroll/runs/{$runId}/lock");
        $lock->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', PayrollRunStatus::LOCKED->value);

        $this->assertDatabaseHas('payroll_runs', [
            'id' => $runId,
            'status' => PayrollRunStatus::LOCKED->value,
        ]);

        $period = AttendancePeriod::query()->where('month', 2)->where('year', 2026)->firstOrFail();
        $this->assertDatabaseHas('attendance_periods', [
            'id' => $period->id,
            'status' => AttendancePeriodStatus::LOCKED->value,
        ]);

        $detail = $this->withHeaders($headers)->getJson("/api/payroll/payslips/{$payslipId}/details");
        $detail->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $payslipId)
            ->assertJsonPath('data.status', PayrollRunStatus::LOCKED->value);

        $this->assertNotEmpty($detail->json('data.items'));
        $this->assertNotEmpty($detail->json('data.items_by_group'));
        $this->assertNotNull($detail->json('data.contract'));
        $this->assertNotNull($detail->json('data.attendance'));
    }

    private function authHeaders(): array
    {
        $login = $this->postJson('/api/auth/login', [
            'username' => 'admin01',
            'password' => 'password',
        ]);

        $login->assertOk();

        return [
            'Authorization' => 'Bearer '.$login->json('data.token'),
        ];
    }
}
