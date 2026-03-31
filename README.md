# Enterprise Payroll ERP

Hệ thống quản lý nhân sự, chấm công, tính lương dành cho doanh nghiệp Việt Nam.

**Stack**: React 19 + Vite 8 + Tailwind 4 | Laravel 11 + Sanctum | SQL Server 2022

## Mục lục

- [Yêu cầu hệ thống](#yêu-cầu-hệ-thống)
- [Cài đặt & Chạy bằng Docker (Khuyến nghị)](#cài-đặt--chạy-bằng-docker-khuyến-nghị)
  - [Bước 1: Clone dự án](#bước-1-clone-dự-án)
  - [Bước 2: Khởi động Docker](#bước-2-khởi-động-docker)
  - [Bước 3: Kiểm tra container đã chạy](#bước-3-kiểm-tra-container-đã-chạy)
  - [Bước 4: Tạo APP_KEY cho Laravel](#bước-4-tạo-app_key-cho-laravel)
  - [Bước 5: Tạo database & seed dữ liệu test](#bước-5-tạo-database--seed-dữ-liệu-test)
  - [Bước 6: Truy cập ứng dụng](#bước-6-truy-cập-ứng-dụng)
- [Cập nhật code để dùng tiếp](#cập-nhật-code-để-dùng-tiếp)
  - [Nếu bạn không sửa gì ở máy](#nếu-bạn-không-sửa-gì-ở-máy)
  - [Nếu bạn đã sửa code ở máy và muốn giữ lại](#nếu-bạn-đã-sửa-code-ở-máy-và-muốn-giữ-lại)
  - [Kiểm tra nhanh](#kiểm-tra-nhanh)
  - [Khi gặp lỗi](#khi-gặp-lỗi)
- [Tài khoản đăng nhập test](#tài-khoản-đăng-nhập-test)
  - [Chế độ có Database (sau khi chạy migrate:fresh --seed)](#chế-độ-có-database-sau-khi-chạy-migratefresh---seed)
  - [Chế độ Mock (khi chưa có DB / DB chưa migrate)](#chế-độ-mock-khi-chưa-có-db--db-chưa-migrate)
- [Dữ liệu test có sẵn (sau khi seed)](#dữ-liệu-test-có-sẵn-sau-khi-seed)
  - [Tổ chức](#tổ-chức)
  - [Hợp đồng](#hợp-đồng)
  - [Chấm công (tháng 02/2026 & 03/2026)](#chấm-công-tháng-022026--032026)
  - [Bảng lương](#bảng-lương)
  - [Người phụ thuộc](#người-phụ-thuộc)
- [Quy tắc giao diện (UI Conventions)](#quy-tắc-giao-diện-ui-conventions)
- [Cấu trúc dự án](#cấu-trúc-dự-án)
- [API Endpoints chính](#api-endpoints-chính)
  - [Authentication](#authentication)
  - [Nhân sự](#nhân-sự)
  - [Chấm công](#chấm-công)
  - [Bảng lương](#bảng-lương-1)
  - [Báo cáo & Quản trị](#báo-cáo--quản-trị)
- [Chạy không dùng Docker (Local)](#chạy-không-dùng-docker-local)
  - [Backend](#backend)
  - [Frontend](#frontend)
- [Các lệnh hữu ích](#các-lệnh-hữu-ích)
- [Thông tin kết nối SQL Server](#thông-tin-kết-nối-sql-server)
- [Xử lý sự cố](#xử-lý-sự-cố)
  - [Backend không khởi động](#backend-không-khởi-động)
  - [SQL Server chưa sẵn sàng](#sql-server-chưa-sẵn-sàng)
  - [Frontend không kết nối được API](#frontend-không-kết-nối-được-api)
  - [Lỗi "port already in use"](#lỗi-port-already-in-use)

---

## Yêu cầu hệ thống

- **Docker Desktop** >= 4.x (bật WSL2 trên Windows)
- **Git**
- RAM tối thiểu 4GB (SQL Server cần ~2GB)



## Cài đặt & Chạy bằng Docker (Khuyến nghị)

### Bước 1: Clone dự án

```bash
git clone <repo-url>
cd enterprise-payroll-erp
```

### Bước 2: Khởi động Docker

Đảm bảo Docker Desktop đang chạy, sau đó:

```bash
docker compose up -d --build
```

Lệnh này sẽ tự động:
- Build backend (Laravel + PHP 8.3 + SQL Server driver)
- Build frontend (Node 22 + React 19)
- Khởi động SQL Server 2022

Thời gian build lần đầu: **5-10 phút** (tải image SQL Server ~1.5GB).

### Bước 3: Kiểm tra container đã chạy

```bash
docker compose ps
```

Kết quả mong đợi:

| Container | Port | Mô tả |
|-----------|------|-------|
| `payroll-backend` | http://localhost:8001 | Laravel API |
| `payroll-frontend` | http://localhost:5173 | React App |
| `payroll-sqlserver` | localhost:1433 | SQL Server |

### Bước 4: Tạo APP_KEY cho Laravel

```bash
docker compose exec backend php artisan key:generate
```

### Bước 5: Tạo database & seed dữ liệu test

```bash
docker compose exec backend php artisan migrate:fresh --seed
```

Lệnh này tạo toàn bộ bảng và nạp dữ liệu test (~15 nhân viên, chấm công, bảng lương).

### Bước 6: Truy cập ứng dụng

- **Frontend**: http://localhost:5173
- **Backend API**: http://localhost:8001/api

---
## Cập nhật code để dùng tiếp

Dùng phần này khi bạn **đã clone dự án trước đó** và chỉ muốn lấy code mới nhất để chạy tiếp.

### Nếu bạn không sửa gì ở máy

```bash
cd enterprise-payroll-erp
git pull origin main
docker compose up -d --build
```

### Nếu bạn đã sửa code ở máy và muốn giữ lại

```bash
cd enterprise-payroll-erp
git add .
git commit -m "save local work"
git pull origin main
docker compose up -d --build
docker compose exec backend php artisan migrate
```

### Kiểm tra nhanh

```bash
git status
docker compose ps
```

- `working tree clean` = local không còn file sửa dở
- Có đủ `payroll-backend`, `payroll-frontend`, `payroll-sqlserver` = hệ thống đang chạy

### Khi gặp lỗi

```bash
docker compose down
docker compose up -d --build
```

Nếu vẫn lỗi sau khi `git pull`, đặc biệt là báo **conflict**, hãy nhờ người phụ trách kỹ thuật hỗ trợ.

---



## Tài khoản đăng nhập test

Tất cả mật khẩu: **`password`**

### Chế độ có Database (sau khi chạy `migrate:fresh --seed`)

| Username | Vai trò | Họ tên | Quyền chính |
|----------|---------|--------|-------------|
| `admin01` | System Admin | Nguyen Van Admin | Toàn quyền hệ thống |
| `hr01` | HR Staff | Tran Thi HR | Quản lý nhân sự, chấm công |
| `hr02` | HR Staff | Le Van HR | Quản lý nhân sự, chấm công |
| `payroll01` | Accountant | Pham Thi Payroll | Tính lương, phiếu lương |
| `manager01` | Management | Hoang Van Manager | Xem báo cáo, duyệt |
| `emp001` - `emp010` | Employee | NV thường | Xem chấm công cá nhân |

### Chế độ Mock (khi chưa có DB / DB chưa migrate)

| Username | Vai trò |
|----------|---------|
| `admin` | system_admin |
| `hr_user` | hr_staff |
| `accountant` | accountant |
| `manager` | management |

> **Lưu ý**: Khi DB có dữ liệu, hệ thống tự động dùng tài khoản DB. Khi DB trống/lỗi, tự động fallback sang mock.

---

## Dữ liệu test có sẵn (sau khi seed)

### Tổ chức
- **8 phòng ban**: Ban Giám đốc, Nhân sự, Kế toán, Vận hành, Kỹ thuật, Kiểm định, Kinh doanh, CNTT
- **15 nhân viên** (NV001-NV015) đầy đủ thông tin CMND, MST, tài khoản ngân hàng

### Hợp đồng
- **15 hợp đồng lao động**: Thu việc, 12 tháng, 24 tháng, không xác định thời hạn
- **6 loại phụ cấp**: Ăn trưa, Xăng xe, Điện thoại, Trách nhiệm, Khu vực, Chuyên môn
- **7 bậc lương**: 8M - 25M VNĐ

### Chấm công (tháng 02/2026 & 03/2026)
- **3 kỳ công**: Tháng 1 (locked), Tháng 2 (confirmed), Tháng 3 (draft)
- **4 ca làm**: Hành chính 08-17h, Chiều 13-22h, Đêm 22-06h, Ca linh hoạt
- **~500 bản ghi chấm công** với 16 trường hợp bất thường:
  - Đi trễ (8-65 phút)
  - Về sớm (10-25 phút)
  - Vắng mặt
  - Quên chấm công vào/ra
  - Log trùng lặp
- **7 đơn từ**: Nghỉ phép, bổ sung công, tăng ca, nghỉ ốm (các trạng thái: draft, pending, approved, applied, rejected)
- **6 ngày lễ**: Tết Nguyên đán, 30/4, 1/5, 2/9, 1/1

### Bảng lương
- **3 đợt chạy lương**: Tháng 1 (locked), Tháng 2 (previewed), Tháng 3 (draft)
- **20 phiếu lương** (10 NV x 2 tháng) với chi tiết:
  - Lương cơ bản, phụ cấp
  - BHXH 8%, BHYT 1.5%, BHTN 1%
  - Thuế TNCN lũy tiến 7 bậc
  - Giảm trừ gia cảnh: bản thân 11M, người phụ thuộc 4.4M
- **10 khoản thưởng/khấu trừ**: Thưởng KPI, thưởng dự án, trừ đi trễ, tạm ứng, phí công đoàn

### Người phụ thuộc
- **10 người phụ thuộc** cho 5 nhân viên (vợ/chồng, con, cha mẹ) - ảnh hưởng giảm trừ thuế

---

## Quy tắc giao diện (UI Conventions)

- **Ngôn ngữ**: Toàn bộ text hiển thị trên UI bằng **tiếng Việt có dấu** (trừ mã code, tên biến)
- **Kiểu chữ**: Sentence case cho labels (không ALL CAPS trong JSX — CSS `uppercase` xử lý nếu cần)
- **Sidebar**: Cấu trúc 3 cấp: Nhóm chức năng → Phân loại (Danh mục/Biến động/Báo cáo) → Trang cụ thể
- **Icons**: Sử dụng `lucide-react`, không dùng Material Symbols
- **Fonts**: Manrope (body), IBM Plex Serif (headings)
- **Trạng thái**: Nháp / Đã tạo / Đã hoàn tất / Đã khóa (không dùng Draft/Generated/Finalized/Locked)

---

## Cấu trúc dự án

```
enterprise-payroll-erp/
├── 📁 UML_des/                          # Sơ đồ thiết kế (Class, Use case, Sequence)
├── 📁 backend/                          # Laravel 11 API
│   ├── app/
│   │   ├── Enums/                       # 7 enums (Status, Role)
│   │   ├── Http/Controllers/Api/        # 8 API controllers
│   │   ├── Http/Middleware/             # Role & Permission middleware
│   │   ├── Http/Requests/               # Form validation requests
│   │   ├── Models/                      # 40+ Eloquent models
│   │   ├── Services/                    # 7 business logic services
│   │   └── Traits/ApiResponse.php       # Unified API response format
│   ├── database/
│   │   ├── migrations/                  # DB schema
│   │   ├── seeders/                     # Test data seeders
│   │   └── sql/                         # Views, functions, stored procedures
│   ├── tests/
│   │   ├── Feature/                     # Integration tests (11 flows)
│   │   └── Unit/                        # Unit tests
│   ├── Dockerfile
│   └── README.md
├── 📁 frontend/                         # React 19 + Vite + Tailwind 4
│   ├── design/                          # Stitch HTML mockups (14 screens)
│   ├── src/
│   │   ├── components/                  # Shared UI components
│   │   ├── pages/                       # 14 route pages
│   │   ├── lib/                         # API client, formatters, helpers
│   │   ├── context/                     # Auth state
│   │   └── layouts/                     # AppLayout wrapper
│   ├── Dockerfile
│   └── package.json
├── 📁 docs/                             # Documentation
├── docker-compose.yml                   # Full stack orchestration
└── README.md                            # This file
```

**Key files:**
- `backend/routes/api.php` — All 43 API endpoints
- `frontend/src/App.tsx` — Router & auth flow
- `docker-compose.yml` — Quick start (all services in one command)


## API Endpoints chính

Base URL: `http://localhost:8001/api`

### Authentication
```
POST   /api/auth/login          # Đăng nhập
POST   /api/auth/logout         # Đăng xuất
GET    /api/me                  # Thông tin user hiện tại
GET    /api/me/permissions      # Quyền hạn user
```

### Nhân sự
```
GET    /api/employees           # Danh sách nhân viên
GET    /api/employees/{id}      # Chi tiết nhân viên
GET    /api/employees/{id}/active-contract    # Hợp đồng hiện tại
GET    /api/employees/{id}/dependents         # Người phụ thuộc
```

### Chấm công
```
GET    /api/attendance/checkin-logs      # Log chấm công
POST   /api/attendance/checkin-logs/manual  # Chấm công thủ công
GET    /api/attendance/daily             # Chấm công theo ngày
GET    /api/attendance/monthly-summary   # Tổng hợp tháng
POST   /api/attendance/recalculate       # Tính lại chấm công
GET    /api/attendance/requests          # Danh sách đơn từ
POST   /api/attendance/requests          # Tạo đơn từ
POST   /api/attendance/requests/{id}/approve  # Duyệt đơn
POST   /api/attendance/requests/{id}/reject   # Từ chối đơn
```

### Bảng lương
```
GET    /api/payroll/periods              # Danh sách kỳ lương
POST   /api/payroll/periods/open         # Mở kỳ lương mới
GET    /api/payroll/runs/preview-parameters  # Tham số tính lương
POST   /api/payroll/runs/preview         # Xem trước bảng lương
GET    /api/payroll/runs/{runId}         # Chi tiết đợt chạy lương
POST   /api/payroll/runs/{runId}/finalize  # Duyệt bảng lương
POST   /api/payroll/runs/{runId}/lock    # Khóa bảng lương
GET    /api/payroll/payslips             # Danh sách phiếu lương
GET    /api/payroll/payslips/{id}        # Chi tiết phiếu lương
GET    /api/payroll/payslips/{id}/details  # Các dòng phiếu lương
POST   /api/payroll/adjustments          # Tạo điều chỉnh lương
```

### Báo cáo & Quản trị
```
GET    /api/reports/templates            # Mẫu báo cáo
POST   /api/reports/{code}/preview       # Xem trước báo cáo
POST   /api/reports/{code}/export        # Xuất báo cáo
GET    /api/users                        # Quản lý users (Admin)
POST   /api/users                        # Tạo user mới
GET    /api/roles                        # Danh sách vai trò
```

---

## Chạy không dùng Docker (Local)

### Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate

# Nếu có SQL Server local:
# Sửa .env: DB_PASSWORD=YourStrong!Passw0rd
# php artisan migrate:fresh --seed

php artisan serve    # http://localhost:8000
```

### Frontend

```bash
cd frontend
npm install
npm run dev          # http://localhost:5173
```

> **Lưu ý**: Khi chạy local backend ở port 8000, frontend tự kết nối đúng. Khi chạy Docker, frontend dùng port 8001.

---

## Các lệnh hữu ích

```bash
# Xem log backend
docker compose logs -f backend

# Truy cập Laravel Tinker (REPL)
docker compose exec backend php artisan tinker

# Kiểm tra routes
docker compose exec backend php artisan route:list --path=api

# Reset toàn bộ dữ liệu
docker compose exec backend php artisan migrate:fresh --seed

# Chạy tests
docker compose exec backend composer test:sqlite

# Dừng tất cả
docker compose down

# Dừng và xóa dữ liệu DB
docker compose down -v
```

---

## Thông tin kết nối SQL Server

| Thông số | Giá trị |
|----------|---------|
| Host | `localhost` |
| Port | `1433` |
| Database | `enterprise_payroll_erp` |
| Username | `sa` |
| Password | `YourStrong!Passw0rd` |

Có thể dùng **Azure Data Studio**, **DBeaver**, hoặc **SSMS** để kết nối trực tiếp.

---

## Xử lý sự cố

### Backend không khởi động
```bash
docker compose logs backend    # Xem lỗi
docker compose exec backend php artisan config:clear
docker compose restart backend
```

### SQL Server chưa sẵn sàng
SQL Server cần 20-30s để khởi động. Backend sẽ tự retry nhờ `depends_on: condition: service_healthy`.

### Frontend không kết nối được API
- Kiểm tra backend đang chạy: `curl http://localhost:8001/api/auth/login -X POST`
- Kiểm tra biến môi trường: `VITE_API_BASE_URL=http://localhost:8001/api`

### Lỗi "port already in use"
```bash
# Kiểm tra port đang dùng
netstat -an | findstr :8001
netstat -an | findstr :5173
netstat -an | findstr :1433

# Dừng container cũ
docker compose down
```
