<?php

namespace Tests\Feature;

use App\Services\EmployeeService;
use App\Services\ReferenceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceBackendGroup1Test extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_contract_detail_uses_insurance_allowances_and_probation_flags(): void
    {
        $employeeService = app(EmployeeService::class);

        $managerContract = $employeeService->getContract(1);
        $probationContract = $employeeService->getContract(12);

        $this->assertSame(27000000.0, $managerContract['insurance_salary']);
        $this->assertFalse($managerContract['is_probation']);
        $this->assertTrue($probationContract['is_probation']);
    }

    public function test_active_contract_requires_current_effective_dates(): void
    {
        Carbon::setTestNow('2026-03-21 09:00:00');

        try {
            $employeeService = app(EmployeeService::class);

            $this->assertNotNull($employeeService->getActiveContract(1));
            $this->assertNull($employeeService->getActiveContract(12));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_reference_service_returns_hours_descriptions_and_probation_days_from_seeded_data(): void
    {
        $referenceService = app(ReferenceService::class);

        $shift = collect($referenceService->getShifts())->firstWhere('code', 'HC08');
        $holiday = collect($referenceService->getHolidays())->firstWhere('id', 1);
        $contractType = collect($referenceService->getContractTypes())->firstWhere('code', 'THU_VIEC');
        $payrollType = collect($referenceService->getPayrollTypes())->firstWhere('code', 'LUONG_THU_VIEC');

        $this->assertSame(8.0, $shift['working_hours']);
        $this->assertSame(3.0, $holiday['multiplier']);
        $this->assertSame(60, $contractType['max_probation_days']);
        $this->assertSame('Tinh luong ap dung cho nhan vien thu viec', $payrollType['description']);
    }
}
