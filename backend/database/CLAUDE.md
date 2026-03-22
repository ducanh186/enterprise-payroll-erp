# CLAUDE.md

Database layer for Enterprise Payroll ERP.

## Migrations

35+ migrations covering 8 table groups (see Plan.md Section 4 for full schema):
1. Identity/RBAC: users, roles, permissions, user_roles, role_permissions
2. Organization: departments, positions, employees, dependents
3. Contract: contract_types, payroll_types, salary_levels, labour_contracts, allowance_types, contract_allowances
4. Attendance Master: shifts, holidays, late_early_rules, attendance_periods
5. Attendance Transaction: shift_assignments, time_logs, attendance_requests, attendance_request_details
6. Attendance Calculated: attendance_daily, attendance_monthly_summary
7. Payroll: payroll_parameters, payroll_parameter_details, bonus_deduction_types, bonus_deductions, payroll_runs, payslips, payslip_items
8. System: report_templates, audit_logs, attachments, system_configs

## Seeders

Run order (enforced in DatabaseSeeder.php):
1. RolePermissionSeeder (5 roles, ~25 permissions, role_permissions matrix)
2. DepartmentPositionSeeder (8 departments + positions)
3. UserSeeder (admin01, hr01, hr02, payroll01, manager01, emp001-emp010)
4. EmployeeSeeder (15 employees linked to users)
5. ContractSeeder (contract types, payroll types, salary levels, labour contracts)
6. ShiftHolidaySeeder (4 shifts, 6 holidays, 6 late/early rules)
7. AttendanceSeeder (attendance periods, shift assignments, 400-500 time logs, requests)
8. PayrollSeeder (payroll parameters, bonus/deduction types, payroll runs, 20 payslips)
9. ReportTemplateSeeder (6 report templates)

## SQL Scripts (database/sql/)

SQL Server scripts for client's DB team:
- 01_tables.sql — CREATE TABLE for the current schema snapshot, including base Laravel tables and app tables
- 02_views.sql — 6 views
- 03_functions.sql — 6 scalar functions
- 04_stored_procedures.sql — 14 stored procedures, including 6 `sp_Report_*` wrappers for report templates
- 05_seed_data.sql — seed/master data aligned to the current seeders snapshot

## Commands

```bash
php artisan migrate:fresh --seed    # reset + seed all
php artisan migrate                 # run pending migrations
php artisan db:seed                 # seed only
php artisan db:seed --class=UserSeeder  # seed specific
```

## Conventions

- All money fields: `decimal(18,2)`
- All status fields: `string(20-30)` with enum values
- All FKs: `foreignId()->constrained()->onDelete('cascade')` unless nullable
- Timestamps: `$table->timestamps()` on most tables, except audit_logs (immutable)
