-- ============================================================
-- TEMPLATE: Thêm mới Stored Procedure vào hệ thống
-- ============================================================
-- Hướng dẫn: Copy toàn bộ file này, thay thế các giá trị
-- trong [...] bằng thông tin thực tế, rồi chạy trong SSMS.
-- ============================================================

-- ============================================================
-- PHẦN A: Tạo Stored Procedure (nếu chưa có)
-- ============================================================
-- Bỏ qua phần này nếu procedure đã tồn tại trong SQL Server.
--
-- CREATE OR ALTER PROCEDURE dbo.[usp_TenProcedure]
--     @_DocDate1   DATE         = '20240801',
--     @_DocDate2   DATE         = '20240831',
--     @_EmployeeId VARCHAR(512) = '',
--     @_DeptId     VARCHAR(512) = '',
--     @_BranchCode VARCHAR(3)   = 'A01',
--     @_nUserId    INT          = 0
-- AS
-- BEGIN
--     SET NOCOUNT ON;
--     -- ... logic truy vấn ở đây ...
--     SELECT ... FROM ... WHERE ...
-- END
-- GO


-- ============================================================
-- PHẦN B: Đăng ký procedure vào catalog
-- ============================================================
-- Thay các giá trị sau:
--   [ma-code]          : mã nội bộ duy nhất, chỉ gồm chữ thường và dấu gạch ngang
--                        ví dụ: overtime-report, salary-summary
--   [Tên hiển thị]     : tên tiếng Việt hiển thị trên giao diện
--   [dbo.usp_TenProc]  : tên thật của stored procedure trong SQL Server
--   [module]            : nhóm chức năng: attendance, payroll, general, ...
--   [Mô tả]            : ghi chú ngắn về procedure

INSERT INTO procedure_catalog
    (code, label, procedure_name, module, description, is_active)
VALUES
    (N'[ma-code]', N'[Tên hiển thị]', N'[dbo.usp_TenProc]', N'[module]', N'[Mô tả]', 1);
GO

-- Lấy ID vừa insert để dùng cho phần dưới
DECLARE @proc_id BIGINT = SCOPE_IDENTITY();

-- Nếu chạy riêng từng phần, thay @proc_id bằng số ID thực tế:
-- DECLARE @proc_id BIGINT = (SELECT id FROM procedure_catalog WHERE code = N'[ma-code]');


-- ============================================================
-- PHẦN C: Khai báo tham số (Parameters)
-- ============================================================
-- Mỗi tham số của stored procedure cần 1 dòng INSERT.
--
-- Giải thích các cột:
--   procedure_id  : ID của procedure vừa thêm ở Phần B
--   name          : tên thân thiện (dùng nội bộ app), ví dụ: date_from, department_id
--   sp_param_name : tên tham số thật trong SQL Server, ví dụ: @_DocDate1
--   type          : kiểu dữ liệu: date | string | integer | tinyint | boolean
--   label         : nhãn hiển thị trên giao diện (tiếng Việt)
--   required      : 1 = bắt buộc nhập, 0 = không bắt buộc
--   default_value : giá trị mặc định (NULL nếu không có)
--   sort_order    : thứ tự hiển thị trên form (1, 2, 3, ...)

INSERT INTO procedure_parameters
    (procedure_id, name, sp_param_name, type, label, required, default_value, sort_order)
VALUES
    (@proc_id, N'date_from',     N'@_DocDate1',    N'date',    N'Từ ngày',        1, NULL,  1),
    (@proc_id, N'date_to',       N'@_DocDate2',    N'date',    N'Đến ngày',       1, NULL,  2),
    (@proc_id, N'employee_id',   N'@_EmployeeId',  N'string',  N'Mã nhân viên',   0, N'',   3),
    (@proc_id, N'department_id', N'@_DeptId',       N'string',  N'Mã phòng ban',   0, N'',   4),
    (@proc_id, N'branch_code',   N'@_BranchCode',  N'string',  N'Mã chi nhánh',   0, N'',   5),
    (@proc_id, N'user_id',       N'@_nUserId',     N'integer', N'User ID',         0, N'0',  6);
GO

-- Thêm hoặc bớt dòng tùy số lượng tham số thực tế.
-- Xóa dòng nào không dùng, thêm dòng mới nếu procedure có nhiều tham số hơn.


-- ============================================================
-- PHẦN D: Khai báo cột kết quả (Columns)
-- ============================================================
-- Mỗi cột trong SELECT của stored procedure cần 1 dòng INSERT.
--
-- Giải thích các cột:
--   procedure_id : ID của procedure vừa thêm ở Phần B
--   [key]        : tên cột trả về từ procedure (phải khớp chính xác tên cột trong SELECT)
--   label        : nhãn hiển thị trên bảng kết quả (tiếng Việt)
--   type         : kiểu dữ liệu hiển thị: string | number | date | boolean
--   visible      : 1 = hiển thị trên bảng, 0 = ẩn (vẫn có trong dữ liệu)
--   exportable   : 1 = xuất Excel, 0 = không xuất
--   sort_order   : thứ tự cột từ trái sang phải (1, 2, 3, ...)

INSERT INTO procedure_columns
    (procedure_id, [key], label, type, visible, exportable, sort_order)
VALUES
    (@proc_id, N'EmployeeCode',  N'Mã NV',      N'string',  1, 1, 1),
    (@proc_id, N'EmployeeName',  N'Họ và tên',   N'string',  1, 1, 2),
    (@proc_id, N'DeptName',      N'Phòng ban',   N'string',  1, 1, 3),
    (@proc_id, N'Column4',       N'[Tên cột 4]', N'number',  1, 1, 4),
    (@proc_id, N'Column5',       N'[Tên cột 5]', N'string',  1, 1, 5);
GO

-- Thêm hoặc bớt dòng tùy số cột trả về thực tế.
-- Tên cột trong [key] phải KHỚP CHÍNH XÁC tên cột trong SELECT của procedure.
-- Nếu tên cột sai, bảng kết quả sẽ không hiển thị được dữ liệu đúng.


-- ============================================================
-- PHẦN E: Kiểm tra
-- ============================================================
-- Chạy các câu lệnh sau để xác nhận đã đăng ký đúng:

-- 1. Kiểm tra procedure trong catalog
SELECT * FROM procedure_catalog WHERE code = N'[ma-code]';

-- 2. Kiểm tra parameters
SELECT p.name, p.sp_param_name, p.type, p.label, p.required
FROM procedure_parameters p
JOIN procedure_catalog c ON c.id = p.procedure_id
WHERE c.code = N'[ma-code]'
ORDER BY p.sort_order;

-- 3. Kiểm tra columns
SELECT col.[key], col.label, col.type, col.visible
FROM procedure_columns col
JOIN procedure_catalog c ON c.id = col.procedure_id
WHERE c.code = N'[ma-code]'
ORDER BY col.sort_order;

-- 4. Sau đó vào web → SQL Integration → chọn procedure → nhập filter → bấm Thực thi
