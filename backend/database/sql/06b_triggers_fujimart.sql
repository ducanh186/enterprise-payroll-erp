-- =============================================================================
-- INSTEAD OF triggers for writable Fujimart views.
-- Allows SPs of Dung to INSERT/UPDATE/DELETE via dbo.D20PayrollParameter,
-- dbo.D30AssignedShift, dbo.D30Attendance, dbo.D30Payroll.
-- =============================================================================
SET ANSI_NULLS ON;
GO
SET QUOTED_IDENTIFIER ON;
GO

-- =============================================================================
-- 1. D20PayrollParameter (INSERT/UPDATE/DELETE)
-- =============================================================================
CREATE OR ALTER TRIGGER dbo.tr_D20PayrollParameter_IOI
ON dbo.D20PayrollParameter
INSTEAD OF INSERT
AS
BEGIN
    SET NOCOUNT ON;
    INSERT INTO dbo.d20_payroll_parameters
        (parameter, name, description, effective_date, type, amount, is_active, created_at, updated_at)
    SELECT
        i.Parameter, i.Name, i.Description, i.EffectiveDate, i.[Type], i.Amount,
        1, GETDATE(), GETDATE()
    FROM inserted i;
END;
GO

CREATE OR ALTER TRIGGER dbo.tr_D20PayrollParameter_IOU
ON dbo.D20PayrollParameter
INSTEAD OF UPDATE
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE p
       SET p.parameter      = i.Parameter,
           p.name           = i.Name,
           p.description    = i.Description,
           p.effective_date = i.EffectiveDate,
           p.type           = i.[Type],
           p.amount         = i.Amount,
           p.updated_at     = GETDATE()
    FROM dbo.d20_payroll_parameters p
    JOIN inserted i ON i.Id = p.id;
END;
GO

CREATE OR ALTER TRIGGER dbo.tr_D20PayrollParameter_IOD
ON dbo.D20PayrollParameter
INSTEAD OF DELETE
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE p SET is_active = 0, updated_at = GETDATE()
    FROM dbo.d20_payroll_parameters p
    JOIN deleted d ON d.Id = p.id;
END;
GO

-- =============================================================================
-- 2. D30AssignedShift INSERT — expand date range + Mon-Sun pattern → N rows
-- =============================================================================
CREATE OR ALTER TRIGGER dbo.tr_D30AssignedShift_IOI
ON dbo.D30AssignedShift
INSTEAD OF INSERT
AS
BEGIN
    SET NOCOUNT ON;

    WITH numbers AS (
        SELECT TOP (366) ROW_NUMBER() OVER (ORDER BY (SELECT NULL)) - 1 AS n
        FROM sys.all_objects
    ),
    expanded AS (
        SELECT
            e.id AS employee_id,
            s.id AS shift_id,
            DATEADD(day, n.n, i.StartDate) AS work_date,
            i.StartDate, i.EndDate,
            i.IncludeMon, i.IncludeTue, i.IncludeWed, i.IncludeThu,
            i.IncludeFri, i.IncludeSat, i.IncludeSun,
            i.Description
        FROM inserted i
        JOIN dbo.employees e ON e.employee_code = i.EmployeeCode
        JOIN dbo.shifts s    ON s.code = i.ShiftCode
        CROSS APPLY numbers n
        WHERE DATEADD(day, n.n, i.StartDate) <= i.EndDate
    )
    INSERT INTO dbo.shift_assignments
        (employee_id, work_date, shift_id, source, note,
         start_date, end_date,
         include_mon, include_tue, include_wed, include_thu,
         include_fri, include_sat, include_sun,
         created_at, updated_at)
    SELECT
        ex.employee_id, ex.work_date, ex.shift_id, 'fujimart_sp', ex.Description,
        ex.StartDate, ex.EndDate,
        ex.IncludeMon, ex.IncludeTue, ex.IncludeWed, ex.IncludeThu,
        ex.IncludeFri, ex.IncludeSat, ex.IncludeSun,
        GETDATE(), GETDATE()
    FROM expanded ex
    WHERE
        (DATEPART(weekday, ex.work_date) = 2 AND ex.IncludeMon = 1) OR
        (DATEPART(weekday, ex.work_date) = 3 AND ex.IncludeTue = 1) OR
        (DATEPART(weekday, ex.work_date) = 4 AND ex.IncludeWed = 1) OR
        (DATEPART(weekday, ex.work_date) = 5 AND ex.IncludeThu = 1) OR
        (DATEPART(weekday, ex.work_date) = 6 AND ex.IncludeFri = 1) OR
        (DATEPART(weekday, ex.work_date) = 7 AND ex.IncludeSat = 1) OR
        (DATEPART(weekday, ex.work_date) = 1 AND ex.IncludeSun = 1);
END;
GO

-- =============================================================================
-- 3. D30Attendance INSERT/UPDATE → upsert attendance_daily
-- =============================================================================
CREATE OR ALTER TRIGGER dbo.tr_D30Attendance_IOI
ON dbo.D30Attendance
INSTEAD OF INSERT
AS
BEGIN
    SET NOCOUNT ON;

    INSERT INTO dbo.attendance_daily
        (doc_id, employee_id, work_date, attendance_period_id, shift_assignment_id,
         assigned_shift_code, regular_hours, workday_value,
         paid_leave_days, unpaid_leave_days, exclude_days, exclude_hours,
         night_hours, meal_count, attendance_status, calculation_version,
         created_at, updated_at)
    SELECT
        i.DocId,
        sa.employee_id,
        i.[Date],
        ap.id,
        sa.id,
        i.AssignedShiftCode,
        i.WorkingHours, i.WorkingDays,
        i.PaidLeaveDays, i.UnpaidLeaveDays, i.ExcludeDays, i.ExcludeHours,
        i.WorkNightHours, i.ShiftMeal,
        CASE WHEN i.WorkingDays > 0 THEN N'present' ELSE N'absent' END,
        1, GETDATE(), GETDATE()
    FROM inserted i
    OUTER APPLY (
        SELECT TOP 1 sa2.id, sa2.employee_id
        FROM dbo.shift_assignments sa2
        JOIN dbo.shifts s ON s.id = sa2.shift_id
        WHERE s.code = i.AssignedShiftCode AND sa2.work_date = i.[Date]
    ) sa
    OUTER APPLY (
        SELECT TOP 1 ap2.id FROM dbo.attendance_periods ap2
        WHERE i.[Date] BETWEEN ap2.from_date AND ap2.to_date
    ) ap
    WHERE sa.id IS NOT NULL AND ap.id IS NOT NULL;
END;
GO

CREATE OR ALTER TRIGGER dbo.tr_D30Attendance_IOU
ON dbo.D30Attendance
INSTEAD OF UPDATE
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE ad
       SET ad.regular_hours      = i.WorkingHours,
           ad.workday_value      = i.WorkingDays,
           ad.paid_leave_days    = i.PaidLeaveDays,
           ad.unpaid_leave_days  = i.UnpaidLeaveDays,
           ad.exclude_days       = i.ExcludeDays,
           ad.exclude_hours      = i.ExcludeHours,
           ad.night_hours        = i.WorkNightHours,
           ad.meal_count         = i.ShiftMeal,
           ad.updated_at         = GETDATE()
    FROM dbo.attendance_daily ad
    JOIN inserted i ON i.DocId = ad.doc_id
                    OR i.DocId = CAST(ad.id AS VARCHAR(16));
END;
GO

-- =============================================================================
-- 4. D30Payroll UPDATE → update payslips
-- =============================================================================
CREATE OR ALTER TRIGGER dbo.tr_D30Payroll_IOU
ON dbo.D30Payroll
INSTEAD OF UPDATE
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE p
       SET p.gross_salary       = i.GrossSalary,
           p.taxable_income     = i.TaxableIncome,
           p.insurance_base     = i.SalaryInsurance,
           p.insurance_employee = i.SocialInsEMPLPay,
           p.insurance_company  = i.SocialInsPay,
           p.pit_amount         = i.PersonalIncomeTaxAmount,
           p.bonus_total        = i.BonusSalary,
           p.deduction_total    = i.OtherDeductionsAmount,
           p.net_salary         = i.NetIncome,
           p.updated_at         = GETDATE()
    FROM dbo.payslips p
    JOIN inserted i ON CAST(p.id AS VARCHAR(16)) = i.Id;
END;
GO
