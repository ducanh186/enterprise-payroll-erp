# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working in this repository.

## Project Overview

Enterprise Payroll ERP is a web-based HRM/payroll system for Vietnamese business workflows:
- attendance tracking and attendance requests
- labour contracts and allowance management
- payroll period operations, payslips, bonus/deductions
- operational and payroll reports
- RBAC with role and permission checks

The business context is Vietnamese payroll and compliance terminology: BHXH, BHYT, BHTN, PIT brackets, Vietnamese employee names, and month-based payroll periods.

## Current State

This repository is no longer mock-only.

- Backend routes, controllers, models, migrations, seeders, and SQL Server scripts are present.
- Frontend contains real routed pages for employees, attendance, payroll, reports, and admin screens.
- Authentication uses Sanctum bearer tokens for API access.
- SQL Server scripts exist for client DB handoff, including tables, views, functions, stored procedures, and seed data.

Some documentation in the repo may still mention an older mock-only phase. Prefer the actual code layout over older notes.

## Architecture

```text
React 19 (Vite + TS + Tailwind 4) -> Laravel 11 API (Sanctum bearer token) -> SQL Server 2022
         :5173                                :8001 via Docker / :8000 local          :1433
```

Current backend flow is domain-oriented:

```text
FE -> API Route -> Controller -> Service -> Eloquent/DB layer -> API envelope -> FE
```

Planned SQL Server integration direction remains:

```text
FE -> Controller -> Service -> Repository/DB call -> SQL views/SP/functions -> DTO/array -> FE
```

Key principle:
- keep domain API endpoints stable
- do not expose a generic "execute arbitrary stored procedure" endpoint
- integrate SQL logic behind services or repositories

## Monorepo Structure

- `backend/` - Laravel 11 API
- `backend/database/sql/` - SQL Server handoff scripts for client DB team
- `frontend/` - React 19 app
- `frontend/design/` - Stitch-generated design references, not runtime app code
- `UML_des/` - UML and business-flow reference artifacts

## Backend Snapshot

- API routes are defined in `backend/routes/api.php`
- Models are present in `backend/app/Models/`
- Services in `backend/app/Services/` contain real business logic and DB-backed operations
- Payroll and attendance flows already use `DB::transaction(...)` in service methods
- Reports currently use Laravel queries/service logic; report templates still store `sp_name` metadata for SQL-backed handoff

Main modules:
- Auth
- Reference
- Employee
- Contract
- Attendance
- Payroll
- Reports
- Admin

Roles used across the system:
- `hr_staff`
- `accountant`
- `system_admin`
- `management`

## Frontend Snapshot

- API wrapper lives in `frontend/src/lib/api.ts`
- Auth state is managed in `frontend/src/context/AuthContext.tsx`
- Protected routes render inside `frontend/src/layouts/AppLayout.tsx`
- Routed pages live in `frontend/src/pages/`

Important:
- frontend text should remain Vietnamese with proper diacritics
- code identifiers stay English
- the app already has routed pages for employees, attendance logs/summary, payroll run/periods/payslips, reports, and admin

## Authentication Mode

- API auth uses Sanctum personal access token via `Authorization: Bearer <token>`
- frontend stores and attaches the bearer token on every request
- do not switch to SPA cookie/CSRF login flow unless the project is intentionally redesigned for that

## API Response Format

Controllers use the `ApiResponse` trait and return envelopes shaped like:

```json
{
  "success": true,
  "data": {},
  "message": "OK",
  "errors": null
}
```

Paginated responses also include:

```json
{
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

## SQL Server Notes

SQL Server artifacts already exist under `backend/database/sql/`:
- `01_tables.sql`
- `02_views.sql`
- `03_functions.sql`
- `04_stored_procedures.sql`
- `05_seed_data.sql`

The repository already contains SQL procedure definitions for attendance, payroll, and report wrappers. When wiring client-provided SQL into the app:
- keep mapping at the service/repository layer
- preserve existing API routes
- keep request/response contracts stable for frontend pages

## Common Commands

```bash
# Backend
cd backend
composer install
php artisan key:generate
php artisan serve
php artisan route:list --path=api
php artisan test
composer test:sqlite

# Frontend
cd frontend
npm install
npm run dev
npm run build
npm run lint

# Full stack
docker compose up -d --build
docker compose down
docker compose logs -f backend
```

## Conventions

- Controllers: `backend/app/Http/Controllers/Api/`
- Services: `backend/app/Services/`
- Models: `backend/app/Models/`
- Form requests: `backend/app/Http/Requests/`
- Enums: `backend/app/Enums/`
- Frontend pages: `frontend/src/pages/`
- Shared frontend helpers: `frontend/src/lib/`
- Vietnamese labels in UI, English in code

## Documentation Priority

When instructions conflict:
1. trust the current code
2. trust the SQL scripts and migrations for schema direction
3. treat older prose documentation as potentially stale
