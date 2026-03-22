# CLAUDE.md

UML design diagrams - source of truth for business requirements.

## Diagrams

| File | Type | Content |
|------|------|---------|
| `Class_diagram.png` | Class Diagram | All domain entities: User, Employee, LabourContract, Shift, CheckInOut, Attendance, Payslip, PayrollParameters, BonusDeduction, Dependent, Holiday, LateEarlyRegulation, SalaryLevel |
| `Usecase_diagram.png` | Use Case Diagram | 4 actors (Nhan su/HR, Ke toan/Accountant, Quan tri vien/SysAdmin, Ban quan ly/Management) and ~20 use cases |
| `bussines_flow.jpg` | Activity Diagram | Attendance business flow: Shift assignment -> Check-in/out -> Import logs -> Generate summary -> Employee confirmation -> Save |
| `sequence_diagram.jpg` | Sequence Diagram | Payroll calculation flow: UI -> Controller -> DB (get SP + params) -> execute -> return result |
| `Manader.jpg` | Module Tree | 5 top-level modules: Master data, Contracts, Attendance, Payroll, Reports |

## Mapping to Code

- Class Diagram entities -> `backend/app/Models/*.php`
- Use Case actors -> `backend/app/Enums/UserRole.php` (5 roles)
- Use Cases -> `backend/routes/api.php` (API endpoints)
- Business Flow -> `backend/app/Services/AttendanceService.php`
- Sequence Diagram -> `backend/app/Services/PayrollService.php`
- Module Tree -> Controller groups in `backend/app/Http/Controllers/Api/`
