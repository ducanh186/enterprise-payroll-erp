# Kiểm thử E2E Fujimart HRM


## Cách chạy toàn bộ vòng kiểm tra

Chạy lần lượt các lệnh sau từ thư mục gốc của repo `D:\CODE\enterprise-payroll-erp`.

```powershell
docker compose up -d --build
```

```powershell
.\scripts\restore-fujimart-db.ps1
```

```powershell
docker compose exec -T backend php artisan migrate:fresh --seed --force
```

```powershell
docker compose exec -T backend composer test:sqlite
```

```powershell
docker compose exec -T frontend npm run build
```

```powershell
docker compose exec -T frontend npm run lint
```

```powershell
cd frontend
$env:E2E_BASE_URL = "http://localhost:5173"
npm run test:e2e:smoke
```

Nếu một bước bị lỗi, sửa đúng tầng đang hỏng, build lại Docker nếu cần, chạy lại đúng bước nhỏ nhất để xác nhận, rồi mới quay lại toàn bộ vòng kiểm tra.

## Tài khoản smoke test

- `admin01` / `password`: đi toàn bộ luồng chính.
- `hr01` / `password`: test đăng nhập vai trò HR.
- `payroll01` / `password`: test đăng nhập vai trò payroll.
- `manager01` / `password`: test đăng nhập vai trò quản lý.
- `emp001` / `password`: test đăng nhập nhân viên được seed từ dữ liệu Fujimart.

## Cập nhật dữ liệu demo qua Docker

Dữ liệu demo được seed để có tối thiểu 30 nhân viên, 30 người phụ thuộc, bảng công tháng 01/2026 và bảng lương tháng 01/2026. Chạy các lệnh này khi cần làm mới dữ liệu test:

```powershell
docker compose up -d --build
```

```powershell
docker compose exec -T backend php artisan migrate:fresh --seed --force
```

Nếu chỉ muốn bổ sung lại bộ dữ liệu 30 record mà không reset toàn bộ database:

```powershell
docker compose exec -T backend php artisan db:seed --class=DemoVolumeSeeder --force
```

File `.bak` trong thư mục `docker/data/fujimart/` là nguồn SQL Server của khách hàng. Khi chưa restore được `.bak`, app vẫn dùng seed Laravel để test nhanh các màn HRM. File Excel check-in/out import trên UI cần có cột `Mã NV` và `Thời gian`; các header tương đương như `EmployeeCode`, `Code`, `CheckTime` cũng được nhận.

## Ánh xạ yêu cầu sang UI test

| Yêu cầu | UI test trong `frontend/e2e/app-smoke.spec.ts` | Thao tác trên UI | Kết quả cần thấy |
| --- | --- | --- | --- |
| Hiển thị `Fujimart HRM` và logo Fujimart | `admin can use Fujimart HRM customer flows...` | Đăng nhập `admin01`, vào dashboard | Web title là `Fujimart HRM`, sidebar chỉ hiển thị logo Fujimart |
| Sidebar theo BFD 3 cấp, ẩn từ `Quản lý` | Step `Sidebar exposes the Fujimart BFD structure` | Mở các nhóm `Nhân sự & HĐLĐ`, `Chấm công`, `Tính lương` | Có các liên kết đúng nghiệp vụ, không còn nhãn `Quản lý` |
| Chi tiết nhân viên có tab người phụ thuộc | Step `Employee detail includes dependents tab` | Vào `/employees`, mở chi tiết nhân viên, chọn `Người phụ thuộc` | Tab và nội dung người phụ thuộc hiển thị đúng |
| Tham số lương có 2 view của khách hàng | Step `Payroll parameters expose Fujimart source views` | Vào `/payroll/parameters`, bấm 2 tab | Hiển thị `Tham số giá trị`, `Loại thu nhập, lương thưởng` và nguồn view tương ứng |
| Bảng công thời gian D30Attendance sửa được | Manual/browser-use | Vào `/attendance`, chọn ngày 01/2026, bấm `Sửa` ở dòng chấm công | Lưu qua `PUT /attendance/daily/{id}`, reload vẫn thấy dữ liệu mới |
| Bảng lương D30Payroll/D30PayrollDetail sửa được | Manual/browser-use | Vào `/payroll/payslips`, chọn phiếu, bấm `Sửa` | Lưu đầu phiếu và chi tiết qua API, trước bước gửi email phiếu lương |
| Import file Excel check-in/check-out của khách hàng | Step `Import customer check-in/out Excel` | Vào `/attendance/logs`, tải lên `Data checkinout.xlsx`, bấm `Import Excel` | Hiển thị `Import hoàn tất` và số dòng đã nhập lớn hơn 0 |
| Chạy tính công | Step `Run attendance procedure` | Vào `/attendance/summary`, chọn tháng 1 năm 2026, bấm tính lại | Hiển thị thông báo đã chạy tính công, không có API 4xx/5xx |
| Chạy tính lương | Step `Run payroll procedure` | Vào `/payroll/run`, chọn tháng 1 năm 2026, bấm `Chạy tính lương` | Hiển thị phần xem trước nhân viên/bảng lương, không có API 4xx/5xx |
| Xem trước/xuất Bảng chấm công | Step `Preview and export Bang cham cong` | Vào báo cáo `FUJIMART_ATTENDANCE_REPORT`, bấm `Xem trước`, `Xuất báo cáo`, `Tải file` | Phần xem trước có JSON và tải được file `.xlsx` |
| Xem trước/xuất Bảng thanh toán lương | Step `Preview and export Bang thanh toan luong...` | Vào báo cáo `FUJIMART_PAYROLL_REPORT`, bấm `Xem trước`, `Xuất báo cáo`, `Tải file` | Phần xem trước có JSON và tải được file `.xlsx` |
| Xem trước/xuất Phiếu lương cá nhân | Step `Preview and export Phieu luong ca nhan` | Vào báo cáo `FUJIMART_PAYROLL_SLIP`, nhập `NV001`, export | Phần xem trước có JSON và tải được file `.xlsx` |
| Token cũ bị từ chối thì quay về login | Test `stale stored session is cleared...` | Mở `/payroll/run` với stale token, bấm thao tác bất kỳ | Ứng dụng quay về `/login`, không để lỗi console hoặc API làm treo UI |
| Đăng nhập các vai trò đã seed | Test `seed user ... can log in through the UI` | Đăng nhập từng user đã seed | Mỗi vai trò đều vào được dashboard |

Ghi chú: các bước import -> tính công -> tính lương -> báo cáo phụ thuộc dữ liệu liên tiếp, nên trong Playwright nên gom thành một user journey và chia bằng `test.step` theo đúng nghiệp vụ. Nếu thêm yêu cầu độc lập thì tạo `test(...)` riêng. Nếu yêu cầu sau cần dữ liệu từ bước trước, giữ nó trong `test.step(...)` với tên bước đủ rõ.

## Checklist kiểm thử thủ công bằng browser-use

Sau khi Playwright đã pass, mở thêm browser-use hoặc in-app browser để kiểm tra lại bằng mắt:

1. Mở `http://localhost:5173/login`.
2. Đăng nhập `admin01` / `password`.
3. Xác nhận web title là `Fujimart HRM`, sidebar chỉ có logo Fujimart và không còn nhãn `Quản lý`.
4. Mở `Nhân sự & HĐLĐ` -> `Hồ sơ cán bộ nhân viên` -> chi tiết -> `Người phụ thuộc`.
5. Mở `Chấm công` -> `Dữ liệu thời gian vào - ra`, tải lên file Excel mẫu, bấm `Import Excel`.
6. Mở `Bảng chấm công`, chọn `01/2026`, bấm tính lại.
7. Mở `Tiền lương` -> `Tham số lương`, bấm cả 2 tab view.
8. Mở `Trình chạy bảng lương`, chọn `01/2026`, bấm `Chạy tính lương`.
9. Mở `Báo cáo`, xem trước/xuất 3 báo cáo Fujimart và tải file `.xlsx`.
10. Mở DevTools hoặc console nếu có thể; smoke test không nên còn page error, console error hay API 4xx/5xx.

## Tệp kỳ vọng và ranh giới phạm vi

- Không commit file `.bak` vào git.
- File Excel import mẫu nằm trong runtime storage và chỉ dùng cho test.
- Logo và các Excel template được commit vì runtime cần chúng để hiển thị và export.
- SQL Server Docker cục bộ phải có `DUNGNTN_HRM.bak` trong `docker/data/fujimart/` trước khi restore.
- Report phiếu lương khi preview/export gọi `dbo.usp_PayrollSlip` với `_SendEmail = 0` trong smoke test để không phụ thuộc vào cấu hình Database Mail của máy local.
