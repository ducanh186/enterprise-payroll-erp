# Test Cases - Enterprise Payroll ERP (MVP)

Tài liệu test case cho khách hàng tự kiểm thử các chức năng MVP.

**Môi trường**: Docker (`docker compose up -d --build` + `migrate:fresh --seed`)
**Frontend**: http://localhost:5173
**API**: http://localhost:8001/api

---

## Quy ước

- **[UI]** = Test trên giao diện web (Frontend)
- **[API]** = Test bằng cURL / Postman / Thunder Client
- Trạng thái: ⬜ Chưa test | ✅ Pass | ❌ Fail

---

## 1. Đăng nhập / Xác thực

### TC-1.1: Đăng nhập thành công với System Admin
- **[UI]** Mở http://localhost:5173/login
- Nhập `admin01` / `password`
- Nhấn Đăng nhập
- **Kết quả mong đợi**: Chuyển đến trang Dashboard, hiển thị tên "Nguyen Van Admin"
- ⬜

### TC-1.2: Đăng nhập thành công với HR Staff
- **[UI]** Đăng nhập với `hr01` / `password`
- **Kết quả mong đợi**: Vào Dashboard, menu sidebar hiển thị các mục Nhân sự, Chấm công
- ⬜

### TC-1.3: Đăng nhập thành công với Accountant
- **[UI]** Đăng nhập với `payroll01` / `password`
- **Kết quả mong đợi**: Vào Dashboard, menu sidebar hiển thị mục Bảng lương
- ⬜

### TC-1.4: Đăng nhập thành công với Manager
- **[UI]** Đăng nhập với `manager01` / `password`
- **Kết quả mong đợi**: Vào Dashboard, quyền xem báo cáo
- ⬜

### TC-1.5: Đăng nhập thất bại - sai mật khẩu
- **[UI]** Đăng nhập với `admin01` / `wrongpassword`
- **Kết quả mong đợi**: Hiển thị lỗi "Sai tên đăng nhập hoặc mật khẩu"
- ⬜

### TC-1.6: Đăng nhập thất bại - tài khoản không tồn tại
- **[UI]** Đăng nhập với `notexist` / `password`
- **Kết quả mong đợi**: Hiển thị lỗi đăng nhập
- ⬜

### TC-1.7: Đăng nhập qua API
- **[API]**
```bash
curl -X POST http://localhost:8001/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin01","password":"password"}'
```
- **Kết quả mong đợi**: Response chứa `token`, `user.role = "system_admin"`
- ⬜

### TC-1.8: Đăng xuất
- **[UI]** Sau khi đăng nhập, nhấn nút đăng xuất (avatar/menu góc phải)
- **Kết quả mong đợi**: Quay về trang Login, token bị xóa
- ⬜

### TC-1.9: Truy cập không có token
- **[API]**
```bash
curl http://localhost:8001/api/me
```
- **Kết quả mong đợi**: HTTP 401 Unauthorized
- ⬜

---

## 2. Danh sách nhân viên

> Đăng nhập bằng `admin01` hoặc `hr01` để test module này.

### TC-2.1: Xem danh sách nhân viên
- **[UI]** Vào menu Nhân sự > Danh sách nhân viên
- **Kết quả mong đợi**: Hiển thị 15 nhân viên (NV001-NV015)
- ⬜

### TC-2.2: Xem danh sách qua API
- **[API]**
```bash
curl http://localhost:8001/api/employees \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: `success: true`, danh sách 15 nhân viên có `employee_code`, `full_name`, `department`
- ⬜

### TC-2.3: Xem chi tiết nhân viên NV001
- **[API]**
```bash
curl http://localhost:8001/api/employees/1 \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: Thông tin đầy đủ: Nguyen Van Admin, NV001, Phòng CNTT
- ⬜

### TC-2.4: Xem hợp đồng hiện tại
- **[API]**
```bash
curl http://localhost:8001/api/employees/1/active-contract \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: Hợp đồng HD-2020-001, lương cơ bản 25,000,000 VNĐ
- ⬜

### TC-2.5: Xem người phụ thuộc
- **[API]**
```bash
curl http://localhost:8001/api/employees/1/dependents \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: 2 người phụ thuộc (vợ: Tran Thi Hoa, con: Nguyen Minh Anh)
- ⬜

### TC-2.6: Xem nhân viên không có người phụ thuộc
- **[API]**
```bash
curl http://localhost:8001/api/employees/6/dependents \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: Danh sách rỗng `data: []`
- ⬜

---

## 3. Hợp đồng lao động

### TC-3.1: Danh sách hợp đồng
- **[API]**
```bash
curl http://localhost:8001/api/contracts \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: 15 hợp đồng, bao gồm các loại: thử việc, 12 tháng, 24 tháng, không thời hạn
- ⬜

### TC-3.2: Chi tiết hợp đồng thử việc (NV012)
- **[API]**
```bash
curl http://localhost:8001/api/contracts/12 \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: Loại "Thu Viec", probation_rate = 85%, lương 8,000,000
- ⬜

### TC-3.3: Chi tiết hợp đồng không thời hạn (NV001)
- **[API]**
```bash
curl http://localhost:8001/api/contracts/1 \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: Loại "KDXDTH", end_date = null, lương 25,000,000
- ⬜

---

## 4. Dữ liệu tham chiếu (Reference)

### TC-4.1: Danh sách phòng ban
- **[API]**
```bash
curl http://localhost:8001/api/reference/departments \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: 8 phòng ban
- ⬜

### TC-4.2: Danh sách ca làm việc
- **[API]**
```bash
curl http://localhost:8001/api/reference/shifts \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: 4 ca (HC08 08-17h, HC13 13-22h, N22 22-06h, LINH_HOAT)
- ⬜

### TC-4.3: Danh sách ngày lễ
- **[API]**
```bash
curl http://localhost:8001/api/reference/holidays \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: 6 ngày lễ (Tết, 30/4, 1/5, Quốc khánh, Tết dương)
- ⬜

### TC-4.4: Loại hợp đồng
- **[API]**
```bash
curl http://localhost:8001/api/reference/contract-types \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: 5 loại (Thử việc, 12 tháng, 24 tháng, 36 tháng, Không thời hạn)
- ⬜

### TC-4.5: Tham số lương
- **[API]**
```bash
curl http://localhost:8001/api/reference/payroll-parameters \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: 5 bộ tham số (tỷ lệ BH, thuế TNCN, giảm trừ gia cảnh, lương tối thiểu, trần BH)
- ⬜

---

## 5. Chấm công

> Đăng nhập bằng `admin01` hoặc `hr01`.

### TC-5.1: Xem log chấm công
- **[API]**
```bash
curl "http://localhost:8001/api/attendance/checkin-logs?period_id=2" \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: Hàng trăm bản ghi chấm công tháng 02/2026, bao gồm cả log bất thường
- ⬜

### TC-5.2: Xem chấm công theo ngày
- **[API]**
```bash
curl "http://localhost:8001/api/attendance/daily?period_id=2" \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: Danh sách chấm công daily cho 15 NV, tháng 02/2026
- Kiểm tra có record trạng thái: `present`, `absent`, `partial`, `anomaly`
- ⬜

### TC-5.3: Kiểm tra NV đi trễ 15 phút (NV006 - emp001)
- **[API]** Xem attendance daily cho employee_id = 6, tháng 02/2026
- **Kết quả mong đợi**: Có ít nhất 1 ngày `late_minutes = 15`, status = `present`
- ⬜

### TC-5.4: Kiểm tra NV vắng mặt (NV007 - emp002)
- **[API]** Xem attendance daily cho employee_id = 7
- **Kết quả mong đợi**: Có 1 ngày `attendance_status = absent`, `workday_value = 0`
- ⬜

### TC-5.5: Kiểm tra NV quên chấm công ra (NV008 - emp003)
- **[API]** Xem attendance daily cho employee_id = 8
- **Kết quả mong đợi**: Có 1 ngày `attendance_status = anomaly`, `source_status = missing_checkout`
- ⬜

### TC-5.6: Tổng hợp chấm công tháng
- **[API]**
```bash
curl "http://localhost:8001/api/attendance/monthly-summary?period_id=2" \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: 15 bản ghi tổng hợp, mỗi NV có: total_workdays, regular_hours, late_minutes, meal_count
- ⬜

### TC-5.7: Xem danh sách đơn từ
- **[API]**
```bash
curl http://localhost:8001/api/attendance/requests \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: 7 đơn từ các loại:
  - 2 đơn nghỉ phép (approved)
  - 2 đơn bổ sung công (1 applied, 1 pending)
  - 1 đơn tăng ca (approved)
  - 1 đơn nghỉ ốm (draft)
  - 1 đơn bổ sung bị từ chối (rejected)
- ⬜

### TC-5.8: Xem chi tiết đơn từ
- **[API]**
```bash
curl http://localhost:8001/api/attendance/requests/1 \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: Đơn nghỉ phép của NV007 (emp002), status = approved
- ⬜

---

## 6. Bảng lương

> Đăng nhập bằng `payroll01` (accountant) hoặc `admin01`.

### TC-6.1: Xem danh sách kỳ lương
- **[API]**
```bash
curl http://localhost:8001/api/payroll/periods \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: 3 kỳ:
  - 2026-01: locked
  - 2026-02: confirmed
  - 2026-03: draft
- ⬜

### TC-6.2: Xem tham số tính lương
- **[API]**
```bash
curl http://localhost:8001/api/payroll/runs/preview-parameters \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: Tham số BH (BHXH 8%, BHYT 1.5%, BHTN 1%), thuế TNCN, giảm trừ
- ⬜

### TC-6.3: Xem đợt chạy lương tháng 01 (locked)
- **[API]**
```bash
curl http://localhost:8001/api/payroll/runs/1 \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: status = `locked`, có locked_at, locked_by
- ⬜

### TC-6.4: Xem đợt chạy lương tháng 02 (previewed)
- **[API]**
```bash
curl http://localhost:8001/api/payroll/runs/2 \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: status = `previewed`, chưa finalize
- ⬜

### TC-6.5: Danh sách phiếu lương
- **[API]**
```bash
curl http://localhost:8001/api/payroll/payslips \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: 20 phiếu lương (10 NV x 2 tháng)
- ⬜

### TC-6.6: Chi tiết phiếu lương NV006 (emp001) - Lương 10M
- **[API]**
```bash
curl http://localhost:8001/api/payroll/payslips/1 \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**:
  - base_salary = 10,000,000
  - gross = base + phụ cấp (730K ăn + 500K xe) + thưởng (nếu có)
  - BHXH = 800,000 (8%)
  - BHYT = 150,000 (1.5%)
  - BHTN = 100,000 (1%)
  - Có tính thuế TNCN
- ⬜

### TC-6.7: Chi tiết dòng phiếu lương
- **[API]**
```bash
curl http://localhost:8001/api/payroll/payslips/1/details \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: Các dòng chi tiết:
  - earning: BASE_SALARY, ALW_MEAL, ALW_TRANSPORT
  - deduction: INS_BHXH, INS_BHYT, INS_BHTN, PIT
  - employer: INS_ER_BHXH, INS_ER_BHYT, INS_ER_BHTN
- ⬜

### TC-6.8: Phiếu lương NV thử việc (NV012 - emp007)
- **[API]** Xem payslip của employee_id = 12
- **Kết quả mong đợi**: base_salary_snapshot = 6,800,000 (= 8M x 85%)
- ⬜

### TC-6.9: Phiếu lương NV có thưởng (NV001, tháng 02)
- **[API]** Xem payslip employee_id = 1, period 2
- **Kết quả mong đợi**: bonus_total = 3,000,000 (Thưởng KPI quý 4/2025)
- ⬜

### TC-6.10: Phiếu lương NV có khấu trừ (NV007 - emp002, tháng 02)
- **[API]** Xem payslip employee_id = 7, period 2
- **Kết quả mong đợi**: deduction_total = 3,000,000 (Trừ tạm ứng tháng 01/2026)
- ⬜

### TC-6.11: State machine - Finalize run
- **[API]** (Đăng nhập payroll01)
```bash
curl -X POST http://localhost:8001/api/payroll/runs/2/finalize \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json"
```
- **Kết quả mong đợi**: Run 2 chuyển từ `previewed` → `finalized`
- ⬜

### TC-6.12: State machine - Lock run
- **[API]** (Đăng nhập admin01, sau khi finalize)
```bash
curl -X POST http://localhost:8001/api/payroll/runs/2/lock \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json"
```
- **Kết quả mong đợi**: Run 2 chuyển từ `finalized` → `locked`
- ⬜

### TC-6.13: State machine - Không cho phép chuyển ngược
- **[API]** Thử finalize run 1 (đã locked)
```bash
curl -X POST http://localhost:8001/api/payroll/runs/1/finalize \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json"
```
- **Kết quả mong đợi**: HTTP 422 hoặc error "Cannot transition from locked"
- ⬜

---

## 7. Báo cáo

### TC-7.1: Danh sách mẫu báo cáo
- **[API]**
```bash
curl http://localhost:8001/api/reports/templates \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: Danh sách mẫu báo cáo có sẵn
- ⬜

---

## 8. Quản trị (Admin)

> Đăng nhập bằng `admin01`.

### TC-8.1: Danh sách users
- **[API]**
```bash
curl http://localhost:8001/api/users \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: 15 users
- ⬜

### TC-8.2: Danh sách roles
- **[API]**
```bash
curl http://localhost:8001/api/roles \
  -H "Authorization: Bearer <token>"
```
- **Kết quả mong đợi**: 5 roles (system_admin, hr_staff, accountant, management, employee)
- ⬜

### TC-8.3: Phân quyền - HR không truy cập được Admin
- **[API]** Đăng nhập `hr01`, thử xem users:
```bash
curl http://localhost:8001/api/users \
  -H "Authorization: Bearer <token_hr01>"
```
- **Kết quả mong đợi**: HTTP 403 Forbidden
- ⬜

### TC-8.4: Phân quyền - Employee không xem được nhân viên khác
- **[API]** Đăng nhập `emp001`, thử xem employees:
```bash
curl http://localhost:8001/api/employees \
  -H "Authorization: Bearer <token_emp001>"
```
- **Kết quả mong đợi**: HTTP 403 hoặc chỉ trả về thông tin bản thân
- ⬜

---

## 9. Flow nghiệp vụ end-to-end

### TC-9.1: Flow hoàn chỉnh - Chấm công đến Lương

Mô phỏng quy trình từ chấm công đến phiếu lương:

1. **HR đăng nhập** (`hr01` / `password`) ✅ xem được chấm công
2. **Xem chấm công tháng 02/2026** (period_id = 2)
   - Kiểm tra có đủ 15 NV
   - Kiểm tra các trường hợp bất thường
3. **Xem tổng hợp chấm công** - Verify total_workdays, late_minutes
4. **Xem/xử lý đơn từ** - 7 đơn các trạng thái
5. **Kế toán đăng nhập** (`payroll01` / `password`)
6. **Xem tham số lương** - Kiểm tra BHXH, thuế TNCN
7. **Xem bảng lương tháng 02** (run_id = 2, status = previewed)
8. **Kiểm tra phiếu lương** - So sánh gross, deductions, net
9. **Finalize bảng lương** (chuyển previewed → finalized)
10. **Admin khóa bảng lương** (`admin01`) (chuyển finalized → locked)

- ⬜

### TC-9.2: Flow kiểm tra tính lương

Kiểm tra công thức tính lương cho NV006 (emp001, lương 10M, 0 người phụ thuộc):

| Mục | Công thức | Giá trị (VNĐ) |
|-----|-----------|---------------|
| Lương cơ bản | | 10,000,000 |
| Phụ cấp ăn trưa | | 730,000 |
| Phụ cấp xăng xe | | 500,000 |
| **Tổng thu nhập (Gross)** | | **11,230,000** |
| BHXH (8%) | 10M × 8% | -800,000 |
| BHYT (1.5%) | 10M × 1.5% | -150,000 |
| BHTN (1%) | 10M × 1% | -100,000 |
| **Tổng BH NLĐ** | | **-1,050,000** |
| Thu nhập chịu thuế | Gross - BH - 11M (bản thân) | 11,230,000 - 1,050,000 - 11,000,000 = 0 |
| Thuế TNCN | 0 (vì TNCT < 0) | 0 |
| **Lương thực nhận (Net)** | Gross - BH - Thuế | **10,180,000** |

- ⬜

### TC-9.3: Flow kiểm tra tính lương NV có người phụ thuộc

Kiểm tra cho NV005 (Manager, lương 25M, 3 người phụ thuộc):

| Mục | Giá trị (VNĐ) |
|-----|---------------|
| Lương cơ bản | 25,000,000 |
| Phụ cấp (ăn + xe + TN + ĐT) | 730K + 500K + 2M + 300K = 3,530,000 |
| **Gross** | **28,530,000** |
| Tổng BH NLĐ (10.5%) | -2,625,000 |
| Giảm trừ bản thân | -11,000,000 |
| Giảm trừ 3 PT × 4.4M | -13,200,000 |
| Thu nhập chịu thuế | 28,530,000 - 2,625,000 - 11,000,000 - 13,200,000 = 1,705,000 |
| Thuế TNCN (bậc 1: 5%) | 1,705,000 × 5% = 85,250 |
| **Net** | 28,530,000 - 2,625,000 - 85,250 = **25,819,750** |

- ⬜

---

## 10. Kiểm tra API Response Format

### TC-10.1: Format response thành công
- Mọi API trả về:
```json
{
  "success": true,
  "data": { ... },
  "message": "OK",
  "errors": null
}
```
- ⬜

### TC-10.2: Format response lỗi
- Khi gửi request sai:
```json
{
  "success": false,
  "data": null,
  "message": "Error message",
  "errors": { ... }
}
```
- ⬜

### TC-10.3: Format response phân trang
- API danh sách có phân trang trả thêm:
```json
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 15,
    "last_page": 1
  }
}
```
- ⬜

---

## Hướng dẫn test nhanh bằng cURL

### Bước 1: Lấy token

```bash
# Lưu token vào biến
TOKEN=$(curl -s -X POST http://localhost:8001/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin01","password":"password"}' | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['token'])")

echo $TOKEN
```

### Bước 2: Gọi API với token

```bash
# Xem thông tin user
curl -s http://localhost:8001/api/me -H "Authorization: Bearer $TOKEN" | python3 -m json.tool

# Danh sách nhân viên
curl -s http://localhost:8001/api/employees -H "Authorization: Bearer $TOKEN" | python3 -m json.tool

# Chấm công tháng 02
curl -s "http://localhost:8001/api/attendance/daily?period_id=2" -H "Authorization: Bearer $TOKEN" | python3 -m json.tool

# Phiếu lương
curl -s http://localhost:8001/api/payroll/payslips -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
```

### Nếu dùng Postman

1. Tạo request `POST http://localhost:8001/api/auth/login` với body JSON
2. Copy `token` từ response
3. Trong tab Authorization chọn Bearer Token, paste token
4. Gọi các API khác

---

## Tổng kết Test Cases

| Module | Số TC | Mô tả |
|--------|-------|-------|
| Đăng nhập | 9 | Login/Logout, phân quyền, mock/DB mode |
| Nhân viên | 6 | CRUD, hợp đồng, người phụ thuộc |
| Hợp đồng | 3 | Các loại hợp đồng, thử việc |
| Tham chiếu | 5 | Phòng ban, ca, lễ, tham số |
| Chấm công | 8 | Logs, daily, summary, đơn từ, bất thường |
| Bảng lương | 13 | Runs, payslips, state machine, thưởng/khấu trừ |
| Báo cáo | 1 | Templates |
| Quản trị | 4 | Users, roles, phân quyền |
| E2E Flow | 3 | Flow nghiệp vụ, kiểm tra công thức lương |
| API Format | 3 | Response format chuẩn |
| **Tổng** | **55** | |
