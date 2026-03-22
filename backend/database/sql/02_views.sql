/*******************************************************************************
 * Enterprise Payroll ERP — SQL Server Views
 *
 * 6 views for the HRM attendance/payroll system.
 * Depends on: employees, labour_contracts, departments, positions,
 *             contract_types, shifts, attendance_periods, attendance_daily,
 *             attendance_monthly_summary, time_logs, shift_assignments,
 *             payroll_runs, payslips, payslip_items
 *
 * Run AFTER all CREATE TABLE statements.
 ******************************************************************************/

-- =============================================================================
-- 1. vw_employee_active_contract
--    Joins employees with their currently active labour contract,
--    department, and position. Only ONE row per employee (the latest
--    active contract by start_date).
-- =============================================================================
GO
CREATE OR ALTER VIEW dbo.vw_employee_active_contract
AS
WITH ranked_contracts AS (
    SELECT
        lc.*,
        ROW_NUMBER() OVER (
            PARTITION BY lc.employee_id
            ORDER BY lc.start_date DESC, lc.id DESC
        ) AS rn
    FROM labour_contracts lc
    WHERE lc.status = 'active'
      AND lc.start_date <= GETDATE()
      AND (lc.end_date IS NULL OR lc.end_date >= GETDATE())
)
SELECT
    e.id                        AS employee_id,
    e.employee_code,
    e.full_name,
    e.dob,
    e.gender,
    e.national_id,
    e.tax_code,
    e.email,
    e.phone,
    e.bank_account_no,
    e.bank_name,
    e.join_date,
    e.employment_status,
    d.id                        AS department_id,
    d.code                      AS department_code,
    d.name                      AS department_name,
    p.id                        AS position_id,
    p.code                      AS position_code,
    p.name                      AS position_name,
    rc.id                       AS contract_id,
    rc.contract_no,
    rc.contract_type_id,
    ct.name                     AS contract_type_name,
    rc.start_date               AS contract_start_date,
    rc.end_date                 AS contract_end_date,
    rc.base_salary,
    rc.salary_level_id,
    rc.payroll_type_id,
    rc.probation_rate
FROM employees e
LEFT JOIN departments d         ON d.id = e.department_id
LEFT JOIN positions p           ON p.id = e.position_id
LEFT JOIN ranked_contracts rc   ON rc.employee_id = e.id AND rc.rn = 1
LEFT JOIN contract_types ct     ON ct.id = rc.contract_type_id
WHERE e.employment_status = 'active';
GO


-- =============================================================================
-- 2. vw_time_log_anomalies
--    Time logs that are: (a) marked invalid, (b) missing a paired
--    check_in or check_out on the same day, or (c) have duplicate
--    entries (more than 2 logs) on the same day for the same employee.
-- =============================================================================
GO
CREATE OR ALTER VIEW dbo.vw_time_log_anomalies
AS
WITH daily_log_stats AS (
    SELECT
        tl.employee_id,
        CAST(tl.log_time AS DATE)       AS log_date,
        COUNT(*)                         AS log_count,
        MIN(CASE WHEN tl.log_type = 'check_in'  THEN tl.log_time END) AS first_check_in,
        MIN(CASE WHEN tl.log_type = 'check_out' THEN tl.log_time END) AS first_check_out
    FROM time_logs tl
    WHERE tl.is_valid = 1
    GROUP BY tl.employee_id, CAST(tl.log_time AS DATE)
)
-- Explicitly invalid logs
SELECT
    tl.id               AS time_log_id,
    tl.employee_id,
    e.employee_code,
    e.full_name,
    CAST(tl.log_time AS DATE) AS log_date,
    tl.log_time,
    tl.log_type,
    tl.source,
    tl.machine_number,
    'invalid'           AS anomaly_type,
    tl.invalid_reason   AS anomaly_detail
FROM time_logs tl
JOIN employees e ON e.id = tl.employee_id
WHERE tl.is_valid = 0

UNION ALL

-- Missing check_out (has check_in but no check_out)
SELECT
    NULL                AS time_log_id,
    dls.employee_id,
    e.employee_code,
    e.full_name,
    dls.log_date,
    dls.first_check_in  AS log_time,
    'check_in'          AS log_type,
    NULL                AS source,
    NULL                AS machine_number,
    'missing_check_out' AS anomaly_type,
    N'Co check-in nhung khong co check-out' AS anomaly_detail
FROM daily_log_stats dls
JOIN employees e ON e.id = dls.employee_id
WHERE dls.first_check_in IS NOT NULL
  AND dls.first_check_out IS NULL

UNION ALL

-- Missing check_in (has check_out but no check_in)
SELECT
    NULL                AS time_log_id,
    dls.employee_id,
    e.employee_code,
    e.full_name,
    dls.log_date,
    dls.first_check_out AS log_time,
    'check_out'         AS log_type,
    NULL                AS source,
    NULL                AS machine_number,
    'missing_check_in'  AS anomaly_type,
    N'Co check-out nhung khong co check-in' AS anomaly_detail
FROM daily_log_stats dls
JOIN employees e ON e.id = dls.employee_id
WHERE dls.first_check_out IS NOT NULL
  AND dls.first_check_in IS NULL

UNION ALL

-- Duplicate logs (more than 2 entries on the same day)
SELECT
    NULL                AS time_log_id,
    dls.employee_id,
    e.employee_code,
    e.full_name,
    dls.log_date,
    NULL                AS log_time,
    NULL                AS log_type,
    NULL                AS source,
    NULL                AS machine_number,
    'duplicate'         AS anomaly_type,
    CONCAT(N'Co ', dls.log_count, N' log trong ngay (binh thuong la 2)') AS anomaly_detail
FROM daily_log_stats dls
JOIN employees e ON e.id = dls.employee_id
WHERE dls.log_count > 2;
GO


-- =============================================================================
-- 3. vw_attendance_daily_detail
--    Attendance daily records joined with employee info, shift info,
--    and attendance period for a detailed operational view.
-- =============================================================================
GO
CREATE OR ALTER VIEW dbo.vw_attendance_daily_detail
AS
SELECT
    ad.id                       AS attendance_daily_id,
    ad.work_date,
    ad.attendance_period_id,
    ap.period_code,
    ap.month                    AS period_month,
    ap.year                     AS period_year,
    ap.status                   AS period_status,
    ad.employee_id,
    e.employee_code,
    e.full_name,
    e.department_id,
    d.name                      AS department_name,
    ad.shift_assignment_id,
    s.id                        AS shift_id,
    s.code                      AS shift_code,
    s.name                      AS shift_name,
    s.start_time                AS shift_start_time,
    s.end_time                  AS shift_end_time,
    s.is_overnight              AS shift_is_overnight,
    ad.first_in,
    ad.last_out,
    ad.late_minutes,
    ad.early_minutes,
    ad.regular_hours,
    ad.ot_hours,
    ad.night_hours,
    ad.workday_value,
    ad.meal_count,
    ad.attendance_status,
    ad.source_status,
    ad.is_confirmed_by_employee,
    ad.confirmed_at,
    ad.calculation_version,
    h.id                        AS holiday_id,
    h.name                      AS holiday_name,
    h.multiplier                AS holiday_multiplier
FROM attendance_daily ad
JOIN employees e                ON e.id = ad.employee_id
JOIN attendance_periods ap      ON ap.id = ad.attendance_period_id
LEFT JOIN departments d         ON d.id = e.department_id
LEFT JOIN shift_assignments sa  ON sa.id = ad.shift_assignment_id
LEFT JOIN shifts s              ON s.id = sa.shift_id
LEFT JOIN holidays h            ON h.holiday_date = ad.work_date;
GO


-- =============================================================================
-- 4. vw_attendance_monthly_summary
--    Attendance monthly summary joined with employee and department
--    data for reporting purposes.
-- =============================================================================
GO
CREATE OR ALTER VIEW dbo.vw_attendance_monthly_summary
AS
SELECT
    ams.id                      AS summary_id,
    ams.attendance_period_id,
    ap.period_code,
    ap.month                    AS period_month,
    ap.year                     AS period_year,
    ap.from_date                AS period_from_date,
    ap.to_date                  AS period_to_date,
    ap.status                   AS period_status,
    ams.employee_id,
    e.employee_code,
    e.full_name,
    e.department_id,
    d.code                      AS department_code,
    d.name                      AS department_name,
    e.position_id,
    p.name                      AS position_name,
    ams.total_workdays,
    ams.regular_hours,
    ams.ot_hours,
    ams.night_hours,
    ams.paid_leave_days,
    ams.unpaid_leave_days,
    ams.late_minutes,
    ams.early_minutes,
    ams.meal_count,
    ams.status                  AS summary_status,
    ams.generated_at,
    ams.confirmed_at
FROM attendance_monthly_summary ams
JOIN employees e                ON e.id = ams.employee_id
JOIN attendance_periods ap      ON ap.id = ams.attendance_period_id
LEFT JOIN departments d         ON d.id = e.department_id
LEFT JOIN positions p           ON p.id = e.position_id;
GO


-- =============================================================================
-- 5. vw_payslip_print
--    Payslips joined with employee, payroll run, and attendance period
--    for a print-ready payslip view. Includes contract snapshot data.
-- =============================================================================
GO
CREATE OR ALTER VIEW dbo.vw_payslip_print
AS
SELECT
    ps.id                       AS payslip_id,
    ps.payroll_run_id,
    pr.run_no,
    pr.status                   AS payroll_run_status,
    ps.attendance_period_id,
    ap.period_code,
    ap.month                    AS period_month,
    ap.year                     AS period_year,
    ap.from_date                AS period_from_date,
    ap.to_date                  AS period_to_date,
    ps.employee_id,
    e.employee_code,
    e.full_name,
    e.national_id,
    e.tax_code,
    e.bank_account_no,
    e.bank_name,
    e.department_id,
    d.name                      AS department_name,
    pos.name                    AS position_name,
    ps.contract_id,
    ps.base_salary_snapshot,
    ps.gross_salary,
    ps.taxable_income,
    ps.insurance_base,
    ps.insurance_employee,
    ps.insurance_company,
    ps.pit_amount,
    ps.bonus_total,
    ps.deduction_total,
    ps.net_salary,
    ps.status                   AS payslip_status,
    ps.generated_at,
    ps.locked_at,
    -- Attendance summary for the same period (for display on payslip)
    ams.total_workdays,
    ams.regular_hours,
    ams.ot_hours,
    ams.night_hours,
    ams.paid_leave_days,
    ams.unpaid_leave_days,
    ams.late_minutes,
    ams.early_minutes,
    ams.meal_count
FROM payslips ps
JOIN payroll_runs pr            ON pr.id = ps.payroll_run_id
JOIN attendance_periods ap      ON ap.id = ps.attendance_period_id
JOIN employees e                ON e.id = ps.employee_id
LEFT JOIN departments d         ON d.id = e.department_id
LEFT JOIN positions pos         ON pos.id = e.position_id
LEFT JOIN attendance_monthly_summary ams
    ON ams.attendance_period_id = ps.attendance_period_id
   AND ams.employee_id = ps.employee_id;
GO


-- =============================================================================
-- 6. vw_payroll_summary_by_department
--    Aggregate payroll data grouped by department for a given payroll run.
-- =============================================================================
GO
CREATE OR ALTER VIEW dbo.vw_payroll_summary_by_department
AS
SELECT
    ps.payroll_run_id,
    pr.status                   AS payroll_run_status,
    ps.attendance_period_id,
    ap.period_code,
    ap.month                    AS period_month,
    ap.year                     AS period_year,
    e.department_id,
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
    SUM(ps.net_salary)          AS total_net_salary
FROM payslips ps
JOIN payroll_runs pr            ON pr.id = ps.payroll_run_id
JOIN attendance_periods ap      ON ap.id = ps.attendance_period_id
JOIN employees e                ON e.id = ps.employee_id
LEFT JOIN departments d         ON d.id = e.department_id
GROUP BY
    ps.payroll_run_id,
    pr.status,
    ps.attendance_period_id,
    ap.period_code,
    ap.month,
    ap.year,
    e.department_id,
    d.code,
    d.name;
GO
