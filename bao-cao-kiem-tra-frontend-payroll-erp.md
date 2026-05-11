# Báo cáo kiểm tra frontend Payroll ERP

Ngày kiểm tra: 29/03/2026  
Phiên bản kiểm tra: Frontend localhost:5173 | Backend localhost:8001  
Tài khoản test: admin01 (system_admin)  
Trình duyệt: Chrome  

---

## 1. Cấu trúc navigation của sidebar chưa đúng brief

### Theo brief

Panel điều hướng cần có 3 cấp chính:

- **Nhân sự & HĐLĐ**
  - Danh mục: Hồ sơ cán bộ nhân viên, Danh mục loại hợp đồng, Danh mục thang/bậc lương
  - Biến động: Hợp đồng lao động & phụ lục hợp đồng, Phụ cấp
  - Báo cáo: Danh sách người lao động theo loại hợp đồng
- **Chấm công**
  - Danh mục: Quy định đi trễ về sớm, Danh mục ngày nghỉ trong năm, Danh mục ca làm việc
  - Biến động: Phân ca làm việc, Dữ liệu thời gian vào - ra, Đơn xin nghỉ phép, Chấm công bổ sung, Tổng hợp công
  - Báo cáo: Bảng phân ca hàng ngày, Bảng tổng hợp đi trễ về sớm, Bảng chấm công
- **Tính lương**
  - Danh mục: Bộ công thức & Tham số lương
  - Biến động: Khen thưởng & Kỷ luật, Tính lương, Bảng lương
  - Báo cáo: Phiếu lương, Bảng tổng hợp thanh toán lương và các khoản hỗ trợ

### Hiện trạng

Sidebar hiện tại vẫn là cấu trúc phẳng, chưa theo 3 cấp:

```text
Dashboard
Chấm công
  ├── Nhật ký check-in
  └── Tổng kết tháng
Tính lương
  ├── Chạy bảng lương
  └── Phiếu lương
Hợp đồng
Báo cáo
Quản trị
  ├── Người dùng
  └── Phân quyền
```

### Các vấn đề cụ thể

| # | Vấn đề | Mức độ |
| --- | --- | --- |
| 1.1 | Thiếu nhóm "Nhân sự & HĐLĐ". Brief yêu cầu một module riêng, nhưng hiện tại chỉ có "Hợp đồng" đứng tách lẻ. | Nghiêm trọng |
| 1.2 | Thiếu phân cấp Danh mục / Biến động / Báo cáo. Mỗi module cần 3 nhóm con, còn sidebar hiện tại vẫn là danh sách phẳng. | Nghiêm trọng |
| 1.3 | Thiếu nhiều menu con trong Nhân sự: Hồ sơ cán bộ NV, Danh mục loại hợp đồng, Danh mục thang/bậc lương, Phụ cấp, DS NLĐ theo loại HĐ. | Nghiêm trọng |
| 1.4 | Thiếu nhiều menu con trong Chấm công: Quy định đi trễ/về sớm, Danh mục ngày nghỉ, Danh mục ca làm việc, Phân ca, Dữ liệu vào-ra, Đơn xin nghỉ phép, Chấm công bổ sung, Tổng hợp công, Bảng phân ca, Bảng tổng hợp đi trễ/về sớm, Bảng chấm công. | Nghiêm trọng |
| 1.5 | Thiếu menu con trong Tính lương: Bộ công thức & Tham số lương, Khen thưởng & Kỷ luật, Tính lương, Bảng lương, Bảng tổng hợp thanh toán lương. | Nghiêm trọng |
| 1.6 | Mục "Quản trị" không có trong brief nhưng lại xuất hiện ở sidebar. | Nhẹ |

---

## 2. Text chưa đồng nhất, cần đưa về sentence case

Brief ghi rõ: "Sửa lại text trong UI đồng nhất theo Sentence case."

### 2.1 Trộn lẫn Tiếng Anh và Tiếng Việt

| Trang | Text hiện tại (EN) | Nên sửa thành (VI) |
| --- | --- | --- |
| Dashboard | "OVERVIEW" | "Tổng quan" |
| Dashboard | "Payroll snapshots" | "Tổng quan phiếu lương" |
| Dashboard | "Contracts" | "Hợp đồng" |
| Dashboard | "Quick view từ API hiện có" | "Xem nhanh dữ liệu chấm công" |
| Dashboard | "Template báo cáo" | "Mẫu báo cáo" |
| Dashboard | "Seed report templates" | Cần dịch sang tiếng Việt |
| Dashboard | "HR", "Live", "Payroll", "Reports" (badges) | Cần dịch hoặc thống nhất |
| Nhật ký check-in | "EMPLOYEE ID", "CHECK TIME", "CHECK TYPE", "REASON" | "Mã nhân viên", "Thời gian", "Loại", "Lý do" |
| Nhật ký check-in | "Manual check-in" | "Chấm công thủ công" |
| Nhật ký check-in | "Tạo manual log" | "Tạo bản ghi thủ công" |
| Tổng kết tháng | "Recalculate" | "Tính lại" |
| Tổng kết tháng | "PENDING" (trạng thái) | "Chờ duyệt" |
| Payroll Run | "Payroll Run Wizard" | "Trình chạy bảng lương" |
| Payroll Run | "Initialize the payroll framework..." | Cần dịch |
| Payroll Run | "SETUP", "INPUTS & ADJUSTMENTS", "PREVIEW & FINALIZE" | Cần dịch |
| Payroll Run | "Setup", "Inputs & Adjustments", "Preview & Finalize" | Cần dịch |
| Payroll Run | "EXECUTION PARAMETERS" | "Tham số thực thi" |
| Payroll Run | "Payroll Month", "Year", "Payroll Scope" | Cần dịch |
| Payroll Run | "Full Monthly (All)", "By Department" | Cần dịch |
| Payroll Run | "PREVIOUS MONTH REFERENCE" | Cần dịch |
| Payroll Run | "TOTAL NET PAY", "CONFIRMED" | Cần dịch |
| Payroll Run | "PARAMS LOADED", "DEPARTMENTS" | Cần dịch |
| Payroll Run | "Open Period & Exit", "Proceed to Next Step" | Cần dịch |
| Hợp đồng | "TỔNG BASE SALARY" | "Tổng lương cơ bản" |
| Admin | "User Management Directory" | "Quản lý người dùng" |
| Admin | "Manage system permissions..." | Cần dịch |
| Admin | "Add New User" | "Thêm người dùng mới" |
| Admin | "TOTAL USERS", "ACTIVE", "ROLES" | Cần dịch |
| Admin | "USER DETAILS", "SYSTEM ROLE", "DEPARTMENT", "STATUS", "ACTIONS" | Cần dịch |
| Admin | "Filter by name, email, or username..." | Cần dịch |
| Admin | "EDIT USER", "USERNAME", "LAST LOGIN", "NAME", "EMAIL", "ROLE", "DEPARTMENT" | Cần dịch |
| Admin | "Account active", "Update User", "Reset Password", "Assign Role" | Cần dịch |
| Phân quyền | "Role & Permissions Matrix" | "Ma trận vai trò & quyền hạn" |
| Phân quyền | "View and understand the permissions..." | Cần dịch |
| Phân quyền | "Refresh" | "Làm mới" |
| Phân quyền | "ROLE INSIGHTS", "ACCESS REMINDER" | Cần dịch |
| Phân quyền | "MODULE / PERMISSION" | Cần dịch |
| Báo cáo | "Reports Center" | "Trung tâm báo cáo" |
| Phiếu lương | "KỲ GẦN NHẤT" (ALL CAPS) | "Kỳ gần nhất" (Sentence case) |

### 2.2 Tên báo cáo thiếu dấu tiếng Việt (trang Báo cáo)

| Hiện tại (không dấu) | Cần sửa |
| --- | --- |
| "Bao Cao Cham Cong Hang Ngay" | "Báo cáo chấm công hàng ngày" |
| "Bao Cao Cham Cong Hang Thang" | "Báo cáo chấm công hàng tháng" |
| "Bao Cao Tong Hop Luong" | "Báo cáo tổng hợp lương" |
| "Phieu Luong Ca Nhan" | "Phiếu lương cá nhân" |
| "Bao Cao Bao Hiem Xa Hoi" | "Báo cáo bảo hiểm xã hội" |
| "Bao Cao Thue Thu Nhap Ca Nhan" | "Báo cáo thuế thu nhập cá nhân" |
| Mô tả cũng thiếu dấu tương tự | Cần sửa toàn bộ |

### 2.3 All caps không đúng sentence case

Nhiều label đang dùng ALL CAPS (ví dụ: "PHÒNG BAN", "THÁNG LƯƠNG", "NĂM", "MÃ NV", "TÊN NHÂN VIÊN", "NGÀY CÔNG", "OT (GIỜ)", "NP KHÔNG LƯƠNG", "NP CÓ LƯƠNG", "TRẠNG THÁI", "HÀNH ĐỘNG", "HÀNG LOẠT", "ĐANG HIỆU LỰC", "CẦN GIA HẠN", "TỔNG HỢP ĐỒNG", "TỪ NGÀY", "ĐẾN NGÀY", "EMPLOYEE ID", "MACHINE", "TRẠNG THÁI").

Theo brief, toàn bộ các label này cần đưa về sentence case, tức chỉ viết hoa chữ cái đầu.

---

## 3. Form editor chưa mở bằng popup hoặc tab mới

Brief yêu cầu: "Khi sửa hoặc tạo bản ghi mới thì sẽ hiển thị pop-up hoặc tab mới trên browser màn hình editor, không để trong cùng 1 tab với màn hình hiển thị danh sách."

### Hiện trạng của form editor

| Trang | Hành vi hiện tại | Vấn đề |
| --- | --- | --- |
| Hợp đồng — nút "Hợp đồng mới" | Click không có phản hồi | Nút không hoạt động |
| Hợp đồng — nút view/edit (icon) | Click không mở gì | Nút không hoạt động |
| Admin — Edit User | Panel hiện inline bên phải cùng trang | Không phải popup/tab mới |
| Admin — "Add New User" | Không kiểm tra được | Cần verify |

Cần sửa: khi tạo hoặc sửa bản ghi, form nên mở bằng popup (modal) hoặc tab mới thay vì nằm ngay trong trang danh sách.

---

## 4. Lỗi chức năng

| # | Trang | Lỗi | Mức độ |
| --- | --- | --- | --- |
| 4.1 | Hợp đồng | Nút "Hợp đồng mới" click không phản hồi | Nghiêm trọng |
| 4.2 | Hợp đồng | Nút View (👁) và Edit (✏) click không mở gì | Nghiêm trọng |
| 4.3 | Hợp đồng | Cột "Phòng ban" và "Loại" đều hiển thị "N/A" cho tất cả bản ghi | Trung bình |
| 4.4 | Dashboard | Tất cả nhân viên hiển thị "N/A / absent" cho chấm công hôm nay; trường hợp này có thể đúng nếu chưa có dữ liệu chấm công | Nhẹ |
| 4.5 | Tổng kết tháng | Tất cả nhân viên có 0 / 22 ngày công; khả năng cao là dữ liệu chấm công chưa được duyệt | Nhẹ |

---

## 5. Vấn đề UI/UX khác

| # | Vấn đề | Trang | Mức độ |
| --- | --- | --- | --- |
| 5.1 | Breadcrumb hiển thị đường dẫn kỹ thuật (ví dụ: "/ATTENDANCE/LOGS", "/PAYROLL/RUN") thay vì tên thân thiện | Toàn bộ | Nhẹ |
| 5.2 | Label "Từ /employees", "Từ /attendance/daily" trên Dashboard lộ endpoint API | Dashboard | Nhẹ |
| 5.3 | Phiếu lương hiển thị text API "POST /users" trong panel edit user | Admin | Nhẹ |
| 5.4 | Chấm công sidebar badge "Live" hiện bằng tiếng Anh | Sidebar | Nhẹ |
| 5.5 | Sidebar tagline "Attendance, payroll, contract" nên dịch sang tiếng Việt | Sidebar | Nhẹ |
| 5.6 | Nhật ký check-in: Mô tả "Khi backend hoàn thiện, các trường này vẫn bám dùng contract controller" — text dev note không nên hiển thị cho user | Nhật ký check-in | Trung bình |

---

## 6. Tóm tắt ưu tiên sửa

### Ưu tiên cao

1. Tái cấu trúc sidebar navigation theo Brief (3 module chính, mỗi module có Danh mục/Biến động/Báo cáo)
2. Sửa nút "Hợp đồng mới", View, Edit trên trang Hợp đồng (không hoạt động)
3. Triển khai popup/tab mới cho form tạo/sửa bản ghi

### Ưu tiên trung bình

1. Dịch toàn bộ text tiếng Anh sang tiếng Việt
2. Chuyển ALL CAPS labels sang Sentence case
3. Sửa tên báo cáo thiếu dấu tiếng Việt
4. Sửa cột "Phòng ban" và "Loại" hiển thị N/A trên trang Hợp đồng
5. Xóa dev notes khỏi UI

### Ưu tiên thấp

1. Sửa breadcrumb hiển thị tên thân thiện thay vì đường dẫn kỹ thuật
2. Dịch sidebar tagline
3. Ẩn endpoint API khỏi Dashboard labels

---

Ghi chú: báo cáo này tổng hợp từ lần kiểm tra giao diện ngày 29/03/2026.
