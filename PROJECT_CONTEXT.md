# Enterprise Payroll ERP - Project Context

Last inspected: 2026-05-16

This file is a practical map of the project for a beginner ICT learner. It explains what the system is, where the important code lives, how data moves through the app, and what trade-offs are visible from the current repository.

## 1. Short Overview

This repository is an Enterprise Payroll ERP for HR, attendance, contracts, payroll, reporting, permissions, and customer payroll workflows. In simple terms: it helps a company manage employees, record attendance, calculate salary, generate payslips, and control who can access which modules.

The project is a monorepo: one repository contains both the backend and the frontend.

- `backend/`: Laravel API application.
- `frontend/`: React single-page application.
- `docker-compose.yml`: local Docker environment for backend, frontend, and SQL Server.
- `docs/`, `test-evidence/`, `.firecrawl/`: project notes, implementation plans, and verification evidence.

## 2. Important Beginner Terms

The first three explanations use Vietnamese plus English. After that, the English term is used directly.

### Stack

- Level 5-year-old: "Ngăn xếp (Stack)" is like a pile of tools. This app uses one tool for the server, one tool for the website screen, and one tool for the database.
- Level middle-school: "Ngăn xếp (Stack)" means the main technologies used together to build the system.
- Level first-year university: "Ngăn xếp (Stack)" is the set of runtime, framework, database, libraries, and tooling that define how the system is built, deployed, and tested.

After this point, this document uses `Stack`.

### Backend

- Level 5-year-old: "Phía sau (Backend)" is the kitchen. Users do not see it directly, but it prepares the result.
- Level middle-school: "Phía sau (Backend)" receives requests, checks permissions, reads/writes database records, and returns data.
- Level first-year university: "Phía sau (Backend)" is the server-side application layer exposing API endpoints, enforcing authentication/authorization, running business logic, and coordinating persistence.

After this point, this document uses `Backend`.

### Frontend

- Level 5-year-old: "Phía trước (Frontend)" is the screen and buttons the user touches.
- Level middle-school: "Phía trước (Frontend)" is the browser app that shows forms, tables, navigation, and reports.
- Level first-year university: "Phía trước (Frontend)" is the client-side application that handles routing, UI state, API calls, authentication state, and user interaction.

After this point, this document uses `Frontend`.

## 3. Current Stack

### Backend Stack

- PHP `^8.3`
- Laravel `^13.0`
- Laravel Sanctum for token-based API authentication
- PHPUnit `^12.5.12`
- SQL Server in Docker for local/customer-like runtime
- SQLite fallback for automated tests
- Laravel service/controller pattern

Key files:

- `backend/composer.json`
- `backend/routes/api.php`
- `backend/app/Http/Controllers/Api/`
- `backend/app/Services/`
- `backend/app/Models/`
- `backend/database/migrations/`
- `backend/tests/`

### Frontend Stack

- React `^19.2.4`
- TypeScript `~5.9.3`
- Vite `^8.0.1`
- React Router `^7.13.1`
- TanStack Query `^5.91.0`
- Axios `^1.13.6`
- Tailwind CSS `^4.2.2`
- Lucide React icons
- Playwright for E2E smoke tests

Key files:

- `frontend/package.json`
- `frontend/src/App.tsx`
- `frontend/src/lib/api.ts`
- `frontend/src/context/AuthContext.tsx`
- `frontend/src/layouts/AppLayout.tsx`
- `frontend/src/pages/`
- `frontend/e2e/app-smoke.spec.ts`

## 4. Repository Structure

Top-level structure:

```text
enterprise-payroll-erp/
  backend/
  frontend/
  docker/
  docs/
  scripts/
  test-evidence/
  UML_design/
  .firecrawl/
  docker-compose.yml
  README.md
  Brief.pdf
  HRM_BRD_SRS_Permission_API.docx
  bao-cao-kiem-tra-frontend-payroll-erp.md
  mentor_syncup_combined_plan (1).md
```

Important note: Laravel is inside `backend/`, not at the repository root. React is inside `frontend/`.

## 5. What The App Does

From the code and existing docs, the app covers these business domains:

- Authentication and current-user permission lookup.
- User, role, and permission administration.
- Employee records and employee details.
- Dependents for employees.
- Labour contracts and contract types.
- Departments and positions.
- Attendance logs, manual check-in, daily attendance, monthly summaries.
- Leave or attendance requests with approve/reject flow.
- Shift assignments, shifts, holidays, late/early rules.
- Payroll periods, parameters, adjustments, payroll runs, payslips.
- Payroll email payslip execution.
- Report templates, report preview, report export, and download.
- Procedure catalog and stored-procedure execution.
- Fujimart/customer-specific HRM workflows documented under `docs/` and `test-evidence/`.

## 6. Backend Architecture

The Backend mostly follows this flow:

```text
HTTP request
  -> route in backend/routes/api.php
  -> API controller in backend/app/Http/Controllers/Api/
  -> service in backend/app/Services/
  -> model/repository/database/procedure
  -> JSON response
```

This is a good beginner-friendly pattern because each layer has a clear job:

- Route: defines the URL and HTTP method.
- Controller: validates request and calls the right service.
- Service: contains business logic.
- Model: represents database tables and relationships.
- Migration: creates or changes database tables.
- Seeder: creates demo/reference data.

Main Backend controllers:

- `AdminController`: users, roles, permissions, password reset, role assignment.
- `AttendanceController`: logs, import, manual check-in, daily/monthly attendance, requests, shift assignments.
- `AuthController`: login, logout, current user, current permissions.
- `ContractController`: contract CRUD and contract rules.
- `EmployeeController`: employee CRUD, suspend, active contract, dependents.
- `PayrollController`: periods, preview, calculation, payslips, adjustments, email payslip.
- `ProcedureController`: procedure catalog metadata and execution.
- `ReferenceController`: shifts, holidays, contract types, payroll parameters, salary levels/scales, allowances.
- `ReportController`: templates, preview, export, download.

Main Backend services:

- `AdminService`
- `AttendanceService`
- `AuthService`
- `CustomerProcedureService`
- `EmployeeService`
- `ExcelWorkbookService`
- `PayrollService`
- `ProcedureService`
- `ReferenceService`
- `ReportService`

## 7. API Groups

The API routes are grouped mainly by domain:

- `auth`: login.
- authenticated routes via Sanctum:
  - `/auth/logout`
  - `/me`
  - `/me/permissions`
- `reference`: shifts, holidays, contract types, payroll types, payroll parameters, salary levels/scales, allowances.
- `employees`: employee list/detail/create/update/suspend, dependents, active contract.
- `contracts`: labour contracts.
- `attendance`: logs, import, manual check-in, daily records, monthly summary, requests, shift assignments.
- `payroll`: periods, preview, calculate, finalize, lock, payslips, payslip items, adjustments, email payslip.
- `reports`: templates, preview, export, download.
- `procedures`: procedure list, metadata, execution.

Permission middleware appears in the route layer, for example `permission:employee.view`, `permission:reference.view`, and `permission:reports.view`.

## 8. Database Context

The main database schema is managed by Laravel migrations under `backend/database/migrations/`.

Core table groups:

- Auth and RBAC:
  - `users`
  - `roles`
  - `permissions`
  - `user_roles`
  - `role_permissions`
  - `personal_access_tokens`
- Organization:
  - `departments`
  - `positions`
  - `employees`
  - `dependents`
- Contracts and salary references:
  - `contract_types`
  - `payroll_types`
  - `salary_levels`
  - `labour_contracts`
  - `allowance_types`
  - `contract_allowances`
- Attendance:
  - `shifts`
  - `holidays`
  - `late_early_rules`
  - `attendance_periods`
  - `shift_assignments`
  - `time_logs`
  - `attendance_requests`
  - `attendance_request_details`
  - `attendance_daily`
  - `attendance_monthly_summary`
- Payroll:
  - `payroll_parameters`
  - `payroll_parameter_details`
  - `bonus_deduction_types`
  - `bonus_deductions`
  - `payroll_runs`
  - `payslips`
  - `payslip_items`
- Reporting and system:
  - `report_templates`
  - `audit_logs`
  - `attachments`
  - `system_configs`
  - procedure catalog tables

Recent migration context:

- `2026_05_12_000001_add_resign_date_to_employees_table.php`
- `2026_05_15_000001_add_status_to_contract_types_payroll_types_salary_levels.php`

Seeders live under `backend/database/seeders/`, including:

- `RolePermissionSeeder.php`
- `DepartmentPositionSeeder.php`
- `EmployeeSeeder.php`
- `PayrollSeeder.php`
- `ProcedureCatalogSeeder.php`
- `DemoVolumeSeeder.php`
- `CustomerEmployeeSeeder.php`

SQL Server support files live under `backend/database/sql/`:

- `01_tables.sql`
- `02_views.sql`
- `03_functions.sql`
- `04_stored_procedures.sql`
- `05_seed_data.sql`
- `procedure_template.sql`

## 9. Frontend Architecture

The Frontend is a React SPA. The main router is in `frontend/src/App.tsx`.

Core Frontend flow:

```text
User opens browser
  -> React route in App.tsx
  -> page component under frontend/src/pages/
  -> API helper in frontend/src/lib/api.ts
  -> Backend API at http://localhost:8001/api
  -> render table/form/result
```

Authentication flow:

- `frontend/src/context/AuthContext.tsx` stores auth state.
- `frontend/src/lib/auth.ts` handles stored token/session helpers.
- `frontend/src/lib/api.ts` attaches the bearer token to every request.
- If an API response returns `401`, the Frontend clears the stored session and dispatches `auth:session-cleared`.

Main routes:

- `/login`
- `/`
- `/employees`
- `/reference/contract-types`
- `/reference/salary-levels`
- `/reference/allowances`
- `/contracts`
- `/contracts/:id`
- `/reference/late-early-rules`
- `/reference/holidays`
- `/reference/shifts`
- `/attendance`
- `/attendance/shift-assignments`
- `/attendance/logs`
- `/attendance/leave-requests`
- `/attendance/manual`
- `/attendance/summary`
- `/payroll`
- `/payroll/parameters`
- `/payroll/bonus-deductions`
- `/payroll/run`
- `/payroll/payslips/email`
- `/payroll/periods`
- `/payroll/payslips`
- `/payroll/payslips/:id`
- `/reports`
- `/procedures`
- `/admin`
- `/admin/users`
- `/admin/roles`

Main page files:

- `DashboardPage.tsx`
- `EmployeesPage.tsx`
- `ContractsPage.tsx`
- `AttendanceLogsPage.tsx`
- `AttendanceSummaryPage.tsx`
- `PayrollRunPage.tsx`
- `PayrollEmailPayslipsPage.tsx`
- `PayslipsPage.tsx`
- `ReportsPage.tsx`
- `ProceduresPage.tsx`
- `AdminUsersPage.tsx`
- `RolePermissionsPage.tsx`
- reference pages such as `SalaryLevelsPage.tsx`, `ContractTypesPage.tsx`, `ShiftsPage.tsx`, `HolidaysPage.tsx`

## 10. Docker And Local Runtime

`docker-compose.yml` defines three main services:

- `backend`
  - container: `payroll-backend`
  - host port: `8001`
  - internal Laravel port: `8000`
  - database connection: SQL Server
- `frontend`
  - container: `payroll-frontend`
  - host port: `5173`
  - API base URL: `http://localhost:8001/api`
- `sqlserver`
  - container: `payroll-sqlserver`
  - host port: `1433`
  - image: `mcr.microsoft.com/mssql/server:2025-latest`
  - database: `enterprise_payroll_erp`
  - customer database: `fujimart_hrm_source`

Common local commands from the repository docs:

```powershell
docker compose up -d --build
```

```powershell
.\scripts\restore-fujimart-db.ps1
```

```powershell
docker compose exec -T backend php artisan migrate:fresh --seed --force
```

```powershell
docker compose exec -T backend composer test:sqlite
```

```powershell
docker compose exec -T frontend npm run build
```

```powershell
docker compose exec -T frontend npm run lint
```

```powershell
cd frontend
$env:E2E_BASE_URL = "http://localhost:5173"
npm run test:e2e:smoke
```

## 11. Test And Verification Context

Backend test files under `backend/tests/Feature/` include:

- `AdminFlowTest.php`
- `AttendanceFlowTest.php`
- `AttendanceImportTest.php`
- `AttendanceRequestFlowTest.php`
- `AuthApiTest.php`
- `EmployeeContractFlowTest.php`
- `PayrollApiTest.php`
- `RbacFlowTest.php`
- `ReportFlowTest.php`
- `ServiceBackendGroup1Test.php`
- `ServiceBackendGroup2Test.php`

Frontend E2E smoke test:

- `frontend/e2e/app-smoke.spec.ts`

Existing evidence docs mention a prior verification loop:

- Backend: `OK (62 tests, 650 assertions)`
- Frontend build: Vite build passed
- Frontend lint: ESLint passed
- UI smoke: `7 passed`

That evidence is historical. Re-run the commands above before claiming the current workspace still passes.

## 12. Existing Project Documents

Important docs and evidence files:

- `README.md`: E2E Fujimart HRM test commands.
- `docs/FUJIMART_THE5_E2E_EVIDENCE.md`: evidence map for Fujimart "The 5" implementation.
- `docs/superpowers/specs/2026-05-12-fujimart-the-5-design.md`: prior design spec.
- `docs/superpowers/plans/2026-05-12-fujimart-the-5.md`: prior implementation plan.
- `bao-cao-kiem-tra-frontend-payroll-erp.md`: Vietnamese frontend audit report.
- `.firecrawl/hrm-tab3-20260515.md`: scraped/project requirement context.
- `test-evidence/`: screenshots, logs, exported Excel files, and rerun evidence.
- `Brief.pdf`: untracked local brief file at inspection time.
- `HRM_BRD_SRS_Permission_API.docx`: requirement/API document.

## 13. Data Flow Examples

### Example 1: User logs in

```text
LoginPage
  -> POST /api/login
  -> AuthController@login
  -> AuthService@login
  -> token generated by Sanctum
  -> Frontend stores token
  -> api.ts attaches token to future requests
```

### Example 2: HR opens employee list

```text
EmployeesPage
  -> GET /api/employees
  -> EmployeeController@index
  -> EmployeeService@getEmployees
  -> Employee model/database
  -> JSON list
  -> React table renders employees
```

### Example 3: Payroll user runs payroll

```text
PayrollRunPage
  -> preview/open/calculate API request
  -> PayrollController
  -> PayrollService
  -> attendance periods + payroll parameters + payslip tables
  -> payroll run and payslip data returned
```

### Example 4: Report is exported

```text
ReportsPage
  -> report preview/export API
  -> ReportController
  -> ReportService
  -> ExcelWorkbookService if export is needed
  -> generated file/download response
```

## 14. Trade-Offs Visible In The Current Project

### Monorepo

Benefit: Backend and Frontend are easy to coordinate in one place.

Trade-off: A beginner must remember that Laravel commands run inside `backend/`, while React commands run inside `frontend/`.

### Service Layer

Benefit: Business logic is not jammed directly into controllers.

Trade-off: To understand one feature, you usually need to inspect route, controller, service, model, migration, and frontend page together.

### SQL Server Runtime + SQLite Tests

Benefit: SQL Server matches customer-like deployment better, while SQLite makes automated tests faster and simpler.

Trade-off: Some SQL Server-specific behavior may not be fully caught by SQLite tests.

### Customer Procedure Support

Benefit: The app can integrate with customer-specific stored procedures such as Fujimart payroll/report workflows.

Trade-off: Stored procedures create a stronger dependency on SQL Server schema and customer database availability.

### Permission Middleware

Benefit: Access control is centralized near API routes.

Trade-off: When a page fails with 403, debugging needs both frontend RBAC visibility and backend permission middleware checks.

## 15. Current Git State At Inspection Time

The workspace is a Git repository.

- Branch: `main`
- HEAD: `e6136bc`
- Dirty/untracked files:
  - modified: `excalidraw.log`
  - untracked: `Brief.pdf`
  - untracked: `mentor_syncup_combined_plan (1).md`

This new file, `PROJECT_CONTEXT.md`, was added as documentation only.

## 16. How To Read This Project Next

Recommended beginner path:

1. Read `README.md` to understand the official local verification loop.
2. Open `docker-compose.yml` to understand the three running services.
3. Open `frontend/src/App.tsx` to see all user-facing pages.
4. Pick one route, for example `/employees`.
5. Follow the flow:
   - `frontend/src/pages/EmployeesPage.tsx`
   - `frontend/src/lib/api.ts`
   - `backend/routes/api.php`
   - `backend/app/Http/Controllers/Api/EmployeeController.php`
   - `backend/app/Services/EmployeeService.php`
   - `backend/app/Models/Employee.php`
   - `backend/database/migrations/*employees*`
6. Run the smallest relevant test after reading one feature.

This style helps you avoid feeling lost. You follow one vertical slice from screen to database instead of trying to understand every file at once.

## 17. Assumptions And Limits Of This Context File

- This file was generated from current repository files and command output.
- I did not run the full Docker/test verification loop while creating this context file.
- Historical test evidence is included only as previous evidence, not as a fresh pass.
- `Brief.pdf` and `HRM_BRD_SRS_Permission_API.docx` were identified as important documents, but their full internal content was not extracted in this pass.
- No production code was changed.

