# Fujimart DB Mapping Audit

Date: 2026-05-16

## Scope

This audit maps the current smoke-flow code to the original Fujimart SQL Server schema and procedures. It is intentionally an audit and implementation plan only. No API, UI, migration, or git state change is included here.

Primary modules:

1. Shift master / Ca lam viec
2. Payroll parameters / Tham so tinh luong
3. Shift assignment / Phan ca lam viec
4. Attendance calculation / Tong hop cong
5. Payroll calculation / Tinh luong

## Source Evidence

- `PROJECT_CONTEXT.md` describes the current Laravel API modules and migrated tables such as `shifts`, `shift_assignments`, `attendance_daily`, `payroll_parameters`, `payslips`, and `payslip_items`.
- `db_explain.md` documents the Fujimart source tables:
  - `D20Shift`
  - `D20PayrollParameter`
  - `D30AssignedShift`
  - `D30CheckInOut`
  - `D30Attendance`
  - `D30Payroll`
  - `D30PayrollDetail`
- `Brief.ai.md` states payroll parameters must be displayed as two tabs from:
  - `vD20PayrollPara_ValuePara`
  - `vD20PayrollPara_SalaryType`
- `Brief.ai.md` states attendance calculation writes to `D30Attendance`.
- `Brief.ai.md` states payroll calculation writes to `D30Payroll` and `D30PayrollDetail`.
- `docker-compose.yml` defines `CUSTOMER_DB_DATABASE=fujimart_hrm_source`.
- `docker/data/fujimart/DUNGNTN_HRM.bak` exists.
- `scripts/restore-fujimart-db.ps1` can restore the customer database into SQL Server.

## Current Finding Summary

The current FE routes already point at stable business endpoints, but those endpoints mostly resolve to Laravel migrated tables. This creates two independent data sources:

- FE smoke flow: `shifts`, `shift_assignments`, `payroll_parameters`, `payroll_parameter_details`, `attendance_daily`, `attendance_monthly_summary`, `payroll_runs`, `payslips`, `payslip_items`.
- Customer Fujimart procedures/schema: `D20Shift`, `D30AssignedShift`, `D20PayrollParameter`, `vD20PayrollPara_ValuePara`, `vD20PayrollPara_SalaryType`, `D30Attendance`, `D30Payroll`, `D30PayrollDetail`.

The correction should keep the FE routes stable where possible, and change the backend service layer so those routes read/write the Fujimart source schema.

## Mapping Table

| Feature/module | FE route/page | Backend controller/service | Current migrated table/model | Required Fujimart table/view/procedure | Current field | Required Fujimart field | Data type | Direction | Risk or mismatch | Proposed fix | Test evidence needed |
|---|---|---|---|---|---|---|---|---|---|---|---|
| Shift master | `frontend/src/pages/ShiftsPage.tsx`, `GET /reference/shifts` | `ReferenceController::shifts`, `ReferenceService::getShifts` | `Shift` model / `shifts` table | `D20Shift` | `id` | `Code` | `VARCHAR(16)` | read | Current response identity is numeric Laravel id. Fujimart primary business key is `Code`. | Return `id` as `Code` or add `source_code`; keep `code = Code` for FE compatibility. | Backend test asserts query uses `D20Shift` and returned `code` matches `Code`. |
| Shift master | `ShiftsPage.tsx` | `ReferenceService::getShifts` | `shifts.code` | `D20Shift.Code` | `code`, `shift_code` | `Code` | `VARCHAR(16)` | read/write | Width differs: current migration allows 20/255, Fujimart allows 16. | Validate max 16 and write to `Code`. | API test creates/updates a 16-char code and rejects longer code. |
| Shift master | `ShiftsPage.tsx` | `ReferenceService::getShifts` | `shifts.name` | `D20Shift.Name` | `name`, `shift_name` | `Name` | `NVARCHAR(128)` | read/write | Current migration length differs. | Map `name = Name`; validate max 128. | API response contains `name` from `D20Shift.Name`. |
| Shift master | `ShiftsPage.tsx` | `ReferenceService::getShifts` | `shifts.start_time` | `D20Shift.StartTime` | `start_time`, `check_in_time` | `StartTime` | `DATETIME` | read/write | FE expects time string, Fujimart stores `DATETIME`. | Format `StartTime` as `HH:mm`; when writing, persist time on a safe date anchor or preserve date portion if provided. Needs DB confirmation for desired date anchor. | Unit/API test checks time formatting from SQL Server datetime. |
| Shift master | `ShiftsPage.tsx` | `ReferenceService::getShifts` | `shifts.end_time` | `D20Shift.EndTime` | `end_time`, `check_out_time` | `EndTime` | `DATETIME` | read/write | Same time-vs-datetime mismatch as `StartTime`. | Format `EndTime` as `HH:mm`; write through `EndTime`. Needs DB confirmation for desired date anchor. | Unit/API test checks overnight and same-day formatting. |
| Shift master | `ShiftsPage.tsx` | `ReferenceService::getShifts` | `break_start_time`, `break_end_time` | `D20Shift.ShiftBreak`, `StartShiftBreak`, `EndShiftBreak`, `ShiftBreakMins` | `break_minutes`, `break_time`, `rest_minutes` | `ShiftBreakMins` | `INT` | read/write | Current app calculates break from two time fields. Fujimart has explicit minutes plus break markers. | Use `ShiftBreakMins` for `break_minutes`; optionally expose break start/end later. | API test asserts `break_minutes` equals `D20Shift.ShiftBreakMins`. |
| Shift master | `ShiftsPage.tsx` | `ReferenceService::getShifts` | `workday_value`, calculated working hours | `D20Shift.WorkDay`, `D20Shift.WorkingHours` | `working_hours`, `workday_value` | `WorkingHours`, `WorkDay` | `NUMERIC(8,2)` | read/write | Current service calculates hours from start/end. Fujimart stores official `WorkingHours`. | Prefer stored `WorkingHours`; expose `workday_value = WorkDay`. | API test compares output to `D20Shift.WorkingHours`. |
| Shift master | `ShiftsPage.tsx` | `ReferenceService::getShifts` | `is_overnight` | `D20Shift.StartWorkingNightTime`, `D20Shift.EndWorkingNightTime` | `is_night_shift` | derived from night-time fields | `DATETIME` | read | Fujimart does not show direct boolean in docs. | Derive true when either night-time field is non-null, or mark Needs DB confirmation if business expects another rule. | Test with sample night-time rows. |
| Payroll parameters | `frontend/src/pages/PayrollParametersPage.tsx`, `GET /reference/payroll-parameters` | `ReferenceController::payrollParameters`, `ReferenceService::getPayrollParameters` | `PayrollParameter`, `PayrollParameterDetail`, `payroll_parameters`, `payroll_parameter_details` | `vD20PayrollPara_ValuePara` | `code`, `param_code`, `name`, `param_name`, `value`, `unit`, `effective_from`, `type` | View columns need DB introspection | Needs DB confirmation | read | FE already labels tab as `vD20PayrollPara_ValuePara`, but backend still reads Laravel tables and heuristically splits rows. | Query view directly and return normalized fields plus `source_view = vD20PayrollPara_ValuePara`. Inspect actual columns using `sys.columns` before coding. | DB integration test asserts endpoint returns rows from view and includes `source_view`. |
| Payroll parameters | `PayrollParametersPage.tsx`, `GET /reference/payroll-parameters` | `ReferenceService::getPayrollParameters` | same as above | `vD20PayrollPara_SalaryType` | `type` heuristic uses code keywords like salary/bonus/deduction | View columns need DB introspection | Needs DB confirmation | read | Current salary tab classification is inferred from text, not source view. | Query `vD20PayrollPara_SalaryType` separately; return both groups explicitly or add `category`. | FE test asserts tab switch does not depend on keyword heuristic. |
| Payroll parameters write | `PayrollParametersPage.tsx`, `POST/PUT /reference/payroll-parameters` | `ReferenceController::storePayrollParameter`, `ReferenceService::createPayrollParameter/updatePayrollParameter` | `D20PayrollParameter` absent; currently `payroll_parameters` + details | `D20PayrollParameter` | `code`, `name`, `description`, `effective_from`, `type`, `value` | `Parameter`, `Name`, `Description`, `EffectiveDate`, `Type`, `Amount` | `NVARCHAR(64)`, `NVARCHAR(128)`, `NVARCHAR(128)`, `DATE`, `VARCHAR(32)`, `NUMERIC(18,2)` | write | Current route validates uniqueness against `payroll_parameters`, not `D20PayrollParameter.Parameter`. | Point validation and persistence to `D20PayrollParameter`; map FE `code` to `Parameter`. | Backend test writes a row and verifies `D20PayrollParameter.Parameter`. |
| Payroll parameters suspend | `PayrollParametersPage.tsx`, `POST /reference/payroll-parameters/{id}/suspend` | `ReferenceController::suspendPayrollParameter`, `ReferenceService::suspendPayrollParameter` | `payroll_parameters.status` | `D20PayrollParameter` | `status`, `is_active` | No active/status field in documented schema | Needs DB confirmation | write | Fujimart docs do not list a status column. A hard suspend action may be impossible without a real status field. | Disable or hide suspend for Fujimart-backed parameters unless live DB confirms a status-like column. | DB schema query confirms whether active/status column exists. |
| Shift assignment | `frontend/src/pages/ShiftAssignmentsPage.tsx`, `GET /attendance/shift-assignments` | `AttendanceController::shiftAssignments`, `AttendanceService::getShiftAssignments` | `ShiftAssignment`, `shift_assignments`; joins `Employee`, `Shift` by numeric ids | `D30AssignedShift` joined to `D20Employee`, `D20Shift` | `employee_id`, `shift_id`, `work_date`, `source`, `note` | `AssignId`, `EmployeeCode`, `ShiftCode`, `Date`, `StartDate`, `EndDate`, `Description` | `VARCHAR`, `VARCHAR(16)`, `VARCHAR(16)`, `DATE`, `DATE`, `DATE`, `NVARCHAR(512)` | read | Current code maps numeric IDs. Customer schema maps by `EmployeeCode + ShiftCode`. | Query `D30AssignedShift`; join employee/shift by codes; return FE-compatible `employee_code`, `employee_name`, `shift_code`, `shift_name`, `work_date`. | API test asserts assignment row uses `EmployeeCode` and `ShiftCode`, not numeric ids. |
| Shift assignment write | likely future modal in `ShiftAssignmentsPage.tsx` | No POST/PUT route currently visible for `/attendance/shift-assignments` | No current write endpoint in inspected routes | `D30AssignedShift` | missing | `AssignId`, `Date`, `EmployeeCode`, `ShiftCode`, `StartDate`, `EndDate`, day include flags | Mixed | write | P0 requires read/write, but routes currently expose GET only. | Add POST/PUT endpoints only after confirming expected FE create/edit flow; generate/accept `AssignId`; validate existing employee/shift codes. | TDD: failing test for POST creating `D30AssignedShift` with `EmployeeCode + ShiftCode`. |
| Attendance check-in/out source | current check-in APIs and import | `AttendanceService` imports `TimeLog` | `time_logs` | `D30CheckInOut` | `log_time`, `employee_id` | `CheckTime`, `EmployeeCode` | `DATETIME`, `VARCHAR(16)` | write/read input | Current import stores numeric employee relation. Fujimart procedure likely consumes `D30CheckInOut`. | Plan a separate mapping for import after P0/P1; for calculation evidence, verify procedure reads `D30CheckInOut`. | Integration test imports sample Excel then verifies `D30CheckInOut`. |
| Attendance calculation | `frontend/src/pages/AttendanceSummaryPage.tsx`, `POST /attendance/recalculate` | `AttendanceController::recalculate`, `AttendanceService::recalculate`, `tryCustomerAttendanceProcedure` | fallback writes `attendance_monthly_summary`; reads `attendance_daily` | `dbo.usp_CreateAndCalculateAttendance`, output `D30Attendance` | `month`, `year`, `department_id`, `employee_id` | `@_DocDate1`, `@_BranchCode`, `@_DeptCode`, `@_EmployeeCode` | `DATE`, `NVARCHAR/VARCHAR` | procedure output | Current procedure call is hard-coded to `dbo.usp_CreateAndCalculateAttendance`, but fallback still writes Laravel tables. Docs say output writes `Attendance`; table docs name `D30Attendance`. | Keep route stable; after procedure success, query `D30Attendance` to confirm rows for date/month. If procedure unavailable, surface a clear DB warning instead of silently treating Laravel fallback as Fujimart-aligned. | Backend integration test checks `execution_mode=stored_procedure` and row count from `D30Attendance`. |
| Attendance summary read | `AttendanceSummaryPage.tsx`, `GET /attendance/monthly-summary` | `AttendanceService::getMonthlySummary` | `AttendanceDaily`, `AttendanceMonthlySummary` | `D30Attendance` | `total_workdays`, `regular_hours`, `ot_hours`, `night_hours`, `paid_leave_days`, `unpaid_leave_days`, `meal_count` | `WorkingDays`, `WorkingHours`, `WorkNightHours`, `PaidLeaveDays`, `UnpaidLeaveDays`, `ShiftMeal`, `ExcludeDays`, `ExcludeHours` | `NUMERIC(8,2)`, `INT` | read | UI can show calculated output from Laravel summaries even when procedure wrote `D30Attendance`. | Rebuild monthly summary response from `D30Attendance` grouped by `EmployeeCode`; map to FE-compatible fields. | API test seeds `D30Attendance`; endpoint returns those values without `attendance_daily`. |
| Payroll preview | `frontend/src/pages/PayrollRunPage.tsx`, `GET /payroll/runs/preview-parameters`, `POST /payroll/runs/preview` | `PayrollController::previewParameters/previewRun`, `PayrollService::getPreviewParameters/previewRun` | `PayrollParameter`, `SystemConfig`, `PayrollRun`, `Payslip`, `PayslipItem`, `AttendanceDaily` | `vD20PayrollPara_ValuePara`, `vD20PayrollPara_SalaryType`, possibly read-only preview from procedure output | many Laravel payroll fields | Needs DB confirmation | Mixed | Current preview persists Laravel payslips before customer payroll procedure. That can create misleading evidence. | For P1, make calculate authoritative first; keep preview as pre-check only or mark as non-authoritative until customer procedure is confirmed. | Test proves calculate path does not depend on `payslips`. |
| Payroll calculation | `PayrollRunPage.tsx`, `POST /payroll/runs/calculate` | `PayrollController::calculateRun`, `PayrollService::calculateRun`, `tryCustomerPayrollProcedure` | fallback writes `payroll_runs`, `payslips`, `payslip_items` | verified payroll calculation procedure, output `D30Payroll`, `D30PayrollDetail` | `month`, `year`, `department_id`, `employee_code` | `@_DocDate1`, `@_BranchCode`, `@_DeptCode`, `@_EmployeeCode` | `DATE`, `VARCHAR/NVARCHAR` | procedure output | Code currently guesses procedure names: `dbo.usp_CreateAndCalculatePayroll`, `dbo.usp_CalculatePayroll`, `dbo.usp_PayrollCalculation`. User explicitly says do not assume name. Brief.ai.md appears to duplicate attendance procedure under payroll section, likely ambiguous. | Before implementation, restore/live-connect customer DB and inspect `sys.procedures` + `sp_helptext`. Store verified procedure name in config or procedure catalog. Mark unresolved now: Needs DB confirmation. | Evidence file with exact `sys.procedures` result and `sp_helptext` snippet; integration test asserts selected procedure writes `D30Payroll` and `D30PayrollDetail`. |
| Payroll read after calculation | `PayrollRunPage.tsx` result ledger | `PayrollService::calculateRun`, `formatRun` | `Payslip`, `PayslipItem` | `D30Payroll`, `D30PayrollDetail` | `gross_salary`, `net_salary`, `employee_code`, `items` | `GrossSalary`, `NetIncome`, `EmployeeCode`, detail rows `SalaryType`, `Amount`, `Days`, `Hours` | `NUMERIC(18,2)`, `VARCHAR`, `NVARCHAR` | read/procedure output | Current result can come from Laravel-calculated payslips. | After procedure success, return a normalized result built from `D30Payroll` and `D30PayrollDetail`. | API test seeds procedure output tables and asserts FE result uses `D30Payroll.Id`. |
| Payslip edit before email | `frontend/src/pages/PayslipsPage.tsx` | `PayrollController::payslips/updatePayslip/updatePayslipItem` | `payslips`, `payslip_items` | `D30Payroll`, `D30PayrollDetail` | Laravel payslip ids/items | `D30Payroll.Id`, `D30PayrollDetail.RowId`, `PayRollId` | `VARCHAR(16)` | read/write | Outside current P0/P1 page list, but Brief.ai.md says D30Payroll/D30PayrollDetail must be editable before email. | Plan as follow-up after `/payroll/run` source alignment. | Browser smoke edits D30Payroll and D30PayrollDetail rows, then email uses `usp_PayrollSlip`. |

## Priority Plan

### P0: Source schema alignment for master data

1. `ReferenceService::getShifts`
   - Replace `Shift::query()` with a Fujimart SQL Server query to `D20Shift`.
   - Keep API shape stable for FE: `id`, `code`, `name`, `start_time`, `end_time`, `break_minutes`, `working_hours`, `is_night_shift`, `is_active`.
   - Add write routes only if current UI actually supports create/edit. If write is required by customer but UI modal is currently non-functional, implement backend first with tests and then wire the modal.

2. `ReferenceService::getPayrollParameters`
   - Replace Laravel `PayrollParameter` + details read with two direct reads:
     - `vD20PayrollPara_ValuePara`
     - `vD20PayrollPara_SalaryType`
   - Do not infer tab membership from code keywords.
   - First inspect actual live view columns. Current docs confirm the view names but not their column lists.

3. `AttendanceService::getShiftAssignments`
   - Replace `ShiftAssignment::query()` with `D30AssignedShift`.
   - Map by `EmployeeCode + ShiftCode`, not numeric `employee_id + shift_id`.
   - Return FE-compatible fields: `employee_code`, `employee_name`, `shift_code`, `shift_name`, `work_date`, `start_date`, `end_date`, `source`, `note`.
   - Add POST/PUT endpoints for write only after confirming desired form behavior.

### P1: Procedure-backed calculations

1. Attendance calculation
   - Keep `dbo.usp_CreateAndCalculateAttendance` only if live DB confirms the procedure exists.
   - Execute with:
     - `@_DocDate1`
     - `@_BranchCode`
     - `@_DeptCode`
     - `@_EmployeeCode`
   - After execution, verify rows in `D30Attendance`.
   - Read `/attendance/monthly-summary` from `D30Attendance`, not `attendance_daily` or `attendance_monthly_summary`.

2. Payroll calculation
   - Do not keep guessed names in `tryCustomerPayrollProcedure`.
   - Inspect live DB:
     - `SELECT name FROM sys.procedures WHERE name LIKE '%Payroll%' OR name LIKE '%Calculate%'`
     - `EXEC sp_helptext 'dbo.<verified_procedure_name>'`
   - Execute only the verified procedure.
   - After execution, verify rows in `D30Payroll` and `D30PayrollDetail`.
   - Return normalized FE result from those tables.

## Exact Files Proposed To Change

Backend:

- `backend/app/Services/ReferenceService.php`
- `backend/app/Http/Controllers/Api/ReferenceController.php`
- `backend/app/Services/AttendanceService.php`
- `backend/app/Http/Controllers/Api/AttendanceController.php`
- `backend/app/Services/PayrollService.php`
- `backend/app/Services/CustomerProcedureService.php`
- `backend/routes/api.php`
- New backend tests under `backend/tests/Feature` or existing test folders.

Frontend:

- `frontend/src/pages/PayrollParametersPage.tsx`
- `frontend/src/pages/ShiftAssignmentsPage.tsx`
- `frontend/src/pages/AttendanceSummaryPage.tsx`
- `frontend/src/pages/PayrollRunPage.tsx`
- `frontend/src/lib/api.ts` only if response normalization needs a shared helper.

Likely unchanged initially:

- `frontend/src/pages/ShiftsPage.tsx` can probably remain stable if the API keeps the same response shape.
- UI polish should stay out of this work.

## Needs DB Confirmation

These points must be confirmed against restored/live SQL Server before implementation:

1. Actual columns returned by `vD20PayrollPara_ValuePara`.
2. Actual columns returned by `vD20PayrollPara_SalaryType`.
3. Whether `D20PayrollParameter` has any active/status/suspend-compatible column beyond documented fields.
4. Whether `D20Shift` has hidden primary key/status columns not shown in docs.
5. The real payroll calculation procedure name.
6. The real payroll calculation procedure parameters.
7. Whether attendance procedure name is exactly `dbo.usp_CreateAndCalculateAttendance` in the restored customer database.
8. Whether payroll procedure writes directly to `D30Payroll` and `D30PayrollDetail`, or calls another procedure that does.

## Verification Plan

Smallest first:

1. Restore/confirm customer DB:
   - `powershell -ExecutionPolicy Bypass -File .\scripts\restore-fujimart-db.ps1`
   - Query `sys.tables`, `sys.views`, `sys.procedures`, and `sp_helptext`.
2. Backend targeted tests:
   - Shift endpoint reads `D20Shift`.
   - Payroll parameters endpoint reads both views.
   - Shift assignments endpoint reads/writes `D30AssignedShift`.
   - Attendance recalculate executes verified procedure and confirms `D30Attendance`.
   - Payroll calculate executes verified procedure and confirms `D30Payroll` + `D30PayrollDetail`.
3. Frontend build.
4. Frontend lint.
5. Docker rebuild.
6. Playwright/browser smoke:
   - `/reference/shifts`
   - `/reference/payroll-parameters`
   - `/attendance/shift-assignments`
   - `/attendance/summary`
   - `/payroll/run`
7. DB capture evidence:
   - Counts/sample rows from `D20Shift`, both payroll parameter views, `D30AssignedShift`, `D30Attendance`, `D30Payroll`, `D30PayrollDetail`.

## Recommended Implementation Approach

Recommended: backend source-schema adapter first.

Build a small Fujimart data-access layer inside existing services or a narrowly scoped helper. Keep FE routes and response shapes stable, but change the backing source from Laravel migrated tables to Fujimart SQL Server objects. This gives the fastest path to customer alignment while limiting UI churn.

Alternative 1: direct edits inside each service method.

This is fastest, but it can scatter SQL Server naming and type conversion across services. It is acceptable if the implementation remains small, but it may become harder to test and maintain.

Alternative 2: broad model replacement with Eloquent models for every Fujimart table.

This may look clean long term, but it is too much for the current P0/P1 scope. It risks spending time on model structure before the procedure and view contracts are confirmed.

## Current Stop Point

Do not implement until the DB-confirmation items above are resolved or explicitly accepted as assumptions. The payroll procedure name is the main blocker because the current code guesses procedure names and the user explicitly required live inspection via `sys.procedures` and `sp_helptext`.

## DB Confirmation Update - 2026-05-16

Evidence files:

- `test-evidence/fujimart-source-schema/db-confirmation.md`
- `test-evidence/fujimart-source-schema/db-samples.md`

Confirmed restored customer/source DB:

- `fujimart_hrm_source`

Confirmed tables:

- `D20Shift`
- `D30AssignedShift`
- `D20PayrollParameter`
- `D30Attendance`
- `D30Payroll`
- `D30PayrollDetail`
- `D30CheckInOut`

Confirmed views:

- `vD20PayrollPara_ValuePara`
- `vD20PayrollPara_SalaryType`

Confirmed procedures:

- `dbo.usp_CreateAndCalculateAttendance`
- `dbo.usp_CreateAndCalculatePayroll`
- `dbo.usp_AttendanceReport`
- `dbo.usp_PayrollReport`
- `dbo.usp_PayrollSlip`

Confirmed attendance calculation procedure:

- Name: `dbo.usp_CreateAndCalculateAttendance`
- Parameters: `@_DocDate1`, `@_BranchCode`, `@_DeptCode`, `@_EmployeeCode`
- `sp_helptext` evidence confirms writes to `D30Attendance`.

Confirmed payroll calculation procedure:

- Name: `dbo.usp_CreateAndCalculatePayroll`
- Parameters: `@_DocDate1`, `@_BranchCode`, `@_DeptCode`, `@_EmployeeCode`, `@_UserId`
- `sp_helptext` evidence confirms use of `D30Attendance` and writes to `D30Payroll` and `D30PayrollDetail`.

Implementation status:

- P0 shift master read path now uses `D20Shift`.
- P0 payroll parameters read path now returns two explicit groups from `vD20PayrollPara_ValuePara` and `vD20PayrollPara_SalaryType`.
- P0 shift assignment read path now uses `D30AssignedShift` with `EmployeeCode` and `ShiftCode`.
- P1 attendance calculation now calls the confirmed procedure and reads summary evidence from `D30Attendance`.
- P1 payroll calculation now calls the confirmed payroll procedure and reads result evidence from `D30Payroll` and `D30PayrollDetail`.

Remaining Needs DB confirmation:

- Write/update behavior for `D20Shift` beyond read alignment.
- Write/update behavior for `D30AssignedShift` because current route surface exposes GET only.
- Safe write behavior for payroll parameters. The two payroll parameter views are treated as read-only.
- Suspend/status semantics for `D20PayrollParameter` and `D20Shift` if customer expects soft-disable behavior.
