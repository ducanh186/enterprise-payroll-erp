# Fujimart Source DB Confirmation

Database: fujimart_hrm_source
Captured: 2026-05-16 16:38:46 +07:00

No secrets are included in this file.

## Confirmed source database

```text
database_name
-------------
fujimart_hrm_source

(1 rows affected)
```

## Required tables

```text
TABLE_SCHEMA|TABLE_NAME
------------|----------
dbo|D20PayrollParameter
dbo|D20Shift
dbo|D30AssignedShift
dbo|D30Attendance
dbo|D30CheckInOut
dbo|D30Payroll
dbo|D30PayrollDetail

(7 rows affected)
```

## Required views

```text
TABLE_SCHEMA|TABLE_NAME
------------|----------
dbo|vD20PayrollPara_SalaryType
dbo|vD20PayrollPara_ValuePara

(2 rows affected)
```

## Required columns

```text
TABLE_NAME|COLUMN_NAME|DATA_TYPE|CHARACTER_MAXIMUM_LENGTH|NUMERIC_PRECISION|NUMERIC_SCALE|IS_NULLABLE
----------|-----------|---------|------------------------|-----------------|-------------|-----------
D20PayrollParameter|Id|int|NULL|10|0|NO
D20PayrollParameter|Parameter|nvarchar|64|NULL|NULL|NO
D20PayrollParameter|Name|nvarchar|128|NULL|NULL|NO
D20PayrollParameter|Description|nvarchar|128|NULL|NULL|NO
D20PayrollParameter|EffectiveDate|date|NULL|NULL|NULL|YES
D20PayrollParameter|Type|varchar|32|NULL|NULL|NO
D20PayrollParameter|Amount|numeric|NULL|18|2|NO
D20PayrollParameter|IsActive|bit|NULL|NULL|NULL|NO
D20PayrollParameter|CreatedBy|int|NULL|10|0|NO
D20PayrollParameter|CreatedAt|smalldatetime|NULL|NULL|NULL|YES
D20PayrollParameter|ModifiedBy|int|NULL|10|0|NO
D20PayrollParameter|ModifiedAt|smalldatetime|NULL|NULL|NULL|NO
D20PayrollParameter|timestamp|timestamp|NULL|NULL|NULL|NO
D20Shift|Id|int|NULL|10|0|NO
D20Shift|ParentId|int|NULL|10|0|NO
D20Shift|IsGroup|bit|NULL|NULL|NULL|NO
D20Shift|IsActive|bit|NULL|NULL|NULL|NO
D20Shift|CreatedBy|int|NULL|10|0|NO
D20Shift|CreatedAt|datetime|NULL|NULL|NULL|NO
D20Shift|ModifiedBy|int|NULL|10|0|NO
D20Shift|ModifiedAt|datetime|NULL|NULL|NULL|NO
D20Shift|Code|varchar|16|NULL|NULL|NO
D20Shift|Name|nvarchar|128|NULL|NULL|NO
D20Shift|Description|nvarchar|256|NULL|NULL|YES
D20Shift|StartTime|datetime|NULL|NULL|NULL|YES
D20Shift|StartTimeValid1|datetime|NULL|NULL|NULL|YES
D20Shift|StartTimeValid2|datetime|NULL|NULL|NULL|YES
D20Shift|EndTime|datetime|NULL|NULL|NULL|YES
D20Shift|EndTimeValid1|datetime|NULL|NULL|NULL|YES
D20Shift|EndTimeValid2|datetime|NULL|NULL|NULL|YES
D20Shift|ShiftBreak|int|NULL|10|0|YES
D20Shift|StartShiftBreak|datetime|NULL|NULL|NULL|YES
D20Shift|EndShiftBreak|datetime|NULL|NULL|NULL|YES
D20Shift|WorkDay|numeric|NULL|8|2|YES
D20Shift|WorkingHours|numeric|NULL|8|2|YES
D20Shift|ShiftBreakMins|int|NULL|10|0|YES
D20Shift|StartWorkingNightTime|datetime|NULL|NULL|NULL|YES
D20Shift|EndWorkingNightTime|datetime|NULL|NULL|NULL|YES
D20Shift|ShiftMeal|int|NULL|10|0|YES
D20Shift|MinHourMeal|numeric|NULL|8|2|YES
D20Shift|StartBreakTimeValid1|datetime|NULL|NULL|NULL|YES
D20Shift|StartBreakTimeValid2|datetime|NULL|NULL|NULL|YES
D20Shift|EndBreakTimeValid1|datetime|NULL|NULL|NULL|YES
D20Shift|EndBreakTimeValid2|datetime|NULL|NULL|NULL|YES
D20Shift|IsCheckIn|tinyint|NULL|3|0|YES
D20Shift|IsCheckOut|tinyint|NULL|3|0|YES
D30AssignedShift|Id|int|NULL|10|0|NO
D30AssignedShift|IsActive|bit|NULL|NULL|NULL|NO
D30AssignedShift|CreatedBy|int|NULL|10|0|NO
D30AssignedShift|CreatedAt|datetime|NULL|NULL|NULL|NO
D30AssignedShift|ModifiedBy|int|NULL|10|0|NO
D30AssignedShift|ModifiedAt|datetime|NULL|NULL|NULL|NO
D30AssignedShift|Date|date|NULL|NULL|NULL|YES
D30AssignedShift|EmployeeCode|varchar|16|NULL|NULL|YES
D30AssignedShift|ShiftCode|varchar|16|NULL|NULL|YES
D30AssignedShift|StartDate|date|NULL|NULL|NULL|YES
D30AssignedShift|EndDate|date|NULL|NULL|NULL|YES
D30AssignedShift|IncludeMon|int|NULL|10|0|YES
D30AssignedShift|IncludeTue|int|NULL|10|0|YES
D30AssignedShift|IncludeWed|int|NULL|10|0|YES
D30AssignedShift|IncludeThu|int|NULL|10|0|YES
D30AssignedShift|IncludeFri|int|NULL|10|0|YES
D30AssignedShift|IncludeSat|int|NULL|10|0|YES
D30AssignedShift|IncludeSun|int|NULL|10|0|YES
D30AssignedShift|Description|nvarchar|512|NULL|NULL|YES
D30AssignedShift|AssignId|varchar|6|NULL|NULL|YES
D30Attendance|Id|int|NULL|10|0|NO
D30Attendance|DocId|varchar|12|NULL|NULL|YES
D30Attendance|Date|date|NULL|NULL|NULL|NO
D30Attendance|AssignId|varchar|16|NULL|NULL|YES
D30Attendance|WorkingHours|numeric|NULL|8|2|NO
D30Attendance|WorkingDays|numeric|NULL|8|2|NO
D30Attendance|ExcludeDays|numeric|NULL|8|2|NO
D30Attendance|ExcludeHours|numeric|NULL|8|2|NO
D30Attendance|WorkNightHours|numeric|NULL|8|2|NO
D30Attendance|ShiftMeal|int|NULL|10|0|NO
D30Attendance|IsActive|bit|NULL|NULL|NULL|NO
D30Attendance|CreatedBy|int|NULL|10|0|NO
D30Attendance|CreatedAt|datetime|NULL|NULL|NULL|NO
D30Attendance|ModifiedBy|int|NULL|10|0|NO
D30Attendance|ModifiedAt|datetime|NULL|NULL|NULL|NO
D30Attendance|PaidLeaveDays|numeric|NULL|8|2|NO
D30Attendance|UnpaidLeaveDays|numeric|NULL|8|2|YES
D30CheckInOut|Id|int|NULL|10|0|NO
D30CheckInOut|CheckTime|datetime|NULL|NULL|NULL|NO
D30CheckInOut|EmployeeCode|varchar|16|NULL|NULL|NO
D30CheckInOut|IsActive|bit|NULL|NULL|NULL|NO
D30CheckInOut|CreatedBy|int|NULL|10|0|NO
D30CheckInOut|CreatedAt|datetime|NULL|NULL|NULL|NO
D30CheckInOut|ModifiedBy|int|NULL|10|0|NO
D30CheckInOut|ModifiedAt|datetime|NULL|NULL|NULL|NO
D30Payroll|Id|int|NULL|10|0|NO
D30Payroll|BranchCode|char|3|NULL|NULL|NO
D30Payroll|RowId|varchar|16|NULL|NULL|NO
D30Payroll|Date|date|NULL|NULL|NULL|YES
D30Payroll|DeptCode|varchar|32|NULL|NULL|NO
D30Payroll|EmployeeCode|varchar|32|NULL|NULL|NO
D30Payroll|WorkArea|varchar|32|NULL|NULL|NO
D30Payroll|Trained|tinyint|NULL|3|0|NO
D30Payroll|NetCalc|tinyint|NULL|3|0|NO
D30Payroll|GrossSalary|numeric|NULL|18|2|NO
D30Payroll|Probationary|tinyint|NULL|3|0|NO
D30Payroll|ProbationaryRate|numeric|NULL|8|4|NO
D30Payroll|SalaryInsurance|numeric|NULL|18|2|NO
D30Payroll|OvertimeSalary|numeric|NULL|18|2|NO
D30Payroll|OffsetSalary|numeric|NULL|18|2|NO
D30Payroll|OVTTaxableIncome|numeric|NULL|18|2|NO
D30Payroll|BonusSalary|numeric|NULL|18|2|NO
D30Payroll|OtherSalary|numeric|NULL|18|2|NO
D30Payroll|NetIncome|numeric|NULL|18|2|NO
D30Payroll|SocialInsPay|numeric|NULL|18|2|NO
D30Payroll|SocialInsEMPLPay|numeric|NULL|18|2|NO
D30Payroll|HealthInsPay|numeric|NULL|18|2|NO
D30Payroll|HealthInsEMPLPay|numeric|NULL|18|2|NO
D30Payroll|TradeUnionInsPay|numeric|NULL|18|2|NO
D30Payroll|TradeUnionInsEMPLPay|numeric|NULL|18|2|NO
D30Payroll|UnemployedInsPay|numeric|NULL|18|2|NO
D30Payroll|UnemployedInsEMPLPay|numeric|NULL|18|2|NO
D30Payroll|AccidentInsPay|numeric|NULL|18|2|NO
D30Payroll|AccidentInsEMPLPay|numeric|NULL|18|2|NO
D30Payroll|AdvancesAmount|numeric|NULL|18|2|NO
D30Payroll|TaxableIncome|numeric|NULL|18|2|NO
D30Payroll|SelfDeduction|numeric|NULL|18|2|NO
D30Payroll|DependQuantity|int|NULL|10|0|NO
D30Payroll|DependentDeduction|numeric|NULL|18|2|NO
D30Payroll|CharityAmount|numeric|NULL|18|2|NO
D30Payroll|OtherDeductionsAmount|numeric|NULL|18|2|NO
D30Payroll|AssessableIncome|numeric|NULL|18|2|NO
D30Payroll|PersonalIncomeTaxAmount|numeric|NULL|18|2|NO
D30Payroll|ParaCode|varchar|32|NULL|NULL|NO
D30Payroll|SocialInsurance|tinyint|NULL|3|0|NO
D30Payroll|HealthInsurance|tinyint|NULL|3|0|NO
D30Payroll|TradeUnionInsurance|tinyint|NULL|3|0|NO
D30Payroll|UnemployedInsurance|tinyint|NULL|3|0|NO
D30Payroll|PersonalIncomeTax|tinyint|NULL|3|0|NO
D30Payroll|InEcoZones|tinyint|NULL|3|0|NO
D30Payroll|DeductionPITaxAmount|numeric|NULL|18|2|NO
D30Payroll|IsActive|bit|NULL|NULL|NULL|NO
D30Payroll|CreatedBy|int|NULL|10|0|NO
D30Payroll|CreatedAt|smalldatetime|NULL|NULL|NULL|YES
D30Payroll|ModifiedBy|int|NULL|10|0|NO
D30Payroll|ModifiedAt|smalldatetime|NULL|NULL|NULL|NO
D30Payroll|timestamp|timestamp|NULL|NULL|NULL|NO
D30PayrollDetail|Id|int|NULL|10|0|NO
D30PayrollDetail|ParentId|int|NULL|10|0|NO
D30PayrollDetail|IsGroup|bit|NULL|NULL|NULL|NO
D30PayrollDetail|BranchCode|char|3|NULL|NULL|NO
D30PayrollDetail|RowIdPR|varchar|16|NULL|NULL|NO
D30PayrollDetail|SalaryType|nvarchar|128|NULL|NULL|NO
D30PayrollDetail|BuiltinOrder|int|NULL|10|0|NO
D30PayrollDetail|Coeff|numeric|NULL|18|2|NO
D30PayrollDetail|Amount|numeric|NULL|18|2|NO
D30PayrollDetail|Days|numeric|NULL|6|2|NO
D30PayrollDetail|Hours|numeric|NULL|6|2|NO
D30PayrollDetail|IsActive|bit|NULL|NULL|NULL|NO
D30PayrollDetail|CreatedBy|int|NULL|10|0|NO
D30PayrollDetail|CreatedAt|smalldatetime|NULL|NULL|NULL|YES
D30PayrollDetail|ModifiedBy|int|NULL|10|0|NO
D30PayrollDetail|ModifiedAt|smalldatetime|NULL|NULL|NULL|NO
D30PayrollDetail|timestamp|timestamp|NULL|NULL|NULL|NO
vD20PayrollPara_SalaryType|Id|int|NULL|10|0|NO
vD20PayrollPara_SalaryType|Parameter|nvarchar|64|NULL|NULL|NO
vD20PayrollPara_SalaryType|Name|nvarchar|128|NULL|NULL|NO
vD20PayrollPara_SalaryType|Description|nvarchar|128|NULL|NULL|NO
vD20PayrollPara_SalaryType|EffectiveDate|date|NULL|NULL|NULL|YES
vD20PayrollPara_SalaryType|Type|varchar|32|NULL|NULL|NO
vD20PayrollPara_SalaryType|Amount|numeric|NULL|18|2|NO
vD20PayrollPara_SalaryType|IsActive|bit|NULL|NULL|NULL|NO
vD20PayrollPara_SalaryType|CreatedBy|int|NULL|10|0|NO
vD20PayrollPara_SalaryType|CreatedAt|smalldatetime|NULL|NULL|NULL|YES
vD20PayrollPara_SalaryType|ModifiedBy|int|NULL|10|0|NO
vD20PayrollPara_SalaryType|ModifiedAt|smalldatetime|NULL|NULL|NULL|NO
vD20PayrollPara_SalaryType|timestamp|timestamp|NULL|NULL|NULL|NO
vD20PayrollPara_ValuePara|Id|int|NULL|10|0|NO
vD20PayrollPara_ValuePara|Parameter|nvarchar|64|NULL|NULL|NO
vD20PayrollPara_ValuePara|Name|nvarchar|128|NULL|NULL|NO
vD20PayrollPara_ValuePara|Description|nvarchar|128|NULL|NULL|NO
vD20PayrollPara_ValuePara|EffectiveDate|date|NULL|NULL|NULL|YES
vD20PayrollPara_ValuePara|Type|varchar|32|NULL|NULL|NO
vD20PayrollPara_ValuePara|Amount|numeric|NULL|18|2|NO
vD20PayrollPara_ValuePara|IsActive|bit|NULL|NULL|NULL|NO
vD20PayrollPara_ValuePara|CreatedBy|int|NULL|10|0|NO
vD20PayrollPara_ValuePara|CreatedAt|smalldatetime|NULL|NULL|NULL|YES
vD20PayrollPara_ValuePara|ModifiedBy|int|NULL|10|0|NO
vD20PayrollPara_ValuePara|ModifiedAt|smalldatetime|NULL|NULL|NULL|NO
vD20PayrollPara_ValuePara|timestamp|timestamp|NULL|NULL|NULL|NO

(186 rows affected)
```

## Candidate procedures

```text
schema_name|name
-----------|----
dbo|usp_AttendanceReport
dbo|usp_CreateAndCalculateAttendance
dbo|usp_CreateAndCalculatePayroll
dbo|usp_PayrollReport
dbo|usp_PayrollSlip

(5 rows affected)
```

## Candidate procedure parameters

```text
Sqlcmd: The y and the W options are mutually exclusive.
```

## Candidate procedure parameters corrected

```text
schema_name|procedure_name|parameter_name|data_type|max_length|precision|scale|is_output
-----------|--------------|--------------|---------|----------|---------|-----|---------
dbo|usp_AttendanceReport|@_DocDate1|date|3|10|0|0
dbo|usp_AttendanceReport|@_DocDate2|date|3|10|0|0
dbo|usp_AttendanceReport|@_BranchCode|varchar|16|0|0|0
dbo|usp_AttendanceReport|@_DeptCode|varchar|512|0|0|0
dbo|usp_AttendanceReport|@_EmployeeCode|varchar|512|0|0|0
dbo|usp_CreateAndCalculateAttendance|@_DocDate1|date|3|10|0|0
dbo|usp_CreateAndCalculateAttendance|@_BranchCode|nvarchar|32|0|0|0
dbo|usp_CreateAndCalculateAttendance|@_DeptCode|nvarchar|32|0|0|0
dbo|usp_CreateAndCalculateAttendance|@_EmployeeCode|nvarchar|32|0|0|0
dbo|usp_CreateAndCalculatePayroll|@_DocDate1|date|3|10|0|0
dbo|usp_CreateAndCalculatePayroll|@_BranchCode|varchar|16|0|0|0
dbo|usp_CreateAndCalculatePayroll|@_DeptCode|varchar|512|0|0|0
dbo|usp_CreateAndCalculatePayroll|@_EmployeeCode|varchar|512|0|0|0
dbo|usp_CreateAndCalculatePayroll|@_UserId|int|4|10|0|0
dbo|usp_PayrollReport|@_DocDate1|date|3|10|0|0
dbo|usp_PayrollReport|@_BranchCode|varchar|512|0|0|0
dbo|usp_PayrollReport|@_DeptCode|varchar|512|0|0|0
dbo|usp_PayrollReport|@_EmployeeCode|varchar|512|0|0|0
dbo|usp_PayrollReport|@_Month|int|4|10|0|1
dbo|usp_PayrollReport|@_Year|int|4|10|0|1
dbo|usp_PayrollReport|@_BranchName|nvarchar|-1|0|0|1
dbo|usp_PayrollReport|@_DeptName|nvarchar|-1|0|0|1
dbo|usp_PayrollReport|@_StandardWorkDays|numeric|9|18|2|1
dbo|usp_PayrollReport|@_SocialInsCompanyRate|numeric|5|8|4|1
dbo|usp_PayrollReport|@_HealthInsCompanyRate|numeric|5|8|4|1
dbo|usp_PayrollReport|@_UnemployedInsCompanyRate|numeric|5|8|4|1
dbo|usp_PayrollReport|@_SocialInsEmployeeRate|numeric|5|8|4|1
dbo|usp_PayrollReport|@_HealthInsEmployeeRate|numeric|5|8|4|1
dbo|usp_PayrollReport|@_UnemployedInsEmployeeRate|numeric|5|8|4|1
dbo|usp_PayrollSlip|@_DocDate1|date|3|10|0|0
dbo|usp_PayrollSlip|@_EmployeeCode|varchar|512|0|0|0
dbo|usp_PayrollSlip|@_DeptCode|varchar|512|0|0|0
dbo|usp_PayrollSlip|@_BranchCode|varchar|512|0|0|0
dbo|usp_PayrollSlip|@_SendEmail|bit|1|1|0|0
dbo|usp_PayrollSlip|@_MailProfile|nvarchar|256|0|0|0
dbo|usp_PayrollSlip|@_TotalSalary|numeric|9|18|2|1
dbo|usp_PayrollSlip|@_TotalDeduction|numeric|9|18|2|1
dbo|usp_PayrollSlip|@_NetIncome|numeric|9|18|2|1
dbo|usp_PayrollSlip|@_SentEmailCount|int|4|10|0|1

(39 rows affected)
```

## Procedure module target references corrected

```text
schema_name|procedure_name|references_D30Attendance|references_D30Payroll|references_D30PayrollDetail|references_D30AssignedShift|references_D30CheckInOut
-----------|--------------|------------------------|---------------------|---------------------------|---------------------------|------------------------
dbo|usp_AttendanceReport|1|0|0|1|0
dbo|usp_CreateAndCalculateAttendance|1|0|0|1|1
dbo|usp_CreateAndCalculatePayroll|1|1|1|1|0
dbo|usp_PayrollReport|0|1|1|0|0
dbo|usp_PayrollSlip|0|1|1|0|0

(5 rows affected)
```

## sp_helptext dbo.usp_CreateAndCalculateAttendance corrected

```text
Text
----
-- Thủ tục tính và tổng hợp ngày công theo tháng

CREATE PROC dbo.usp_CreateAndCalculateAttendance

	@_DocDate1		DATE = '20260101',

	@_BranchCode	NVARCHAR(16) = 'A01,A02',

	@_DeptCode		NVARCHAR(16) = '',

	@_EmployeeCode	NVARCHAR(16) = ''

AS

BEGIN

	SET NOCOUNT ON;



	-- Xử lý điều kiện lọc

		DECLARE @_DocDate2 DATE,

				@_KeyAS NVARCHAR(MAX) = '', 

				@_Str NVARCHAR(MAX) = ''

		

		SET @_DocDate1 = DATEADD(DAY, 1, EOMONTH(@_DocDate1, -1))

		SET	@_DocDate2 = EOMONTH(@_DocDate1)



		IF ISNULL(@_BranchCode,'') <> ''

			SET @_KeyAS = @_KeyAS + CHAR(13) + ' AND emp.BranchCode IN (''' + REPLACE(@_BranchCode,',',''',''') + ''')'



		IF ISNULL(@_DeptCode,'') <> ''

			SET @_KeyAS = @_KeyAS + CHAR(13) + ' AND emp.DeptCode IN (''' + REPLACE(@_DeptCode,',',''',''') + ''')'



		IF ISNULL(@_EmployeeCode,'') <> ''

			SET @_KeyAS = @_KeyAS + CHAR(13) + ' AND a.EmployeeCode IN (''' + REPLACE(@_EmployeeCode,',',''',''') + ''')'

		

		

	-- Lấy phân ca làm việc

		DROP TABLE IF EXISTS #AssignShiftTmp

		SELECT TOP 0 AssignId, EmployeeCode AS BranchCode, EmployeeCode AS DeptCode, EmployeeCode, ShiftCode, StartDate, EndDate, 

					CAST(0 AS INT) AS WorkingType, CAST(0 AS INT) AS _Check

		INTO #AssignShiftTmp

		FROM dbo.D30AssignedShift



		SET @_Str = '

		;WITH CTE AS (

			SELECT a.EmployeeCode, b.StartDate, b.EndDate, b.WorkingType

			FROM #AssignShiftTmp a

				OUTER APPLY (

					SELECT TOP 1 StartDate, EndDate, WorkingType

					FROM dbo.D30LabourContract

					WHERE EmployeeCode = a.EmployeeCode

					  AND IsActive = 1

					  AND ISNULL(StartDate, ''19000101'') <= ''' + CAST(@_DocDate2 AS NVARCHAR(16)) + '''

					  AND ISNULL(EndDate,   ''99991231'') >= ''' + CAST(@_DocDate1 AS NVARCHAR(16)) + '''

					ORDER BY DocDate DESC

				) b

		)

		INSERT INTO #AssignShiftTmp

			(AssignId, BranchCode, DeptCode, EmployeeCode, ShiftCode, StartDate, EndDate, WorkingType, _Check)

		SELECT

			a.AssignId, emp.BranchCode, emp.DeptCode, a.EmployeeCode, a.ShiftCode,



			-- StartDate = MAX(@_DocDate1, a.StartDate, CTE.StartDate)

			(SELECT MAX(v) FROM (VALUES

				(CAST(''' + CAST(@_DocDate1 AS NVARCHAR(16)) + ''' AS DATE)),

				(a.StartDate),

				(ISNULL(CTE.StartDate, ''19000101''))

			) t(v)) AS StartDate,



			-- EndDate = MIN(@_DocDate2, a.EndDate, CTE.EndDate)

			(SELECT MIN(v) FROM (VALUES

				(CAST(''' + CAST(@_DocDate2 AS NVARCHAR(16)) + ''' AS DATE)),

				(ISNULL(a.EndDate,    ''99991231'')),

				(ISNULL(CTE.EndDate,  ''99991231''))

			) t(v)) AS EndDate,



			CTE.WorkingType,

			0

		FROM dbo.D30AssignedShift a

			INNER JOIN dbo.D20Employee emp ON emp.Code = a.EmployeeCode

			LEFT  JOIN CTE ON CTE.EmployeeCode = a.EmployeeCode

		WHERE a.StartDate <= ''' + CAST(@_DocDate2 AS NVARCHAR(20)) + '''

		  AND a.EndDate   >= ''' + CAST(@_DocDate1 AS NVARCHAR(20)) + '''

		  AND a.IsActive  = 1

		  -- Bỏ các dòng có HĐLĐ không phủ kỳ tính công (intersection rỗng)

		  AND ISNULL(CTE.StartDate, ''19000101'') <= ''' + CAST(@_DocDate2 AS NVARCHAR(16)) + '''

		  AND ISNULL(CTE.EndDate,   ''99991231'') >= ''' + CAST(@_DocDate1 AS NVARCHAR(16)) + '''

		' + @_KeyAS;



		EXECUTE(@_Str);



	-- Lấy chi tiết phân ca làm việc theo từng ngày	

		DROP TABLE IF EXISTS #AssignDetailTmp

		CREATE TABLE #AssignDetailTmp (Date DATE, WorkDay INT, AssignId NVARCHAR(32), EmployeeCode NVARCHAR(32)); 





		DECLARE @_AssignId VARCHAR(16), @_StartDate DATE, @_EndDate DATE

		WHILE EXISTS (SELECT 1 FROM #AssignShiftTmp WHERE _Check = 0)

		BEGIN

			SELECT TOP 1 @_AssignId	= AssignId, @_StartDate = StartDate, @_EndDate = EndDate

			FROM #AssignShiftTmp

			WHERE _Check = 0



			EXEC dbo.usp_SetDetailFromAssignedShift @_StartDate = @_StartDate, -- date

			                                        @_EndDate = @_EndDate,   -- date

			                                        @_AssignId = @_AssignId,           -- nvarchar(16)

			                                        @_ResultTbl = N'#AssignDetailTmp'          -- nvarchar(32)



			UPDATE #AssignShiftTmp SET _Check = 1

				WHERE AssignId = @_AssignId

						 

		END

	

		



	-- Lấy dữ liệu chấm công

		DROP TABLE IF EXISTS #CheckTmp

		SELECT TOP 0 CAST (NULL AS DATE) AS Date, EmployeeCode, CheckTime 

		INTO #CheckTmp

		FROM dbo.D30CheckInOut



		SET @_Str = '

		INSERT INTO #CheckTmp (Date, EmployeeCode, CheckTime)

		SELECT CAST(CheckTime AS Date), EmployeeCode, CheckTime

		FROM dbo.D30CheckInOut c

		INNER JOIN dbo.D20Employee emp ON emp.Code = c.EmployeeCode

			WHERE c.IsActive = 1 AND (c.CheckTime BETWEEN ''' + CAST(@_DocDate1 AS NVARCHAR(16)) + ''' AND ''' + CAST(@_DocDate2 AS NVARCHAR(16)) + ''')'

		+ @_KeyAS



		EXEC (@_Str)



	-- Lấy dữ liệu chấm công bổ sung

		SET @_Str = '

		INSERT INTO #CheckTmp (Date, EmployeeCode, CheckTime)

		SELECT CAST(c.CheckTime AS DATE) AS Date,ad.EmployeeCode, c.CheckTime

		FROM dbo.D30AttendanceDoc ad

		LEFT OUTER JOIN dbo.D30CheckInManualDetail c ON c.DocId = ad.DocId

		INNER JOIN dbo.D20Employee emp ON emp.Code = ad.EmployeeCode		

			WHERE ad.IsActive = 1 AND c.IsActive = 1 

			AND (c.CheckTime BETWEEN ''' + CAST(@_DocDate1 AS NVARCHAR(16)) + ''' AND ''' + CAST(@_DocDate2 AS NVARCHAR(16)) + ''')'

		+ @_KeyAS



		EXEC (@_Str)



	-- Lấy dữ liệu từ đơn xin nghỉ phép

		DROP TABLE IF EXISTS #AbsenceTmp

		SELECT TOP 0 Date, AbsenceType, CAST('' AS NVARCHAR(32)) AS EmployeeCode, WorkingDays, CAST(0 AS INT) AS _Check

		INTO #AbsenceTmp	

		FROM dbo.D30AbsenceDetail



		INSERT INTO #AbsenceTmp (Date, AbsenceType, EmployeeCode, WorkingDays, _Check)

		SELECT Ct0.Date, Ct0.AbsenceType, Ct.EmployeeCode, Ct0.WorkingDays, 0

		FROM dbo.D30AttendanceDoc Ct

		LEFT OUTER JOIN dbo.D30AbsenceDetail Ct0 ON ct0.DocId = Ct.DocId

		WHERE EXISTS (SELECT 1 FROM #AssignShiftTmp a WHERE a.EmployeeCode = Ct.EmployeeCode)

		AND DATEPART(YEAR,Ct0.Date) = DATEPART(YEAR,@_DocDate1) AND Ct0.Date <= @_DocDate2

	

	-- Tính số dư ngày nghỉ phép 

		DROP TABLE IF EXISTS #SabbBal

		SELECT a.EmployeeCode, 

				CASE WHEN DATEPART(YEAR,MAX(e.FirstWorkingDate)) < DATEPART(YEAR,@_DocDate1) THEN 12 ELSE 12-DATEPART(MONTH,MAX(e.FirstWorkingDate))+1 END AS OpenBal0,

				CAST(0 AS NUMERIC(8,2)) AS UsedLeaves0,

				CAST(0 AS NUMERIC(8,2)) AS OpenBal, CAST(0 AS NUMERIC(8,2)) AS UsedLeaves, CAST(0 AS NUMERIC(8,2)) AS CloseBal

		INTO #SabbBal

		FROM #AssignShiftTmp a

		LEFT OUTER JOIN dbo.D20Employee e ON e.Code = a.EmployeeCode

		GROUP BY a.EmployeeCode



		UPDATE #SabbBal

			SET UsedLeaves0 = ISNULL(a.Used,0)

			FROM #SabbBal s

				LEFT OUTER JOIN (SELECT EmployeeCode, SUM(WorkingDays) AS Used FROM #AbsenceTmp

								WHERE Date < @_DocDate1

								GROUP BY EmployeeCode) a ON a.EmployeeCode = s.EmployeeCode

		

		UPDATE #SabbBal

			SET OpenBal = OpenBal0 - UsedLeaves0



		UPDATE #SabbBal 

			SET CloseBal = OpenBal - UsedLeaves



	-- Tổng hợp các nguồn dữ liệu vào 1 bảng tạm để tính công

		DROP TABLE IF EXISTS #CtTmp

		SELECT Date, a.AssignId, b.ShiftCode, a.EmployeeCode, 

			CAST(0 AS NUMERIC(8,2)) AS WorkingHours, CAST(0 AS NUMERIC(8,2)) AS WorkingDays, 

			CAST(0 AS NUMERIC(8,2)) AS ExcludeDays, CAST(0 AS NUMERIC(8,2)) AS ExcludeHours, CAST(0 AS INT) AS LateEarlyMins,

			CAST(0 AS NUMERIC(8,2)) AS WorkNightHours, CAST(0 AS NUMERIC(8,2)) AS ShiftMeal, CAST(0 AS NUMERIC(8,2)) AS PaidLeaveDays, CAST(0 AS NUMERIC(8,2)) AS UnpaidLeaveDays,

			CAST(NULL AS DATETIME) AS CheckTime1, CAST(NULL AS DATETIME) AS CheckTime2,

			s.IsCheckIn, CAST(s.StartTime AS TIME) AS StartTime, CAST(s.StartTimeValid1 AS TIME) AS StartTimeValid1, CAST(s.StartTimeValid2 AS TIME) AS StartTimeValid2, 

			s.IsCheckOut, CAST(s.EndTime AS TIME) AS EndTime, CAST(s.EndTimeValid1 AS TIME) AS EndTimeValid1, CAST(s.EndTimeValid2 AS TIME) AS EndTimeValid2, 

			s.ShiftBreak, CAST(s.StartShiftBreak AS TIME) AS StartShiftBreak, CAST(s.EndShiftBreak AS TIME) AS EndShiftBreak, 

			CAST(s.StartBreakTimeValid1 AS TIME) AS StartBreakTimeValid1, CAST(s.StartBreakTimeValid2 AS TIME) AS StartBreakTimeValid2,

			CAST(s.EndBreakTimeValid1 AS TIME) AS EndBreakTimeValid1, CAST(s.EndBreakTimeValid2 AS TIME) AS EndBreakTimeValid2,

			a.WorkDay AS WorkDay_AS, s.WorkingHours AS WorkHours_AS, s.MinHourMeal, s.ShiftMeal AS ShiftMeal_AS, 

			CAST(s.StartWorkingNightTime AS TIME) AS StartWorkingNightTime, CAST(s.EndWorkingNightTime AS TIME) AS EndWorkingNightTime 

		INTO #CtTmp

		FROM #AssignDetailTmp a

			INNER JOIN #AssignShiftTmp b ON a.AssignId = b.AssignId

			LEFT OUTER JOIN dbo.D20Shift s ON s.Code = b.ShiftCode





	-- Tính ngày công dựa trên thời gian chấm công

		-- Tính thời gian bắt đầu, kết thúc ca làm trong ngày với các ngày xin nghỉ nửa buổi (nửa ca)		

		UPDATE #CtTmp

			SET StartTime = Ct.EndShiftBreak, StartTimeValid1 = Ct.EndBreakTimeValid1, StartTimeValid2 = Ct.EndBreakTimeValid2

			FROM #CtTmp Ct

				INNER JOIN #AbsenceTmp a ON a.EmployeeCode = Ct.EmployeeCode AND a.Date = Ct.Date

				WHERE AbsenceType = 1



		UPDATE #CtTmp

			SET EndTime = Ct.StartShiftBreak, EndTimeValid1 = Ct.StartBreakTimeValid1, EndTimeValid2 = Ct.StartBreakTimeValid2

			FROM #CtTmp Ct

				INNER JOIN #AbsenceTmp a ON a.EmployeeCode = Ct.EmployeeCode AND a.Date = Ct.Date

				WHERE AbsenceType = 2

	

		UPDATE #CtTmp

			SET EndTime = NULL, EndTimeValid1 = NULL, EndTimeValid2 = NULL,

				StartTime = NULL, StartTimeValid1 =	NULL, StartTimeValid2 = NULL

			FROM #CtTmp Ct

				INNER JOIN #AbsenceTmp a ON a.EmployeeCode = Ct.EmployeeCode AND a.Date = Ct.Date

				WHERE AbsenceType = 3

				OR Ct.StartTime >= Ct.EndTime



		-- Tính ngày nghỉ phép

			DECLARE @_Date DATE, @_Emp VARCHAR(16), @_Type INT



			WHILE EXISTS (SELECT 1 FROM #AbsenceTmp WHERE _Check = 0)

			BEGIN

				SELECT TOP 1 @_Date = Date, @_Emp = EmployeeCode, @_Type = AbsenceType FROM #AbsenceTmp ORDER BY Date



				UPDATE #CtTmp

					SET PaidLeaveDays = Ct.PaidLeaveDays + CASE WHEN s.CloseBal >= a.WorkingDays THEN a.WorkingDays ELSE s.CloseBal END	,

						UnpaidLeaveDays = Ct.PaidLeaveDays + CASE WHEN s.CloseBal >= a.WorkingDays THEN 0 ELSE a.WorkingDays - s.CloseBal END	

					FROM #CtTmp Ct

						LEFT OUTER JOIN #SabbBal s ON s.EmployeeCode = Ct.EmployeeCode

						LEFT OUTER JOIN #AbsenceTmp a ON a.Date = ct.Date AND a.EmployeeCode = Ct.EmployeeCode

						WHERE a.Date = @_Date





				UPDATE #SabbBal SET	UsedLeaves = UsedLeaves + a.WorkingDays

					FROM #SabbBal s

						LEFT OUTER JOIN #AbsenceTmp a ON a.EmployeeCode = s.EmployeeCode

						WHERE a.Date = @_Date 



				UPDATE #SabbBal SET CloseBal = CASE WHEN OpenBal - UsedLeaves < 0 THEN 0 ELSE OpenBal - UsedLeaves END	

					

				UPDATE #AbsenceTmp SET _Check = 1 WHERE Date = @_Date AND EmployeeCode = @_Emp AND AbsenceType = @_Type

			END



		-- Đổ dữ liệu thời gian chấm công

		;WITH CheckTime AS 

		(SELECT c.Date, MAX(c.EmployeeCode) AS EmployeeCode, MIN(c.CheckTime) AS CheckTime1, MAX(c.CheckTime) AS CheckTime2

		FROM #CtTmp Ct

		LEFT OUTER JOIN #CheckTmp c ON Ct.EmployeeCode = c.EmployeeCode AND Ct.Date = c.Date

		GROUP BY c.Date, Ct.AssignId )

		UPDATE #CtTmp

			SET CheckTime1 = CheckTime.CheckTime1, CheckTime2 = CheckTime.CheckTime2

			FROM #CtTmp Ct INNER JOIN CheckTime ON CheckTime.Date = Ct.Date AND CheckTime.EmployeeCode = Ct.EmployeeCode



		-- Tính số phút đi trễ về sớm

		UPDATE #CtTmp

			SET LateEarlyMins = CASE WHEN CAST(CheckTime1 AS TIME) > StartTime THEN DATEDIFF(MINUTE, StartTime, CAST(CheckTime1 AS TIME)) ELSE 0 END	

						+ CASE WHEN CAST(CheckTime2 AS TIME) < EndTime THEN DATEDIFF(MINUTE, CAST(CheckTime2 AS TIME), EndTime) ELSE 0 END	

			

		-- Tính số giờ làm việc, ngày làm việc bị trừ do đi trễ về sớm

		UPDATE #CtTmp

			SET ExcludeHours = ISNULL(ex.ExcludeTime / 60,0), ExcludeDays = ISNULL(ex.ExcludeWorkDay, 0)

			FROM #CtTmp Ct 

				OUTER APPLY (SELECT TOP 1 le0.ExcludeTime, le0.ExcludeWorkDay FROM dbo.D20LateEarlyRegulation le

								INNER JOIN dbo.D20LateEarlyRegulationDetail le0 ON le0.LateEarlyRegCode = le.Code

								WHERE Ct.LateEarlyMins BETWEEN le0.StartMinute AND le0.EndMinute

								AND le.AppliedDate <= Ct.Date

								ORDER BY le.AppliedDate DESC) ex

		

		-- Tính giờ làm đêm (xử lý sau)



		-- Tính số giờ làm việc, ngày làm việc

			-- Ca làm việc yêu cầu chấm công 2 lần

			UPDATE #CtTmp

				SET UnpaidLeaveDays = UnpaidLeaveDays + (DATEDIFF(HOUR,StartTime,EndTime)-DATEDIFF(HOUR,StartShiftBreak,EndShiftBreak))/WorkHours_AS

				WHERE (CheckTime1 IS NULL OR CheckTime2 IS NULL) AND ischeckin = 1 AND IScheckout = 1

			

			UPDATE #CtTmp

				SET WorkingDays = WorkDay_AS - PaidLeaveDays - UnpaidLeaveDays,

					WorkingHours = CASE WHEN WorkDay_AS - PaidLeaveDays - UnpaidLeaveDays > 0 THEN WorkHours_AS - LateEarlyMins/60 ELSE 0 end



			UPDATE #CtTmp 

				SET ShiftMeal = CASE WHEN WorkingHours >= MinHourMeal THEN ShiftMeal_AS ELSE 0 END

			



			-- TH nhân viên làm bán thời gian

			--UPDATE #CtTmp

			--	SET WorkingHours

			--	FROM #CtTmp

					

			

			-- Ca làm việc yêu cầm chấm công 1 lần (bổ sung sau)

	

	-- Chèn dữ liệu vào bảng D30Attendance (Bảng tổng hợp công)

		BEGIN TRY

			BEGIN TRANSACTION;

 

			DELETE att

			FROM dbo.D30Attendance att

			WHERE att.[Date] BETWEEN @_DocDate1 AND @_DocDate2

			  AND EXISTS (

					SELECT 1 FROM #CtTmp ct

					WHERE ct.AssignId = att.AssignId AND ct.[Date] = att.[Date]

				  );

 

			INSERT INTO dbo.D30Attendance

				([Date], AssignId, WorkingHours, WorkingDays,

				 ExcludeDays, ExcludeHours, WorkNightHours, ShiftMeal, PaidLeaveDays, UnpaidLeaveDays

				 )

			SELECT

				[Date], AssignId, WorkingHours, WorkingDays,

				ExcludeDays, ExcludeHours, WorkNightHours, ISNULL(ShiftMeal, 0), PaidLeaveDays, UnpaidLeaveDays

			FROM #CtTmp

			WHERE AssignId IS NOT NULL;

 

			DECLARE @_RowCount INT = @@ROWCOUNT;

			COMMIT TRANSACTION;

 

			-- Thông báo kết quả

			DECLARE @_Msg NVARCHAR(500) =

				N'✓ Đã lưu thành công ' + CAST(@_RowCount AS NVARCHAR(20))

			  + N' dòng dữ liệu chấm công vào bảng D30Attendance'

			  + N' (từ ' + CONVERT(NVARCHAR(10), @_DocDate1, 103)

			  + N' đến ' + CONVERT(NVARCHAR(10), @_DocDate2, 103) + N').';

 

			PRINT @_Msg;

			RAISERROR(N'%s', 0, 1, @_Msg) WITH NOWAIT;

 

			-- Trả về result set cho ứng dụng đọc

			SELECT @_RowCount AS InsertedRows,

				   @_DocDate1 AS FromDate,

				   @_DocDate2 AS ToDate,

				   @_Msg      AS Message,

				   1          AS IsSuccess;

		END TRY

		BEGIN CATCH

			IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;

 

			DECLARE @_ErrMsg NVARCHAR(2048) =

				N'✗ Lỗi khi lưu dữ liệu chấm công: ' + ERROR_MESSAGE();

			PRINT @_ErrMsg;

 

			-- Trả về result set báo lỗi (không THROW để app dễ xử lý)

			SELECT 0          AS InsertedRows,

				   @_DocDate1 AS FromDate,

				   @_DocDate2 AS ToDate,

				   @_ErrMsg   AS Message,

				   0          AS IsSuccess;

 

			THROW;

		END CATCH



	DROP TABLE IF EXISTS #AssignShiftTmp

	DROP TABLE IF EXISTS #AssignDetailTmp

	DROP TABLE IF EXISTS #CheckTmp

	DROP TABLE IF EXISTS #AbsenceTmp

END

```

## sp_helptext dbo.usp_CreateAndCalculatePayroll corrected

```text
Text
----
-- =============================================================================

-- usp_CreatePayroll v3 — sửa các bug từ v2:

--   [A] WorkingType convention đúng: 0 = Fulltime, 1 = Parttime (lương theo giờ)

--   [B] Ngày công hưởng lương = WorkingDays + PaidLeaveDays

--       → WorkSalary tính trên TỔNG (không tách ra PaidLeaveSalary nữa)

--   [C] Parttime: WorkSalary = UnitPerHour × WorkingHours (không có phép quy đổi)

--   [D] PaidLeaveSalary giờ chỉ để hiển thị trên phiếu lương (split của WorkSalary)

--

-- Tóm tắt v3 vs v2:

--   v2: WorkSalary = UnitDay × WorkingDays                  (THIẾU ngày phép)

--       PaidLeaveSalary = UnitDay × PaidLeaveDays           (cộng vào Gross)

--       → Tách 2 dòng nhưng tổng là đúng (khi cùng đơn giá)

--   v3: PaidDays = WorkingDays + PaidLeaveDays

--       WorkSalary = UnitDay × PaidDays

--       PaidLeaveSalary = UnitDay × PaidLeaveDays (chỉ để hiển thị, KHÔNG cộng Gross)

--       → Bảo đảm logic đúng cho cả trường hợp đơn giá đặc biệt

-- =============================================================================

CREATE PROC dbo.usp_CreateAndCalculatePayroll

    @_DocDate1     DATE         = '20260901',

    @_BranchCode   VARCHAR(16)  = '',

    @_DeptCode     VARCHAR(512) = NULL,

    @_EmployeeCode VARCHAR(512) = NULL,

    @_UserId       INT          = -1

AS

BEGIN

    SET NOCOUNT ON;



	DECLARE @_DocDate2 DATE 

	SET @_DocDate1 = DATEADD(DAY, 1, EOMONTH(@_DocDate1, -1))

	SET	@_DocDate2 = EOMONTH(@_DocDate1)



    DECLARE @_RowCount INT = 0,

            @_DetailCount INT = 0,

            @_Period CHAR(6) = FORMAT(@_DocDate2, 'yyyyMM');



    -- =========================================================================

    -- B0. Validate

    -- =========================================================================

    IF @_DocDate1 IS NULL OR @_DocDate2 IS NULL OR @_DocDate1 > @_DocDate2

    BEGIN

        RAISERROR(N'Khoảng ngày không hợp lệ.', 16, 1); RETURN;

    END



    -- =========================================================================

    -- B1. Snapshot tham số TYPE='1' và '3' đang hiệu lực vào #Param

    --     EffectiveDate <= @_DocDate2 + IsActive=1, lấy bản gần nhất theo Parameter.

    -- =========================================================================

    DROP TABLE IF EXISTS #Param;

    CREATE TABLE #Param ([Parameter] NVARCHAR(64) PRIMARY KEY, [Amount] NUMERIC(18,2));



    ;WITH p AS (

        SELECT [Parameter], [Amount],

               ROW_NUMBER() OVER (PARTITION BY [Parameter]

                                  ORDER BY ISNULL(EffectiveDate, '19000101') DESC, Id DESC) rn

        FROM dbo.D20PayrollParameter

        WHERE IsActive = 1

          AND [Type] IN ('1', '3')

          AND ISNULL(EffectiveDate, '19000101') <= @_DocDate2

    )

    INSERT INTO #Param ([Parameter], [Amount])

    SELECT [Parameter], [Amount] FROM p WHERE rn = 1;



    -- Hàm helper: đọc tham số (trả 0 nếu không có)

    DECLARE @_DaysPerMonth        NUMERIC(18,2) = ISNULL((SELECT Amount FROM #Param WHERE Parameter = N'_DaysPerMonth'), 24);

    DECLARE @_HoursPerDay         NUMERIC(18,2) = ISNULL((SELECT Amount FROM #Param WHERE Parameter = N'_HoursPerDay'), 8);

    DECLARE @_ShiftMealsUnit      NUMERIC(18,2) = ISNULL((SELECT Amount FROM #Param WHERE Parameter = N'_ShiftMealsUnit'), 0);

    DECLARE @_ShiftAllowanceMax   NUMERIC(18,2) = ISNULL((SELECT Amount FROM #Param WHERE Parameter = N'_ShiftAllowanceMax'), 0);

    DECLARE @_NormalOVTRate       NUMERIC(18,2) = ISNULL((SELECT Amount FROM #Param WHERE Parameter = N'_Normal_OVTRate'), 1.5);

    DECLARE @_WeekendsOVTRate     NUMERIC(18,2) = ISNULL((SELECT Amount FROM #Param WHERE Parameter = N'_Weekends_OVTRate'), 2);

    DECLARE @_HolidayOVTRate      NUMERIC(18,2) = ISNULL((SELECT Amount FROM #Param WHERE Parameter = N'_Holiday_OVTRate'), 3);

    DECLARE @_WorknightRate       NUMERIC(18,2) = ISNULL((SELECT Amount FROM #Param WHERE Parameter = N'_Worknight_Rate'), 0.3);



    DECLARE @_PctSocialEMPL       NUMERIC(18,2) = ISNULL((SELECT Amount FROM #Param WHERE Parameter = N'_PercentofSocialInsEMPLPay'), 8);

    DECLARE @_PctHealthEMPL       NUMERIC(18,2) = ISNULL((SELECT Amount FROM #Param WHERE Parameter = N'_PercentofHealthInsEMPLPay'), 1.5);

    DECLARE @_PctTradeUnionEMPL   NUMERIC(18,2) = ISNULL((SELECT Amount FROM #Param WHERE Parameter = N'_PercentofTradeUnionInsEMPLPay'), 0);

    DECLARE @_PctUnemployedEMPL   NUMERIC(18,2) = ISNULL((SELECT Amount FROM #Param WHERE Parameter = N'_PercentofUnemployedInsEMPLPay'), 1);

    DECLARE @_PctAccidentEMPL     NUMERIC(18,2) = ISNULL((SELECT Amount FROM #Param WHERE Parameter = N'_PercentofAccidentInsEMPLPay'), 0);



    DECLARE @_PctSocial           NUMERIC(18,2) = ISNULL((SELECT Amount FROM #Param WHERE Parameter = N'_PercentofSocialIns'), 17.5);

    DECLARE @_PctHealth           NUMERIC(18,2) = ISNULL((SELECT Amount FROM #Param WHERE Parameter = N'_PercentofHealthIns'), 3);

    DECLARE @_PctTradeUnion       NUMERIC(18,2) = ISNULL((SELECT Amount FROM #Param WHERE Parameter = N'_PercentofTradeUnionIns'), 2);

    DECLARE @_PctUnemployed       NUMERIC(18,2) = ISNULL((SELECT Amount FROM #Param WHERE Parameter = N'_PercentofUnemployedIns'), 1);

    DECLARE @_PctAccident         NUMERIC(18,2) = ISNULL((SELECT Amount FROM #Param WHERE Parameter = N'_PercentofAccidentIns'), 0);



    DECLARE @_RedYourself         NUMERIC(18,2) = ISNULL((SELECT Amount FROM #Param WHERE Parameter = N'_ReductionBasedOnYourselfLevel'), 11000000);

    DECLARE @_RedDepend           NUMERIC(18,2) = ISNULL((SELECT Amount FROM #Param WHERE Parameter = N'_ReductionBasedOnDependLevel'), 4400000);

    DECLARE @_TotalityPITRate     NUMERIC(18,2) = ISNULL((SELECT Amount FROM #Param WHERE Parameter = N'_TotalityPITRate'), 0.10);

    DECLARE @_pRound              NUMERIC(18,2) = ISNULL((SELECT Amount FROM #Param WHERE Parameter = N'_pRound'), 0);



    -- =========================================================================

    -- B2. Lấy danh sách NV trong phạm vi + HĐLĐ hiệu lực mới nhất phủ kỳ

    -- =========================================================================

    DROP TABLE IF EXISTS #Emp;

    CREATE TABLE #Emp (

        EmployeeCode      VARCHAR(16) PRIMARY KEY,

        FullName          NVARCHAR(256),

        BranchCode        VARCHAR(16),

        DeptCode          VARCHAR(16),

        PositionCode      VARCHAR(16),

        ResignDate        DATE,

        ContractCode      VARCHAR(16),

        ContractStart     DATE,

        ContractEnd       DATE,

        ProbationaryRate  NUMERIC(8,4),

        IsProbationary    INT,

        WorkingType       INT,            -- 0=FT, 1=PT theo giờ

        SalaryGradeId     INT,            -- ID của bậc lương trên HĐLĐ

        DependQuantity    INT             -- số người phụ thuộc

    );



    DECLARE @_Filter NVARCHAR(MAX) = N'';

    IF NULLIF(@_BranchCode, '') IS NOT NULL

        SET @_Filter = @_Filter + N' AND e.BranchCode IN (SELECT value FROM STRING_SPLIT(@p_branch, '','')) ';

    IF NULLIF(@_DeptCode, '') IS NOT NULL

        SET @_Filter = @_Filter + N' AND e.DeptCode   IN (SELECT value FROM STRING_SPLIT(@p_dept, '','')) ';

    IF NULLIF(@_EmployeeCode, '') IS NOT NULL

        SET @_Filter = @_Filter + N' AND e.Code       IN (SELECT value FROM STRING_SPLIT(@p_emp, '','')) ';



    DECLARE @_SqlEmp NVARCHAR(MAX) = N'

    INSERT INTO #Emp (EmployeeCode, FullName, BranchCode, DeptCode, PositionCode,

                      ResignDate, ContractCode, ContractStart, ContractEnd,

                      ProbationaryRate, IsProbationary, WorkingType, SalaryGradeId,

                      DependQuantity)

    SELECT e.Code, e.FullName, e.BranchCode, e.DeptCode, e.PositionCode,

           e.ResignDate,

           lc.ContractTypeCode, lc.StartDate, lc.EndDate,

           ISNULL(lc.ProbationaryRate, 1),

           ISNULL(ct.IsProbationary, 0),

           ISNULL(lc.WorkingType, 0),  -- mặc định FT nếu thiếu

           lc.SalaryGradeId,

           dep.DependQuantity

    FROM dbo.D20Employee e

        OUTER APPLY (

            -- HĐLĐ active mới nhất phủ kỳ. Ưu tiên DocDate gần nhất, sau đó Id mới nhất.

            SELECT TOP 1 c.ContractTypeCode, c.StartDate, c.EndDate,

                         c.ProbationaryRate, c.PositionCode, c.WorkingType,

                         c.SalaryGradeId       /* <-- cột mới: trỏ đến D20SalaryGrade.Id */

            FROM dbo.D30LabourContract c

            WHERE c.EmployeeCode = e.Code

              AND c.IsActive = 1

              AND ISNULL(c.StartDate, ''19000101'') <= @p_d2

              AND ISNULL(c.EndDate,   ''99991231'') >= @p_d1

            ORDER BY ISNULL(c.DocDate, c.StartDate) DESC, c.Id DESC

        ) lc

        LEFT JOIN dbo.D20ContractType ct ON ct.Code = lc.ContractTypeCode

        OUTER APPLY (

            SELECT COUNT(*) AS DependQuantity

            FROM dbo.D20Dependent d

            WHERE d.EmployeeCode = e.Code AND d.IsActive = 1

              AND ISNULL(d.ReductionStartDate, ''19000101'') <= @p_d2

              AND ISNULL(d.ReductionEndDate,   ''99991231'') >= @p_d1

        ) dep

    WHERE e.IsActive = 1 AND ISNULL(e.IsGroup, 0) = 0

      AND (e.ResignDate IS NULL OR e.ResignDate >= @p_d1)

    ' + @_Filter;



    EXEC sp_executesql @_SqlEmp,

        N'@p_d1 DATE, @p_d2 DATE, @p_branch VARCHAR(16), @p_dept VARCHAR(512), @p_emp VARCHAR(512)',

        @_DocDate1, @_DocDate2, @_BranchCode, @_DeptCode, @_EmployeeCode;



    -- =========================================================================

    -- B3. Tổng hợp chấm công trong kỳ cho từng NV

    --     D30Attendance không có EmployeeCode → join qua D30AssignedShift.

    -- =========================================================================

    DROP TABLE IF EXISTS #AttSum;

    CREATE TABLE #AttSum (

        EmployeeCode    VARCHAR(16) PRIMARY KEY,

        WorkingDays     NUMERIC(18,2),

        WorkingHours    NUMERIC(18,2),

        PaidLeaveDays   NUMERIC(18,2),

        UnpaidLeaveDays NUMERIC(18,2),

        WorkNightHours  NUMERIC(18,2),

        ShiftMealDays   INT,

        ExcludeDays     NUMERIC(18,2),

        ExcludeHours    NUMERIC(18,2)

    );



    INSERT INTO #AttSum

    SELECT asn.EmployeeCode,

           SUM(att.WorkingDays),

           SUM(att.WorkingHours),

           SUM(att.PaidLeaveDays),

           SUM(ISNULL(att.UnpaidLeaveDays, 0)),

           SUM(att.WorkNightHours),

           SUM(att.ShiftMeal),

           SUM(att.ExcludeDays),

           SUM(att.ExcludeHours)

    FROM dbo.D30Attendance att

        INNER JOIN dbo.D30AssignedShift asn ON asn.AssignId = att.AssignId

    WHERE att.IsActive = 1 AND asn.IsActive = 1

      AND att.[Date] BETWEEN @_DocDate1 AND @_DocDate2

      AND asn.EmployeeCode IN (SELECT EmployeeCode FROM #Emp)

    GROUP BY asn.EmployeeCode;

    -- =========================================================================

    -- B4. Lấy thưởng/giảm trừ từ D30BonusDeduction

    --     CHÚ Ý: schema D30BonusDeduction trong dump không có cột EmployeeCode

    --     hay Amount; nếu có chi tiết riêng thì điều chỉnh JOIN ở đây.

    -- =========================================================================

    DROP TABLE IF EXISTS #BonusDed;

    CREATE TABLE #BonusDed (

        EmployeeCode  VARCHAR(16) PRIMARY KEY,

        BonusAmount   NUMERIC(18,2) DEFAULT 0,

        DeductAmount  NUMERIC(18,2) DEFAULT 0

    );

    -- TODO: INSERT từ D30BonusDeduction khi có cột EmployeeCode + Amount

    -- INSERT INTO #BonusDed (EmployeeCode, BonusAmount, DeductAmount)

    -- SELECT EmployeeCode,

    --        SUM(CASE WHEN DocType = 1 THEN Amount ELSE 0 END),

    --        SUM(CASE WHEN DocType = 2 THEN Amount ELSE 0 END)

    -- FROM dbo.D30BonusDeduction

    -- WHERE IsActive = 1 AND DocDate BETWEEN @_DocDate1 AND @_DocDate2

    -- GROUP BY EmployeeCode;



    -- =========================================================================

    -- B5. Lấy lương theo bậc từ D20SalaryGradeDetail

    --     Map: NV → HĐLĐ.SalaryGradeId → D20SalaryGrade.Id → D20SalaryGradeDetail.ParentId

    --     Lấy tất cả khoản lương: BASE_SAL, MAIN_SAL, các phụ cấp.

    -- =========================================================================

    DROP TABLE IF EXISTS #SalAmt;

    CREATE TABLE #SalAmt (

        EmployeeCode VARCHAR(16),

        SalaryType   NVARCHAR(128),

        Amount       NUMERIC(18,2),

        PRIMARY KEY (EmployeeCode, SalaryType)

    );



    INSERT INTO #SalAmt (EmployeeCode, SalaryType, Amount)

    SELECT e.EmployeeCode, sgd.SalaryType, SUM(sgd.Amount)

    FROM #Emp e

        INNER JOIN dbo.D20SalaryGrade sg

            ON sg.Id = e.SalaryGradeId

           AND sg.IsActive = 1 AND ISNULL(sg.IsGroup, 0) = 0

           AND ISNULL(sg.EffectiveDate, '19000101') <= @_DocDate2

        INNER JOIN dbo.D20SalaryGradeDetail sgd

            ON sgd.ParentId = sg.Id AND sgd.IsActive = 1

    GROUP BY e.EmployeeCode, sgd.SalaryType;



    -- =========================================================================

    -- B6. Bảng tạm Payroll — 1 dòng / NV — chứa toàn bộ kết quả tính lương

    -- =========================================================================

    DROP TABLE IF EXISTS #Payroll;

    CREATE TABLE #Payroll (

        RowId             VARCHAR(16) NOT NULL PRIMARY KEY,

        BranchCode        CHAR(3),

        EmployeeCode      VARCHAR(32),

        DeptCode          VARCHAR(32),

        Probationary      TINYINT,

        ProbationaryRate  NUMERIC(8,4),

        WorkingType       INT,

        DependQuantity    INT,



        -- Ngày/giờ công

        WorkingDays       NUMERIC(8,2),

        WorkingHours      NUMERIC(8,2),

        PaidLeaveDays     NUMERIC(8,2),

        UnpaidLeaveDays   NUMERIC(8,2),

        WorkNightHours    NUMERIC(8,2),

        ShiftMealDays     INT,



        -- Lương cơ bản, lương đóng BH (lấy từ #SalAmt)

        BaseSalary        NUMERIC(18,2),    -- BASE_SAL: lương cơ bản (đóng BH)

        MainSalary        NUMERIC(18,2),    -- MAIN_SAL: lương công việc

        SalaryInsurance   NUMERIC(18,2),    -- lương đóng BH (= BASE_SAL by default)



        -- Phụ cấp cố định theo bậc lương (từ D20SalaryGradeDetail)

        TransportAllowance NUMERIC(18,2),   -- TRAFFIC_ALLOWANCE

        PhoneAllowance     NUMERIC(18,2),   -- TEL_ALLOWANCE

        OtherAllowanceFix  NUMERIC(18,2),   -- OTHER_ALLOWANCE

        ShiftAllowanceFix  NUMERIC(18,2),   -- SHIFT_ALLOWANCE2 (ăn ca cố định/tháng nếu có)



        -- Đơn giá / tháng và / giờ (sau khi áp tỉ lệ thử việc)

        UnitPerDay        NUMERIC(18,2),

        UnitPerHour       NUMERIC(18,2),



        -- Các khoản tiền lương

        WorkSalary        NUMERIC(18,2),    -- WORK_SAL = (BASE+MAIN)/_DaysPerMonth * WorkingDays

        PaidLeaveSalary   NUMERIC(18,2),    -- PAID_LEAVE_SAL

        OvertimeSalary    NUMERIC(18,2),    -- OvertimeSalary (tổng OT đã có hệ số)

        OVTTaxableIncome  NUMERIC(18,2),    -- phần OT chịu thuế

        WorkNightSalary   NUMERIC(18,2),

        ShiftMealAmount   NUMERIC(18,2),    -- Tiền ăn ca động (theo ngày × đơn giá)

        BonusSalary       NUMERIC(18,2),

        OtherSalary       NUMERIC(18,2),

        OffsetSalary      NUMERIC(18,2),    -- bù lương tối thiểu vùng (nếu có)



        -- Tổng thu nhập gộp

        GrossSalary       NUMERIC(18,2),



        -- BH NV trả

        SocialInsEMPLPay      NUMERIC(18,2),

        HealthInsEMPLPay      NUMERIC(18,2),

        TradeUnionInsEMPLPay  NUMERIC(18,2),

        UnemployedInsEMPLPay  NUMERIC(18,2),

        AccidentInsEMPLPay    NUMERIC(18,2),



        -- BH DN trả

        SocialInsPay          NUMERIC(18,2),

        HealthInsPay          NUMERIC(18,2),

        TradeUnionInsPay      NUMERIC(18,2),

        UnemployedInsPay      NUMERIC(18,2),

        AccidentInsPay        NUMERIC(18,2),



     -- Thuế TNCN

        TaxableIncome           NUMERIC(18,2),

        SelfDeduction           NUMERIC(18,2),

        DependentDeduction      NUMERIC(18,2),

        CharityAmount           NUMERIC(18,2),

        OtherDeductionsAmount   NUMERIC(18,2),

        AssessableIncome        NUMERIC(18,2),

        PersonalIncomeTaxAmount NUMERIC(18,2),

        DeductionPITaxAmount    NUMERIC(18,2),



        AdvancesAmount    NUMERIC(18,2),

        NetIncome         NUMERIC(18,2)

    );



    -- =========================================================================

    -- B7. Insert dữ liệu cơ bản vào #Payroll

    --     RowId = BranchCode (3) + 'PR' (2) + YYYYMM (6) + STT (5) = 16 chars

    --     STT đánh số PARTITION theo BranchCode để cùng branch không trùng nhau

    -- =========================================================================

    INSERT INTO #Payroll (

        RowId, BranchCode, EmployeeCode, DeptCode,

        Probationary, ProbationaryRate, WorkingType, DependQuantity,

        WorkingDays, WorkingHours, PaidLeaveDays, UnpaidLeaveDays, WorkNightHours, ShiftMealDays,

        BaseSalary, MainSalary, SalaryInsurance,

        TransportAllowance, PhoneAllowance, OtherAllowanceFix, ShiftAllowanceFix

    )

    SELECT

        LEFT(ISNULL(e.BranchCode, '___'), 3)

            + 'PR' + @_Period

            + RIGHT('00000' + CAST(ROW_NUMBER() OVER (

                PARTITION BY ISNULL(e.BranchCode, '___')

                ORDER BY e.EmployeeCode) AS VARCHAR(5)), 5),

        LEFT(ISNULL(e.BranchCode, '___'), 3),

        e.EmployeeCode, ISNULL(e.DeptCode, ''),

        e.IsProbationary, ISNULL(e.ProbationaryRate, 1),

        ISNULL(e.WorkingType, 0), ISNULL(e.DependQuantity, 0),



        ISNULL(a.WorkingDays, 0), ISNULL(a.WorkingHours, 0),

        ISNULL(a.PaidLeaveDays, 0), ISNULL(a.UnpaidLeaveDays, 0),

        ISNULL(a.WorkNightHours, 0), ISNULL(a.ShiftMealDays, 0),



        ISNULL(s_base.Amount, 0),

        ISNULL(s_main.Amount, 0),

        ISNULL(s_base.Amount, 0),    -- SalaryInsurance default = BASE_SAL



        ISNULL(s_traf.Amount, 0),    -- TRAFFIC_ALLOWANCE

        ISNULL(s_tel.Amount,  0),    -- TEL_ALLOWANCE

        ISNULL(s_oth.Amount,  0),    -- OTHER_ALLOWANCE

        ISNULL(s_sft.Amount,  0)     -- SHIFT_ALLOWANCE2 (cố định/tháng)

    FROM #Emp e

        LEFT JOIN #AttSum a    ON a.EmployeeCode = e.EmployeeCode

        LEFT JOIN #SalAmt s_base ON s_base.EmployeeCode = e.EmployeeCode AND s_base.SalaryType = N'BASE_SAL'

        LEFT JOIN #SalAmt s_main ON s_main.EmployeeCode = e.EmployeeCode AND s_main.SalaryType = N'MAIN_SAL'

        LEFT JOIN #SalAmt s_traf ON s_traf.EmployeeCode = e.EmployeeCode AND s_traf.SalaryType = N'TRAFFIC_ALLOWANCE'

        LEFT JOIN #SalAmt s_tel  ON s_tel.EmployeeCode  = e.EmployeeCode AND s_tel.SalaryType  = N'TEL_ALLOWANCE'

        LEFT JOIN #SalAmt s_oth  ON s_oth.EmployeeCode  = e.EmployeeCode AND s_oth.SalaryType  = N'OTHER_ALLOWANCE'

        LEFT JOIN #SalAmt s_sft  ON s_sft.EmployeeCode  = e.EmployeeCode AND s_sft.SalaryType  = N'SHIFT_ALLOWANCE2';

		

    -- =========================================================================

    -- B8. Tính các đơn giá và lương cơ bản

    --     Áp dụng tỉ lệ thử việc (ProbationaryRate) lên TẤT CẢ khoản tiền cứng

    --     (lương + phụ cấp), KHÔNG áp lên SalaryInsurance (đóng BH theo HĐ thật).

    --     UnitPerDay/Hour tính trên TỔNG lương cứng (BASE + MAIN) → để chia

    --     theo công thực tế.

    -- =========================================================================

    UPDATE #Payroll

    SET

        MainSalary         = ROUND(MainSalary         * ISNULL(ProbationaryRate, 1), 0),

        BaseSalary         = ROUND(BaseSalary         * ISNULL(ProbationaryRate, 1), 0),

        TransportAllowance = ROUND(TransportAllowance * ISNULL(ProbationaryRate, 1), 0),

        PhoneAllowance     = ROUND(PhoneAllowance     * ISNULL(ProbationaryRate, 1), 0),

        OtherAllowanceFix  = ROUND(OtherAllowanceFix  * ISNULL(ProbationaryRate, 1), 0),

        ShiftAllowanceFix  = ROUND(ShiftAllowanceFix  * ISNULL(ProbationaryRate, 1), 0)

    WHERE Probationary = 1;



    UPDATE #Payroll

    SET

        -- Đơn giá tính trên TỔNG lương cứng (BASE + MAIN), không chỉ MAIN.

        -- (Nếu chính sách công ty chỉ tính MAIN_SAL theo công thực tế, đổi lại.)

        UnitPerDay  = CASE WHEN @_DaysPerMonth > 0

                           THEN (BaseSalary + MainSalary) / @_DaysPerMonth ELSE 0 END,

        UnitPerHour = CASE WHEN @_DaysPerMonth > 0 AND @_HoursPerDay > 0

                           THEN (BaseSalary + MainSalary) / (@_DaysPerMonth * @_HoursPerDay) ELSE 0 END;



    -- =========================================================================

    -- B9. Tính các khoản lương theo công thực tế

    --     QUAN TRỌNG: Ngày công hưởng lương = WorkingDays + PaidLeaveDays

    --       - WorkingDays   = ngày đi làm thực tế (từ chấm công)

    --       - PaidLeaveDays = ngày nghỉ có lương (lễ, phép, việc riêng)

    --     → WorkSalary tính trên TỔNG, sau đó tách hiển thị PaidLeaveSalary riêng

    --       cho phiếu lương đẹp (nhưng KHÔNG cộng PaidLeaveSalary lần nữa vào Gross).

    --

    --     WorkingType: 0 = Fulltime, 1 = Parttime (lương theo giờ)

    -- =========================================================================

    UPDATE p

    SET

        -- FT: lương theo (ngày làm thực tế + ngày nghỉ có lương)

        -- PT: lương theo giờ làm thực tế (PT thường không có chế độ phép quy đổi giờ)

        WorkSalary = CASE

            WHEN p.WorkingType = 1

                THEN ROUND(p.UnitPerHour * p.WorkingHours, 0)

            ELSE ROUND(p.UnitPerDay * (p.WorkingDays + p.PaidLeaveDays), 0)

        END,

        -- Để hiển thị riêng trên phiếu lương (split của WorkSalary, không cộng Gross lần nữa)

        PaidLeaveSalary = CASE

            WHEN p.WorkingType = 1 THEN 0    -- PT không tách lương phép

            ELSE ROUND(p.UnitPerDay * p.PaidLeaveDays, 0)

        END,

        -- Tiền ăn ca (động): theo số ngày × đơn giá + cố định/tháng nếu có

        ShiftMealAmount  = ROUND(@_ShiftMealsUnit * p.ShiftMealDays, 0)

                         + ISNULL(p.ShiftAllowanceFix, 0),

        -- Phụ cấp lương đêm

        WorkNightSalary  = ROUND(p.UnitPerHour * p.WorkNightHours * @_WorknightRate, 0),

        -- Tăng ca: chưa tính do D30Attendance chưa tách OT theo loại.

        OvertimeSalary   = 0,

        OVTTaxableIncome = 0,

        -- Bonus / Other / Offset

        BonusSalary      = ISNULL(bd.BonusAmount, 0),

        OtherSalary      = ISNULL(p.TransportAllowance, 0)

                         + ISNULL(p.PhoneAllowance,     0)

                         + ISNULL(p.OtherAllowanceFix,  0),

        OffsetSalary     = 0

    FROM #Payroll p

        LEFT JOIN #BonusDed bd ON bd.EmployeeCode = p.EmployeeCode;

    -- =========================================================================

    -- B10. Gross salary và các khoản BH (NV trả + DN trả)

    --      GrossSalary = WorkSal (đã bao gồm phép) + OT + WorkNight + ShiftMeal

    --                  + Bonus + Other + Offset

    --      LƯU Ý: KHÔNG cộng PaidLeaveSalary vào Gross vì đã nằm trong WorkSalary.

    --             PaidLeaveSalary chỉ để hiển thị trên phiếu lương (split).

    -- =========================================================================

    UPDATE #Payroll

    SET

        GrossSalary = ISNULL(WorkSalary,       0)

                    + ISNULL(OvertimeSalary,   0)

                    + ISNULL(WorkNightSalary,  0)

                    + ISNULL(ShiftMealAmount,  0)

                    + ISNULL(BonusSalary,      0)

                    + ISNULL(OtherSalary,      0)

                    + ISNULL(OffsetSalary,     0),



        -- BH NV trả (% lương đóng BH)

        SocialInsEMPLPay     = ROUND(SalaryInsurance * @_PctSocialEMPL    / 100, 0),

        HealthInsEMPLPay     = ROUND(SalaryInsurance * @_PctHealthEMPL    / 100, 0),

        TradeUnionInsEMPLPay = ROUND(SalaryInsurance * @_PctTradeUnionEMPL/ 100, 0),

        UnemployedInsEMPLPay = ROUND(SalaryInsurance * @_PctUnemployedEMPL/ 100, 0),

        AccidentInsEMPLPay   = ROUND(SalaryInsurance * @_PctAccidentEMPL  / 100, 0),



        -- BH DN trả

        SocialInsPay         = ROUND(SalaryInsurance * @_PctSocial       / 100, 0),

        HealthInsPay         = ROUND(SalaryInsurance * @_PctHealth       / 100, 0),

        TradeUnionInsPay     = ROUND(SalaryInsurance * @_PctTradeUnion   / 100, 0),

        UnemployedInsPay     = ROUND(SalaryInsurance * @_PctUnemployed   / 100, 0),

        AccidentInsPay       = ROUND(SalaryInsurance * @_PctAccident     / 100, 0);





    -- =========================================================================

    -- B11. Thuế TNCN

    --   - TaxableIncome = GrossSalary - phần ăn ca không tính thuế (max _ShiftAllowanceMax)

    --   - AssessableIncome = TaxableIncome - BH NV trả - GTBT - GT người PT

    --   - PIT: bậc thang lũy tiến (chính thức) hoặc 10% toàn phần (thử việc/HĐ < 3 tháng)

    -- =========================================================================

    UPDATE #Payroll

    SET

        -- Phần ăn ca vượt mức max sẽ tính thuế

        TaxableIncome = GrossSalary

                      - CASE WHEN ShiftMealAmount > @_ShiftAllowanceMax

                             THEN @_ShiftAllowanceMax

                             ELSE ShiftMealAmount END,

        SelfDeduction       = @_RedYourself,

        DependentDeduction  = @_RedDepend * ISNULL(DependQuantity, 0),

        CharityAmount       = 0,

        OtherDeductionsAmount = 0,

        DeductionPITaxAmount  = 0;



    UPDATE #Payroll

    SET AssessableIncome =

        TaxableIncome

        - ISNULL(SocialInsEMPLPay,     0)

        - ISNULL(HealthInsEMPLPay,     0)

        - ISNULL(UnemployedInsEMPLPay, 0)

        - ISNULL(SelfDeduction,        0)

        - ISNULL(DependentDeduction,   0)

        - ISNULL(CharityAmount,        0)

        - ISNULL(OtherDeductionsAmount,0);



    UPDATE #Payroll SET AssessableIncome = 0 WHERE AssessableIncome < 0;



    -- PIT lũy tiến (HĐ chính thức) — nhánh thử việc/HĐ ngắn hạn dùng @_TotalityPITRate

    -- Theo luật VN: thuế 10% toàn phần CHỈ áp khi thu nhập >= 2,000,000/lần trả.

    -- Dưới 2tr → không khấu trừ tại nguồn (NV tự quyết toán cuối năm nếu có).

    UPDATE #Payroll

    SET PersonalIncomeTaxAmount =

        CASE

            WHEN (Probationary = 1 OR WorkingType = 1) AND GrossSalary >= 2000000 THEN

                ROUND(GrossSalary * @_TotalityPITRate, 0)

            WHEN  Probationary = 1 OR WorkingType = 1 THEN

                0     -- thu nhập < 2tr không khấu trừ

            ELSE

                CASE

                    WHEN AssessableIncome <=  5000000 THEN AssessableIncome * 0.05

                    WHEN AssessableIncome <= 10000000 THEN AssessableIncome * 0.10 -    250000

                    WHEN AssessableIncome <= 18000000 THEN AssessableIncome * 0.15 -    750000

                    WHEN AssessableIncome <= 32000000 THEN AssessableIncome * 0.20 -   1650000

                    WHEN AssessableIncome <= 52000000 THEN AssessableIncome * 0.25 -   3250000

                    WHEN AssessableIncome <= 80000000 THEN AssessableIncome * 0.30 -   5850000

                    ELSE                                   AssessableIncome * 0.35 -   9850000

                END

        END;



    UPDATE #Payroll SET PersonalIncomeTaxAmount = 0 WHERE PersonalIncomeTaxAmount < 0;





    -- =========================================================================

    -- B12. Net income = Gross - BH NV trả - PIT - Tạm ứng - Khoản trừ khác

    -- =========================================================================

    UPDATE #Payroll

    SET AdvancesAmount = 0,

        NetIncome = GrossSalary

                  - ISNULL(SocialInsEMPLPay,       0)

                  - ISNULL(HealthInsEMPLPay,       0)

                  - ISNULL(TradeUnionInsEMPLPay,   0)

                  - ISNULL(UnemployedInsEMPLPay,   0)

                  - ISNULL(AccidentInsEMPLPay,     0)

                  - ISNULL(PersonalIncomeTaxAmount,0)

                  - ISNULL(AdvancesAmount,         0)

                  - ISNULL(OtherDeductionsAmount,  0)

                  + ISNULL(DeductionPITaxAmount,   0);



    -- =========================================================================

    -- B13. Build #PayrollDetail từ các tham số TYPE='9' (chỉ tiêu lương)

    --      Mỗi Parameter Type=9 → 1 dòng D30PayrollDetail.

    --      Các giá trị Days/Hours/Amount được map từ #Payroll theo tên chỉ tiêu.

    -- =========================================================================

    DROP TABLE IF EXISTS #PD;

    CREATE TABLE #PD (

        RowIdPR      VARCHAR(16),

        BranchCode   CHAR(3),

        SalaryType   VARCHAR(64),

        BuiltinOrder INT,

        Coeff        NUMERIC(18,2),

        Amount       NUMERIC(18,2),

        Days         NUMERIC(6,2),

        Hours        NUMERIC(6,2)

    );



    -- Lấy danh sách chỉ tiêu (Type=9) đang hiệu lực

    DECLARE @_Type9 TABLE (Parameter NVARCHAR(64), Ord INT);

    INSERT INTO @_Type9 (Parameter, Ord)

    SELECT [Parameter], ROW_NUMBER() OVER (ORDER BY Id)

    FROM dbo.D20PayrollParameter

    WHERE IsActive = 1 AND [Type] = '9' 

      AND ISNULL(EffectiveDate, '19000101') <= @_DocDate2;



    -- Map từng chỉ tiêu sang giá trị tương ứng từ #Payroll

    INSERT INTO #PD (RowIdPR, BranchCode, SalaryType, BuiltinOrder, Coeff, Amount, Days, Hours)

    SELECT

        p.RowId,

        p.BranchCode,

        t.Parameter,

        t.Ord,

        0 AS Coeff,

        CASE t.Parameter

            -- Lương cứng từ bậc lương

            WHEN N'BASE_SAL'              THEN p.BaseSalary

            WHEN N'MAIN_SAL'              THEN p.MainSalary

            -- Lương theo công thực tế

            WHEN N'WORK_SAL'              THEN p.WorkSalary

            WHEN N'PAID_LEAVE_SAL'        THEN p.PaidLeaveSalary

            WHEN N'MAIN1_SAL'             THEN p.WorkSalary

            WHEN N'MAIN2_SAL'             THEN p.WorkSalary

            -- Phụ cấp (lấy từ #Payroll đã giảm theo Probationary)

            WHEN N'TRAFFIC_ALLOWANCE'     THEN p.TransportAllowance

            WHEN N'TEL_ALLOWANCE'         THEN p.PhoneAllowance

            WHEN N'OTHER_ALLOWANCE'       THEN p.OtherAllowanceFix

            WHEN N'SHIFT_ALLOWANCE'       THEN p.ShiftMealAmount    -- ăn ca động (theo ngày)

            WHEN N'SHIFT_ALLOWANCE2'      THEN p.ShiftAllowanceFix  -- ăn ca cố định/tháng

            -- Lương đêm

            WHEN N'WORKNIGHTTIME'         THEN p.WorkNightSalary

            -- Lương net

            WHEN N'NET_SAL'               THEN p.NetIncome

            WHEN N'BASE_NET_SAL'          THEN p.NetIncome

            -- OT (chưa tính, để 0)

            WHEN N'NORMAL_OVT'            THEN 0

            WHEN N'WEEKENDS_OVT'          THEN 0

            WHEN N'HOLIDAYS_OVT'          THEN 0

            WHEN N'WORKNIGHT_NORMAL_OT'   THEN 0

            WHEN N'WORKNIGHT_WEEKENDS_OT' THEN 0

            WHEN N'WORKNIGHT_HOLIDAY_OT'  THEN 0

            WHEN N'SHIPPER_SAL'           THEN 0

            ELSE 0

        END AS Amount,

        CASE t.Parameter

            WHEN N'WORK_DAY'        THEN p.WorkingDays

            WHEN N'PAID_LEAVE_DAY'  THEN p.PaidLeaveDays

            WHEN N'SHIFT_DAY'       THEN p.ShiftMealDays

            ELSE 0

        END AS Days,

        CASE t.Parameter

            WHEN N'WORKNIGHTTIME'         THEN p.WorkNightHours

            WHEN N'SHIFT_HOUR'            THEN p.WorkingHours

            ELSE 0

        END AS Hours

    FROM #Payroll p

        CROSS JOIN  @_Type9 t;





    -- Bỏ các dòng chỉ tiêu = 0 hết (giảm size bảng detail)

    DELETE FROM #PD WHERE Amount = 0 AND Days = 0 AND Hours = 0;



    -- =========================================================================

    -- B14. Xóa dữ liệu cũ cùng kỳ (cùng Branch + Date trong khoảng) rồi INSERT

    -- =========================================================================

    BEGIN TRY

        BEGIN TRANSACTION;



        -- Xóa Detail (theo Payroll cũ trong kỳ)

        DELETE pd

        FROM dbo.D30PayrollDetail pd

            INNER JOIN dbo.D30Payroll pr

                ON pr.BranchCode = pd.BranchCode AND pr.RowId = pd.RowIdPR

        WHERE pr.[Date] BETWEEN @_DocDate1 AND @_DocDate2

          AND pr.EmployeeCode IN (SELECT EmployeeCode FROM #Emp);



        -- Xóa Payroll cũ

        DELETE FROM dbo.D30Payroll

        WHERE [Date] BETWEEN @_DocDate1 AND @_DocDate2

          AND EmployeeCode IN (SELECT EmployeeCode FROM #Emp);



        -- INSERT D30Payroll

        INSERT INTO dbo.D30Payroll (

            BranchCode, RowId, [Date], DeptCode, EmployeeCode, WorkArea,

            Trained, NetCalc,

            GrossSalary, Probationary, ProbationaryRate, SalaryInsurance,

            OvertimeSalary, OffsetSalary, OVTTaxableIncome,

            BonusSalary, OtherSalary, NetIncome,

            SocialInsPay, SocialInsEMPLPay,

            HealthInsPay, HealthInsEMPLPay,

            TradeUnionInsPay, TradeUnionInsEMPLPay,

            UnemployedInsPay, UnemployedInsEMPLPay,

            AccidentInsPay, AccidentInsEMPLPay,

            AdvancesAmount,

            TaxableIncome, SelfDeduction, DependQuantity, DependentDeduction,

            CharityAmount, OtherDeductionsAmount,

            AssessableIncome, PersonalIncomeTaxAmount,

            ParaCode,

            SocialInsurance, HealthInsurance, TradeUnionInsurance,

            UnemployedInsurance, PersonalIncomeTax, InEcoZones,

            DeductionPITaxAmount,

            IsActive, CreatedBy, CreatedAt, ModifiedBy, ModifiedAt

        )

        SELECT

            BranchCode, RowId, @_DocDate2, DeptCode, EmployeeCode, '',

            0, 0,

            ISNULL(GrossSalary, 0), ISNULL(Probationary, 0),

            ISNULL(ProbationaryRate, 1), ISNULL(SalaryInsurance, 0),

            ISNULL(OvertimeSalary, 0), ISNULL(OffsetSalary, 0), ISNULL(OVTTaxableIncome, 0),

            ISNULL(BonusSalary, 0), ISNULL(OtherSalary, 0), ISNULL(NetIncome, 0),

            ISNULL(SocialInsPay, 0),     ISNULL(SocialInsEMPLPay, 0),

            ISNULL(HealthInsPay, 0),     ISNULL(HealthInsEMPLPay, 0),

            ISNULL(TradeUnionInsPay, 0), ISNULL(TradeUnionInsEMPLPay, 0),

            ISNULL(UnemployedInsPay, 0), ISNULL(UnemployedInsEMPLPay, 0),

            ISNULL(AccidentInsPay, 0),   ISNULL(AccidentInsEMPLPay, 0),

            ISNULL(AdvancesAmount, 0),

            ISNULL(TaxableIncome, 0), ISNULL(SelfDeduction, 0),

            ISNULL(DependQuantity, 0), ISNULL(DependentDeduction, 0),

            ISNULL(CharityAmount, 0), ISNULL(OtherDeductionsAmount, 0),

            ISNULL(AssessableIncome, 0), ISNULL(PersonalIncomeTaxAmount, 0),

            'DEFAULT',

            CASE WHEN SalaryInsurance > 0 THEN 1 ELSE 0 END,

            CASE WHEN SalaryInsurance > 0 THEN 1 ELSE 0 END,

            CASE WHEN SalaryInsurance > 0 THEN 1 ELSE 0 END,

            CASE WHEN SalaryInsurance > 0 THEN 1 ELSE 0 END,

            1, 0,

            ISNULL(DeductionPITaxAmount, 0),

            1, @_UserId, GETUTCDATE(), @_UserId, GETUTCDATE()

        FROM #Payroll;



        SET @_RowCount = @@ROWCOUNT;



        -- INSERT D30PayrollDetail

        INSERT INTO dbo.D30PayrollDetail (

            ParentId, IsGroup, BranchCode, RowIdPR, SalaryType,

            BuiltinOrder, Coeff, Amount, Days, Hours,

            IsActive, CreatedBy, CreatedAt, ModifiedBy, ModifiedAt

        )

        SELECT

            -1, 0, BranchCode, RowIdPR, SalaryType,

            BuiltinOrder, ISNULL(Coeff, 0), ISNULL(Amount, 0),

            ISNULL(Days, 0), ISNULL(Hours, 0),

            1, @_UserId, GETUTCDATE(), @_UserId, GETUTCDATE()

 FROM #PD;



        SET @_DetailCount = @@ROWCOUNT;



        COMMIT TRANSACTION;



        DECLARE @_Msg NVARCHAR(500) =

            N'✓ Đã tạo bảng lương: '

          + CAST(@_RowCount AS NVARCHAR(20)) + N' dòng D30Payroll, '

          + CAST(@_DetailCount AS NVARCHAR(20)) + N' dòng D30PayrollDetail '

          + N'(từ ' + CONVERT(NVARCHAR(10), @_DocDate1, 103)

          + N' đến ' + CONVERT(NVARCHAR(10), @_DocDate2, 103) + N').';

        PRINT @_Msg;

        RAISERROR(N'%s', 0, 1, @_Msg) WITH NOWAIT;



        SELECT @_RowCount    AS PayrollRows,

               @_DetailCount AS DetailRows,

               @_DocDate1    AS FromDate,

               @_DocDate2    AS ToDate,

               @_Msg         AS Message,

               1             AS IsSuccess;

    END TRY

    BEGIN CATCH

        IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;

        DECLARE @_Err NVARCHAR(2048) = N'✗ Lỗi: ' + ERROR_MESSAGE();

        SELECT 0, 0, @_DocDate1, @_DocDate2, @_Err, 0;

        THROW;

    END CATCH



    DROP TABLE IF EXISTS #Param;

    DROP TABLE IF EXISTS #Emp;

    DROP TABLE IF EXISTS #AttSum;

    DROP TABLE IF EXISTS #BonusDed;

    DROP TABLE IF EXISTS #SalAmt;

    DROP TABLE IF EXISTS #Payroll;

    DROP TABLE IF EXISTS #PD;

END

```
