# Fujimart HRM The 5 rerun evidence - 2026-05-12

## Source checked

- Google Doc tab `t.kordwspms8yb` scraped by Firecrawl to `.firecrawl/fujimart-the5-20260512.json`.
- Docker was rebuilt and the Fujimart SQL Server `.bak` was restored before final verification.

## Final verification commands

- `docker compose build backend frontend`
- `docker compose up -d --force-recreate backend sqlserver`
- `.\scripts\restore-fujimart-db.ps1`
- `docker compose exec -T backend php artisan migrate --force`
- `docker compose exec -T backend composer test:sqlite`
- `docker exec payroll-frontend-4173 npm run build`
- `docker exec payroll-frontend-4173 npm run lint`
- `npm run test:e2e:smoke`
- Browser Use CLI post-rebuild smoke: login dashboard and report center screenshots.

## Results

- Backend tests: `OK (62 tests, 650 assertions)`.
- Frontend build: passed.
- Frontend lint: passed.
- E2E smoke: `7 passed`.
- Clean backend container log: `backend-tail.log`, no `ERROR`, `Exception`, `4xx`, or `5xx` pattern found in the final log tail.

## Requirement evidence map

| Requirement | Evidence |
| --- | --- |
| Fujimart branding and login/dashboard render | `00-login-dashboard-browseruse.png`, `19-post-rebuild-browseruse-dashboard.png` |
| Sidebar collapse/expand button visible and not overlapping | `01-sidebar-toggle-browseruse.png` |
| BFD navigator, labels, payroll email route | `02-navigator-bfd-browseruse.png`, `12-payroll-email-browseruse.png` |
| Report center only shows the 3 Fujimart reports | `03-reports-three-date-refresh-browseruse.png`, `20-post-rebuild-browseruse-reports.png` |
| Refresh button and DD/MM/YYYY date inputs | `03-reports-three-date-refresh-browseruse.png`, `16-inapp-report-attendance-export-success.png` |
| Employee records save and show in list | `06-inapp-employees-list.png`, E2E smoke main flow |
| Suspend action / `IsActive = 0` behavior | `08-inapp-employee-detail-actions-loaded.png`, E2E smoke main flow |
| Unified employee detail with visible actions | `08-inapp-employee-detail-actions-loaded.png` |
| Dependent tab create/edit surface | `09-inapp-dependent-tab.png`, E2E smoke main flow |
| Employee edit prefilled fields: Gender, BirthDate, IdCardNo, Email, Mobile, ResignDate | `10-inapp-employee-edit-prefill.png` |
| Salary scale hierarchy: scale, grade, detail | `11-salary-scale-browseruse.png` |
| Import Excel, run attendance, run payroll | `13-attendance-logs-imported-browseruse.png`, `14-attendance-summary-browseruse.png`, `15-payroll-run-browseruse.png` |
| Report preview/export: attendance, payroll by branch/department, payslip | `16-inapp-report-attendance-export-success.png`, `17-inapp-report-payroll-export-success.png`, `18-inapp-report-slip-export-success.png` |
| Exported Excel files exist | `attendance-report.xlsx`, `payroll-report.xlsx`, `payroll-slip.xlsx` |

