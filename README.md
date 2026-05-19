# Kiểm thử Fujimart HRM Payroll

Tài liệu này dành cho người muốn tự dựng app, restore database khách hàng, chạy test tự động và kiểm tra thủ công các màn Fujimart HRM Payroll.

## Yêu cầu trước khi chạy

- Docker Desktop đang chạy.
- PowerShell chạy từ thư mục gốc repo: `D:\CODE\enterprise-payroll-erp`.
- File backup SQL Server tồn tại tại `docker/data/fujimart/DUNGNTN_HRM.bak`.
- Port local cần trống:
  - `8001`: Backend API.
  - `5173`: Frontend Docker dev server.
  - `4173`: Playwright preview server.
  - `1433`: SQL Server Docker.

## Hai database cần hiểu rõ

Hệ thống local dùng 2 database SQL Server khác nhau:

| Database | Vai trò | Lệnh nào tác động |
| --- | --- | --- |
| `enterprise_payroll_erp` | App DB do Laravel migrate/seed tạo, dùng cho user, role, các bảng app còn lại | `php artisan migrate:fresh --seed --force` |
| `fujimart_hrm_source` | Source DB khách hàng restore từ `.bak`, chứa `D20Shift`, `D30AssignedShift`, `D30Attendance`, `D30Payroll`, stored procedures | `scripts/restore-fujimart-db.ps1` |

`migrate:fresh` chỉ nên reset app DB `enterprise_payroll_erp`; không dùng nó để reset source DB khách hàng. Source DB khách hàng được restore bằng script riêng.

## Cách chạy toàn bộ vòng kiểm tra

Chạy lần lượt các lệnh sau từ thư mục gốc của repo `D:\CODE\enterprise-payroll-erp`.

```powershell
docker compose up -d --build
```

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\restore-fujimart-db.ps1
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
$env:E2E_BASE_URL = "http://127.0.0.1:4173"
npm run test:e2e:smoke
```

Ghi chú về URL: trên một số máy Windows, `localhost` có thể resolve qua IPv6/WSL relay và làm login bị `Network Error`. Nếu gặp lỗi đó, dùng `127.0.0.1` thay cho `localhost`.

## Sửa lỗi login `Network Error` trên local

Triệu chứng thường gặp: mở `http://localhost:5174/login`, nhập `admin01` / `password`, nhưng frontend báo `Network Error`.

Nguyên nhân hay gặp là browser đang mở frontend bằng `localhost`, trong khi backend API ổn định hơn khi gọi qua `127.0.0.1`. Hãy dùng cùng một kiểu host cho local: ưu tiên `127.0.0.1`.

Nếu chạy bằng Docker Compose, dùng đúng URL này:

```powershell
docker compose up -d --build
```

```text
http://127.0.0.1:5173/login
```

Nếu tự chạy Vite trong thư mục `frontend` và Vite báo đang chạy ở port `5174`, dừng frontend cũ bằng `Ctrl+C`, rồi chạy lại:

```powershell
cd D:\CODE\enterprise-payroll-erp\frontend
$env:VITE_API_BASE_URL = "http://127.0.0.1:8001/api"
npm run dev -- --host 127.0.0.1 --port 5174
```

Sau đó mở:

```text
http://127.0.0.1:5174/login
```

Kiểm tra backend trước khi thử login lại:

```powershell
$login = Invoke-RestMethod -Method Post -Uri "http://127.0.0.1:8001/api/auth/login" `
  -ContentType "application/json" `
  -Body '{"username":"admin01","password":"password"}'

$login.data.user.username
```

Nếu lệnh trên trả về `admin01`, backend đã chạy đúng; lỗi còn lại thường là frontend đang dùng sai `VITE_API_BASE_URL` hoặc bạn vẫn đang mở URL `localhost`.

Nếu một bước bị lỗi, sửa đúng tầng đang hỏng, build lại Docker nếu cần, chạy lại đúng bước nhỏ nhất để xác nhận, rồi mới quay lại toàn bộ vòng kiểm tra.

## Chạy test nhỏ trước khi chạy full loop

Khi đang sửa code, nên chạy test nhỏ nhất trước:

```powershell
docker compose exec -T backend php artisan test --filter FujimartSourceSchemaTest
```

```powershell
docker compose exec -T backend php artisan test --filter Reference
```

```powershell
docker compose exec -T backend php artisan test --filter Attendance
```

```powershell
docker compose exec -T backend php artisan test --filter Payroll
```

Sau khi các test nhỏ pass, chạy full backend:

```powershell
docker compose exec -T backend composer test:sqlite
```

## Tài khoản smoke test

- `admin01` / `password`: đi toàn bộ luồng chính.
- `hr01` / `password`: test đăng nhập vai trò HR.
- `payroll01` / `password`: test đăng nhập vai trò payroll.
- `manager01` / `password`: test đăng nhập vai trò quản lý.
- `emp001` / `password`: test đăng nhập nhân viên được seed từ dữ liệu Fujimart.

## Kiểm tra nhanh app đang dùng Fujimart source schema

Sau khi restore DB và đăng nhập được, các API dưới đây phải đọc source object Fujimart, không chỉ đọc bảng migrate Laravel.

```powershell
$login = Invoke-RestMethod -Method Post -Uri "http://127.0.0.1:8001/api/auth/login" `
  -ContentType "application/json" `
  -Body '{"username":"admin01","password":"password"}'

$headers = @{ Authorization = "Bearer $($login.data.token)" }
```

Shift master phải trả `source_table = D20Shift`:

```powershell
(Invoke-RestMethod -Uri "http://127.0.0.1:8001/api/reference/shifts" -Headers $headers).data |
  Select-Object -First 3 code,name,source_table
```

Payroll parameters phải có 2 nhóm từ 2 view:

```powershell
$params = Invoke-RestMethod -Uri "http://127.0.0.1:8001/api/reference/payroll-parameters" -Headers $headers
$params.data.value_parameters | Select-Object -First 3 code,name,source_view
$params.data.salary_types | Select-Object -First 3 code,name,source_view
```

Shift assignment phải trả `source_table = D30AssignedShift` và dùng business key `EmployeeCode` + `ShiftCode`:

```powershell
(Invoke-RestMethod -Uri "http://127.0.0.1:8001/api/attendance/shift-assignments" -Headers $headers).data |
  Select-Object -First 3 employee_code,shift_code,source_table
```

Kỳ vọng:

- `source_table` của shift là `D20Shift`.
- `source_view` của nhóm tham số giá trị là `vD20PayrollPara_ValuePara`.
- `source_view` của nhóm loại lương/thưởng là `vD20PayrollPara_SalaryType`.
- `source_table` của phân ca là `D30AssignedShift`.

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

1. Mở `http://127.0.0.1:5173/login`.
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
- Firecrawl CLI hiện không scrape trực tiếp được URL local như `http://127.0.0.1:5173` vì tool yêu cầu URL có top-level domain. Dùng Playwright hoặc in-app browser cho kiểm thử UI local.
