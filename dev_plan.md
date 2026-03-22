<!-- markdownlint-disable MD060 -->

# BRD / SRS NGẮN GỌN

## HRM Salary & Contract Management System

Phiên bản làm việc chung cho FE, BE và UX/UI Designer.

| Mục | Nội dung |
| --- | --- |
| Project | Web quản lý hợp đồng, chấm công và tiền lương |
| Tech stack | Frontend: React + Vite + Tailwind CSS; Backend: Laravel; Database: SQL Server |
| Phạm vi ưu tiên | Attendance (chấm công) + Payroll (tính lương) chạy hoàn chỉnh trước; Contract ở mức đủ dùng |
| Tài liệu này gồm | BRD/SRS ngắn gọn, permission matrix, API contract JSON mẫu, prompt cho AI thiết kế giao diện |

---

## 1. Mục tiêu và phạm vi

Mục tiêu hệ thống là quản lý dữ liệu nhân sự liên quan đến hợp đồng lao động, chấm công, tính lương, phiếu lương và báo cáo; trong đó Attendance và Payroll là trục nghiệp vụ chính phải vận hành end-to-end.

Phạm vi MVP của tài liệu này gồm:

- Đăng nhập, phân quyền theo role và điều hướng menu theo permission.
- Quản lý employee master, contract master, shift, holiday, late/early rule, payroll parameter.
- Nhận time logs từ máy chấm công hoặc nhập bổ sung; tạo attendance daily và attendance monthly summary.
- Employee xác nhận bảng công cá nhân; HR xử lý điều chỉnh; Payroll preview, finalize, lock kỳ lương; xuất payslip và report.

## 2. Role và nguyên tắc phân quyền

Hệ thống dùng RBAC (Role-Based Access Control). UI phải ẩn theo permission thật sự, không chỉ disable button. Admin hệ thống không mặc định được sửa dữ liệu nghiệp vụ nếu chưa được gán business role.

| Role | Mô tả | Được làm | Không được làm |
| --- | --- | --- | --- |
| Employee | Cán bộ nhân viên tự phục vụ | Xem công cá nhân, gửi request, xác nhận bảng công, xem payslip | Không xem dữ liệu người khác; không chạy payroll; không sửa master data |
| HR Staff | Vận hành nhân sự và attendance | Quản lý nhân viên, hợp đồng, ca làm, log chấm công, request, tổng hợp công | Không finalize/lock payroll; không quản user/role hệ thống |
| Payroll Accountant | Vận hành tính lương | Quản lý payroll parameters, bonus/deduction, preview/finalize/lock payroll, xuất payslip | Không sửa raw time logs; không phân ca; không quản user/role |
| Management | Lãnh đạo xem điều hành | Xem dashboard, summary, report, export | Không sửa nghiệp vụ; không chạy payroll; không import logs |
| System Admin | Quản trị kỹ thuật | Quản lý user, role, permission, audit log, system config | Không mặc định sửa lương/công; không approve nghiệp vụ nếu chưa được gán role |

## 3. Danh sách feature nên có

### 3.1 Shared / System

- Login, logout, change password, current user profile, notification center.
- Role-based sidebar / menu config.
- Audit log cho login, update, approve, finalize, export.

### 3.2 Employee Self-Service

- My Attendance: calendar/list, log vào/ra, late/early, anomaly.
- My Shift Calendar.
- My Requests: leave, manual attendance, adjustment, overtime.
- My Payslips: list, detail, print view.
- Attendance confirmation page.

### 3.3 HR Operations

- Employee master, contract master, shift master, holiday master, late/early rule master.
- Shift assignment theo ngày/tháng hoặc import template.
- Attendance log monitor, anomaly queue, request approval.
- Generate attendance daily, attendance monthly summary, gửi xác nhận bảng công, lock attendance period.

### 3.4 Payroll Operations

- Payroll parameter master, bonus/deduction management.
- Payroll run wizard: chọn kỳ và phạm vi -> kiểm tra input parameters -> preview -> finalize -> lock.
- Payslip list/detail, export payslip batch, payroll reports.

### 3.5 Reports / Management

- Attendance summary, payroll summary, labor cost by department, contract expiry report.
- Executive dashboard và report center cho management.

### 3.6 Admin

- User management, role & permission matrix, system config, backup/integration monitor.

## 4. BRD / SRS ngắn gọn

### 4.1 Actors

Employee, HR Staff, Payroll Accountant, Management, System Admin.

### 4.2 Business flow chính

- Master data setup (employee, contract, shift, holiday, late/early rule, payroll parameters).
- HR tạo phân ca hoặc import phân ca cho nhân viên.
- Hệ thống nhận time logs từ máy chấm công; nếu phát sinh thiếu/sai, employee tạo attendance request và HR duyệt.
- Hệ thống generate attendance daily và attendance monthly summary theo attendance period.
- Employee xác nhận bảng công cá nhân; HR review và lock attendance period.
- Payroll Accountant preview payroll, điều chỉnh bonus/deduction, finalize và lock payroll period.
- Hệ thống xuất payslip và management reports.

### 4.3 Functional requirements

| ID | Module | Requirement | Priority |
| --- | --- | --- | --- |
| FR-01 | Auth | Người dùng đăng nhập bằng username/password; menu hiển thị theo permission. | High |
| FR-02 | Employee | Employee xem công, lịch ca, request, payslip và xác nhận bảng công cá nhân. | High |
| FR-03 | Attendance | HR import/sync time logs, xử lý anomaly, duyệt requests, generate daily và monthly summary. | High |
| FR-04 | Attendance | Attendance period có state machine: draft -> generated -> employee_confirming -> confirmed -> locked. | High |
| FR-05 | Payroll | Payroll Accountant preview payroll theo period/scope; finalize tạo payslip snapshot; lock không cho sửa trực tiếp. | High |
| FR-06 | Reports | Xuất report theo template; log lại người export và thời điểm export. | Medium |
| FR-07 | Admin | Admin quản lý user/role/permission/system config, nhưng không mặc định có quyền sửa business data. | High |

### 4.4 Non-functional requirements

- Auditability: mọi approve/finalize/lock/export phải có audit log.
- Consistency: payslip và attendance summary là snapshot; contract mới không làm thay đổi kỳ lương cũ.
- Security: RBAC, account lockout, password policy, session timeout.
- Performance: list màn hình chính phải phân trang; summary/report dùng view hoặc stored procedure.
- Traceability: có thể drill-down từ payslip về attendance summary và bonus/deduction items.

## 5. Logical DB schema

Thiết kế database nên ưu tiên tách rõ master data, transaction data, calculated data và snapshot data. Calculation nặng nằm ở SQL Server; Backend chịu trách nhiệm validation, orchestration và RBAC.

| Nhóm bảng | Danh sách bảng |
| --- | --- |
| 5.1 Identity / RBAC | users, roles, permissions, user_roles, role_permissions |
| 5.2 Organization / Employee | departments, positions, employees, dependents |
| 5.3 Contract / Compensation | contract_types, payroll_types, salary_levels, labour_contracts, allowance_types, contract_allowances |
| 5.4 Attendance Master | shifts, holidays, late_early_rules, attendance_periods |
| 5.5 Attendance Transaction | shift_assignments, time_logs, attendance_requests, attendance_request_details, attachments |
| 5.6 Attendance Calculated | attendance_daily, attendance_monthly_summary |
| 5.7 Payroll | payroll_parameters, payroll_parameter_details, bonus_deduction_types, bonus_deductions, payroll_runs, payslips, payslip_items |
| 5.8 Reporting / Audit / System | report_templates, report_jobs, audit_logs, system_configs |

### 5.9 SQL Server objects nên có

- Views: vw_employee_active_contract, vw_time_log_anomalies, vw_attendance_daily_detail, vw_attendance_monthly_summary, vw_payslip_print, vw_payroll_summary_by_department.
- Functions: fn_calc_late_minutes, fn_calc_early_minutes, fn_calc_night_hours, fn_calc_workday_value, fn_calc_pit, fn_salary_proration.
- Stored procedures: sp_import_time_logs, sp_generate_attendance_daily, sp_generate_attendance_summary, sp_apply_attendance_request, sp_preview_payroll, sp_finalize_payroll, sp_lock_payroll_period, sp_report_payroll_summary.

## 6. Permission matrix

| Permission | Employee | HR | Payroll | Management | Sys Admin |
| --- | --- | --- | --- | --- | --- |
| auth.login | Y | Y | Y | Y | Y |
| employee.self.view | Y |  |  |  |  |
| attendance.self.view | Y | Y | R | R |  |
| attendance.request.create | Y | Y |  |  |  |
| attendance.request.approve |  | Y |  |  |  |
| attendance.logs.view |  | Y |  |  |  |
| attendance.logs.import |  | Y |  |  |  |
| attendance.summary.generate |  | Y |  |  |  |
| attendance.period.lock |  | Y |  |  |  |
| contract.view | Self | Y | R | R |  |
| contract.manage |  | Y |  |  |  |
| payroll.parameter.manage |  |  | Y |  |  |
| payroll.run.preview |  |  | Y |  |  |
| payroll.run.finalize |  |  | Y |  |  |
| payroll.run.lock |  |  | Y |  |  |
| payslip.self.view | Y |  |  |  |  |
| payslip.all.view |  |  | Y | R |  |
| report.attendance.export |  | Y |  | Y |  |
| report.payroll.export |  |  | Y | Y |  |
| admin.user.manage |  |  |  |  | Y |
| admin.role.manage |  |  |  |  | Y |
| admin.audit.view |  |  |  |  | Y |

Ghi chú:

- Y = full access trong phạm vi nghiệp vụ.
- R = read-only.
- Self = chỉ dữ liệu của chính user.

## 7. Role-based UI visibility

| Màn hình / Menu | Employee | HR | Payroll | Management | Sys Admin |
| --- | --- | --- | --- | --- | --- |
| Dashboard cá nhân | Y |  |  |  |  |
| HR Dashboard |  | Y |  |  |  |
| Payroll Dashboard |  |  | Y |  |  |
| Executive Dashboard |  |  |  | Y |  |
| My Attendance | Y |  |  |  |  |
| Attendance Logs |  | Y |  |  |  |
| Attendance Requests Approval |  | Y |  |  |  |
| Attendance Summary |  | Y | R | R |  |
| Contract Management |  | Y | R | R |  |
| Payroll Run Wizard |  |  | Y |  |  |
| Payslip List All |  |  | Y | R |  |
| My Payslip | Y |  |  |  |  |
| Reports Center |  | Y | Y | Y |  |
| User / Role Management |  |  |  |  | Y |
| System Config / Audit Log |  |  |  |  | Y |

## 8. API contract JSON mẫu

Nguyên tắc API:

- FE chỉ gọi domain API.
- Laravel service layer quyết định dùng view/function/stored procedure nào ở SQL Server.
- Tất cả response nên thống nhất envelope: success, message, data, meta.

### 8.1 Auth

POST /api/auth/login

Request

```json
{
  "username": "hr01",
  "password": "********"
}
```

Response

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "access_token_here",
    "user": {
      "id": 2,
      "username": "hr01",
      "full_name": "Nguyen Thi HR",
      "roles": ["HR"],
      "permissions": ["attendance.logs.view", "attendance.request.approve"]
    }
  }
}
```

### 8.2 Attendance - import/generate/summary

POST /api/attendance/periods

Request

```json
{
  "month": 3,
  "year": 2026,
  "from_date": "2026-03-01",
  "to_date": "2026-03-31"
}
```

Response

```json
{
  "success": true,
  "data": {
    "id": 5,
    "period_code": "2026-03",
    "status": "draft"
  }
}
```

POST /api/attendance/logs/import

Request

```json
{
  "source": "machine",
  "file_name": "machine_logs_2026_03_01.csv"
}
```

Response

```json
{
  "success": true,
  "data": {
    "imported_rows": 420,
    "invalid_rows": 8,
    "job_status": "completed"
  }
}
```

POST /api/attendance/periods/5/generate-summary

Request

```json
{
  "scope": "all"
}
```

Response

```json
{
  "success": true,
  "data": {
    "period_id": 5,
    "generated_employees": 20,
    "status": "generated"
  }
}
```

GET /api/attendance/periods/5/summary?department_id=2&page=1

Response

```json
{
  "success": true,
  "data": [
    {
      "employee_id": 10,
      "employee_code": "EMP010",
      "employee_name": "Tran Van A",
      "total_workdays": 25,
      "regular_hours": 200,
      "ot_hours": 10,
      "night_hours": 0,
      "late_minutes": 15,
      "early_minutes": 0,
      "meal_count": 22,
      "status": "employee_confirmed"
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 1
  }
}
```

### 8.3 Employee attendance request

POST /api/me/requests

Request

```json
{
  "request_type": "manual_checkin",
  "from_date": "2026-03-08",
  "to_date": "2026-03-08",
  "reason": "Quên chấm công buổi sáng",
  "details": [
    {
      "work_date": "2026-03-08",
      "requested_check_in": "2026-03-08T08:03:00"
    }
  ]
}
```

Response

```json
{
  "success": true,
  "data": {
    "request_id": 1005,
    "status": "pending"
  }
}
```

### 8.4 Payroll preview/finalize

POST /api/payroll/periods/5/required-params

Request

```json
{}
```

Response

```json
{
  "success": true,
  "data": {
    "period_id": 5,
    "parameters": [
      {
        "key": "insurance_rate_employee",
        "label": "Insurance rate employee",
        "type": "number",
        "required": true,
        "default": 10.5
      }
    ]
  }
}
```

POST /api/payroll/periods/5/preview

Request

```json
{
  "scope_type": "department",
  "scope_value": 2,
  "parameters": {
    "insurance_rate_employee": 10.5
  }
}
```

Response

```json
{
  "success": true,
  "data": {
    "payroll_run_id": 301,
    "status": "previewed",
    "employees": [
      {
        "employee_id": 10,
        "employee_name": "Tran Van A",
        "base_salary": 12000000,
        "bonus_total": 500000,
        "deduction_total": 0,
        "insurance_employee": 1260000,
        "pit_amount": 150000,
        "net_salary": 11090000
      }
    ]
  }
}
```

POST /api/payroll/periods/5/finalize

Request

```json
{
  "payroll_run_id": 301
}
```

Response

```json
{
  "success": true,
  "data": {
    "payroll_run_id": 301,
    "status": "finalized",
    "generated_payslips": 12
  }
}
```

GET /api/payroll/payslips/9001

Response

```json
{
  "success": true,
  "data": {
    "id": 9001,
    "employee_id": 10,
    "employee_name": "Tran Van A",
    "period_code": "2026-03",
    "base_salary_snapshot": 12000000,
    "gross_salary": 12500000,
    "insurance_employee": 1260000,
    "pit_amount": 150000,
    "bonus_total": 500000,
    "deduction_total": 0,
    "net_salary": 11090000,
    "status": "locked",
    "items": [
      {
        "item_code": "BASE",
        "item_name": "Base Salary",
        "item_group": "earning",
        "amount": 12000000
      },
      {
        "item_code": "BONUS",
        "item_name": "KPI Bonus",
        "item_group": "earning",
        "amount": 500000
      }
    ]
  }
}
```

### 8.5 Reports

POST /api/reports/payroll-summary/export

Request

```json
{
  "period_id": 5,
  "format": "xlsx"
}
```

Response

```json
{
  "success": true,
  "data": {
    "job_id": 7001,
    "status": "queued",
    "download_url": "/downloads/report_7001.xlsx"
  }
}
```

## 9. Data mẫu nên seed để test

- Role seeds: SYS_ADMIN, HR, PAYROLL, MANAGEMENT, EMPLOYEE.
- Users: admin01, hr01, hr02, payroll01, manager01, emp001 .. emp010.
- Departments: HR, Accounting, Operations, Engineering, Management.
- Shifts: HC08 (08:00-17:00), HC13 (13:00-22:00), N22 (22:00-06:00).
- Attendance data: 10 nhân viên x 20 ngày, 400-500 raw logs, ít nhất 15 anomaly cases.
- Payroll data: 1 period draft, 1 period previewed, 1 period finalized/locked, 20 payslips, 10 bonus/deduction entries.

## 10. Prompt cho AI thiết kế giao diện xem trước

Bạn là Senior UX/UI Designer cho một Web-App enterprise về HRM Salary & Contract Management. Hãy tạo bản thiết kế giao diện xem trước (preview UI) cho các role Employee, HR Staff, Payroll Accountant, Management và System Admin.

### Bối cảnh nghiệp vụ

- Module chính: Employee Self-Service, Contracts, Attendance, Payroll, Reports, Admin.
- Trọng tâm UX: Attendance và Payroll phải dễ hiểu, ít sai thao tác, nhìn rõ kỳ đang thao tác và trạng thái dữ liệu draft/generated/confirmed/locked.
- Tech stack FE: React + Vite + Tailwind CSS.
- Kiểu sản phẩm: desktop-first, enterprise, data-dense nhưng sạch và dễ đọc.

### Yêu cầu thiết kế

1. Thiết kế site map và sidebar theo role.
2. Tạo các màn hình preview sau:
   - Login page
   - Dashboard theo role
   - My Attendance
   - Attendance Logs
   - Attendance Monthly Summary
   - Attendance Request Approval
   - Contract List/Detail
   - Payroll Run Wizard (3 bước: chọn kỳ -> nhập tham số -> preview/finalize)
   - Payslip List
   - Payslip Detail / print-friendly
   - Reports Center
   - User / Role Management
3. Mỗi màn hình phải có:
   - loading state, empty state, error state
   - filter/search/sort/pagination
   - status badge rõ ràng
   - action bar nhất quán
4. Mọi action nguy hiểm như approve adjustment, finalize payroll, lock payroll phải có confirm modal.
5. UI phải ẩn theo permission thật sự:
   - Employee chỉ thấy dữ liệu cá nhân
   - HR không thấy user/role admin
   - Payroll không thấy màn import raw logs
   - Management chỉ read-only
   - Sys Admin không mặc định thấy payroll run
6. Phong cách hình ảnh:
   - neutral palette, nhấn xanh dương hoặc xanh lá
   - sidebar trái, topbar trên
   - table là thành phần trung tâm
   - typography rõ, dễ đọc số liệu
   - card bo nhẹ, bóng đổ nhẹ, enterprise style
7. Output mong muốn:
   - wireframe low-fi
   - high-fi preview cho key screens
   - component list
   - design tokens
   - ghi chú rõ role nào thấy UI nào, role nào không thấy

## 11. Ghi chú triển khai

- Attendance summary và payslip phải là snapshot; không được tính lại ngược từ dữ liệu hiện tại khi xem kỳ cũ.
- SQL Server nên giữ calculations nặng qua views/functions/stored procedures; Laravel là orchestration layer.
- Không dùng endpoint generic kiểu execute-procedure; FE gọi domain API.
- Mọi export report, finalize payroll, lock period đều phải được audit.
