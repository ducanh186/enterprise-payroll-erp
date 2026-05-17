-- =============================================================================
-- Fujimart View Layer
-- Maps migrate tables (Laravel snake_case) to Fujimart-original PascalCase
-- schema so legacy stored procedures can run unchanged.
-- =============================================================================
SET ANSI_NULLS ON;
GO
SET QUOTED_IDENTIFIER ON;
GO

-- =============================================================================
-- 1. D00User
-- =============================================================================
CREATE OR ALTER VIEW dbo.D00User AS
SELECT
    u.username                          AS UserName,
    u.name                              AS FullName,
    CAST(e.id AS INT)                   AS EmployeeCode,
    u.password                          AS Password,
    CAST(NULL AS DATETIME)              AS LockDate
FROM dbo.users u
LEFT JOIN dbo.employees e ON e.user_id = u.id;
GO

-- =============================================================================
-- 2. D20Branch
-- =============================================================================
CREATE OR ALTER VIEW dbo.D20Branch AS
SELECT
    b.code      AS Code,
    b.name      AS Name,
    b.address   AS Address
FROM dbo.branches b
WHERE b.is_active = 1;
GO

-- =============================================================================
-- 3. D20Department
-- =============================================================================
CREATE OR ALTER VIEW dbo.D20Department AS
SELECT
    d.code      AS Code,
    d.name      AS Name
FROM dbo.departments d
WHERE d.status = N'active';
GO

-- =============================================================================
-- 4. D20Position
-- =============================================================================
CREATE OR ALTER VIEW dbo.D20Position AS
SELECT
    p.code      AS Code,
    p.name      AS Name
FROM dbo.positions p
WHERE p.status = N'active';
GO

-- =============================================================================
-- 5. D20Employee
-- =============================================================================
CREATE OR ALTER VIEW dbo.D20Employee AS
SELECT
    e.employee_code                          AS Code,
    e.full_name                              AS FullName,
    b.code                                   AS BranchCode,
    CAST(e.department_id AS INT)             AS DeptCode,
    CAST(e.position_id AS INT)               AS PositionCode,
    e.dob                                    AS BirthDate,
    CASE LOWER(e.gender)
         WHEN 'male' THEN 1
         WHEN 'female' THEN 2
         ELSE 3 END                          AS Gender,
    e.national_id                            AS IdCardNo,
    e.tax_code                               AS TaxRegNo,
    e.nationality                            AS Nationality,
    e.address                                AS Address,
    e.phone                                  AS Mobile,
    e.email                                  AS Email,
    e.bank_account_no                        AS BankAccountNo,
    e.bank_name                              AS BankName,
    COALESCE(e.first_working_date, e.join_date) AS FirstWorkingDate,
    CAST(NULL AS DATE)                       AS ResignDate
FROM dbo.employees e
LEFT JOIN dbo.branches b ON b.id = e.branch_id
WHERE e.employment_status = N'active';
GO

-- =============================================================================
-- 6. D20Dependent
-- =============================================================================
CREATE OR ALTER VIEW dbo.D20Dependent AS
SELECT
    d.full_name                  AS Name,
    e.employee_code              AS EmployeeCode,
    d.dob                        AS BirthDate,
    CAST(NULL AS NVARCHAR(256))  AS Occupation,
    d.national_id                AS IdCardNo,
    CAST(NULL AS VARCHAR(24))    AS TaxRegNo,
    d.relationship               AS Relationship,
    d.tax_reduction_from         AS ReductionStartDate,
    d.tax_reduction_to           AS ReductionEndDate
FROM dbo.dependents d
JOIN dbo.employees e ON e.id = d.employee_id;
GO

-- =============================================================================
-- 7. D20ContractType
-- =============================================================================
CREATE OR ALTER VIEW dbo.D20ContractType AS
SELECT
    ct.code                                AS Code,
    ct.name                                AS Name,
    0                                      AS [Type],
    ct.duration_months                     AS NumberOfMonth,
    CAST(ct.is_probationary AS INT)        AS IsProbationary
FROM dbo.contract_types ct
WHERE ISNULL(ct.is_active, 1) = 1;
GO

-- =============================================================================
-- 8. D20Shift (25 cột mapping đầy đủ Fujimart)
-- =============================================================================
CREATE OR ALTER VIEW dbo.D20Shift AS
SELECT
    s.code                                   AS Code,
    s.name                                   AS Name,
    s.description                            AS Description,
    CAST(s.is_checkin AS TINYINT)            AS IsCheckin,
    CAST(s.start_time AS DATETIME)           AS StartTime,
    s.start_time_valid1                      AS StartTimeValid1,
    s.start_time_valid2                      AS StartTimeValid2,
    CAST(s.is_checkout AS TINYINT)           AS IsCheckout,
    CAST(s.end_time AS DATETIME)             AS EndTime,
    s.end_time_valid1                        AS EndTimeValid1,
    s.end_time_valid2                        AS EndTimeValid2,
    s.shift_break                            AS ShiftBreak,
    CAST(s.break_start_time AS DATETIME)     AS StartShiftBreak,
    s.start_break_time_valid1                AS StartBreakTimeValid1,
    s.start_break_time_valid2                AS StartBreakTimeValid2,
    CAST(s.break_end_time AS DATETIME)       AS EndShiftBreak,
    s.end_break_time_valid1                  AS EndBreakTimeValid1,
    s.end_break_time_valid2                  AS EndBreakTimeValid2,
    s.workday_value                          AS WorkDay,
    s.working_hours                          AS WorkingHours,
    s.shift_break_mins                       AS ShiftBreakMins,
    s.start_working_night_time               AS StartWorkingNightTime,
    s.end_working_night_time                 AS EndWorkingNightTime,
    s.shift_meal                             AS ShiftMeal,
    s.min_meal_hours                         AS MinHourMeal
FROM dbo.shifts s
WHERE s.is_active = 1 AND s.status = N'active';
GO

-- =============================================================================
-- 9. D20Holiday
-- =============================================================================
CREATE OR ALTER VIEW dbo.D20Holiday AS
SELECT
    h.holiday_date                  AS [Date],
    h.name                          AS Description,
    h.multiplier                    AS NumberOfDay,
    CASE WHEN h.is_paid = 1 THEN 1 ELSE 2 END AS HolidayType
FROM dbo.holidays h
WHERE ISNULL(h.is_active, 1) = 1;
GO

-- =============================================================================
-- 10. D20LateEarlyRegulation
-- =============================================================================
CREATE OR ALTER VIEW dbo.D20LateEarlyRegulation AS
SELECT
    ler.code                        AS Code,
    ler.name                        AS Name,
    CAST(NULL AS NVARCHAR(256))     AS Description,
    ler.effective_from              AS AppliedDate,
    ler.effective_to                AS ExpiredDate
FROM dbo.late_early_rules ler
WHERE ISNULL(ler.is_active, 1) = 1;
GO

-- =============================================================================
-- 11. D20LateEarlyRegulationDetail
-- =============================================================================
CREATE OR ALTER VIEW dbo.D20LateEarlyRegulationDetail AS
SELECT
    d.row_id                        AS RowId,
    ler.code                        AS LateEarlyRegCode,
    d.description                   AS Description,
    d.start_minute                  AS StartMinute,
    d.end_minute                    AS EndMinute,
    d.exclude_time                  AS ExcludeTime,
    d.exclude_workday               AS ExcludeWorkDay
FROM dbo.late_early_rule_details d
JOIN dbo.late_early_rules ler ON ler.id = d.late_early_rule_id;
GO

-- =============================================================================
-- 12. D20SalaryScale
-- =============================================================================
CREATE OR ALTER VIEW dbo.D20SalaryScale AS
SELECT
    ss.code         AS Code,
    ss.name         AS Name,
    ss.description  AS Description
FROM dbo.salary_scales ss
WHERE ss.is_active = 1;
GO

-- =============================================================================
-- 13. D20SalaryGrade
-- =============================================================================
CREATE OR ALTER VIEW dbo.D20SalaryGrade AS
SELECT
    CAST(sg.id AS INT)              AS Id,
    sg.scale_code                   AS ScaleCode,
    sg.effective_date               AS EffectiveDate,
    sg.salary_level                 AS SalaryLevel,
    sg.description                  AS Description
FROM dbo.salary_grades sg;
GO

-- =============================================================================
-- 14. D20SalaryGradeDetail
-- =============================================================================
CREATE OR ALTER VIEW dbo.D20SalaryGradeDetail AS
SELECT
    sgd.row_id                          AS RowId,
    CAST(sgd.salary_grade_id AS VARCHAR(64)) AS ParentId,
    sgd.salary_type                     AS SalaryType,
    sgd.amount                          AS Amount,
    sgd.description                     AS Description
FROM dbo.salary_grade_details sgd;
GO

-- =============================================================================
-- 15. D20PayrollParameter (flat)
-- =============================================================================
CREATE OR ALTER VIEW dbo.D20PayrollParameter AS
SELECT
    CAST(p.id AS INT)        AS Id,
    p.parameter              AS Parameter,
    p.name                   AS Name,
    p.description            AS Description,
    p.effective_date         AS EffectiveDate,
    p.type                   AS [Type],
    p.amount                 AS Amount
FROM dbo.d20_payroll_parameters p
WHERE p.is_active = 1;
GO

-- =============================================================================
-- 15a. vD20PayrollPara_ValuePara (tab tham số giá trị)
-- =============================================================================
CREATE OR ALTER VIEW dbo.vD20PayrollPara_ValuePara AS
SELECT * FROM dbo.D20PayrollParameter
WHERE [Type] IN ('VALUE', 'RATE', 'COEFF');
GO

-- =============================================================================
-- 15b. vD20PayrollPara_SalaryType (tab loại thu nhập)
-- =============================================================================
CREATE OR ALTER VIEW dbo.vD20PayrollPara_SalaryType AS
SELECT * FROM dbo.D20PayrollParameter
WHERE [Type] IN ('INCOME', 'BONUS', 'DEDUCTION');
GO

-- =============================================================================
-- 16. D30LabourContract
-- =============================================================================
CREATE OR ALTER VIEW dbo.D30LabourContract AS
SELECT
    CAST(lc.id AS VARCHAR(32))               AS DocId,
    lc.contract_no                           AS DocNo,
    lc.sign_date                             AS DocDate,
    e.employee_code                          AS EmployeeCode,
    CAST(lc.contract_type_id AS INT)         AS TypeCode,
    lc.start_date                            AS StartDate,
    lc.end_date                              AS EndDate,
    lc.probation_rate                        AS ProbationaryRate,
    CAST(e.position_id AS INT)               AS PositionCode,
    CAST(lc.salary_level_id AS INT)          AS SalaryGradeId,
    0                                        AS WorkingType
FROM dbo.labour_contracts lc
JOIN dbo.employees e ON e.id = lc.employee_id;
GO

-- =============================================================================
-- 17. D30BonusDeduction
-- =============================================================================
CREATE OR ALTER VIEW dbo.D30BonusDeduction AS
SELECT
    CAST(bd.id AS VARCHAR(32))               AS DocId,
    CAST(bd.id AS VARCHAR(32))               AS DocNo,
    bd.created_at                            AS DocDate,
    CASE bdt.kind WHEN 'bonus' THEN 1 ELSE 2 END AS DocType,
    d.code                                   AS DeptCode,
    e.employee_code                          AS EmployeeCode,
    bd.description                           AS Description,
    bdt.code                                 AS SalaryType,
    bd.amount                                AS Amount
FROM dbo.bonus_deductions bd
JOIN dbo.bonus_deduction_types bdt ON bdt.id = bd.type_id
JOIN dbo.employees e               ON e.id = bd.employee_id
LEFT JOIN dbo.departments d        ON d.id = e.department_id
WHERE bd.status = N'active';
GO

-- =============================================================================
-- 18. D30AssignedShift (1-1, trigger sẽ expand khi insert)
-- =============================================================================
CREATE OR ALTER VIEW dbo.D30AssignedShift AS
SELECT
    CAST(sa.id AS VARCHAR(32))               AS AssignId,
    sa.work_date                             AS [Date],
    e.employee_code                          AS EmployeeCode,
    s.code                                   AS ShiftCode,
    COALESCE(sa.start_date, sa.work_date)    AS StartDate,
    COALESCE(sa.end_date, sa.work_date)      AS EndDate,
    CAST(sa.include_mon AS INT)              AS IncludeMon,
    CAST(sa.include_tue AS INT)              AS IncludeTue,
    CAST(sa.include_wed AS INT)              AS IncludeWed,
    CAST(sa.include_thu AS INT)              AS IncludeThu,
    CAST(sa.include_fri AS INT)              AS IncludeFri,
    CAST(sa.include_sat AS INT)              AS IncludeSat,
    CAST(sa.include_sun AS INT)              AS IncludeSun,
    sa.note                                  AS Description
FROM dbo.shift_assignments sa
JOIN dbo.employees e ON e.id = sa.employee_id
JOIN dbo.shifts s    ON s.id = sa.shift_id;
GO

-- =============================================================================
-- 19. D30CheckInOut
-- =============================================================================
CREATE OR ALTER VIEW dbo.D30CheckInOut AS
SELECT
    tl.log_time          AS CheckTime,
    e.employee_code      AS EmployeeCode
FROM dbo.time_logs tl
JOIN dbo.employees e ON e.id = tl.employee_id
WHERE tl.is_valid = 1;
GO

-- =============================================================================
-- 20. D30AttendanceDoc
-- =============================================================================
CREATE OR ALTER VIEW dbo.D30AttendanceDoc AS
SELECT
    CAST(ar.id AS VARCHAR(32))    AS DocId,
    CAST(ar.id AS VARCHAR(32))    AS DocNo,
    ar.submitted_at               AS DocDate,
    CASE ar.request_type
         WHEN N'leave' THEN 'AL'
         WHEN N'manual_checkin' THEN 'MC'
         ELSE 'XX' END            AS DocType,
    e.employee_code               AS EmployeeCode,
    eu.employee_code              AS ManagerCode,
    ar.reason                     AS Description
FROM dbo.attendance_requests ar
JOIN dbo.employees e            ON e.id = ar.employee_id
LEFT JOIN dbo.users u           ON u.id = ar.approved_by
LEFT JOIN dbo.employees eu      ON eu.user_id = u.id;
GO

-- =============================================================================
-- 21. D30AbsenceDetail
-- =============================================================================
CREATE OR ALTER VIEW dbo.D30AbsenceDetail AS
SELECT
    CAST(ard.id AS VARCHAR(32))     AS RowId,
    CAST(ard.request_id AS VARCHAR(32)) AS DocId,
    ard.work_date                   AS [Date],
    3                               AS [Type],
    ard.requested_hours             AS WorkingHours,
    CAST(1.0 AS NUMERIC(8,2))       AS WorkingDays,
    ard.note                        AS Description
FROM dbo.attendance_request_details ard
JOIN dbo.attendance_requests ar ON ar.id = ard.request_id
WHERE ar.request_type = N'leave';
GO

-- =============================================================================
-- 22. D30CheckInManualDetail
-- =============================================================================
CREATE OR ALTER VIEW dbo.D30CheckInManualDetail AS
SELECT
    CAST(ard.id AS VARCHAR(32))     AS RowId,
    CAST(ard.request_id AS VARCHAR(32)) AS DocId,
    ard.requested_check_in          AS CheckTime,
    ard.note                        AS Description
FROM dbo.attendance_request_details ard
JOIN dbo.attendance_requests ar ON ar.id = ard.request_id
WHERE ar.request_type = N'manual_checkin';
GO

-- =============================================================================
-- 23. D30Attendance
-- =============================================================================
CREATE OR ALTER VIEW dbo.D30Attendance AS
SELECT
    COALESCE(ad.doc_id, CAST(ad.id AS VARCHAR(16))) AS DocId,
    ad.work_date                                    AS [Date],
    COALESCE(ad.assigned_shift_code, s.code)        AS AssignedShiftCode,
    ad.regular_hours                                AS WorkingHours,
    ad.workday_value                                AS WorkingDays,
    ad.unpaid_leave_days                            AS UnpaidLeaveDays,
    ad.paid_leave_days                              AS PaidLeaveDays,
    ad.exclude_days                                 AS ExcludeDays,
    ad.exclude_hours                                AS ExcludeHours,
    ad.night_hours                                  AS WorkNightHours,
    ad.meal_count                                   AS ShiftMeal
FROM dbo.attendance_daily ad
LEFT JOIN dbo.shift_assignments sa ON sa.id = ad.shift_assignment_id
LEFT JOIN dbo.shifts s             ON s.id = sa.shift_id;
GO

-- =============================================================================
-- 24. D30Payroll
-- =============================================================================
CREATE OR ALTER VIEW dbo.D30Payroll AS
SELECT
    COALESCE(b.code, 'A01')                    AS BranchCode,
    CAST(p.id AS VARCHAR(16))                  AS Id,
    ap.from_date                               AS [Date],
    d.code                                     AS DeptCode,
    e.employee_code                            AS EmployeeCode,
    CAST(NULL AS VARCHAR(32))                  AS ParaCode,
    CAST(0 AS TINYINT)                         AS NetCalc,
    p.gross_salary                             AS GrossSalary,
    CAST(0 AS TINYINT)                         AS Probationary,
    CAST(NULL AS NUMERIC(8,4))                 AS ProbationaryRate,
    p.insurance_base                           AS SalaryInsurance,
    CAST(0 AS NUMERIC(18,2))                   AS OvertimeSalary,
    CAST(0 AS NUMERIC(18,2))                   AS OffsetSalary,
    CAST(0 AS NUMERIC(18,2))                   AS OVTTaxableIncome,
    p.bonus_total                              AS BonusSalary,
    CAST(0 AS NUMERIC(18,2))                   AS OtherSalary,
    p.net_salary                               AS NetIncome,
    p.insurance_company                        AS SocialInsPay,
    p.insurance_employee                       AS SocialInsEMPLPay,
    CAST(0 AS NUMERIC(18,2))                   AS HealthInsPay,
    CAST(0 AS NUMERIC(18,2))                   AS HealthInsEMPLPay,
    CAST(0 AS NUMERIC(18,2))                   AS TradeUnionInsPay,
    CAST(0 AS NUMERIC(18,2))                   AS TradeUnionInsEMPLPay,
    CAST(0 AS NUMERIC(18,2))                   AS UnemployedInsPay,
    CAST(0 AS NUMERIC(18,2))                   AS UnemployedInsEMPLPay,
    CAST(0 AS NUMERIC(18,2))                   AS AccidentInsPay,
    CAST(0 AS NUMERIC(18,2))                   AS AccidentInsEMPLPay,
    CAST(0 AS NUMERIC(18,2))                   AS AdvancesAmount,
    p.taxable_income                           AS TaxableIncome,
    CAST(0 AS NUMERIC(18,2))                   AS SelfDeduction,
    CAST(0 AS INT)                             AS DependQuantity,
    CAST(0 AS NUMERIC(18,2))                   AS DependentDeduction,
    p.deduction_total                          AS OtherDeductionsAmount,
    p.taxable_income                           AS AssessableIncome,
    p.pit_amount                               AS PersonalIncomeTaxAmount,
    CAST(1 AS TINYINT)                         AS SocialInsurance,
    CAST(1 AS TINYINT)                         AS HealthInsurance,
    CAST(1 AS TINYINT)                         AS TradeUnionInsurance,
    CAST(1 AS TINYINT)                         AS UnemployedInsurance,
    CAST(1 AS TINYINT)                         AS PersonalIncomeTax,
    p.pit_amount                               AS DeductionPITaxAmount
FROM dbo.payslips p
JOIN dbo.payroll_runs pr           ON pr.id = p.payroll_run_id
JOIN dbo.attendance_periods ap     ON ap.id = pr.attendance_period_id
JOIN dbo.employees e               ON e.id = p.employee_id
LEFT JOIN dbo.branches b           ON b.id = e.branch_id
LEFT JOIN dbo.departments d        ON d.id = e.department_id;
GO

-- =============================================================================
-- 25. D30PayrollDetail
-- =============================================================================
CREATE OR ALTER VIEW dbo.D30PayrollDetail AS
SELECT
    CAST(pi.id AS VARCHAR(16))         AS RowId,
    CAST(pi.payslip_id AS VARCHAR(16)) AS PayRollId,
    pi.item_name                       AS SalaryType,
    pi.sort_order                      AS BuiltinOrder,
    CAST(1 AS NUMERIC(18,2))           AS Coeff,
    pi.amount                          AS Amount,
    pi.qty                             AS Days,
    CAST(NULL AS NUMERIC(6,2))         AS Hours
FROM dbo.payslip_items pi;
GO
