<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\IdentityInsert;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use IdentityInsert;
    public function run(): void
    {
        DB::table('user_roles')->delete();
        DB::table('users')->where('id', '>', 0)->delete();

        $now = now();
        $pw = Hash::make('password');

        // ---------------------------------------------------------------
        // Users: 15 total (admin01, hr01, hr02, payroll01, manager01, emp001-emp010)
        // ---------------------------------------------------------------
        $users = [
            ['id' => 1,  'name' => 'Nguyen Van Admin',   'username' => 'admin01',   'email' => 'admin01@erp.vn',    'password' => $pw, 'phone' => '0901000001', 'is_active' => true, 'last_login_at' => $now, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2,  'name' => 'Tran Thi HR',        'username' => 'hr01',      'email' => 'hr01@erp.vn',       'password' => $pw, 'phone' => '0901000002', 'is_active' => true, 'last_login_at' => $now, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3,  'name' => 'Le Van HR',           'username' => 'hr02',      'email' => 'hr02@erp.vn',       'password' => $pw, 'phone' => '0901000003', 'is_active' => true, 'last_login_at' => null, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4,  'name' => 'Pham Thi Payroll',    'username' => 'payroll01', 'email' => 'payroll01@erp.vn',  'password' => $pw, 'phone' => '0901000004', 'is_active' => true, 'last_login_at' => $now, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5,  'name' => 'Hoang Van Manager',   'username' => 'manager01', 'email' => 'manager01@erp.vn', 'password' => $pw, 'phone' => '0901000005', 'is_active' => true, 'last_login_at' => $now, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6,  'name' => 'Vo Thi Mai',          'username' => 'emp001',    'email' => 'emp001@erp.vn',     'password' => $pw, 'phone' => '0901000006', 'is_active' => true, 'last_login_at' => null, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7,  'name' => 'Dang Van Tuan',       'username' => 'emp002',    'email' => 'emp002@erp.vn',     'password' => $pw, 'phone' => '0901000007', 'is_active' => true, 'last_login_at' => null, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8,  'name' => 'Bui Thi Lan',         'username' => 'emp003',    'email' => 'emp003@erp.vn',     'password' => $pw, 'phone' => '0901000008', 'is_active' => true, 'last_login_at' => null, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9,  'name' => 'Do Van Hung',         'username' => 'emp004',    'email' => 'emp004@erp.vn',     'password' => $pw, 'phone' => '0901000009', 'is_active' => true, 'last_login_at' => null, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 10, 'name' => 'Ngo Thi Huong',       'username' => 'emp005',    'email' => 'emp005@erp.vn',     'password' => $pw, 'phone' => '0901000010', 'is_active' => true, 'last_login_at' => null, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 11, 'name' => 'Nguyen Van Thanh',    'username' => 'emp006',    'email' => 'emp006@erp.vn',     'password' => $pw, 'phone' => '0901000011', 'is_active' => true, 'last_login_at' => null, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 12, 'name' => 'Tran Van Duc',        'username' => 'emp007',    'email' => 'emp007@erp.vn',     'password' => $pw, 'phone' => '0901000012', 'is_active' => true, 'last_login_at' => null, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 13, 'name' => 'Le Thi Ngoc',         'username' => 'emp008',    'email' => 'emp008@erp.vn',     'password' => $pw, 'phone' => '0901000013', 'is_active' => true, 'last_login_at' => null, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 14, 'name' => 'Pham Van Long',       'username' => 'emp009',    'email' => 'emp009@erp.vn',     'password' => $pw, 'phone' => '0901000014', 'is_active' => true, 'last_login_at' => null, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 15, 'name' => 'Hoang Thi Thao',      'username' => 'emp010',    'email' => 'emp010@erp.vn',     'password' => $pw, 'phone' => '0901000015', 'is_active' => true, 'last_login_at' => null, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
        ];

        $this->insertWithIdentity('users', $users);

        // ---------------------------------------------------------------
        // User-Role assignments
        // ---------------------------------------------------------------
        $userRoles = [
            // admin01 => system_admin + employee
            ['id' => 1,  'user_id' => 1,  'role_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2,  'user_id' => 1,  'role_id' => 5, 'created_at' => $now, 'updated_at' => $now],
            // hr01 => hr_staff + employee
            ['id' => 3,  'user_id' => 2,  'role_id' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4,  'user_id' => 2,  'role_id' => 5, 'created_at' => $now, 'updated_at' => $now],
            // hr02 => hr_staff + employee
            ['id' => 5,  'user_id' => 3,  'role_id' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6,  'user_id' => 3,  'role_id' => 5, 'created_at' => $now, 'updated_at' => $now],
            // payroll01 => accountant + employee
            ['id' => 7,  'user_id' => 4,  'role_id' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8,  'user_id' => 4,  'role_id' => 5, 'created_at' => $now, 'updated_at' => $now],
            // manager01 => management + employee
            ['id' => 9,  'user_id' => 5,  'role_id' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 10, 'user_id' => 5,  'role_id' => 5, 'created_at' => $now, 'updated_at' => $now],
            // emp001-emp010 => employee only
            ['id' => 11, 'user_id' => 6,  'role_id' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 12, 'user_id' => 7,  'role_id' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 13, 'user_id' => 8,  'role_id' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 14, 'user_id' => 9,  'role_id' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 15, 'user_id' => 10, 'role_id' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 16, 'user_id' => 11, 'role_id' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 17, 'user_id' => 12, 'role_id' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 18, 'user_id' => 13, 'role_id' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 19, 'user_id' => 14, 'role_id' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 20, 'user_id' => 15, 'role_id' => 5, 'created_at' => $now, 'updated_at' => $now],
        ];

        $this->insertWithIdentity('user_roles', $userRoles);
    }
}

