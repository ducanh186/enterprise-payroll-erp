# Hướng dẫn thêm mới / cập nhật báo cáo SQL

Hệ thống hỗ trợ gọi Stored Procedure từ SQL Server theo cấu hình metadata.
**Quý khách không cần chỉnh sửa source code Backend (PHP/Laravel) hay Frontend (React).**

Chỉ cần thao tác trong SQL Server Management Studio (SSMS) hoặc Azure Data Studio.

## Tài liệu liên quan

- [README.md](README.md): tổng quan hệ thống và điều hướng tài liệu.
- [sql_serv_imp.md](sql_serv_imp.md): giải thích cách SQL Integration hoạt động theo ngôn ngữ đơn giản.
- [SMOKE_TEST_CHECKLIST.md](SMOKE_TEST_CHECKLIST.md): checklist kiểm tra sau khi khai báo xong.
- [backend/database/sql/procedure_template.sql](backend/database/sql/procedure_template.sql): file SQL mẫu để copy-paste.

---

## Tổng quan kiến trúc

```text
Khách quản lý (SQL Server)          App tự xử lý (không cần sửa)
─────────────────────────────       ────────────────────────────────
1. Stored Procedures                → Backend gọi procedure, trả JSON
2. Bảng procedure_catalog           → Frontend hiển thị danh sách
3. Bảng procedure_parameters        → Frontend tự dựng form nhập
4. Bảng procedure_columns           → Frontend tự dựng bảng kết quả
```

**Nguyên tắc**: Khi thêm một stored procedure mới, chỉ cần đăng ký metadata vào 3 bảng config → hệ thống tự động nhận diện và hiển thị trên giao diện web.

---

## Quy trình 5 bước

### Bước 1: Tạo hoặc cập nhật Stored Procedure

Tạo procedure trong SQL Server theo chuẩn công ty. Yêu cầu:

- Procedure chỉ trả về **1 result set** (1 câu SELECT cuối cùng)
- Không dùng **output parameter** (chỉ dùng input parameter)
- Tên cột trong SELECT cần **cố định** (không thay đổi theo dữ liệu)

Ví dụ các procedure đã có trong hệ thống:

| Stored Procedure | Chức năng |
| --- | --- |
| `dbo.usp_Hrm_AttendanceCollection` | Tổng hợp công |
| `dbo.usp_Hrm_AttendanceReport` | Bảng chấm công chi tiết |
| `dbo.usp_Hrm_B30HrmAssignShift` | Bảng phân ca hàng ngày |
| `dbo.usp_Hrm_InOut_LaterEarly` | Tổng hợp đi trễ về sớm |

### Bước 2: Khai báo procedure vào bảng `procedure_catalog`

```sql
INSERT INTO procedure_catalog
    (code, label, procedure_name, module, description, is_active)
VALUES
    (N'overtime-report', N'Bảng tăng ca', N'dbo.usp_Hrm_OvertimeReport', N'attendance', N'Thống kê giờ tăng ca theo nhân viên.', 1);
```

| Cột | Ý nghĩa | Ví dụ |
| --- | --- | --- |
| `code` | Mã nội bộ duy nhất (chữ thường, dấu gạch ngang) | `overtime-report` |
| `label` | Tên hiển thị trên giao diện (tiếng Việt) | `Bảng tăng ca` |
| `procedure_name` | Tên stored procedure thật trong SQL Server | `dbo.usp_Hrm_OvertimeReport` |
| `module` | Nhóm: `attendance`, `payroll`, `general` | `attendance` |
| `description` | Mô tả ngắn | `Thống kê giờ tăng ca...` |
| `is_active` | 1 = hiện, 0 = ẩn | `1` |

### Bước 3: Khai báo tham số vào bảng `procedure_parameters`

Mỗi tham số `@...` trong procedure cần 1 dòng INSERT:

```sql
-- Lấy ID procedure vừa thêm
DECLARE @proc_id BIGINT = (SELECT id FROM procedure_catalog WHERE code = N'overtime-report');

INSERT INTO procedure_parameters
    (procedure_id, name, sp_param_name, type, label, required, default_value, sort_order)
VALUES
    (@proc_id, N'date_from',     N'@_DocDate1',    N'date',    N'Từ ngày',        1, NULL,  1),
    (@proc_id, N'date_to',       N'@_DocDate2',    N'date',    N'Đến ngày',       1, NULL,  2),
    (@proc_id, N'employee_id',   N'@_EmployeeId',  N'string',  N'Mã nhân viên',   0, N'',   3),
    (@proc_id, N'department_id', N'@_DeptId',       N'string',  N'Mã phòng ban',   0, N'',   4),
    (@proc_id, N'branch_code',   N'@_BranchCode',  N'string',  N'Mã chi nhánh',   0, N'',   5),
    (@proc_id, N'user_id',       N'@_nUserId',     N'integer', N'User ID',         0, N'0',  6);
```

| Cột | Ý nghĩa |
| --- | --- |
| `name` | Tên thân thiện dùng nội bộ app (tiếng Anh, snake_case) |
| `sp_param_name` | Tên tham số thật trong procedure (bao gồm `@`) |
| `type` | Kiểu dữ liệu: `date`, `string`, `integer`, `tinyint`, `boolean` |
| `label` | Nhãn hiển thị trên form (tiếng Việt) |
| `required` | `1` = bắt buộc nhập, `0` = không bắt buộc |
| `default_value` | Giá trị mặc định, `NULL` nếu không có |
| `sort_order` | Thứ tự hiển thị trên form (1, 2, 3...) |

> **Lưu ý**: Tham số có `name = 'user_id'` sẽ được app tự động gán, không hiển thị trên form.

### Bước 4: Khai báo cột kết quả vào bảng `procedure_columns`

Mỗi cột trong câu SELECT của procedure cần 1 dòng INSERT:

```sql
INSERT INTO procedure_columns
    (procedure_id, [key], label, type, visible, exportable, sort_order)
VALUES
    (@proc_id, N'EmployeeCode',  N'Mã NV',       N'string',  1, 1, 1),
    (@proc_id, N'EmployeeName',  N'Họ và tên',    N'string',  1, 1, 2),
    (@proc_id, N'DeptName',      N'Phòng ban',    N'string',  1, 1, 3),
    (@proc_id, N'OtHours',       N'Giờ tăng ca',  N'number',  1, 1, 4),
    (@proc_id, N'OtDate',        N'Ngày tăng ca', N'date',    1, 1, 5);
```

| Cột | Ý nghĩa |
| --- | --- |
| `key` | Tên cột trả về từ procedure (**phải khớp chính xác** tên cột trong SELECT) |
| `label` | Nhãn hiển thị tiêu đề cột trên bảng (tiếng Việt) |
| `type` | Kiểu hiển thị: `string`, `number`, `date`, `boolean` |
| `visible` | `1` = hiện trên bảng, `0` = ẩn |
| `exportable` | `1` = có trong file xuất Excel, `0` = không xuất |
| `sort_order` | Thứ tự cột từ trái sang phải |

> **Quan trọng**: Nếu tên cột trong `key` không khớp với tên cột thực tế từ procedure, bảng kết quả sẽ hiện ô trống.

### Bước 5: Vào web kiểm tra

1. Đăng nhập hệ thống web
2. Vào menu **SQL Integration** (trong nhóm Báo cáo)
3. Chọn procedure vừa thêm
4. Nhập các điều kiện lọc
5. Bấm **Thực thi**
6. Kiểm tra dữ liệu trả về trong bảng kết quả

---

## Bảng mapping hiện tại

| Tên trên giao diện | Code | Stored Procedure |
| --- | --- | --- |
| Tổng hợp công | `attendance-collection` | `dbo.usp_Hrm_AttendanceCollection` |
| Bảng chấm công | `attendance-report` | `dbo.usp_Hrm_AttendanceReport` |
| Bảng phân ca hàng ngày | `assign-shift` | `dbo.usp_Hrm_B30HrmAssignShift` |
| Tổng hợp đi trễ về sớm | `late-early` | `dbo.usp_Hrm_InOut_LaterEarly` |

---

## Ví dụ nhanh: File SQL mẫu

Xem file `backend/database/sql/procedure_template.sql` để có template copy-paste sẵn với đầy đủ hướng dẫn cho từng phần.

---

## Cập nhật procedure đã có

### Thêm/bớt tham số

```sql
-- Thêm tham số mới
DECLARE @proc_id BIGINT = (SELECT id FROM procedure_catalog WHERE code = N'attendance-collection');

INSERT INTO procedure_parameters
    (procedure_id, name, sp_param_name, type, label, required, default_value, sort_order)
VALUES
    (@proc_id, N'status_filter', N'@_Status', N'string', N'Trạng thái', 0, N'', 7);

-- Xóa tham số không dùng nữa
DELETE FROM procedure_parameters
WHERE procedure_id = @proc_id AND name = N'status_filter';
```

### Thêm/bớt cột kết quả

```sql
-- Thêm cột mới
INSERT INTO procedure_columns
    (procedure_id, [key], label, type, visible, exportable, sort_order)
VALUES
    (@proc_id, N'NewColumn', N'Cột mới', N'string', 1, 1, 10);

-- Ẩn cột (không xóa, chỉ ẩn)
UPDATE procedure_columns
SET visible = 0
WHERE procedure_id = @proc_id AND [key] = N'NewColumn';
```

### Tắt procedure (không xóa)

```sql
UPDATE procedure_catalog SET is_active = 0 WHERE code = N'attendance-collection';
```

---

## Lưu ý quan trọng

1. **Không nhập raw SQL query** — hệ thống chỉ thực thi stored procedure đã đăng ký trong catalog
2. **Tên cột phải cố định** — nếu procedure trả cột khác nhau tùy dữ liệu, giao diện không xử lý được
3. **Chỉ 1 result set** — procedure trả nhiều SELECT sẽ chỉ lấy kết quả đầu tiên
4. **Quyền EXECUTE** — tài khoản DB của app (`sa` hoặc user riêng) phải có quyền EXECUTE trên procedure
5. **Kiểm tra tên tham số** — `sp_param_name` phải khớp chính xác (kể cả hoa/thường) với tên parameter trong procedure

---

## Khi nào cần liên hệ đội phát triển

- Stored procedure trả **nhiều result set**
- Có **output parameter** cần hiển thị
- Cần logic **phân trang** đặc biệt từ phía procedure
- Tên cột kết quả **thay đổi động** theo dữ liệu đầu vào
- Cần **dropdown/select** cho tham số thay vì text input đơn giản
