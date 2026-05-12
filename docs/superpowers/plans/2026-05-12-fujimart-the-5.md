# Fujimart The 5 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement and verify Fujimart Thẻ 5 UI, CRUD, report, and payroll-email fixes.

**Architecture:** Keep changes in the existing Laravel service/controller pattern and React page pattern. Add focused API methods for employee persistence, salary scale drilldown, and payroll email, then wire existing pages plus one new email page.

**Tech Stack:** Laravel 13, Sanctum, SQL Server/SQLite test fallback, React 19, Vite, TanStack Query, Playwright, Docker Compose.

---

## Files

- Modify `backend/app/Services/EmployeeService.php` for create/update/suspend and detailed fields.
- Modify `backend/app/Http/Controllers/Api/EmployeeController.php` and `backend/routes/api.php` for employee write routes and dependent write routes.
- Modify `backend/app/Services/ReferenceService.php` and `backend/app/Http/Controllers/Api/ReferenceController.php` for salary scale/grade/detail APIs.
- Modify `backend/app/Services/PayrollService.php`, `backend/app/Http/Controllers/Api/PayrollController.php`, and `backend/routes/api.php` for email payslip execution.
- Modify `backend/app/Services/ReportService.php` for three-report filtering.
- Add backend migrations for `employees.resign_date` and `salary_levels.is_active`.
- Modify `frontend/src/lib/format.ts`, add `frontend/src/components/DateInput.tsx`.
- Modify `frontend/src/lib/rbac.ts`, `frontend/src/App.tsx`, `frontend/src/pages/ReportsPage.tsx`, `frontend/src/pages/EmployeesPage.tsx`, and `frontend/src/pages/SalaryLevelsPage.tsx`.
- Add `frontend/src/pages/PayrollEmailPayslipsPage.tsx`.
- Modify backend feature tests and frontend smoke tests.

## Tasks

- [ ] Write failing backend tests for the Thẻ 5 API contract.
- [ ] Run the targeted backend tests and confirm they fail for missing behavior.
- [ ] Implement backend routes/services/migrations.
- [ ] Run targeted backend tests until green.
- [ ] Write failing frontend smoke expectations for navigation, reports, email payslip, employee edit/suspend, salary drilldown, and date format.
- [ ] Implement frontend pages/components.
- [ ] Run frontend build/lint/e2e locally.
- [ ] Run `docker compose up -d --build`.
- [ ] Use browser-use/browser plugin to capture one screenshot per requirement and collect backend logs.
- [ ] Fix failures from the smallest failing layer upward, rebuilding Docker after code fixes.
- [ ] Commit and push only after all required checks pass.
