# Fujimart HRM Payroll - Huong dan chay va test local

Tai lieu nay danh cho user/tester muon tu chay app bang Docker, dang nhap, va test thu cong cac luong chinh cua Fujimart HRM Payroll.

## 1. Tong quan nhanh

He thong gom 3 service chinh:

| Service | Vai tro | URL/Port |
| --- | --- | --- |
| Frontend | Man hinh React/Vite de user thao tac | `http://127.0.0.1:5174/login` |
| Backend | Laravel API xu ly nghiep vu | `http://127.0.0.1:8001/api` |
| SQL Server | Database local trong Docker | `127.0.0.1:1433` |

Nen dung `127.0.0.1` thay cho `localhost` khi test tren Windows. Cach nay giup tranh loi login `Network Error` do may resolve `localhost` qua IPv6/WSL relay.

## 2. Yeu cau truoc khi chay

- Docker Desktop dang chay.
- PowerShell mo tai thu muc repo:

```powershell
cd D:\CODE\enterprise-payroll-erp
```

- Cac port can trong:
  - `5174`: Frontend Vite qua Docker Compose override.
  - `8001`: Backend API.
  - `1433`: SQL Server.

Kiem tra port `5174` neu frontend khong len:

```powershell
Get-NetTCPConnection -LocalPort 5174 -State Listen -ErrorAction SilentlyContinue |
  Select-Object LocalAddress,LocalPort,OwningProcess
```

Neu port dang bi app khac chiem, xem process:

```powershell
Get-CimInstance Win32_Process -Filter "ProcessId=<PID>" |
  Select-Object ProcessId,CommandLine
```

Chi dung `Stop-Process -Id <PID> -Force` khi ban chac chan process do khong can giu lai.

## 3. Chay app bang Docker

Chay lenh nay tu thu muc goc repo:

```powershell
docker compose up -d --build
```

Kiem tra container:

```powershell
docker compose ps
```

Mo app:

```text
http://127.0.0.1:5174/login
```

Neu moi clone repo hoac can reset lai app DB, chay:

```powershell
docker compose exec -T backend php artisan migrate:fresh --seed --force
```

Neu co file backup khach hang tai `docker/data/fujimart/DUNGNTN_HRM.bak`, restore source DB Fujimart:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\restore-fujimart-db.ps1
```

## 4. Tai khoan test

Tat ca tai khoan seed mac dinh dung password:

```text
password
```

| Username | Vai tro de test | Nen dung khi nao |
| --- | --- | --- |
| `admin01` | Admin | Test toan bo luong chinh va UAT tong hop |
| `hr01` | HR | Test nhan su, hop dong, cham cong |
| `payroll01` | Payroll | Test tinh luong, ky luong, phieu luong |
| `manager01` | Manager | Test goc nhin quan ly |
| `emp001` | Employee | Test goc nhin nhan vien |

Kiem tra backend login bang PowerShell:

```powershell
$login = Invoke-RestMethod -Method Post -Uri "http://127.0.0.1:8001/api/auth/login" `
  -ContentType "application/json" `
  -Body '{"username":"admin01","password":"password"}'

$login.data.user.username
```

Neu tra ve `admin01`, backend va user seed dang hoat dong.

## 5. Lenh Docker nen biet

Xem container dang chay:

```powershell
docker compose ps
```

Xem log backend:

```powershell
docker compose logs -f backend
```

Xem log frontend:

```powershell
docker compose logs -f frontend
```

Restart frontend khi UI chua cap nhat:

```powershell
docker compose restart frontend
```

Build lai rieng frontend:

```powershell
docker compose up -d --build frontend
```

Restart toan bo app:

```powershell
docker compose restart
```

Tat container nhung giu volume database:

```powershell
docker compose down
```

Chi reset volume database khi that su can lam sach SQL Server local:

```powershell
docker compose down -v
```

## 6. Lenh test tu dong

Backend full test bang SQLite:

```powershell
docker compose exec -T backend composer test:sqlite
```

Frontend lint:

```powershell
docker compose exec -T frontend npm run lint
```

Frontend build:

```powershell
docker compose exec -T frontend npm run build
```

Chay Playwright smoke test tu may local:

```powershell
cd D:\CODE\enterprise-payroll-erp\frontend
$env:E2E_BASE_URL = "http://127.0.0.1:5174"
npm run test:e2e:smoke
```

## 7. Checklist manual UAT quan trong

Dang nhap bang:

```text
admin01 / password
```

### A. Excel report

1. Mo `http://127.0.0.1:5174/reports`.
2. Export 3 bao cao thang `01/2026`:
   - Bang cham cong.
   - Bang thanh toan luong.
   - Phieu luong ca nhan.
3. Mo file Excel tai ve va kiem tra:
   - Du lieu nam trong template sheet.
   - Khong co sheet raw dump kieu `Sheet1`, `Data`, `Export`.
   - Header thang/nam dung.
   - Format, border, merged cells con giu.

### B. Salary grade

1. Mo `http://127.0.0.1:5174/reference/salary-levels`.
2. Mo mot thang luong.
3. Mo mot bac luong.
4. Them dong chi tiet khoan thu nhap:
   - `salary_type = BASE_SAL`
   - `amount = 6500000`
5. Luu, bam F5, kiem tra dong van con.
6. Sua amount thanh `7000000`, luu, F5 lai.

Neu sau reload du lieu van con, luong luu DB cua salary grade dang dung.

### C. Leave request

1. Mo `http://127.0.0.1:5174/attendance/leave-requests`.
2. Tao don:
   - `employee_code = HANPN`
   - `manager = MANAGER`
   - `request_date = 2026-01-12`
   - `to_date = 2026-01-12`
   - `working_hours = 4`
   - `working_days = 0.5`
   - `absence_type = 1`
   - `reason = Xin nghi buoi sang`
3. Luu, bam F5, kiem tra record moi con tren danh sach.
4. Sua `reason`, luu, F5, kiem tra noi dung moi con.

### D. Payroll run

1. Mo `http://127.0.0.1:5174/payroll/run`.
2. Chon thang `01`, nam `2026`.
3. Bam chay tinh luong/xem truoc theo UI.
4. O buoc cuoi, bam `Hoan tat va mo ky luong`.
5. Sau khi thanh cong, app phai tu chuyen sang:

```text
http://127.0.0.1:5174/payroll/periods
```

### E. Gui email phieu luong

1. Mo `http://127.0.0.1:5174/payroll/payslips/email`.
2. Nhap ngay tinh luong va bo loc neu can.
3. Bam `Gui email phieu luong`.
4. Kiem tra thong bao thanh cong/loi tren UI.

Luồng gửi mail hoạt động như sau:

- Frontend gọi API `/api/payroll/payslips/email`.
- Backend chạy stored procedure `dbo.usp_PayrollSlip`.
- Procedure lấy nhân viên từ `D30Payroll`, join qua bảng `employees`.
- Email người nhận lấy từ cột `employees.email`.
- SQL Server gửi mail bằng `msdb.dbo.sp_send_dbmail`.

Muốn xem tài khoản nào nhận mail, mở:

```text
http://127.0.0.1:5174/employees
```

Sau đó xem cột `Email` của nhân viên tương ứng. Nếu email là domain demo như `@erp.vn` hoặc `@fujimart.local`, có thể không có inbox thật. Môi trường production cần cấu hình SQL Server Database Mail thì mail mới gửi ra ngoài được.

### F. An debug UI

Mo lan luot:

```text
http://127.0.0.1:5174/reports
http://127.0.0.1:5174/payroll/payslips/email
http://127.0.0.1:5174/payroll/run
http://127.0.0.1:5174/attendance/logs
http://127.0.0.1:5174/attendance/shift-assignments
```

Kiem tra khong thay lai cac text/debug UI:

```text
JSON.stringify
<pre>
Dang thuc hien
Nguon
Loai
Trang thai
Muc luong
Grade debug
```

## 8. Khi gap loi thi bao cao nhu the nao

Dung mau nay de gui loi:

```text
Page:
Step:
Expected:
Actual:
Screenshot:
Console/API error:
```

Khong nen yeu cau sua tong the. Hay gom tung loi nho thanh tung ticket rieng de tranh lam hong cac luong da pass.

## 9. Ghi chu ve database

Local co 2 database SQL Server:

| Database | Vai tro |
| --- | --- |
| `enterprise_payroll_erp` | App DB Laravel: user, role, config, va cac bang app |
| `fujimart_hrm_source` | Source DB khach hang restore tu `.bak`: `D20Shift`, `D30AssignedShift`, `D30Attendance`, `D30Payroll`, stored procedures |

`php artisan migrate:fresh --seed --force` chi reset app DB. Source DB khach hang phai restore bang `scripts/restore-fujimart-db.ps1`.

Khong commit file `.bak` vao git.
