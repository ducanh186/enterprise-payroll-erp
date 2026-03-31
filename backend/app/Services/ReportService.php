<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\EmploymentStatus;
use App\Models\AttendanceDaily;
use App\Models\AttendancePeriod;
use App\Models\Employee;
use App\Models\LabourContract;
use App\Models\Payslip;
use App\Models\PayslipItem;
use App\Models\ReportTemplate;
use App\Models\Shift;
use App\Models\SystemConfig;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    public function getTemplates(): array
    {
        return ReportTemplate::query()
            ->active()
            ->orderBy('id')
            ->get()
            ->map(fn (ReportTemplate $template) => $this->formatTemplate($template))
            ->values()
            ->all();
    }

    public function previewReport(string $code, array $parameters = []): array
    {
        $normalizedCode = $this->normalizeReportCode($code);
        [$month, $year] = $this->resolveMonthYear($parameters);

        return match ($normalizedCode) {
            'RPT_ATTENDANCE_DAILY' => $this->previewAttendanceDaily($parameters, $normalizedCode),
            'RPT_ATTENDANCE_MONTHLY' => $this->previewAttendanceMonthly($month, $year, $parameters, $normalizedCode),
            'RPT_PAYROLL_SUMMARY' => $this->previewPayrollSummary($month, $year, $parameters, $normalizedCode),
            'RPT_PAYSLIP' => $this->previewPayslip($month, $year, $parameters, $normalizedCode),
            'RPT_INSURANCE' => $this->previewInsuranceReport($month, $year, $parameters, $normalizedCode),
            'RPT_PIT' => $this->previewPITReport($month, $year, $parameters, $normalizedCode),
            'EMPLOYEE_LIST' => $this->previewEmployeeList($parameters, $normalizedCode),
            'BANK_TRANSFER' => $this->previewBankTransfer($month, $year, $parameters, $normalizedCode),
            default => [
                'report_code' => $normalizedCode,
                'title' => 'Unknown Report',
                'generated_at' => now()->toISOString(),
                'data' => [],
            ],
        };
    }

    public function exportReport(string $code, array $parameters = []): array
    {
        $normalizedCode = $this->normalizeReportCode($code);
        $preview = $this->previewReport($normalizedCode, $parameters);
        $format = strtolower((string) ($parameters['format'] ?? 'xlsx'));
        $suffix = $this->buildExportSuffix($parameters);
        $fileName = sprintf('%s_%s.%s', $normalizedCode, $suffix, $format);

        return [
            'report_code' => $normalizedCode,
            'format' => $format,
            'file_name' => $fileName,
            'file_url' => '/storage/reports/' . $fileName,
            'file_size' => $this->estimateFileSize($preview),
            'generated_at' => now()->toISOString(),
            'expires_at' => now()->addHours(24)->toISOString(),
        ];
    }

    private function formatTemplate(ReportTemplate $template): array
    {
        $definition = $this->reportDefinition($template->code);

        return [
            'id' => $template->id,
            'code' => $template->code,
            'name' => $template->name,
            'description' => $definition['description'],
            'category' => $definition['category'] ?? $template->module,
            'parameters' => $definition['parameters'],
            'export_formats' => $definition['export_formats'],
        ];
    }

    private function reportDefinition(string $code): array
    {
        return $this->templateDefinitions()[$this->normalizeReportCode($code)] ?? [
            'description' => 'Report template from database.',
            'category' => 'general',
            'parameters' => [],
            'export_formats' => ['xlsx'],
        ];
    }

    private function templateDefinitions(): array
    {
        return [
            'RPT_ATTENDANCE_DAILY' => [
                'description' => 'Báo cáo chấm công hàng ngày.',
                'category' => 'attendance',
                'parameters' => [
                    ['name' => 'date', 'type' => 'date', 'required' => true],
                    ['name' => 'department_id', 'type' => 'integer', 'required' => false],
                    ['name' => 'employee_id', 'type' => 'integer', 'required' => false],
                ],
                'export_formats' => ['xlsx', 'pdf'],
            ],
            'RPT_ATTENDANCE_MONTHLY' => [
                'description' => 'Bảng chấm công chi tiết theo tháng.',
                'category' => 'attendance',
                'parameters' => [
                    ['name' => 'month', 'type' => 'integer', 'required' => true],
                    ['name' => 'year', 'type' => 'integer', 'required' => true],
                    ['name' => 'department_id', 'type' => 'integer', 'required' => false],
                    ['name' => 'employee_id', 'type' => 'integer', 'required' => false],
                ],
                'export_formats' => ['xlsx', 'pdf'],
            ],
            'RPT_PAYROLL_SUMMARY' => [
                'description' => 'Báo cáo tổng hợp chi phí lương theo tháng.',
                'category' => 'payroll',
                'parameters' => [
                    ['name' => 'month', 'type' => 'integer', 'required' => true],
                    ['name' => 'year', 'type' => 'integer', 'required' => true],
                    ['name' => 'department_id', 'type' => 'integer', 'required' => false],
                ],
                'export_formats' => ['xlsx', 'pdf'],
            ],
            'RPT_PAYSLIP' => [
                'description' => 'Phiếu lương cá nhân.',
                'category' => 'payroll',
                'parameters' => [
                    ['name' => 'month', 'type' => 'integer', 'required' => true],
                    ['name' => 'year', 'type' => 'integer', 'required' => true],
                    ['name' => 'employee_id', 'type' => 'integer', 'required' => false],
                    ['name' => 'payslip_id', 'type' => 'integer', 'required' => false],
                ],
                'export_formats' => ['xlsx', 'pdf'],
            ],
            'RPT_INSURANCE' => [
                'description' => 'Báo cáo đóng bảo hiểm xã hội, y tế, thất nghiệp.',
                'category' => 'payroll',
                'parameters' => [
                    ['name' => 'month', 'type' => 'integer', 'required' => true],
                    ['name' => 'year', 'type' => 'integer', 'required' => true],
                    ['name' => 'department_id', 'type' => 'integer', 'required' => false],
                ],
                'export_formats' => ['xlsx'],
            ],
            'RPT_PIT' => [
                'description' => 'Báo cáo thuế thu nhập cá nhân.',
                'category' => 'payroll',
                'parameters' => [
                    ['name' => 'month', 'type' => 'integer', 'required' => true],
                    ['name' => 'year', 'type' => 'integer', 'required' => true],
                    ['name' => 'department_id', 'type' => 'integer', 'required' => false],
                ],
                'export_formats' => ['xlsx', 'pdf'],
            ],
        ];
    }

    private function previewAttendanceDaily(array $parameters, string $reportCode): array
    {
        $date = $this->resolveDate($parameters);
        $departmentId = isset($parameters['department_id']) ? (int) $parameters['department_id'] : null;
        $employeeId = isset($parameters['employee_id']) ? (int) $parameters['employee_id'] : null;

        $query = AttendanceDaily::query()
            ->with(['employee.department', 'shiftAssignment.shift'])
            ->whereDate('work_date', $date->toDateString())
            ->orderBy('employee_id');

        if ($departmentId) {
            $query->whereHas('employee', fn ($builder) => $builder->where('department_id', $departmentId));
        }

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $records = $query->get();
        $activeEmployees = $this->filteredEmployees($departmentId, $employeeId);
        $standardDays = $this->standardWorkingDays();

        $presentCount = $records->filter(fn ($row) => $this->normalizeAttendanceStatus($row->attendance_status) === AttendanceStatus::PRESENT->value)->count();
        $partialCount = $records->filter(fn ($row) => $this->normalizeAttendanceStatus($row->attendance_status) === AttendanceStatus::PARTIAL->value)->count();
        $leaveCount = $records->filter(fn ($row) => $this->normalizeAttendanceStatus($row->attendance_status) === AttendanceStatus::LEAVE->value)->count();
        $holidayCount = $records->filter(fn ($row) => $this->normalizeAttendanceStatus($row->attendance_status) === AttendanceStatus::HOLIDAY->value)->count();
        $absentCount = $records->filter(fn ($row) => $this->normalizeAttendanceStatus($row->attendance_status) === AttendanceStatus::ABSENT->value)->count();
        $lateCount = $records->filter(fn ($row) => (int) ($row->late_minutes ?? 0) > 0)->count();
        $earlyLeaveCount = $records->filter(fn ($row) => (int) ($row->early_minutes ?? 0) > 0)->count();
        $totalOvertimeHours = round($records->sum(fn ($row) => (float) ($row->ot_hours ?? 0)), 1);

        $byDepartment = $records
            ->groupBy(fn ($row) => $row->employee?->department?->name ?? 'Unknown')
            ->map(function (Collection $items, string $department) {
                return [
                    'department' => $department,
                    'employee_count' => $items->pluck('employee_id')->unique()->count(),
                    'present_count' => $items->filter(fn ($row) => $this->normalizeAttendanceStatus($row->attendance_status) === AttendanceStatus::PRESENT->value)->count(),
                    'late_instances' => $items->filter(fn ($row) => (int) ($row->late_minutes ?? 0) > 0)->count(),
                    'total_overtime_hours' => round($items->sum(fn ($row) => (float) ($row->ot_hours ?? 0)), 1),
                ];
            })
            ->values()
            ->all();

        return [
            'report_code' => $reportCode,
            'title' => 'Bao cao cham cong ngay ' . $date->format('d/m/Y'),
            'generated_at' => now()->toISOString(),
            'summary' => [
                'date' => $date->toDateString(),
                'standard_working_days' => $standardDays,
                'total_employees' => $activeEmployees,
                'record_count' => $records->count(),
                'present_count' => $presentCount,
                'partial_count' => $partialCount,
                'leave_count' => $leaveCount,
                'holiday_count' => $holidayCount,
                'absent_count' => $absentCount,
                'average_attendance_rate' => $activeEmployees > 0
                    ? round((($presentCount + $partialCount) / $activeEmployees) * 100, 1)
                    : 0.0,
                'late_instances' => $lateCount,
                'early_leave_instances' => $earlyLeaveCount,
                'total_overtime_hours' => $totalOvertimeHours,
            ],
            'by_department' => $byDepartment,
            'records' => $records->map(fn (AttendanceDaily $record) => $this->formatDailyReportRecord($record))->values()->all(),
        ];
    }

    private function previewAttendanceMonthly(int $month, int $year, array $parameters, string $reportCode): array
    {
        [$period, $fromDate, $toDate] = $this->resolveAttendancePeriod($month, $year);
        $departmentId = isset($parameters['department_id']) ? (int) $parameters['department_id'] : null;
        $employeeId = isset($parameters['employee_id']) ? (int) $parameters['employee_id'] : null;

        $query = AttendanceDaily::query()
            ->with(['employee.department'])
            ->whereBetween('work_date', [$fromDate, $toDate]);

        if ($period) {
            $query->where('attendance_period_id', $period->id);
        }

        if ($departmentId) {
            $query->whereHas('employee', fn ($builder) => $builder->where('department_id', $departmentId));
        }

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $rows = $query->get();
        $employees = $this->filteredEmployees($departmentId, $employeeId);
        $standardDays = $this->standardWorkingDays();
        $employeeCount = max(1, $employees);
        $actualWorkingDays = round($rows->sum(fn ($row) => (float) ($row->workday_value ?? 0)), 1);
        $lateInstances = $rows->filter(fn ($row) => (int) ($row->late_minutes ?? 0) > 0)->count();
        $earlyLeaveInstances = $rows->filter(fn ($row) => (int) ($row->early_minutes ?? 0) > 0)->count();
        $absentDays = $rows->filter(fn ($row) => $this->normalizeAttendanceStatus($row->attendance_status) === AttendanceStatus::ABSENT->value)->count();
        $leaveDays = $rows->filter(fn ($row) => $this->normalizeAttendanceStatus($row->attendance_status) === AttendanceStatus::LEAVE->value)->count();
        $totalOvertimeHours = round($rows->sum(fn ($row) => (float) ($row->ot_hours ?? 0)), 1);

        return [
            'report_code' => $reportCode,
            'title' => 'Bao cao cham cong thang ' . $month . '/' . $year,
            'generated_at' => now()->toISOString(),
            'summary' => [
                'standard_working_days' => $standardDays,
                'total_employees' => $employees,
                'actual_working_days' => $actualWorkingDays,
                'average_attendance_rate' => round(($actualWorkingDays / ($employeeCount * $standardDays)) * 100, 1),
                'total_late_instances' => $lateInstances,
                'total_early_leave_instances' => $earlyLeaveInstances,
                'total_absent_days' => $absentDays,
                'total_leave_days' => $leaveDays,
                'total_overtime_hours' => $totalOvertimeHours,
            ],
        ];
    }

    private function previewPayrollSummary(int $month, int $year, array $parameters, string $reportCode): array
    {
        [$period, $fromDate, $toDate] = $this->resolveAttendancePeriod($month, $year);
        $departmentId = isset($parameters['department_id']) ? (int) $parameters['department_id'] : null;

        $payslipQuery = Payslip::query()
            ->with(['employee.department', 'attendancePeriod'])
            ->whereHas('attendancePeriod', fn ($builder) => $builder->where('month', $month)->where('year', $year));

        if ($period) {
            $payslipQuery->where('attendance_period_id', $period->id);
        }

        if ($departmentId) {
            $payslipQuery->whereHas('employee', fn ($builder) => $builder->where('department_id', $departmentId));
        }

        if (!empty($parameters['employee_id'])) {
            $payslipQuery->where('employee_id', (int) $parameters['employee_id']);
        }

        $payslips = $payslipQuery->get();
        $standardDays = $this->standardWorkingDays();
        $standardHours = (float) SystemConfig::getValue('standard_work_hours_day', '8');
        $employees = $payslips->pluck('employee_id')->unique()->count();
        $totalBaseSalary = round($payslips->sum(fn ($payslip) => (float) ($payslip->base_salary_snapshot ?? 0)), 2);
        $totalGrossSalary = round($payslips->sum(fn ($payslip) => (float) ($payslip->gross_salary ?? 0)), 2);
        $totalAllowances = round(max(0, $totalGrossSalary - $totalBaseSalary), 2);

        $attendanceRows = AttendanceDaily::query()
            ->whereBetween('work_date', [$fromDate, $toDate]);

        if ($period) {
            $attendanceRows->where('attendance_period_id', $period->id);
        }

        if ($departmentId) {
            $attendanceRows->whereHas('employee', fn ($builder) => $builder->where('department_id', $departmentId));
        }

        if (!empty($parameters['employee_id'])) {
            $attendanceRows->where('employee_id', (int) $parameters['employee_id']);
        }

        $overtimeHours = round($attendanceRows->sum('ot_hours'), 1);
        $averageHourlyRate = $employees > 0
            ? $totalBaseSalary / max(1, $employees * $standardDays * $standardHours)
            : 0.0;
        $overtimeRate = (float) SystemConfig::getValue('overtime_rate_weekday', '1.5');
        $totalOvertime = round($overtimeHours * $averageHourlyRate * $overtimeRate, 2);

        $totalGrossIncome = round($totalGrossSalary + $totalOvertime, 2);
        $totalInsuranceEmployee = round($payslips->sum(fn ($payslip) => (float) ($payslip->insurance_employee ?? 0)), 2);
        $totalInsuranceEmployer = round($payslips->sum(fn ($payslip) => (float) ($payslip->insurance_company ?? 0)), 2);
        $totalPit = round($payslips->sum(fn ($payslip) => (float) ($payslip->pit_amount ?? 0)), 2);
        $totalNetSalary = round($payslips->sum(fn ($payslip) => (float) ($payslip->net_salary ?? 0)), 2);
        $totalCompanyCost = round($totalGrossIncome + $totalInsuranceEmployer, 2);

        $byDepartment = $payslips
            ->groupBy(fn ($payslip) => $payslip->employee?->department?->name ?? 'Unknown')
            ->map(function (Collection $items, string $department) {
                return [
                    'department' => $department,
                    'employee_count' => $items->pluck('employee_id')->unique()->count(),
                    'total_gross' => round($items->sum(fn ($payslip) => (float) ($payslip->gross_salary ?? 0)), 2),
                    'total_net' => round($items->sum(fn ($payslip) => (float) ($payslip->net_salary ?? 0)), 2),
                ];
            })
            ->sortByDesc('total_gross')
            ->values()
            ->all();

        return [
            'report_code' => $reportCode,
            'title' => 'Bao cao tong hop luong thang ' . $month . '/' . $year,
            'generated_at' => now()->toISOString(),
            'summary' => [
                'total_employees' => $employees,
                'total_gross_salary' => $totalGrossSalary,
                'total_allowances' => $totalAllowances,
                'total_overtime' => $totalOvertime,
                'total_gross_income' => $totalGrossIncome,
                'total_insurance_employee' => $totalInsuranceEmployee,
                'total_insurance_employer' => $totalInsuranceEmployer,
                'total_pit' => $totalPit,
                'total_net_salary' => $totalNetSalary,
                'total_company_cost' => $totalCompanyCost,
            ],
            'by_department' => $byDepartment,
        ];
    }

    private function previewPayslip(int $month, int $year, array $parameters, string $reportCode): array
    {
        [$period] = $this->resolveAttendancePeriod($month, $year);

        $query = Payslip::query()
            ->with(['employee.department', 'attendancePeriod', 'items'])
            ->whereHas('attendancePeriod', fn ($builder) => $builder->where('month', $month)->where('year', $year));

        if ($period) {
            $query->where('attendance_period_id', $period->id);
        }

        if (!empty($parameters['payslip_id'])) {
            $query->where('id', (int) $parameters['payslip_id']);
        } elseif (!empty($parameters['employee_id'])) {
            $query->where('employee_id', (int) $parameters['employee_id']);
        }

        $payslip = $query->orderBy('employee_id')->orderBy('id')->first()
            ?? Payslip::query()->with(['employee.department', 'attendancePeriod', 'items'])->orderByDesc('id')->first();

        if (!$payslip) {
            return [
                'report_code' => $reportCode,
                'title' => 'Phieu luong thang ' . $month . '/' . $year,
                'generated_at' => now()->toISOString(),
                'summary' => [],
                'items' => [],
            ];
        }

        $payslip->loadMissing(['employee.department', 'attendancePeriod', 'items']);
        $items = $payslip->items->sortBy('sort_order')->values()->map(fn (PayslipItem $item) => $this->formatPayslipItem($item))->all();

        return [
            'report_code' => $reportCode,
            'title' => 'Phieu luong ' . ($payslip->employee?->full_name ?? 'Unknown') . ' thang ' . ($payslip->attendancePeriod?->month ?? $month) . '/' . ($payslip->attendancePeriod?->year ?? $year),
            'generated_at' => now()->toISOString(),
            'summary' => [
                'employee_id' => $payslip->employee_id,
                'employee_code' => $payslip->employee?->employee_code,
                'employee_name' => $payslip->employee?->full_name,
                'department_name' => $payslip->employee?->department?->name,
                'bank_name' => $payslip->employee?->bank_name,
                'base_salary_snapshot' => (float) $payslip->base_salary_snapshot,
                'gross_salary' => (float) $payslip->gross_salary,
                'taxable_income' => (float) $payslip->taxable_income,
                'insurance_employee' => (float) $payslip->insurance_employee,
                'insurance_company' => (float) $payslip->insurance_company,
                'pit_amount' => (float) $payslip->pit_amount,
                'bonus_total' => (float) $payslip->bonus_total,
                'deduction_total' => (float) $payslip->deduction_total,
                'net_salary' => (float) $payslip->net_salary,
                'status' => (string) $payslip->status,
            ],
            'items' => $items,
        ];
    }

    private function previewInsuranceReport(int $month, int $year, array $parameters, string $reportCode): array
    {
        [$period] = $this->resolveAttendancePeriod($month, $year);
        $departmentId = isset($parameters['department_id']) ? (int) $parameters['department_id'] : null;

        $payslipQuery = Payslip::query()
            ->with(['employee.department'])
            ->whereHas('attendancePeriod', fn ($builder) => $builder->where('month', $month)->where('year', $year));

        if ($period) {
            $payslipQuery->where('attendance_period_id', $period->id);
        }

        if ($departmentId) {
            $payslipQuery->whereHas('employee', fn ($builder) => $builder->where('department_id', $departmentId));
        }

        $payslips = $payslipQuery->get();
        $insuranceBase = round($payslips->sum(fn ($payslip) => (float) ($payslip->insurance_base ?? 0)), 2);
        $employeeContribution = round($payslips->sum(fn ($payslip) => (float) ($payslip->insurance_employee ?? 0)), 2);
        $employerContribution = round($payslips->sum(fn ($payslip) => (float) ($payslip->insurance_company ?? 0)), 2);

        return [
            'report_code' => $reportCode,
            'title' => 'Bao cao bao hiem thang ' . $month . '/' . $year,
            'generated_at' => now()->toISOString(),
            'summary' => [
                'total_employees' => $payslips->pluck('employee_id')->unique()->count(),
                'total_insurance_salary' => $insuranceBase,
                'bhxh_employee' => round($insuranceBase * 0.08, 2),
                'bhyt_employee' => round($insuranceBase * 0.015, 2),
                'bhtn_employee' => round($insuranceBase * 0.01, 2),
                'bhxh_employer' => round($insuranceBase * 0.175, 2),
                'bhyt_employer' => round($insuranceBase * 0.03, 2),
                'bhtn_employer' => round($insuranceBase * 0.01, 2),
                'total_employee_contribution' => $employeeContribution,
                'total_employer_contribution' => $employerContribution,
                'grand_total' => round($employeeContribution + $employerContribution, 2),
            ],
        ];
    }

    private function previewPITReport(int $month, int $year, array $parameters, string $reportCode): array
    {
        [$period] = $this->resolveAttendancePeriod($month, $year);
        $departmentId = isset($parameters['department_id']) ? (int) $parameters['department_id'] : null;

        $payslipQuery = Payslip::query()
            ->with(['employee.department'])
            ->whereHas('attendancePeriod', fn ($builder) => $builder->where('month', $month)->where('year', $year));

        if ($period) {
            $payslipQuery->where('attendance_period_id', $period->id);
        }

        if ($departmentId) {
            $payslipQuery->whereHas('employee', fn ($builder) => $builder->where('department_id', $departmentId));
        }

        $payslips = $payslipQuery->get();
        $taxableIncome = round($payslips->sum(fn ($payslip) => (float) ($payslip->taxable_income ?? 0)), 2);
        $pitAmount = round($payslips->sum(fn ($payslip) => (float) ($payslip->pit_amount ?? 0)), 2);
        $employeesWithTax = $payslips->filter(fn ($payslip) => (float) ($payslip->pit_amount ?? 0) > 0)->pluck('employee_id')->unique()->count();
        $totalEmployees = $payslips->pluck('employee_id')->unique()->count();

        return [
            'report_code' => $reportCode,
            'title' => 'Bao cao thue TNCN thang ' . $month . '/' . $year,
            'generated_at' => now()->toISOString(),
            'summary' => [
                'total_employees' => $totalEmployees,
                'employees_with_tax' => $employeesWithTax,
                'employees_no_tax' => max(0, $totalEmployees - $employeesWithTax),
                'total_taxable_income' => $taxableIncome,
                'total_pit' => $pitAmount,
                'average_tax_rate' => $taxableIncome > 0 ? round(($pitAmount / $taxableIncome) * 100, 1) : 0.0,
            ],
        ];
    }

    private function previewEmployeeList(array $parameters, string $reportCode): array
    {
        $departmentId = isset($parameters['department_id']) ? (int) $parameters['department_id'] : null;
        $activeStatus = strtolower((string) ($parameters['active_status'] ?? 'all'));

        $query = Employee::query()->with(['department', 'position'])->orderBy('employee_code');

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        if ($activeStatus === 'active') {
            $query->where('employment_status', EmploymentStatus::ACTIVE);
        } elseif ($activeStatus === 'inactive') {
            $query->whereIn('employment_status', [EmploymentStatus::INACTIVE, EmploymentStatus::TERMINATED]);
        } elseif ($activeStatus === 'terminated') {
            $query->where('employment_status', EmploymentStatus::TERMINATED);
        }

        $employees = $query->get();
        $employeeIds = $employees->pluck('id')->all();

        $totalActive = $employees->filter(fn (Employee $employee) => $employee->employment_status === EmploymentStatus::ACTIVE)->count();
        $totalInactive = $employees->filter(fn (Employee $employee) => $employee->employment_status !== EmploymentStatus::ACTIVE)->count();
        $totalProbation = LabourContract::query()
            ->active()
            ->where('probation_rate', '<', 100)
            ->whereIn('employee_id', $employeeIds)
            ->distinct('employee_id')
            ->count('employee_id');

        $genderCounts = ['male' => 0, 'female' => 0, 'other' => 0];
        foreach ($employees as $employee) {
            $genderCounts[$this->normalizeGender($employee->gender)]++;
        }

        return [
            'report_code' => $reportCode,
            'title' => 'Danh sach nhan vien',
            'generated_at' => now()->toISOString(),
            'summary' => [
                'total_active' => $totalActive,
                'total_inactive' => $totalInactive,
                'total_probation' => $totalProbation,
                'by_gender' => $genderCounts,
            ],
        ];
    }

    private function previewBankTransfer(int $month, int $year, array $parameters, string $reportCode): array
    {
        [$period] = $this->resolveAttendancePeriod($month, $year);
        $departmentId = isset($parameters['department_id']) ? (int) $parameters['department_id'] : null;

        $query = Payslip::query()
            ->with(['employee.department'])
            ->whereHas('attendancePeriod', fn ($builder) => $builder->where('month', $month)->where('year', $year));

        if ($period) {
            $query->where('attendance_period_id', $period->id);
        }

        if ($departmentId) {
            $query->whereHas('employee', fn ($builder) => $builder->where('department_id', $departmentId));
        }

        $payslips = $query->get();
        $bankName = $this->mostCommonBankName($payslips);
        $paymentDay = max(1, (int) SystemConfig::getValue('payroll_payment_day', '5'));
        $transferDate = Carbon::create($year, $month, 1)->day(min($paymentDay, Carbon::create($year, $month, 1)->daysInMonth))->toDateString();

        return [
            'report_code' => $reportCode,
            'title' => 'Bang ke chuyen khoan luong thang ' . $month . '/' . $year,
            'generated_at' => now()->toISOString(),
            'summary' => [
                'total_employees' => $payslips->pluck('employee_id')->unique()->count(),
                'total_amount' => round($payslips->sum(fn ($payslip) => (float) ($payslip->net_salary ?? 0)), 2),
                'bank_name' => $bankName,
                'transfer_date' => $transferDate,
            ],
        ];
    }

    private function formatDailyReportRecord(AttendanceDaily $record): array
    {
        $employee = $record->employee;
        $shift = $record->shiftAssignment?->shift ?? $this->defaultShift();

        return [
            'employee_id' => $record->employee_id,
            'employee_code' => $employee?->employee_code,
            'employee_name' => $employee?->full_name ?? 'Unknown',
            'department_name' => $employee?->department?->name,
            'date' => $record->work_date?->format('Y-m-d'),
            'shift_code' => $shift?->code,
            'shift_name' => $shift?->name,
            'check_in' => $record->first_in?->format('Y-m-d H:i:s'),
            'check_out' => $record->last_out?->format('Y-m-d H:i:s'),
            'working_hours' => (float) $record->regular_hours,
            'overtime_hours' => (float) $record->ot_hours,
            'late_minutes' => (int) $record->late_minutes,
            'early_leave_minutes' => (int) $record->early_minutes,
            'status' => $this->normalizeAttendanceStatus($record->attendance_status),
            'note' => $record->source_status,
        ];
    }

    private function formatPayslipItem(PayslipItem $item): array
    {
        return [
            'item_code' => $item->item_code,
            'item_name' => $item->item_name,
            'item_group' => $item->item_group,
            'qty' => $item->qty !== null ? (float) $item->qty : null,
            'rate' => $item->rate !== null ? (float) $item->rate : null,
            'amount' => (float) $item->amount,
            'sort_order' => (int) $item->sort_order,
            'source_ref' => $item->source_ref,
        ];
    }

    private function normalizeReportCode(string $code): string
    {
        $code = strtoupper(trim($code));

        return match ($code) {
            'PAYROLL_SUMMARY' => 'RPT_PAYROLL_SUMMARY',
            'ATTENDANCE_MONTHLY' => 'RPT_ATTENDANCE_MONTHLY',
            'ATTENDANCE_DAILY' => 'RPT_ATTENDANCE_DAILY',
            'PAYSLIP' => 'RPT_PAYSLIP',
            'INSURANCE_REPORT' => 'RPT_INSURANCE',
            'PIT_REPORT' => 'RPT_PIT',
            default => $code,
        };
    }

    private function resolveMonthYear(array $parameters): array
    {
        if (!empty($parameters['date'])) {
            $date = $this->safeParseDate((string) $parameters['date']);

            if ($date) {
                return [(int) $date->month, (int) $date->year];
            }
        }

        $month = isset($parameters['month']) ? (int) $parameters['month'] : null;
        $year = isset($parameters['year']) ? (int) $parameters['year'] : null;

        if (!$month || !$year) {
            $latestPeriod = AttendancePeriod::query()->orderByDesc('year')->orderByDesc('month')->first();

            if ($latestPeriod) {
                return [(int) $latestPeriod->month, (int) $latestPeriod->year];
            }

            $now = now();

            return [(int) $now->month, (int) $now->year];
        }

        return [$month, $year];
    }

    private function resolveDate(array $parameters): Carbon
    {
        if (!empty($parameters['date'])) {
            $date = $this->safeParseDate((string) $parameters['date']);

            if ($date) {
                return $date;
            }
        }

        $latestDate = AttendanceDaily::query()->max('work_date');

        return $latestDate ? Carbon::parse($latestDate) : now();
    }

    private function safeParseDate(string $value): ?Carbon
    {
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveAttendancePeriod(int $month, int $year): array
    {
        $period = AttendancePeriod::query()
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        $fromDate = $period?->from_date?->toDateString() ?? Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $toDate = $period?->to_date?->toDateString() ?? Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        return [$period, $fromDate, $toDate];
    }

    private function standardWorkingDays(): int
    {
        return max(1, (int) SystemConfig::getValue('standard_work_days_month', '22'));
    }

    private function filteredEmployees(?int $departmentId = null, ?int $employeeId = null): int
    {
        $query = Employee::query()->active();

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        if ($employeeId) {
            $query->where('id', $employeeId);
        }

        return $query->count();
    }

    private function defaultShift(): ?Shift
    {
        return Shift::query()->active()->orderBy('id')->first();
    }

    private function normalizeAttendanceStatus(mixed $status): string
    {
        if ($status instanceof AttendanceStatus) {
            return $status->value;
        }

        return (string) $status;
    }

    private function normalizeGender(?string $gender): string
    {
        $gender = strtolower(trim((string) $gender));

        return match ($gender) {
            'm', 'male', 'nam' => 'male',
            'f', 'female', 'nu', 'nữ' => 'female',
            default => 'other',
        };
    }

    private function mostCommonBankName(Collection $payslips): ?string
    {
        $banks = $payslips
            ->map(fn (Payslip $payslip) => trim((string) ($payslip->employee?->bank_name ?? '')))
            ->filter()
            ->countBy();

        if ($banks->isEmpty()) {
            return null;
        }

        return (string) $banks->sortDesc()->keys()->first();
    }

    private function buildExportSuffix(array $parameters): string
    {
        if (!empty($parameters['date'])) {
            $date = $this->safeParseDate((string) $parameters['date']);

            if ($date) {
                return $date->format('Y-m-d');
            }
        }

        [$month, $year] = $this->resolveMonthYear($parameters);

        return sprintf('%02d_%04d', $month, $year);
    }

    private function estimateFileSize(array $payload): int
    {
        return max(1024, strlen(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) * 3);
    }
}
