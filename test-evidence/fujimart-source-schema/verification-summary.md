# Fujimart Source Schema Verification Summary

Date: 2026-05-16

## DB confirmation

- Restored source database: `fujimart_hrm_source`
- Confirmed source tables: `D20Shift`, `D30AssignedShift`, `D20PayrollParameter`, `D30Attendance`, `D30Payroll`, `D30PayrollDetail`, `D30CheckInOut`
- Confirmed source views: `vD20PayrollPara_ValuePara`, `vD20PayrollPara_SalaryType`
- Confirmed attendance procedure: `dbo.usp_CreateAndCalculateAttendance`
- Confirmed payroll procedure: `dbo.usp_CreateAndCalculatePayroll`
- Payroll procedure parameters include `@_UserId`

## API source evidence

- `GET /api/reference/shifts` returns rows with `source_table = D20Shift`
- `GET /api/reference/payroll-parameters` returns grouped `value_parameters` and `salary_types`
- Payroll parameter rows include `source_view = vD20PayrollPara_ValuePara` or `source_view = vD20PayrollPara_SalaryType`
- `GET /api/attendance/shift-assignments` returns rows with `source_table = D30AssignedShift`
- Attendance summary reads from `D30Attendance` when the source DB is configured
- Payroll calculation reads result rows from `D30Payroll` and `D30PayrollDetail` after the confirmed procedure runs

## Test results

- `docker compose up -d --build`: passed
- `powershell -ExecutionPolicy Bypass -File .\scripts\restore-fujimart-db.ps1`: passed
- `docker compose exec -T backend php artisan test --filter FujimartSourceSchemaTest`: passed, 5 tests, 40 assertions
- `docker compose exec -T backend php artisan test --filter Reference`: passed, 7 tests, 90 assertions
- `docker compose exec -T backend php artisan test --filter Attendance`: passed, 19 tests, 190 assertions
- `docker compose exec -T backend php artisan test --filter Payroll`: passed, 6 tests, 72 assertions
- `docker compose exec -T backend composer test:sqlite`: passed, 67 tests, 690 assertions
- `docker compose exec -T frontend npm run build`: passed with existing Vite chunk-size warning
- `docker compose exec -T frontend npm run lint`: passed
- `E2E_BASE_URL=http://127.0.0.1:4173 npm run test:e2e:smoke`: passed, 7 tests

## Browser evidence

Screenshots saved under `test-evidence/fujimart-source-schema/browser/`:

- `reference-shifts.png`
- `payroll-parameters.png`
- `shift-assignments.png`
- `attendance-summary.png`
- `payroll-run.png`

## Tool notes

- `firecrawl --status` succeeded and showed the CLI is authenticated.
- `firecrawl scrape http://127.0.0.1:4173/login` was not usable because Firecrawl rejected localhost URLs as missing a valid top-level domain.
- `browser-use` CLI is installed, but `browser-use doctor` fails in the current Python environment with a Pydantic TypeVar error. Browser evidence was captured with Playwright instead.

## Re-run notes - 2026-05-16

- `docker compose up -d --build`: passed.
- `powershell -ExecutionPolicy Bypass -File .\scripts\restore-fujimart-db.ps1`: passed.
- `docker compose exec -T backend php artisan test --filter FujimartSourceSchemaTest`: passed, 5 tests, 40 assertions.
- `docker compose exec -T backend php artisan test --filter Reference`: passed, 7 tests, 90 assertions.
- `docker compose exec -T backend php artisan test --filter Attendance`: passed, 19 tests, 190 assertions.
- `docker compose exec -T backend php artisan test --filter Payroll`: passed, 6 tests, 72 assertions.
- `docker compose exec -T backend composer test:sqlite`: passed, 67 tests, 690 assertions.
- `docker compose exec -T frontend npm run build`: passed with the existing Vite chunk-size warning.
- `docker compose exec -T frontend npm run lint`: passed.
- `E2E_BASE_URL=http://127.0.0.1:4173 npm run test:e2e:smoke`: passed, 7 tests.
- Browser plugin check loaded `/payroll/parameters`, `/attendance/summary`, and `/payroll/run`; payroll parameters visibly showed `Nguồn view: vD20PayrollPara_ValuePara`.
- Browser plugin screenshot capture timed out in CDP; existing Playwright screenshots remain under `test-evidence/fujimart-source-schema/browser/`.
- `firecrawl --status`: passed and authenticated.
- `firecrawl scrape "http://127.0.0.1:5173/login"`: blocked by Firecrawl URL validation because localhost URLs do not have a valid top-level domain.
