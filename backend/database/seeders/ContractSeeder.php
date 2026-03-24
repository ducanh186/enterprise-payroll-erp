<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\IdentityInsert;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContractSeeder extends Seeder
{
    use IdentityInsert;
    public function run(): void
    {
        DB::table('contract_allowances')->delete();
        DB::table('labour_contracts')->delete();
        DB::table('salary_levels')->delete();
        DB::table('payroll_types')->delete();
        DB::table('contract_types')->delete();
        DB::table('allowance_types')->delete();

        $now = now();

        // ---------------------------------------------------------------
        // Contract Types
        // ---------------------------------------------------------------
        $this->insertWithIdentity('contract_types', [
            ['id' => 1, 'code' => 'THU_VIEC',  'name' => 'Hop Dong Thu Viec',             'duration_months' => 2,    'is_probationary' => true,  'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'code' => 'XDTH_12',   'name' => 'Hop Dong Xac Dinh Thoi Han 12 thang', 'duration_months' => 12, 'is_probationary' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'code' => 'XDTH_24',   'name' => 'Hop Dong Xac Dinh Thoi Han 24 thang', 'duration_months' => 24, 'is_probationary' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'code' => 'XDTH_36',   'name' => 'Hop Dong Xac Dinh Thoi Han 36 thang', 'duration_months' => 36, 'is_probationary' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'code' => 'KDXDTH',     'name' => 'Hop Dong Khong Xac Dinh Thoi Han',    'duration_months' => null, 'is_probationary' => false, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ---------------------------------------------------------------
        // Payroll Types
        // ---------------------------------------------------------------
        $this->insertWithIdentity('payroll_types', [
            ['id' => 1, 'code' => 'LUONG_CO_BAN',    'name' => 'Luong Co Ban',         'is_probationary' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'code' => 'LUONG_THU_VIEC',  'name' => 'Luong Thu Viec',       'is_probationary' => true,  'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'code' => 'LUONG_KHOAN',     'name' => 'Luong Khoan',          'is_probationary' => false, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ---------------------------------------------------------------
        // Salary Levels (per payroll type)
        // ---------------------------------------------------------------
        $this->insertWithIdentity('salary_levels', [
            // Regular salary levels
            ['id' => 1,  'payroll_type_id' => 1, 'code' => 'CB_B1', 'level_no' => 1, 'amount' => 8000000.00,  'effective_from' => '2024-01-01', 'effective_to' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2,  'payroll_type_id' => 1, 'code' => 'CB_B2', 'level_no' => 2, 'amount' => 10000000.00, 'effective_from' => '2024-01-01', 'effective_to' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3,  'payroll_type_id' => 1, 'code' => 'CB_B3', 'level_no' => 3, 'amount' => 12000000.00, 'effective_from' => '2024-01-01', 'effective_to' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4,  'payroll_type_id' => 1, 'code' => 'CB_B4', 'level_no' => 4, 'amount' => 15000000.00, 'effective_from' => '2024-01-01', 'effective_to' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5,  'payroll_type_id' => 1, 'code' => 'CB_B5', 'level_no' => 5, 'amount' => 18000000.00, 'effective_from' => '2024-01-01', 'effective_to' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6,  'payroll_type_id' => 1, 'code' => 'CB_B6', 'level_no' => 6, 'amount' => 22000000.00, 'effective_from' => '2024-01-01', 'effective_to' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7,  'payroll_type_id' => 1, 'code' => 'CB_B7', 'level_no' => 7, 'amount' => 25000000.00, 'effective_from' => '2024-01-01', 'effective_to' => null, 'created_at' => $now, 'updated_at' => $now],

            // Probation salary levels (85% of regular)
            ['id' => 8,  'payroll_type_id' => 2, 'code' => 'TV_B1', 'level_no' => 1, 'amount' => 6800000.00,  'effective_from' => '2024-01-01', 'effective_to' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9,  'payroll_type_id' => 2, 'code' => 'TV_B2', 'level_no' => 2, 'amount' => 8500000.00,  'effective_from' => '2024-01-01', 'effective_to' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 10, 'payroll_type_id' => 2, 'code' => 'TV_B3', 'level_no' => 3, 'amount' => 10200000.00, 'effective_from' => '2024-01-01', 'effective_to' => null, 'created_at' => $now, 'updated_at' => $now],

            // Lump-sum salary levels
            ['id' => 11, 'payroll_type_id' => 3, 'code' => 'LK_B1', 'level_no' => 1, 'amount' => 15000000.00, 'effective_from' => '2024-01-01', 'effective_to' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ---------------------------------------------------------------
        // Allowance Types
        // ---------------------------------------------------------------
        $this->insertWithIdentity('allowance_types', [
            ['id' => 1, 'code' => 'PC_AN',     'name' => 'Phu Cap An Trua',          'is_taxable' => false, 'is_insurance_base' => false, 'default_amount' => 730000.00,   'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'code' => 'PC_XE',     'name' => 'Phu Cap Xang Xe',          'is_taxable' => false, 'is_insurance_base' => false, 'default_amount' => 500000.00,   'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'code' => 'PC_DT',     'name' => 'Phu Cap Dien Thoai',       'is_taxable' => false, 'is_insurance_base' => false, 'default_amount' => 300000.00,   'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'code' => 'PC_TN',     'name' => 'Phu Cap Trach Nhiem',      'is_taxable' => true,  'is_insurance_base' => true,  'default_amount' => 2000000.00,  'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'code' => 'PC_KV',     'name' => 'Phu Cap Khu Vuc',          'is_taxable' => true,  'is_insurance_base' => false, 'default_amount' => 500000.00,   'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'code' => 'PC_CHUYEN', 'name' => 'Phu Cap Chuyen Mon',       'is_taxable' => true,  'is_insurance_base' => true,  'default_amount' => 1000000.00,  'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ---------------------------------------------------------------
        // Labour Contracts (one per employee, various types)
        // ---------------------------------------------------------------
        // Salary assignments:
        // NV001 Admin:      25M (B7)  - indefinite
        // NV002 HR Lead:    18M (B5)  - indefinite
        // NV003 HR Staff:   12M (B3)  - 24-month
        // NV004 Payroll:    22M (B6)  - indefinite
        // NV005 Manager:    25M (B7)  - indefinite
        // NV006 emp001:     10M (B2)  - 12-month
        // NV007 emp002:     10M (B2)  - 24-month
        // NV008 emp003:     12M (B3)  - 24-month
        // NV009 emp004:     15M (B4)  - indefinite
        // NV010 emp005:     10M (B2)  - 12-month
        // NV011 emp006:     12M (B3)  - 24-month
        // NV012 emp007:      8M (B1)  - probation (thu viec)
        // NV013 emp008:     10M (B2)  - 12-month
        // NV014 emp009:     12M (B3)  - 24-month
        // NV015 emp010:      8M (B1)  - 12-month

        $this->insertWithIdentity('labour_contracts', [
            ['id' => 1,  'employee_id' => 1,  'contract_no' => 'HD-2020-001', 'contract_type_id' => 5, 'position_title_snapshot' => 'Truong Phong IT',         'department_snapshot' => 'Phong Cong Nghe Thong Tin', 'start_date' => '2020-01-15', 'end_date' => null,         'sign_date' => '2020-01-10', 'status' => 'active', 'base_salary' => 25000000.00, 'salary_level_id' => 7,  'payroll_type_id' => 1, 'probation_rate' => 100.00, 'created_by' => 1, 'approved_by' => 5,  'created_at' => $now, 'updated_at' => $now],
            ['id' => 2,  'employee_id' => 2,  'contract_no' => 'HD-2021-002', 'contract_type_id' => 5, 'position_title_snapshot' => 'Truong Phong Nhan Su',    'department_snapshot' => 'Phong Nhan Su',             'start_date' => '2021-03-01', 'end_date' => null,         'sign_date' => '2021-02-25', 'status' => 'active', 'base_salary' => 18000000.00, 'salary_level_id' => 5,  'payroll_type_id' => 1, 'probation_rate' => 100.00, 'created_by' => 1, 'approved_by' => 5,  'created_at' => $now, 'updated_at' => $now],
            ['id' => 3,  'employee_id' => 3,  'contract_no' => 'HD-2022-003', 'contract_type_id' => 3, 'position_title_snapshot' => 'Nhan Vien Nhan Su',       'department_snapshot' => 'Phong Nhan Su',             'start_date' => '2022-06-15', 'end_date' => '2024-06-14', 'sign_date' => '2022-06-10', 'status' => 'active', 'base_salary' => 12000000.00, 'salary_level_id' => 3,  'payroll_type_id' => 1, 'probation_rate' => 100.00, 'created_by' => 1, 'approved_by' => 5,  'created_at' => $now, 'updated_at' => $now],
            ['id' => 4,  'employee_id' => 4,  'contract_no' => 'HD-2020-004', 'contract_type_id' => 5, 'position_title_snapshot' => 'Truong Phong Ke Toan',    'department_snapshot' => 'Phong Ke Toan',             'start_date' => '2020-09-01', 'end_date' => null,         'sign_date' => '2020-08-25', 'status' => 'active', 'base_salary' => 22000000.00, 'salary_level_id' => 6,  'payroll_type_id' => 1, 'probation_rate' => 100.00, 'created_by' => 1, 'approved_by' => 5,  'created_at' => $now, 'updated_at' => $now],
            ['id' => 5,  'employee_id' => 5,  'contract_no' => 'HD-2018-005', 'contract_type_id' => 5, 'position_title_snapshot' => 'Giam Doc',                'department_snapshot' => 'Ban Giam Doc',              'start_date' => '2018-06-01', 'end_date' => null,         'sign_date' => '2018-05-25', 'status' => 'active', 'base_salary' => 25000000.00, 'salary_level_id' => 7,  'payroll_type_id' => 1, 'probation_rate' => 100.00, 'created_by' => 1, 'approved_by' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6,  'employee_id' => 6,  'contract_no' => 'HD-2023-006', 'contract_type_id' => 2, 'position_title_snapshot' => 'Nhan Vien Van Hanh',      'department_snapshot' => 'Phong Van Hanh',            'start_date' => '2023-01-10', 'end_date' => '2024-01-09', 'sign_date' => '2023-01-05', 'status' => 'active', 'base_salary' => 10000000.00, 'salary_level_id' => 2,  'payroll_type_id' => 1, 'probation_rate' => 100.00, 'created_by' => 2, 'approved_by' => 5,  'created_at' => $now, 'updated_at' => $now],
            ['id' => 7,  'employee_id' => 7,  'contract_no' => 'HD-2023-007', 'contract_type_id' => 3, 'position_title_snapshot' => 'Nhan Vien Van Hanh',      'department_snapshot' => 'Phong Van Hanh',            'start_date' => '2023-03-01', 'end_date' => '2025-02-28', 'sign_date' => '2023-02-25', 'status' => 'active', 'base_salary' => 10000000.00, 'salary_level_id' => 2,  'payroll_type_id' => 1, 'probation_rate' => 100.00, 'created_by' => 2, 'approved_by' => 5,  'created_at' => $now, 'updated_at' => $now],
            ['id' => 8,  'employee_id' => 8,  'contract_no' => 'HD-2022-008', 'contract_type_id' => 3, 'position_title_snapshot' => 'Nhan Vien Ky Thuat',      'department_snapshot' => 'Phong Ky Thuat',            'start_date' => '2022-08-15', 'end_date' => '2024-08-14', 'sign_date' => '2022-08-10', 'status' => 'active', 'base_salary' => 12000000.00, 'salary_level_id' => 3,  'payroll_type_id' => 1, 'probation_rate' => 100.00, 'created_by' => 2, 'approved_by' => 5,  'created_at' => $now, 'updated_at' => $now],
            ['id' => 9,  'employee_id' => 9,  'contract_no' => 'HD-2021-009', 'contract_type_id' => 5, 'position_title_snapshot' => 'Nhan Vien Ky Thuat',      'department_snapshot' => 'Phong Ky Thuat',            'start_date' => '2021-11-01', 'end_date' => null,         'sign_date' => '2021-10-25', 'status' => 'active', 'base_salary' => 15000000.00, 'salary_level_id' => 4,  'payroll_type_id' => 1, 'probation_rate' => 100.00, 'created_by' => 2, 'approved_by' => 5,  'created_at' => $now, 'updated_at' => $now],
            ['id' => 10, 'employee_id' => 10, 'contract_no' => 'HD-2023-010', 'contract_type_id' => 2, 'position_title_snapshot' => 'Nhan Vien Ky Thuat',      'department_snapshot' => 'Phong Ky Thuat',            'start_date' => '2023-07-01', 'end_date' => '2024-06-30', 'sign_date' => '2023-06-25', 'status' => 'active', 'base_salary' => 10000000.00, 'salary_level_id' => 2,  'payroll_type_id' => 1, 'probation_rate' => 100.00, 'created_by' => 2, 'approved_by' => 5,  'created_at' => $now, 'updated_at' => $now],
            ['id' => 11, 'employee_id' => 11, 'contract_no' => 'HD-2022-011', 'contract_type_id' => 3, 'position_title_snapshot' => 'Nhan Vien Kiem Dinh',     'department_snapshot' => 'Phong Kiem Dinh',           'start_date' => '2022-02-01', 'end_date' => '2024-01-31', 'sign_date' => '2022-01-25', 'status' => 'active', 'base_salary' => 12000000.00, 'salary_level_id' => 3,  'payroll_type_id' => 1, 'probation_rate' => 100.00, 'created_by' => 2, 'approved_by' => 5,  'created_at' => $now, 'updated_at' => $now],
            ['id' => 12, 'employee_id' => 12, 'contract_no' => 'HD-2024-012', 'contract_type_id' => 1, 'position_title_snapshot' => 'Nhan Vien Kinh Doanh',    'department_snapshot' => 'Phong Kinh Doanh',          'start_date' => '2024-01-15', 'end_date' => '2024-03-14', 'sign_date' => '2024-01-10', 'status' => 'active', 'base_salary' => 8000000.00,  'salary_level_id' => 8,  'payroll_type_id' => 2, 'probation_rate' => 85.00,  'created_by' => 2, 'approved_by' => 5,  'created_at' => $now, 'updated_at' => $now],
            ['id' => 13, 'employee_id' => 13, 'contract_no' => 'HD-2023-013', 'contract_type_id' => 2, 'position_title_snapshot' => 'Nhan Vien Ke Toan',       'department_snapshot' => 'Phong Ke Toan',             'start_date' => '2023-04-01', 'end_date' => '2024-03-31', 'sign_date' => '2023-03-25', 'status' => 'active', 'base_salary' => 10000000.00, 'salary_level_id' => 2,  'payroll_type_id' => 1, 'probation_rate' => 100.00, 'created_by' => 2, 'approved_by' => 4,  'created_at' => $now, 'updated_at' => $now],
            ['id' => 14, 'employee_id' => 14, 'contract_no' => 'HD-2023-014', 'contract_type_id' => 3, 'position_title_snapshot' => 'Nhan Vien IT',            'department_snapshot' => 'Phong Cong Nghe Thong Tin', 'start_date' => '2023-09-01', 'end_date' => '2025-08-31', 'sign_date' => '2023-08-25', 'status' => 'active', 'base_salary' => 12000000.00, 'salary_level_id' => 3,  'payroll_type_id' => 1, 'probation_rate' => 100.00, 'created_by' => 1, 'approved_by' => 5,  'created_at' => $now, 'updated_at' => $now],
            ['id' => 15, 'employee_id' => 15, 'contract_no' => 'HD-2024-015', 'contract_type_id' => 2, 'position_title_snapshot' => 'Nhan Vien Van Hanh',      'department_snapshot' => 'Phong Van Hanh',            'start_date' => '2024-06-01', 'end_date' => '2025-05-31', 'sign_date' => '2024-05-25', 'status' => 'active', 'base_salary' => 8000000.00,  'salary_level_id' => 1,  'payroll_type_id' => 1, 'probation_rate' => 100.00, 'created_by' => 2, 'approved_by' => 5,  'created_at' => $now, 'updated_at' => $now],
        ]);

        // ---------------------------------------------------------------
        // Contract Allowances
        // ---------------------------------------------------------------
        $contractAllowances = [];
        $caId = 1;

        // All employees get meal (PC_AN) and transport (PC_XE) allowances
        for ($empContractId = 1; $empContractId <= 15; $empContractId++) {
            $startDate = DB::table('labour_contracts')->where('id', $empContractId)->value('start_date');

            // Meal allowance
            $contractAllowances[] = [
                'id' => $caId++,
                'contract_id' => $empContractId,
                'allowance_type_id' => 1, // PC_AN
                'amount' => 730000.00,
                'effective_from' => $startDate,
                'effective_to' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Transport allowance
            $contractAllowances[] = [
                'id' => $caId++,
                'contract_id' => $empContractId,
                'allowance_type_id' => 2, // PC_XE
                'amount' => 500000.00,
                'effective_from' => $startDate,
                'effective_to' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Managers and leads get responsibility allowance (PC_TN)
        foreach ([1, 2, 4, 5] as $contractId) {
            $startDate = DB::table('labour_contracts')->where('id', $contractId)->value('start_date');
            $contractAllowances[] = [
                'id' => $caId++,
                'contract_id' => $contractId,
                'allowance_type_id' => 4, // PC_TN
                'amount' => 2000000.00,
                'effective_from' => $startDate,
                'effective_to' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Phone allowance for managers
        foreach ([1, 2, 4, 5] as $contractId) {
            $startDate = DB::table('labour_contracts')->where('id', $contractId)->value('start_date');
            $contractAllowances[] = [
                'id' => $caId++,
                'contract_id' => $contractId,
                'allowance_type_id' => 3, // PC_DT
                'amount' => 300000.00,
                'effective_from' => $startDate,
                'effective_to' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Technical expertise allowance for engineering staff
        foreach ([8, 9, 10, 14] as $contractId) {
            $startDate = DB::table('labour_contracts')->where('id', $contractId)->value('start_date');
            $contractAllowances[] = [
                'id' => $caId++,
                'contract_id' => $contractId,
                'allowance_type_id' => 6, // PC_CHUYEN
                'amount' => 1000000.00,
                'effective_from' => $startDate,
                'effective_to' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->insertWithIdentity('contract_allowances', $contractAllowances);
    }
}

