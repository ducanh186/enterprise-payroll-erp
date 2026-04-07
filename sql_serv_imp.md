# SQL Integration Overview

Tài liệu này giải thích ngắn gọn cách phần **SQL Integration / Procedures** hoạt động trong dự án.

## Nói cực dễ hiểu

Hãy tưởng tượng web là một **máy bán hàng tự động**.

Khách hàng chuẩn bị 3 thứ trong SQL Server:

- tên stored procedure thật
- danh sách tham số đầu vào
- danh sách cột output

App sẽ tự biết:

- cần hiện ô nhập gì trên form
- khi bấm chạy thì gọi procedure nào
- dữ liệu trả về phải vẽ thành bảng như thế nào

Ý chính là: **code app gần như không đổi, chỉ thay dữ liệu cấu hình**.

## Nói chính xác theo kỹ thuật

Người dùng web **không nhập trực tiếp tên stored procedure**.
Thay vào đó, họ chọn một procedure đã được đăng ký sẵn trong metadata.

Metadata hiện nằm ở 4 bảng:

- `procedure_catalog`: danh sách procedure được phép gọi
- `procedure_parameters`: mô tả từng tham số đầu vào
- `procedure_columns`: mô tả từng cột output để frontend render
- `procedure_execution_logs`: log lại mỗi lần chạy, bao gồm trạng thái và thời gian thực thi

## Data flow hiện tại

```text
1. Frontend gọi GET /api/procedures
2. User chọn một procedure đã đăng ký
3. Frontend gọi GET /api/procedures/{code}/meta
4. App lấy metadata params + columns
5. Frontend dựng form nhập theo metadata
6. User bấm Thực thi
7. Backend gọi POST /api/procedures/{code}/execute
8. ProcedureService map code -> procedure_name trong SQL Server
9. Backend bind parameters an toàn rồi EXEC procedure
10. Backend trả JSON records + columns + execution_ms
11. Frontend dựng bảng kết quả theo procedure_columns
```

## Ranh giới trách nhiệm

### Khách hàng phụ trách

- tạo hoặc cập nhật stored procedure trong SQL Server
- khai báo metadata vào các bảng config
- kiểm tra logic nghiệp vụ đúng trong SSMS

### Ứng dụng phụ trách

- xác thực người dùng và phân quyền
- lấy metadata và dựng form động
- bind parameters an toàn
- gọi stored procedure qua backend
- log kết quả thực thi
- hiển thị bảng dữ liệu trên web

## Khi nào không cần sửa code

Mô hình hiện tại hoạt động tốt nếu procedure mới nằm trong các điều kiện sau:

1. Chỉ trả về **1 result set**.
2. Tên cột output **cố định**.
3. Chỉ dùng các kiểu tham số app đang hỗ trợ: `date`, `string`, `integer`, `tinyint`, `boolean`.
4. Không cần UI đặc biệt như dropdown động, nhiều bảng kết quả hoặc output parameter.

Nếu vẫn nằm trong 4 điều kiện trên, thường chỉ cần cập nhật **SQL metadata**, không cần sửa Laravel/React.

## Khi nào có thể phải sửa code

- procedure trả nhiều result set
- procedure có output parameter
- UI cần control đặc biệt ngoài text/date/number cơ bản
- tên cột thay đổi động theo dữ liệu
- cần export hoặc phân trang theo logic riêng

## Tài liệu liên quan

- [README.md](README.md): trang tổng quan và cổng điều hướng tài liệu.
- [HOW_TO_ADD_NEW_REPORT.md](HOW_TO_ADD_NEW_REPORT.md): hướng dẫn thao tác cho khách hàng.
- [SMOKE_TEST_CHECKLIST.md](SMOKE_TEST_CHECKLIST.md): checklist kiểm tra sau triển khai.
- [backend/database/sql/procedure_template.sql](backend/database/sql/procedure_template.sql): mẫu SQL dùng để đăng ký procedure mới.
