/*******************************************************************************
 * Enterprise Payroll ERP — SQL Server Scalar Functions
 *
 * 6 scalar functions for attendance and payroll calculations.
 * Vietnamese business context: BHXH/BHYT/BHTN insurance, PIT brackets,
 * night shift premium, OT rates.
 *
 * Run AFTER all CREATE TABLE statements.
 ******************************************************************************/

-- =============================================================================
-- 1. fn_calc_late_minutes
--    Returns minutes the employee was late. 0 if on time or early.
--    @check_in_time   — actual check-in datetime
--    @shift_start_time — scheduled shift start (TIME)
--    @grace_minutes   — allowed grace period before counting as late
-- =============================================================================
GO
CREATE OR ALTER FUNCTION dbo.fn_calc_late_minutes (
    @check_in_time   DATETIME,
    @shift_start_time TIME,
    @grace_minutes   INT
)
RETURNS INT
AS
BEGIN
    DECLARE @result INT = 0;
    DECLARE @actual_time TIME = CAST(@check_in_time AS TIME);
    DECLARE @grace_deadline TIME;

    -- If no check-in, return 0 (handled separately as anomaly)
    IF @check_in_time IS NULL
        RETURN 0;

    -- Add grace minutes to shift start to get the deadline
    SET @grace_deadline = DATEADD(MINUTE, ISNULL(@grace_minutes, 0), @shift_start_time);

    -- If actual check-in is after the grace deadline, calculate late minutes
    IF @actual_time > @grace_deadline
    BEGIN
        SET @result = DATEDIFF(MINUTE, @shift_start_time, @actual_time);
        -- Ensure non-negative
        IF @result < 0
            SET @result = 0;
    END

    RETURN @result;
END;
GO


-- =============================================================================
-- 2. fn_calc_early_minutes
--    Returns minutes the employee left early. 0 if on time or stayed late.
--    @check_out_time  — actual check-out datetime
--    @shift_end_time  — scheduled shift end (TIME)
-- =============================================================================
GO
CREATE OR ALTER FUNCTION dbo.fn_calc_early_minutes (
    @check_out_time  DATETIME,
    @shift_end_time  TIME
)
RETURNS INT
AS
BEGIN
    DECLARE @result INT = 0;
    DECLARE @actual_time TIME = CAST(@check_out_time AS TIME);

    IF @check_out_time IS NULL
        RETURN 0;

    -- If actual check-out is before shift end, calculate early minutes
    IF @actual_time < @shift_end_time
    BEGIN
        SET @result = DATEDIFF(MINUTE, @actual_time, @shift_end_time);
        IF @result < 0
            SET @result = 0;
    END

    RETURN @result;
END;
GO


-- =============================================================================
-- 3. fn_calc_night_hours
--    Returns decimal hours worked during the night window (22:00 - 06:00).
--    Handles overnight shifts where check_in is before midnight and
--    check_out is after midnight.
--    Night shift premium = 30% extra per Vietnamese labor law.
-- =============================================================================
GO
CREATE OR ALTER FUNCTION dbo.fn_calc_night_hours (
    @check_in_time   DATETIME,
    @check_out_time  DATETIME
)
RETURNS DECIMAL(5, 2)
AS
BEGIN
    IF @check_in_time IS NULL OR @check_out_time IS NULL
        RETURN 0.00;

    -- Ensure check_out is after check_in
    IF @check_out_time <= @check_in_time
        RETURN 0.00;

    DECLARE @night_minutes INT = 0;
    DECLARE @current_date DATE = CAST(@check_in_time AS DATE);
    DECLARE @end_date DATE = CAST(@check_out_time AS DATE);

    -- Iterate through each calendar day the shift spans
    WHILE @current_date <= @end_date
    BEGIN
        -- Night window for this day: 22:00 of current_date to 06:00 of next day
        DECLARE @night_start DATETIME = DATEADD(HOUR, 22, CAST(@current_date AS DATETIME));
        DECLARE @night_end   DATETIME = DATEADD(HOUR, 30, CAST(@current_date AS DATETIME)); -- 06:00 next day

        -- Also consider early morning window: 00:00 to 06:00 of current_date
        DECLARE @morning_start DATETIME = CAST(@current_date AS DATETIME); -- 00:00
        DECLARE @morning_end   DATETIME = DATEADD(HOUR, 6, CAST(@current_date AS DATETIME));  -- 06:00

        -- Calculate overlap with morning window (00:00-06:00)
        DECLARE @overlap_start DATETIME;
        DECLARE @overlap_end   DATETIME;

        -- Morning portion (00:00 - 06:00) only if current_date > check_in date
        -- (to avoid double-counting with previous day's evening window)
        IF @current_date > CAST(@check_in_time AS DATE)
        BEGIN
            SET @overlap_start = CASE WHEN @check_in_time > @morning_start THEN @check_in_time ELSE @morning_start END;
            SET @overlap_end   = CASE WHEN @check_out_time < @morning_end THEN @check_out_time ELSE @morning_end END;

            IF @overlap_end > @overlap_start
                SET @night_minutes = @night_minutes + DATEDIFF(MINUTE, @overlap_start, @overlap_end);
        END

        -- Evening portion (22:00 - 24:00 of current day, continuing to 06:00 next day)
        SET @overlap_start = CASE WHEN @check_in_time > @night_start THEN @check_in_time ELSE @night_start END;
        SET @overlap_end   = CASE WHEN @check_out_time < @night_end THEN @check_out_time ELSE @night_end END;

        IF @overlap_end > @overlap_start
            SET @night_minutes = @night_minutes + DATEDIFF(MINUTE, @overlap_start, @overlap_end);

        SET @current_date = DATEADD(DAY, 1, @current_date);
    END

    RETURN CAST(@night_minutes AS DECIMAL(5, 2)) / 60.0;
END;
GO


-- =============================================================================
-- 4. fn_calc_workday_value
--    Returns a decimal workday value based on actual vs standard hours.
--    1.0 = full day, 0.5 = half day, 0.0 = absent.
--    Threshold: >= standard_hours => 1.0
--              >= standard_hours / 2 => 0.5
--              else => 0.0
-- =============================================================================
GO
CREATE OR ALTER FUNCTION dbo.fn_calc_workday_value (
    @actual_hours   DECIMAL(5, 2),
    @standard_hours DECIMAL(5, 2)
)
RETURNS DECIMAL(3, 1)
AS
BEGIN
    IF @standard_hours IS NULL OR @standard_hours <= 0
        RETURN 0.0;

    IF @actual_hours IS NULL OR @actual_hours <= 0
        RETURN 0.0;

    -- Full day
    IF @actual_hours >= @standard_hours
        RETURN 1.0;

    -- Half day: worked at least half the standard hours
    IF @actual_hours >= (@standard_hours / 2.0)
        RETURN 0.5;

    RETURN 0.0;
END;
GO


-- =============================================================================
-- 5. fn_calc_pit
--    Vietnamese Progressive Personal Income Tax (PIT) calculation.
--    Input: @taxable_income — monthly taxable income in VND
--           (already after personal deduction 11,000,000 and
--            dependent deductions 4,400,000/person)
--
--    7 brackets (per Vietnamese tax law):
--      Up to  5,000,000  =>  5%
--      5M  - 10,000,000  => 10%
--      10M - 18,000,000  => 15%
--      18M - 32,000,000  => 20%
--      32M - 52,000,000  => 25%
--      52M - 80,000,000  => 30%
--      Over 80,000,000   => 35%
-- =============================================================================
GO
CREATE OR ALTER FUNCTION dbo.fn_calc_pit (
    @taxable_income DECIMAL(18, 2)
)
RETURNS DECIMAL(18, 2)
AS
BEGIN
    IF @taxable_income IS NULL OR @taxable_income <= 0
        RETURN 0.00;

    DECLARE @tax DECIMAL(18, 2) = 0.00;
    DECLARE @remaining DECIMAL(18, 2) = @taxable_income;

    -- Bracket 1: 0 - 5,000,000 => 5%
    IF @remaining > 0
    BEGIN
        DECLARE @b1 DECIMAL(18, 2) = CASE WHEN @remaining > 5000000 THEN 5000000 ELSE @remaining END;
        SET @tax = @tax + @b1 * 0.05;
        SET @remaining = @remaining - @b1;
    END

    -- Bracket 2: 5,000,001 - 10,000,000 => 10%
    IF @remaining > 0
    BEGIN
        DECLARE @b2 DECIMAL(18, 2) = CASE WHEN @remaining > 5000000 THEN 5000000 ELSE @remaining END;
        SET @tax = @tax + @b2 * 0.10;
        SET @remaining = @remaining - @b2;
    END

    -- Bracket 3: 10,000,001 - 18,000,000 => 15%
    IF @remaining > 0
    BEGIN
        DECLARE @b3 DECIMAL(18, 2) = CASE WHEN @remaining > 8000000 THEN 8000000 ELSE @remaining END;
        SET @tax = @tax + @b3 * 0.15;
        SET @remaining = @remaining - @b3;
    END

    -- Bracket 4: 18,000,001 - 32,000,000 => 20%
    IF @remaining > 0
    BEGIN
        DECLARE @b4 DECIMAL(18, 2) = CASE WHEN @remaining > 14000000 THEN 14000000 ELSE @remaining END;
        SET @tax = @tax + @b4 * 0.20;
        SET @remaining = @remaining - @b4;
    END

    -- Bracket 5: 32,000,001 - 52,000,000 => 25%
    IF @remaining > 0
    BEGIN
        DECLARE @b5 DECIMAL(18, 2) = CASE WHEN @remaining > 20000000 THEN 20000000 ELSE @remaining END;
        SET @tax = @tax + @b5 * 0.25;
        SET @remaining = @remaining - @b5;
    END

    -- Bracket 6: 52,000,001 - 80,000,000 => 30%
    IF @remaining > 0
    BEGIN
        DECLARE @b6 DECIMAL(18, 2) = CASE WHEN @remaining > 28000000 THEN 28000000 ELSE @remaining END;
        SET @tax = @tax + @b6 * 0.30;
        SET @remaining = @remaining - @b6;
    END

    -- Bracket 7: Over 80,000,000 => 35%
    IF @remaining > 0
    BEGIN
        SET @tax = @tax + @remaining * 0.35;
    END

    RETURN @tax;
END;
GO


-- =============================================================================
-- 6. fn_salary_proration
--    Prorates monthly salary based on actual working days vs standard
--    working days in the period.
--    @monthly_salary  — full monthly salary from contract
--    @standard_days   — standard working days in the period (e.g., 22)
--    @actual_days     — actual workday value (can be decimal, e.g., 20.5)
-- =============================================================================
GO
CREATE OR ALTER FUNCTION dbo.fn_salary_proration (
    @monthly_salary  DECIMAL(18, 2),
    @standard_days   DECIMAL(5, 1),
    @actual_days     DECIMAL(5, 1)
)
RETURNS DECIMAL(18, 2)
AS
BEGIN
    IF @monthly_salary IS NULL OR @monthly_salary <= 0
        RETURN 0.00;

    IF @standard_days IS NULL OR @standard_days <= 0
        RETURN 0.00;

    IF @actual_days IS NULL OR @actual_days < 0
        RETURN 0.00;

    -- If actual >= standard, return full salary (no overpay from proration)
    IF @actual_days >= @standard_days
        RETURN @monthly_salary;

    RETURN ROUND(@monthly_salary * @actual_days / @standard_days, 0);
END;
GO
