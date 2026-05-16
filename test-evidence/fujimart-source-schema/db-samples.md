# Fujimart DB Sample Evidence

## SELECT TOP 10 * FROM D20Shift ORDER BY Code;

```text
Id ParentId IsGroup IsActive CreatedBy CreatedAt ModifiedBy ModifiedAt Code Name Description StartTime StartTimeValid1 StartTimeValid2 EndTime EndTimeValid1 EndTimeValid2 ShiftBreak StartShiftBreak EndShiftBreak WorkDay WorkingHours ShiftBreakMins StartWorkingNightTime EndWorkingNightTime ShiftMeal MinHourMeal StartBreakTimeValid1 StartBreakTimeValid2 EndBreakTimeValid1 EndBreakTimeValid2 IsCheckIn IsCheckOut
-- -------- ------- -------- --------- --------- ---------- ---------- ---- ---- ----------- --------- --------------- --------------- ------- ------------- ------------- ---------- --------------- ------------- ------- ------------ -------------- --------------------- ------------------- --------- ----------- -------------------- -------------------- ------------------ ------------------ --------- ----------
14 -1 0 1 -1 2026-04-20 16:52:27.300 -1 2026-04-20 16:52:27.300 CAHC Ca hành chính (Áp dụng cho khối VP)  2026-01-07 08:00:00.000 2026-01-07 06:00:00.000 2026-01-07 10:00:00.000 2026-01-07 17:00:00.000 2026-01-07 15:00:00.000 2026-01-07 20:00:00.000 1 2026-01-07 12:00:00.000 2026-01-07 13:00:00.000 1.00 8.00 60 NULL NULL 1 7.50 NULL NULL NULL NULL 1 1
15 -1 0 1 -1 2026-04-20 16:52:27.300 -1 2026-04-20 16:52:27.300 CAHC_BGD Ca hành chính (Áp dụng cho BGĐ và HĐQT)  2026-01-07 08:00:00.000 NULL NULL 2026-01-07 17:00:00.000 NULL NULL 1 2026-01-07 12:00:00.000 2026-01-07 13:00:00.000 1.00 8.00 60 NULL NULL 1 7.50 NULL NULL NULL NULL 0 0
12 -1 0 1 -1 2026-04-20 16:52:27.300 -1 2026-04-20 16:52:27.300 FTTN01 Ca full-time 01 (Thu ngân) Ca 01 áp dụng cho CBNV thuộc khối thu ngân 2025-12-31 07:30:00.000 2025-12-31 06:00:00.000 2025-12-31 09:00:00.000 2025-12-31 16:30:00.000 2025-12-31 15:00:00.000 2025-12-31 18:00:00.000 1 2025-12-31 11:30:00.000 2025-12-31 12:30:00.000 1.00 8.00 0 NULL NULL 1 7.00 2025-12-31 11:30:00.000 2025-12-31 13:30:00.000 2025-12-31 11:30:00.000 2025-12-31 15:00:00.000 1 1
13 -1 0 1 -1 2026-04-20 16:52:27.300 -1 2026-04-20 16:52:27.300 FTTN02 Ca full-time 02 (Thu ngân) Ca 02 áp dụng cho CBNV thuộc khối thu ngân 2025-12-31 15:30:00.000 2025-12-31 14:00:00.000 2025-12-31 16:00:00.000 2025-12-31 22:30:00.000 2025-12-31 21:00:00.000 2025-12-31 23:30:00.000 0 NULL NULL 1.00 8.00 0 2025-12-31 22:00:00.000 2025-12-31 06:00:00.000 1 7.00 NULL NULL NULL NULL 1 1
9 -1 0 1 -1 2026-04-20 16:52:27.300 -1 2026-04-20 16:52:27.300 PTTN01 Ca part-time 01 (Thu ngân) Ca nửa ngày 01 áp dụng cho CBNV part-time thuộc khối thu ngân 2025-12-31 07:30:00.000 2025-12-31 06:00:00.000 2025-12-31 08:00:00.000 2025-12-31 12:30:00.000 2025-12-31 11:30:00.000 2025-12-31 14:00:00.000 0 NULL NULL .50 5.00 0 NULL NULL 0 .00 NULL NULL NULL NULL 1 1
10 -1 0 1 -1 2026-04-20 16:52:27.300 -1 2026-04-20 16:52:27.300 PTTN02 Ca part-time 02 (Thu ngân) Ca nửa ngày 02 áp dụng cho CBNV part-time thuộc khối thu ngân 2025-12-31 12:30:00.000 2025-12-31 11:00:00.000 2025-12-31 13:00:00.000 2025-12-31 17:30:00.000 2025-12-31 16:30:00.000 2025-12-31 19:00:00.000 0 NULL NULL .50 5.00 0 NULL NULL 0 .00 NULL NULL NULL NULL 1 1
11 -1 0 1 -1 2026-04-20 16:52:27.300 -1 2026-04-20 16:52:27.300 PTTN03 Ca part-time 03 (Thu ngân) Ca nửa ngày 03 áp dụng cho CBNV part-time thuộc khối thu ngân 2025-12-31 17:30:00.000 2025-12-31 16:00:00.000 2025-12-31 18:00:00.000 2025-12-31 22:30:00.000 2025-12-31 21:30:00.000 2025-12-31 23:00:00.000 0 NULL NULL .50 5.00 0 2025-12-31 22:00:00.000 2025-12-31 06:00:00.000 0 .00 NULL NULL NULL NULL 1 1

(7 rows affected)
```

## SELECT TOP 10 * FROM vD20PayrollPara_ValuePara;

```text
Id Parameter Name Description EffectiveDate Type Amount IsActive CreatedBy CreatedAt ModifiedBy ModifiedAt timestamp
-- --------- ---- ----------- ------------- ---- ------ -------- --------- --------- ---------- ---------- ---------
1 _AttendanceAllowance Mức phụ cấp chuyên cần Tham số lương: Mức phụ cấp chuyên cần NULL 1 150000.00 1 -1 2026-05-05 17:12:00 -1 2026-05-05 17:12:00 0x0000000000007B88
2 _BaseSalaryPerMonth Mức lương cơ sở Tham số lương: Mức lương cơ sở NULL 1 2340000.00 1 -1 2026-05-05 17:12:00 -1 2026-05-05 17:12:00 0x0000000000007B89
3 _DaysPerMonth Số ngày công chuẩn trong 1 tháng (ngày) Tham số lương: Số ngày công chuẩn trong 1 tháng (ngày) NULL 1 24.00 1 -1 2026-05-05 17:12:00 -1 2026-05-05 17:12:00 0x0000000000007B8A
19 _HoursPerDay Số giờ chuẩn trong 1 ngày (giờ) Tham số lương: Số giờ chuẩn trong 1 ngày (giờ) NULL 1 8.00 1 -1 2026-05-05 17:12:00 -1 2026-05-05 17:12:00 0x0000000000007B9A
32 _Min_Sal1 Mức lương tối thiểu vùng I Tham số lương: Mức lương tối thiểu vùng I NULL 1 4420000.00 1 -1 2026-05-05 17:12:00 -1 2026-05-05 17:12:00 0x0000000000007BA7
34 _Min_Sal2 Mức lương tối thiểu vùng II Tham số lương: Mức lương tối thiểu vùng II NULL 1 3920000.00 1 -1 2026-05-05 17:12:00 -1 2026-05-05 17:12:00 0x0000000000007BA9
36 _Min_Sal3 Mức lương tối thiểu vùng III Tham số lương: Mức lương tối thiểu vùng III NULL 1 3430000.00 1 -1 2026-05-05 17:12:00 -1 2026-05-05 17:12:00 0x0000000000007BAB
38 _Min_Sal4 Mức lương tối thiểu vùng IV Tham số lương: Mức lương tối thiểu vùng IV NULL 1 3070000.00 1 -1 2026-05-05 17:12:00 -1 2026-05-05 17:12:00 0x0000000000007BAD
40 _MinResidenceDay Số ngày làm việc tối thiểu tại VN của người nước ngoài để xác định là Cá nhân cư trú Tham số lương: Số ngày làm việc tối thiểu tại VN của người nước ngoài để xác định là Cá nhân cư trú NULL 1 183.00 1 -1 2026-05-05 17:12:00 -1 2026-05-05 17:12:00 0x0000000000007BAF
41 _PercentofAccidentIns Phần trăm BHTNLD DN trả (%) Tham số lương: Phần trăm BHTNLD DN trả (%) NULL 1 .50 1 -1 2026-05-05 17:12:00 -1 2026-05-05 17:12:00 0x0000000000007BB0

(10 rows affected)
```

## SELECT TOP 10 * FROM vD20PayrollPara_SalaryType;

```text
Id Parameter Name Description EffectiveDate Type Amount IsActive CreatedBy CreatedAt ModifiedBy ModifiedAt timestamp
-- --------- ---- ----------- ------------- ---- ------ -------- --------- --------- ---------- ---------- ---------
86 ATT_ALLOWANCE Tiền phụ cấp thu hút Tham số lương: Tiền phụ cấp thu hút NULL 9 .00 1 -1 2026-05-05 17:12:00 -1 2026-05-05 17:12:00 0x0000000000007CB5
89 BASE_NET_SAL Lương NET = Mức lương NET * Lương cơ bản * Số ngày công * % Thử việc / Số ngày công tiêu chuẩn Tham số lương: Lương NET = Mức lương NET * Lương cơ bản * Số ngày công * % Thử việc / Số ngày công tiêu chuẩn NULL 9 .00 1 -1 2026-05-05 17:12:00 -1 2026-05-05 17:12:00 0x0000000000007CB6
91 BASE_SAL Tiền lương cơ bản = Lương cơ bản * Số ngày công * % Thử việc / Số ngày công tiêu chuẩn Tham số lương: Tiền lương cơ bản = Lương cơ bản * Số ngày công * % Thử việc / Số ngày công tiêu chuẩn NULL 9 .00 1 -1 2026-05-05 17:12:00 -1 2026-05-05 17:12:00 0x0000000000007CB7
102 OTHER_ALLOWANCE Tiền phụ cấp khác Tham số lương: Tiền phụ cấp khác NULL 9 .00 1 -1 2026-05-05 17:12:00 -1 2026-05-05 17:12:00 0x0000000000007CB8
105 POS_ALLOWANCE Tiền phụ cấp chức vụ Tham số lương: Tiền phụ cấp chức vụ NULL 9 .00 1 -1 2026-05-05 17:12:00 -1 2026-05-05 17:12:00 0x0000000000007CB9
107 RESP_ALLOWANCE Tiền phụ cấp trách nhiệm Tham số lương: Tiền phụ cấp trách nhiệm NULL 9 .00 1 -1 2026-05-05 17:12:00 -1 2026-05-05 17:12:00 0x0000000000007CBA
109 SEN_ALLOWANCE Tiền phụ cấp thâm niên Tham số lương: Tiền phụ cấp thâm niên NULL 9 .00 1 -1 2026-05-05 17:12:00 -1 2026-05-05 17:12:00 0x0000000000007CBB
112 SHIFT_ALLOWANCE Tiền phụ cấp ăn ca Tham số lương: Tiền phụ cấp ăn ca NULL 9 .00 1 -1 2026-05-05 17:12:00 -1 2026-05-05 17:12:00 0x0000000000007CBC
115 UnionFees Phí công đoàn Tham số lương: Phí công đoàn NULL 9 .00 1 -1 2026-05-05 17:12:00 -1 2026-05-05 17:12:00 0x0000000000007CBE

(9 rows affected)
```

## SELECT TOP 10 * FROM D30AssignedShift ORDER BY StartDate DESC;

```text
Id IsActive CreatedBy CreatedAt ModifiedBy ModifiedAt Date EmployeeCode ShiftCode StartDate EndDate IncludeMon IncludeTue IncludeWed IncludeThu IncludeFri IncludeSat IncludeSun Description AssignId
-- -------- --------- --------- ---------- ---------- ---- ------------ --------- --------- ------- ---------- ---------- ---------- ---------- ---------- ---------- ---------- ----------- --------
4 1 -1 2026-04-20 17:10:39.137 -1 2026-04-20 17:10:39.137 2026-01-01 GIANGTA CAHC_BGD 2026-01-01 2026-01-31 1 1 1 1 1 0 0 Phân ca hành chính Ban Giám đốc tháng 01/2026 cho nhân viên GIANGTA 0004AS
3 1 -1 2026-04-20 17:10:39.137 -1 2026-04-20 17:10:39.137 2026-01-01 HANPN FTTN01 2026-01-01 2026-01-31 1 1 1 1 1 1 1 Phân ca số 1 thu ngân tháng 01/2026 cho nhân viên HANPN 0003AS
2 1 -1 2026-04-20 17:10:39.137 -1 2026-04-20 17:10:39.137 2026-01-01 DUNGNTN CAHC 2026-01-01 2026-01-31 1 1 1 1 1 0 0 Phân ca hành chính khối văn phòng tháng 01/2026 cho nhân viên DUNGNTN 0002AS

(3 rows affected)
```

## SELECT TOP 10 * FROM D30Attendance ORDER BY Date DESC;

```text
Id DocId Date AssignId WorkingHours WorkingDays ExcludeDays ExcludeHours WorkNightHours ShiftMeal IsActive CreatedBy CreatedAt ModifiedBy ModifiedAt PaidLeaveDays UnpaidLeaveDays
-- ----- ---- -------- ------------ ----------- ----------- ------------ -------------- --------- -------- --------- --------- ---------- ---------- ------------- ---------------
1447 000001447ATT 2026-01-31 0003AS .00 .00 .00 .00 .00 0 1 -1 2026-05-16 14:05:30.470 -1 2026-05-16 14:05:30.470 .00 1.00
1366 000001366ATT 2026-01-30 0002AS 8.00 1.00 .00 .00 .00 1 1 -1 2026-05-04 17:03:20.073 -1 2026-05-04 17:03:20.073 .00 .00
1446 000001446ATT 2026-01-30 0003AS 8.00 1.00 .00 .00 .00 1 1 -1 2026-05-16 14:05:30.470 -1 2026-05-16 14:05:30.470 .00 .00
1468 000001468ATT 2026-01-30 0004AS 8.00 1.00 .00 .00 .00 1 1 -1 2026-05-16 14:05:30.470 -1 2026-05-16 14:05:30.470 .00 .00
1365 000001365ATT 2026-01-29 0002AS 8.00 1.00 .00 .00 .00 1 1 -1 2026-05-04 17:03:20.073 -1 2026-05-04 17:03:20.073 .00 .00
1445 000001445ATT 2026-01-29 0003AS 8.00 1.00 .00 .00 .00 1 1 -1 2026-05-16 14:05:30.470 -1 2026-05-16 14:05:30.470 .00 .00
1467 000001467ATT 2026-01-29 0004AS 8.00 1.00 .00 .00 .00 1 1 -1 2026-05-16 14:05:30.470 -1 2026-05-16 14:05:30.470 .00 .00
1364 000001364ATT 2026-01-28 0002AS 8.00 1.00 .00 .00 .00 1 1 -1 2026-05-04 17:03:20.073 -1 2026-05-04 17:03:20.073 .00 .00
1444 000001444ATT 2026-01-28 0003AS 8.00 1.00 .00 .00 .00 1 1 -1 2026-05-16 14:05:30.470 -1 2026-05-16 14:05:30.470 .00 .00
1466 000001466ATT 2026-01-28 0004AS 8.00 1.00 .00 .00 .00 1 1 -1 2026-05-16 14:05:30.470 -1 2026-05-16 14:05:30.470 .00 .00

(10 rows affected)
```

## SELECT TOP 10 * FROM D30Payroll ORDER BY Date DESC;

```text
Id BranchCode RowId Date DeptCode EmployeeCode WorkArea Trained NetCalc GrossSalary Probationary ProbationaryRate SalaryInsurance OvertimeSalary OffsetSalary OVTTaxableIncome BonusSalary OtherSalary NetIncome SocialInsPay SocialInsEMPLPay HealthInsPay HealthInsEMPLPay TradeUnionInsPay TradeUnionInsEMPLPay UnemployedInsPay UnemployedInsEMPLPay AccidentInsPay AccidentInsEMPLPay AdvancesAmount TaxableIncome SelfDeduction DependQuantity DependentDeduction CharityAmount OtherDeductionsAmount AssessableIncome PersonalIncomeTaxAmount ParaCode SocialInsurance HealthInsurance TradeUnionInsurance UnemployedInsurance PersonalIncomeTax InEcoZones DeductionPITaxAmount IsActive CreatedBy CreatedAt ModifiedBy ModifiedAt timestamp
-- ---------- ----- ---- -------- ------------ -------- ------- ------- ----------- ------------ ---------------- --------------- -------------- ------------ ---------------- ----------- ----------- --------- ------------ ---------------- ------------ ---------------- ---------------- -------------------- ---------------- -------------------- -------------- ------------------ -------------- ------------- ------------- -------------- ------------------ ------------- --------------------- ---------------- ----------------------- -------- --------------- --------------- ------------------- ------------------- ----------------- ---------- -------------------- -------- --------- --------- ---------- ---------- ---------
200 A03 A03PR20260100001 2026-01-31 IT DUNGNTN  0 0 680000.00 0 1.0000 .00 .00 .00 .00 .00 .00 680000.00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 680000.00 11000000.00 0 .00 .00 .00 .00 .00 DEFAULT 0 0 0 0 1 0 .00 1 1 2026-05-16 14:06:00 1 2026-05-16 14:06:00 0x0000000000008514
199 A01 A01PR20260100009 2026-01-31 MARKETING TRIETLM  0 0 .00 0 1.0000 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 11000000.00 0 .00 .00 .00 .00 .00 DEFAULT 0 0 0 0 1 0 .00 1 1 2026-05-16 14:06:00 1 2026-05-16 14:06:00 0x0000000000008513
198 A01 A01PR20260100008 2026-01-31 STORE THANHTN  0 0 .00 0 1.0000 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 11000000.00 2 8800000.00 .00 .00 .00 .00 DEFAULT 0 0 0 0 1 0 .00 1 1 2026-05-16 14:06:00 1 2026-05-16 14:06:00 0x0000000000008512
197 A01 A01PR20260100007 2026-01-31 STORE NHINT  0 0 .00 0 1.0000 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 11000000.00 0 .00 .00 .00 .00 .00 DEFAULT 0 0 0 0 1 0 .00 1 1 2026-05-16 14:06:00 1 2026-05-16 14:06:00 0x0000000000008511
196 A01 A01PR20260100006 2026-01-31 STORE LINHNK  0 0 .00 0 1.0000 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 11000000.00 0 .00 .00 .00 .00 .00 DEFAULT 0 0 0 0 1 0 .00 1 1 2026-05-16 14:06:00 1 2026-05-16 14:06:00 0x0000000000008510
195 A01 A01PR20260100005 2026-01-31 LOGISTIC JONATHANB  0 0 .00 0 1.0000 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 11000000.00 1 4400000.00 .00 .00 .00 .00 DEFAULT 0 0 0 0 1 0 .00 1 1 2026-05-16 14:06:00 1 2026-05-16 14:06:00 0x000000000000850F
194 A01 A01PR20260100004 2026-01-31 STORE HANPN  0 0 10854167.00 0 1.0000 6000000.00 .00 .00 .00 .00 1000000.00 10164167.00 1020000.00 480000.00 180000.00 90000.00 120000.00 60000.00 60000.00 60000.00 30000.00 .00 .00 10854167.00 11000000.00 0 .00 .00 .00 .00 .00 DEFAULT 1 1 1 1 1 0 .00 1 1 2026-05-16 14:06:00 1 2026-05-16 14:06:00 0x000000000000850E
193 A01 A01PR20260100003 2026-01-31 MB GIANGTA  0 0 27840000.00 0 1.0000 18000000.00 .00 .00 .00 .00 2500000.00 24277500.00 3060000.00 1440000.00 540000.00 270000.00 360000.00 180000.00 180000.00 180000.00 90000.00 .00 .00 27840000.00 11000000.00 0 .00 .00 .00 14950000.00 1492500.00 DEFAULT 1 1 1 1 1 0 .00 1 1 2026-05-16 14:06:00 1 2026-05-16 14:06:00 0x000000000000850D
192 A01 A01PR20260100002 2026-01-31 MB AXT  0 0 .00 0 1.0000 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 11000000.00 0 .00 .00 .00 .00 .00 DEFAULT 0 0 0 0 1 0 .00 1 1 2026-05-16 14:06:00 1 2026-05-16 14:06:00 0x000000000000850C
191 A01 A01PR20260100001 2026-01-31 ACCOUNTING ANDK  0 0 .00 0 1.0000 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 .00 11000000.00 0 .00 .00 .00 .00 .00 DEFAULT 0 0 0 0 1 0 .00 1 1 2026-05-16 14:06:00 1 2026-05-16 14:06:00 0x000000000000850B

(10 rows affected)
```

## SELECT TOP 10 * FROM D30PayrollDetail;

```text
Id ParentId IsGroup BranchCode RowIdPR SalaryType BuiltinOrder Coeff Amount Days Hours IsActive CreatedBy CreatedAt ModifiedBy ModifiedAt timestamp
-- -------- ------- ---------- ------- ---------- ------------ ----- ------ ---- ----- -------- --------- --------- ---------- ---------- ---------
87 -1 0 A01 A01PR20260100003 BASE_NET_SAL 2 .00 24277500.00 .00 .00 1 1 2026-05-16 14:06:00 1 2026-05-16 14:06:00 0x0000000000008516
88 -1 0 A01 A01PR20260100004 BASE_NET_SAL 2 .00 10164167.00 .00 .00 1 1 2026-05-16 14:06:00 1 2026-05-16 14:06:00 0x0000000000008517
89 -1 0 A03 A03PR20260100001 BASE_NET_SAL 2 .00 680000.00 .00 .00 1 1 2026-05-16 14:06:00 1 2026-05-16 14:06:00 0x0000000000008518
90 -1 0 A01 A01PR20260100003 BASE_SAL 3 .00 18000000.00 .00 .00 1 1 2026-05-16 14:06:00 1 2026-05-16 14:06:00 0x0000000000008519
91 -1 0 A01 A01PR20260100004 BASE_SAL 3 .00 6000000.00 .00 .00 1 1 2026-05-16 14:06:00 1 2026-05-16 14:06:00 0x000000000000851A
92 -1 0 A01 A01PR20260100003 OTHER_ALLOWANCE 4 .00 1000000.00 .00 .00 1 1 2026-05-16 14:06:00 1 2026-05-16 14:06:00 0x000000000000851B
93 -1 0 A01 A01PR20260100004 OTHER_ALLOWANCE 4 .00 600000.00 .00 .00 1 1 2026-05-16 14:06:00 1 2026-05-16 14:06:00 0x000000000000851C
94 -1 0 A01 A01PR20260100003 SHIFT_ALLOWANCE 8 .00 840000.00 .00 .00 1 1 2026-05-16 14:06:00 1 2026-05-16 14:06:00 0x000000000000851D
95 -1 0 A01 A01PR20260100004 SHIFT_ALLOWANCE 8 .00 1000000.00 .00 .00 1 1 2026-05-16 14:06:00 1 2026-05-16 14:06:00 0x000000000000851E
96 -1 0 A03 A03PR20260100001 SHIFT_ALLOWANCE 8 .00 680000.00 .00 .00 1 1 2026-05-16 14:06:00 1 2026-05-16 14:06:00 0x000000000000851F

(10 rows affected)
```
