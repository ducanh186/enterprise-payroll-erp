# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Enterprise Payroll ERP — a web-based HRM system for attendance tracking, payroll calculation, and contract management. Vietnamese business context (BHXH/BHYT/BHTN insurance, PIT brackets, Vietnamese employee names).

**Current state**: All backend endpoints return **mock data**. DB schema from the DB team is pending. SQL Server container runs but has no tables yet — `AuthService.dbAvailable()` falls back to mock users automatically.

## Architecture

```
React 19 (Vite 8+TS+Tailwind 4) → Laravel 11 API (Sanctum) → SQL Server 2022
       :5173                            :8001 (Docker)            :1433
                                        :8000 (local artisan serve)
```

**Data flow**: FE → Controller → Service (mock) → (future: Repository → DB views/SP/functions) → DTO → FE

**Key principle**: Domain API endpoints, NOT generic SP executor. Controllers call Services; Services will call Repositories/DB when schema arrives.

## Authentication Mode

- API authentication uses Sanctum personal access token via `Authorization: Bearer <token>`.
- This project currently runs in stateless token-flow for API endpoints.
- Frontend should call `POST /api/auth/login` directly and store/attach bearer token.
- Do not use SPA cookie CSRF flow (`/sanctum/csrf-cookie`, `XSRF-TOKEN`) for current API login flow.

## Monorepo Structure

- `backend/` — Laravel 11 PHP API (43 endpoints, 7 modules)
- `frontend/` — React 19 + Vite 8 + Tailwind 4 + lucide-react icons + Manrope/IBM Plex Serif fonts
- `frontend/design/` — Stitch-generated HTML designs (14 screens, reference only, not served)

## Common Commands

```bash
# Backend (local)
cd backend
composer install
php artisan key:generate
php artisan serve                    # http://localhost:8000
php artisan route:list --path=api    # verify all API routes
php artisan test                     # PHPUnit
composer test:sqlite                 # all tests with SQLite :memory:
composer test:auth                   # AuthApiTest only (SQLite :memory:)
composer test:services               # ServiceBackendGroup* tests (SQLite :memory:)
composer test:payroll                # PayrollApiTest only (SQLite :memory:)
composer test:feature                # all Feature tests (SQLite :memory:)
composer test:unit                   # all Unit tests (SQLite :memory:)

# Frontend (local)
cd frontend
npm install
npm run dev                          # http://localhost:5173
npm run build                        # TypeScript check + production build
npm run lint

# Docker (full stack)
docker compose up -d --build         # starts backend(:8001) + frontend(:5173) + sqlserver(:1433)
docker compose down
docker compose logs -f backend
docker compose exec backend php artisan tinker   # Laravel REPL inside container
```

## Docker Notes

- Backend mapped to **port 8001** (host) → 8000 (container) to avoid conflicts
- Frontend env: `VITE_API_BASE_URL=http://localhost:8001/api`
- SQL Server: `sa` / `YourStrong!Passw0rd`, database `enterprise_payroll_erp`
- Backend `.env` uses `DB_CONNECTION=sqlsrv` with `DB_TRUST_SERVER_CERTIFICATE=true`
- Source volumes mounted: changes to `frontend/src/` and `backend/` reflect live
- Runtime web/API vẫn dùng SQL Server; các lệnh `composer test:*` chạy pipeline SQLite `:memory:` để tách biệt khỏi policy FK của SQL Server.

## Backend Modules & Roles

| Module | Endpoints | Write Access |
|--------|-----------|-------------|
| Auth | 4 | all roles |
| Reference | 7 | system_admin |
| Employee | 4 | hr_staff, system_admin |
| Contract | 2 | read-only MVP |
| Attendance | 10 | hr_staff, system_admin |
| Payroll | 13 | accountant, system_admin |
| Reports | 3 | all roles |
| Admin | 6 | system_admin |

**Roles**: `hr_staff`, `accountant`, `system_admin`, `management`

**Mock login**: POST `/api/auth/login` with `{"username":"admin01","password":"password"}` — also `hr01`, `payroll01`, `manager01` (all password: `password`)

## API Response Format

All endpoints use `ApiResponse` trait:
```json
{"success": true, "data": {}, "message": "OK", "errors": null}
```
Paginated adds `meta: {current_page, per_page, total, last_page}`.

## Payroll State Machine

`draft` → `previewed` → `finalized` → `locked` (one-way, enforced in `PayrollRunStatus::canTransitionTo()`)

## Frontend Design System

- **Fonts**: Manrope (body, `var(--font-body)`), IBM Plex Serif (headings, `font-[family-name:var(--font-display)]`)
- **Icons**: `lucide-react` (NOT Material Symbols)
- **Palette**: slate/sky/indigo primary, emerald for success, rose for errors
- **Cards**: `rounded-2xl` / `rounded-3xl`, `border border-white/70 bg-white/80 backdrop-blur`, `shadow-[0_18px_40px_rgba(15,23,42,0.06)]`
- **Shared components** (`components/ui.tsx`): `Badge`, `Panel`, `PageHeader`, `MetricCard`, `EmptyState`
- **Data helpers** (`lib/records.ts`): `toArray()`, `textValue()`, `numberValue()`, `boolValue()` for safe record access
- **Formatters** (`lib/format.ts`): `formatCurrency()`, `formatDate()`, `formatNumber()`, `formatPercent()`
- **Stitch designs**: HTML reference files in `frontend/design/*.html` (16 screens from Google Stitch project `16102987164730108480`)

## When DB Schema Arrives

Replace mock data in `app/Services/*.php` with Eloquent models or raw SQL calls to views/stored procedures. Controllers and routes stay unchanged. Create `app/Repositories/` layer if needed.

## Conventions

- Controllers in `app/Http/Controllers/Api/`
- Services in `app/Services/` (one per module)
- Enums in `app/Enums/` (backed string enums)
- FormRequests in `app/Http/Requests/`
- Middleware aliases: `role:hr_staff,system_admin` and `permission:module.action`
- Frontend labels in Vietnamese for sidebar/UI; code in English
- Pages in `frontend/src/pages/` render inside `AppLayout` (except `LoginPage` which is standalone)
