SET ANSI_NULLS ON;
GO
SET QUOTED_IDENTIFIER ON;
GO

CREATE TABLE [dbo].[allowance_types] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [code] NVARCHAR(255) not null, [name] NVARCHAR(255) not null, [is_taxable] BIT not null DEFAULT 0, [is_insurance_base] BIT not null DEFAULT 0, [default_amount] DECIMAL(18,2) not null DEFAULT 0, [status] NVARCHAR(255) not null DEFAULT N'active', [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[attachments] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [module] NVARCHAR(255) not null, [ref_id] BIGINT not null, [file_name] NVARCHAR(255) not null, [file_path] NVARCHAR(255) not null, [mime_type] NVARCHAR(255), [uploaded_by] BIGINT, [created_at] DATETIME2 not null)
GO

CREATE TABLE [dbo].[attendance_daily] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [employee_id] BIGINT not null, [work_date] DATE not null, [attendance_period_id] BIGINT not null, [shift_assignment_id] BIGINT, [first_in] DATETIME2, [last_out] DATETIME2, [late_minutes] INT not null DEFAULT 0, [early_minutes] INT not null DEFAULT 0, [regular_hours] DECIMAL(18,2) not null DEFAULT 0, [ot_hours] DECIMAL(18,2) not null DEFAULT 0, [night_hours] DECIMAL(18,2) not null DEFAULT 0, [workday_value] DECIMAL(18,2) not null DEFAULT 0, [meal_count] INT not null DEFAULT 0, [attendance_status] NVARCHAR(255) not null DEFAULT N'absent', [source_status] NVARCHAR(255), [is_confirmed_by_employee] BIT not null DEFAULT 0, [confirmed_at] DATETIME2, [confirmed_by] BIGINT, [calculation_version] INT not null DEFAULT 1, [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[attendance_monthly_summary] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [attendance_period_id] BIGINT not null, [employee_id] BIGINT not null, [total_workdays] DECIMAL(18,2) not null DEFAULT 0, [regular_hours] DECIMAL(18,2) not null DEFAULT 0, [ot_hours] DECIMAL(18,2) not null DEFAULT 0, [night_hours] DECIMAL(18,2) not null DEFAULT 0, [paid_leave_days] DECIMAL(18,2) not null DEFAULT 0, [unpaid_leave_days] DECIMAL(18,2) not null DEFAULT 0, [late_minutes] INT not null DEFAULT 0, [early_minutes] INT not null DEFAULT 0, [meal_count] INT not null DEFAULT 0, [status] NVARCHAR(255) not null DEFAULT N'generated', [generated_at] DATETIME2, [confirmed_at] DATETIME2, [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[attendance_periods] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [period_code] NVARCHAR(255) not null, [month] INT not null, [year] INT not null, [from_date] DATE not null, [to_date] DATE not null, [status] NVARCHAR(255) not null DEFAULT N'draft', [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[attendance_request_details] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [request_id] BIGINT not null, [work_date] DATE not null, [requested_check_in] DATETIME2, [requested_check_out] DATETIME2, [requested_hours] DECIMAL(18,2), [note] NVARCHAR(MAX))
GO

CREATE TABLE [dbo].[attendance_requests] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [employee_id] BIGINT not null, [request_type] NVARCHAR(255) not null, [from_date] DATE not null, [to_date] DATE not null, [reason] NVARCHAR(MAX), [status] NVARCHAR(255) not null DEFAULT N'draft', [submitted_at] DATETIME2, [approved_by] BIGINT, [approved_at] DATETIME2, [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[audit_logs] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [actor_user_id] BIGINT, [module] NVARCHAR(255) not null, [action] NVARCHAR(255) not null, [ref_table] NVARCHAR(255), [ref_id] BIGINT, [before_json] NVARCHAR(MAX), [after_json] NVARCHAR(MAX), [ip_address] NVARCHAR(255), [created_at] DATETIME2 not null)
GO

CREATE TABLE [dbo].[bonus_deduction_types] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [code] NVARCHAR(255) not null, [name] NVARCHAR(255) not null, [kind] NVARCHAR(255) not null, [is_taxable] BIT not null DEFAULT 0, [is_insurance_base] BIT not null DEFAULT 0, [is_recurring] BIT not null DEFAULT 0, [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[bonus_deductions] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [employee_id] BIGINT not null, [attendance_period_id] BIGINT not null, [type_id] BIGINT not null, [amount] DECIMAL(18,2) not null, [description] NVARCHAR(MAX), [status] NVARCHAR(255) not null DEFAULT N'active', [created_by] BIGINT, [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[cache] ([key] NVARCHAR(255) not null, [value] NVARCHAR(MAX) not null, [expiration] INT not null, PRIMARY KEY ([key]))
GO

CREATE TABLE [dbo].[cache_locks] ([key] NVARCHAR(255) not null, [owner] NVARCHAR(255) not null, [expiration] INT not null, PRIMARY KEY ([key]))
GO

CREATE TABLE [dbo].[contract_allowances] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [contract_id] BIGINT not null, [allowance_type_id] BIGINT not null, [amount] DECIMAL(18,2) not null, [effective_from] DATE not null, [effective_to] DATE, [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[contract_types] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [code] NVARCHAR(255) not null, [name] NVARCHAR(255) not null, [duration_months] INT, [is_probationary] BIT not null DEFAULT 0, [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[departments] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [code] NVARCHAR(255) not null, [name] NVARCHAR(255) not null, [parent_id] BIGINT, [manager_employee_id] BIGINT, [status] NVARCHAR(255) not null DEFAULT N'active', [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[dependents] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [employee_id] BIGINT not null, [full_name] NVARCHAR(255) not null, [dob] DATE, [relationship] NVARCHAR(255) not null, [national_id] NVARCHAR(255), [tax_reduction_from] DATE, [tax_reduction_to] DATE, [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[employees] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [employee_code] NVARCHAR(255) not null, [user_id] BIGINT, [full_name] NVARCHAR(255) not null, [dob] DATE, [gender] NVARCHAR(255), [national_id] NVARCHAR(255), [tax_code] NVARCHAR(255), [email] NVARCHAR(255), [phone] NVARCHAR(255), [bank_account_no] NVARCHAR(255), [bank_name] NVARCHAR(255), [department_id] BIGINT, [position_id] BIGINT, [join_date] DATE, [employment_status] NVARCHAR(255) not null DEFAULT N'active', [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[failed_jobs] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [uuid] NVARCHAR(255) not null, [connection] NVARCHAR(MAX) not null, [queue] NVARCHAR(MAX) not null, [payload] NVARCHAR(MAX) not null, [exception] NVARCHAR(MAX) not null, [failed_at] DATETIME2 not null DEFAULT GETDATE())
GO

CREATE TABLE [dbo].[holidays] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [holiday_date] DATE not null, [name] NVARCHAR(255) not null, [multiplier] DECIMAL(18,2) not null DEFAULT 1, [is_paid] BIT not null DEFAULT 1, [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[job_batches] ([id] NVARCHAR(255) not null, [name] NVARCHAR(255) not null, [total_jobs] INT not null, [pending_jobs] INT not null, [failed_jobs] INT not null, [failed_job_ids] NVARCHAR(MAX) not null, [options] NVARCHAR(MAX), [cancelled_at] INT, [created_at] INT not null, [finished_at] INT, PRIMARY KEY ([id]))
GO

CREATE TABLE [dbo].[jobs] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [queue] NVARCHAR(255) not null, [payload] NVARCHAR(MAX) not null, [attempts] INT not null, [reserved_at] INT, [available_at] INT not null, [created_at] INT not null)
GO

CREATE TABLE [dbo].[labour_contracts] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [employee_id] BIGINT not null, [contract_no] NVARCHAR(255) not null, [contract_type_id] BIGINT not null, [position_title_snapshot] NVARCHAR(255), [department_snapshot] NVARCHAR(255), [start_date] DATE not null, [end_date] DATE, [sign_date] DATE, [status] NVARCHAR(255) not null DEFAULT N'draft', [base_salary] DECIMAL(18,2) not null DEFAULT 0, [salary_level_id] BIGINT, [payroll_type_id] BIGINT not null, [probation_rate] DECIMAL(18,2) not null DEFAULT 100, [created_by] BIGINT, [approved_by] BIGINT, [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[late_early_rules] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [code] NVARCHAR(255) not null, [name] NVARCHAR(255) not null, [from_minute] INT not null, [to_minute] INT not null, [deduction_type] NVARCHAR(255) not null, [deduction_value] DECIMAL(18,2) not null, [exclude_meal] BIT not null DEFAULT 0, [effective_from] DATE not null, [effective_to] DATE, [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[migrations] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [migration] NVARCHAR(255) not null, [batch] INT not null)
GO

CREATE TABLE [dbo].[password_reset_tokens] ([email] NVARCHAR(255) not null, [token] NVARCHAR(255) not null, [created_at] DATETIME2, PRIMARY KEY ([email]))
GO

CREATE TABLE [dbo].[payroll_parameter_details] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [payroll_parameter_id] BIGINT not null, [param_key] NVARCHAR(255) not null, [param_type] NVARCHAR(255) not null, [default_value] NVARCHAR(255), [validation_rule] NVARCHAR(255), [display_order] INT not null DEFAULT 0, [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[payroll_parameters] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [code] NVARCHAR(255) not null, [name] NVARCHAR(255) not null, [description] NVARCHAR(MAX), [effective_from] DATE not null, [effective_to] DATE, [formula_json] NVARCHAR(MAX), [status] NVARCHAR(255) not null DEFAULT N'active', [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[payroll_runs] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [attendance_period_id] BIGINT not null, [run_no] INT not null DEFAULT 1, [scope_type] NVARCHAR(255) not null DEFAULT N'all', [scope_value] NVARCHAR(255), [status] NVARCHAR(255) not null DEFAULT N'draft', [requested_by] BIGINT, [previewed_at] DATETIME2, [finalized_at] DATETIME2, [finalized_by] BIGINT, [locked_at] DATETIME2, [locked_by] BIGINT, [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[payroll_types] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [code] NVARCHAR(255) not null, [name] NVARCHAR(255) not null, [is_probationary] BIT not null DEFAULT 0, [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[payslip_items] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [payslip_id] BIGINT not null, [item_code] NVARCHAR(255) not null, [item_name] NVARCHAR(255) not null, [item_group] NVARCHAR(255) not null, [qty] DECIMAL(18,2), [rate] DECIMAL(18,2), [amount] DECIMAL(18,2) not null DEFAULT 0, [sort_order] INT not null DEFAULT 0, [source_ref] NVARCHAR(255))
GO

CREATE TABLE [dbo].[payslips] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [attendance_period_id] BIGINT not null, [employee_id] BIGINT not null, [payroll_run_id] BIGINT not null, [contract_id] BIGINT, [base_salary_snapshot] DECIMAL(18,2) not null DEFAULT 0, [gross_salary] DECIMAL(18,2) not null DEFAULT 0, [taxable_income] DECIMAL(18,2) not null DEFAULT 0, [insurance_base] DECIMAL(18,2) not null DEFAULT 0, [insurance_employee] DECIMAL(18,2) not null DEFAULT 0, [insurance_company] DECIMAL(18,2) not null DEFAULT 0, [pit_amount] DECIMAL(18,2) not null DEFAULT 0, [bonus_total] DECIMAL(18,2) not null DEFAULT 0, [deduction_total] DECIMAL(18,2) not null DEFAULT 0, [net_salary] DECIMAL(18,2) not null DEFAULT 0, [status] NVARCHAR(255) not null DEFAULT N'draft', [generated_at] DATETIME2, [locked_at] DATETIME2, [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[permissions] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [code] NVARCHAR(255) not null, [name] NVARCHAR(255) not null, [module] NVARCHAR(255) not null, [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[personal_access_tokens] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [tokenable_type] NVARCHAR(255) not null, [tokenable_id] BIGINT not null, [name] NVARCHAR(255) not null, [token] NVARCHAR(255) not null, [abilities] NVARCHAR(MAX), [last_used_at] DATETIME2, [expires_at] DATETIME2, [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[positions] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [code] NVARCHAR(255) not null, [name] NVARCHAR(255) not null, [department_id] BIGINT not null, [status] NVARCHAR(255) not null DEFAULT N'active', [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[report_templates] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [code] NVARCHAR(255) not null, [name] NVARCHAR(255) not null, [module] NVARCHAR(255) not null, [sp_name] NVARCHAR(255), [is_active] BIT not null DEFAULT 1, [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[role_permissions] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [role_id] BIGINT not null, [permission_id] BIGINT not null)
GO

CREATE TABLE [dbo].[roles] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [code] NVARCHAR(255) not null, [name] NVARCHAR(255) not null, [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[salary_levels] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [payroll_type_id] BIGINT not null, [code] NVARCHAR(255) not null, [level_no] INT not null, [amount] DECIMAL(18,2) not null, [effective_from] DATE not null, [effective_to] DATE, [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[sessions] ([id] NVARCHAR(255) not null, [user_id] BIGINT, [ip_address] NVARCHAR(255), [user_agent] NVARCHAR(MAX), [payload] NVARCHAR(MAX) not null, [last_activity] INT not null, PRIMARY KEY ([id]))
GO

CREATE TABLE [dbo].[shift_assignments] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [employee_id] BIGINT not null, [work_date] DATE not null, [shift_id] BIGINT not null, [source] NVARCHAR(255) not null DEFAULT N'manual', [note] NVARCHAR(MAX), [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[shifts] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [code] NVARCHAR(255) not null, [name] NVARCHAR(255) not null, [start_time] TIME not null, [end_time] TIME not null, [break_start_time] TIME, [break_end_time] TIME, [workday_value] DECIMAL(18,2) not null DEFAULT 1, [timesheet_type] NVARCHAR(255) not null DEFAULT N'standard', [is_overnight] BIT not null DEFAULT 0, [min_meal_hours] DECIMAL(18,2) not null DEFAULT 4, [grace_late_minutes] INT not null DEFAULT 0, [grace_early_minutes] INT not null DEFAULT 0, [status] NVARCHAR(255) not null DEFAULT N'active', [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[system_configs] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [config_key] NVARCHAR(255) not null, [config_value] NVARCHAR(MAX), [description] NVARCHAR(255), [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[time_logs] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [employee_id] BIGINT not null, [log_time] DATETIME2 not null, [machine_number] NVARCHAR(255), [log_type] NVARCHAR(255) not null DEFAULT N'unknown', [source] NVARCHAR(255) not null DEFAULT N'machine', [is_valid] BIT not null DEFAULT 1, [invalid_reason] NVARCHAR(255), [raw_ref] NVARCHAR(255), [created_at] DATETIME2)
GO

CREATE TABLE [dbo].[user_roles] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [user_id] BIGINT not null, [role_id] BIGINT not null, [created_at] DATETIME2, [updated_at] DATETIME2)
GO

CREATE TABLE [dbo].[users] ([id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY, [name] NVARCHAR(255) not null, [email] NVARCHAR(255) not null, [email_verified_at] DATETIME2, [password] NVARCHAR(255) not null, [remember_token] NVARCHAR(255), [created_at] DATETIME2, [updated_at] DATETIME2, [username] NVARCHAR(255) not null, [phone] NVARCHAR(255), [is_active] BIT not null DEFAULT 1, [last_login_at] DATETIME2)
GO

ALTER TABLE [dbo].[attendance_daily] ADD CONSTRAINT [FK_attendance_daily_employee_id_employees] FOREIGN KEY ([employee_id]) REFERENCES [dbo].[employees] ([id]) ON DELETE CASCADE;
GO

ALTER TABLE [dbo].[attendance_daily] ADD CONSTRAINT [FK_attendance_daily_attendance_period_id_attendance_periods] FOREIGN KEY ([attendance_period_id]) REFERENCES [dbo].[attendance_periods] ([id]) ON DELETE CASCADE;
GO

ALTER TABLE [dbo].[attendance_daily] ADD CONSTRAINT [FK_attendance_daily_shift_assignment_id_shift_assignments] FOREIGN KEY ([shift_assignment_id]) REFERENCES [dbo].[shift_assignments] ([id]) ON DELETE SET NULL;
GO

ALTER TABLE [dbo].[attendance_monthly_summary] ADD CONSTRAINT [FK_attendance_monthly_summary_attendance_period_id_attendance_periods] FOREIGN KEY ([attendance_period_id]) REFERENCES [dbo].[attendance_periods] ([id]) ON DELETE CASCADE;
GO

ALTER TABLE [dbo].[attendance_monthly_summary] ADD CONSTRAINT [FK_attendance_monthly_summary_employee_id_employees] FOREIGN KEY ([employee_id]) REFERENCES [dbo].[employees] ([id]) ON DELETE CASCADE;
GO

ALTER TABLE [dbo].[attendance_request_details] ADD CONSTRAINT [FK_attendance_request_details_request_id_attendance_requests] FOREIGN KEY ([request_id]) REFERENCES [dbo].[attendance_requests] ([id]) ON DELETE CASCADE;
GO

ALTER TABLE [dbo].[attendance_requests] ADD CONSTRAINT [FK_attendance_requests_employee_id_employees] FOREIGN KEY ([employee_id]) REFERENCES [dbo].[employees] ([id]) ON DELETE CASCADE;
GO

ALTER TABLE [dbo].[attendance_requests] ADD CONSTRAINT [FK_attendance_requests_approved_by_users] FOREIGN KEY ([approved_by]) REFERENCES [dbo].[users] ([id]);
GO

ALTER TABLE [dbo].[bonus_deductions] ADD CONSTRAINT [FK_bonus_deductions_employee_id_employees] FOREIGN KEY ([employee_id]) REFERENCES [dbo].[employees] ([id]) ON DELETE CASCADE;
GO

ALTER TABLE [dbo].[bonus_deductions] ADD CONSTRAINT [FK_bonus_deductions_attendance_period_id_attendance_periods] FOREIGN KEY ([attendance_period_id]) REFERENCES [dbo].[attendance_periods] ([id]);
GO

ALTER TABLE [dbo].[bonus_deductions] ADD CONSTRAINT [FK_bonus_deductions_type_id_bonus_deduction_types] FOREIGN KEY ([type_id]) REFERENCES [dbo].[bonus_deduction_types] ([id]);
GO

ALTER TABLE [dbo].[contract_allowances] ADD CONSTRAINT [FK_contract_allowances_contract_id_labour_contracts] FOREIGN KEY ([contract_id]) REFERENCES [dbo].[labour_contracts] ([id]) ON DELETE CASCADE;
GO

ALTER TABLE [dbo].[contract_allowances] ADD CONSTRAINT [FK_contract_allowances_allowance_type_id_allowance_types] FOREIGN KEY ([allowance_type_id]) REFERENCES [dbo].[allowance_types] ([id]);
GO

ALTER TABLE [dbo].[dependents] ADD CONSTRAINT [FK_dependents_employee_id_employees] FOREIGN KEY ([employee_id]) REFERENCES [dbo].[employees] ([id]) ON DELETE CASCADE;
GO

ALTER TABLE [dbo].[employees] ADD CONSTRAINT [FK_employees_user_id_users] FOREIGN KEY ([user_id]) REFERENCES [dbo].[users] ([id]) ON DELETE CASCADE;
GO

ALTER TABLE [dbo].[employees] ADD CONSTRAINT [FK_employees_department_id_departments] FOREIGN KEY ([department_id]) REFERENCES [dbo].[departments] ([id]) ON DELETE CASCADE;
GO

ALTER TABLE [dbo].[employees] ADD CONSTRAINT [FK_employees_position_id_positions] FOREIGN KEY ([position_id]) REFERENCES [dbo].[positions] ([id]) ON DELETE CASCADE;
GO

ALTER TABLE [dbo].[labour_contracts] ADD CONSTRAINT [FK_labour_contracts_employee_id_employees] FOREIGN KEY ([employee_id]) REFERENCES [dbo].[employees] ([id]) ON DELETE CASCADE;
GO

ALTER TABLE [dbo].[labour_contracts] ADD CONSTRAINT [FK_labour_contracts_contract_type_id_contract_types] FOREIGN KEY ([contract_type_id]) REFERENCES [dbo].[contract_types] ([id]);
GO

ALTER TABLE [dbo].[labour_contracts] ADD CONSTRAINT [FK_labour_contracts_salary_level_id_salary_levels] FOREIGN KEY ([salary_level_id]) REFERENCES [dbo].[salary_levels] ([id]);
GO

ALTER TABLE [dbo].[labour_contracts] ADD CONSTRAINT [FK_labour_contracts_payroll_type_id_payroll_types] FOREIGN KEY ([payroll_type_id]) REFERENCES [dbo].[payroll_types] ([id]);
GO

ALTER TABLE [dbo].[payroll_parameter_details] ADD CONSTRAINT [FK_payroll_parameter_details_payroll_parameter_id_payroll_parameters] FOREIGN KEY ([payroll_parameter_id]) REFERENCES [dbo].[payroll_parameters] ([id]) ON DELETE CASCADE;
GO

ALTER TABLE [dbo].[payroll_runs] ADD CONSTRAINT [FK_payroll_runs_attendance_period_id_attendance_periods] FOREIGN KEY ([attendance_period_id]) REFERENCES [dbo].[attendance_periods] ([id]);
GO

ALTER TABLE [dbo].[payslip_items] ADD CONSTRAINT [FK_payslip_items_payslip_id_payslips] FOREIGN KEY ([payslip_id]) REFERENCES [dbo].[payslips] ([id]) ON DELETE CASCADE;
GO

ALTER TABLE [dbo].[payslips] ADD CONSTRAINT [FK_payslips_employee_id_employees] FOREIGN KEY ([employee_id]) REFERENCES [dbo].[employees] ([id]) ON DELETE CASCADE;
GO

ALTER TABLE [dbo].[payslips] ADD CONSTRAINT [FK_payslips_payroll_run_id_payroll_runs] FOREIGN KEY ([payroll_run_id]) REFERENCES [dbo].[payroll_runs] ([id]) ON DELETE CASCADE;
GO

ALTER TABLE [dbo].[payslips] ADD CONSTRAINT [FK_payslips_attendance_period_id_attendance_periods] FOREIGN KEY ([attendance_period_id]) REFERENCES [dbo].[attendance_periods] ([id]);
GO

ALTER TABLE [dbo].[payslips] ADD CONSTRAINT [FK_payslips_contract_id_labour_contracts] FOREIGN KEY ([contract_id]) REFERENCES [dbo].[labour_contracts] ([id]) ON DELETE SET NULL;
GO

ALTER TABLE [dbo].[positions] ADD CONSTRAINT [FK_positions_department_id_departments] FOREIGN KEY ([department_id]) REFERENCES [dbo].[departments] ([id]) ON DELETE CASCADE;
GO

ALTER TABLE [dbo].[role_permissions] ADD CONSTRAINT [FK_role_permissions_role_id_roles] FOREIGN KEY ([role_id]) REFERENCES [dbo].[roles] ([id]) ON DELETE CASCADE;
GO

ALTER TABLE [dbo].[role_permissions] ADD CONSTRAINT [FK_role_permissions_permission_id_permissions] FOREIGN KEY ([permission_id]) REFERENCES [dbo].[permissions] ([id]) ON DELETE CASCADE;
GO

ALTER TABLE [dbo].[salary_levels] ADD CONSTRAINT [FK_salary_levels_payroll_type_id_payroll_types] FOREIGN KEY ([payroll_type_id]) REFERENCES [dbo].[payroll_types] ([id]);
GO

ALTER TABLE [dbo].[shift_assignments] ADD CONSTRAINT [FK_shift_assignments_employee_id_employees] FOREIGN KEY ([employee_id]) REFERENCES [dbo].[employees] ([id]) ON DELETE CASCADE;
GO

ALTER TABLE [dbo].[shift_assignments] ADD CONSTRAINT [FK_shift_assignments_shift_id_shifts] FOREIGN KEY ([shift_id]) REFERENCES [dbo].[shifts] ([id]);
GO

ALTER TABLE [dbo].[time_logs] ADD CONSTRAINT [FK_time_logs_employee_id_employees] FOREIGN KEY ([employee_id]) REFERENCES [dbo].[employees] ([id]) ON DELETE CASCADE;
GO

ALTER TABLE [dbo].[user_roles] ADD CONSTRAINT [FK_user_roles_user_id_users] FOREIGN KEY ([user_id]) REFERENCES [dbo].[users] ([id]) ON DELETE CASCADE;
GO

ALTER TABLE [dbo].[user_roles] ADD CONSTRAINT [FK_user_roles_role_id_roles] FOREIGN KEY ([role_id]) REFERENCES [dbo].[roles] ([id]) ON DELETE CASCADE;
GO

CREATE UNIQUE INDEX [allowance_types_code_unique] ON [dbo].[allowance_types] ([code]);
GO

CREATE UNIQUE INDEX [ams_period_employee_unique] ON [dbo].[attendance_monthly_summary] ([attendance_period_id], [employee_id]);
GO

CREATE INDEX [attachments_module_ref_index] ON [dbo].[attachments] ([module], [ref_id]);
GO

CREATE INDEX [attendance_daily_attendance_period_id_employee_id_index] ON [dbo].[attendance_daily] ([attendance_period_id], [employee_id]);
GO

CREATE UNIQUE INDEX [attendance_daily_employee_id_work_date_unique] ON [dbo].[attendance_daily] ([employee_id], [work_date]);
GO

-- =================================================================
-- Config-driven Stored Procedure Gateway tables
-- =================================================================

CREATE TABLE [dbo].[procedure_catalog] (
    [id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    [code] NVARCHAR(80) NOT NULL,
    [label] NVARCHAR(200) NOT NULL,
    [procedure_name] NVARCHAR(200) NOT NULL,
    [module] NVARCHAR(50) NOT NULL DEFAULT N'general',
    [description] NVARCHAR(MAX) NULL,
    [is_active] BIT NOT NULL DEFAULT 1,
    [created_at] DATETIME2 NULL,
    [updated_at] DATETIME2 NULL
);
GO
CREATE UNIQUE INDEX [procedure_catalog_code_unique] ON [dbo].[procedure_catalog] ([code]);
GO

CREATE TABLE [dbo].[procedure_parameters] (
    [id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    [procedure_id] BIGINT NOT NULL,
    [name] NVARCHAR(100) NOT NULL,
    [sp_param_name] NVARCHAR(100) NOT NULL,
    [type] NVARCHAR(20) NOT NULL,
    [label] NVARCHAR(200) NULL,
    [required] BIT NOT NULL DEFAULT 0,
    [default_value] NVARCHAR(200) NULL,
    [sort_order] SMALLINT NOT NULL DEFAULT 0,
    [created_at] DATETIME2 NULL,
    [updated_at] DATETIME2 NULL,
    CONSTRAINT [procedure_parameters_procedure_id_foreign] FOREIGN KEY ([procedure_id]) REFERENCES [dbo].[procedure_catalog] ([id]) ON DELETE CASCADE
);
GO
CREATE UNIQUE INDEX [procedure_parameters_procedure_id_name_unique] ON [dbo].[procedure_parameters] ([procedure_id], [name]);
GO

CREATE TABLE [dbo].[procedure_columns] (
    [id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    [procedure_id] BIGINT NOT NULL,
    [key] NVARCHAR(100) NOT NULL,
    [label] NVARCHAR(200) NOT NULL,
    [type] NVARCHAR(20) NOT NULL DEFAULT N'string',
    [visible] BIT NOT NULL DEFAULT 1,
    [exportable] BIT NOT NULL DEFAULT 1,
    [sort_order] SMALLINT NOT NULL DEFAULT 0,
    [created_at] DATETIME2 NULL,
    [updated_at] DATETIME2 NULL,
    CONSTRAINT [procedure_columns_procedure_id_foreign] FOREIGN KEY ([procedure_id]) REFERENCES [dbo].[procedure_catalog] ([id]) ON DELETE CASCADE
);
GO
CREATE UNIQUE INDEX [procedure_columns_procedure_id_key_unique] ON [dbo].[procedure_columns] ([procedure_id], [key]);
GO

CREATE TABLE [dbo].[procedure_execution_logs] (
    [id] BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    [procedure_id] BIGINT NOT NULL,
    [user_id] BIGINT NULL,
    [parameters] NVARCHAR(MAX) NULL,
    [row_count] INT NOT NULL DEFAULT 0,
    [execution_ms] INT NOT NULL DEFAULT 0,
    [status] NVARCHAR(20) NOT NULL DEFAULT N'success',
    [error_message] NVARCHAR(MAX) NULL,
    [ip_address] NVARCHAR(45) NULL,
    [executed_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
    CONSTRAINT [procedure_execution_logs_procedure_id_foreign] FOREIGN KEY ([procedure_id]) REFERENCES [dbo].[procedure_catalog] ([id]) ON DELETE CASCADE,
    CONSTRAINT [procedure_execution_logs_user_id_foreign] FOREIGN KEY ([user_id]) REFERENCES [dbo].[users] ([id]) ON DELETE SET NULL
);
GO

CREATE UNIQUE INDEX [attendance_periods_month_year_unique] ON [dbo].[attendance_periods] ([month], [year]);
GO

CREATE UNIQUE INDEX [attendance_periods_period_code_unique] ON [dbo].[attendance_periods] ([period_code]);
GO

CREATE INDEX [attendance_requests_employee_id_status_index] ON [dbo].[attendance_requests] ([employee_id], [status]);
GO

CREATE INDEX [audit_logs_actor_user_id_index] ON [dbo].[audit_logs] ([actor_user_id]);
GO

CREATE INDEX [audit_logs_created_at_index] ON [dbo].[audit_logs] ([created_at]);
GO

CREATE INDEX [audit_logs_module_index] ON [dbo].[audit_logs] ([module]);
GO

CREATE INDEX [bd_period_employee_index] ON [dbo].[bonus_deductions] ([attendance_period_id], [employee_id]);
GO

CREATE UNIQUE INDEX [bonus_deduction_types_code_unique] ON [dbo].[bonus_deduction_types] ([code]);
GO

CREATE INDEX [cache_expiration_index] ON [dbo].[cache] ([expiration]);
GO

CREATE INDEX [cache_locks_expiration_index] ON [dbo].[cache_locks] ([expiration]);
GO

CREATE UNIQUE INDEX [contract_types_code_unique] ON [dbo].[contract_types] ([code]);
GO

CREATE UNIQUE INDEX [departments_code_unique] ON [dbo].[departments] ([code]);
GO

CREATE INDEX [departments_parent_id_index] ON [dbo].[departments] ([parent_id]);
GO

CREATE UNIQUE INDEX [employees_employee_code_unique] ON [dbo].[employees] ([employee_code]);
GO

CREATE UNIQUE INDEX [employees_national_id_unique] ON [dbo].[employees] ([national_id]);
GO

CREATE UNIQUE INDEX [employees_user_id_unique] ON [dbo].[employees] ([user_id]);
GO

CREATE UNIQUE INDEX [failed_jobs_uuid_unique] ON [dbo].[failed_jobs] ([uuid]);
GO

CREATE UNIQUE INDEX [holidays_holiday_date_unique] ON [dbo].[holidays] ([holiday_date]);
GO

CREATE INDEX [jobs_queue_index] ON [dbo].[jobs] ([queue]);
GO

CREATE UNIQUE INDEX [labour_contracts_contract_no_unique] ON [dbo].[labour_contracts] ([contract_no]);
GO

CREATE UNIQUE INDEX [late_early_rules_code_unique] ON [dbo].[late_early_rules] ([code]);
GO

CREATE UNIQUE INDEX [payroll_parameters_code_unique] ON [dbo].[payroll_parameters] ([code]);
GO

CREATE UNIQUE INDEX [payroll_types_code_unique] ON [dbo].[payroll_types] ([code]);
GO

CREATE INDEX [payslips_period_employee_index] ON [dbo].[payslips] ([attendance_period_id], [employee_id]);
GO

CREATE UNIQUE INDEX [payslips_run_employee_unique] ON [dbo].[payslips] ([payroll_run_id], [employee_id]);
GO

CREATE UNIQUE INDEX [permissions_code_unique] ON [dbo].[permissions] ([code]);
GO

CREATE UNIQUE INDEX [personal_access_tokens_token_unique] ON [dbo].[personal_access_tokens] ([token]);
GO

CREATE INDEX [personal_access_tokens_tokenable_type_tokenable_id_index] ON [dbo].[personal_access_tokens] ([tokenable_type], [tokenable_id]);
GO

CREATE UNIQUE INDEX [positions_code_unique] ON [dbo].[positions] ([code]);
GO

CREATE INDEX [pr_period_status_index] ON [dbo].[payroll_runs] ([attendance_period_id], [status]);
GO

CREATE UNIQUE INDEX [report_templates_code_unique] ON [dbo].[report_templates] ([code]);
GO

CREATE UNIQUE INDEX [role_permissions_role_id_permission_id_unique] ON [dbo].[role_permissions] ([role_id], [permission_id]);
GO

CREATE UNIQUE INDEX [roles_code_unique] ON [dbo].[roles] ([code]);
GO

CREATE INDEX [sessions_last_activity_index] ON [dbo].[sessions] ([last_activity]);
GO

CREATE INDEX [sessions_user_id_index] ON [dbo].[sessions] ([user_id]);
GO

CREATE UNIQUE INDEX [shift_assignments_employee_id_work_date_unique] ON [dbo].[shift_assignments] ([employee_id], [work_date]);
GO

CREATE INDEX [shift_assignments_work_date_index] ON [dbo].[shift_assignments] ([work_date]);
GO

CREATE UNIQUE INDEX [shifts_code_unique] ON [dbo].[shifts] ([code]);
GO

CREATE UNIQUE INDEX [system_configs_config_key_unique] ON [dbo].[system_configs] ([config_key]);
GO

CREATE INDEX [time_logs_employee_id_log_time_index] ON [dbo].[time_logs] ([employee_id], [log_time]);
GO

CREATE INDEX [time_logs_log_time_index] ON [dbo].[time_logs] ([log_time]);
GO

CREATE UNIQUE INDEX [user_roles_user_id_role_id_unique] ON [dbo].[user_roles] ([user_id], [role_id]);
GO

CREATE UNIQUE INDEX [users_email_unique] ON [dbo].[users] ([email]);
GO

CREATE UNIQUE INDEX [users_username_unique] ON [dbo].[users] ([username]);
GO

