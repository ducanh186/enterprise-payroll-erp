# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this directory.

## Backend — Laravel 11 API

43 API endpoints across 7 modules, all returning mock data. Sanctum auth with role/permission middleware.

## Commands

```bash
composer install
php artisan key:generate
php artisan serve                        # http://localhost:8000
php artisan route:list --path=api        # list all routes
php artisan test                         # run PHPUnit tests
./vendor/bin/pint                        # code formatting (Laravel Pint)
```

## Module Architecture

```
routes/api.php → Controller → Service (mock data) → [future: Repository → SQL Server]
```

Each module: Controller + Service + FormRequest(s). No Eloquent models used yet (mock mode).

### Services (app/Services/)

All services are stateless classes returning hardcoded arrays. When DB arrives:
1. Create Eloquent models in `app/Models/`
2. Optionally create `app/Repositories/` for complex DB queries (views, SPs, functions)
3. Inject repositories into services, replace mock arrays

### Key Business Logic Locations

- **Payroll calculation**: `PayrollService::previewRun()` — gross, insurance (BHXH 8%, BHYT 1.5%, BHTN 1%), PIT brackets, net
- **State machine**: `PayrollRunStatus::canTransitionTo()` — draft→previewed→finalized→locked
- **Permission matrix**: `CheckPermission::PERMISSION_MAP` — module.action → allowed roles
- **Mock users**: `AuthService::mockUsers` — admin01, hr01, payroll01, manager01 (all pw: "password")

### Middleware Stack (applied in routes/api.php)

- `auth:sanctum` — token validation (all authenticated routes)
- `role:role1,role2` — checks user role against whitelist
- `permission:module.action` — checks against PERMISSION_MAP

### Response Trait

All controllers `use ApiResponse`. Methods: `success()`, `created()`, `error()`, `notFound()`, `forbidden()`, `paginated()`.

### SQL Server Configuration

`.env.example` has `DB_CONNECTION=sqlsrv` placeholder. Current `.env` uses file/array drivers for zero-DB development.

### CORS

Configured in `config/cors.php` and `bootstrap/app.php` for `localhost:5173` (frontend dev server).
