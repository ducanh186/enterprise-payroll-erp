# Hướng dẫn chạy dự án cho Developer

## Yêu cầu hệ thống

| Công cụ | Phiên bản tối thiểu | Ghi chú |
|---------|---------------------|---------|
| PHP | 8.3+ | Cần extensions: mbstring, zip, bcmath, openssl |
| Composer | 2.x | https://getcomposer.org |
| Node.js | 20+ (khuyến nghị 22) | https://nodejs.org |
| npm | 10+ | Đi kèm Node.js |
| Git | 2.x | |
| Docker (tuỳ chọn) | 24+ | Nếu muốn chạy bằng Docker |

> **Auth mode (quan trọng)**: Backend đang dùng **Sanctum Bearer token (stateless)** cho API. Luồng login gọi trực tiếp `POST /api/auth/login` và **không dùng** SPA cookie CSRF flow (`/sanctum/csrf-cookie`, `XSRF-TOKEN`).
> **Database**: SQL Server 2022 chạy trong Docker, đã có đầy đủ schema (41 migrations) và seed data.

---

## Cách 1: Chạy trực tiếp (không Docker)

### Bước 1 — Clone repo

```bash
git clone <repo-url> enterprise-payroll-erp
cd enterprise-payroll-erp
```

### Bước 2 — Cài đặt Backend

```bash
cd backend

# Cài dependencies
composer install

# Tạo file .env từ template
cp .env.example .env

# Sinh APP_KEY
php artisan key:generate

# Kiểm tra routes
php artisan route:list --path=api
```

### Bước 3 — Cài đặt Frontend

```bash
cd ../frontend

# Cài dependencies
npm install
```

### Bước 4 — Chạy cả 2 server

Mở **2 terminal riêng biệt**:

**Terminal 1 — Backend** (http://localhost:8000):
```bash
cd backend
php artisan serve
```

**Terminal 2 — Frontend** (http://localhost:5173):
```bash
cd frontend
npm run dev
```

### Bước 5 — Kiểm tra

Mở trình duyệt: http://localhost:5173

Test API bằng curl hoặc Postman:
```bash
# Đăng nhập (local port 8000)
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin01","password":"password"}'

# Các request tiếp theo cần Bearer token
TOKEN="<token_từ_response_login>"

curl http://localhost:8000/api/employees \
  -H "Authorization: Bearer $TOKEN"

curl "http://localhost:8000/api/attendance/monthly-summary?month=2&year=2026" \
  -H "Authorization: Bearer $TOKEN"

curl http://localhost:8000/api/payroll/periods \
  -H "Authorization: Bearer $TOKEN"
```

---

## Cách 2: Chạy bằng Docker (khuyến nghị)

### Bước 1 — Chuẩn bị

```bash
cd enterprise-payroll-erp

# (Tuỳ chọn) Đổi mật khẩu SQL Server
cp .env.example .env
# Sửa DB_PASSWORD trong file .env nếu muốn
```

### Bước 2 — Build và chạy

```bash
docker compose up -d --build
```

Lần đầu sẽ mất 3-5 phút để build images. Sau đó:

| Service | URL | Container |
|---------|-----|-----------|
| Frontend | http://localhost:5173 | payroll-frontend |
| Backend API | http://localhost:8001 | payroll-backend |
| SQL Server | localhost:1433 | payroll-sqlserver |

> **Lưu ý port**: Backend map ra **port 8001** (host) → 8000 (container) để tránh conflict.

### Bước 3 — Sinh APP_KEY (chỉ lần đầu)

```bash
docker exec payroll-backend php artisan key:generate
```

### Các lệnh Docker thường dùng

```bash
# Xem logs
docker compose logs -f backend
docker compose logs -f frontend

# Restart 1 service
docker compose restart backend

# Dừng tất cả
docker compose down

# Dừng và xoá volume DB (reset data)
docker compose down -v

# Vào shell container
docker exec -it payroll-backend bash
docker exec -it payroll-frontend sh
```

---

## Tài khoản đăng nhập

Tất cả tài khoản đều dùng password: **`password`**

### Tài khoản quản lý (5 users)

| Username | Tên | Vai trò | Quyền chính |
|----------|-----|---------|-------------|
| `admin01` | Nguyen Van Admin | system_admin | Toàn quyền hệ thống, quản lý user/role |
| `hr01` | Tran Thi HR | hr_staff | Quản lý nhân viên, hợp đồng, chấm công, duyệt đơn |
| `hr02` | Le Van HR | hr_staff | Tương tự hr01 |
| `payroll01` | Pham Thi Payroll | accountant | Tham số lương, preview/finalize/lock payroll |
| `manager01` | Hoang Van Manager | management | Xem báo cáo, dashboard (read-only) |

### Tài khoản nhân viên (10 users)

| Username | Tên | Vai trò |
|----------|-----|---------|
| `emp001` | Vo Thi Mai | employee |
| `emp002` | Dang Van Tuan | employee |
| `emp003` | Bui Thi Lan | employee |
| `emp004` | Do Van Hung | employee |
| `emp005` | Ngo Thi Huong | employee |
| `emp006` | Nguyen Van Thanh | employee |
| `emp007` | Tran Van Duc | employee |
| `emp008` | Le Thi Ngoc | employee |
| `emp009` | Pham Van Long | employee |
| `emp010` | Hoang Thi Thao | employee |

> **Lưu ý**: Mỗi user quản lý cũng có role `employee` (để tự xem công/lương cá nhân).

### Ví dụ đăng nhập qua API

```bash
# Docker (port 8001)
curl -X POST http://localhost:8001/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin01","password":"password"}'

# Local artisan serve (port 8000)
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"hr01","password":"password"}'
```

Response trả về `token` — dùng làm Bearer token cho các request tiếp theo:
```bash
curl http://localhost:8001/api/me \
  -H "Authorization: Bearer <token_từ_login>"
```

---

## Danh sách API Endpoints

### Auth
| Method | Endpoint | Mô tả |
|--------|----------|-------|
| POST | `/api/auth/login` | Đăng nhập |
| POST | `/api/auth/logout` | Đăng xuất |
| GET | `/api/me` | Thông tin user hiện tại |
| GET | `/api/me/permissions` | Danh sách quyền |

### Dữ liệu danh mục (Reference)
| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/reference/shifts` | Ca làm việc |
| GET | `/api/reference/holidays` | Ngày lễ |
| GET | `/api/reference/contract-types` | Loại hợp đồng |
| GET | `/api/reference/payroll-types` | Loại bảng lương |
| GET | `/api/reference/payroll-parameters` | Tham số lương |
| GET | `/api/reference/late-early-rules` | Quy định đi muộn/về sớm |
| GET | `/api/reference/departments` | Phòng ban |

### Nhân viên (Employee)
| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/employees` | Danh sách NV (filter: keyword, department_id, active_status) |
| GET | `/api/employees/{id}` | Chi tiết NV |
| GET | `/api/employees/{id}/active-contract` | Hợp đồng đang hiệu lực |
| GET | `/api/employees/{id}/dependents` | Người phụ thuộc |

### Hợp đồng (Contract)
| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/contracts` | Danh sách hợp đồng |
| GET | `/api/contracts/{id}` | Chi tiết hợp đồng |

### Chấm công (Attendance) — Ưu tiên cao
| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/attendance/checkin-logs` | Log chấm công (filter: date_from, date_to, employee_id, machine_number, is_valid) |
| POST | `/api/attendance/checkin-logs/manual` | Thêm chấm công thủ công |
| GET | `/api/attendance/daily` | Chấm công theo ngày |
| GET | `/api/attendance/monthly-summary` | Tổng hợp công tháng (filter: month, year, department_id, employee_id) |
| POST | `/api/attendance/recalculate` | Tính lại công |
| GET | `/api/attendance/requests` | Danh sách đơn (nghỉ phép, điều chỉnh) |
| POST | `/api/attendance/requests` | Tạo đơn mới |
| GET | `/api/attendance/requests/{id}` | Chi tiết đơn |
| POST | `/api/attendance/requests/{id}/approve` | Duyệt đơn |
| POST | `/api/attendance/requests/{id}/reject` | Từ chối đơn |

### Tiền lương (Payroll) — Ưu tiên cao
| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/payroll/periods` | Danh sách kỳ lương |
| POST | `/api/payroll/periods/open` | Mở kỳ lương mới |
| GET | `/api/payroll/runs/preview-parameters` | Tham số cần nhập trước khi tính |
| POST | `/api/payroll/runs/preview` | Preview tính lương |
| GET | `/api/payroll/runs/{runId}` | Xem kết quả chạy lương |
| POST | `/api/payroll/runs/{runId}/finalize` | Chốt bảng lương |
| POST | `/api/payroll/runs/{runId}/lock` | Khoá bảng lương |
| GET | `/api/payroll/payslips` | Danh sách phiếu lương (filter: month, year, employee_id, department_id, status) |
| GET | `/api/payroll/payslips/{id}` | Chi tiết phiếu lương |
| GET | `/api/payroll/payslips/{id}/details` | Bảng kê chi tiết lương |
| POST | `/api/payroll/adjustments` | Thêm điều chỉnh (thưởng/trừ) |
| PUT | `/api/payroll/adjustments/{id}` | Sửa điều chỉnh |
| DELETE | `/api/payroll/adjustments/{id}` | Xoá điều chỉnh |

### Báo cáo (Reports)
| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/reports/templates` | Danh sách mẫu báo cáo |
| POST | `/api/reports/{code}/preview` | Xem trước báo cáo |
| POST | `/api/reports/{code}/export` | Xuất báo cáo (Excel/PDF) |

### Quản trị (Admin)
| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/users` | Danh sách tài khoản |
| POST | `/api/users` | Tạo tài khoản |
| PUT | `/api/users/{id}` | Sửa tài khoản |
| POST | `/api/users/{id}/reset-password` | Reset mật khẩu |
| GET | `/api/roles` | Danh sách vai trò |
| POST | `/api/users/{id}/roles` | Gán vai trò |

---

## Cấu trúc response chuẩn

**Thành công:**
```json
{
  "success": true,
  "data": { ... },
  "message": "OK",
  "errors": null
}
```

**Thành công (có phân trang):**
```json
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 100,
    "last_page": 5
  }
}
```

**Lỗi:**
```json
{
  "success": false,
  "data": null,
  "message": "Validation failed",
  "errors": {
    "field_name": ["Lỗi cụ thể"]
  }
}
```

---

## Luồng nghiệp vụ tính lương

```
1. Mở kỳ lương     POST /api/payroll/periods/open
       ↓
2. Xem tham số     GET  /api/payroll/runs/preview-parameters
       ↓
3. Preview lương    POST /api/payroll/runs/preview        → status: "previewed"
       ↓
4. Điều chỉnh      POST /api/payroll/adjustments          (thưởng/trừ nếu cần)
       ↓
5. Chốt lương      POST /api/payroll/runs/{id}/finalize   → status: "finalized"
       ↓
6. Khoá lương      POST /api/payroll/runs/{id}/lock       → status: "locked" (không sửa được nữa)
       ↓
7. Xem phiếu lương GET  /api/payroll/payslips/{id}/details
```

**State machine**: `draft` → `previewed` → `finalized` → `locked` (một chiều, không quay lại)

---

## Cấu trúc code Backend

```
backend/app/
├── Enums/                  # PayrollRunStatus, AttendanceRequestStatus, UserRole
├── Http/
│   ├── Controllers/Api/    # 8 controllers (1 per module)
│   ├── Middleware/          # CheckRole, CheckPermission
│   └── Requests/           # FormRequest validation classes
├── Services/               # 7 services (mock data, business logic)
└── Traits/
    └── ApiResponse.php     # Standard JSON response helpers
```

**Quy tắc khi thêm endpoint mới:**
1. Thêm route trong `routes/api.php`
2. Thêm method trong Controller tương ứng
3. Thêm logic trong Service tương ứng
4. Nếu cần validate input, tạo FormRequest trong `app/Http/Requests/`

---

## Cấu trúc code Frontend

```
frontend/src/
├── components/     # Sidebar, Topbar, ProtectedRoute, PlaceholderPage
├── context/        # AuthContext (mock auth)
├── layouts/        # AppLayout (sidebar + topbar + outlet)
├── pages/          # LoginPage, DashboardPage
└── lib/
    ├── api.ts      # Axios instance (auto-attach Bearer token)
    └── query.ts    # React Query client
```

**Quy tắc khi thêm page mới:**
1. Tạo component trong `src/pages/`
2. Thêm route trong `src/App.tsx`
3. Thêm link vào `src/components/Sidebar.tsx`
4. Dùng `api` từ `lib/api.ts` để gọi backend

---

## Dữ liệu seed có sẵn

Khi chạy Docker hoặc `php artisan migrate:fresh --seed`, hệ thống tự tạo data sau:

| Loại dữ liệu | Số lượng | Chi tiết |
|---------------|----------|----------|
| Users | 15 | 5 quản lý + 10 nhân viên |
| Employees | 15 | Gắn với users, có phòng ban/chức vụ |
| Departments | 8 | HR, Accounting, Operations, Engineering, ... |
| Labour Contracts | 15 | Mỗi NV 1 hợp đồng (có allowances, salary levels) |
| Shifts | 4 | HC08 (8h-17h), HC04, N22 (ca đêm), ... |
| Holidays | 12 | Tết, 30/4, 1/5, 2/9, ... |
| Shift Assignments | 510 | Phân ca cho NV trong 3 tháng |
| Time Logs | 1015 | Dữ liệu chấm công máy + manual |
| Attendance Daily | 285 | Chấm công theo ngày |
| Attendance Monthly Summary | 15 | Tổng hợp công tháng |
| Attendance Periods | 3 | T1/2026 (locked), T2/2026 (confirmed), T3/2026 (draft) |
| Attendance Requests | 7 | Nghỉ phép, điều chỉnh, OT (các trạng thái khác nhau) |
| Payroll Runs | 3 | T1 (locked, 10 payslips), T2 (previewed, 10 payslips), T3 (draft) |
| Payslips | 20 | Phiếu lương chi tiết với 192 items |
| Bonus/Deductions | 10 | Thưởng KPI, phụ cấp, khấu trừ |
| Report Templates | 6 | Attendance, Payroll, Payslip, Labor Cost, ... |
| System Configs | 14 | Ngày công chuẩn, tỷ lệ OT, ... |

### Trạng thái dữ liệu theo kỳ

| Kỳ | Attendance Period | Payroll Run | Payslips |
|----|-------------------|-------------|----------|
| 2026-01 | `locked` | `locked` | 10 phiếu (chốt xong) |
| 2026-02 | `confirmed` | `previewed` | 10 phiếu (đang preview) |
| 2026-03 | `draft` | `draft` | 0 (chưa chạy) |

### Đơn chấm công mẫu

| # | Nhân viên | Loại | Trạng thái |
|---|-----------|------|-----------|
| 1 | Dang Van Tuan | annual_leave | approved |
| 2 | Ngo Thi Huong | annual_leave | approved |
| 3 | Bui Thi Lan | correction | applied |
| 4 | Nguyen Van Thanh | correction | pending |
| 5 | Pham Van Long | overtime | approved |
| 6 | Vo Thi Mai | sick_leave | draft |
| 7 | Tran Van Duc | correction | rejected |

---

## Chạy Tests

### Unit & Feature Tests (SQLite in-memory)

Tests chạy trên SQLite `:memory:`, **không cần SQL Server**. Mỗi test tự migrate + seed.

```bash
cd backend

# Tất cả tests
composer test

# Chỉ Feature tests (bao gồm flow tests)
composer test:feature

# Chỉ từng nhóm
composer test:auth        # Auth login/logout/token
composer test:payroll     # Payroll preview→finalize→lock
composer test:services    # Service-level tests
composer test:unit        # Unit tests
```

### Danh sách test files

| File | Tests | Mô tả |
|------|-------|-------|
| `AuthApiTest` | 3 | Login, logout, token validation |
| `PayrollApiTest` | 1 | Full payroll flow: preview→finalize→lock→payslip |
| `ServiceBackendGroup1Test` | 3 | Contract detail, active contract, reference data |
| `ServiceBackendGroup2Test` | 3 | Report templates, checkin logs, report preview |
| `RbacFlowTest` | 7 | Login từng role, access control, permissions endpoint |
| `AdminFlowTest` | 7 | CRUD users, reset password, assign roles, validation |
| `AttendanceFlowTest` | 8 | Checkin logs, manual checkin, daily/monthly, recalculate |
| `AttendanceRequestFlowTest` | 6 | Create/approve/reject đơn, filter, detail |
| `EmployeeContractFlowTest` | 8 | List/detail employees, contracts, dependents, 404 |
| `ReportFlowTest` | 7 | Report templates, preview, export, reference endpoints |
| **Tổng** | **54 tests, 562 assertions** | |

### Test nhanh API bằng curl (Docker)

```bash
# 1. Login lấy token
TOKEN=$(curl -s -X POST http://localhost:8001/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin01","password":"password"}' | grep -o '"token":"[^"]*' | cut -d'"' -f4)

echo "Token: $TOKEN"

# 2. Xem thông tin user
curl -s http://localhost:8001/api/me \
  -H "Authorization: Bearer $TOKEN" | python -m json.tool

# 3. Danh sách nhân viên
curl -s http://localhost:8001/api/employees \
  -H "Authorization: Bearer $TOKEN" | python -m json.tool

# 4. Tổng hợp chấm công tháng 2
curl -s "http://localhost:8001/api/attendance/monthly-summary?month=2&year=2026" \
  -H "Authorization: Bearer $TOKEN" | python -m json.tool

# 5. Danh sách kỳ lương
curl -s http://localhost:8001/api/payroll/periods \
  -H "Authorization: Bearer $TOKEN" | python -m json.tool

# 6. Preview tính lương tháng 3
curl -s -X POST http://localhost:8001/api/payroll/runs/preview \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"month":3,"year":2026,"scope":"all"}' | python -m json.tool

# 7. Danh sách phiếu lương
curl -s http://localhost:8001/api/payroll/payslips \
  -H "Authorization: Bearer $TOKEN" | python -m json.tool

# 8. Báo cáo
curl -s http://localhost:8001/api/reports/templates \
  -H "Authorization: Bearer $TOKEN" | python -m json.tool
```

---

## Ghi chú quan trọng

- **Database**: SQL Server 2022 trong Docker, đã có đầy đủ 41 tables + seed data. Backend Services dùng Eloquent models truy vấn DB thật.
- **Khi cần thêm logic DB**: Thêm views/functions/stored procedures vào `database/sql/`, sửa Services trong `backend/app/Services/`.
- **Không dùng generic SP executor**: Mỗi endpoint gọi đúng SP/view cụ thể thông qua Service layer, không expose tên SP ra FE.
- **CORS**: BE đã config sẵn cho FE ở `localhost:5173`. Nếu đổi port FE, cần sửa `backend/config/cors.php` và `SANCTUM_STATEFUL_DOMAINS` trong `.env`.
- **Reset data**: Chạy `docker compose exec backend php artisan migrate:fresh --seed` để reset toàn bộ DB về trạng thái ban đầu.
