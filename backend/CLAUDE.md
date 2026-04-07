# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working in `backend/`.

## Backend Overview

Laravel 11 API for Enterprise Payroll ERP.

The backend is DB-backed, not mock-only:
- API routes are active and grouped by business module
- Eloquent models exist for the current schema
- migrations and seeders cover the working schema
- attendance and payroll services already perform real DB writes and calculations
- SQL Server scripts are maintained under `database/sql/` for client handoff

## Runtime Architecture

```text
routes/api.php -> Controller -> Service -> Eloquent/DB facade -> ApiResponse
```

Preferred future integration for client-owned SQL logic:

```text
Controller -> Service -> Repository/DB call -> SQL Server view/SP/function
```

Do not introduce a generic "run any SP" endpoint. Keep domain routes stable and map SQL logic behind services or repositories.

## Main Modules

- Auth
- Reference
- Employee
- Contract
- Attendance
- Payroll
- Reports
- Admin

## Key Files

- `routes/api.php` - API route surface
- `app/Http/Controllers/Api/` - module controllers
- `app/Services/` - core business logic
- `app/Models/` - Eloquent models
- `app/Enums/` - status, role, and domain enums
- `database/migrations/` - schema
- `database/seeders/` - seeded reference and sample data
- `database/sql/` - SQL Server handoff scripts

## Commands

```bash
composer install
php artisan key:generate
php artisan serve
php artisan route:list --path=api
php artisan test
./vendor/bin/pint
```

Useful project-specific test entrypoints:

```bash
composer test:sqlite
composer test:auth
composer test:services
composer test:payroll
composer test:feature
composer test:unit
```

## Authentication

- Sanctum personal access token flow
- API clients send `Authorization: Bearer <token>`
- current app behavior is stateless API login, not SPA cookie auth

## Business Logic Locations

- Auth fallback and mock user compatibility: `app/Services/AuthService.php`
- Attendance operations and recalculation flows: `app/Services/AttendanceService.php`
- Payroll period/run logic, payslips, adjustments: `app/Services/PayrollService.php`
- Reports and report template mapping: `app/Services/ReportService.php`
- Permission matrix enforcement: middleware and enum/permission mapping

## Response Convention

Controllers use `ApiResponse` and should keep a stable envelope:

```json
{
  "success": true,
  "data": {},
  "message": "OK",
  "errors": null
}
```

Keep response shape consistent when replacing service internals with SQL-backed implementations.

## SQL Server Integration Guidance

The backend already contains SQL Server-oriented work:
- `config/database.php` contains `sqlsrv` connection config
- Docker image enables `sqlsrv` and `pdo_sqlsrv`
- `database/sql/` contains tables, views, functions, procedures, and seed scripts
- report templates include `sp_name` metadata

When integrating client procedures/functions:
1. preserve the existing route and controller contract
2. isolate direct DB/SP calls in a repository or clearly bounded service helper
3. normalize DB results into the existing array/API shapes
4. avoid leaking SQL-specific column names directly to the frontend unless the FE contract is intentionally updated

## Data Model Notes

The backend already includes models for:
- RBAC: `User`, `Role`, `Permission`
- HR: `Employee`, `Department`, `Position`, `Dependent`
- Contract: `LabourContract`, `ContractType`, `PayrollType`, `SalaryLevel`, `ContractAllowance`, `AllowanceType`
- Attendance: `AttendancePeriod`, `Shift`, `ShiftAssignment`, `TimeLog`, `AttendanceDaily`, `AttendanceMonthlySummary`, `AttendanceRequest`, `AttendanceRequestDetail`, `Holiday`, `LateEarlyRule`
- Payroll: `PayrollParameter`, `PayrollParameterDetail`, `PayrollRun`, `Payslip`, `PayslipItem`, `BonusDeduction`, `BonusDeductionType`
- System/reporting: `ReportTemplate`, `SystemConfig`, `Attachment`, `AuditLog`

## Conventions

- Controllers in `app/Http/Controllers/Api/`
- Services in `app/Services/`
- Models in `app/Models/`
- Prefer enum-backed statuses where already present
- Keep business logic in services; controllers stay thin
- Keep UI-facing labels/messages appropriate for Vietnamese business users

## Caution

Some repo-level docs still describe an earlier mock phase. In `backend/`, trust:
1. current routes
2. current models/services
3. migrations/seeders
4. SQL scripts
