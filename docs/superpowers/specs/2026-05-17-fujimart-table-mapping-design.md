# Fujimart HRM — Schema Mapping & View Layer Design

- **Author:** AL
- **Date:** 2026-05-17
- **Status:** Draft for user review
- **Approach:** Option A — SQL View Layer (giữ migrate tables, expose VIEW có tên/cột schema gốc Fujimart)

---

## 1. Bối cảnh

Backend hiện đang dùng schema "migrate" theo convention Laravel (`shifts`, `shift_assignments`, `payroll_parameters` + `payroll_parameter_details`, `attendance_daily` + `attendance_monthly_summary`, `payroll_runs` + `payslips` + `payslip_items`, ...).

Khách hàng (Dung) viết stored procedures trực tiếp trên SQL Server dùng schema **gốc Fujimart** (`D00User`, `D20Shift`, `D20PayrollParameter`, `D30AssignedShift`, `D30Attendance`, `D30Payroll`, `D30PayrollDetail`, ...). Khi FE gọi API BE → BE truy vấn bảng migrate → schema lệch → tính lương / tổng hợp công / ca làm việc đều fail.

**Mục tiêu:** Cho SP của Dung chạy được trên DB hiện hữu mà KHÔNG phải sửa lại SP, đồng thời FE / BE vẫn dùng bảng migrate. Ưu tiên 3 mảng: **Ca làm việc**, **Tham số tính lương**, **Phân ca**.

## 2. Yêu cầu (trích trực tiếp từ Google Doc + chat user)

### Yêu cầu trực tiếp từ user (chat 5/16):
- Map dữ liệu giữa bảng gốc và bảng migrate.
- Map lại thủ tục tính lương, tổng hợp công.
- Ưu tiên: **Ca làm việc**, **Tham số tính lương**, **Phân ca**.
- Phân tích trước rồi mới code.
- Test phải kiểm tra Docker container đang chạy, không ép dừng, tự đổi port.

### Từ Google Doc — Thẻ 3 (Database chốt + 4 SP mới):
1. `usp_CreateAndCalculateAttendance` (tính & tổng hợp công)
2. `usp_CreateAndCalculatePayroll` (tạo bảng lương)
3. `usp_AttendanceReport` (bảng chấm công excel)
4. `usp_PayrollReport` (bảng thanh toán lương excel)
5. `usp_PayrollSlip` (phiếu lương; flag `@_SendEmail`)
6. 2 view tham số: `vD20PayrollPara_ValuePara`, `vD20PayrollPara_SalaryType`

### Từ Google Doc — Thẻ 5 (UI fix):
- Bổ sung trường D20Employee: `Gender`, `BirthDate`, `IdCardNo`, `Email`, `Mobile`, `ResignDate`.
- Cấp 3: `D20SalaryScale` → `D20SalaryGrade` → `D20SalaryGradeDetail`.
- D30Payroll / D30PayrollDetail / D30Attendance cho phép sửa.
- Nút "Đình chỉ" = `UPDATE … SET IsActive = 0`.
- Date format `DD/MM/YYYY`.

### Từ UML (`D:\CODE\enterprise-payroll-erp\UML_design\UML_KLTN\`):
- USECASE: Nhân sự, Kế toán, Admin, BLĐ — flow đăng nhập → 5 module (HĐLĐ, Chấm công, Tính lương, Báo cáo, Người dùng).
- BFD: 5 nhóm chính (Danh mục / HĐLĐ / Chấm công / Tiền lương / Báo cáo).
- ACTIVITY chấm công: Cán bộ NV → Nhân sự → Hệ thống (3 lane).
- SEQUENCE: User → UI → Controller → DB (cơ chế chạy SP với biến tham số).
- CLASS: 22 lớp/bảng. Toàn bộ schema gốc được phản chiếu trong "Chú thích bảng".

## 3. Phân tích lệch hiện tại

### 3.1. D20Shift ↔ `shifts` (LỆCH NẶNG — thiếu 14 cột)

| GỐC D20Shift | MIGRATE shifts | Hành động |
|---|---|---|
| Code VARCHAR(16) | code NVARCHAR(255) | giữ |
| Name NVARCHAR(128) | name NVARCHAR(255) | giữ |
| **Description NVARCHAR(256)** | — | **THÊM** |
| **IsCheckin TINYINT** | — | **THÊM** |
| StartTime DATETIME | start_time TIME | giữ TIME, view convert |
| **StartTimeValid1/2 DATETIME** | — | **THÊM** |
| **IsCheckout TINYINT** | — | **THÊM** |
| EndTime DATETIME | end_time TIME | giữ |
| **EndTimeValid1/2 DATETIME** | — | **THÊM** |
| **ShiftBreak INT** | — | **THÊM** |
| StartShiftBreak DATETIME | break_start_time TIME | giữ TIME |
| **StartBreakTimeValid1/2** | — | **THÊM** |
| EndShiftBreak DATETIME | break_end_time TIME | giữ |
| **EndBreakTimeValid1/2** | — | **THÊM** |
| WorkDay NUMERIC(8,2) | workday_value DEC(18,2) | giữ |
| **WorkingHours NUMERIC(8,2)** | — | **THÊM** |
| **ShiftBreakMins INT** | — | **THÊM** |
| **StartWorkingNightTime DATETIME** | — | **THÊM** |
| **EndWorkingNightTime DATETIME** | — | **THÊM** |
| **ShiftMeal INT** | — | **THÊM** |
| MinHourMeal NUMERIC(8,2) | min_meal_hours DEC(18,2) | giữ |
| — | timesheet_type, is_overnight, grace_late_minutes, grace_early_minutes, status | giữ (BE-internal) |

### 3.2. D30AssignedShift ↔ `shift_assignments` (LỆCH KHÁI NIỆM)

**Gốc:** 1 record = 1 dải ngày + 7 cờ `IncludeMon..Sun`. Một phân ca có thể áp dụng nhiều ngày trong tuần lặp lại trong khoảng `[StartDate, EndDate]`.

**Migrate:** 1 record = 1 ngày cụ thể (`work_date`), unique `(employee_id, work_date)`.

→ View `D30AssignedShift` phải **expand** dữ liệu migrate thành dạng dải ngày + tổng hợp các ngày liền kề có cùng `shift_id` cho cùng nhân viên. Hoặc đơn giản hơn (lựa chọn được khuyến nghị): mỗi record migrate map 1-1 thành record view với `StartDate=EndDate=work_date` và 1 cờ tương ứng day-of-week, các cờ khác = 0.

### 3.3. D20PayrollParameter ↔ `payroll_parameters` + `payroll_parameter_details` (LỆCH KHÁI NIỆM)

**Gốc:** 1 bảng flat. Mỗi record = 1 tham số đơn lẻ với (`Id`, `Parameter`, `Name`, `Description`, `EffectiveDate`, `Type`, `Amount`).

**Migrate:** 2 bảng cha-con. Cha có `formula_json`, con có `param_key/param_type/default_value`.

→ Phương án: **thêm bảng mới `d20_payroll_parameters_flat`** đặt cạnh hai bảng migrate (đừng drop cũ vì BE có thể đang dùng). View `D20PayrollParameter` trỏ vào bảng flat.

### 3.4. Bảng khác (mức ưu tiên thấp hơn)

| GỐC | MIGRATE | Hướng map |
|---|---|---|
| D00User | `users` | join với `employees` để ra `EmployeeCode` |
| D20Branch | (chưa có) | **TẠO MỚI `branches`** + seed `A01` |
| D20Department | `departments` | view rename |
| D20Position | `positions` | view rename |
| D20Employee | `employees` + `users` | view join + bổ sung cột Gender/BirthDate/... (theo Thẻ 5 đã add migration `add_resign_date`) |
| D20Dependent | `dependents` | view rename |
| D20ContractType | `contract_types` | view rename, thêm `Type` (0/1) |
| D20Holiday | `holidays` | view rename |
| D20LateEarlyRegulation + Detail | `late_early_rules` | gốc tách 2, migrate gộp 1 — view tách lại 2 |
| D20SalaryScale + D20SalaryGrade + D20SalaryGradeDetail | `salary_levels` (1 bảng) | **TẠO MỚI** 3 bảng vì gốc tách 3 cấp |
| D30LabourContract | `labour_contracts` | view rename, thêm `WorkingType` |
| D30BonusDeduction | `bonus_deductions` + `bonus_deduction_types` | view join |
| D30CheckInOut | `time_logs` | view rename |
| D30AttendanceDoc + D30AbsenceDetail + D30CheckInManualDetail | `attendance_requests` + `attendance_request_details` | gốc tách 3, migrate gộp 2 — view tách 3 |
| D30Attendance | `attendance_daily` + `attendance_monthly_summary` | view = `attendance_daily` join shift_assignment, có thêm `PaidLeaveDays`/`UnpaidLeaveDays` từ summary |
| D30Payroll | `payroll_runs` + `payslips` | view = `payslips` join `payroll_runs` |
| D30PayrollDetail | `payslip_items` | view rename |

## 4. Kiến trúc giải pháp

```
┌──────────────────────────────────────────────────────────────┐
│  FE (React) ──► BE (Laravel) ──► Bảng MIGRATE (snake_case)   │
│                                                              │
│                                   ▲                          │
│                                   │  (read & write)          │
│                                   │                          │
│                            ┌──────┴──────┐                   │
│                            │  VIEW LAYER │                   │
│                            │  D00/D20/D30│                   │
│                            └──────┬──────┘                   │
│                                   │  (chỉ đọc; INSTEAD OF    │
│                                   │   trigger cho 4 bảng     │
│                                   │   lệch concept)          │
│                                   │                          │
│  SP của Dung (usp_*) ─────────────┘                          │
└──────────────────────────────────────────────────────────────┘
```

**Nguyên tắc:**
- View đặt schema `dbo` (đúng theo SP gốc dùng `dbo.D20Shift`, `dbo.usp_*`).
- View dùng `WITH SCHEMABINDING` khi có thể để index hóa được + chống thay đổi bảng nguồn ngầm.
- Bảng migrate vẫn là nguồn sự thật cho FE/BE write path.
- Khi SP của Dung cần INSERT/UPDATE qua view (ví dụ `INSERT INTO D30Attendance`), dùng **INSTEAD OF triggers** chuyển lệnh sang bảng migrate.

## 5. Triển khai theo Sprint

### Sprint 1 — Bổ sung cột thiếu + bảng còn thiếu (ngày 1-2)

**Migrations Laravel mới (đặt timestamp `2026_05_17_*`):**

| File | Action |
|---|---|
| `2026_05_17_100001_add_fujimart_columns_to_shifts.php` | ALTER `shifts`: thêm `description`, `is_checkin`, `is_checkout`, `start_time_valid1`, `start_time_valid2`, `end_time_valid1`, `end_time_valid2`, `shift_break`, `start_break_time_valid1`, `start_break_time_valid2`, `end_break_time_valid1`, `end_break_time_valid2`, `working_hours`, `shift_break_mins`, `start_working_night_time`, `end_working_night_time`, `shift_meal` |
| `2026_05_17_100002_add_pattern_columns_to_shift_assignments.php` | ALTER `shift_assignments`: thêm `start_date`, `end_date`, `include_mon..sun`, drop unique cũ → unique theo `(employee_id, start_date, shift_id)` |
| `2026_05_17_100003_create_branches_table.php` | CREATE `branches`(`id`, `code` VARCHAR(16) UNIQUE, `name` NVARCHAR(128), `address` NVARCHAR(256), `is_active`, timestamps). Seed `A01 = Cơ sở chính` |
| `2026_05_17_100004_add_branch_to_employees.php` | ALTER `employees` thêm `branch_id` FK → `branches.id`, `birth_date`, `gender`, `national_id`, `email`, `mobile`, `bank_account_no`, `bank_name`, `tax_code`, `nationality`, `address` (chỉ thêm cột chưa tồn tại) |
| `2026_05_17_100005_create_d20_payroll_parameters_flat.php` | CREATE `d20_payroll_parameters`(`id`, `parameter` NVARCHAR(64) UNIQUE, `name`, `description`, `effective_date`, `type` VARCHAR(32), `amount` DECIMAL(18,2), `is_active`, timestamps) |
| `2026_05_17_100006_create_salary_scales_grades_details.php` | CREATE 3 bảng: `salary_scales`, `salary_grades`, `salary_grade_details` |
| `2026_05_17_100007_add_late_early_detail.php` | CREATE `late_early_rule_details` (tách `late_early_rules` ra parent + detail) |
| `2026_05_17_100008_add_doc_columns_to_attendance.php` | ALTER `attendance_daily`: thêm `assigned_shift_code`, `paid_leave_days`, `unpaid_leave_days`, `exclude_days`, `exclude_hours` |
| `2026_05_17_100009_add_is_active_for_soft_disable.php` | ALTER tất cả bảng danh mục (`shifts`, `holidays`, `branches`, `late_early_rules`, `contract_types`, `salary_scales`, …): thêm `is_active` BIT DEFAULT 1 (cho nút Đình chỉ) |

**File-level acceptance:**
- `php artisan migrate` chạy không lỗi.
- `php artisan migrate:rollback` revert được toàn bộ Sprint 1.
- Test seed dữ liệu mẫu cho `branches` và 3 record `shifts` đủ 14 cột mới.

### Sprint 2 — View Layer (ngày 3-4)

**File mới:** `backend/database/sql/06_views_fujimart.sql`

Tạo 22 view (CREATE OR ALTER):

```sql
-- 1. D00User
CREATE OR ALTER VIEW dbo.D00User AS
SELECT
    u.username        AS UserName,
    u.name            AS FullName,
    e.id              AS EmployeeCode,  -- INT theo schema gốc
    u.password        AS Password,
    NULL              AS LockDate
FROM dbo.users u
LEFT JOIN dbo.employees e ON e.user_id = u.id;
GO

-- 2. D20Branch
CREATE OR ALTER VIEW dbo.D20Branch AS
SELECT
    b.code    AS Code,
    b.name    AS Name,
    b.address AS Address
FROM dbo.branches b
WHERE b.is_active = 1;
GO

-- 3. D20Department
CREATE OR ALTER VIEW dbo.D20Department AS
SELECT
    d.code AS Code,
    d.name AS Name
FROM dbo.departments d
WHERE d.status = N'active';
GO

-- 4. D20Position, D20Employee, D20Dependent, D20ContractType — tương tự

-- 7. D20Shift (sau khi thêm cột)
CREATE OR ALTER VIEW dbo.D20Shift AS
SELECT
    s.code                                                AS Code,
    s.name                                                AS Name,
    s.description                                         AS Description,
    s.is_checkin                                          AS IsCheckin,
    CAST(s.start_time AS DATETIME)                        AS StartTime,
    CAST(s.start_time_valid1 AS DATETIME)                 AS StartTimeValid1,
    CAST(s.start_time_valid2 AS DATETIME)                 AS StartTimeValid2,
    s.is_checkout                                         AS IsCheckout,
    CAST(s.end_time AS DATETIME)                          AS EndTime,
    CAST(s.end_time_valid1 AS DATETIME)                   AS EndTimeValid1,
    CAST(s.end_time_valid2 AS DATETIME)                   AS EndTimeValid2,
    s.shift_break                                         AS ShiftBreak,
    CAST(s.break_start_time AS DATETIME)                  AS StartShiftBreak,
    CAST(s.start_break_time_valid1 AS DATETIME)           AS StartBreakTimeValid1,
    CAST(s.start_break_time_valid2 AS DATETIME)           AS StartBreakTimeValid2,
    CAST(s.break_end_time AS DATETIME)                    AS EndShiftBreak,
    CAST(s.end_break_time_valid1 AS DATETIME)             AS EndBreakTimeValid1,
    CAST(s.end_break_time_valid2 AS DATETIME)             AS EndBreakTimeValid2,
    s.workday_value                                       AS WorkDay,
    s.working_hours                                       AS WorkingHours,
    s.shift_break_mins                                    AS ShiftBreakMins,
    CAST(s.start_working_night_time AS DATETIME)          AS StartWorkingNightTime,
    CAST(s.end_working_night_time AS DATETIME)            AS EndWorkingNightTime,
    s.shift_meal                                          AS ShiftMeal,
    s.min_meal_hours                                      AS MinHourMeal
FROM dbo.shifts s
WHERE s.status = N'active';
GO

-- 13. D20PayrollParameter (trỏ bảng flat mới, không trỏ payroll_parameters cũ)
CREATE OR ALTER VIEW dbo.D20PayrollParameter AS
SELECT
    p.id              AS Id,
    p.parameter       AS Parameter,
    p.name            AS Name,
    p.description     AS Description,
    p.effective_date  AS EffectiveDate,
    p.type            AS Type,
    p.amount          AS Amount
FROM dbo.d20_payroll_parameters p
WHERE p.is_active = 1;
GO

-- 13a. vD20PayrollPara_ValuePara (Tab tham số giá trị)
CREATE OR ALTER VIEW dbo.vD20PayrollPara_ValuePara AS
SELECT * FROM dbo.D20PayrollParameter
WHERE Type IN ('VALUE', 'RATE', 'COEFF');  -- danh sách cụ thể thống nhất sau
GO

-- 13b. vD20PayrollPara_SalaryType (Tab loại thu nhập)
CREATE OR ALTER VIEW dbo.vD20PayrollPara_SalaryType AS
SELECT * FROM dbo.D20PayrollParameter
WHERE Type IN ('INCOME', 'BONUS', 'DEDUCTION');
GO

-- 16. D30AssignedShift (mapping 1-1, không expand)
CREATE OR ALTER VIEW dbo.D30AssignedShift AS
SELECT
    CAST(sa.id AS VARCHAR(32))                                 AS AssignId,
    sa.work_date                                               AS Date,
    e.employee_code                                            AS EmployeeCode,
    s.code                                                     AS ShiftCode,
    ISNULL(sa.start_date, sa.work_date)                        AS StartDate,
    ISNULL(sa.end_date, sa.work_date)                          AS EndDate,
    ISNULL(sa.include_mon, CASE WHEN DATEPART(weekday, sa.work_date) = 2 THEN 1 ELSE 0 END) AS IncludeMon,
    ISNULL(sa.include_tue, CASE WHEN DATEPART(weekday, sa.work_date) = 3 THEN 1 ELSE 0 END) AS IncludeTue,
    ISNULL(sa.include_wed, CASE WHEN DATEPART(weekday, sa.work_date) = 4 THEN 1 ELSE 0 END) AS IncludeWed,
    ISNULL(sa.include_thu, CASE WHEN DATEPART(weekday, sa.work_date) = 5 THEN 1 ELSE 0 END) AS IncludeThu,
    ISNULL(sa.include_fri, CASE WHEN DATEPART(weekday, sa.work_date) = 6 THEN 1 ELSE 0 END) AS IncludeFri,
    ISNULL(sa.include_sat, CASE WHEN DATEPART(weekday, sa.work_date) = 7 THEN 1 ELSE 0 END) AS IncludeSat,
    ISNULL(sa.include_sun, CASE WHEN DATEPART(weekday, sa.work_date) = 1 THEN 1 ELSE 0 END) AS IncludeSun,
    sa.note                                                    AS Description
FROM dbo.shift_assignments sa
JOIN dbo.employees e ON e.id = sa.employee_id
JOIN dbo.shifts    s ON s.id = sa.shift_id;
GO

-- 18. D30Attendance (join daily + summary; chính là daily, lấy paid/unpaid từ summary)
CREATE OR ALTER VIEW dbo.D30Attendance AS
SELECT
    CAST(ad.id AS VARCHAR(16))    AS DocId,
    ad.work_date                  AS Date,
    s.code                        AS AssignedShiftCode,
    ad.regular_hours              AS WorkingHours,
    ad.workday_value              AS WorkingDays,
    ISNULL(ams.unpaid_leave_days, 0) AS UnpaidLeaveDays,
    ISNULL(ams.paid_leave_days, 0)   AS PaidLeaveDays,
    ad.exclude_days               AS ExcludeDays,
    ad.exclude_hours              AS ExcludeHours,
    ad.night_hours                AS WorkNightHours,
    ad.meal_count                 AS ShiftMeal
FROM dbo.attendance_daily ad
LEFT JOIN dbo.shift_assignments sa ON sa.id = ad.shift_assignment_id
LEFT JOIN dbo.shifts s           ON s.id = sa.shift_id
LEFT JOIN dbo.attendance_monthly_summary ams
    ON ams.attendance_period_id = ad.attendance_period_id
   AND ams.employee_id = ad.employee_id;
GO

-- 20. D30Payroll
CREATE OR ALTER VIEW dbo.D30Payroll AS
SELECT
    'A01'                       AS BranchCode,  -- TODO: lấy từ employee.branch khi multi-branch
    CAST(p.id AS VARCHAR(16))   AS Id,
    ap.from_date                AS Date,
    d.code                      AS DeptCode,
    e.employee_code             AS EmployeeCode,
    NULL                        AS ParaCode,
    0                           AS NetCalc,
    p.gross_salary              AS GrossSalary,
    /* … 30+ cột khác — chi tiết trong file SQL … */
    p.net_salary                AS NetSalary
FROM dbo.payslips p
JOIN dbo.payroll_runs pr        ON pr.id = p.payroll_run_id
JOIN dbo.attendance_periods ap  ON ap.id = pr.attendance_period_id
JOIN dbo.employees e            ON e.id = p.employee_id
LEFT JOIN dbo.departments d     ON d.id = e.department_id;
GO

-- 21. D30PayrollDetail
CREATE OR ALTER VIEW dbo.D30PayrollDetail AS
SELECT
    CAST(pi.id AS VARCHAR(16))   AS RowId,
    CAST(pi.payslip_id AS VARCHAR(16)) AS PayRollId,
    pi.item_name                 AS SalaryType,
    pi.sort_order                AS BuiltinOrder,
    1                            AS Coeff,
    pi.amount                    AS Amount,
    pi.qty                       AS Days,
    NULL                         AS Hours
FROM dbo.payslip_items pi;
GO
```

**INSTEAD OF triggers** cho 4 view ghi được:
- `tr_D20PayrollParameter_IOI` (INSERT) / `tr_D20PayrollParameter_IOU` (UPDATE) / `tr_D20PayrollParameter_IOD` (DELETE) → ghi vào `d20_payroll_parameters`.
- `tr_D30AssignedShift_IOI / IOU / IOD` → **expand dải ngày**: 1 row input (StartDate, EndDate, IncludeMon..Sun) → sinh N row trong `shift_assignments` cho mỗi work_date thoả mãn cờ day-of-week. Khi UPDATE: xoá toàn bộ row cũ của (AssignId) rồi sinh lại theo input mới.
- `tr_D30Attendance_IOI / IOU` → ghi vào `attendance_daily` + recalc summary qua `sp_generate_attendance_summary`.
- `tr_D30Payroll_IOI / IOU` → ghi vào `payslips` (và `payroll_runs` nếu chưa tồn tại run cho period).

### Sprint 3 — 4 SP mới (ngày 5-6)

**File:** `backend/database/sql/07_sp_fujimart.sql`

| SP | Input | Output | Logic chính |
|---|---|---|---|
| `usp_CreateAndCalculateAttendance` | `@_DocDate1 DATE`, `@_BranchCode NVARCHAR(16)`, `@_DeptCode NVARCHAR(16)`, `@_EmployeeCode NVARCHAR(16)` | Update/Insert vào `D30Attendance` (= `attendance_daily`). Trả về `@result_msg` | 1) Lấy phân ca từ `D30AssignedShift`. 2) Lấy `D30CheckInOut`. 3) Tính WorkingHours/Days/Late/Early/Night. 4) Apply nghỉ phép từ `D30AbsenceDetail`. 5) Apply ngày lễ `D20Holiday`. 6) Upsert vào view. |
| `usp_CreateAndCalculatePayroll` | tương tự | Update/Insert `D30Payroll` + `D30PayrollDetail`. Trả về thông báo | 1) Lấy lương căn cứ từ `D30LabourContract` + `D20SalaryGradeDetail`. 2) Lấy công từ `D30Attendance`. 3) Lấy `D20PayrollParameter` (tỷ lệ BHXH/BHYT/BHTN/Công đoàn). 4) Lấy `D30BonusDeduction`. 5) Tính Gross/Net/Tax theo công thức chuẩn VN. 6) Upsert. |
| `usp_AttendanceReport` | `@_DocDate1`, `@_DocDate2`, `@_BranchCode`, `@_DeptCode`, `@_EmployeeCode` | 2 result sets + cột động/cố định 31 ngày | Pivot `D30Attendance` theo nhân viên × ngày. Result set 1: header (NV, phòng ban, tổng công). Result set 2: ngày-by-ngày. |
| `usp_PayrollReport` | `@_DocDate1`, `@_BranchCode`, `@_DeptCode`, `@_EmployeeCode` | Result set + tổng cộng | Aggregate `D30Payroll` theo (Branch, Dept). |
| `usp_PayrollSlip` | `@_DocDate1`, `@_EmployeeCode`, `@_DeptCode`, `@_BranchCode`, `@_SendEmail TINYINT`, `@_MailProfile NVARCHAR(128)` | Khi `@_SendEmail=0`: result set theo template. Khi `@_SendEmail=1`: gửi mail qua `sp_send_dbmail` + trả thông báo | Lấy `D30Payroll` + `D30PayrollDetail` cho mỗi NV. Build HTML body. Loop `sp_send_dbmail` từng người. |

### Sprint 4 — Cập nhật BE/FE (ngày 6-7)

- BE: 2 controllers mới `ProcedureController@runFujimart` (gọi 5 SP), giữ controller cũ.
- BE: 1 view PHP Resource cho 2 tab tham số.
- FE: 2 tab UI cho `vD20PayrollPara_ValuePara` + `vD20PayrollPara_SalaryType`.
- FE: ShiftsPage thêm 14 cột mới (collapsed group).
- FE: ShiftAssignmentsPage chuyển sang dạng dải ngày + 7 checkbox Mon-Sun.

## 6. Test strategy

### 6.1. Pre-test environment check
**Bắt buộc trước mọi lần test:**

```powershell
docker ps --format "table {{.Names}}\t{{.Ports}}"
```

Nếu port `5173` / `8001` / `1433` bị chiếm (vd mesoco-vite đang chạy 5173), **KHÔNG dừng container kia**. Thay vào đó override port trong `docker-compose.override.yml`:

```yaml
services:
  frontend:
    ports: ["5174:5173"]
  backend:
    ports: ["8002:8000"]
    environment:
      - APP_URL=http://localhost:8002
  sqlserver:
    ports: ["1434:1433"]
```

Đặt file này vào `.gitignore` (host-specific). Update `frontend` env `VITE_API_BASE_URL=http://127.0.0.1:8002/api` tương ứng.

### 6.2. Unit tests (BE — phpunit)
- `tests/Feature/Migrations/Sprint1Test.php`: chạy migrate fresh, kiểm tra schema từng bảng có đủ cột.
- `tests/Feature/Views/D20ShiftViewTest.php`: insert 1 shift, query `SELECT * FROM dbo.D20Shift`, assert 25 cột.
- `tests/Feature/Views/D30AssignedShiftViewTest.php`: insert qua view (test trigger INSTEAD OF).
- `tests/Feature/StoredProcedures/UspCreateAndCalculateAttendanceTest.php`: seed 1 NV + 1 ca + 5 check-in log, exec SP, assert `attendance_daily` có record.

### 6.3. Integration test
- Run docker compose up với override ports.
- `php artisan migrate:fresh --seed`.
- `php artisan db:seed --class=FujimartSampleSeeder`.
- Gọi `EXEC usp_CreateAndCalculateAttendance @_DocDate1='20260101', @_BranchCode='A01'`.
- Assert FE `/api/attendance/daily` trả số liệu đúng.

### 6.4. Acceptance criteria (theo yêu cầu user)

- [ ] SP `usp_CreateAndCalculateAttendance` chạy thành công cho data tháng 1/2026.
- [ ] SP `usp_CreateAndCalculatePayroll` chạy thành công.
- [ ] FE Ca làm việc hiển thị đủ 25 cột.
- [ ] FE Tham số lương có 2 tab `vD20PayrollPara_ValuePara`, `vD20PayrollPara_SalaryType`.
- [ ] FE Phân ca có dải ngày + 7 checkbox.
- [ ] Tất cả thực hiện trong port 5174/8002/1434, không can thiệp mesoco containers.

## 7. Rủi ro & giảm thiểu

| Rủi ro | Mức | Giảm thiểu |
|---|---|---|
| INSTEAD OF trigger chậm khi insert lớn | Med | Bulk insert thẳng vào bảng migrate, không qua view |
| 2 bảng `payroll_parameters` cũ + `d20_payroll_parameters` mới gây nhầm | Low | Document rõ trong README; BE chỉ đọc bảng mới cho tab tham số |
| SP dùng VARCHAR cho `EmployeeCode` nhưng BE dùng INT id | High | Trong view luôn map `e.employee_code` (string) ra; SP không bao giờ chạm `employees.id` |
| Mất unique constraint cũ `(employee_id, work_date)` ở `shift_assignments` | Med | Migration drop unique kèm test BE seed; replace bằng unique mới |
| Multi-branch chưa rõ rule | Low | Sprint 1 chỉ seed `A01`; multi-branch ở sprint sau |

## 8. Rollback plan

Mỗi migration Sprint 1 có method `down()` đầy đủ. Để rollback toàn bộ:

```bash
docker exec payroll-backend php artisan migrate:rollback --step=9
docker exec payroll-sqlserver /opt/mssql-tools18/bin/sqlcmd -U sa -P "$PWD" -i /tmp/drop_views.sql
```

`drop_views.sql` chứa `DROP VIEW IF EXISTS dbo.D00User; … DROP PROCEDURE IF EXISTS dbo.usp_CreateAndCalculateAttendance; …`.

## 9. File mới sẽ tạo

```
backend/
  database/
    migrations/
      2026_05_17_100001_add_fujimart_columns_to_shifts.php
      2026_05_17_100002_add_pattern_columns_to_shift_assignments.php
      2026_05_17_100003_create_branches_table.php
      2026_05_17_100004_add_branch_to_employees.php
      2026_05_17_100005_create_d20_payroll_parameters_flat.php
      2026_05_17_100006_create_salary_scales_grades_details.php
      2026_05_17_100007_add_late_early_detail.php
      2026_05_17_100008_add_doc_columns_to_attendance.php
      2026_05_17_100009_add_is_active_for_soft_disable.php
    seeders/
      FujimartSampleSeeder.php
    sql/
      06_views_fujimart.sql           ← 22 view CREATE OR ALTER
      07_sp_fujimart.sql              ← 5 SP CREATE OR ALTER + triggers
      08_seed_fujimart_params.sql     ← seed `d20_payroll_parameters` mẫu
docker-compose.override.yml.example   ← gợi ý cho dev đổi port
docs/superpowers/specs/
  2026-05-17-fujimart-table-mapping-design.md  ← (file này)
```

## 10. Câu hỏi mở (cần xác nhận trước khi sang plan)

1. Có cần giữ song song bảng `payroll_parameters` + `payroll_parameter_details` cũ không? (đề xuất: giữ vì BE có thể đang dùng cho formula JSON; bảng flat mới chỉ phục vụ SP gốc.)
2. `EmployeeCode` trong view `D00User` để là `INT` (theo schema gốc) hay `VARCHAR(16)` (theo `employees.employee_code` migrate)? Theo Chú thích bảng là `INT` → việc map sẽ là `e.id`. **Đề xuất:** giữ `INT = e.id`.
3. Quy ước `Type` của `d20_payroll_parameters` cho 2 tab cụ thể là gì? (đề xuất: `VALUE`/`RATE`/`COEFF` cho tab 1; `INCOME`/`BONUS`/`DEDUCTION` cho tab 2.)

---

**Next step:** Sau khi user duyệt spec → chuyển sang `writing-plans` để vẽ kế hoạch step-by-step cho Sprint 1 trước.
