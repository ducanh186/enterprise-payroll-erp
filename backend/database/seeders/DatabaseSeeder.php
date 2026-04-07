<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Run order respects foreign key constraints:
     * 1. Roles & Permissions (no FK dependencies)
     * 2. Departments & Positions (no FK dependencies)
     * 3. Users (depends on nothing, but user_roles needs roles)
     * 4. Employees (depends on users, departments, positions)
     * 5. Contracts (depends on employees)
     * 6. Shifts & Holidays (no FK dependencies on above)
     * 7. Attendance (depends on employees, shifts, attendance_periods)
     * 8. Payroll (depends on attendance_periods, employees, contracts)
     * 9. System (report_templates, system_configs, audit_logs)
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            DepartmentPositionSeeder::class,
            UserSeeder::class,
            EmployeeSeeder::class,
            ContractSeeder::class,
            ShiftHolidaySeeder::class,
            AttendanceSeeder::class,
            PayrollSeeder::class,
            SystemSeeder::class,
            ProcedureCatalogSeeder::class,
        ]);
    }
}

