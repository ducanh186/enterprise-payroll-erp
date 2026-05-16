Brief

LINK THIẾT KẾ:
https://drive.google.com/drive/folders/1PHWNbxX_1CoeqtqYWuAs5QnqO12Rj7tA?usp=sha
ring

1.  Thông tin chung: Web-App quản lý hợp đồng, tính công và tiền lương
2.  Thời gian: Trước 10/4 cần hoàn chỉnh
3.  Framework

-  Database: SQL Server:  bảng, views, function và stored procedures để hiện

lên phần mềm (bên t lo phần này)

-  Backend: .NET(C#) / tuỳ theo Dev tư vấn
-  Frontend: Tuỳ theo Dev tư vấn

4.  Nội dung

-  Tập trung chính vào chấm công và tính lương chạy hoàn chỉnh được
-  Usecase:

-  Class:

-  Sequence

-

Thẻ 2

1.  Panel navigator sửa lại hiển thị của 3 cấp chính:

●  Nhân sự & HĐLĐ
○  Danh mục

■  Hồ sơ cán bộ nhân viên
■  Danh mục loại hợp đồng
■  Danh mục thang/bậc lương

○  Biến động

■  Hợp đồng lao động & phụ lục hợp đồng

●  Chấm công

○  Danh mục

■  Quy định đi trễ, về sớm
■  Danh mục ngày nghỉ trong năm
■  Danh mục ca làm việc

○  Biến động

■  Phân ca làm việc
■  Dữ liệu thời gian vào - ra
■  Đơn xin nghỉ phép
■  Chấm công bổ sung
■  Tổng hợp công

○  Báo cáo

■  Bảng chấm công

●  Tính lương

○  Danh mục

■  Bộ công thức & Tham số lương

○  Biến động

■  Khen thưởng & Kỷ luật
■  Tính lương
■  Bảng lương

○  Báo cáo

■  Phiếu lương cá nhân
■  Bảng lương theo phòng ban

2.  Sửa lại text trong UI đồng nhất theo Sentence case.

3.  Khi sửa hoặc tạo bản ghi mới thì sẽ hiển thị pop-up hoặc tab mới trên browser màn
hình editor chứ không để trong cùng 1 tab với màn hình hiển thị danh sách được
không?

4.  Stored procedure

●  Tổng hợp công: usp_Hrm_AttendanceCollection
@_DocDate1 DATE = '20240801',
@_DocDate2 DATE = '20240831',
@_EmployeeId VARCHAR(512) = '’,
@_DeptId VARCHAR(512) = '',
@_BranchCode VARCHAR(3) = 'A01',
@_nUserId INT = 0

●  Bảng chấm công: dbo.usp_Hrm_AttendanceReport

@_DocDate1 DATE = '20240801',
@_DocDate2 DATE = '20240831',
@_GroupDeptLevel TINYINT = 3, -- Cấp nhóm
@_DeptId VARCHAR(512) = ‘’,
@_EmployeeId VARCHAR(512) = ‘’,
@_ShowDataType TINYINT = 0, -- 1-Xem rút gọn, 2-Đầy đủ
@_SymbolWorkday TINYINT = 1, -- Hiển thị biểu tượng chấm công
@_NotInOutSymbol_RowId VARCHAR(24) = '', -- Không chấm công

vào, ra

@_NotInSymbol_RowId VARCHAR(24)

= '', -- Không chấm công

vào

@_NotOutSymbol_RowId VARCHAR(24) = '', -- Không chấm công ra
@_Holiday1Symbol VARCHAR(24) = '', -- Ngày nghỉ lễ
@_Holiday2Symbol VARCHAR(24) = '', -- Ngày nghỉ lễ bù
@_BranchCode VARCHAR(3) = ‘’, – Mã đơn vị cơ sở
@_nUserId INT = 0

●  Bảng phân ca hàng ngày: usp_Hrm_B30HrmAssignShift

@_DocDate1 DATE = '20240101',
@_DocDate2 DATE = '20241231',
@_EmployeeId VARCHAR(1024) = NULL,
@_DeptId VARCHAR(1024) = NULL,
@_nUserId INT = 0,
@_BranchCode VARCHAR(3) = 'A01'

●  Bảng tổng hợp đi trễ về sớm: usp_Hrm_InOut_LaterEarly

@_DocDate1 DATE = '20240101',
@_DocDate2 DATE = '20241231',
@_EmployeeId VARCHAR(1024) = NULL,
@_DeptId VARCHAR(1024) = NULL,
@_nUserId INT = 0,
@_BranchCode VARCHAR(3) = 'A01'

●  Tính lương:
●  Phiếu lương cá nhân:
●  Bảng tổng hợp thanh toán lương và các khoản hỗ trợ
●

Thẻ 3

Folder các file sửa:

HRM_Fujimart

1.  Sửa tên web thành: Fujimart HRM

-  Logo

2.  Database chốt map lại theo file này:
-  File bak database: DATABASE
-  Có 1 số dữ liệu để test, có thể chạy thử tháng 1/2026
-  Chú thích diễn giải cho các bảng

Brief

3.  Sửa lại navigator và các chức năng hiển thị theo BFD này (bỏ chữ quản lý đi

khi lên phần mềm với các danh mục, chứng từ)

-  Bổ sung thêm bảng lương (hiển thị D30Payroll đầu phiếu và D30PayrollDetail
chi tiết) cho phép sửa dữ liệu ở quản lý tiền lương trước gửi email phiếu
lương cá nhân

-  Bổ sung thêm Bảng công thời gian (D30Attendance) cho phép sửa dữ liệu.

4.  Tham số tính lương: Hiển thị 2 theo 2 tab tương ứng với 2 view
vD20PayrollPara_ValuePara: Tham số giá trị
vD20PayrollPara_SalaryType: Loại thu nhập, lương thưởng

-
-

5.  Danh mục người phụ thuộc sẽ là một tab chi tiết của danh mục nhân viên

6.  Dữ liệu thời gian vào ra: Cần import được từ excel lên trên phần mềm

File excel đổ vào trên phần mềm sẽ có dạng như sau

Data checkinout.xlsx

7.  Các chức năng, báo cáo sử dụng thủ tục sẽ xử lý như sau (3 chức năng + 3

report)

a.  Tính và tổng hợp công:

EXEC usp_CreateAndCalculateAttendance

DATE = '20260101',
@_DocDate1
NVARCHAR(16) = 'A01,A02',
@_BranchCode
NVARCHAR(16) = '',
@_DeptCode
@_EmployeeCode  NVARCHAR(16) = ''

Output: Update/Insert dữ liệu vào bảng Attendance, chạy xong hiển thị thông
báo kết quả đã thành công

b.  Tạo bảng lương và tính lương

EXEC usp_CreateAndCalculateAttendance

@_DocDate1
DATE = '20260101',
@_BranchCode
NVARCHAR(16) = 'A01,A02',
NVARCHAR(16) = '',
@_DeptCode
@_EmployeeCode  NVARCHAR(16) = ''

Output tương tự như tính và tổng hợp công, Update/Insert vào bảng Payroll
và PayrollDetail,  chạy xong hiển thị thông báo kết quả đã thành công

c.  Bảng chấm công

EXEC dbo.usp_AttendanceReport
    @_DocDate1     DATE         = '20260901',
    @_DocDate2     DATE         = '20260930',
    @_BranchCode   VARCHAR(16)  = '',
    @_DeptCode     VARCHAR(512) = ‘’,
    @_EmployeeCode VARCHAR(512) = ‘’

Output báo cáo theo mẫu excel, hiện tại là báo cáo động ở phần các ngày trong kỳ chạy báo
cáo, dev có thể cân nhắc xem làm được k thì fix cứng 31 cột cũng dc:
-  2 results set + biến output trả về gán cho các thông tin trên file

Template excel:
Demo:

Demo bảng chấm công.xlsx

Template bảng chấm công.xlsx

d.  Bảng thanh toán lương theo chi nhánh, phòng ban

EXEC PROC dbo.usp_PayrollReport
    @_DocDate1     DATE         = '20260901',
    @_BranchCode   VARCHAR(16)  = '',
    @_DeptCode     VARCHAR(512) = ‘’,
    @_EmployeeCode VARCHAR(512) = ‘’

=> Output báo cáo theo mẫu excel
Template:
Demo:

Template bảng lương.xlsx

Demo bảng lương theo phòng ban.xlsx

e.  Phiếu lương cá nhân

EXEC dbo.usp_PayrollSlip
@_DocDate1 = '2026-05-05',
                         @_EmployeeCode = '',
                         @_DeptCode = '',
                         @_BranchCode = '',
                         @_SendEmail = 0,
                         @_MailProfile = N''

-  Set các biến (trừ EmployeeCode và DocDate1) mặc định theo giá trị trên
-  Chỉ hiển thị và cho nhập  EmployeeCode và DocDate1

=> Output trả về bảng theo demo
Template:
Demo:

Demo phiếu lương cá nhân.xlsx

Template phiếu lương cá nhân.xlsx

f.  Gửi email phiếu lương cá nhân

EXEC dbo.usp_PayrollSlip

@_DocDate1 = '2026-05-05',
                         @_EmployeeCode = '',

                         @_DeptCode = '',
                         @_BranchCode = 'A01,A02',
                         @_SendEmail = 1,
                         @_MailProfile = N''

-  Set cứng SendEmail và MailProfile mặc định bằng giá trị trên
-  Chỉ hiển thị và cho nhập điều kiện 4 biến đầu

=> Output trả về thông báo kết quả gửi thành công hay chưa

8.  Export kết xuất được 3 báo cáo trên ra dạng excel

Chú thích bảng

-  D00User: Quản lý người sử dụng

Tên trường
UserName

Kiểu dữ liệu  Độ dài  Nội dung
24
VARCHAR

FullName
EmployeeCode
Password
LockDate

NVARCHAR  96
INT
NVARCHAR  64
DATETIME

User  đăng  nhập  vào  chương  trình,  để  dạng
Unique Key là duy nhất
Tên đầy đủ của người sử dụng
Mã nhân viên sử dụng
Mật khẩu
Ngày khóa dữ liệu của người sử dụng

-  D20Branch: Danh mục đơn vị cơ sở, chi nhánh

Tên trường
Code
Name
Address

Kiểu dữ liệu  Độ dài  Nội dung
VARCHAR
16
NVARCHAR  128
NVARCHAR  256

Mã đơn vị cơ sở
Tên đơn vị cơ sở
Địa chỉ

-  D20Department: Danh mục bộ phận

Tên trường
Code
Name

Kiểu dữ liệu  Độ dài  Nội dung
VARCHAR
16
NVARCHAR  256

Mã bộ phận
Tên bộ phận

-  D20Position: Danh mục chức vụ

Tên trường  Kiểu dữ liệu
Code
Name

VARCHAR
NVARCHAR

Độ dài  Nội dung
16
256

Mã chức vụ
Tên chức vụ

-  D20Employee: Danh mục nhân viên

Tên trường
Code
FullName
BranchCode
DeptCode
PositionCode
BirthDate
Gender

Kiểu dữ liệu  Độ dài  Nội dung
16
VARCHAR
NVARCHAR  256
VARCHAR
16
INT
INT
DATE
TINYINT

Mã cán bộ nhân viên
Tên đầy đủ của cán bộ nhân viên
Mã đơn vị cơ sở
Mã bộ phận
Mã chức vụ
Ngày sinh của cán bộ nhân viên
Giới  tính  của  cán  bộ  nhân  viên,  quy  định
theo

-  1: Nam
-  2: Nữ
-  3: Khác

IdCardNo

VARCHAR

24

TaxRegNo
Nationality
Address

24
VARCHAR
NVARCHAR  64
NVARCHAR  256

Số  CCCD/CC/CMND  của  cán  bộ  nhân
viên
Mã số thuế của cán bộ nhân viên
Quốc tịch của cán bộ nhân viên
Địa chỉ của cán bộ nhân viên

VARCHAR
Mobile
Email
VARCHAR
BankAccountNo  VARCHAR

12
128
24

BankName
FirstWorkingDate  DATE
DATE
ResignDate

NVARCHAR  256

Số điện thoại của cán bộ nhân viên
Địa chỉ email của cán bộ nhân viên
Số  tài  khoản  ngân  hàng  của  cán  bộ  nhân
viên
Ngân hàng thụ hưởng
Ngày bắt đầu vào làm
Ngày nghỉ việc

-  D20Dependent: Danh mục người phụ thuộc

Tên trường
Name
EmployeeCode

Kiểu dữ liệu  Độ dài  Nội dung
NVARCHAR  256
NVARCHAR  16

BirthDate

DATE

Occupation
IdCardNo

NVARCHAR  256
16
VARCHAR

TaxRegNo
Relationship
ReductionStartDate  DATE
ReductionEndDate  DATE

VARCHAR
24
NVARCHAR  128

Tên đầy đủ của người phụ thuộc
Mã  của  nhân viên tương ứng với người
phụ thuộc
Ngày  tháng  năm  sinh  của  người  phụ
thuộc
Nghề nghiệp của người phụ thuộc
Số  CC/CCCD/CMND  của  người  phụ
thuộc
Mã số thuế của người phụ thuộc
Quan hệ với cán bộ nhân viên
Ngày bắt đầu giảm trừ
Ngày kết thúc giảm trừ

-  D20ContractType: Danh mục loại hợp đồng

Tên trường
Code
Name
Type

Kiểu dữ liệu  Độ dài  Nội dung
NVARCHAR  24
NVARCHAR  256
INT

Mã loại hợp đồng
Tên loại hợp đồng
Hợp đồng hoặc phụ lục hợp đồng

NumberOfMonth
IsProbationary

INT
INT

-  0: Hợp đồng
-  1: Phụ lục hợp đồng

Thời hạn hợp đồng
Là hợp đồng thử việc

-  D20Shift: Danh mục ca làm việc

Tên trường
Code
Name
Description
IsCheckin

Kiểu dữ liệu  Độ dài  Nội dung
VARCHAR
16
NVARCHAR  128
NVARCHAR  256
TINYINT

Mã ca làm việc
Tên của ca làm việc
Nội dung, diễn giải về ca làm việc
Yêu cầu chấm công vào

-  0: Không
-  1: Có

StartTime
StartTimeValid1
StartTimeValid2

DATETIME
DATETIME
DATETIME

Giờ vào ca
Giờ bắt đầu chấm công vào hợp lệ
Giờ kết thúc chấm công vào hợp lệ

IsCheckout

TINYINT

Yêu cầu chấm công ra

EndTime
EndTimeValid1
EndTimeValid2
ShiftBreak

DATETIME
DATETIME
DATETIME
INT

StartShiftBreak
StartBreakTimeValid1

DATETIME
DATETIME

StartBreakTimeValid2

DATETIME

EndShiftBreak
EndBreakTimeValid1

DATETIME
DATETIME

EndBreakTimeValid2

DATETIME

NUMERIC
NUMERIC
INT

WorkDay
WorkingHours
ShiftBreakMins
StartWorkingNightTime  DATETIME
EndWorkingNightTime  DATETIME
ShiftMeal
MinHourMeal

INT
NUMERIC

-  0: Không
-  1: Có

Giờ ra ca
Giờ bắt đầu chấm công ra hợp lệ
Giờ kết thúc chấm công ra hợp lệ
Có khoảng nghỉ giữa ca
-  0: Không nghỉ
-  1: Có nghỉ

Giờ bắt đầu nghỉ giữa ca
Giờ  bắt  đầu  chấm  công  ra  giữa  ca
hợp lệ
Giờ  kết  thúc  chấm  công  ra  giữa  ca
hợp lệ
Giờ kết thúc nghỉ giữa ca
Giờ  bắt  đầu  chấm  công  vào  giữa ca
hợp lệ
Giờ  kết  thúc chấm công vào giữa ca
hợp lệ
Số ngày công trong 1 ca
Số giờ làm việc trong 1 ca
Số phút nghỉ giữa ca
Giờ bắt đầu tính làm đêm
Giờ kết thúc tính làm đêm
Số bữa ăn ca được tính
Số  giờ  làm  việc  tối  thiểu  để  tính ăn
ca

8,2
8,2

8,2

-  D20Holiday: Danh mục ngày nghỉ trong năm

Tên trường
Date
Description
NumberOfDay
HolidayType

Kiểu dữ liệu  Độ dài  Nội dung
DATE
NVARCHAR  256
8,2
NUMERIC
INT

Ngày nghỉ trong năm
Nội dung ngày nghỉ
Số ngày được tính
Loại ngày nghỉ:

-  1: Ngày lễ, tết
-  2: Ngày nghỉ thường

-  D20LateEarlyRegulation: Quy định đi trễ về sớm

Tên trường

Kiểu dữ liệu  Độ dài  Nội dung

Code
Name
Description
AppliedDate
ExpiredDate

NVARCHAR  24
NVARCHAR  128
NVARCHAR  256
DATE
DATE

Mã của quy định
Tên của quy định đi trễ và về sớm
Diễn giải về quy định
Ngày bắt đầu áp dụng
Ngày hết hạn

-  D20LateEarlyRegulationDetail: Bảng chi tiết về quy định đi trễ và về sớm

Tên trường
Kiểu dữ liệu  Độ dài  Nội dung
NVARCHAR  16
RowId
LateEarlyRegCode  NVARCHAR  24
NVARCHAR  256
Description
INT
StartMinute
INT
EndMinute
ExcludeTime
NUMERIC
ExcludeWorkDay  NUMERIC

8,2
8,2

Số phút công bị trừ
Số ngày công bị trừ

Mã định danh của chi tiết quy định
Mã của quy định
Diễn giải về chi tiết quy định

Mốc thời gian đi trễ/về sớm

-  D20SalaryScale: Danh mục dải lương

Tên trường
Code
Name
Description

Kiểu dữ liệu  Độ dài  Nội dung
NVARCHAR  24
NVARCHAR  256
NVARCHAR  256

Mã của dải lương
Tên diễn giải của dải lương
Diễn giải về quy định

-  D20SalaryGrade: Danh mục bậc lương

Tên trường
Id
ScaleCode
EffectiveDate
SalaryLevel
Description

Kiểu dữ liệu  Độ dài  Nội dung
INT
VARCHAR
DATE
INT
NVARCHAR  256

Mã định danh bậc lương
Mã của dải lương
Ngày bắt đầu áp dụng
Số bậc lương
Ghi chú

64

-  D20SalaryGradeDetail: Chi tiết các khoản thu nhập của bậc lương

Tên trường
RowId
ParentId
SalaryType
Amount
Description

Kiểu dữ liệu  Độ dài  Nội dung
NVARCHAR  32
VARCHAR
64
INT
18,2
NUMERIC
NVARCHAR  256

Mã định danh bậc lương
Mã định danh của dải lương
Loại thu nhập, phụ cấp
Giá trị
Ghi chú

-  D20PayrollParameter: Tham số lương

Tên trường

Kiểu dữ liệu  Độ dài  Nội dung

Id

INT

Mã định danh tự tăng của tham số lương

Parameter

NVARCHAR  64

Mã  tham  số  lương  dùng  trong  công  thức
hoặc thủ tục tính lương

Name

NVARCHAR  128

Tên hiển thị của tham số lương

Description

NVARCHAR  128

Diễn  giải  chi  tiết  về  ý  nghĩa  của  tham  số
lương

EffectiveDate  DATE

Ngày bắt đầu áp dụng tham số

Type

VARCHAR

32

Amount

NUMERIC

18,2

Loại tham số lương, dùng để phân nhóm khi
xử lý tính lương

Giá  trị  số  tiền,  tỷ  lệ  hoặc  hệ  số  tương  ứng
với tham số

-  D30LabourContract: Hợp đồng lao động & Phụ lục hợp đồng

Tên trường
DocId
DocNo
DocDate
EmployeeCode
TypeCode

Kiểu dữ liệu  Độ dài  Nội dung
32
VARCHAR
VARCHAR
32
DATE
VARCHAR
INT

16

StartDate
EndDate
ProbationaryRate
PositionCode
SalaryGradeId
WorkingType

DATE
DATE
DECIMAL
INT
INT
INT

10,2

Mã định danh cho hợp đồng
Số hợp đồng/phụ lục hợp đồng
Ngày ký hợp đồng
Mã nhân viên
Mã ID của loại hợp đồng/phụ lục hợp
đồng
Ngày bắt đầu thực hiện hợp đồng
Ngày kết thúc thực hiện hợp đồng
Tỷ lệ hưởng lương thử việc
Mã ID của chức vụ
Mã định danh của dải lương
Hình thức làm việc:
-  0: Full-time
-  1: Part-time thời vụ theo giờ

Mã định danh cho chứng từ
Số chứng từ
Ngày chứng từ
Loại chứng từ, quy định như sau:
1: Ghi nhận tăng
2: Ghi nhận giảm
Mã bộ phận của chứng từ
Mã nhân viên của chứng từ
Diễn giải về thu nhập khác/giảm trừ
khác
Tham số lương tương ứng

-  D30BonusDeduction: Chứng từ ghi nhận thu nhập khác/lương thưởng/tiền phạt
Kiểu dữ liệu  Độ dài  Nội dung
32
VARCHAR
VARCHAR
32
DATE
INT

Tên trường
DocId
DocNo
DocDate
DocType

DeptCode
EmployeeCode
Description

16
VARCHAR
VARCHAR
16
NVARCHAR   256

SalaryType

NVARCHAR

32

Amount

NUMERIC

18,2

Số tiền tương ứng

-  D30AssignedShift: Phân ca làm việc

Tên trường

Kiểu dữ liệu  Độ dài  Nội dung

AssignId

VARCHAR

Mã định danh phân ca

Date

DATE

Ngày lập hoặc ngày ghi nhận phân ca

EmployeeCode

VARCHAR

ShiftCode

VARCHAR

16

16

StartDate

EndDate

IncludeMon

IncludeTue

IncludeWed

IncludeThu

IncludeFri

IncludeSat

IncludeSun

DATE

DATE

INT

INT

INT

INT

INT

INT

INT

Mã nhân viên được phân ca

Mã  ca  làm  việc  được  phân  cho  nhân
viên

Ngày bắt đầu áp dụng phân ca

Ngày kết thúc hiệu lực phân ca

Xác  định  có  áp  dụng  phân ca vào thứ
Hai hay không: 1 là có, 0 là không

Xác  định  có  áp  dụng  phân ca vào thứ
Ba hay không: 1 là có, 0 là không

Xác  định  có  áp  dụng  phân ca vào thứ
Tư hay không: 1 là có, 0 là không

Xác  định  có  áp  dụng  phân ca vào thứ
Năm hay không: 1 là có, 0 là không

Xác  định  có  áp  dụng  phân ca vào thứ
Sáu hay không: 1 là có, 0 là không

Xác  định  có  áp  dụng  phân ca vào thứ
Bảy hay không: 1 là có, 0 là không

Xác định có áp dụng phân ca vào Chủ
nhật hay không: 1 là có, 0 là không

Description

NVARCHAR  512

Diễn giải cụ thể về nội dung phân ca

-  D30CheckInOut: Thời gian vào - ra của cán bộ nhân viên

Tên trường
CheckTime
EmployeeCode

Kiểu dữ liệu  Độ dài  Nội dung
DATETIME
VARCHAR

Thời gian chấm công
Mã của nhân viên

16

-  D30AttendanceDoc: Đơn từ chấm công

Tên trường

Kiểu dữ liệu  Độ dài  Nội dung

DocId
DocNo
DocDate
DocType

VARCHAR
VARCHAR
DATE
VARCHAR

32
32

3

EmployeeCode
ManagerCode
Description

16
VARCHAR
VARCHAR
16
NVARCHAR  256

Mã định danh của chứng từ
Số của chứng từ
Ngày làm đơn
Số hiệu loại chứng từ:

-  AL: Đơn xin nghỉ phép
-  MC: Chấm công bổ sung

Mã nhân viên áp dụng
Mã nhân viên quản lý phê duyệt
Diễn giải chung

-  D30AbsenceDetail: Chi tiết đơn xin nghỉ phép

Tên trường
RowId
DocId
Date
Type

Kiểu dữ liệu  Độ dài  Nội dung
32
VARCHAR
VARCHAR
32
DATE
INT

Mã ID định danh của từng chi tiết
Mã ID định danh của chứng từ mẹ
Ngày nghỉ
Loại ngày nghỉ

-  1: Nghỉ nửa ca đầu ngày
-  2: Nghỉ nửa ca cuối ngày
-  3: Nghỉ cả ngày

WorkingHours
WorkingDays
Description

NUMERIC
NUMERIC
NVARCHAR

8,2
8,2
256

Số giờ làm việc dự kiến nghỉ
Số ngày làm việc dự kiến nghỉ
Diễn giải chi tiết

-  D30CheckInManualDetail: Chi tiết chấm công bổ sung
Nội dung
Mã ID định danh của từng chi tiết
Mã ID định danh của chứng từ mẹ
Thời gian chấm bổ sung
Lý do giải trình chi tiết

Kiểu dữ liệu  Độ dài
VARCHAR
VARCHAR
DATETIME
NVARCHAR  256

Tên trường
RowId
DocId
CheckTime
Description

32
32

-  D30Attendance: Bảng tính ngày công

Tên trường
DocId
Date
AssignedShiftCode
WorkingHours
WorkingDays
UnpaidLeaveDays
PaidLeaveDays
ExcludeDays
ExcludeHours
WorkNightHours
ShiftMeal

Kiểu dữ liệu  Độ dài  Nội dung
VARCHAR
16
DATE
VARCHAR
NUMERIC
NUMERIC
NUMERIC
NUMERIC
NUMERIC
NUMERIC
NUMERIC
INT

Mã ID định danh của từng ngày công
Ngày công làm việc
Mã phân ca làm việc
Số giờ làm việc thực tế
Số ngày làm việc thực tế
Số ngày nghỉ không lương
Số ngày tính nghỉ phép
Số ngày công bị trừ do đi trễ, về sớm
Số giờ công bị trừ do đi trễ, về sớm
Số giờ tính làm việc ban đêm
Số bữa ăn được phụ cấp

16
8,2
8,2
8,2
8,2
8,2
8,2
8,2

-  D30Payroll: Bảng lương tháng của nhân viên

Tên trường

BranchCode

Id

Date

Kiểu dữ liệu  Độ dài  Nội dung

CHAR

3

Mã đơn vị cơ sở

VARCHAR

16

Mã định danh bản ghi tính lương

DATE

Ngày tính lương

DeptCode

VARCHAR

32

Mã bộ phận

EmployeeCode

VARCHAR

32

Mã nhân viên

ParaCode

NetCalc

GrossSalary

Probationary

VARCHAR

32

Mã tham số tính lương

TINYINT

Cờ xác định tính lương theo NET

NUMERIC

18,2

Lương gross

TINYINT

Cờ xác định nhân viên thử việc

ProbationaryRate

NUMERIC

8,4

Tỷ lệ hưởng lương thử việc

SalaryInsurance

NUMERIC

18,2  Mức  lương  làm  căn  cứ  đóng  bảo

hiểm

OvertimeSalary

NUMERIC

18,2

Tiền lương làm thêm giờ

OffsetSalary

NUMERIC

18,2

Khoản bù trừ lương

OVTTaxableIncome

NUMERIC

18,2

Thu  nhập  chịu  thuế  từ  làm  thêm
giờ

BonusSalary

OtherSalary

NetIncome

NUMERIC

18,2

Tiền thưởng

NUMERIC

18,2

Thu nhập khác

NUMERIC

18,2

Thu  nhập  thực  nhận  trước  khấu
trừ

SocialInsPay

NUMERIC

18,2

SocialInsEMPLPay

NUMERIC

18,2

HealthInsPay

NUMERIC

18,2

Tiền  bảo  hiểm  xã  hội  doanh
nghiệp đóng

Tiền  bảo  hiểm  xã  hội  người  lao
động đóng

Tiền  bảo  hiểm  y  tế  doanh nghiệp
đóng

HealthInsEMPLPay

NUMERIC

18,2

TradeUnionInsPay

NUMERIC

18,2

TradeUnionInsEMPLPay

NUMERIC

18,2

UnemployedInsPay

NUMERIC

18,2

UnemployedInsEMPLPay  NUMERIC

18,2

AccidentInsPay

NUMERIC

18,2

AccidentInsEMPLPay

NUMERIC

18,2

Tiền bảo hiểm y tế người lao động
đóng

Kinh phí công đoàn doanh nghiệp
đóng

Đoàn  phí  công  đoàn  người  lao
động đóng

Tiền  bảo  hiểm  thất  nghiệp  doanh
nghiệp đóng

Tiền  bảo  hiểm  thất  nghiệp  người
lao động đóng

Tiền  bảo  hiểm  tai  nạn  lao  động
doanh nghiệp đóng

Tiền  bảo  hiểm  tai  nạn  lao  động
người lao động đóng

AdvancesAmount

NUMERIC

18,2

Số tiền tạm ứng

TaxableIncome

NUMERIC

18,2

Thu nhập chịu thuế

SelfDeduction

NUMERIC

18,2  Mức giảm trừ bản thân

DependQuantity

INT

Số lượng người phụ thuộc

DependentDeduction

NUMERIC

18,2  Mức giảm trừ người phụ thuộc

OtherDeductionsAmount

NUMERIC

18,2

Các khoản giảm trừ khác

AssessableIncome

NUMERIC

18,2

Thu nhập tính thuế

PersonalIncomeTaxAmoun
t

NUMERIC

18,2

Số  tiền  thuế  thu  nhập  cá  nhân
phải nộp

SocialInsurance

TINYINT

HealthInsurance

TINYINT

TradeUnionInsurance

TINYINT

UnemployedInsurance

TINYINT

Cờ xác định có tham gia bảo hiểm
xã hội

Cờ xác định có tham gia bảo hiểm
y tế

Cờ  xác  định  có  tham  gia  công
đoàn

Cờ xác định có tham gia bảo hiểm
thất nghiệp

PersonalIncomeTax

TINYINT

DeductionPITaxAmount

NUMERIC

18,2

Cờ  xác  định  có  phát  sinh  thuế
TNCN

Khoản  khấu  trừ  thuế  thu  nhập  cá
nhân

-  D30PayrollDetail: Chi tiết các khoản thu nhập trong bảng lương

Tên trường

Kiểu dữ liệu

Độ dài  Nội dung

RowId

VARCHAR

PayRollId

VARCHAR

16

16

Mã định danh cho dòng chi tiết

Mã định danh của bảng lương mẹ

SalaryType

NVARCHAR

128

BuiltinOrder

INT

Coeff

NUMERIC

18,2

Amount

NUMERIC

18,2

Days

NUMERIC

6,2

Hours

NUMERIC

6,2

Mã  hoặc  tên  loại  thu nhập, lương tương
ứng

Thứ  tự  hiển  thị  của  dòng  chi  tiết  trong
bảng lương

Hệ  số  dùng  để  tính  khoản  lương  hoặc
khoản phụ cấp

Số  tiền  tương  ứng  của  dòng  chi  tiết
lương

Số  ngày  công  hoặc  số  ngày  được  tính
cho khoản lương

Số  giờ  công  hoặc  số  giờ  được  tính  cho
khoản lương

Thẻ 5

1.  Nút thu gọn đang bị trồng hình lên với các mục

2.  Sửa lại navigator, bỏ các mục sau:

-  Nhân sự & HĐLĐ

-  Chấm công

-  Tiền lương:

+  Thêm Gửi email phiếu lương vào dưới Tính lương (sử dụng thủ tục

đã chú thích trong tab 3

+  Sửa Bộ công thức và tham số lương thành Tham số lương

a.  Gửi email phiếu lương cá nhân

EXEC dbo.usp_PayrollSlip

@_DocDate1 = '2026-05-05',
                         @_EmployeeCode = '',
                         @_DeptCode = '',
                         @_BranchCode = 'A01,A02',
                         @_SendEmail = 1,
                         @_MailProfile = N''

-  Set cứng SendEmail và MailProfile mặc định bằng giá trị trên
-  Chỉ hiển thị và cho nhập điều kiện 4 biến đầu

=> Output trả về thông báo kết quả gửi thành công hay chưa

3.  Trung tâm báo cáo chỉ lấy 3 cái trong BFD:

-  Bảng chấm công
-  Bảng thanh toán lương theo chi nhánh phòng ban
-  Phiếu lương cá nhân

4.  Nút refresh chưa chạy

5.  Sửa định dạng trường input ngày thành DD/MM/YYYY

6.  Chưa lưu được bản ghi vào CSDL
7.  Thêm 1 nút Đình chỉ (Icon thùng rác) ở đây cho phép đình chỉ dữ liệu (UPDATE bảng

SET IsActive = 0)

8.  Thống nhất các phần input sẽ hiển thị như ở hợp đồng lao động như này + nút Đình

chỉ, cho action luôn visible

9.  Thêm nút Sửa khi mở các tab chi tiết để xem, cho phép sửa bảng mẹ và sửa, thêm

mới ở bảng con

10. Nhân viên bổ sung thêm các trường như Gender (Giới tính), BirthDate (Ngày sinh),
IdCardNo (Số CCCD), Email (Địa chỉ email), Mobile (Số điện thoại), ResignDate
(Ngày nghỉ việc)

11. Danh mục thang lương hiển thị theo thang lương (D20SalaryScale) -> bậc lương

(D20SalaryGrade) sẽ ở trong chi tiết của từng thang và từ bậc lương có thể mở tab
chi tiết thêm để xem hoặc cập nhật chi tiết các khoản thu nhập của bậc
(D20SalaryGradeDetail)

12. Nút sửa đang chưa link với bản ghi cần  sửa , khi view sửa thì không có dữ liệu

13.

