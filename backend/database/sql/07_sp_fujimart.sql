-- =============================================================================
-- Fujimart Stored Procedures
-- 5 SPs: usp_CreateAndCalculateAttendance, usp_CreateAndCalculatePayroll,
--         usp_AttendanceReport, usp_PayrollReport, usp_PayrollSlip
-- =============================================================================
SET ANSI_NULLS ON;
GO
SET QUOTED_IDENTIFIER ON;
GO

-- =============================================================================
-- 1. usp_CreateAndCalculateAttendance
--    Tính & tổng hợp công cho kỳ chứa @_DocDate1
-- =============================================================================
CREATE OR ALTER PROCEDURE dbo.usp_CreateAndCalculateAttendance
    @_DocDate1     DATE,
    @_BranchCode   NVARCHAR(16)  = N'',
    @_DeptCode     NVARCHAR(16)  = N'',
    @_EmployeeCode NVARCHAR(16)  = N'',
    @_result_msg   NVARCHAR(200) = N'' OUTPUT
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @period_id BIGINT;
    DECLARE @from_date DATE = DATEFROMPARTS(YEAR(@_DocDate1), MONTH(@_DocDate1), 1);
    DECLARE @to_date   DATE = EOMONTH(@from_date);
    DECLARE @inserted  INT  = 0;

    BEGIN TRY
        BEGIN TRANSACTION;

        SELECT @period_id = id FROM dbo.attendance_periods
        WHERE from_date = @from_date AND to_date = @to_date;

        IF @period_id IS NULL
        BEGIN
            INSERT INTO dbo.attendance_periods
                (period_code, month, year, from_date, to_date, status, created_at, updated_at)
            VALUES
                (CONCAT(YEAR(@from_date), '-',
                        RIGHT('0' + CAST(MONTH(@from_date) AS VARCHAR(2)), 2)),
                 MONTH(@from_date), YEAR(@from_date), @from_date, @to_date,
                 N'draft', GETDATE(), GETDATE());
            SET @period_id = SCOPE_IDENTITY();
        END

        -- Delegate to existing core SP
        EXEC dbo.sp_generate_attendance_daily
            @attendance_period_id = @period_id,
            @generated_count = @inserted OUTPUT;

        -- Aggregate to monthly summary
        DECLARE @sum_count INT = 0;
        EXEC dbo.sp_generate_attendance_summary
            @attendance_period_id = @period_id,
            @generated_count = @sum_count OUTPUT;

        SET @_result_msg = CONCAT(N'Đã tính & tổng hợp công cho ', @inserted, N' bản ghi.');
        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
        SET @_result_msg = CONCAT(N'Lỗi: ', ERROR_MESSAGE());
        THROW;
    END CATCH
END;
GO

-- =============================================================================
-- 2. usp_CreateAndCalculatePayroll
-- =============================================================================
CREATE OR ALTER PROCEDURE dbo.usp_CreateAndCalculatePayroll
    @_DocDate1     DATE,
    @_BranchCode   NVARCHAR(16)  = N'',
    @_DeptCode     NVARCHAR(16)  = N'',
    @_EmployeeCode NVARCHAR(16)  = N'',
    @_result_msg   NVARCHAR(200) = N'' OUTPUT
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @period_id BIGINT;
    DECLARE @run_id    BIGINT;
    DECLARE @from_date DATE = DATEFROMPARTS(YEAR(@_DocDate1), MONTH(@_DocDate1), 1);
    DECLARE @to_date   DATE = EOMONTH(@from_date);

    BEGIN TRY
        BEGIN TRANSACTION;

        SELECT @period_id = id FROM dbo.attendance_periods
        WHERE from_date = @from_date AND to_date = @to_date;

        IF @period_id IS NULL
        BEGIN
            SET @_result_msg = N'Chưa có kỳ chấm công cho tháng này. Hãy chạy usp_CreateAndCalculateAttendance trước.';
            ROLLBACK;
            RETURN;
        END

        -- sp_preview_payroll creates the payroll_run + payslips itself
        EXEC dbo.sp_preview_payroll
            @attendance_period_id = @period_id,
            @scope_type   = N'all',
            @scope_value  = NULL,
            @payroll_run_id = @run_id OUTPUT;

        DECLARE @count INT = 0;
        SELECT @count = COUNT(*) FROM dbo.payslips WHERE payroll_run_id = @run_id;

        SET @_result_msg = CONCAT(N'Đã tính lương cho ', @count, N' nhân viên. (run_id=', @run_id, N')');
        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
        SET @_result_msg = CONCAT(N'Lỗi: ', ERROR_MESSAGE());
        THROW;
    END CATCH
END;
GO

-- =============================================================================
-- 3. usp_AttendanceReport — 2 result sets (header + daily detail)
-- =============================================================================
CREATE OR ALTER PROCEDURE dbo.usp_AttendanceReport
    @_DocDate1     DATE,
    @_DocDate2     DATE,
    @_BranchCode   VARCHAR(16)  = '',
    @_DeptCode     VARCHAR(512) = '',
    @_EmployeeCode VARCHAR(512) = ''
AS
BEGIN
    SET NOCOUNT ON;

    -- Result set 1: per-employee aggregate
    SELECT
        e.employee_code              AS EmployeeCode,
        e.full_name                  AS FullName,
        d.code                       AS DeptCode,
        d.name                       AS DeptName,
        b.code                       AS BranchCode,
        SUM(ad.workday_value)        AS TotalWorkDays,
        SUM(ad.regular_hours)        AS TotalWorkHours,
        SUM(ad.paid_leave_days)      AS TotalPaidLeave,
        SUM(ad.unpaid_leave_days)    AS TotalUnpaidLeave,
        SUM(ad.late_minutes)         AS TotalLateMinutes,
        SUM(ad.early_minutes)        AS TotalEarlyMinutes
    FROM dbo.attendance_daily ad
    JOIN dbo.employees e         ON e.id = ad.employee_id
    LEFT JOIN dbo.departments d  ON d.id = e.department_id
    LEFT JOIN dbo.branches b     ON b.id = e.branch_id
    WHERE ad.work_date BETWEEN @_DocDate1 AND @_DocDate2
      AND (@_BranchCode   = '' OR b.code IN (SELECT value FROM STRING_SPLIT(@_BranchCode, ',')))
      AND (@_DeptCode     = '' OR d.code IN (SELECT value FROM STRING_SPLIT(@_DeptCode, ',')))
      AND (@_EmployeeCode = '' OR e.employee_code IN (SELECT value FROM STRING_SPLIT(@_EmployeeCode, ',')))
    GROUP BY e.employee_code, e.full_name, d.code, d.name, b.code
    ORDER BY b.code, d.code, e.employee_code;

    -- Result set 2: per-employee per-day detail
    SELECT
        e.employee_code              AS EmployeeCode,
        ad.work_date                 AS WorkDate,
        ad.regular_hours             AS WorkingHours,
        ad.workday_value             AS WorkingDays,
        ad.paid_leave_days           AS PaidLeaveDays,
        ad.unpaid_leave_days         AS UnpaidLeaveDays,
        ad.late_minutes              AS LateMinutes,
        ad.early_minutes             AS EarlyMinutes,
        ad.night_hours               AS NightHours,
        ad.meal_count                AS MealCount,
        s.code                       AS ShiftCode
    FROM dbo.attendance_daily ad
    JOIN dbo.employees e             ON e.id = ad.employee_id
    LEFT JOIN dbo.departments d      ON d.id = e.department_id
    LEFT JOIN dbo.branches b         ON b.id = e.branch_id
    LEFT JOIN dbo.shift_assignments sa ON sa.id = ad.shift_assignment_id
    LEFT JOIN dbo.shifts s           ON s.id = sa.shift_id
    WHERE ad.work_date BETWEEN @_DocDate1 AND @_DocDate2
      AND (@_BranchCode   = '' OR b.code IN (SELECT value FROM STRING_SPLIT(@_BranchCode, ',')))
      AND (@_DeptCode     = '' OR d.code IN (SELECT value FROM STRING_SPLIT(@_DeptCode, ',')))
      AND (@_EmployeeCode = '' OR e.employee_code IN (SELECT value FROM STRING_SPLIT(@_EmployeeCode, ',')))
    ORDER BY e.employee_code, ad.work_date;
END;
GO

-- =============================================================================
-- 4. usp_PayrollReport — aggregate payslip by branch/dept
-- =============================================================================
CREATE OR ALTER PROCEDURE dbo.usp_PayrollReport
    @_DocDate1     DATE,
    @_BranchCode   VARCHAR(16)  = '',
    @_DeptCode     VARCHAR(512) = '',
    @_EmployeeCode VARCHAR(512) = ''
AS
BEGIN
    SET NOCOUNT ON;

    SELECT
        p.BranchCode,
        p.DeptCode,
        p.EmployeeCode,
        e.full_name              AS FullName,
        p.GrossSalary,
        p.NetIncome,
        p.SocialInsEMPLPay,
        p.PersonalIncomeTaxAmount,
        (p.NetIncome - p.SocialInsEMPLPay - p.PersonalIncomeTaxAmount) AS TakeHome
    FROM dbo.D30Payroll p
    JOIN dbo.employees e ON e.employee_code = p.EmployeeCode
    WHERE p.[Date] = DATEFROMPARTS(YEAR(@_DocDate1), MONTH(@_DocDate1), 1)
      AND (@_BranchCode   = '' OR p.BranchCode   IN (SELECT value FROM STRING_SPLIT(@_BranchCode, ',')))
      AND (@_DeptCode     = '' OR p.DeptCode     IN (SELECT value FROM STRING_SPLIT(@_DeptCode, ',')))
      AND (@_EmployeeCode = '' OR p.EmployeeCode IN (SELECT value FROM STRING_SPLIT(@_EmployeeCode, ',')))
    ORDER BY p.BranchCode, p.DeptCode, p.EmployeeCode;
END;
GO

-- =============================================================================
-- 5. usp_PayrollSlip — phiếu lương cá nhân, with optional email
-- =============================================================================
CREATE OR ALTER PROCEDURE dbo.usp_PayrollSlip
    @_DocDate1     DATE,
    @_EmployeeCode VARCHAR(512) = '',
    @_DeptCode     VARCHAR(512) = '',
    @_BranchCode   VARCHAR(16)  = '',
    @_SendEmail    TINYINT      = 0,
    @_MailProfile  NVARCHAR(128) = N''
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @period_start DATE = DATEFROMPARTS(YEAR(@_DocDate1), MONTH(@_DocDate1), 1);

    IF @_SendEmail = 0
    BEGIN
        -- Result set 1: slip header
        SELECT
            p.EmployeeCode,
            e.full_name              AS FullName,
            p.BranchCode,
            p.DeptCode,
            p.GrossSalary,
            p.NetIncome,
            p.SocialInsEMPLPay,
            p.PersonalIncomeTaxAmount,
            (p.NetIncome - p.SocialInsEMPLPay - p.PersonalIncomeTaxAmount) AS TakeHome,
            e.email                  AS Email
        FROM dbo.D30Payroll p
        JOIN dbo.employees e ON e.employee_code = p.EmployeeCode
        WHERE p.[Date] = @period_start
          AND (@_EmployeeCode = '' OR p.EmployeeCode IN (SELECT value FROM STRING_SPLIT(@_EmployeeCode, ',')))
          AND (@_DeptCode     = '' OR p.DeptCode     IN (SELECT value FROM STRING_SPLIT(@_DeptCode, ',')))
          AND (@_BranchCode   = '' OR p.BranchCode   IN (SELECT value FROM STRING_SPLIT(@_BranchCode, ',')))
        ORDER BY p.EmployeeCode;

        -- Result set 2: detail items
        SELECT
            d.PayRollId,
            d.SalaryType,
            d.BuiltinOrder,
            d.Amount,
            d.Days,
            d.Hours
        FROM dbo.D30PayrollDetail d
        WHERE d.PayRollId IN (
            SELECT p.Id FROM dbo.D30Payroll p
            WHERE p.[Date] = @period_start
              AND (@_EmployeeCode = '' OR p.EmployeeCode IN (SELECT value FROM STRING_SPLIT(@_EmployeeCode, ',')))
              AND (@_DeptCode     = '' OR p.DeptCode     IN (SELECT value FROM STRING_SPLIT(@_DeptCode, ',')))
              AND (@_BranchCode   = '' OR p.BranchCode   IN (SELECT value FROM STRING_SPLIT(@_BranchCode, ',')))
        )
        ORDER BY d.PayRollId, d.BuiltinOrder;
    END
    ELSE
    BEGIN
        DECLARE @sent INT = 0, @failed INT = 0;
        DECLARE @code VARCHAR(32), @mail NVARCHAR(128), @body NVARCHAR(MAX), @subject NVARCHAR(256);
        DECLARE @full_name NVARCHAR(256), @gross DECIMAL(18,2), @net DECIMAL(18,2);

        DECLARE cur CURSOR FAST_FORWARD FOR
            SELECT p.EmployeeCode, e.email, e.full_name, p.GrossSalary, p.NetIncome
            FROM dbo.D30Payroll p
            JOIN dbo.employees e ON e.employee_code = p.EmployeeCode
            WHERE p.[Date] = @period_start
              AND e.email IS NOT NULL
              AND (@_EmployeeCode = '' OR p.EmployeeCode IN (SELECT value FROM STRING_SPLIT(@_EmployeeCode, ',')))
              AND (@_DeptCode     = '' OR p.DeptCode     IN (SELECT value FROM STRING_SPLIT(@_DeptCode, ',')))
              AND (@_BranchCode   = '' OR p.BranchCode   IN (SELECT value FROM STRING_SPLIT(@_BranchCode, ',')));

        OPEN cur;
        FETCH NEXT FROM cur INTO @code, @mail, @full_name, @gross, @net;
        WHILE @@FETCH_STATUS = 0
        BEGIN
            SET @subject = CONCAT(N'Phiếu lương tháng ', FORMAT(@_DocDate1, 'MM/yyyy'));
            SET @body = CONCAT(N'<p>Kính gửi ', @full_name, N',</p>',
                               N'<p>Lương gross tháng ', FORMAT(@_DocDate1, 'MM/yyyy'),
                               N': ', FORMAT(@gross, 'N0', 'vi-VN'), N' đ</p>',
                               N'<p>Lương net: ', FORMAT(@net, 'N0', 'vi-VN'), N' đ</p>',
                               N'<p>Trân trọng.</p>');
            BEGIN TRY
                EXEC msdb.dbo.sp_send_dbmail
                    @profile_name = @_MailProfile,
                    @recipients   = @mail,
                    @subject      = @subject,
                    @body         = @body,
                    @body_format  = 'HTML';
                SET @sent = @sent + 1;
            END TRY
            BEGIN CATCH
                SET @failed = @failed + 1;
                PRINT CONCAT(N'Mail failed for ', @code, N': ', ERROR_MESSAGE());
            END CATCH
            FETCH NEXT FROM cur INTO @code, @mail, @full_name, @gross, @net;
        END
        CLOSE cur;
        DEALLOCATE cur;

        SELECT
            CONCAT(N'Đã gửi ', @sent, N' email phiếu lương, ',
                   @failed, N' lỗi.') AS Message,
            @sent   AS SentCount,
            @failed AS FailedCount;
    END
END;
GO
