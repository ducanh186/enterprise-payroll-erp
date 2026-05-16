<?php

namespace Tests\Feature;

use App\Services\AttendanceService;
use App\Services\CustomerProcedureService;
use App\Services\ReferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FujimartSourceSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.customer_sqlsrv.database' => null]);
    }

    public function test_reference_shifts_read_from_d20_shift_source_table(): void
    {
        $this->createFujimartReferenceObjects();

        DB::table('D20Shift')->insert([
            'Id' => 901,
            'Code' => 'SRC01',
            'Name' => 'Source Morning Shift',
            'Description' => 'Shift from Fujimart source schema',
            'IsCheckIn' => 1,
            'StartTime' => '2026-01-01 08:15:00',
            'IsCheckOut' => 1,
            'EndTime' => '2026-01-01 17:45:00',
            'ShiftBreakMins' => 75,
            'WorkDay' => 1,
            'WorkingHours' => 8.5,
            'StartWorkingNightTime' => null,
            'EndWorkingNightTime' => null,
            'IsActive' => 1,
        ]);

        $shifts = app(ReferenceService::class)->getShifts();

        $this->assertCount(1, $shifts);
        $this->assertSame('D20Shift', $shifts[0]['source_table']);
        $this->assertSame('SRC01', $shifts[0]['id']);
        $this->assertSame('SRC01', $shifts[0]['code']);
        $this->assertSame('Source Morning Shift', $shifts[0]['name']);
        $this->assertSame('08:15', $shifts[0]['start_time']);
        $this->assertSame('17:45', $shifts[0]['end_time']);
        $this->assertSame(75, $shifts[0]['break_minutes']);
        $this->assertSame(8.5, $shifts[0]['working_hours']);
        $this->assertTrue($shifts[0]['is_checkin']);
        $this->assertTrue($shifts[0]['is_checkout']);
    }

    public function test_reference_payroll_parameters_return_two_fujimart_view_groups(): void
    {
        $this->createFujimartReferenceObjects();

        DB::table('D20PayrollParameter')->insert([
            [
                'Id' => 11,
                'Parameter' => 'STANDARD_DAYS',
                'Name' => 'Standard days',
                'Description' => 'Value parameter',
                'EffectiveDate' => '2026-01-01',
                'Type' => 'VALUE',
                'Amount' => 26,
                'IsActive' => 1,
            ],
            [
                'Id' => 12,
                'Parameter' => 'BASE_SALARY',
                'Name' => 'Base salary',
                'Description' => 'Salary type',
                'EffectiveDate' => '2026-01-01',
                'Type' => 'SALARY',
                'Amount' => 1000000,
                'IsActive' => 1,
            ],
        ]);

        $parameters = app(ReferenceService::class)->getPayrollParameters();

        $this->assertArrayHasKey('value_parameters', $parameters);
        $this->assertArrayHasKey('salary_types', $parameters);
        $this->assertSame('vD20PayrollPara_ValuePara', $parameters['value_parameters'][0]['source_view']);
        $this->assertSame('STANDARD_DAYS', $parameters['value_parameters'][0]['code']);
        $this->assertSame(26.0, $parameters['value_parameters'][0]['value']);
        $this->assertSame('vD20PayrollPara_SalaryType', $parameters['salary_types'][0]['source_view']);
        $this->assertSame('BASE_SALARY', $parameters['salary_types'][0]['code']);
    }

    public function test_shift_assignments_read_from_d30_assigned_shift_business_keys(): void
    {
        $this->createFujimartAttendanceObjects();

        DB::table('D20Employee')->insert([
            'Code' => 'NV900',
            'FullName' => 'Nguyen Source',
        ]);
        DB::table('D20Shift')->insert([
            'Code' => 'SRC01',
            'Name' => 'Source Morning Shift',
        ]);
        DB::table('D30AssignedShift')->insert([
            'Id' => 51,
            'AssignId' => 'AS9001',
            'Date' => '2026-01-01',
            'EmployeeCode' => 'NV900',
            'ShiftCode' => 'SRC01',
            'StartDate' => '2026-01-01',
            'EndDate' => '2026-01-31',
            'IncludeMon' => 1,
            'IncludeTue' => 1,
            'IncludeWed' => 1,
            'IncludeThu' => 1,
            'IncludeFri' => 1,
            'IncludeSat' => 0,
            'IncludeSun' => 0,
            'Description' => 'Source assignment',
            'IsActive' => 1,
        ]);

        $assignments = app(AttendanceService::class)->getShiftAssignments();

        $this->assertCount(1, $assignments);
        $this->assertSame('D30AssignedShift', $assignments[0]['source_table']);
        $this->assertSame('AS9001', $assignments[0]['assign_id']);
        $this->assertSame('NV900', $assignments[0]['employee_code']);
        $this->assertSame('Nguyen Source', $assignments[0]['employee_name']);
        $this->assertSame('SRC01', $assignments[0]['shift_code']);
        $this->assertSame('Source Morning Shift', $assignments[0]['shift_name']);
        $this->assertSame('2026-01-01', $assignments[0]['start_date']);
        $this->assertSame(1, $assignments[0]['include_mon']);
    }

    public function test_attendance_recalculate_confirms_d30_attendance_output(): void
    {
        $this->createFujimartCalculationObjects();

        $fake = new class {
            public array $calls = [];

            public function execute(string $procedureName, array $parameters): array
            {
                $this->calls[] = compact('procedureName', 'parameters');

                DB::table('D30Attendance')->insert([
                    'Id' => 77,
                    'DocId' => 'AT900',
                    'Date' => '2026-01-01',
                    'AssignId' => 'AS9001',
                    'WorkingHours' => 8,
                    'WorkingDays' => 1,
                    'ExcludeDays' => 0,
                    'ExcludeHours' => 0,
                    'WorkNightHours' => 0,
                    'ShiftMeal' => 1,
                    'PaidLeaveDays' => 0,
                    'UnpaidLeaveDays' => 0,
                    'IsActive' => 1,
                ]);

                return [
                    'available' => true,
                    'procedure' => $procedureName,
                    'result_sets' => [],
                    'row_count' => 1,
                    'execution_ms' => 12,
                    'error' => null,
                ];
            }
        };
        app()->instance(CustomerProcedureService::class, $fake);

        $result = app(AttendanceService::class)->recalculate([
            'month' => 1,
            'year' => 2026,
            'branch_code' => 'A01',
        ]);

        $this->assertSame('stored_procedure', $result['execution_mode']);
        $this->assertSame('dbo.usp_CreateAndCalculateAttendance', $fake->calls[0]['procedureName']);
        $this->assertSame('D30Attendance', $result['source_table']);
        $this->assertSame(1, $result['d30_attendance_count']);
    }

    public function test_payroll_calculate_uses_confirmed_procedure_and_d30_payroll_output(): void
    {
        $this->createFujimartCalculationObjects();

        $fake = new class {
            public array $calls = [];

            public function execute(string $procedureName, array $parameters): array
            {
                $this->calls[] = compact('procedureName', 'parameters');

                DB::table('D30Payroll')->insert([
                    'Id' => 88,
                    'RowId' => 'PR9001',
                    'Date' => '2026-01-01',
                    'BranchCode' => 'A01',
                    'DeptCode' => 'D01',
                    'EmployeeCode' => 'NV900',
                    'GrossSalary' => 10000000,
                    'NetIncome' => 8900000,
                    'TaxableIncome' => 9500000,
                    'PersonalIncomeTaxAmount' => 100000,
                    'SocialInsEMPLPay' => 700000,
                    'HealthInsEMPLPay' => 150000,
                    'UnemployedInsEMPLPay' => 50000,
                    'IsActive' => 1,
                ]);
                DB::table('D30PayrollDetail')->insert([
                    'Id' => 89,
                    'RowIdPR' => 'PR9001',
                    'BranchCode' => 'A01',
                    'SalaryType' => 'BASE_SALARY',
                    'BuiltinOrder' => 1,
                    'Coeff' => 1,
                    'Amount' => 10000000,
                    'Days' => 26,
                    'Hours' => 208,
                    'IsActive' => 1,
                ]);

                return [
                    'available' => true,
                    'procedure' => $procedureName,
                    'result_sets' => [],
                    'row_count' => 1,
                    'execution_ms' => 20,
                    'error' => null,
                ];
            }
        };
        app()->instance(CustomerProcedureService::class, $fake);

        $result = app(\App\Services\PayrollService::class)->calculateRun([
            'month' => 1,
            'year' => 2026,
            'branch_code' => 'A01',
        ]);

        $this->assertSame('stored_procedure', $result['execution_mode']);
        $this->assertSame('dbo.usp_CreateAndCalculatePayroll', $fake->calls[0]['procedureName']);
        $this->assertArrayHasKey('@_UserId', $fake->calls[0]['parameters']);
        $this->assertSame('D30Payroll', $result['source_table']);
        $this->assertSame('D30PayrollDetail', $result['detail_source_table']);
        $this->assertSame(1, $result['summary']['total_employees']);
        $this->assertSame(8900000.0, $result['summary']['total_net_salary']);
        $this->assertSame('PR9001', $result['items'][0]['id']);
        $this->assertSame('BASE_SALARY', $result['items'][0]['items'][0]['item_code']);
    }

    private function createFujimartReferenceObjects(): void
    {
        DB::statement('CREATE TABLE D20Shift (
            Id INTEGER,
            Code VARCHAR(16) NOT NULL,
            Name VARCHAR(128) NOT NULL,
            Description VARCHAR(256) NULL,
            IsCheckIn INTEGER NULL,
            StartTime DATETIME NULL,
            IsCheckOut INTEGER NULL,
            EndTime DATETIME NULL,
            ShiftBreakMins INTEGER NULL,
            WorkDay NUMERIC NULL,
            WorkingHours NUMERIC NULL,
            StartWorkingNightTime DATETIME NULL,
            EndWorkingNightTime DATETIME NULL,
            IsActive INTEGER NOT NULL DEFAULT 1
        )');

        DB::statement('CREATE TABLE D20PayrollParameter (
            Id INTEGER,
            Parameter VARCHAR(64) NOT NULL,
            Name VARCHAR(128) NOT NULL,
            Description VARCHAR(128) NOT NULL,
            EffectiveDate DATE NULL,
            Type VARCHAR(32) NOT NULL,
            Amount NUMERIC NOT NULL,
            IsActive INTEGER NOT NULL DEFAULT 1
        )');

        DB::statement("CREATE VIEW vD20PayrollPara_ValuePara AS
            SELECT * FROM D20PayrollParameter WHERE Type = 'VALUE'");
        DB::statement("CREATE VIEW vD20PayrollPara_SalaryType AS
            SELECT * FROM D20PayrollParameter WHERE Type = 'SALARY'");
    }

    private function createFujimartAttendanceObjects(): void
    {
        DB::statement('CREATE TABLE D20Employee (
            Code VARCHAR(16) NOT NULL,
            FullName VARCHAR(128) NOT NULL
        )');
        DB::statement('CREATE TABLE D20Shift (
            Code VARCHAR(16) NOT NULL,
            Name VARCHAR(128) NOT NULL
        )');
        DB::statement('CREATE TABLE D30AssignedShift (
            Id INTEGER,
            AssignId VARCHAR(16) NULL,
            Date DATE NULL,
            EmployeeCode VARCHAR(16) NULL,
            ShiftCode VARCHAR(16) NULL,
            StartDate DATE NULL,
            EndDate DATE NULL,
            IncludeMon INTEGER NULL,
            IncludeTue INTEGER NULL,
            IncludeWed INTEGER NULL,
            IncludeThu INTEGER NULL,
            IncludeFri INTEGER NULL,
            IncludeSat INTEGER NULL,
            IncludeSun INTEGER NULL,
            Description VARCHAR(512) NULL,
            IsActive INTEGER NOT NULL DEFAULT 1
        )');
    }

    private function createFujimartCalculationObjects(): void
    {
        DB::statement('CREATE TABLE D30Attendance (
            Id INTEGER,
            DocId VARCHAR(16) NULL,
            Date DATE NOT NULL,
            AssignId VARCHAR(16) NULL,
            WorkingHours NUMERIC NOT NULL,
            WorkingDays NUMERIC NOT NULL,
            ExcludeDays NUMERIC NOT NULL,
            ExcludeHours NUMERIC NOT NULL,
            WorkNightHours NUMERIC NOT NULL,
            ShiftMeal INTEGER NOT NULL,
            PaidLeaveDays NUMERIC NOT NULL,
            UnpaidLeaveDays NUMERIC NULL,
            IsActive INTEGER NOT NULL DEFAULT 1
        )');

        DB::statement('CREATE TABLE D30Payroll (
            Id INTEGER,
            RowId VARCHAR(16) NOT NULL,
            Date DATE NULL,
            BranchCode VARCHAR(3) NOT NULL,
            DeptCode VARCHAR(32) NOT NULL,
            EmployeeCode VARCHAR(32) NOT NULL,
            GrossSalary NUMERIC NOT NULL,
            NetIncome NUMERIC NOT NULL,
            TaxableIncome NUMERIC NOT NULL,
            PersonalIncomeTaxAmount NUMERIC NOT NULL,
            SocialInsEMPLPay NUMERIC NOT NULL,
            HealthInsEMPLPay NUMERIC NOT NULL,
            UnemployedInsEMPLPay NUMERIC NOT NULL,
            IsActive INTEGER NOT NULL DEFAULT 1
        )');

        DB::statement('CREATE TABLE D30PayrollDetail (
            Id INTEGER,
            RowIdPR VARCHAR(16) NOT NULL,
            BranchCode VARCHAR(3) NOT NULL,
            SalaryType VARCHAR(128) NOT NULL,
            BuiltinOrder INTEGER NOT NULL,
            Coeff NUMERIC NOT NULL,
            Amount NUMERIC NOT NULL,
            Days NUMERIC NOT NULL,
            Hours NUMERIC NOT NULL,
            IsActive INTEGER NOT NULL DEFAULT 1
        )');
    }
}
