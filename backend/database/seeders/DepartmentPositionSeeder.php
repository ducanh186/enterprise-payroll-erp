<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentPositionSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('positions')->delete();
        DB::table('departments')->delete();

        $now = now();

        // ---------------------------------------------------------------
        // Departments
        // ---------------------------------------------------------------
        $departments = [
            ['id' => 1, 'code' => 'BOD',   'name' => 'Ban Giam Doc',           'parent_id' => null, 'manager_employee_id' => null, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'code' => 'HR',     'name' => 'Phong Nhan Su',          'parent_id' => 1,    'manager_employee_id' => null, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'code' => 'ACCT',   'name' => 'Phong Ke Toan',          'parent_id' => 1,    'manager_employee_id' => null, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'code' => 'OPS',    'name' => 'Phong Van Hanh',         'parent_id' => 1,    'manager_employee_id' => null, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'code' => 'ENG',    'name' => 'Phong Ky Thuat',         'parent_id' => 1,    'manager_employee_id' => null, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'code' => 'QA',     'name' => 'Phong Kiem Dinh',        'parent_id' => 4,    'manager_employee_id' => null, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7, 'code' => 'SALES',  'name' => 'Phong Kinh Doanh',       'parent_id' => 1,    'manager_employee_id' => null, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8, 'code' => 'IT',     'name' => 'Phong Cong Nghe Thong Tin', 'parent_id' => 1, 'manager_employee_id' => null, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('departments')->insert($departments);

        // ---------------------------------------------------------------
        // Positions (linked to departments)
        // ---------------------------------------------------------------
        $positions = [
            // BOD
            ['id' => 1,  'code' => 'GD',      'name' => 'Giam Doc',               'department_id' => 1, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2,  'code' => 'PGD',      'name' => 'Pho Giam Doc',           'department_id' => 1, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],

            // HR
            ['id' => 3,  'code' => 'TP_NS',    'name' => 'Truong Phong Nhan Su',   'department_id' => 2, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4,  'code' => 'NV_NS',    'name' => 'Nhan Vien Nhan Su',      'department_id' => 2, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],

            // Accounting
            ['id' => 5,  'code' => 'TP_KT',    'name' => 'Truong Phong Ke Toan',   'department_id' => 3, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6,  'code' => 'NV_KT',    'name' => 'Nhan Vien Ke Toan',      'department_id' => 3, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],

            // Operations
            ['id' => 7,  'code' => 'TP_VH',    'name' => 'Truong Phong Van Hanh',  'department_id' => 4, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8,  'code' => 'NV_VH',    'name' => 'Nhan Vien Van Hanh',     'department_id' => 4, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],

            // Engineering
            ['id' => 9,  'code' => 'TP_KTH',   'name' => 'Truong Phong Ky Thuat',  'department_id' => 5, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 10, 'code' => 'NV_KTH',   'name' => 'Nhan Vien Ky Thuat',     'department_id' => 5, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],

            // QA
            ['id' => 11, 'code' => 'NV_QA',    'name' => 'Nhan Vien Kiem Dinh',    'department_id' => 6, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],

            // Sales
            ['id' => 12, 'code' => 'NV_KD',    'name' => 'Nhan Vien Kinh Doanh',   'department_id' => 7, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],

            // IT
            ['id' => 13, 'code' => 'TP_IT',    'name' => 'Truong Phong IT',         'department_id' => 8, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 14, 'code' => 'NV_IT',    'name' => 'Nhan Vien IT',            'department_id' => 8, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('positions')->insert($positions);
    }
}

