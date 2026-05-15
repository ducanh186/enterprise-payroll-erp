<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoVolumeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $this->seedEmployees($now);
        $this->seedDependents($now);
        $this->seedJanuaryAttendance($now);
        $this->seedJanuaryPayroll($now);
    }

    private function seedEmployees(Carbon $now): void
    {
        $names = [
            'Nguyen Minh Khoa', 'Tran Thu Ha', 'Le Quang Huy', 'Pham Anh Dao', 'Hoang Bao Ngoc',
            'Do Thanh Nam', 'Bui Kim Chi', 'Dang Viet Anh', 'Vo My Linh', 'Phan Hoai An',
            'Mai Duc Long', 'Trinh Gia Han', 'Cao Tuan Kiet', 'Ly Thanh Vy', 'Duong Minh Chau',
        ];
        $departmentIds = DB::table('departments')->orderBy('id')->pluck('id')->values()->all() ?: [1];
        $positionIds = DB::table('positions')->orderBy('id')->pluck('id')->values()->all() ?: [1];
        $banks = ['Vietcombank', 'BIDV', 'Techcombank', 'ACB', 'Vietinbank'];

        foreach ($names as $index => $name) {
            $number = $index + 16;
            $code = sprintf('NV%03d', $number);

            DB::table('employees')->updateOrInsert(
                ['employee_code' => $code],
                [
                    'user_id' => null,
                    'full_name' => $name,
                    'dob' => Carbon::create(1988 + ($index % 12), ($index % 12) + 1, (($index * 3) % 25) + 1)->toDateString(),
                    'gender' => $index % 3 === 0 ? 'male' : ($index % 3 === 1 ? 'female' : 'other'),
                    'national_id' => sprintf('079%09d', 860000 + $number),
                    'tax_code' => sprintf('840%07d', 1000 + $number),
                    'email' => strtolower(str_replace(' ', '.', $name)) . '@fujimart.local',
                    'phone' => sprintf('0912%06d', $number),
                    'bank_account_no' => sprintf('220100%06d', $number),
                    'bank_name' => $banks[$index % count($banks)],
                    'department_id' => $departmentIds[$index % count($departmentIds)],
                    'position_id' => $positionIds[$index % count($positionIds)],
                    'join_date' => Carbon::create(2021 + ($index % 4), ($index % 12) + 1, 1)->toDateString(),
                    'employment_status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    private function seedDependents(Carbon $now): void
    {
        $employees = DB::table('employees')
            ->where('employee_code', '>=', 'NV016')
            ->orderBy('employee_code')
            ->limit(15)
            ->get(['id', 'employee_code', 'full_name']);
        $relationships = ['child', 'spouse', 'parent'];
        $sequence = 1;

        foreach ($employees as $employee) {
            foreach ([1, 2] as $slot) {
                DB::table('dependents')->updateOrInsert(
                    [
                        'employee_id' => $employee->id,
                        'full_name' => "Nguoi phu thuoc {$slot} {$employee->employee_code}",
                    ],
                    [
                        'dob' => Carbon::create(2010 + ($sequence % 10), ($sequence % 12) + 1, (($sequence * 2) % 25) + 1)->toDateString(),
                        'relationship' => $relationships[$sequence % count($relationships)],
                        'national_id' => sprintf('079%09d', 990000 + $sequence),
                        'tax_reduction_from' => '2026-01-01',
                        'tax_reduction_to' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
                $sequence++;
            }
        }
    }

    private function seedJanuaryAttendance(Carbon $now): void
    {
        $periodId = $this->resolvePeriod('2026-01', 1, 2026, 'locked', $now);
        $employees = DB::table('employees')->orderBy('employee_code')->limit(30)->get(['id']);
        $workDays = $this->workDays(2026, 1);

        foreach ($employees as $employeeIndex => $employee) {
            foreach ($workDays as $dayIndex => $date) {
                $late = ($employeeIndex + $dayIndex) % 9 === 0 ? 15 + (($employeeIndex % 3) * 5) : 0;
                $early = ($employeeIndex + $dayIndex) % 13 === 0 ? 10 : 0;
                $status = ($employeeIndex + $dayIndex) % 17 === 0 ? 'leave' : 'present';
                $hours = $status === 'leave' ? 0 : 8;

                DB::table('attendance_daily')->updateOrInsert(
                    ['employee_id' => $employee->id, 'work_date' => $date],
                    [
                        'attendance_period_id' => $periodId,
                        'shift_assignment_id' => null,
                        'first_in' => $status === 'leave' ? null : "{$date} " . sprintf('08:%02d:00', $late),
                        'last_out' => $status === 'leave' ? null : "{$date} " . sprintf('17:%02d:00', max(0, 30 - $early)),
                        'late_minutes' => $late,
                        'early_minutes' => $early,
                        'regular_hours' => $hours,
                        'ot_hours' => ($employeeIndex + $dayIndex) % 11 === 0 ? 1.5 : 0,
                        'night_hours' => 0,
                        'workday_value' => $status === 'leave' ? 0 : 1,
                        'meal_count' => $status === 'leave' ? 0 : 1,
                        'attendance_status' => $status,
                        'source_status' => $late || $early ? 'manual_review' : 'calculated',
                        'is_confirmed_by_employee' => false,
                        'calculation_version' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }

            $rows = DB::table('attendance_daily')
                ->where('attendance_period_id', $periodId)
                ->where('employee_id', $employee->id)
                ->get();

            DB::table('attendance_monthly_summary')->updateOrInsert(
                ['attendance_period_id' => $periodId, 'employee_id' => $employee->id],
                [
                    'total_workdays' => $rows->sum('workday_value'),
                    'regular_hours' => $rows->sum('regular_hours'),
                    'ot_hours' => $rows->sum('ot_hours'),
                    'night_hours' => $rows->sum('night_hours'),
                    'paid_leave_days' => $rows->where('attendance_status', 'leave')->count(),
                    'unpaid_leave_days' => 0,
                    'late_minutes' => $rows->sum('late_minutes'),
                    'early_minutes' => $rows->sum('early_minutes'),
                    'meal_count' => $rows->sum('meal_count'),
                    'status' => 'confirmed',
                    'generated_at' => $now,
                    'confirmed_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    private function seedJanuaryPayroll(Carbon $now): void
    {
        $periodId = $this->resolvePeriod('2026-01', 1, 2026, 'locked', $now);
        $runId = DB::table('payroll_runs')->where('attendance_period_id', $periodId)->orderBy('id')->value('id');

        if (!$runId) {
            $runId = DB::table('payroll_runs')->insertGetId([
                'attendance_period_id' => $periodId,
                'run_no' => 1,
                'scope_type' => 'all',
                'scope_value' => null,
                'status' => 'previewed',
                'requested_by' => 4,
                'previewed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $employees = DB::table('employees')->orderBy('employee_code')->limit(30)->get(['id']);

        foreach ($employees as $index => $employee) {
            $base = 8500000 + (($index % 8) * 1250000);
            $bonus = ($index % 4) * 350000;
            $deduction = ($index % 5) * 120000;
            $insuranceEmployee = round($base * 0.105);
            $insuranceCompany = round($base * 0.215);
            $gross = $base + 1230000 + $bonus;
            $taxable = max(0, $gross - $insuranceEmployee - 11000000);
            $pit = $taxable > 0 ? round($taxable * 0.05) : 0;
            $net = $gross - $insuranceEmployee - $pit - $deduction;
            $contractId = DB::table('labour_contracts')->where('employee_id', $employee->id)->value('id');

            DB::table('payslips')->updateOrInsert(
                ['payroll_run_id' => $runId, 'employee_id' => $employee->id],
                [
                    'attendance_period_id' => $periodId,
                    'contract_id' => $contractId,
                    'base_salary_snapshot' => $base,
                    'gross_salary' => $gross,
                    'taxable_income' => $taxable,
                    'insurance_base' => $base,
                    'insurance_employee' => $insuranceEmployee,
                    'insurance_company' => $insuranceCompany,
                    'pit_amount' => $pit,
                    'bonus_total' => $bonus,
                    'deduction_total' => $deduction,
                    'net_salary' => $net,
                    'status' => 'previewed',
                    'generated_at' => $now,
                    'locked_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );

            $payslipId = DB::table('payslips')->where('payroll_run_id', $runId)->where('employee_id', $employee->id)->value('id');
            if (DB::table('payslip_items')->where('payslip_id', $payslipId)->exists()) {
                continue;
            }

            foreach ($this->payrollItems($payslipId, $base, $bonus, $deduction, $insuranceEmployee, $pit) as $item) {
                DB::table('payslip_items')->insert($item);
            }
        }
    }

    private function payrollItems(int $payslipId, float $base, float $bonus, float $deduction, float $insuranceEmployee, float $pit): array
    {
        $rows = [
            ['BASE_SALARY', 'Luong co ban', 'earning', 1, $base, $base],
            ['ALW_MEAL', 'Phu cap an ca', 'earning', 1, 730000, 730000],
            ['ALW_TRANSPORT', 'Phu cap di lai', 'earning', 1, 500000, 500000],
            ['INS_EMPLOYEE', 'Bao hiem nguoi lao dong', 'deduction', 1, null, $insuranceEmployee],
        ];

        if ($bonus > 0) {
            $rows[] = ['BONUS_MONTH', 'Thuong thang', 'earning', 1, null, $bonus];
        }
        if ($deduction > 0) {
            $rows[] = ['DED_OTHER', 'Khau tru khac', 'deduction', 1, null, $deduction];
        }
        if ($pit > 0) {
            $rows[] = ['PIT', 'Thue TNCN', 'deduction', 1, null, $pit];
        }

        return array_map(fn (array $row, int $index) => [
            'payslip_id' => $payslipId,
            'item_code' => $row[0],
            'item_name' => $row[1],
            'item_group' => $row[2],
            'qty' => $row[3],
            'rate' => $row[4],
            'amount' => $row[5],
            'sort_order' => $index + 1,
            'source_ref' => 'demo-volume',
        ], $rows, array_keys($rows));
    }

    private function resolvePeriod(string $code, int $month, int $year, string $status, Carbon $now): int
    {
        DB::table('attendance_periods')->updateOrInsert(
            ['period_code' => $code],
            [
                'month' => $month,
                'year' => $year,
                'from_date' => Carbon::create($year, $month, 1)->startOfMonth()->toDateString(),
                'to_date' => Carbon::create($year, $month, 1)->endOfMonth()->toDateString(),
                'status' => $status,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        return (int) DB::table('attendance_periods')->where('period_code', $code)->value('id');
    }

    private function workDays(int $year, int $month): array
    {
        $cursor = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $cursor->copy()->endOfMonth();
        $days = [];

        while ($cursor->lte($end)) {
            if ($cursor->isWeekday()) {
                $days[] = $cursor->toDateString();
            }
            $cursor->addDay();
        }

        return $days;
    }
}
