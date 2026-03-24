<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\IdentityInsert;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeSeeder extends Seeder
{
    use IdentityInsert;
    public function run(): void
    {
        DB::table('dependents')->delete();
        DB::table('employees')->delete();

        $now = now();

        // ---------------------------------------------------------------
        // 15 employees linked to users 1-15
        // ---------------------------------------------------------------
        $employees = [
            // Admin (IT dept, TP_IT)
            ['id' => 1,  'employee_code' => 'NV001', 'user_id' => 1,  'full_name' => 'Nguyen Van Admin',  'dob' => '1985-03-15', 'gender' => 'male',   'national_id' => '001085003456', 'tax_code' => '8001234567',   'email' => 'admin01@erp.vn',   'phone' => '0901000001', 'bank_account_no' => '1001000001', 'bank_name' => 'Vietcombank', 'department_id' => 8, 'position_id' => 13, 'join_date' => '2020-01-15', 'employment_status' => 'active', 'created_at' => $now, 'updated_at' => $now],

            // HR staff (HR dept)
            ['id' => 2,  'employee_code' => 'NV002', 'user_id' => 2,  'full_name' => 'Tran Thi HR',       'dob' => '1990-07-22', 'gender' => 'female', 'national_id' => '001090007890', 'tax_code' => '8001234568',   'email' => 'hr01@erp.vn',      'phone' => '0901000002', 'bank_account_no' => '1001000002', 'bank_name' => 'Vietcombank', 'department_id' => 2, 'position_id' => 3,  'join_date' => '2021-03-01', 'employment_status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3,  'employee_code' => 'NV003', 'user_id' => 3,  'full_name' => 'Le Van HR',          'dob' => '1992-11-10', 'gender' => 'male',   'national_id' => '001092001234', 'tax_code' => '8001234569',   'email' => 'hr02@erp.vn',      'phone' => '0901000003', 'bank_account_no' => '1001000003', 'bank_name' => 'Techcombank', 'department_id' => 2, 'position_id' => 4,  'join_date' => '2022-06-15', 'employment_status' => 'active', 'created_at' => $now, 'updated_at' => $now],

            // Accountant (Accounting dept)
            ['id' => 4,  'employee_code' => 'NV004', 'user_id' => 4,  'full_name' => 'Pham Thi Payroll',   'dob' => '1988-05-30', 'gender' => 'female', 'national_id' => '001088005678', 'tax_code' => '8001234570',   'email' => 'payroll01@erp.vn', 'phone' => '0901000004', 'bank_account_no' => '1001000004', 'bank_name' => 'BIDV',        'department_id' => 3, 'position_id' => 5,  'join_date' => '2020-09-01', 'employment_status' => 'active', 'created_at' => $now, 'updated_at' => $now],

            // Manager (BOD)
            ['id' => 5,  'employee_code' => 'NV005', 'user_id' => 5,  'full_name' => 'Hoang Van Manager',  'dob' => '1980-01-05', 'gender' => 'male',   'national_id' => '001080009012', 'tax_code' => '8001234571',   'email' => 'manager01@erp.vn', 'phone' => '0901000005', 'bank_account_no' => '1001000005', 'bank_name' => 'Vietcombank', 'department_id' => 1, 'position_id' => 1,  'join_date' => '2018-06-01', 'employment_status' => 'active', 'created_at' => $now, 'updated_at' => $now],

            // emp001 - Operations
            ['id' => 6,  'employee_code' => 'NV006', 'user_id' => 6,  'full_name' => 'Vo Thi Mai',         'dob' => '1995-04-12', 'gender' => 'female', 'national_id' => '001095003456', 'tax_code' => '8001234572',   'email' => 'emp001@erp.vn',    'phone' => '0901000006', 'bank_account_no' => '1001000006', 'bank_name' => 'Vietinbank',  'department_id' => 4, 'position_id' => 8,  'join_date' => '2023-01-10', 'employment_status' => 'active', 'created_at' => $now, 'updated_at' => $now],

            // emp002 - Operations
            ['id' => 7,  'employee_code' => 'NV007', 'user_id' => 7,  'full_name' => 'Dang Van Tuan',      'dob' => '1993-08-25', 'gender' => 'male',   'national_id' => '001093007890', 'tax_code' => '8001234573',   'email' => 'emp002@erp.vn',    'phone' => '0901000007', 'bank_account_no' => '1001000007', 'bank_name' => 'Vietcombank', 'department_id' => 4, 'position_id' => 8,  'join_date' => '2023-03-01', 'employment_status' => 'active', 'created_at' => $now, 'updated_at' => $now],

            // emp003 - Engineering
            ['id' => 8,  'employee_code' => 'NV008', 'user_id' => 8,  'full_name' => 'Bui Thi Lan',        'dob' => '1994-12-03', 'gender' => 'female', 'national_id' => '001094001234', 'tax_code' => '8001234574',   'email' => 'emp003@erp.vn',    'phone' => '0901000008', 'bank_account_no' => '1001000008', 'bank_name' => 'ACB',         'department_id' => 5, 'position_id' => 10, 'join_date' => '2022-08-15', 'employment_status' => 'active', 'created_at' => $now, 'updated_at' => $now],

            // emp004 - Engineering
            ['id' => 9,  'employee_code' => 'NV009', 'user_id' => 9,  'full_name' => 'Do Van Hung',        'dob' => '1991-06-18', 'gender' => 'male',   'national_id' => '001091005678', 'tax_code' => '8001234575',   'email' => 'emp004@erp.vn',    'phone' => '0901000009', 'bank_account_no' => '1001000009', 'bank_name' => 'Techcombank', 'department_id' => 5, 'position_id' => 10, 'join_date' => '2021-11-01', 'employment_status' => 'active', 'created_at' => $now, 'updated_at' => $now],

            // emp005 - Engineering
            ['id' => 10, 'employee_code' => 'NV010', 'user_id' => 10, 'full_name' => 'Ngo Thi Huong',      'dob' => '1996-02-28', 'gender' => 'female', 'national_id' => '001096009012', 'tax_code' => '8001234576',   'email' => 'emp005@erp.vn',    'phone' => '0901000010', 'bank_account_no' => '1001000010', 'bank_name' => 'BIDV',        'department_id' => 5, 'position_id' => 10, 'join_date' => '2023-07-01', 'employment_status' => 'active', 'created_at' => $now, 'updated_at' => $now],

            // emp006 - QA
            ['id' => 11, 'employee_code' => 'NV011', 'user_id' => 11, 'full_name' => 'Nguyen Van Thanh',   'dob' => '1989-09-14', 'gender' => 'male',   'national_id' => '001089003456', 'tax_code' => '8001234577',   'email' => 'emp006@erp.vn',    'phone' => '0901000011', 'bank_account_no' => '1001000011', 'bank_name' => 'Vietcombank', 'department_id' => 6, 'position_id' => 11, 'join_date' => '2022-02-01', 'employment_status' => 'active', 'created_at' => $now, 'updated_at' => $now],

            // emp007 - Sales
            ['id' => 12, 'employee_code' => 'NV012', 'user_id' => 12, 'full_name' => 'Tran Van Duc',       'dob' => '1997-03-20', 'gender' => 'male',   'national_id' => '001097007890', 'tax_code' => '8001234578',   'email' => 'emp007@erp.vn',    'phone' => '0901000012', 'bank_account_no' => '1001000012', 'bank_name' => 'Vietinbank',  'department_id' => 7, 'position_id' => 12, 'join_date' => '2024-01-15', 'employment_status' => 'active', 'created_at' => $now, 'updated_at' => $now],

            // emp008 - Accounting
            ['id' => 13, 'employee_code' => 'NV013', 'user_id' => 13, 'full_name' => 'Le Thi Ngoc',        'dob' => '1993-10-08', 'gender' => 'female', 'national_id' => '001093001234', 'tax_code' => '8001234579',   'email' => 'emp008@erp.vn',    'phone' => '0901000013', 'bank_account_no' => '1001000013', 'bank_name' => 'ACB',         'department_id' => 3, 'position_id' => 6,  'join_date' => '2023-04-01', 'employment_status' => 'active', 'created_at' => $now, 'updated_at' => $now],

            // emp009 - IT
            ['id' => 14, 'employee_code' => 'NV014', 'user_id' => 14, 'full_name' => 'Pham Van Long',      'dob' => '1994-07-25', 'gender' => 'male',   'national_id' => '001094005678', 'tax_code' => '8001234580',   'email' => 'emp009@erp.vn',    'phone' => '0901000014', 'bank_account_no' => '1001000014', 'bank_name' => 'BIDV',        'department_id' => 8, 'position_id' => 14, 'join_date' => '2023-09-01', 'employment_status' => 'active', 'created_at' => $now, 'updated_at' => $now],

            // emp010 - Operations
            ['id' => 15, 'employee_code' => 'NV015', 'user_id' => 15, 'full_name' => 'Hoang Thi Thao',     'dob' => '1998-11-30', 'gender' => 'female', 'national_id' => '001098009012', 'tax_code' => '8001234581',   'email' => 'emp010@erp.vn',    'phone' => '0901000015', 'bank_account_no' => '1001000015', 'bank_name' => 'Techcombank', 'department_id' => 4, 'position_id' => 8,  'join_date' => '2024-06-01', 'employment_status' => 'active', 'created_at' => $now, 'updated_at' => $now],
        ];

        $this->insertWithIdentity('employees', $employees);

        // Update department manager_employee_id after employees exist
        DB::table('departments')->where('id', 1)->update(['manager_employee_id' => 5]);  // BOD => Manager
        DB::table('departments')->where('id', 2)->update(['manager_employee_id' => 2]);  // HR => HR lead
        DB::table('departments')->where('id', 3)->update(['manager_employee_id' => 4]);  // Accounting => Payroll lead
        DB::table('departments')->where('id', 8)->update(['manager_employee_id' => 1]);  // IT => Admin

        // ---------------------------------------------------------------
        // Dependents (tax deduction persons for some employees)
        // ---------------------------------------------------------------
        $dependents = [
            // Employee 1 (Admin) - wife and child
            ['id' => 1,  'employee_id' => 1,  'full_name' => 'Tran Thi Hoa',       'dob' => '1987-06-20', 'relationship' => 'spouse',  'national_id' => '001087001111', 'tax_reduction_from' => '2020-01-01', 'tax_reduction_to' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2,  'employee_id' => 1,  'full_name' => 'Nguyen Minh Anh',     'dob' => '2015-09-10', 'relationship' => 'child',   'national_id' => null,           'tax_reduction_from' => '2020-01-01', 'tax_reduction_to' => null, 'created_at' => $now, 'updated_at' => $now],

            // Employee 5 (Manager) - wife and 2 children
            ['id' => 3,  'employee_id' => 5,  'full_name' => 'Nguyen Thi Lan',      'dob' => '1982-04-15', 'relationship' => 'spouse',  'national_id' => '001082002222', 'tax_reduction_from' => '2018-06-01', 'tax_reduction_to' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4,  'employee_id' => 5,  'full_name' => 'Hoang Minh Tu',        'dob' => '2010-12-01', 'relationship' => 'child',   'national_id' => null,           'tax_reduction_from' => '2018-06-01', 'tax_reduction_to' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5,  'employee_id' => 5,  'full_name' => 'Hoang Minh Khoi',      'dob' => '2014-05-20', 'relationship' => 'child',   'national_id' => null,           'tax_reduction_from' => '2018-06-01', 'tax_reduction_to' => null, 'created_at' => $now, 'updated_at' => $now],

            // Employee 7 (emp002) - mother
            ['id' => 6,  'employee_id' => 7,  'full_name' => 'Dang Thi Bich',        'dob' => '1965-02-14', 'relationship' => 'parent',  'national_id' => '001065003333', 'tax_reduction_from' => '2023-03-01', 'tax_reduction_to' => null, 'created_at' => $now, 'updated_at' => $now],

            // Employee 9 (emp004) - wife and child
            ['id' => 7,  'employee_id' => 9,  'full_name' => 'Le Thi Thuy',          'dob' => '1993-08-12', 'relationship' => 'spouse',  'national_id' => '001093004444', 'tax_reduction_from' => '2022-01-01', 'tax_reduction_to' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8,  'employee_id' => 9,  'full_name' => 'Do Gia Bao',           'dob' => '2022-03-15', 'relationship' => 'child',   'national_id' => null,           'tax_reduction_from' => '2022-04-01', 'tax_reduction_to' => null, 'created_at' => $now, 'updated_at' => $now],

            // Employee 11 (emp006) - wife
            ['id' => 9,  'employee_id' => 11, 'full_name' => 'Pham Thi Dung',        'dob' => '1991-01-25', 'relationship' => 'spouse',  'national_id' => '001091005555', 'tax_reduction_from' => '2022-02-01', 'tax_reduction_to' => null, 'created_at' => $now, 'updated_at' => $now],

            // Employee 13 (emp008) - child
            ['id' => 10, 'employee_id' => 13, 'full_name' => 'Le Minh Khang',        'dob' => '2020-11-08', 'relationship' => 'child',   'national_id' => null,           'tax_reduction_from' => '2021-01-01', 'tax_reduction_to' => null, 'created_at' => $now, 'updated_at' => $now],
        ];

        $this->insertWithIdentity('dependents', $dependents);
    }
}

