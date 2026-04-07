# Checklist kiểm tra Stored Procedure trên hệ thống

Sau khi tạo stored procedure và đăng ký metadata, chạy qua các bước kiểm tra dưới đây.

## Tài liệu liên quan

- [README.md](README.md): tổng quan tài liệu và menu SQL Integration.
- [sql_serv_imp.md](sql_serv_imp.md): mô tả ngắn gọn cách hệ thống xử lý procedure theo metadata.
- [HOW_TO_ADD_NEW_REPORT.md](HOW_TO_ADD_NEW_REPORT.md): hướng dẫn khai báo procedure, parameters và columns.
- [backend/database/sql/procedure_template.sql](backend/database/sql/procedure_template.sql): mẫu SQL đăng ký procedure mới.

---

## 1. Kiểm tra trong SQL Server (SSMS / Azure Data Studio)

### 1.1 Procedure chạy được trực tiếp

```sql
-- Thay tên procedure và tham số cho đúng
EXEC dbo.usp_Hrm_AttendanceCollection
    @_DocDate1 = '20260301',
    @_DocDate2 = '20260331',
    @_EmployeeId = '',
    @_DeptId = '',
    @_BranchCode = 'A01',
    @_nUserId = 0;
```

- [ ] Procedure chạy không lỗi
- [ ] Kết quả trả về có dữ liệu (ít nhất 1 dòng)
- [ ] Tên các cột trong kết quả **cố định** (không thay đổi theo dữ liệu)

### 1.2 Metadata đã được đăng ký đúng

```sql
-- Thay 'ma-code' bằng code thực tế
DECLARE @code NVARCHAR(80) = N'attendance-collection';

-- Kiểm tra catalog
SELECT id, code, label, procedure_name, is_active
FROM procedure_catalog
WHERE code = @code;

-- Kiểm tra parameters
SELECT name, sp_param_name, type, label, required, sort_order
FROM procedure_parameters
WHERE procedure_id = (SELECT id FROM procedure_catalog WHERE code = @code)
ORDER BY sort_order;

-- Kiểm tra columns
SELECT [key], label, type, visible, sort_order
FROM procedure_columns
WHERE procedure_id = (SELECT id FROM procedure_catalog WHERE code = @code)
ORDER BY sort_order;
```

- [ ] `procedure_catalog` có đúng 1 dòng với `is_active = 1`
- [ ] Số lượng parameters khớp với số tham số trong procedure
- [ ] `sp_param_name` khớp chính xác tên tham số trong procedure (kể cả `@` và hoa/thường)
- [ ] Số cột trong `procedure_columns` khớp với số cột SELECT của procedure
- [ ] Tên cột trong `[key]` khớp chính xác tên cột trả về từ procedure

---

## 2. Kiểm tra trên giao diện web

### 2.1 Procedure hiện trong danh sách

1. Đăng nhập hệ thống (tài khoản có quyền xem báo cáo)
2. Vào menu **SQL Integration**
3. Kiểm tra:

- [ ] Procedure mới xuất hiện trong danh sách
- [ ] Tên hiển thị đúng tiếng Việt
- [ ] Số tham số và số cột hiện đúng

### 2.2 Form nhập hoạt động

1. Chọn procedure
2. Kiểm tra:

- [ ] Các trường nhập hiển thị đúng thứ tự
- [ ] Trường ngày có date picker
- [ ] Trường bắt buộc có dấu `*`
- [ ] Tham số `user_id` không hiển thị trên form (app tự gán)

### 2.3 Thực thi và xem kết quả

1. Nhập các điều kiện lọc
2. Bấm **Thực thi**
3. Kiểm tra:

- [ ] Không báo lỗi
- [ ] Dữ liệu hiển thị trong bảng
- [ ] Tên cột tiếng Việt hiển thị đúng
- [ ] Số dòng kết quả hợp lý
- [ ] Dữ liệu số hiển thị đúng định dạng (có dấu phân cách hàng nghìn)
- [ ] Dữ liệu ngày hiển thị đúng format

---

## 3. Lỗi thường gặp và cách khắc phục

| Triệu chứng | Nguyên nhân có thể | Cách sửa |
| --- | --- | --- |
| Procedure không hiện trên web | `is_active = 0` hoặc chưa INSERT catalog | Kiểm tra `procedure_catalog` |
| Bấm Thực thi báo lỗi 500 | Procedure chạy lỗi trong SQL Server | Kiểm tra procedure chạy được trong SSMS |
| Bảng kết quả hiện nhưng ô trống | Tên `key` trong `procedure_columns` không khớp tên cột SELECT | So sánh tên cột chính xác |
| Thiếu trường nhập trên form | Thiếu dòng trong `procedure_parameters` | Thêm INSERT parameter |
| Lỗi "missing required parameter" | Tham số bắt buộc nhưng user không nhập | Kiểm tra `required = 1` có đúng không |
| Lỗi quyền (permission denied) | Tài khoản DB của app không có quyền EXECUTE | `GRANT EXECUTE ON dbo.usp_... TO [app_user]` |
| Dữ liệu trả về nhưng không đúng | BranchCode hoặc filter truyền sai giá trị | Kiểm tra `default_value` trong parameters |

---

## 4. Kiểm tra execution log

Mỗi lần thực thi đều được ghi log. Dùng câu truy vấn sau để kiểm tra:

```sql
SELECT TOP 20
    c.code,
    c.label,
    l.parameters,
    l.row_count,
    l.execution_ms,
    l.status,
    l.error_message,
    l.executed_at
FROM procedure_execution_logs l
JOIN procedure_catalog c ON c.id = l.procedure_id
ORDER BY l.executed_at DESC;
```

- [ ] Log ghi nhận các lần thực thi
- [ ] Trạng thái `success` khi chạy đúng
- [ ] Trạng thái `error` khi có lỗi (kèm `error_message`)
