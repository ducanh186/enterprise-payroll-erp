Dưới đây là bản **system design ở mức Senior** cho hệ thống **quản lý hợp đồng, chấm công, tính lương**, nhưng tôi sẽ **ưu tiên mạnh Attendance (chấm công) và Payroll (tính lương)** vì brief đã chốt trọng tâm là hai luồng này phải chạy hoàn chỉnh trước deadline. Brief cũng nêu rõ hệ thống dùng **SQL Server với tables / views / functions / stored procedures**, và phạm vi gồm **hợp đồng + chấm công + lương + báo cáo**.  

---

# 1) Góc nhìn tổng thể trước khi đi chi tiết

## 1.1 Luồng nghiệp vụ lõi

Từ các sơ đồ bạn gửi, luồng đúng nên là:

**Master data**
→ **Phân ca (Shift Assignment)**
→ **Nhận log máy chấm công / đơn bổ sung**
→ **Tính Attendance theo ngày**
→ **Tổng hợp công theo kỳ**
→ **Nhân viên xác nhận / HR điều chỉnh**
→ **Chạy Payroll preview**
→ **Finalize Payroll**
→ **Lock kỳ lương**
→ **Xuất Payslip / Report**

## 1.2 4 nguyên tắc thiết kế nên giữ

### 1) Tách **transaction data** và **snapshot data**

* Attendance raw log là dữ liệu gốc
* Attendance daily/monthly là dữ liệu đã tính
* Payslip là **snapshot** tại thời điểm chốt lương
  Không được tính ngược trực tiếp từ hợp đồng hiện tại khi xem lương tháng cũ.

### 2) Tính toán nặng để ở **SQL Server**

Vì yêu cầu đã xác định DB side có **views / functions / stored procedures**, nên:

* SQL Server xử lý phần tính công, tính lương
* Backend lo **auth, validation, orchestration, RBAC, audit log, API response**

### 3) UI không gọi stored procedure trực tiếp

Flow đúng là:

**React UI → Laravel API → Service → SQL Server SP/View/Function**

Không để FE biết tên SP.

### 4) Quy trình phải có **status machine**

Ví dụ:

* Attendance Period: `draft -> generated -> employee_confirming -> confirmed -> locked`
* Payroll Run: `draft -> previewed -> finalized -> locked`
* Request: `draft -> pending -> approved/rejected -> applied`

---

# 2) Role (vai trò) và Permission (quyền)

Tôi đề xuất dùng **5 role chính**, đúng với business flow và use case bạn gửi:

1. **Employee**
2. **HR Staff**
3. **Payroll Accountant**
4. **Management**
5. **System Admin**

## 2.1 Mô tả từng role

### 1. Employee

Dành cho cán bộ nhân viên tự phục vụ.

**Có quyền**

* Xem attendance cá nhân
* Xem shift cá nhân
* Gửi request bổ sung / leave / manual attendance
* Xác nhận bảng công cá nhân
* Xem payslip cá nhân
* Xem profile cá nhân

**Không có quyền**

* Không xem dữ liệu người khác
* Không chạy payroll
* Không chỉnh master data
* Không xem dashboard toàn công ty
* Không xem contract của người khác
* Không export report toàn công ty

---

### 2. HR Staff

Chịu trách nhiệm nhân sự và attendance operations.

**Có quyền**

* Quản lý employee master cơ bản
* Quản lý contract
* Quản lý shift, holiday, late/early rules
* Import/check máy chấm công
* Xử lý attendance requests
* Tổng hợp công
* Mở đợt xác nhận attendance
* Chốt attendance sang payroll

**Không có quyền**

* Không lock payroll
* Không sửa công thức lương lõi nếu không được phân quyền thêm
* Không quản lý user/role hệ thống
* Không backup hệ thống

---

### 3. Payroll Accountant

Chịu trách nhiệm tiền lương.

**Có quyền**

* Xem attendance summary đã xác nhận
* Quản lý payroll parameters
* Quản lý bonus/deduction
* Preview payroll
* Finalize payroll
* Lock kỳ lương
* Xuất payslip / payroll report

**Không có quyền**

* Không quản user/role
* Không sửa raw check-in log
* Không đổi shift/holiday master
* Không sửa contract ngoài phạm vi compensation đọc được

---

### 4. Management

Chủ yếu xem dashboard và report.

**Có quyền**

* Xem dashboard quản trị
* Xem attendance/payroll summary
* Xem labor cost report
* Xem contract expiry report
* Export report cấp quản lý

**Không có quyền**

* Không sửa dữ liệu nghiệp vụ
* Không import log
* Không chạy payroll
* Không chỉnh user/role
* Không sửa contract/shift master

---

### 5. System Admin

Chịu trách nhiệm system setup và RBAC.

**Có quyền**

* Quản lý user
* Quản lý role/permission
* Xem audit log
* Quản lý system config
* Backup / restore / integration config

**Không nên có quyền mặc định**

* Không được chỉnh số lương
* Không được approve payroll
* Không được sửa attendance business data
  Muốn làm phải gán thêm business role. Đây là nguyên tắc **segregation of duties**.

---

## 2.2 Permission code nên chuẩn hóa

Dùng format:

`module.resource.action`

Ví dụ:

* `attendance.logs.view`
* `attendance.logs.import`
* `attendance.request.approve`
* `attendance.summary.generate`
* `payroll.run.preview`
* `payroll.run.finalize`
* `payroll.run.lock`
* `payslip.self.view`
* `admin.user.manage`
* `report.payroll.export`

---

# 3) UI nào role nào được thấy, và UI nào không nên có

## 3.1 Employee

**Có UI**

* Login
* Dashboard cá nhân
* My Attendance
* My Shift Calendar
* My Requests
* My Payslips
* My Profile

**Không có UI**

* User management
* Role management
* Attendance import
* Attendance anomaly dashboard toàn công ty
* Payroll run
* Salary formula
* System settings

---

## 3.2 HR Staff

**Có UI**

* HR Dashboard
* Employee List / Detail
* Contract List / Detail
* Shift Master
* Shift Assignment
* Holiday Master
* Late/Early Rule
* Attendance Logs
* Attendance Requests
* Attendance Daily/Monthly Summary
* Attendance Confirmation Batch
* HR Reports

**Không có UI**

* Payroll formula editing sâu nếu không được cấp
* Payroll finalize/lock
* User/role admin
* Backup/restore

---

## 3.3 Payroll Accountant

**Có UI**

* Payroll Dashboard
* Attendance Summary read-only
* Payroll Parameters
* Bonus/Deduction
* Payroll Run Wizard
* Payslip List
* Payslip Detail
* Payroll Lock / Unlock request
* Payroll Reports

**Không có UI**

* Shift assignment
* Check-in raw correction
* User/role admin
* Contract full CRUD

---

## 3.4 Management

**Có UI**

* Executive Dashboard
* Cost Overview
* Attendance Summary
* Payroll Summary
* Reports Center
* Export Report

**Không có UI**

* Không có edit form
* Không có import form
* Không có admin pages
* Không có raw transaction correction pages

---

## 3.5 System Admin

**Có UI**

* User Management
* Role & Permission
* Audit Log
* System Config
* Integration Settings
* Backup/Restore Monitor

**Không có UI mặc định**

* Payroll run
* Attendance approval
* Contract salary edit

---

# 4) DB Schema hợp lý

Tôi sẽ thiết kế theo 8 nhóm bảng.

---

## 4.1 Identity / RBAC

### `users`

* `id` PK
* `username` unique
* `password_hash`
* `email`
* `phone`
* `is_active`
* `last_login_at`
* `created_at`, `updated_at`

### `roles`

* `id` PK
* `code` unique (`EMPLOYEE`, `HR`, `PAYROLL`, `MANAGEMENT`, `SYS_ADMIN`)
* `name`

### `permissions`

* `id` PK
* `code` unique
* `name`
* `module`

### `user_roles`

* `user_id` FK
* `role_id` FK

### `role_permissions`

* `role_id` FK
* `permission_id` FK

---

## 4.2 Organization / Employee Master

### `departments`

* `id`
* `code`
* `name`
* `parent_id` nullable
* `manager_employee_id` nullable
* `status`

### `positions`

* `id`
* `code`
* `name`
* `department_id`
* `status`

### `employees`

* `id`
* `employee_code` unique
* `user_id` nullable FK
* `full_name`
* `dob`
* `gender`
* `national_id`
* `tax_code`
* `email`
* `phone`
* `bank_account_no`
* `bank_name`
* `department_id`
* `position_id`
* `join_date`
* `employment_status` (`active`, `inactive`, `terminated`)
* `created_at`, `updated_at`

### `dependents`

* `id`
* `employee_id`
* `full_name`
* `dob`
* `relationship`
* `national_id`
* `tax_reduction_from`
* `tax_reduction_to`

---

## 4.3 Contract / Compensation Master

### `contract_types`

* `id`
* `code`
* `name`
* `duration_months`
* `is_probationary`

### `payroll_types`

* `id`
* `code`
* `name`
  Ví dụ: monthly, probation, daily-rated

### `salary_levels`

* `id`
* `payroll_type_id`
* `code`
* `level_no`
* `amount`
* `effective_from`
* `effective_to`

### `labour_contracts`

* `id`
* `employee_id`
* `contract_no`
* `contract_type_id`
* `position_title_snapshot`
* `department_snapshot`
* `start_date`
* `end_date`
* `sign_date`
* `status` (`draft`, `active`, `expired`, `terminated`)
* `base_salary`
* `salary_level_id` nullable
* `payroll_type_id`
* `probation_rate`
* `created_by`
* `approved_by` nullable

### `allowance_types`

* `id`
* `code`
* `name`
* `is_taxable`
* `is_insurance_base`
* `default_amount`
* `status`

### `contract_allowances`

* `id`
* `contract_id`
* `allowance_type_id`
* `amount`
* `effective_from`
* `effective_to`

---

## 4.4 Attendance Master

### `shifts`

* `id`
* `code`
* `name`
* `start_time`
* `end_time`
* `break_start_time`
* `break_end_time`
* `workday_value`
* `timesheet_type`
* `is_overnight`
* `min_meal_hours`
* `grace_late_minutes`
* `grace_early_minutes`
* `status`

### `holidays`

* `id`
* `holiday_date`
* `name`
* `multiplier`
* `is_paid`

### `late_early_rules`

* `id`
* `code`
* `name`
* `from_minute`
* `to_minute`
* `deduction_type`
* `deduction_value`
* `exclude_meal`
* `effective_from`
* `effective_to`

### `attendance_periods`

* `id`
* `period_code` unique (`2026-03`)
* `month`
* `year`
* `from_date`
* `to_date`
* `status`

---

## 4.5 Shift Assignment / Raw Logs / Requests

### `shift_assignments`

* `id`
* `employee_id`
* `work_date`
* `shift_id`
* `source` (`manual`, `template`, `import`)
* `note`

### `time_logs`

* `id`
* `employee_id`
* `log_time`
* `machine_number`
* `log_type` (`in`, `out`, `unknown`)
* `source` (`machine`, `manual`, `import`)
* `is_valid`
* `invalid_reason`
* `raw_ref`
* `created_at`

### `attendance_requests`

* `id`
* `employee_id`
* `request_type` (`manual_checkin`, `manual_checkout`, `leave`, `overtime`, `adjustment`)
* `from_date`
* `to_date`
* `reason`
* `status` (`draft`, `pending`, `approved`, `rejected`, `applied`)
* `submitted_at`
* `approved_by`
* `approved_at`

### `attendance_request_details`

* `id`
* `request_id`
* `work_date`
* `requested_check_in`
* `requested_check_out`
* `requested_hours`
* `note`

### `attachments`

* `id`
* `module`
* `ref_id`
* `file_name`
* `file_path`
* `mime_type`
* `uploaded_by`

---

## 4.6 Attendance Calculated Tables

### `attendance_daily`

* `id`
* `employee_id`
* `work_date`
* `attendance_period_id`
* `shift_assignment_id` nullable
* `first_in`
* `last_out`
* `late_minutes`
* `early_minutes`
* `regular_hours`
* `ot_hours`
* `night_hours`
* `workday_value`
* `meal_count`
* `attendance_status` (`present`, `leave`, `holiday`, `absent`, `partial`, `anomaly`)
* `source_status`
* `is_confirmed_by_employee`
* `confirmed_at`
* `confirmed_by`
* `calculation_version`

### `attendance_monthly_summary`

* `id`
* `attendance_period_id`
* `employee_id`
* `total_workdays`
* `regular_hours`
* `ot_hours`
* `night_hours`
* `paid_leave_days`
* `unpaid_leave_days`
* `late_minutes`
* `early_minutes`
* `meal_count`
* `status` (`generated`, `employee_confirmed`, `hr_confirmed`, `locked`)
* `generated_at`
* `confirmed_at`

> **Lưu ý thiết kế:** Có cả `attendance_daily` và `attendance_monthly_summary` để đọc nhanh. Đây là đánh đổi giữa **storage** và **performance**.

---

## 4.7 Payroll

### `payroll_parameters`

* `id`
* `code`
* `name`
* `description`
* `effective_from`
* `effective_to`
* `formula_json`
* `status`

### `payroll_parameter_details`

* `id`
* `payroll_parameter_id`
* `param_key`
* `param_type`
* `default_value`
* `validation_rule`
* `display_order`

### `bonus_deduction_types`

* `id`
* `code`
* `name`
* `kind` (`bonus`, `deduction`)
* `is_taxable`
* `is_insurance_base`
* `is_recurring`

### `bonus_deductions`

* `id`
* `employee_id`
* `attendance_period_id`
* `type_id`
* `amount`
* `description`
* `status`
* `created_by`

### `payroll_runs`

* `id`
* `attendance_period_id`
* `run_no`
* `scope_type` (`all`, `department`, `employee`)
* `scope_value`
* `status` (`draft`, `previewed`, `finalized`, `locked`)
* `requested_by`
* `previewed_at`
* `finalized_at`
* `locked_at`

### `payslips`

* `id`
* `attendance_period_id`
* `employee_id`
* `payroll_run_id`
* `contract_id`
* `base_salary_snapshot`
* `gross_salary`
* `taxable_income`
* `insurance_base`
* `insurance_employee`
* `insurance_company`
* `pit_amount`
* `bonus_total`
* `deduction_total`
* `net_salary`
* `status`
* `generated_at`
* `locked_at`

### `payslip_items`

* `id`
* `payslip_id`
* `item_code`
* `item_name`
* `item_group` (`earning`, `deduction`, `tax`, `insurance`, `summary`)
* `qty`
* `rate`
* `amount`
* `sort_order`
* `source_ref`

---

## 4.8 Reporting / Audit / System

### `report_templates`

* `id`
* `code`
* `name`
* `module`
* `sp_name`
* `is_active`

### `report_jobs`

* `id`
* `template_id`
* `params_json`
* `requested_by`
* `status`
* `file_path`
* `created_at`

### `audit_logs`

* `id`
* `actor_user_id`
* `module`
* `action`
* `ref_table`
* `ref_id`
* `before_json`
* `after_json`
* `ip_address`
* `created_at`

### `system_configs`

* `id`
* `config_key`
* `config_value`
* `description`

---

# 5) Quan hệ dữ liệu quan trọng nhất

## 5.1 Quan hệ lõi

* `users 1-n user_roles`
* `roles 1-n role_permissions`
* `employees 1-1 users` hoặc nullable
* `employees 1-n labour_contracts`
* `employees 1-n shift_assignments`
* `employees 1-n time_logs`
* `employees 1-n attendance_requests`
* `employees 1-n attendance_daily`
* `employees 1-n attendance_monthly_summary`
* `employees 1-n payslips`

## 5.2 Quan hệ nghiệp vụ

* `attendance_periods 1-n attendance_daily`
* `attendance_periods 1-n attendance_monthly_summary`
* `attendance_periods 1-n payroll_runs`
* `payroll_runs 1-n payslips`
* `payslips 1-n payslip_items`

---

# 6) SQL Server objects nên có

Vì brief yêu cầu SQL Server side có đủ **tables / views / functions / stored procedures**, nên tôi đề xuất danh sách sau. 

## 6.1 Views

### Attendance

* `vw_employee_active_contract`
* `vw_time_log_anomalies`
* `vw_attendance_daily_detail`
* `vw_attendance_monthly_summary`
* `vw_attendance_confirmation_queue`

### Payroll

* `vw_payroll_input_base`
* `vw_bonus_deduction_period`
* `vw_payslip_print`
* `vw_payroll_summary_by_department`

### Management

* `vw_dashboard_hr`
* `vw_dashboard_payroll`
* `vw_dashboard_management`

---

## 6.2 Functions

### Scalar Function

* `fn_calc_late_minutes(...)`
* `fn_calc_early_minutes(...)`
* `fn_calc_night_hours(...)`
* `fn_calc_workday_value(...)`
* `fn_calc_insurance_employee(...)`
* `fn_calc_pit(...)`
* `fn_salary_proration(...)`

### Table-Valued Function

* `fn_get_attendance_window(@employee_id, @from_date, @to_date)`
* `fn_get_payroll_input(@period_id, @employee_id)`

---

## 6.3 Stored Procedures

### Attendance

* `sp_import_time_logs`
* `sp_generate_attendance_daily`
* `sp_generate_attendance_summary`
* `sp_apply_attendance_request`
* `sp_rebuild_attendance_for_employee`
* `sp_lock_attendance_period`

### Payroll

* `sp_get_payroll_required_params`
* `sp_preview_payroll`
* `sp_finalize_payroll`
* `sp_lock_payroll_period`
* `sp_regenerate_payslip_for_employee`

### Reports

* `sp_report_attendance_summary`
* `sp_report_payroll_summary`
* `sp_report_contract_expiry`
* `sp_export_payslip_batch`

---

# 7) LIST Feature nên có

Tôi chia thành **MVP** và **Later**.

## 7.1 MVP bắt buộc

Vì deadline gần và brief ưu tiên attendance + payroll. 

### A. Auth & RBAC

* Login
* Logout
* Current user profile
* Role-based menu
* Permission middleware

### B. Employee Self-Service

* My Attendance
* My Shift Calendar
* My Requests
* My Payslips
* Confirm Timesheet

### C. HR Master

* Employee master
* Contract master
* Shift master
* Holiday master
* Late/Early rule master

### D. Attendance Operations

* Import / sync machine logs
* Manual attendance adjustment
* Attendance request approval
* Attendance daily generation
* Attendance monthly summary
* Attendance confirmation flow
* Attendance lock

### E. Payroll

* Payroll parameter config
* Bonus / deduction
* Payroll preview
* Payroll finalize
* Payroll lock
* Payslip list/detail
* Export payslip

### F. Reports

* Attendance summary report
* Payroll summary report
* Personal payslip report
* Contract expiry report

### G. Admin

* User management
* Role management
* Audit log

---

## 7.2 Later / nice-to-have

* Email payslip
* Auto sync biometric machine schedule
* Multi-company / multi-branch
* Approval workflow nhiều cấp
* Notification center real-time
* Mobile responsive sâu hơn cho employee
* Report builder động

---

# 8) Gợi ý Backend modules

Nếu đi theo **Laravel**, nên tách module như sau:

* `Auth`
* `Users`
* `Employees`
* `Contracts`
* `Attendance`
* `Payroll`
* `Reports`
* `System`

## 8.1 Quy tắc BE

* Controller mỏng
* Service xử lý nghiệp vụ
* Repository/DB layer gọi view/SP/function
* Form Request validate input
* Policy/Gate cho RBAC
* Audit logging ở service layer
* Idempotent cho preview/generate nếu có thể

## 8.2 Phần nào đặt ở DB, phần nào đặt ở BE

### Đặt ở DB

* attendance calculation
* payroll formula
* summary generation
* report dataset

### Đặt ở BE

* auth
* role permission
* request validation
* workflow orchestration
* API contract
* notification
* file export lifecycle

---

# 9) Gợi ý API

Tôi đề xuất REST API theo domain.

## 9.1 Auth

* `POST /api/auth/login`
* `POST /api/auth/logout`
* `GET /api/me`
* `GET /api/me/permissions`

---

## 9.2 Master data

* `GET /api/departments`
* `GET /api/positions`
* `GET /api/shifts`
* `POST /api/shifts`
* `PUT /api/shifts/{id}`
* `GET /api/holidays`
* `POST /api/holidays`
* `GET /api/late-early-rules`
* `POST /api/late-early-rules`

---

## 9.3 Employees / Contracts

* `GET /api/employees`
* `POST /api/employees`
* `GET /api/employees/{id}`
* `PUT /api/employees/{id}`
* `GET /api/employees/{id}/contracts`
* `POST /api/contracts`
* `GET /api/contracts/{id}`
* `PUT /api/contracts/{id}`

---

## 9.4 Employee self-service

* `GET /api/me/attendance`
* `GET /api/me/shifts`
* `GET /api/me/payslips`
* `GET /api/me/payslips/{id}`
* `GET /api/me/requests`
* `POST /api/me/requests`
* `POST /api/me/attendance-confirmation`

---

## 9.5 Attendance

* `POST /api/attendance/logs/import`
* `GET /api/attendance/logs`
* `POST /api/attendance/logs/manual`
* `GET /api/attendance/anomalies`
* `POST /api/attendance/requests/{id}/approve`
* `POST /api/attendance/requests/{id}/reject`
* `POST /api/attendance/periods`
* `GET /api/attendance/periods`
* `POST /api/attendance/periods/{id}/generate-daily`
* `POST /api/attendance/periods/{id}/generate-summary`
* `GET /api/attendance/periods/{id}/summary`
* `POST /api/attendance/periods/{id}/send-confirmation`
* `POST /api/attendance/periods/{id}/lock`

---

## 9.6 Payroll

* `GET /api/payroll/periods`
* `POST /api/payroll/periods/{id}/required-params`
* `POST /api/payroll/periods/{id}/preview`
* `POST /api/payroll/periods/{id}/finalize`
* `POST /api/payroll/periods/{id}/lock`
* `GET /api/payroll/payslips`
* `GET /api/payroll/payslips/{id}`
* `GET /api/payroll/payslips/{id}/items`
* `POST /api/payroll/bonus-deductions`
* `PUT /api/payroll/bonus-deductions/{id}`

---

## 9.7 Reports

* `GET /api/reports/templates`
* `POST /api/reports/{code}/preview`
* `POST /api/reports/{code}/export`

---

## 9.8 Admin

* `GET /api/admin/users`
* `POST /api/admin/users`
* `PUT /api/admin/users/{id}`
* `POST /api/admin/users/{id}/roles`
* `GET /api/admin/roles`
* `GET /api/admin/audit-logs`
* `GET /api/admin/system-configs`
* `PUT /api/admin/system-configs/{key}`

---

# 10) Màn hình UI nên có

## 10.1 Shared

* Login
* Forgot password
* Change password
* Profile
* Notification center

## 10.2 Employee

* Dashboard cá nhân
* My Attendance calendar/list
* My Shift calendar
* My Requests list/create
* My Payslips list/detail
* Attendance confirmation page

## 10.3 HR

* HR Dashboard
* Employee list/detail/create/edit
* Contract list/detail/create/edit
* Shift master
* Shift assignment calendar
* Holiday master
* Late/Early rules
* Attendance log monitor
* Attendance anomaly queue
* Attendance request approval
* Monthly attendance summary
* Confirmation tracking
* HR reports

## 10.4 Payroll

* Payroll Dashboard
* Payroll parameter master
* Bonus/Deduction management
* Payroll run wizard
* Payslip list
* Payslip detail
* Payroll lock page
* Payroll reports

## 10.5 Management

* Executive dashboard
* Attendance summary
* Payroll summary
* Contract expiry dashboard
* Report center

## 10.6 Admin

* Users
* Roles & permissions
* Audit logs
* System configs
* Backup/integration monitor

---

# 11) Login nên ra sao

## Luồng login chuẩn

* Username/password
* Optional: remember me
* Nếu user có nhiều role thì:

  * hoặc auto vào dashboard theo role chính
  * hoặc cho chọn workspace role khi login
* Sau login:

  * trả JWT/Sanctum token
  * tải `me + permissions + menu config`
  * FE render menu theo permission

## Bảo mật login

* lock account sau nhiều lần sai
* force change password lần đầu
* password policy
* audit log login/logout
* session timeout
* CSRF/XSS-safe flow nếu dùng cookie auth

---

# 12) Data mẫu nên có để test

## 12.1 Role seed

* `SYS_ADMIN`
* `HR`
* `PAYROLL`
* `MANAGEMENT`
* `EMPLOYEE`

## 12.2 User seed

* `admin01`
* `hr01`, `hr02`
* `payroll01`
* `manager01`
* `emp001` → `emp010`

## 12.3 Department seed

* Human Resources
* Accounting
* Operations
* Engineering
* Management

## 12.4 Position seed

* HR Executive
* Payroll Accountant
* Staff
* Team Lead
* Manager

## 12.5 Contract seed

* `HD-2026-001` indefinite
* `HD-2026-002` 12-month
* `TV-2026-003` probation 2-month

## 12.6 Shift seed

* `HC08` — 08:00–17:00
* `HC13` — 13:00–22:00
* `N22` — 22:00–06:00

## 12.7 Holiday seed

* 6–10 ngày nghỉ mẫu
* 1 ngày holiday multiplier = 3.0 để test OT/holiday logic

## 12.8 Attendance seed

* 10 nhân viên × 20 ngày công
* 400–500 raw logs
* 15 anomaly cases:

  * missing check-in
  * missing check-out
  * late > 30 phút
  * early leave
  * overtime
  * holiday work

## 12.9 Request seed

* 5 leave requests
* 5 manual attendance adjustments
* 2 rejected requests
* 3 approved-applied requests

## 12.10 Payroll seed

* 1 period closed
* 1 period previewed
* 1 period draft
* 20 payslips
* 10 bonus/deductions entries

---

# 13) Prompt chi tiết cho UX/UI Designer

Bạn có thể copy nguyên block này:

```text
Bạn là Senior UX/UI Designer cho một hệ thống Web-App enterprise về HRM Salary & Contract Management, trọng tâm là Attendance và Payroll.

Bối cảnh:
- Hệ thống gồm các module: Employee Self-Service, HR, Attendance, Payroll, Reports, Admin.
- Role chính: Employee, HR Staff, Payroll Accountant, Management, System Admin.
- Nền tảng triển khai FE: React + Vite + Tailwind CSS.
- Phong cách mong muốn: enterprise, rõ ràng, data-dense nhưng dễ đọc, thao tác nhanh, ít màu thừa, ưu tiên table, filter panel, status badge, modal xác nhận.
- Thiết kế desktop-first, responsive vừa đủ cho tablet; mobile chỉ ưu tiên Employee self-service.

Mục tiêu UX:
1. Attendance và Payroll phải dễ dùng, ít sai thao tác.
2. Người dùng nhìn vào là hiểu đang ở kỳ nào, trạng thái dữ liệu nào, đã lock hay chưa.
3. Mọi action nguy hiểm như finalize payroll, lock payroll, approve attendance adjustment phải có confirm modal và cảnh báo rõ hậu quả.
4. Role khác nhau phải thấy menu khác nhau; không chỉ disable button mà còn ẩn UI không thuộc quyền.

Thiết kế các màn hình sau:

A. Shared
- Login page
- Change password
- User profile
- Notification center
- Unauthorized / Forbidden page
- 404 / 500 pages

B. Employee
- Dashboard cá nhân: tổng quan công tháng này, số request chờ duyệt, kỳ lương gần nhất
- My Attendance: calendar + list, xem check-in/check-out, late/early, anomaly
- My Shift Calendar
- My Requests: list + create request form
- My Payslips: list + detail + print-friendly
- Attendance Confirmation page

C. HR
- HR Dashboard
- Employee list / employee detail / create/edit employee
- Contract list / detail / create/edit contract
- Shift master list / create/edit
- Shift assignment calendar/table
- Holiday master
- Late/Early rule master
- Attendance log monitor
- Attendance anomaly queue
- Attendance request approval
- Attendance daily detail
- Attendance monthly summary
- Send attendance confirmation batch
- HR report center

D. Payroll
- Payroll Dashboard
- Payroll parameter master
- Bonus / deduction management
- Payroll run wizard gồm 3 bước:
  1. Chọn kỳ và phạm vi
  2. Kiểm tra input parameters
  3. Preview kết quả trước khi finalize
- Payslip list
- Payslip detail
- Lock payroll page
- Payroll report center

E. Management
- Executive dashboard
- Attendance summary dashboard
- Payroll summary dashboard
- Contract expiry dashboard
- Report center with export

F. Admin
- User management
- Role & permission matrix
- Audit logs
- System config
- Integration/backup monitoring

Yêu cầu từng màn hình:
- Có loading state, empty state, error state
- Có filter/search/sort/pagination rõ ràng
- Có status badge nhất quán
- Có breadcrumb
- Có action bar cố định cho các thao tác chính
- Các bảng lớn cần hỗ trợ sticky header, column priority, bulk action nếu hợp lý
- Các form dài cần chia section, có helper text, validation message rõ ràng
- Mọi date/time phải thống nhất format
- Tiền tệ hiển thị rõ dấu phân cách hàng nghìn
- Với Payroll, nhấn mạnh tính chính xác và khả năng trace từng dòng thu nhập/khấu trừ

Thiết kế login:
- Minimal, sạch, enterprise
- Có company branding area
- Input username/password
- Remember me
- Forgot password
- Error message rõ ràng khi sai thông tin
- Sau login, dashboard và sidebar thay đổi theo role

Thiết kế navigation:
- Sidebar trái, topbar trên
- Sidebar phải theo permission
- Topbar có: tên người dùng, role, notification, current period, logout

Thiết kế visual style:
- Màu chủ đạo trung tính, nhấn xanh dương hoặc xanh lá
- Typography rõ, ưu tiên đọc bảng số liệu
- Card bo nhẹ, không quá nhiều shadow
- Data table là thành phần trung tâm
- Modal confirm và drawer filter phải nhất quán

Output mong muốn:
1. Site map tổng thể
2. User flow theo từng role
3. Wireframe low-fi
4. UI design key screens hi-fi
5. Component library cơ bản
6. Design token (color, typography, spacing)
7. Role-based menu mapping
8. File chú thích rõ màn nào role nào thấy / không thấy
```

---

# 14) Quy định product/engineering nên chốt luôn

## 14.1 Quy định về edit dữ liệu

* Raw machine log: không xóa cứng
* Attendance daily đã generate: chỉ regenerate bằng job/SP
* Payslip đã lock: không edit trực tiếp
* Mọi thay đổi sau lock phải qua unlock workflow có audit

## 14.2 Quy định về hiển thị

* UI ẩn theo permission, không chỉ disable
* Menu build từ permission map
* Tất cả report export phải log lại ai export, lúc nào

## 14.3 Quy định về lịch sử

* Contract thay đổi không làm thay đổi payslip cũ
* Salary rule thay đổi không retroactively làm biến động kỳ đã lock
* Attendance request approved phải lưu cả request gốc và result sau apply

---

# 15) Trade-off quan trọng để bạn tự đánh giá

## Hướng A: Tính nhiều ở DB

**Ưu**

* nhanh cho aggregate/report
* hợp brief hiện tại
* phù hợp SQL Server SP

**Nhược**

* logic phân tán giữa BE và DB
* test unit khó hơn
* dev Laravel phải phụ thuộc DB team nhiều

## Hướng B: Tính nhiều ở BE

**Ưu**

* code dễ version control hơn
* dễ test hơn

**Nhược**

* lệch brief hiện tại
* khó tối ưu report/tính công lớn
* mất thời gian viết lại logic vốn phù hợp SP

=> Với bài này, tôi chọn **DB-heavy calculation, BE-heavy orchestration**.

---

# 16) Kết luận chốt thiết kế

Nếu bạn muốn hệ thống **đúng business, dễ test, và ít vỡ sau này**, thì nên khóa theo cấu trúc sau:

* **5 role cố định**: Employee, HR, Payroll, Management, System Admin
* **DB chia 8 nhóm bảng**
* **Attendance + Payroll là lõi**
* **SQL Server giữ calculation & reporting dataset**
* **Backend giữ RBAC, workflow, API, audit**
* **UI hiển thị theo permission thật sự**
* **Payslip và Attendance Summary phải là snapshot**
* **Không cho Admin mặc định sửa dữ liệu nghiệp vụ**

Bước tiếp theo hợp lý nhất là tôi chuyển toàn bộ phần trên thành:
**1 bản BRD / SRS ngắn gọn + 1 bảng permission matrix + 1 danh sách API contract JSON mẫu** để dev FE, dev BE, và designer dùng chung.
