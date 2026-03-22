/*******************************************************************************
 * Enterprise Payroll ERP — SQL Server Stored Procedures
 *
 * 14 stored procedures total: 8 core attendance/payroll procedures + 6 report wrappers.
 * Vietnamese business context:
 *   - Insurance (employee): BHXH 8%, BHYT 1.5%, BHTN 1% = 10.5%
 *   - Insurance (employer): BHXH 17.5%, BHYT 3%, BHTN 1% = 21.5%
 *   - Insurance salary cap: 36,000,000 VND (20x minimum wage 1,800,000)
 *   - PIT personal deduction: 11,000,000 VND/month
 *   - PIT dependent deduction: 4,400,000 VND/person/month
 *   - Night shift premium: 30% extra
 *   - OT rates: 150% weekday, 200% weekend, 300% holiday
 *
 * Depends on: all tables, views, and functions.
 ******************************************************************************/

-- =============================================================================
-- 1. sp_import_time_logs
--    Bulk import time logs from a staging table. Detects anomalies
--    (missing pair, duplicates) and marks them.
--    Expects data in a staging table: staging_time_logs
--    (employee_code, log_time, machine_number, log_type, source, raw_ref)
-- =============================================================================
GO
CREATE OR ALTER PROCEDURE dbo.sp_import_time_logs
    @batch_source   NVARCHAR(20) = 'machine',
    @imported_count INT          = 0 OUTPUT,
    @invalid_count  INT          = 0 OUTPUT
AS
BEGIN
    SET NOCOUNT ON;

    BEGIN TRY
        BEGIN TRANSACTION;

        -- Insert valid logs (employee_code matches an employee)
        INSERT INTO time_logs (employee_id, log_time, machine_number, log_type, source, is_valid, raw_ref, created_at)
        SELECT
            e.id,
            stl.log_time,
            stl.machine_number,
            stl.log_type,
            @batch_source,
            1,                  -- is_valid = true initially
            stl.raw_ref,
            GETDATE()
        FROM staging_time_logs stl
        JOIN employees e ON e.employee_code = stl.employee_code
        WHERE NOT EXISTS (
            -- Prevent exact duplicates (same employee, same log_time)
            SELECT 1
            FROM time_logs tl
            WHERE tl.employee_id = e.id
              AND tl.log_time = stl.log_time
        );

        SET @imported_count = @@ROWCOUNT;

        -- Count rows that could not be matched to an employee
        SELECT @invalid_count = COUNT(*)
        FROM staging_time_logs stl
        WHERE NOT EXISTS (
            SELECT 1 FROM employees e WHERE e.employee_code = stl.employee_code
        );

        -- Mark anomalies: detect days with only check_in or only check_out
        -- after the import by checking log pairs per employee per day
        UPDATE tl
        SET tl.is_valid = 0,
            tl.invalid_reason = CASE
                WHEN daily.check_in_count = 0 THEN N'Khong co check-in trong ngay'
                WHEN daily.check_out_count = 0 THEN N'Khong co check-out trong ngay'
                WHEN daily.total_count > 4 THEN N'Qua nhieu log trong ngay'
                ELSE NULL
            END
        FROM time_logs tl
        CROSS APPLY (
            SELECT
                COUNT(CASE WHEN t2.log_type = 'check_in' THEN 1 END)  AS check_in_count,
                COUNT(CASE WHEN t2.log_type = 'check_out' THEN 1 END) AS check_out_count,
                COUNT(*)                                               AS total_count
            FROM time_logs t2
            WHERE t2.employee_id = tl.employee_id
              AND CAST(t2.log_time AS DATE) = CAST(tl.log_time AS DATE)
              AND t2.is_valid = 1
        ) daily
        WHERE tl.is_valid = 1
          AND tl.created_at >= DATEADD(MINUTE, -5, GETDATE())  -- Only check recently imported
          AND (daily.check_in_count = 0 OR daily.check_out_count = 0 OR daily.total_count > 4);

        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;

        THROW;
    END CATCH
END;
GO


-- =============================================================================
-- 2. sp_generate_attendance_daily
--    Calculate attendance_daily records from time_logs + shift_assignments
--    for all employees in a given attendance period.
-- =============================================================================
GO
CREATE OR ALTER PROCEDURE dbo.sp_generate_attendance_daily
    @attendance_period_id BIGINT,
    @generated_count      INT = 0 OUTPUT
AS
BEGIN
    SET NOCOUNT ON;

    BEGIN TRY
        BEGIN TRANSACTION;

        -- Validate period exists and is in draft/generated status
        DECLARE @period_status NVARCHAR(30);
        DECLARE @from_date DATE;
        DECLARE @to_date DATE;

        SELECT @period_status = status, @from_date = from_date, @to_date = to_date
        FROM attendance_periods
        WHERE id = @attendance_period_id;

        IF @period_status IS NULL
        BEGIN
            RAISERROR(N'Attendance period %d not found.', 16, 1, @attendance_period_id);
            RETURN;
        END

        IF @period_status NOT IN ('draft', 'generated')
        BEGIN
            RAISERROR(N'Attendance period is in status [%s], cannot regenerate.', 16, 1, @period_status);
            RETURN;
        END

        -- Remove existing unconfirmed daily records for this period
        -- (keep employee-confirmed records to avoid overwriting)
        DELETE FROM attendance_daily
        WHERE attendance_period_id = @attendance_period_id
          AND is_confirmed_by_employee = 0;

        -- Generate daily attendance for each employee + shift_assignment
        -- in the period date range
        INSERT INTO attendance_daily (
            employee_id, work_date, attendance_period_id, shift_assignment_id,
            first_in, last_out, late_minutes, early_minutes,
            regular_hours, ot_hours, night_hours, workday_value,
            meal_count, attendance_status, source_status,
            calculation_version, created_at, updated_at
        )
        SELECT
            sa.employee_id,
            sa.work_date,
            @attendance_period_id,
            sa.id AS shift_assignment_id,

            -- first_in: earliest check_in log for that day
            ci.first_in,

            -- last_out: latest check_out log for that day
            co.last_out,

            -- Late minutes
            ISNULL(dbo.fn_calc_late_minutes(ci.first_in, s.start_time, s.grace_late_minutes), 0),

            -- Early minutes
            ISNULL(dbo.fn_calc_early_minutes(co.last_out, s.end_time), 0),

            -- Regular hours = actual worked hours (capped at shift duration)
            CASE
                WHEN ci.first_in IS NOT NULL AND co.last_out IS NOT NULL
                THEN CAST(
                    CASE
                        WHEN DATEDIFF(MINUTE, ci.first_in, co.last_out) / 60.0 > shift_hrs.standard_hours
                        THEN shift_hrs.standard_hours
                        ELSE DATEDIFF(MINUTE, ci.first_in, co.last_out) / 60.0
                    END AS DECIMAL(4, 1))
                ELSE 0
            END,

            -- OT hours = hours beyond standard shift
            CASE
                WHEN ci.first_in IS NOT NULL AND co.last_out IS NOT NULL
                    AND DATEDIFF(MINUTE, ci.first_in, co.last_out) / 60.0 > shift_hrs.standard_hours
                THEN CAST(
                    DATEDIFF(MINUTE, ci.first_in, co.last_out) / 60.0 - shift_hrs.standard_hours
                    AS DECIMAL(4, 1))
                ELSE 0
            END,

            -- Night hours
            ISNULL(dbo.fn_calc_night_hours(ci.first_in, co.last_out), 0),

            -- Workday value
            CASE
                WHEN ci.first_in IS NOT NULL AND co.last_out IS NOT NULL
                THEN dbo.fn_calc_workday_value(
                    CAST(DATEDIFF(MINUTE, ci.first_in, co.last_out) / 60.0 AS DECIMAL(5, 2)),
                    shift_hrs.standard_hours
                )
                ELSE 0.0
            END,

            -- Meal count: 1 if worked >= min_meal_hours
            CASE
                WHEN ci.first_in IS NOT NULL AND co.last_out IS NOT NULL
                    AND DATEDIFF(MINUTE, ci.first_in, co.last_out) / 60.0 >= s.min_meal_hours
                THEN 1
                ELSE 0
            END,

            -- Attendance status
            CASE
                WHEN ci.first_in IS NULL AND co.last_out IS NULL THEN 'absent'
                WHEN ci.first_in IS NULL OR co.last_out IS NULL THEN 'anomaly'
                ELSE 'present'
            END,

            -- Source status
            CASE
                WHEN ci.first_in IS NOT NULL OR co.last_out IS NOT NULL THEN 'machine'
                ELSE NULL
            END,

            1,          -- calculation_version
            GETDATE(),
            GETDATE()

        FROM shift_assignments sa
        JOIN shifts s ON s.id = sa.shift_id
        CROSS APPLY (
            SELECT
                CASE
                    WHEN s.is_overnight = 1
                    THEN CAST(DATEDIFF(MINUTE, s.start_time,
                         DATEADD(DAY, 1, CAST(s.end_time AS DATETIME))) / 60.0 AS DECIMAL(5, 2))
                    ELSE CAST(DATEDIFF(MINUTE, s.start_time, s.end_time) / 60.0 AS DECIMAL(5, 2))
                END AS standard_hours
        ) shift_hrs
        -- First check_in of the day
        OUTER APPLY (
            SELECT MIN(tl.log_time) AS first_in
            FROM time_logs tl
            WHERE tl.employee_id = sa.employee_id
              AND CAST(tl.log_time AS DATE) = sa.work_date
              AND tl.log_type = 'check_in'
              AND tl.is_valid = 1
        ) ci
        -- Last check_out of the day
        OUTER APPLY (
            SELECT MAX(tl.log_time) AS last_out
            FROM time_logs tl
            WHERE tl.employee_id = sa.employee_id
              AND (CAST(tl.log_time AS DATE) = sa.work_date
                   OR (s.is_overnight = 1 AND CAST(tl.log_time AS DATE) = DATEADD(DAY, 1, sa.work_date)))
              AND tl.log_type = 'check_out'
              AND tl.is_valid = 1
        ) co
        WHERE sa.work_date BETWEEN @from_date AND @to_date
          -- Skip if already confirmed by employee
          AND NOT EXISTS (
            SELECT 1 FROM attendance_daily ad2
            WHERE ad2.employee_id = sa.employee_id
              AND ad2.work_date = sa.work_date
              AND ad2.is_confirmed_by_employee = 1
          );

        SET @generated_count = @@ROWCOUNT;

        -- Update period status to 'generated'
        UPDATE attendance_periods
        SET status = 'generated', updated_at = GETDATE()
        WHERE id = @attendance_period_id
          AND status = 'draft';

        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;

        THROW;
    END CATCH
END;
GO


-- =============================================================================
-- 3. sp_generate_attendance_summary
--    Aggregate attendance_daily into attendance_monthly_summary for a
--    given attendance period.
-- =============================================================================
GO
CREATE OR ALTER PROCEDURE dbo.sp_generate_attendance_summary
    @attendance_period_id BIGINT,
    @generated_count      INT = 0 OUTPUT
AS
BEGIN
    SET NOCOUNT ON;

    BEGIN TRY
        BEGIN TRANSACTION;

        -- Validate period
        DECLARE @period_status NVARCHAR(30);

        SELECT @period_status = status
        FROM attendance_periods
        WHERE id = @attendance_period_id;

        IF @period_status IS NULL
        BEGIN
            RAISERROR(N'Attendance period %d not found.', 16, 1, @attendance_period_id);
            RETURN;
        END

        IF @period_status NOT IN ('draft', 'generated')
        BEGIN
            RAISERROR(N'Attendance period is in status [%s], cannot generate summary.', 16, 1, @period_status);
            RETURN;
        END

        -- Delete existing summaries for this period (to regenerate)
        DELETE FROM attendance_monthly_summary
        WHERE attendance_period_id = @attendance_period_id;

        -- Aggregate from attendance_daily
        INSERT INTO attendance_monthly_summary (
            attendance_period_id, employee_id,
            total_workdays, regular_hours, ot_hours, night_hours,
            paid_leave_days, unpaid_leave_days,
            late_minutes, early_minutes, meal_count,
            status, generated_at, created_at, updated_at
        )
        SELECT
            @attendance_period_id,
            ad.employee_id,
            SUM(ad.workday_value),
            SUM(ad.regular_hours),
            SUM(ad.ot_hours),
            SUM(ad.night_hours),
            -- paid_leave_days: count of days with leave status that are paid
            SUM(CASE WHEN ad.attendance_status = 'paid_leave' THEN ad.workday_value ELSE 0 END),
            -- unpaid_leave_days: absent days
            SUM(CASE WHEN ad.attendance_status = 'absent' THEN 1.0 ELSE 0 END),
            SUM(ad.late_minutes),
            SUM(ad.early_minutes),
            SUM(ad.meal_count),
            'generated',
            GETDATE(),
            GETDATE(),
            GETDATE()
        FROM attendance_daily ad
        WHERE ad.attendance_period_id = @attendance_period_id
        GROUP BY ad.employee_id;

        SET @generated_count = @@ROWCOUNT;

        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;

        THROW;
    END CATCH
END;
GO


-- =============================================================================
-- 4. sp_apply_attendance_request
--    Apply an approved attendance request to attendance_daily records.
--    Updates check-in/check-out times and recalculates affected metrics.
-- =============================================================================
GO
CREATE OR ALTER PROCEDURE dbo.sp_apply_attendance_request
    @request_id       BIGINT,
    @applied_count    INT = 0 OUTPUT
AS
BEGIN
    SET NOCOUNT ON;

    BEGIN TRY
        BEGIN TRANSACTION;

        -- Validate request exists and is approved
        DECLARE @employee_id BIGINT;
        DECLARE @request_status NVARCHAR(20);
        DECLARE @request_type NVARCHAR(30);

        SELECT
            @employee_id = employee_id,
            @request_status = status,
            @request_type = request_type
        FROM attendance_requests
        WHERE id = @request_id;

        IF @employee_id IS NULL
        BEGIN
            RAISERROR(N'Attendance request %d not found.', 16, 1, @request_id);
            RETURN;
        END

        IF @request_status <> 'approved'
        BEGIN
            RAISERROR(N'Request is in status [%s], must be approved first.', 16, 1, @request_status);
            RETURN;
        END

        -- Apply each detail line to the corresponding attendance_daily record
        SET @applied_count = 0;

        -- Update attendance_daily for each request detail
        UPDATE ad
        SET
            ad.first_in = COALESCE(ard.requested_check_in, ad.first_in),
            ad.last_out = COALESCE(ard.requested_check_out, ad.last_out),
            ad.source_status = 'request',
            -- Recalculate late/early/hours based on updated times
            ad.late_minutes = ISNULL(
                dbo.fn_calc_late_minutes(
                    COALESCE(ard.requested_check_in, ad.first_in),
                    s.start_time,
                    s.grace_late_minutes
                ), 0),
            ad.early_minutes = ISNULL(
                dbo.fn_calc_early_minutes(
                    COALESCE(ard.requested_check_out, ad.last_out),
                    s.end_time
                ), 0),
            ad.regular_hours = CASE
                WHEN COALESCE(ard.requested_check_in, ad.first_in) IS NOT NULL
                    AND COALESCE(ard.requested_check_out, ad.last_out) IS NOT NULL
                THEN CAST(DATEDIFF(MINUTE,
                    COALESCE(ard.requested_check_in, ad.first_in),
                    COALESCE(ard.requested_check_out, ad.last_out)) / 60.0
                    AS DECIMAL(4, 1))
                ELSE 0
            END,
            ad.night_hours = ISNULL(
                dbo.fn_calc_night_hours(
                    COALESCE(ard.requested_check_in, ad.first_in),
                    COALESCE(ard.requested_check_out, ad.last_out)
                ), 0),
            ad.attendance_status = CASE
                WHEN @request_type = 'leave' THEN 'paid_leave'
                WHEN COALESCE(ard.requested_check_in, ad.first_in) IS NOT NULL
                    AND COALESCE(ard.requested_check_out, ad.last_out) IS NOT NULL
                THEN 'present'
                ELSE ad.attendance_status
            END,
            ad.calculation_version = ad.calculation_version + 1,
            ad.updated_at = GETDATE()
        FROM attendance_daily ad
        JOIN attendance_request_details ard ON ard.work_date = ad.work_date
        LEFT JOIN shift_assignments sa ON sa.id = ad.shift_assignment_id
        LEFT JOIN shifts s ON s.id = sa.shift_id
        WHERE ard.request_id = @request_id
          AND ad.employee_id = @employee_id;

        SET @applied_count = @@ROWCOUNT;

        -- Mark request as applied
        UPDATE attendance_requests
        SET status = 'applied', updated_at = GETDATE()
        WHERE id = @request_id;

        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;

        THROW;
    END CATCH
END;
GO


-- =============================================================================
-- 5. sp_preview_payroll
--    Calculate payroll preview for a given attendance period and scope.
--    Creates payroll_run with status 'previewed' and payslip records.
--
--    Calculation flow:
--    1. Prorated base salary = fn_salary_proration(base_salary, std_days, actual_days)
--    2. Allowances from contract_allowances
--    3. OT pay = hourly_rate * ot_hours * OT_multiplier
--    4. Night premium = hourly_rate * night_hours * 0.30
--    5. Gross = prorated_salary + allowances + OT + night + bonus
--    6. Insurance base = MIN(base_salary, 36,000,000)
--    7. Insurance employee = insurance_base * 10.5%
--    8. Insurance company  = insurance_base * 21.5%
--    9. Taxable income = gross - insurance_employee - personal_deduction - dependent_deductions
--   10. PIT = fn_calc_pit(taxable_income)
--   11. Net = gross - insurance_employee - PIT - deductions
-- =============================================================================
GO
CREATE OR ALTER PROCEDURE dbo.sp_preview_payroll
    @attendance_period_id BIGINT,
    @scope_type           NVARCHAR(20)  = 'all',        -- 'all', 'department', 'employee'
    @scope_value          NVARCHAR(50)  = NULL,          -- department_id or employee_id
    @requested_by         BIGINT        = NULL,
    @standard_days        DECIMAL(5, 1) = 22.0,         -- standard working days in period
    @payroll_run_id       BIGINT        = 0 OUTPUT
AS
BEGIN
    SET NOCOUNT ON;

    -- Vietnamese insurance & deduction constants
    DECLARE @INSURANCE_SALARY_CAP   DECIMAL(18, 2) = 36000000.00;  -- 20x minimum wage
    DECLARE @INS_RATE_EMP_BHXH      DECIMAL(5, 4)  = 0.0800;       -- 8%
    DECLARE @INS_RATE_EMP_BHYT      DECIMAL(5, 4)  = 0.0150;       -- 1.5%
    DECLARE @INS_RATE_EMP_BHTN      DECIMAL(5, 4)  = 0.0100;       -- 1%
    DECLARE @INS_RATE_COMP_BHXH     DECIMAL(5, 4)  = 0.1750;       -- 17.5%
    DECLARE @INS_RATE_COMP_BHYT     DECIMAL(5, 4)  = 0.0300;       -- 3%
    DECLARE @INS_RATE_COMP_BHTN     DECIMAL(5, 4)  = 0.0100;       -- 1%
    DECLARE @PERSONAL_DEDUCTION     DECIMAL(18, 2) = 11000000.00;  -- 11M/month
    DECLARE @DEPENDENT_DEDUCTION    DECIMAL(18, 2) = 4400000.00;   -- 4.4M/person/month
    DECLARE @OT_RATE_WEEKDAY        DECIMAL(3, 2)  = 1.50;
    DECLARE @OT_RATE_WEEKEND        DECIMAL(3, 2)  = 2.00;
    DECLARE @OT_RATE_HOLIDAY        DECIMAL(3, 2)  = 3.00;
    DECLARE @NIGHT_PREMIUM_RATE     DECIMAL(3, 2)  = 0.30;

    BEGIN TRY
        BEGIN TRANSACTION;

        -- Validate attendance period
        DECLARE @period_status NVARCHAR(30);
        SELECT @period_status = status FROM attendance_periods WHERE id = @attendance_period_id;

        IF @period_status IS NULL
        BEGIN
            RAISERROR(N'Attendance period %d not found.', 16, 1, @attendance_period_id);
            RETURN;
        END

        -- Create payroll_run record
        DECLARE @run_no INT;
        SELECT @run_no = ISNULL(MAX(run_no), 0) + 1
        FROM payroll_runs
        WHERE attendance_period_id = @attendance_period_id;

        INSERT INTO payroll_runs (
            attendance_period_id, run_no, scope_type, scope_value,
            status, requested_by, previewed_at, created_at, updated_at
        )
        VALUES (
            @attendance_period_id, @run_no, @scope_type, @scope_value,
            'previewed', @requested_by, GETDATE(), GETDATE(), GETDATE()
        );

        SET @payroll_run_id = SCOPE_IDENTITY();

        -- Build payslips using a temp table for intermediate calculations
        CREATE TABLE #payroll_calc (
            employee_id             BIGINT,
            contract_id             BIGINT,
            base_salary             DECIMAL(18, 2),
            probation_rate          DECIMAL(5, 2),
            total_workdays          DECIMAL(4, 1),
            ot_hours                DECIMAL(5, 1),
            night_hours             DECIMAL(5, 1),
            dependent_count         INT,
            prorated_salary         DECIMAL(18, 2),
            allowance_total         DECIMAL(18, 2),
            ot_pay                  DECIMAL(18, 2),
            night_premium           DECIMAL(18, 2),
            bonus_total             DECIMAL(18, 2),
            deduction_total         DECIMAL(18, 2),
            gross_salary            DECIMAL(18, 2),
            insurance_base          DECIMAL(18, 2),
            insurance_employee      DECIMAL(18, 2),
            insurance_company       DECIMAL(18, 2),
            taxable_income          DECIMAL(18, 2),
            pit_amount              DECIMAL(18, 2),
            net_salary              DECIMAL(18, 2)
        );

        -- Step 1: Gather base data per employee
        INSERT INTO #payroll_calc (
            employee_id, contract_id, base_salary, probation_rate,
            total_workdays, ot_hours, night_hours, dependent_count,
            prorated_salary, allowance_total, ot_pay, night_premium,
            bonus_total, deduction_total, gross_salary,
            insurance_base, insurance_employee, insurance_company,
            taxable_income, pit_amount, net_salary
        )
        SELECT
            eac.employee_id,
            eac.contract_id,
            eac.base_salary,
            eac.probation_rate,
            ISNULL(ams.total_workdays, 0),
            ISNULL(ams.ot_hours, 0),
            ISNULL(ams.night_hours, 0),
            ISNULL(dep.dep_count, 0),
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0  -- calculated below
        FROM vw_employee_active_contract eac
        JOIN attendance_monthly_summary ams
            ON ams.employee_id = eac.employee_id
           AND ams.attendance_period_id = @attendance_period_id
        LEFT JOIN (
            SELECT employee_id, COUNT(*) AS dep_count
            FROM dependents
            WHERE tax_reduction_from <= GETDATE()
              AND (tax_reduction_to IS NULL OR tax_reduction_to >= GETDATE())
            GROUP BY employee_id
        ) dep ON dep.employee_id = eac.employee_id
        WHERE eac.contract_id IS NOT NULL
          AND (
              @scope_type = 'all'
              OR (@scope_type = 'department' AND eac.department_id = CAST(@scope_value AS BIGINT))
              OR (@scope_type = 'employee'   AND eac.employee_id  = CAST(@scope_value AS BIGINT))
          );

        -- Step 2: Prorated salary (applying probation rate)
        UPDATE #payroll_calc
        SET prorated_salary = dbo.fn_salary_proration(
                base_salary * probation_rate / 100.0,
                @standard_days,
                total_workdays
            );

        -- Step 3: Allowances from contract_allowances
        UPDATE pc
        SET pc.allowance_total = ISNULL(al.total_allowance, 0)
        FROM #payroll_calc pc
        LEFT JOIN (
            SELECT ca.contract_id, SUM(ca.amount) AS total_allowance
            FROM contract_allowances ca
            WHERE ca.effective_from <= GETDATE()
              AND (ca.effective_to IS NULL OR ca.effective_to >= GETDATE())
            GROUP BY ca.contract_id
        ) al ON al.contract_id = pc.contract_id;

        -- Step 4: OT pay (using weekday rate as default; holidays handled via payslip_items)
        UPDATE #payroll_calc
        SET ot_pay = CASE
            WHEN base_salary > 0 AND @standard_days > 0
            THEN ROUND(
                (base_salary * probation_rate / 100.0 / @standard_days / 8.0)
                * ot_hours * @OT_RATE_WEEKDAY, 0)
            ELSE 0
        END;

        -- Step 5: Night premium
        UPDATE #payroll_calc
        SET night_premium = CASE
            WHEN base_salary > 0 AND @standard_days > 0
            THEN ROUND(
                (base_salary * probation_rate / 100.0 / @standard_days / 8.0)
                * night_hours * @NIGHT_PREMIUM_RATE, 0)
            ELSE 0
        END;

        -- Step 6: Bonus/deduction from bonus_deductions table
        UPDATE pc
        SET pc.bonus_total = ISNULL(bd_bonus.total_amount, 0),
            pc.deduction_total = ISNULL(bd_deduct.total_amount, 0)
        FROM #payroll_calc pc
        LEFT JOIN (
            SELECT bd.employee_id, SUM(bd.amount) AS total_amount
            FROM bonus_deductions bd
            JOIN bonus_deduction_types bdt ON bdt.id = bd.type_id
            WHERE bd.attendance_period_id = @attendance_period_id
              AND bd.status = 'active'
              AND bdt.kind = 'bonus'
            GROUP BY bd.employee_id
        ) bd_bonus ON bd_bonus.employee_id = pc.employee_id
        LEFT JOIN (
            SELECT bd.employee_id, SUM(bd.amount) AS total_amount
            FROM bonus_deductions bd
            JOIN bonus_deduction_types bdt ON bdt.id = bd.type_id
            WHERE bd.attendance_period_id = @attendance_period_id
              AND bd.status = 'active'
              AND bdt.kind = 'deduction'
            GROUP BY bd.employee_id
        ) bd_deduct ON bd_deduct.employee_id = pc.employee_id;

        -- Step 7: Gross salary
        UPDATE #payroll_calc
        SET gross_salary = prorated_salary + allowance_total + ot_pay
                         + night_premium + bonus_total;

        -- Step 8: Insurance
        UPDATE #payroll_calc
        SET insurance_base = CASE
                WHEN base_salary > @INSURANCE_SALARY_CAP THEN @INSURANCE_SALARY_CAP
                ELSE base_salary
            END,
            insurance_employee = ROUND(
                CASE
                    WHEN base_salary > @INSURANCE_SALARY_CAP THEN @INSURANCE_SALARY_CAP
                    ELSE base_salary
                END * (@INS_RATE_EMP_BHXH + @INS_RATE_EMP_BHYT + @INS_RATE_EMP_BHTN), 0),
            insurance_company = ROUND(
                CASE
                    WHEN base_salary > @INSURANCE_SALARY_CAP THEN @INSURANCE_SALARY_CAP
                    ELSE base_salary
                END * (@INS_RATE_COMP_BHXH + @INS_RATE_COMP_BHYT + @INS_RATE_COMP_BHTN), 0);

        -- Step 9: Taxable income
        UPDATE #payroll_calc
        SET taxable_income = CASE
            WHEN (gross_salary - insurance_employee - @PERSONAL_DEDUCTION
                  - (dependent_count * @DEPENDENT_DEDUCTION)) > 0
            THEN gross_salary - insurance_employee - @PERSONAL_DEDUCTION
                 - (dependent_count * @DEPENDENT_DEDUCTION)
            ELSE 0
        END;

        -- Step 10: PIT
        UPDATE #payroll_calc
        SET pit_amount = dbo.fn_calc_pit(taxable_income);

        -- Step 11: Net salary
        UPDATE #payroll_calc
        SET net_salary = gross_salary - insurance_employee - pit_amount - deduction_total;

        -- Insert payslips
        INSERT INTO payslips (
            attendance_period_id, employee_id, payroll_run_id, contract_id,
            base_salary_snapshot, gross_salary, taxable_income,
            insurance_base, insurance_employee, insurance_company,
            pit_amount, bonus_total, deduction_total, net_salary,
            status, generated_at, created_at, updated_at
        )
        SELECT
            @attendance_period_id, employee_id, @payroll_run_id, contract_id,
            base_salary, gross_salary, taxable_income,
            insurance_base, insurance_employee, insurance_company,
            pit_amount, bonus_total, deduction_total, net_salary,
            'previewed', GETDATE(), GETDATE(), GETDATE()
        FROM #payroll_calc;

        -- Insert payslip_items for each payslip (itemized breakdown)
        -- BASE salary item
        INSERT INTO payslip_items (payslip_id, item_code, item_name, item_group, qty, rate, amount, sort_order, source_ref)
        SELECT ps.id, 'BASE', N'Luong co ban', 'earning',
               pc.total_workdays, pc.base_salary / @standard_days,
               pc.prorated_salary, 10, 'contract'
        FROM payslips ps
        JOIN #payroll_calc pc ON pc.employee_id = ps.employee_id
        WHERE ps.payroll_run_id = @payroll_run_id;

        -- Allowance item
        INSERT INTO payslip_items (payslip_id, item_code, item_name, item_group, qty, rate, amount, sort_order, source_ref)
        SELECT ps.id, 'ALLOWANCE', N'Phu cap', 'earning',
               NULL, NULL, pc.allowance_total, 20, 'contract_allowances'
        FROM payslips ps
        JOIN #payroll_calc pc ON pc.employee_id = ps.employee_id
        WHERE ps.payroll_run_id = @payroll_run_id
          AND pc.allowance_total > 0;

        -- OT item
        INSERT INTO payslip_items (payslip_id, item_code, item_name, item_group, qty, rate, amount, sort_order, source_ref)
        SELECT ps.id, 'OT', N'Lam them', 'earning',
               pc.ot_hours, pc.base_salary * pc.probation_rate / 100.0 / @standard_days / 8.0 * @OT_RATE_WEEKDAY,
               pc.ot_pay, 30, 'attendance'
        FROM payslips ps
        JOIN #payroll_calc pc ON pc.employee_id = ps.employee_id
        WHERE ps.payroll_run_id = @payroll_run_id
          AND pc.ot_pay > 0;

        -- Night premium item
        INSERT INTO payslip_items (payslip_id, item_code, item_name, item_group, qty, rate, amount, sort_order, source_ref)
        SELECT ps.id, 'NIGHT', N'Phu cap dem', 'earning',
               pc.night_hours, pc.base_salary * pc.probation_rate / 100.0 / @standard_days / 8.0 * @NIGHT_PREMIUM_RATE,
               pc.night_premium, 35, 'attendance'
        FROM payslips ps
        JOIN #payroll_calc pc ON pc.employee_id = ps.employee_id
        WHERE ps.payroll_run_id = @payroll_run_id
          AND pc.night_premium > 0;

        -- Bonus items (one per bonus_deduction record of kind=bonus)
        INSERT INTO payslip_items (payslip_id, item_code, item_name, item_group, qty, rate, amount, sort_order, source_ref)
        SELECT ps.id, 'BONUS_' + bdt.code, bdt.name, 'earning',
               NULL, NULL, bd.amount, 40, CONCAT('bonus_deductions:', bd.id)
        FROM payslips ps
        JOIN bonus_deductions bd ON bd.employee_id = ps.employee_id
            AND bd.attendance_period_id = @attendance_period_id
            AND bd.status = 'active'
        JOIN bonus_deduction_types bdt ON bdt.id = bd.type_id AND bdt.kind = 'bonus'
        WHERE ps.payroll_run_id = @payroll_run_id;

        -- Insurance employee item
        INSERT INTO payslip_items (payslip_id, item_code, item_name, item_group, qty, rate, amount, sort_order, source_ref)
        SELECT ps.id, 'INS_EMP', N'Bao hiem (NLD)', 'deduction',
               NULL, 0.105, pc.insurance_employee, 50, 'insurance'
        FROM payslips ps
        JOIN #payroll_calc pc ON pc.employee_id = ps.employee_id
        WHERE ps.payroll_run_id = @payroll_run_id
          AND pc.insurance_employee > 0;

        -- PIT item
        INSERT INTO payslip_items (payslip_id, item_code, item_name, item_group, qty, rate, amount, sort_order, source_ref)
        SELECT ps.id, 'PIT', N'Thue TNCN', 'deduction',
               NULL, NULL, pc.pit_amount, 60, 'pit'
        FROM payslips ps
        JOIN #payroll_calc pc ON pc.employee_id = ps.employee_id
        WHERE ps.payroll_run_id = @payroll_run_id
          AND pc.pit_amount > 0;

        -- Deduction items (one per bonus_deduction record of kind=deduction)
        INSERT INTO payslip_items (payslip_id, item_code, item_name, item_group, qty, rate, amount, sort_order, source_ref)
        SELECT ps.id, 'DED_' + bdt.code, bdt.name, 'deduction',
               NULL, NULL, bd.amount, 70, CONCAT('bonus_deductions:', bd.id)
        FROM payslips ps
        JOIN bonus_deductions bd ON bd.employee_id = ps.employee_id
            AND bd.attendance_period_id = @attendance_period_id
            AND bd.status = 'active'
        JOIN bonus_deduction_types bdt ON bdt.id = bd.type_id AND bdt.kind = 'deduction'
        WHERE ps.payroll_run_id = @payroll_run_id;

        -- Insurance company item (informational, not deducted from employee)
        INSERT INTO payslip_items (payslip_id, item_code, item_name, item_group, qty, rate, amount, sort_order, source_ref)
        SELECT ps.id, 'INS_COMP', N'Bao hiem (DN)', 'employer_cost',
               NULL, 0.215, pc.insurance_company, 80, 'insurance'
        FROM payslips ps
        JOIN #payroll_calc pc ON pc.employee_id = ps.employee_id
        WHERE ps.payroll_run_id = @payroll_run_id
          AND pc.insurance_company > 0;

        DROP TABLE #payroll_calc;

        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;

        IF OBJECT_ID('tempdb..#payroll_calc') IS NOT NULL
            DROP TABLE #payroll_calc;

        THROW;
    END CATCH
END;
GO


-- =============================================================================
-- 6. sp_finalize_payroll
--    Snapshot all payslip data, update payroll_run status to 'finalized'.
--    After finalization, payslip data becomes immutable (snapshot).
-- =============================================================================
GO
CREATE OR ALTER PROCEDURE dbo.sp_finalize_payroll
    @payroll_run_id   BIGINT,
    @finalized_by     BIGINT     = NULL,
    @finalized_count  INT        = 0 OUTPUT
AS
BEGIN
    SET NOCOUNT ON;

    BEGIN TRY
        BEGIN TRANSACTION;

        -- Validate payroll_run exists and is in 'previewed' status
        DECLARE @current_status NVARCHAR(20);
        DECLARE @attendance_period_id BIGINT;

        SELECT @current_status = status, @attendance_period_id = attendance_period_id
        FROM payroll_runs
        WHERE id = @payroll_run_id;

        IF @current_status IS NULL
        BEGIN
            RAISERROR(N'Payroll run %d not found.', 16, 1, @payroll_run_id);
            RETURN;
        END

        IF @current_status <> 'previewed'
        BEGIN
            RAISERROR(N'Payroll run is in status [%s], can only finalize from previewed.', 16, 1, @current_status);
            RETURN;
        END

        -- Verify attendance period is at least 'confirmed'
        DECLARE @period_status NVARCHAR(30);
        SELECT @period_status = status
        FROM attendance_periods
        WHERE id = @attendance_period_id;

        IF @period_status NOT IN ('confirmed', 'locked')
        BEGIN
            RAISERROR(N'Attendance period must be confirmed or locked before finalizing payroll. Current status: [%s].', 16, 1, @period_status);
            RETURN;
        END

        -- Update payroll_run status to finalized
        UPDATE payroll_runs
        SET status = 'finalized',
            finalized_at = GETDATE(),
            finalized_by = @finalized_by,
            updated_at = GETDATE()
        WHERE id = @payroll_run_id;

        -- Update all payslips to finalized status and snapshot the data
        UPDATE payslips
        SET status = 'finalized',
            updated_at = GETDATE()
        WHERE payroll_run_id = @payroll_run_id
          AND status = 'previewed';

        SET @finalized_count = @@ROWCOUNT;

        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;

        THROW;
    END CATCH
END;
GO


-- =============================================================================
-- 7. sp_lock_payroll_period
--    Lock a payroll_run (status -> 'locked'), preventing any further changes.
--    This is the final step in the payroll lifecycle.
-- =============================================================================
GO
CREATE OR ALTER PROCEDURE dbo.sp_lock_payroll_period
    @payroll_run_id  BIGINT,
    @locked_by       BIGINT     = NULL,
    @locked_count    INT        = 0 OUTPUT
AS
BEGIN
    SET NOCOUNT ON;

    BEGIN TRY
        BEGIN TRANSACTION;

        -- Validate payroll_run exists and is in 'finalized' status
        DECLARE @current_status NVARCHAR(20);

        SELECT @current_status = status
        FROM payroll_runs
        WHERE id = @payroll_run_id;

        IF @current_status IS NULL
        BEGIN
            RAISERROR(N'Payroll run %d not found.', 16, 1, @payroll_run_id);
            RETURN;
        END

        IF @current_status <> 'finalized'
        BEGIN
            RAISERROR(N'Payroll run is in status [%s], can only lock from finalized.', 16, 1, @current_status);
            RETURN;
        END

        -- Lock the payroll_run
        UPDATE payroll_runs
        SET status = 'locked',
            locked_at = GETDATE(),
            locked_by = @locked_by,
            updated_at = GETDATE()
        WHERE id = @payroll_run_id;

        -- Lock all payslips
        UPDATE payslips
        SET status = 'locked',
            locked_at = GETDATE(),
            updated_at = GETDATE()
        WHERE payroll_run_id = @payroll_run_id
          AND status = 'finalized';

        SET @locked_count = @@ROWCOUNT;

        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;

        THROW;
    END CATCH
END;
GO


-- =============================================================================
-- 8. sp_report_payroll_summary
--    Generate summary report data by department for a given payroll period.
--    Returns a result set with aggregated payroll data per department.
-- =============================================================================
GO
CREATE OR ALTER PROCEDURE dbo.sp_report_payroll_summary
    @attendance_period_id BIGINT,
    @payroll_run_id       BIGINT       = NULL,
    @department_id        BIGINT       = NULL
AS
BEGIN
    SET NOCOUNT ON;

    -- If no specific payroll_run_id, use the latest finalized/locked run
    -- for the given period
    IF @payroll_run_id IS NULL
    BEGIN
        SELECT TOP 1 @payroll_run_id = id
        FROM payroll_runs
        WHERE attendance_period_id = @attendance_period_id
          AND status IN ('finalized', 'locked')
        ORDER BY finalized_at DESC;

        IF @payroll_run_id IS NULL
        BEGIN
            RAISERROR(N'No finalized/locked payroll run found for period %d.', 16, 1, @attendance_period_id);
            RETURN;
        END
    END

    -- Summary by department
    SELECT
        d.id                        AS department_id,
        d.code                      AS department_code,
        d.name                      AS department_name,
        COUNT(ps.id)                AS employee_count,
        SUM(ps.base_salary_snapshot) AS total_base_salary,
        SUM(ps.gross_salary)        AS total_gross_salary,
        SUM(ps.bonus_total)         AS total_bonus,
        SUM(ps.deduction_total)     AS total_deduction,
        SUM(ps.insurance_employee)  AS total_insurance_employee,
        SUM(ps.insurance_company)   AS total_insurance_company,
        SUM(ps.pit_amount)          AS total_pit,
        SUM(ps.net_salary)          AS total_net_salary,
        -- Employer total cost = net + insurance_employee + PIT + insurance_company
        SUM(ps.gross_salary + ps.insurance_company) AS total_labor_cost
    FROM payslips ps
    JOIN employees e        ON e.id = ps.employee_id
    LEFT JOIN departments d ON d.id = e.department_id
    WHERE ps.payroll_run_id = @payroll_run_id
      AND (@department_id IS NULL OR e.department_id = @department_id)
    GROUP BY d.id, d.code, d.name
    ORDER BY d.name;

    -- Grand total
    SELECT
        COUNT(ps.id)                AS total_employees,
        SUM(ps.base_salary_snapshot) AS grand_base_salary,
        SUM(ps.gross_salary)        AS grand_gross_salary,
        SUM(ps.bonus_total)         AS grand_bonus,
        SUM(ps.deduction_total)     AS grand_deduction,
        SUM(ps.insurance_employee)  AS grand_insurance_employee,
        SUM(ps.insurance_company)   AS grand_insurance_company,
        SUM(ps.pit_amount)          AS grand_pit,
        SUM(ps.net_salary)          AS grand_net_salary,
        SUM(ps.gross_salary + ps.insurance_company) AS grand_labor_cost
    FROM payslips ps
    JOIN employees e ON e.id = ps.employee_id
    WHERE ps.payroll_run_id = @payroll_run_id
      AND (@department_id IS NULL OR e.department_id = @department_id);
END;
GO

-- =============================================================================
-- 9. sp_Report_AttendanceDaily
--    Attendance daily report wrapper used by report_templates.
-- =============================================================================
GO
CREATE OR ALTER PROCEDURE dbo.sp_Report_AttendanceDaily
    @attendance_period_id BIGINT = NULL,
    @employee_id          BIGINT = NULL,
    @department_id        BIGINT = NULL,
    @from_date            DATE   = NULL,
    @to_date              DATE   = NULL
AS
BEGIN
    SET NOCOUNT ON;

    SELECT *
    FROM dbo.vw_attendance_daily_detail
    WHERE (@attendance_period_id IS NULL OR attendance_period_id = @attendance_period_id)
      AND (@employee_id IS NULL OR employee_id = @employee_id)
      AND (@department_id IS NULL OR department_id = @department_id)
      AND (@from_date IS NULL OR work_date >= @from_date)
      AND (@to_date IS NULL OR work_date <= @to_date)
    ORDER BY work_date, employee_code;
END;
GO

-- =============================================================================
-- 10. sp_Report_AttendanceMonthly
--    Attendance monthly summary report wrapper used by report_templates.
-- =============================================================================
GO
CREATE OR ALTER PROCEDURE dbo.sp_Report_AttendanceMonthly
    @attendance_period_id BIGINT = NULL,
    @employee_id          BIGINT = NULL,
    @department_id        BIGINT = NULL
AS
BEGIN
    SET NOCOUNT ON;

    SELECT *
    FROM dbo.vw_attendance_monthly_summary
    WHERE (@attendance_period_id IS NULL OR attendance_period_id = @attendance_period_id)
      AND (@employee_id IS NULL OR employee_id = @employee_id)
      AND (@department_id IS NULL OR department_id = @department_id)
    ORDER BY department_name, employee_code;
END;
GO

-- =============================================================================
-- 11. sp_Report_PayrollSummary
--    Payroll summary report wrapper used by report_templates.
-- =============================================================================
GO
CREATE OR ALTER PROCEDURE dbo.sp_Report_PayrollSummary
    @attendance_period_id BIGINT,
    @payroll_run_id       BIGINT = NULL,
    @department_id        BIGINT = NULL
AS
BEGIN
    SET NOCOUNT ON;

    EXEC dbo.sp_report_payroll_summary
        @attendance_period_id = @attendance_period_id,
        @payroll_run_id = @payroll_run_id,
        @department_id = @department_id;
END;
GO

-- =============================================================================
-- 12. sp_Report_Payslip
--    Payslip detail report wrapper used by report_templates.
-- =============================================================================
GO
CREATE OR ALTER PROCEDURE dbo.sp_Report_Payslip
    @attendance_period_id BIGINT = NULL,
    @payroll_run_id       BIGINT = NULL,
    @employee_id          BIGINT = NULL,
    @payslip_id           BIGINT = NULL
AS
BEGIN
    SET NOCOUNT ON;

    SELECT *
    FROM dbo.vw_payslip_print
    WHERE (@attendance_period_id IS NULL OR attendance_period_id = @attendance_period_id)
      AND (@payroll_run_id IS NULL OR payroll_run_id = @payroll_run_id)
      AND (@employee_id IS NULL OR employee_id = @employee_id)
      AND (@payslip_id IS NULL OR payslip_id = @payslip_id)
    ORDER BY employee_code;

    SELECT
        pi.*,
        ps.employee_id,
        ps.attendance_period_id,
        ps.payroll_run_id
    FROM dbo.payslip_items pi
    JOIN dbo.payslips ps ON ps.id = pi.payslip_id
    WHERE (@attendance_period_id IS NULL OR ps.attendance_period_id = @attendance_period_id)
      AND (@payroll_run_id IS NULL OR ps.payroll_run_id = @payroll_run_id)
      AND (@employee_id IS NULL OR ps.employee_id = @employee_id)
      AND (@payslip_id IS NULL OR ps.id = @payslip_id)
    ORDER BY ps.employee_id, pi.sort_order;
END;
GO

-- =============================================================================
-- 13. sp_Report_Insurance
--    Insurance summary report wrapper used by report_templates.
-- =============================================================================
GO
CREATE OR ALTER PROCEDURE dbo.sp_Report_Insurance
    @attendance_period_id BIGINT,
    @department_id        BIGINT = NULL
AS
BEGIN
    SET NOCOUNT ON;

    SELECT
        ps.attendance_period_id,
        e.department_id,
        d.code              AS department_code,
        d.name              AS department_name,
        ps.employee_id,
        e.employee_code,
        e.full_name,
        SUM(ps.insurance_base)     AS insurance_base,
        SUM(ps.insurance_employee) AS insurance_employee,
        SUM(ps.insurance_company)  AS insurance_company
    FROM dbo.payslips ps
    JOIN dbo.employees e ON e.id = ps.employee_id
    LEFT JOIN dbo.departments d ON d.id = e.department_id
    WHERE ps.attendance_period_id = @attendance_period_id
      AND (@department_id IS NULL OR e.department_id = @department_id)
    GROUP BY
        ps.attendance_period_id,
        e.department_id,
        d.code,
        d.name,
        ps.employee_id,
        e.employee_code,
        e.full_name
    ORDER BY d.name, e.employee_code;
END;
GO

-- =============================================================================
-- 14. sp_Report_PIT
--    PIT summary report wrapper used by report_templates.
-- =============================================================================
GO
CREATE OR ALTER PROCEDURE dbo.sp_Report_PIT
    @attendance_period_id BIGINT,
    @department_id        BIGINT = NULL
AS
BEGIN
    SET NOCOUNT ON;

    SELECT
        ps.attendance_period_id,
        e.department_id,
        d.code           AS department_code,
        d.name           AS department_name,
        ps.employee_id,
        e.employee_code,
        e.full_name,
        SUM(ps.taxable_income) AS taxable_income,
        SUM(ps.pit_amount)     AS pit_amount
    FROM dbo.payslips ps
    JOIN dbo.employees e ON e.id = ps.employee_id
    LEFT JOIN dbo.departments d ON d.id = e.department_id
    WHERE ps.attendance_period_id = @attendance_period_id
      AND (@department_id IS NULL OR e.department_id = @department_id)
    GROUP BY
        ps.attendance_period_id,
        e.department_id,
        d.code,
        d.name,
        ps.employee_id,
        e.employee_code,
        e.full_name
    ORDER BY d.name, e.employee_code;
END;
GO
