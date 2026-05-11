# Fujimart HRM E2E Test Guide

## Muc tieu

Tai lieu nay dung de test lai scope Fujimart HRM theo Google Doc "The 3" va database khach hang. Nguyen tac la moi requirement quan trong phai co mot UI check ro rang: thao tac tren man hinh, ket qua mong doi, va lenh de chay lai.

## Source of truth

- Google Doc: "The 3" va bang "Chu thich bang".
- Google Drive: folder `HRM_Fujimart`, gom logo, sample Excel, UML.
- SQL Server backup: `docker/data/fujimart/DUNGNTN_HRM.bak`, restore thanh database `fujimart_hrm_source`.
- Neu Google Doc va restored DB khac nhau, uu tien restored DB va ghi lai mismatch khi bao cao.

## Cach chay loop day du

Chay cac lenh tu root repo `D:\CODE\enterprise-payroll-erp`.

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

Neu mot check fail: sua dung layer dang fail, rebuild Docker, chay lai check nho nhat truoc, sau do chay lai full loop.

## Tai khoan smoke

- `admin01` / `password`: dung de test full flow.
- `hr01` / `password`: test login role HR.
- `payroll01` / `password`: test login role payroll.
- `manager01` / `password`: test login role manager.
- `emp001` / `password`: test login employee seeded tu Fujimart.

## Mapping requirement -> UI test

| Requirement | UI test trong `frontend/e2e/app-smoke.spec.ts` | Thao tac UI | Expected result |
| --- | --- | --- | --- |
| Hien thi `Fujimart HRM` va logo Fujimart | `admin can use Fujimart HRM customer flows...` | Login `admin01`, vao dashboard | Title la `Fujimart HRM`, logo alt `Fujimart` hien thi |
| Sidebar theo BFD 3 cap, an tu "Quan ly" | Step `Sidebar exposes the Fujimart BFD structure` | Mo nhom `Nhan su & HDLD`, `Cham cong`, `Tinh luong` | Co cac link dung nghiep vu, khong co label `Quan ly` |
| Employee detail co tab nguoi phu thuoc | Step `Employee detail includes dependents tab` | Vao `/employees`, mo chi tiet nhan vien, chon `Nguoi phu thuoc` | Tab va noi dung nguoi phu thuoc hien thi |
| Payroll parameter co 2 view khach hang | Step `Payroll parameters expose Fujimart source views` | Vao `/payroll/parameters`, click 2 tab | Hien `vD20PayrollPara_ValuePara` va `vD20PayrollPara_SalaryType` |
| Import Excel check-in/out cua khach | Step `Import customer check-in/out Excel` | Vao `/attendance/logs`, upload `Data checkinout.xlsx`, bam `Import Excel` | Hien `Import hoan tat` va so dong da nhap lon hon 0 |
| Chay tinh cong | Step `Run attendance procedure` | Vao `/attendance/summary`, chon thang 1 nam 2026, bam tinh lai | Hien thong bao da chay tinh cong, khong co API 4xx/5xx |
| Chay tinh luong | Step `Run payroll procedure` | Vao `/payroll/run`, chon thang 1 nam 2026, bam `Chay tinh luong` | Hien preview nhan vien/bang luong, khong co API 4xx/5xx |
| Preview/export Bang cham cong | Step `Preview and export Bang cham cong` | Vao report `FUJIMART_ATTENDANCE_REPORT`, bam `Xem truoc`, `Xuat bao cao`, `Tai file` | Preview co JSON, download file `.xlsx` |
| Preview/export Bang thanh toan luong | Step `Preview and export Bang thanh toan luong...` | Vao report `FUJIMART_PAYROLL_REPORT`, bam `Xem truoc`, `Xuat bao cao`, `Tai file` | Preview co JSON, download file `.xlsx` |
| Preview/export Phieu luong ca nhan | Step `Preview and export Phieu luong ca nhan` | Vao report `FUJIMART_PAYROLL_SLIP`, nhap `NV001`, export | Preview co JSON, download file `.xlsx` |
| Token cu bi reject thi quay ve login | Test `stale stored session is cleared...` | Mo `/payroll/run` voi stale token, bam action | App ve `/login`, khong de loi console/API treo UI |
| Login cac role seeded | Test `seed user ... can log in through the UI` | Login tung user seeded | Moi role vao duoc dashboard |

Ghi chu: cac feature import -> tinh cong -> tinh luong -> report phu thuoc du lieu lien tiep, nen Playwright gom vao mot user journey va dung `test.step` nhu mot UI test cap requirement. Khi them requirement doc lap, tao mot `test(...)` rieng; khi requirement can du lieu tu buoc truoc, tao mot `test.step(...)` ten ro nghiep vu.

## Checklist browser-use manual

Sau khi Playwright pass, dung browser-use/in-app browser de smoke bang mat nguoi:

1. Mo `http://localhost:5173/login`.
2. Login `admin01` / `password`.
3. Xac nhan header/sidebar hien `Fujimart HRM`, co logo, khong co label `Quan ly`.
4. Mo `Nhan su & HDLD` -> `Ho so can bo nhan vien` -> chi tiet -> `Nguoi phu thuoc`.
5. Mo `Cham cong` -> `Du lieu thoi gian vao - ra`, upload sample Excel, bam `Import Excel`.
6. Mo `Bang cham cong`, chon `01/2026`, bam tinh lai.
7. Mo `Tinh luong` -> `Bo cong thuc va tham so luong`, click ca 2 tab view.
8. Mo `Trinh chay bang luong`, chon `01/2026`, bam `Chay tinh luong`.
9. Mo `Bao cao`, preview/export 3 report Fujimart va tai file `.xlsx`.
10. Mo DevTools/console neu co the; khong duoc con page error, console error, hoac API 4xx/5xx trong smoke.

## Expected files and boundaries

- `.bak` khong commit vao git.
- Sample import Excel nam trong runtime storage; chi dung de test.
- Logo va Excel templates duoc commit vi runtime can chung de hien thi/export.
- Local Docker SQL Server can co `DUNGNTN_HRM.bak` trong `docker/data/fujimart/` truoc khi restore.
- Report payslip preview/export goi `dbo.usp_PayrollSlip` voi `_SendEmail = 0` trong smoke de khong phu thuoc cau hinh Database Mail cua may local.
