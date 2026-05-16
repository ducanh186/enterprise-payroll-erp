- D00User: Quản lý người sử dụng

| Tên trường | Kiểu dữ liệu | Độ dài | Nội dung |
| :---- | :---- | :---- | :---- |
| UserName | VARCHAR | 24 | User đăng nhập vào chương trình, để dạng Unique Key là duy nhất |
| FullName | NVARCHAR | 96 | Tên đầy đủ của người sử dụng |
| EmployeeCode | INT |  | Mã nhân viên sử dụng |
| Password | NVARCHAR | 64 | Mật khẩu |
| LockDate | DATETIME |  | Ngày khóa dữ liệu của người sử dụng |

- D20Branch: Danh mục đơn vị cơ sở, chi nhánh

| Tên trường | Kiểu dữ liệu | Độ dài | Nội dung |
| :---- | :---- | :---- | :---- |
| Code | VARCHAR | 16 | Mã đơn vị cơ sở |
| Name | NVARCHAR | 128 | Tên đơn vị cơ sở |
| Address | NVARCHAR | 256 | Địa chỉ |

- D20Department: Danh mục bộ phận

| Tên trường | Kiểu dữ liệu | Độ dài | Nội dung |
| :---- | :---- | :---- | :---- |
| Code | VARCHAR | 16 | Mã bộ phận |
| Name | NVARCHAR | 256 | Tên bộ phận |

- D20Position: Danh mục chức vụ

| Tên trường | Kiểu dữ liệu | Độ dài | Nội dung |
| :---- | :---- | :---- | :---- |
| Code | VARCHAR | 16 | Mã chức vụ |
| Name | NVARCHAR | 256 | Tên chức vụ |

- D20Employee: Danh mục nhân viên

| Tên trường | Kiểu dữ liệu | Độ dài | Nội dung |
| :---- | :---- | :---- | :---- |
| Code | VARCHAR | 16 | Mã cán bộ nhân viên |
| FullName | NVARCHAR | 256 | Tên đầy đủ của cán bộ nhân viên |
| BranchCode | VARCHAR | 16 | Mã đơn vị cơ sở |
| DeptCode | INT |  | Mã bộ phận |
| PositionCode | INT |  | Mã chức vụ |
| BirthDate | DATE |  | Ngày sinh của cán bộ nhân viên |
| Gender | TINYINT |  | Giới tính của cán bộ nhân viên, quy định theo  1: Nam 2: Nữ 3: Khác |
| IdCardNo | VARCHAR | 24 | Số CCCD/CC/CMND của cán bộ nhân viên |
| TaxRegNo | VARCHAR | 24 | Mã số thuế của cán bộ nhân viên |
| Nationality | NVARCHAR | 64 | Quốc tịch của cán bộ nhân viên |
| Address | NVARCHAR | 256 | Địa chỉ của cán bộ nhân viên |
| Mobile | VARCHAR | 12 | Số điện thoại của cán bộ nhân viên |
| Email | VARCHAR | 128 | Địa chỉ email của cán bộ nhân viên |
| BankAccountNo | VARCHAR | 24 | Số tài khoản ngân hàng của cán bộ nhân viên |
| BankName | NVARCHAR | 256 | Ngân hàng thụ hưởng |
| FirstWorkingDate | DATE |  | Ngày bắt đầu vào làm |
| ResignDate | DATE |  | Ngày nghỉ việc |

- D20Dependent: Danh mục người phụ thuộc

| Tên trường | Kiểu dữ liệu | Độ dài | Nội dung |
| :---- | :---- | :---- | :---- |
| Name | NVARCHAR | 256 | Tên đầy đủ của người phụ thuộc |
| EmployeeCode | NVARCHAR | 16 | Mã của nhân viên tương ứng với người phụ thuộc |
| BirthDate | DATE |  | Ngày tháng năm sinh của người phụ thuộc |
| Occupation | NVARCHAR | 256 | Nghề nghiệp của người phụ thuộc |
| IdCardNo | VARCHAR | 16 | Số CC/CCCD/CMND của người phụ thuộc |
| TaxRegNo | VARCHAR | 24 | Mã số thuế của người phụ thuộc |
| Relationship | NVARCHAR | 128 | Quan hệ với cán bộ nhân viên |
| ReductionStartDate | DATE |  | Ngày bắt đầu giảm trừ |
| ReductionEndDate | DATE |  | Ngày kết thúc giảm trừ |

- D20ContractType: Danh mục loại hợp đồng

| Tên trường | Kiểu dữ liệu | Độ dài | Nội dung |
| :---- | :---- | :---- | :---- |
| Code | NVARCHAR | 24 | Mã loại hợp đồng |
| Name | NVARCHAR | 256 | Tên loại hợp đồng |
| Type | INT |  | Hợp đồng hoặc phụ lục hợp đồng 0: Hợp đồng 1: Phụ lục hợp đồng |
| NumberOfMonth | INT |  | Thời hạn hợp đồng |
| IsProbationary | INT |  | Là hợp đồng thử việc |

- D20Shift: Danh mục ca làm việc

| Tên trường | Kiểu dữ liệu | Độ dài | Nội dung |
| :---- | :---- | :---- | :---- |
| Code | VARCHAR | 16 | Mã ca làm việc |
| Name | NVARCHAR | 128 | Tên của ca làm việc |
| Description | NVARCHAR | 256 | Nội dung, diễn giải về ca làm việc |
| IsCheckin | TINYINT |  | Yêu cầu chấm công vào  0: Không 1: Có |
| StartTime | DATETIME |  | Giờ vào ca |
| StartTimeValid1 | DATETIME |  | Giờ bắt đầu chấm công vào hợp lệ |
| StartTimeValid2 | DATETIME |  | Giờ kết thúc chấm công vào hợp lệ |
| IsCheckout | TINYINT |  | Yêu cầu chấm công ra 0: Không 1: Có |
| EndTime | DATETIME |  | Giờ ra ca |
| EndTimeValid1 | DATETIME |  | Giờ bắt đầu chấm công ra hợp lệ |
| EndTimeValid2 | DATETIME |  | Giờ kết thúc chấm công ra hợp lệ |
| ShiftBreak | INT |  | Có khoảng nghỉ giữa ca 0: Không nghỉ 1: Có nghỉ |
| StartShiftBreak | DATETIME |  | Giờ bắt đầu nghỉ giữa ca |
| StartBreakTimeValid1 | DATETIME |  | Giờ bắt đầu chấm công ra giữa ca hợp lệ |
| StartBreakTimeValid2 | DATETIME |  | Giờ kết thúc chấm công ra giữa ca hợp lệ |
| EndShiftBreak | DATETIME |  | Giờ kết thúc nghỉ giữa ca |
| EndBreakTimeValid1 | DATETIME |  | Giờ bắt đầu chấm công vào giữa ca hợp lệ |
| EndBreakTimeValid2 | DATETIME |  | Giờ kết thúc chấm công vào giữa ca hợp lệ |
| WorkDay | NUMERIC | 8,2 | Số ngày công trong 1 ca |
| WorkingHours | NUMERIC | 8,2 | Số giờ làm việc trong 1 ca |
| ShiftBreakMins | INT |  | Số phút nghỉ giữa ca |
| StartWorkingNightTime | DATETIME |  | Giờ bắt đầu tính làm đêm |
| EndWorkingNightTime | DATETIME |  | Giờ kết thúc tính làm đêm |
| ShiftMeal | INT |  | Số bữa ăn ca được tính |
| MinHourMeal | NUMERIC | 8,2 | Số giờ làm việc tối thiểu để tính ăn ca |

- D20Holiday: Danh mục ngày nghỉ trong năm		

| Tên trường | Kiểu dữ liệu | Độ dài | Nội dung |
| :---- | :---- | :---- | :---- |
| Date | DATE |  | Ngày nghỉ trong năm |
| Description | NVARCHAR | 256 | Nội dung ngày nghỉ |
| NumberOfDay | NUMERIC | 8,2 | Số ngày được tính  |
| HolidayType | INT |  | Loại ngày nghỉ: 1: Ngày lễ, tết 2: Ngày nghỉ thường |

- D20LateEarlyRegulation: Quy định đi trễ về sớm

| Tên trường | Kiểu dữ liệu | Độ dài | Nội dung |
| :---- | :---- | :---- | :---- |
| Code | NVARCHAR | 24 | Mã của quy định |
| Name | NVARCHAR | 128 | Tên của quy định đi trễ và về sớm |
| Description | NVARCHAR | 256 | Diễn giải về quy định |
| AppliedDate | DATE |  | Ngày bắt đầu áp dụng |
| ExpiredDate | DATE |  | Ngày hết hạn |

- D20LateEarlyRegulationDetail: Bảng chi tiết về quy định đi trễ và về sớm	

| Tên trường | Kiểu dữ liệu | Độ dài | Nội dung |
| :---- | :---- | :---- | :---- |
| RowId | NVARCHAR | 16 | Mã định danh của chi tiết quy định |
| LateEarlyRegCode | NVARCHAR | 24 | Mã của quy định |
| Description | NVARCHAR | 256 | Diễn giải về chi tiết quy định |
| StartMinute | INT |  | Mốc thời gian đi trễ/về sớm |
| EndMinute | INT |  |  |
| ExcludeTime | NUMERIC | 8,2 | Số phút công bị trừ |
| ExcludeWorkDay | NUMERIC | 8,2 | Số ngày công bị trừ |

- D20SalaryScale: Danh mục dải lương

| Tên trường | Kiểu dữ liệu | Độ dài | Nội dung |
| :---- | :---- | :---- | :---- |
| Code | NVARCHAR | 24 | Mã của dải lương |
| Name | NVARCHAR | 256 | Tên diễn giải của dải lương |
| Description | NVARCHAR | 256 | Diễn giải về quy định |

- D20SalaryGrade: Danh mục bậc lương

| Tên trường | Kiểu dữ liệu | Độ dài | Nội dung |
| :---- | :---- | :---- | :---- |
| Id | INT |  | Mã định danh bậc lương |
| ScaleCode | VARCHAR | 64 | Mã của dải lương |
| EffectiveDate | DATE |  | Ngày bắt đầu áp dụng |
| SalaryLevel | INT |  | Số bậc lương |
| Description | NVARCHAR | 256 | Ghi chú |


- D20SalaryGradeDetail: Chi tiết các khoản thu nhập của bậc lương

| Tên trường | Kiểu dữ liệu | Độ dài | Nội dung |
| :---- | :---- | :---- | :---- |
| RowId | NVARCHAR | 32 | Mã định danh bậc lương |
| ParentId | VARCHAR | 64 | Mã định danh của dải lương |
| SalaryType | INT |  | Loại thu nhập, phụ cấp |
| Amount | NUMERIC | 18,2 | Giá trị |
| Description | NVARCHAR | 256 | Ghi chú |

- D20PayrollParameter: Tham số lương

| Tên trường | Kiểu dữ liệu | Độ dài | Nội dung |
| :---- | :---- | :---- | :---- |
| Id | INT |  | Mã định danh tự tăng của tham số lương |
| Parameter | NVARCHAR | 64 | Mã tham số lương dùng trong công thức hoặc thủ tục tính lương |
| Name | NVARCHAR | 128 | Tên hiển thị của tham số lương |
| Description | NVARCHAR | 128 | Diễn giải chi tiết về ý nghĩa của tham số lương |
| EffectiveDate | DATE |  | Ngày bắt đầu áp dụng tham số |
| Type | VARCHAR | 32 | Loại tham số lương, dùng để phân nhóm khi xử lý tính lương |
| Amount | NUMERIC | 18,2 | Giá trị số tiền, tỷ lệ hoặc hệ số tương ứng với tham số |


- D30LabourContract: Hợp đồng lao động & Phụ lục hợp đồng

| Tên trường | Kiểu dữ liệu | Độ dài | Nội dung |
| :---- | :---- | :---- | :---- |
| DocId | VARCHAR | 32 | Mã định danh cho hợp đồng |
| DocNo | VARCHAR | 32 | Số hợp đồng/phụ lục hợp đồng |
| DocDate | DATE |  | Ngày ký hợp đồng |
| EmployeeCode | VARCHAR | 16 | Mã nhân viên  |
| TypeCode | INT |  | Mã ID của loại hợp đồng/phụ lục hợp đồng |
| StartDate | DATE |  | Ngày bắt đầu thực hiện hợp đồng |
| EndDate | DATE |  | Ngày kết thúc thực hiện hợp đồng |
| ProbationaryRate | DECIMAL | 10,2 | Tỷ lệ hưởng lương thử việc |
| PositionCode | INT |  | Mã ID của chức vụ |
| SalaryGradeId | INT |  | Mã định danh của dải lương |
| WorkingType | INT |  | Hình thức làm việc: 0: Full-time  1: Part-time thời vụ theo giờ |

- D30BonusDeduction**:** Chứng từ ghi nhận thu nhập khác/lương thưởng/tiền phạt

| Tên trường | Kiểu dữ liệu | Độ dài | Nội dung |
| :---- | :---- | :---- | :---- |
| DocId | VARCHAR | 32 | Mã định danh cho chứng từ |
| DocNo | VARCHAR | 32 | Số chứng từ |
| DocDate | DATE |  | Ngày chứng từ |
| DocType | INT |  | Loại chứng từ, quy định như sau: 1: Ghi nhận tăng 2: Ghi nhận giảm |
| DeptCode | VARCHAR | 16 | Mã bộ phận của chứng từ |
| EmployeeCode | VARCHAR | 16 | Mã nhân viên của chứng từ |
| Description | NVARCHAR  | 256 | Diễn giải về thu nhập khác/giảm trừ khác |
| SalaryType | NVARCHAR | 32 | Tham số lương tương ứng |
| Amount | NUMERIC | 18,2 | Số tiền tương ứng |

- D30AssignedShift: Phân ca làm việc

| Tên trường | Kiểu dữ liệu | Độ dài | Nội dung |
| :---- | :---- | :---- | :---- |
| AssignId | VARCHAR |  | Mã định danh phân ca |
| Date | DATE |  | Ngày lập hoặc ngày ghi nhận phân ca |
| EmployeeCode | VARCHAR | 16 | Mã nhân viên được phân ca |
| ShiftCode | VARCHAR | 16 | Mã ca làm việc được phân cho nhân viên |
| StartDate | DATE |  | Ngày bắt đầu áp dụng phân ca |
| EndDate | DATE |  | Ngày kết thúc hiệu lực phân ca |
| IncludeMon | INT |  | Xác định có áp dụng phân ca vào thứ Hai hay không: 1 là có, 0 là không |
| IncludeTue | INT |  | Xác định có áp dụng phân ca vào thứ Ba hay không: 1 là có, 0 là không |
| IncludeWed | INT |  | Xác định có áp dụng phân ca vào thứ Tư hay không: 1 là có, 0 là không |
| IncludeThu | INT |  | Xác định có áp dụng phân ca vào thứ Năm hay không: 1 là có, 0 là không |
| IncludeFri | INT |  | Xác định có áp dụng phân ca vào thứ Sáu hay không: 1 là có, 0 là không |
| IncludeSat | INT |  | Xác định có áp dụng phân ca vào thứ Bảy hay không: 1 là có, 0 là không |
| IncludeSun | INT |  | Xác định có áp dụng phân ca vào Chủ nhật hay không: 1 là có, 0 là không |
| Description | NVARCHAR | 512 | Diễn giải cụ thể về nội dung phân ca |

- D30CheckInOut: Thời gian vào \- ra của cán bộ nhân viên

| Tên trường | Kiểu dữ liệu | Độ dài | Nội dung |
| :---- | :---- | :---- | :---- |
| CheckTime | DATETIME |  | Thời gian chấm công |
| EmployeeCode | VARCHAR | 16 | Mã của nhân viên |

- D30AttendanceDoc: Đơn từ chấm công

| Tên trường | Kiểu dữ liệu | Độ dài | Nội dung |
| :---- | :---- | :---- | :---- |
| DocId | VARCHAR | 32 | Mã định danh của chứng từ |
| DocNo | VARCHAR | 32 | Số của chứng từ |
| DocDate | DATE |  | Ngày làm đơn |
| DocType | VARCHAR | 3 | Số hiệu loại chứng từ: AL: Đơn xin nghỉ phép MC: Chấm công bổ sung |
| EmployeeCode | VARCHAR | 16 | Mã nhân viên áp dụng |
| ManagerCode | VARCHAR | 16 | Mã nhân viên quản lý phê duyệt |
| Description | NVARCHAR | 256 | Diễn giải chung |

- D30AbsenceDetail: Chi tiết đơn xin nghỉ phép

| Tên trường | Kiểu dữ liệu | Độ dài | Nội dung |
| :---- | :---- | :---- | :---- |
| RowId | VARCHAR | 32 | Mã ID định danh của từng chi tiết |
| DocId | VARCHAR | 32 | Mã ID định danh của chứng từ mẹ |
| Date | DATE |  | Ngày nghỉ |
| Type | INT |  | Loại ngày nghỉ 1: Nghỉ nửa ca đầu ngày 2: Nghỉ nửa ca cuối ngày 3: Nghỉ cả ngày |
| WorkingHours | NUMERIC | 8,2 | Số giờ làm việc dự kiến nghỉ |
| WorkingDays | NUMERIC | 8,2 | Số ngày làm việc dự kiến nghỉ |
| Description | NVARCHAR | 256 | Diễn giải chi tiết |

- D30CheckInManualDetail: Chi tiết chấm công bổ sung

| Tên trường | Kiểu dữ liệu | Độ dài | Nội dung |
| :---- | :---- | :---- | :---- |
| RowId | VARCHAR | 32 | Mã ID định danh của từng chi tiết |
| DocId | VARCHAR | 32 | Mã ID định danh của chứng từ mẹ |
| CheckTime | DATETIME |  | Thời gian chấm bổ sung |
| Description | NVARCHAR | 256 | Lý do giải trình chi tiết |

- D30Attendance: Bảng tính ngày công			

| Tên trường | Kiểu dữ liệu | Độ dài | Nội dung |
| :---- | :---- | :---- | :---- |
| DocId | VARCHAR | 16 | Mã ID định danh của từng ngày công |
| Date | DATE |  | Ngày công làm việc |
| AssignedShiftCode | VARCHAR | 16 | Mã phân ca làm việc |
| WorkingHours | NUMERIC | 8,2 | Số giờ làm việc thực tế |
| WorkingDays | NUMERIC | 8,2 | Số ngày làm việc thực tế |
| UnpaidLeaveDays | NUMERIC | 8,2 | Số ngày nghỉ không lương |
| PaidLeaveDays | NUMERIC | 8,2 | Số ngày tính nghỉ phép |
| ExcludeDays | NUMERIC | 8,2 | Số ngày công bị trừ do đi trễ, về sớm |
| ExcludeHours | NUMERIC | 8,2 | Số giờ công bị trừ do đi trễ, về sớm |
| WorkNightHours | NUMERIC | 8,2 | Số giờ tính làm việc ban đêm |
| ShiftMeal | INT |  | Số bữa ăn được phụ cấp |

- D30Payroll: Bảng lương tháng của nhân viên

| Tên trường | Kiểu dữ liệu | Độ dài | Nội dung |
| :---- | :---- | :---- | :---- |
| BranchCode | CHAR | 3 | Mã đơn vị cơ sở |
| Id | VARCHAR | 16 | Mã định danh bản ghi tính lương |
| Date | DATE |  | Ngày tính lương |
| DeptCode | VARCHAR | 32 | Mã bộ phận |
| EmployeeCode | VARCHAR | 32 | Mã nhân viên |
| ParaCode | VARCHAR | 32 | Mã tham số tính lương |
| NetCalc | TINYINT |  | Cờ xác định tính lương theo NET |
| GrossSalary | NUMERIC | 18,2 | Lương gross |
| Probationary | TINYINT |  | Cờ xác định nhân viên thử việc |
| ProbationaryRate | NUMERIC | 8,4 | Tỷ lệ hưởng lương thử việc |
| SalaryInsurance | NUMERIC | 18,2 | Mức lương làm căn cứ đóng bảo hiểm |
| OvertimeSalary | NUMERIC | 18,2 | Tiền lương làm thêm giờ |
| OffsetSalary | NUMERIC | 18,2 | Khoản bù trừ lương |
| OVTTaxableIncome | NUMERIC | 18,2 | Thu nhập chịu thuế từ làm thêm giờ |
| BonusSalary | NUMERIC | 18,2 | Tiền thưởng |
| OtherSalary | NUMERIC | 18,2 | Thu nhập khác |
| NetIncome | NUMERIC | 18,2 | Thu nhập thực nhận trước khấu trừ |
| SocialInsPay | NUMERIC | 18,2 | Tiền bảo hiểm xã hội doanh nghiệp đóng |
| SocialInsEMPLPay | NUMERIC | 18,2 | Tiền bảo hiểm xã hội người lao động đóng |
| HealthInsPay | NUMERIC | 18,2 | Tiền bảo hiểm y tế doanh nghiệp đóng |
| HealthInsEMPLPay | NUMERIC | 18,2 | Tiền bảo hiểm y tế người lao động đóng |
| TradeUnionInsPay | NUMERIC | 18,2 | Kinh phí công đoàn doanh nghiệp đóng |
| TradeUnionInsEMPLPay | NUMERIC | 18,2 | Đoàn phí công đoàn người lao động đóng |
| UnemployedInsPay | NUMERIC | 18,2 | Tiền bảo hiểm thất nghiệp doanh nghiệp đóng |
| UnemployedInsEMPLPay | NUMERIC | 18,2 | Tiền bảo hiểm thất nghiệp người lao động đóng |
| AccidentInsPay | NUMERIC | 18,2 | Tiền bảo hiểm tai nạn lao động doanh nghiệp đóng |
| AccidentInsEMPLPay | NUMERIC | 18,2 | Tiền bảo hiểm tai nạn lao động người lao động đóng |
| AdvancesAmount | NUMERIC | 18,2 | Số tiền tạm ứng |
| TaxableIncome | NUMERIC | 18,2 | Thu nhập chịu thuế |
| SelfDeduction | NUMERIC | 18,2 | Mức giảm trừ bản thân |
| DependQuantity | INT |  | Số lượng người phụ thuộc |
| DependentDeduction | NUMERIC | 18,2 | Mức giảm trừ người phụ thuộc |
| OtherDeductionsAmount | NUMERIC | 18,2 | Các khoản giảm trừ khác |
| AssessableIncome | NUMERIC | 18,2 | Thu nhập tính thuế |
| PersonalIncomeTaxAmount | NUMERIC | 18,2 | Số tiền thuế thu nhập cá nhân phải nộp |
| SocialInsurance | TINYINT |  | Cờ xác định có tham gia bảo hiểm xã hội |
| HealthInsurance | TINYINT |  | Cờ xác định có tham gia bảo hiểm y tế |
| TradeUnionInsurance | TINYINT |  | Cờ xác định có tham gia công đoàn |
| UnemployedInsurance | TINYINT |  | Cờ xác định có tham gia bảo hiểm thất nghiệp |
| PersonalIncomeTax | TINYINT |  | Cờ xác định có phát sinh thuế TNCN |
| DeductionPITaxAmount | NUMERIC | 18,2 | Khoản khấu trừ thuế thu nhập cá nhân |

- D30PayrollDetail: Chi tiết các khoản thu nhập trong bảng lương

| Tên trường | Kiểu dữ liệu | Độ dài | Nội dung |
| :---- | :---- | :---- | :---- |
| RowId | VARCHAR | 16 | Mã định danh cho dòng chi tiết |
| PayRollId | VARCHAR | 16 | Mã định danh của bảng lương mẹ |
| SalaryType | NVARCHAR | 128 | Mã hoặc tên loại thu nhập, lương tương ứng |
| BuiltinOrder | INT |  | Thứ tự hiển thị của dòng chi tiết trong bảng lương |
| Coeff | NUMERIC | 18,2 | Hệ số dùng để tính khoản lương hoặc khoản phụ cấp |
| Amount | NUMERIC | 18,2 | Số tiền tương ứng của dòng chi tiết lương |
| Days | NUMERIC | 6,2 | Số ngày công hoặc số ngày được tính cho khoản lương |
| Hours | NUMERIC | 6,2 | Số giờ công hoặc số giờ được tính cho khoản lương |

