# Fujimart The 5 Design

## Source

- Google Doc tab `t.kordwspms8yb` / "The 5", scraped with Firecrawl on 2026-05-12.
- Existing code in Laravel backend and React frontend.

## Scope

Implement the Thẻ 5 fixes without broad refactors:

- Sidebar collapse control must not overlap navigation items.
- Navigation must match the Fujimart BFD scope and use `Tiền lương`, `Tham số lương`, and `Gửi email phiếu lương`.
- Report center must expose only three Fujimart reports.
- Report/email date inputs must display `DD/MM/YYYY` while API payloads remain ISO dates.
- Refresh buttons must refetch data and clear stale local preview/error state.
- Employee create/update/suspend must persist to the database.
- Employee detail must include `Gender`, `BirthDate`, `IdCardNo`, `Email`, `Mobile`, and `ResignDate`.
- Detail views must offer an Edit action that opens a populated editor.
- Salary scale view must present `D20SalaryScale -> D20SalaryGrade -> D20SalaryGradeDetail` semantics where the restored customer database provides those tables, with a Laravel fallback for SQLite tests.
- Email payslip must execute `dbo.usp_PayrollSlip` with `@_SendEmail = 1` and `@_MailProfile = N''`.

## Assumptions

- The committed app still uses Laravel tables for local tests; customer SQL Server tables are used opportunistically at runtime when they exist.
- If `D20SalaryScale`, `D20SalaryGrade`, or `D20SalaryGradeDetail` are missing in the restored database, the UI must show a clear empty/error state instead of inventing customer rows.
- `IsActive = 0` maps to `employment_status = inactive` in the Laravel employee table, because the internal table does not have an `IsActive` column.

## Verification

- Backend feature tests cover report filtering, employee persistence/suspend, salary scale detail APIs, and payroll email procedure payload.
- Frontend smoke tests cover sidebar, navigator labels, DD/MM/YYYY inputs, email payslip page, reports-only-three, employee edit/save/suspend, and salary scale drilldown.
- Docker loop runs after implementation: `docker compose up -d --build`, backend tests, frontend build/lint/e2e, browser UI screenshots, backend logs.
