# Fujimart The 5 E2E Evidence

Date: 2026-05-12

Target: `http://localhost:4173`

Note: port `5173` was already occupied by another local container, so the rebuilt frontend image was run as `payroll-frontend-4173` on host port `4173`.

## Verification commands

Run from `D:\CODE\enterprise-payroll-erp`.

```powershell
docker compose build backend frontend
docker compose up -d backend sqlserver
docker rm -f payroll-frontend-4173
docker compose run -d --name payroll-frontend-4173 -p 4173:5173 frontend
.\scripts\restore-fujimart-db.ps1
docker compose exec -T backend composer test:sqlite
docker exec payroll-frontend-4173 npm run build
docker exec payroll-frontend-4173 npm run lint
cd frontend
$env:E2E_BASE_URL='http://localhost:4173'
npm run test:e2e:smoke
```

Latest verified result:

- Backend: `OK (62 tests, 650 assertions)`
- Frontend build: `vite ... built`
- Frontend lint: `eslint .`
- UI smoke: `7 passed`

Backend log snapshot:

- `test-evidence/fujimart-the-5/backend-tail.log`

## Requirement evidence

| Requirement | UI test / action | Evidence |
| --- | --- | --- |
| Sidebar toggle does not overlap menu | Collapse and expand sidebar | `test-evidence/fujimart-the-5/01-sidebar-toggle-no-overlap.png` |
| Navigator follows Fujimart BFD labels and hides `Quản lý` from menu labels | Open sidebar groups and verify labels | `test-evidence/fujimart-the-5/02-navigator-payroll-email-labels.png` |
| Payroll menu has `Gửi email phiếu lương` and `Tham số lương` | Open `Tiền lương` group | `test-evidence/fujimart-the-5/02-navigator-payroll-email-labels.png` |
| Report center only exposes 3 Fujimart reports | Open report center | `test-evidence/fujimart-the-5/03-report-center-only-3-bfd-reports.png` |
| Refresh button works | Click `Làm mới` in reports | `test-evidence/fujimart-the-5/04-refresh-button-after-click.png` |
| Date inputs display `DD/MM/YYYY` | Inspect report and payroll email filters | `test-evidence/fujimart-the-5/05-date-input-ddmmyyyy.png`, `test-evidence/fujimart-the-5/12-payroll-email-before-click.png` |
| Employee records are saved and visible | Create/edit employee through UI and refresh list | `test-evidence/fujimart-the-5/06-employee-record-saved-in-db.png` |
| `Đình chỉ` action updates inactive state | Suspend employee through UI | `test-evidence/fujimart-the-5/07-employee-suspend-isactive-zero.png` |
| Employee detail has unified actions | Open employee detail modal | `test-evidence/fujimart-the-5/08-unified-employee-detail-actions.png` |
| Employee detail supports dependent tab | Open `Người phụ thuộc` tab | `test-evidence/fujimart-the-5/09-dependent-tab-create-edit.png` |
| Employee edit action is linked and prefilled | Open `Sửa nhân viên` from detail | `test-evidence/fujimart-the-5/10-employee-edit-prefilled-fields.png` |
| Employee fields include Gender, BirthDate, IdCardNo, Email, Mobile, ResignDate | Inspect detail/edit modal | `test-evidence/fujimart-the-5/08-unified-employee-detail-actions.png`, `test-evidence/fujimart-the-5/10-employee-edit-prefilled-fields.png` |
| Salary scale shows D20SalaryScale -> D20SalaryGrade -> D20SalaryGradeDetail | Open salary level detail and income detail | `test-evidence/fujimart-the-5/11-salary-scale-grade-detail.png` |
| Payroll payslip email procedure runs with SendEmail = 1 | Click `Gửi email phiếu lương` | `test-evidence/fujimart-the-5/12-payroll-email-procedure.png` |
| Customer check-in/out Excel imports | Upload `Data checkinout.xlsx` and import | `test-evidence/fujimart-the-5/13-import-excel-success.png` |
| Attendance procedure runs | Run `Tính lại` for 01/2026 | `test-evidence/fujimart-the-5/14-attendance-run-success.png` |
| Payroll procedure runs | Run payroll for 01/2026 | `test-evidence/fujimart-the-5/15-payroll-run-success.png` |
| `Bảng chấm công` previews and exports | Preview, export, download `.xlsx` | `test-evidence/fujimart-the-5/16-report-attendance-export.png`, `test-evidence/fujimart-the-5/attendance-report.xlsx` |
| `Bảng thanh toán lương theo chi nhánh/phòng ban` previews and exports | Preview, export, download `.xlsx` | `test-evidence/fujimart-the-5/17-report-payroll-export.png`, `test-evidence/fujimart-the-5/payroll-report.xlsx` |
| `Phiếu lương cá nhân` previews and exports | Preview, export, download `.xlsx` | `test-evidence/fujimart-the-5/18-report-slip-export.png`, `test-evidence/fujimart-the-5/payroll-slip.xlsx` |

## E2E feature map

The main UI smoke spec is `frontend/e2e/app-smoke.spec.ts`.

Each high-level requirement is represented by a `test.step(...)` block:

- `Sidebar can be collapsed and expanded`
- `Sidebar exposes the Fujimart BFD structure`
- `Employee create/edit/detail/dependent/suspend actions persist`
- `Payroll parameters expose Fujimart source views`
- `Salary scale page exposes D20SalaryScale to grade detail structure`
- `Payroll slip email action runs customer procedure contract`
- `Import customer check-in/out Excel`
- `Run attendance procedure`
- `Run payroll procedure`
- `Preview and export Bảng chấm công`
- `Preview and export Bảng thanh toán lương theo chi nhánh/phòng ban`
- `Preview and export Phiếu lương cá nhân`

Additional login/session checks:

- `seed user admin01 can log in through the UI`
- `seed user hr01 can log in through the UI`
- `seed user payroll01 can log in through the UI`
- `seed user manager01 can log in through the UI`
- `seed user emp001 can log in through the UI`
- `stale stored session is cleared when the API rejects the token`
