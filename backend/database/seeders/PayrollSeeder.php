<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Database\Seeders\Concerns\IdentityInsert;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PayrollSeeder extends Seeder
{
    use IdentityInsert;
    public function run(): void
    {
        DB::table('payslip_items')->delete();
        DB::table('payslips')->delete();
        DB::table('payroll_runs')->delete();
        DB::table('bonus_deductions')->delete();
        DB::table('bonus_deduction_types')->delete();
        DB::table('payroll_parameter_details')->delete();
        DB::table('payroll_parameters')->delete();

        $now = now();

        // ---------------------------------------------------------------
        // Payroll Parameters
        // ---------------------------------------------------------------
        $this->insertWithIdentity('payroll_parameters', [
            [
                'id' => 1,
                'code' => 'INSURANCE_RATE',
                'name' => 'Ty Le Bao Hiem Bat Buoc',
                'description' => 'Ty le dong bao hiem BHXH, BHYT, BHTN cho nguoi lao dong va nguoi su dung lao dong',
                'effective_from' => '2024-01-01',
                'effective_to' => null,
                'formula_json' => json_encode([
                    'employee' => ['bhxh' => 8.0, 'bhyt' => 1.5, 'bhtn' => 1.0],
                    'employer' => ['bhxh' => 17.5, 'bhyt' => 3.0, 'bhtn' => 1.0],
                ]),
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'code' => 'PIT_BRACKET',
                'name' => 'Bieu Thue Thu Nhap Ca Nhan Luy Tien',
                'description' => 'Bieu thue TNCN luy tien tung phan theo quy dinh',
                'effective_from' => '2024-01-01',
                'effective_to' => null,
                'formula_json' => json_encode([
                    ['from' => 0,         'to' => 5000000,   'rate' => 5],
                    ['from' => 5000000,   'to' => 10000000,  'rate' => 10],
                    ['from' => 10000000,  'to' => 18000000,  'rate' => 15],
                    ['from' => 18000000,  'to' => 32000000,  'rate' => 20],
                    ['from' => 32000000,  'to' => 52000000,  'rate' => 25],
                    ['from' => 52000000,  'to' => 80000000,  'rate' => 30],
                    ['from' => 80000000,  'to' => null,      'rate' => 35],
                ]),
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'code' => 'TAX_DEDUCTION',
                'name' => 'Giam Tru Gia Canh',
                'description' => 'Muc giam tru gia canh cho ban than va nguoi phu thuoc',
                'effective_from' => '2024-01-01',
                'effective_to' => null,
                'formula_json' => json_encode([
                    'self' => 11000000,
                    'dependent' => 4400000,
                ]),
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 4,
                'code' => 'MIN_WAGE',
                'name' => 'Muc Luong Toi Thieu Vung',
                'description' => 'Luong toi thieu vung ap dung tu 01/07/2024',
                'effective_from' => '2024-07-01',
                'effective_to' => null,
                'formula_json' => json_encode([
                    'region_1' => 4960000,
                    'region_2' => 4410000,
                    'region_3' => 3860000,
                    'region_4' => 3450000,
                ]),
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 5,
                'code' => 'INSURANCE_CAP',
                'name' => 'Muc Tran Dong Bao Hiem',
                'description' => 'Muc tran dong BHXH = 20 x luong co so',
                'effective_from' => '2024-07-01',
                'effective_to' => null,
                'formula_json' => json_encode([
                    'base_salary' => 2340000,
                    'cap_multiplier' => 20,
                    'cap_amount' => 46800000,
                ]),
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // ---------------------------------------------------------------
        // Payroll Parameter Details
        // ---------------------------------------------------------------
        $this->insertWithIdentity('payroll_parameter_details', [
            // Insurance rates (employee side)
            ['id' => 1,  'payroll_parameter_id' => 1, 'param_key' => 'bhxh_employee_rate',  'param_type' => 'percent', 'default_value' => '8.0',   'validation_rule' => 'min:0|max:100', 'display_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2,  'payroll_parameter_id' => 1, 'param_key' => 'bhyt_employee_rate',  'param_type' => 'percent', 'default_value' => '1.5',   'validation_rule' => 'min:0|max:100', 'display_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3,  'payroll_parameter_id' => 1, 'param_key' => 'bhtn_employee_rate',  'param_type' => 'percent', 'default_value' => '1.0',   'validation_rule' => 'min:0|max:100', 'display_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            // Insurance rates (employer side)
            ['id' => 4,  'payroll_parameter_id' => 1, 'param_key' => 'bhxh_employer_rate',  'param_type' => 'percent', 'default_value' => '17.5',  'validation_rule' => 'min:0|max:100', 'display_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5,  'payroll_parameter_id' => 1, 'param_key' => 'bhyt_employer_rate',  'param_type' => 'percent', 'default_value' => '3.0',   'validation_rule' => 'min:0|max:100', 'display_order' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6,  'payroll_parameter_id' => 1, 'param_key' => 'bhtn_employer_rate',  'param_type' => 'percent', 'default_value' => '1.0',   'validation_rule' => 'min:0|max:100', 'display_order' => 6, 'created_at' => $now, 'updated_at' => $now],
            // Tax deductions
            ['id' => 7,  'payroll_parameter_id' => 3, 'param_key' => 'self_deduction',      'param_type' => 'amount',  'default_value' => '11000000', 'validation_rule' => 'min:0',      'display_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8,  'payroll_parameter_id' => 3, 'param_key' => 'dependent_deduction',  'param_type' => 'amount',  'default_value' => '4400000',  'validation_rule' => 'min:0',      'display_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            // Insurance cap
            ['id' => 9,  'payroll_parameter_id' => 5, 'param_key' => 'base_salary',         'param_type' => 'amount',  'default_value' => '2340000',  'validation_rule' => 'min:0',      'display_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 10, 'payroll_parameter_id' => 5, 'param_key' => 'cap_amount',          'param_type' => 'amount',  'default_value' => '46800000', 'validation_rule' => 'min:0',      'display_order' => 2, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ---------------------------------------------------------------
        // Bonus/Deduction Types
        // ---------------------------------------------------------------
        $this->insertWithIdentity('bonus_deduction_types', [
            ['id' => 1, 'code' => 'BONUS_KPI',     'name' => 'Thuong KPI',              'kind' => 'bonus',     'is_taxable' => true,  'is_insurance_base' => false, 'is_recurring' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'code' => 'BONUS_TET',     'name' => 'Thuong Tet',              'kind' => 'bonus',     'is_taxable' => true,  'is_insurance_base' => false, 'is_recurring' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'code' => 'BONUS_PROJECT', 'name' => 'Thuong Du An',            'kind' => 'bonus',     'is_taxable' => true,  'is_insurance_base' => false, 'is_recurring' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'code' => 'BONUS_REF',     'name' => 'Thuong Gioi Thieu',       'kind' => 'bonus',     'is_taxable' => true,  'is_insurance_base' => false, 'is_recurring' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'code' => 'DED_LATE',      'name' => 'Tru Di Tre',              'kind' => 'deduction', 'is_taxable' => false, 'is_insurance_base' => false, 'is_recurring' => true,  'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'code' => 'DED_DAMAGE',    'name' => 'Tru Boi Thuong',          'kind' => 'deduction', 'is_taxable' => false, 'is_insurance_base' => false, 'is_recurring' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7, 'code' => 'DED_ADVANCE',   'name' => 'Tru Tam Ung',             'kind' => 'deduction', 'is_taxable' => false, 'is_insurance_base' => false, 'is_recurring' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8, 'code' => 'DED_UNION',     'name' => 'Phi Cong Doan',           'kind' => 'deduction', 'is_taxable' => false, 'is_insurance_base' => false, 'is_recurring' => true,  'created_at' => $now, 'updated_at' => $now],
        ]);

        // ---------------------------------------------------------------
        // Bonus/Deductions for Feb 2026 (attendance_period_id = 2)
        // 10 entries across various employees
        // ---------------------------------------------------------------
        $this->insertWithIdentity('bonus_deductions', [
            ['id' => 1,  'employee_id' => 1,  'attendance_period_id' => 2, 'type_id' => 1, 'amount' => 3000000.00,  'description' => 'Thuong KPI quy 4/2025',            'status' => 'active', 'created_by' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2,  'employee_id' => 5,  'attendance_period_id' => 2, 'type_id' => 1, 'amount' => 5000000.00,  'description' => 'Thuong KPI quy 4/2025',            'status' => 'active', 'created_by' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3,  'employee_id' => 8,  'attendance_period_id' => 2, 'type_id' => 3, 'amount' => 2000000.00,  'description' => 'Thuong hoan thanh du an Alpha',     'status' => 'active', 'created_by' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4,  'employee_id' => 9,  'attendance_period_id' => 2, 'type_id' => 3, 'amount' => 2000000.00,  'description' => 'Thuong hoan thanh du an Alpha',     'status' => 'active', 'created_by' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5,  'employee_id' => 14, 'attendance_period_id' => 2, 'type_id' => 3, 'amount' => 1500000.00,  'description' => 'Thuong hoan thanh du an Beta',      'status' => 'active', 'created_by' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6,  'employee_id' => 6,  'attendance_period_id' => 2, 'type_id' => 5, 'amount' => 200000.00,   'description' => 'Tru di tre thang 02/2026',          'status' => 'active', 'created_by' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7,  'employee_id' => 12, 'attendance_period_id' => 2, 'type_id' => 5, 'amount' => 200000.00,   'description' => 'Tru di tre qua 60 phut',            'status' => 'active', 'created_by' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8,  'employee_id' => 7,  'attendance_period_id' => 2, 'type_id' => 7, 'amount' => 3000000.00,  'description' => 'Tru tam ung thang 01/2026',          'status' => 'active', 'created_by' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9,  'employee_id' => 11, 'attendance_period_id' => 2, 'type_id' => 4, 'amount' => 1000000.00,  'description' => 'Thuong gioi thieu nhan su moi',      'status' => 'active', 'created_by' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 10, 'employee_id' => 13, 'attendance_period_id' => 2, 'type_id' => 8, 'amount' => 100000.00,   'description' => 'Phi cong doan thang 02/2026',        'status' => 'active', 'created_by' => 4, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ---------------------------------------------------------------
        // Payroll Runs: 3 runs for 3 periods
        // Period 1 (Jan) => finalized (later locked for Jan)
        // Period 2 (Feb) => previewed
        // Period 3 (Mar) => draft
        // ---------------------------------------------------------------
        $this->insertWithIdentity('payroll_runs', [
            [
                'id' => 1,
                'attendance_period_id' => 1,
                'run_no' => 1,
                'scope_type' => 'all',
                'scope_value' => null,
                'status' => 'locked',
                'requested_by' => 4,
                'previewed_at' => Carbon::parse('2026-02-01 09:00:00'),
                'finalized_at' => Carbon::parse('2026-02-03 10:00:00'),
                'finalized_by' => 4,
                'locked_at' => Carbon::parse('2026-02-05 14:00:00'),
                'locked_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'attendance_period_id' => 2,
                'run_no' => 1,
                'scope_type' => 'all',
                'scope_value' => null,
                'status' => 'previewed',
                'requested_by' => 4,
                'previewed_at' => Carbon::parse('2026-03-02 09:00:00'),
                'finalized_at' => null,
                'finalized_by' => null,
                'locked_at' => null,
                'locked_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'attendance_period_id' => 3,
                'run_no' => 1,
                'scope_type' => 'all',
                'scope_value' => null,
                'status' => 'draft',
                'requested_by' => 4,
                'previewed_at' => null,
                'finalized_at' => null,
                'finalized_by' => null,
                'locked_at' => null,
                'locked_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // ---------------------------------------------------------------
        // Payslips: 20 payslips for Jan (locked) and Feb (previewed) runs
        // 10 employees (IDs 6-15) for each of 2 runs = 20 payslips
        // ---------------------------------------------------------------

        // Employee salary map (from contracts)
        $salaryMap = [
            6  => 10000000.00,  // emp001
            7  => 10000000.00,  // emp002
            8  => 12000000.00,  // emp003
            9  => 15000000.00,  // emp004
            10 => 10000000.00,  // emp005
            11 => 12000000.00,  // emp006
            12 => 8000000.00,   // emp007 (probation 85%)
            13 => 10000000.00,  // emp008
            14 => 12000000.00,  // emp009
            15 => 8000000.00,   // emp010
        ];

        // Contract IDs
        $contractMap = [
            6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10,
            11 => 11, 12 => 12, 13 => 13, 14 => 14, 15 => 15,
        ];

        // Dependent counts for tax deduction
        $dependentCounts = [
            6 => 0, 7 => 1, 8 => 0, 9 => 2, 10 => 0,
            11 => 1, 12 => 0, 13 => 1, 14 => 0, 15 => 0,
        ];

        $payslips = [];
        $payslipItems = [];
        $psId = 1;
        $piId = 1;

        $selfDeduction = 11000000;
        $dependentDeduction = 4400000;
        $insuranceEmployeeRate = 0.105; // 8% + 1.5% + 1%
        $insuranceCompanyRate = 0.215;  // 17.5% + 3% + 1%

        foreach ([1, 2] as $runId) {
            $periodId = $runId; // Run 1 => Period 1 (Jan), Run 2 => Period 2 (Feb)
            $runStatus = $runId === 1 ? 'locked' : 'previewed';

            foreach ($salaryMap as $empId => $baseSalary) {
                // For probation employee (emp007, id=12), apply 85%
                $effectiveSalary = $baseSalary;
                if ($empId === 12) {
                    $effectiveSalary = $baseSalary * 0.85;
                }

                // Insurance calculation
                $insuranceBase = $effectiveSalary;
                $insuranceEmployee = round($insuranceBase * $insuranceEmployeeRate);
                $insuranceCompany = round($insuranceBase * $insuranceCompanyRate);

                // Gross = base + allowances (meal 730k + transport 500k = 1,230,000)
                $allowanceTotal = 1230000;
                $grossSalary = $effectiveSalary + $allowanceTotal;

                // Bonus/deduction for Feb (run 2)
                $bonusTotal = 0;
                $deductionTotal = 0;
                if ($runId === 2) {
                    // Look up bonus_deductions
                    $bonuses = DB::table('bonus_deductions')
                        ->join('bonus_deduction_types', 'bonus_deductions.type_id', '=', 'bonus_deduction_types.id')
                        ->where('bonus_deductions.employee_id', $empId)
                        ->where('bonus_deductions.attendance_period_id', 2)
                        ->select('bonus_deduction_types.kind', 'bonus_deductions.amount')
                        ->get();

                    foreach ($bonuses as $b) {
                        if ($b->kind === 'bonus') {
                            $bonusTotal += $b->amount;
                        } else {
                            $deductionTotal += $b->amount;
                        }
                    }
                }

                $grossSalary += $bonusTotal;

                // Taxable income
                $numDependents = $dependentCounts[$empId] ?? 0;
                $totalDeduction = $selfDeduction + ($numDependents * $dependentDeduction);
                $taxableIncome = $grossSalary - $insuranceEmployee - $totalDeduction;
                if ($taxableIncome < 0) $taxableIncome = 0;

                // PIT calculation (progressive brackets)
                $pitAmount = $this->calculatePIT($taxableIncome);

                // Net salary
                $netSalary = $grossSalary - $insuranceEmployee - $pitAmount - $deductionTotal;

                $payslips[] = [
                    'id' => $psId,
                    'attendance_period_id' => $periodId,
                    'employee_id' => $empId,
                    'payroll_run_id' => $runId,
                    'contract_id' => $contractMap[$empId],
                    'base_salary_snapshot' => $effectiveSalary,
                    'gross_salary' => $grossSalary,
                    'taxable_income' => $taxableIncome,
                    'insurance_base' => $insuranceBase,
                    'insurance_employee' => $insuranceEmployee,
                    'insurance_company' => $insuranceCompany,
                    'pit_amount' => $pitAmount,
                    'bonus_total' => $bonusTotal,
                    'deduction_total' => $deductionTotal,
                    'net_salary' => $netSalary,
                    'status' => $runStatus,
                    'generated_at' => $now,
                    'locked_at' => $runId === 1 ? Carbon::parse('2026-02-05 14:00:00') : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // Generate payslip items
                $items = $this->buildPayslipItems($piId, $psId, $effectiveSalary, $insuranceEmployee, $insuranceCompany, $pitAmount, $bonusTotal, $deductionTotal, $empId, $runId);
                foreach ($items as $item) {
                    $payslipItems[] = $item;
                    $piId++;
                }

                $psId++;
            }
        }

        $this->insertWithIdentity('payslips', $payslips);
        $this->insertWithIdentity('payslip_items', $payslipItems);
    }

    /**
     * Calculate PIT using Vietnamese progressive tax brackets.
     */
    private function calculatePIT(float $taxableIncome): float
    {
        if ($taxableIncome <= 0) return 0;

        $brackets = [
            [5000000,  0.05],
            [5000000,  0.10],
            [8000000,  0.15],
            [14000000, 0.20],
            [20000000, 0.25],
            [28000000, 0.30],
            [PHP_FLOAT_MAX, 0.35],
        ];

        $remaining = $taxableIncome;
        $tax = 0;

        foreach ($brackets as [$width, $rate]) {
            if ($remaining <= 0) break;
            $taxable = min($remaining, $width);
            $tax += $taxable * $rate;
            $remaining -= $taxable;
        }

        return round($tax);
    }

    /**
     * Build payslip line items for a single payslip.
     */
    private function buildPayslipItems(int &$startId, int $payslipId, float $baseSalary, float $insEmp, float $insComp, float $pit, float $bonusTotal, float $deductionTotal, int $empId, int $runId): array
    {
        $items = [];
        $order = 1;

        // Earnings
        $items[] = ['id' => $startId++, 'payslip_id' => $payslipId, 'item_code' => 'BASE_SALARY',   'item_name' => 'Luong Co Ban',         'item_group' => 'earning',    'qty' => 1.00, 'rate' => $baseSalary,  'amount' => $baseSalary,  'sort_order' => $order++, 'source_ref' => 'contract'];
        $items[] = ['id' => $startId++, 'payslip_id' => $payslipId, 'item_code' => 'ALW_MEAL',      'item_name' => 'Phu Cap An Trua',      'item_group' => 'earning',    'qty' => 1.00, 'rate' => 730000.00,    'amount' => 730000.00,    'sort_order' => $order++, 'source_ref' => 'allowance:PC_AN'];
        $items[] = ['id' => $startId++, 'payslip_id' => $payslipId, 'item_code' => 'ALW_TRANSPORT', 'item_name' => 'Phu Cap Xang Xe',      'item_group' => 'earning',    'qty' => 1.00, 'rate' => 500000.00,    'amount' => 500000.00,    'sort_order' => $order++, 'source_ref' => 'allowance:PC_XE'];

        if ($bonusTotal > 0) {
            $items[] = ['id' => $startId++, 'payslip_id' => $payslipId, 'item_code' => 'BONUS',      'item_name' => 'Thuong',               'item_group' => 'earning',    'qty' => 1.00, 'rate' => $bonusTotal,  'amount' => $bonusTotal,  'sort_order' => $order++, 'source_ref' => 'bonus_deduction'];
        }

        // Insurance deductions (employee)
        $bhxh = round($baseSalary * 0.08);
        $bhyt = round($baseSalary * 0.015);
        $bhtn = round($baseSalary * 0.01);

        $items[] = ['id' => $startId++, 'payslip_id' => $payslipId, 'item_code' => 'INS_BHXH',     'item_name' => 'BHXH (8%)',            'item_group' => 'deduction',  'qty' => 1.00, 'rate' => $baseSalary,  'amount' => $bhxh,        'sort_order' => $order++, 'source_ref' => 'insurance:bhxh'];
        $items[] = ['id' => $startId++, 'payslip_id' => $payslipId, 'item_code' => 'INS_BHYT',     'item_name' => 'BHYT (1.5%)',          'item_group' => 'deduction',  'qty' => 1.00, 'rate' => $baseSalary,  'amount' => $bhyt,        'sort_order' => $order++, 'source_ref' => 'insurance:bhyt'];
        $items[] = ['id' => $startId++, 'payslip_id' => $payslipId, 'item_code' => 'INS_BHTN',     'item_name' => 'BHTN (1%)',            'item_group' => 'deduction',  'qty' => 1.00, 'rate' => $baseSalary,  'amount' => $bhtn,        'sort_order' => $order++, 'source_ref' => 'insurance:bhtn'];

        // PIT
        if ($pit > 0) {
            $items[] = ['id' => $startId++, 'payslip_id' => $payslipId, 'item_code' => 'PIT',       'item_name' => 'Thue TNCN',            'item_group' => 'deduction',  'qty' => 1.00, 'rate' => null,         'amount' => $pit,         'sort_order' => $order++, 'source_ref' => 'pit_calculation'];
        }

        // Other deductions
        if ($deductionTotal > 0) {
            $items[] = ['id' => $startId++, 'payslip_id' => $payslipId, 'item_code' => 'OTHER_DED', 'item_name' => 'Khau Tru Khac',        'item_group' => 'deduction',  'qty' => 1.00, 'rate' => null,         'amount' => $deductionTotal, 'sort_order' => $order++, 'source_ref' => 'bonus_deduction'];
        }

        // Employer insurance (info only)
        $bhxhEr = round($baseSalary * 0.175);
        $bhytEr = round($baseSalary * 0.03);
        $bhtnEr = round($baseSalary * 0.01);

        $items[] = ['id' => $startId++, 'payslip_id' => $payslipId, 'item_code' => 'INS_ER_BHXH',  'item_name' => 'BHXH Cong Ty (17.5%)', 'item_group' => 'employer',   'qty' => 1.00, 'rate' => $baseSalary,  'amount' => $bhxhEr,      'sort_order' => $order++, 'source_ref' => 'insurance:bhxh_er'];
        $items[] = ['id' => $startId++, 'payslip_id' => $payslipId, 'item_code' => 'INS_ER_BHYT',  'item_name' => 'BHYT Cong Ty (3%)',    'item_group' => 'employer',   'qty' => 1.00, 'rate' => $baseSalary,  'amount' => $bhytEr,      'sort_order' => $order++, 'source_ref' => 'insurance:bhyt_er'];
        $items[] = ['id' => $startId++, 'payslip_id' => $payslipId, 'item_code' => 'INS_ER_BHTN',  'item_name' => 'BHTN Cong Ty (1%)',    'item_group' => 'employer',   'qty' => 1.00, 'rate' => $baseSalary,  'amount' => $bhtnEr,      'sort_order' => $order++, 'source_ref' => 'insurance:bhtn_er'];

        return $items;
    }
}

