# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working in `frontend/`.

## Frontend Overview

React 19 + Vite + TypeScript + Tailwind CSS 4 frontend for Enterprise Payroll ERP.

This is no longer just a placeholder shell:
- the app has protected routing
- auth context and API client are wired
- multiple real pages already exist for HR, attendance, payroll, reports, and admin
- frontend depends on stable domain API endpoints from the Laravel backend

## Core Structure

- `src/App.tsx` - route registration
- `src/lib/api.ts` - Axios client and normalized API helpers
- `src/lib/query.ts` - React Query client
- `src/context/AuthContext.tsx` - auth/session state
- `src/layouts/AppLayout.tsx` - main authenticated layout
- `src/components/` - shared UI building blocks and guards
- `src/pages/` - routed pages

## Current Routed Areas

- Login
- Dashboard
- Employees
- Contract reference pages
- Attendance pages
- Payroll pages
- Reports
- Admin users and role/permission pages

Important page files include:
- `EmployeesPage.tsx`
- `AttendanceLogsPage.tsx`
- `AttendanceSummaryPage.tsx`
- `PayrollRunPage.tsx`
- `PayrollPeriodsPage.tsx`
- `PayslipsPage.tsx`
- `PayslipDetailPage.tsx`
- `ReportsPage.tsx`

## API Integration

Frontend talks to the backend through `src/lib/api.ts`.

Behavior:
- base URL defaults to `http://localhost:8001/api`
- bearer token is attached automatically
- helper functions normalize API envelopes

Prefer using:
- `apiGet`
- `apiPost`
- `apiPut`
- `apiDelete`

Do not scatter raw `fetch` calls unless there is a strong reason.

## Commands

```bash
npm install
npm run dev
npm run build
npm run lint
npm run preview
```

## Styling and UI Conventions

- Tailwind 4 is the styling foundation
- UI copy should be Vietnamese with proper diacritics
- code identifiers remain English
- existing app patterns should be preserved instead of inventing a new visual system per page

Use the shared app structure instead of bypassing it:
- authenticated pages render inside `AppLayout`
- login stays standalone
- route titles and navigation should stay consistent with the current information architecture

## Data Contract Rules

- trust backend domain endpoints, not ad hoc endpoint invention
- keep frontend DTO assumptions aligned with the backend response envelope
- if backend moves from service-side logic to SQL-backed procedures, frontend should ideally not need route changes
- when API fields change, update page-level typing and formatting together

## Preferred Implementation Pattern

For data-driven pages:
1. call the backend through `src/lib/api.ts`
2. manage remote state with React Query
3. normalize and format values close to the UI boundary
4. keep page components focused on presentation and user flow

## Caution

Some older docs may still describe the frontend as mostly placeholder content. In practice, there are already many real pages and route entries. Trust the current `src/App.tsx`, `src/pages/`, and shared API/auth utilities.
