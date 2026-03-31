<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\IdentityInsert;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    use IdentityInsert;

    public function run(): void
    {
        // Truncate in correct order (child tables first)
        DB::table('role_permissions')->delete();
        DB::table('user_roles')->delete();
        DB::table('permissions')->delete();
        DB::table('roles')->delete();

        // ---------------------------------------------------------------
        // Roles
        // ---------------------------------------------------------------
        $now = now();

        $roles = [
            ['id' => 1, 'code' => 'system_admin',  'name' => 'System Admin',  'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'code' => 'hr_staff',       'name' => 'HR Staff',      'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'code' => 'accountant',     'name' => 'Accountant',    'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'code' => 'management',     'name' => 'Management',    'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'code' => 'employee',       'name' => 'Employee',      'created_at' => $now, 'updated_at' => $now],
        ];

        $this->insertWithIdentity('roles', $roles);

        // ---------------------------------------------------------------
        // Permissions (module.action format)
        // ---------------------------------------------------------------
        $permissionDefs = [
            // Dashboard module
            ['dashboard.view',       'View Dashboard',                  'dashboard'],

            // Auth module
            ['auth.login',          'Login',                            'auth'],
            ['auth.logout',         'Logout',                           'auth'],
            ['auth.profile',        'View Profile',                     'auth'],
            ['auth.change_password','Change Password',                  'auth'],

            // Reference module
            ['reference.view',      'View Reference Data',              'reference'],
            ['reference.manage',    'Manage Reference Data',            'reference'],

            // Employee module
            ['employee.view',       'View Employees',                   'employee'],
            ['employee.create',     'Create Employee',                  'employee'],
            ['employee.update',     'Update Employee',                  'employee'],
            ['employee.delete',     'Delete Employee',                  'employee'],

            // Contract module
            ['contract.view',       'View Contracts',                   'contract'],
            ['contract.create',     'Create Contract',                  'contract'],
            ['contract.update',     'Update Contract',                  'contract'],
            ['contract.renew',      'Renew Contract',                   'contract'],
            ['contract.terminate',  'Terminate Contract',               'contract'],

            // Attendance module
            ['attendance.view',           'View Attendance',            'attendance'],
            ['attendance.manage_period',  'Manage Attendance Periods',  'attendance'],
            ['attendance.import_logs',    'Import Time Logs',           'attendance'],
            ['attendance.calculate',      'Calculate Attendance',       'attendance'],
            ['attendance.manage_request', 'Manage Attendance Requests', 'attendance'],
            ['attendance.confirm',        'Confirm Attendance',         'attendance'],

            // Payroll module
            ['payroll.view',         'View Payroll',                    'payroll'],
            ['payroll.manage_param', 'Manage Payroll Parameters',       'payroll'],
            ['payroll.adjust',       'Manage Payroll Adjustments',      'payroll'],
            ['payroll.run',          'Run Payroll',                     'payroll'],
            ['payroll.finalize',     'Finalize Payroll',                'payroll'],
            ['payroll.lock',         'Lock Payroll',                    'payroll'],

            // Reports module
            ['reports.view',         'View Reports',                   'reports'],
            ['reports.export',       'Export Reports',                  'reports'],

            // Admin module
            ['admin.users',          'Manage Users',                   'admin'],
            ['admin.roles',          'Manage Roles',                   'admin'],
            ['admin.config',         'Manage System Config',           'admin'],
            ['admin.audit',          'View Audit Logs',                'admin'],
            ['admin.backup',         'Manage Backup & Restore',        'admin'],

            // Self-service module
            ['self.attendance.view', 'View Own Attendance',            'self_service'],
            ['self.request.manage',  'Manage Own Attendance Requests', 'self_service'],
            ['self.payslip.view',    'View Own Payslips',              'self_service'],
        ];

        $permissions = [];
        foreach ($permissionDefs as $idx => $def) {
            $permissions[] = [
                'id'         => $idx + 1,
                'code'       => $def[0],
                'name'       => $def[1],
                'module'     => $def[2],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->insertWithIdentity('permissions', $permissions);

        // ---------------------------------------------------------------
        // Role-Permission matrix
        // ---------------------------------------------------------------
        // Build a code-to-id map
        $permMap = collect($permissions)->pluck('id', 'code');

        $rolePermissionCodes = [
            'system_admin' => [
                'dashboard.view',
                'auth.login', 'auth.logout', 'auth.profile', 'auth.change_password',
                'payroll.manage_param',
                'admin.users', 'admin.roles', 'admin.config', 'admin.audit', 'admin.backup',
            ],
            'hr_staff' => [
                'dashboard.view',
                'auth.login', 'auth.logout', 'auth.profile', 'auth.change_password',
                'reference.view', 'reference.manage',
                'employee.view', 'employee.create', 'employee.update', 'employee.delete',
                'contract.view', 'contract.create', 'contract.update', 'contract.renew', 'contract.terminate',
                'attendance.view', 'attendance.manage_period', 'attendance.import_logs',
                'attendance.calculate', 'attendance.manage_request', 'attendance.confirm',
                'reports.view', 'reports.export',
            ],
            'accountant' => [
                'dashboard.view',
                'auth.login', 'auth.logout', 'auth.profile', 'auth.change_password',
                'reference.view',
                'employee.view',
                'contract.view',
                'attendance.view',
                'payroll.view', 'payroll.adjust', 'payroll.run', 'payroll.finalize', 'payroll.lock',
                'reports.view', 'reports.export',
            ],
            'management' => [
                'dashboard.view',
                'auth.login', 'auth.logout', 'auth.profile', 'auth.change_password',
                'reports.view', 'reports.export',
            ],
            'employee' => [
                'dashboard.view',
                'auth.login', 'auth.logout', 'auth.profile', 'auth.change_password',
                'self.attendance.view', 'self.request.manage', 'self.payslip.view',
            ],
        ];

        $rolePermissions = [];
        $rpId = 1;

        $roleMap = collect($roles)->pluck('id', 'code');
        foreach ($rolePermissionCodes as $roleCode => $codes) {
            foreach ($codes as $code) {
                $rolePermissions[] = [
                    'id' => $rpId++,
                    'role_id' => $roleMap[$roleCode],
                    'permission_id' => $permMap[$code],
                ];
            }
        }

        $this->insertWithIdentity('role_permissions', $rolePermissions);
    }
}
