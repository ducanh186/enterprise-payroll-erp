<?php

namespace App\Services;

use App\Enums\AttendanceRequestStatus;
use App\Models\AttendanceDaily;
use App\Models\AttendanceMonthlySummary;
use App\Models\AttendancePeriod;
use App\Models\AttendanceRequest;
use App\Models\AttendanceRequestDetail;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\SystemConfig;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AttendanceService
{
    public function importCheckinLogsFromExcel(string $path, ?int $userId = null): array
    {
        $rows = app(ExcelWorkbookService::class)->readFirstSheet($path);

        if (count($rows) < 2) {
            return [
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['File không có dòng dữ liệu để import.'],
            ];
        }

        $headers = array_map(fn ($value) => $this->normalizeHeader((string) $value), $rows[0]);
        $timeIndex = $this->findHeaderIndex($headers, ['thoigian', 'checktime', 'time']);
        $employeeCodeIndex = $this->findHeaderIndex($headers, ['manv', 'employee', 'employeecode', 'code']);

        if ($timeIndex === null || $employeeCodeIndex === null) {
            return [
                'imported' => 0,
                'skipped' => count($rows) - 1,
                'errors' => ['File cần có cột Thời gian và Mã NV.'],
            ];
        }

        $employeeIdsByCode = Employee::query()
            ->pluck('id', 'employee_code')
            ->mapWithKeys(fn ($id, $code) => [Str::upper((string) $code) => (int) $id])
            ->all();

        $imported = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($rows, $timeIndex, $employeeCodeIndex, $employeeIdsByCode, $path, &$imported, &$skipped, &$errors) {
            foreach (array_slice($rows, 1) as $offset => $row) {
                $rowNumber = $offset + 2;
                $employeeCode = Str::upper(trim((string) ($row[$employeeCodeIndex] ?? '')));
                $rawTime = $row[$timeIndex] ?? null;

                if ($employeeCode === '' || $rawTime === null || $rawTime === '') {
                    $skipped++;
                    continue;
                }

                $employeeId = $employeeIdsByCode[$employeeCode] ?? $this->syncCustomerEmployee($employeeCode, $employeeIdsByCode);
                if (!$employeeId) {
                    $skipped++;
                    if (count($errors) < 10) {
                        $errors[] = "Dòng {$rowNumber}: Không tìm thấy nhân viên {$employeeCode}.";
                    }
                    continue;
                }

                try {
                    $logTime = $this->parseExcelDateTime($rawTime);
                } catch (\Throwable) {
                    $skipped++;
                    if (count($errors) < 10) {
                        $errors[] = "Dòng {$rowNumber}: Thời gian không hợp lệ.";
                    }
                    continue;
                }

                $logType = (int) $logTime->format('H') < 12 ? 'check_in' : 'check_out';
                $rawRef = 'excel-' . sha1(basename($path) . '|' . $rowNumber . '|' . $employeeCode . '|' . $logTime->toISOString());

                $log = TimeLog::query()->firstOrCreate(
                    ['raw_ref' => $rawRef],
                    [
                        'employee_id' => $employeeId,
                        'log_time' => $logTime,
                        'machine_number' => 'excel',
                        'log_type' => $logType,
                        'source' => 'excel',
                        'is_valid' => true,
                        'invalid_reason' => null,
                        'created_at' => now(),
                    ]
                );

                if ($log->wasRecentlyCreated) {
                    $imported++;
                } else {
                    $skipped++;
                }
            }
        });

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'file_name' => basename($path),
            'imported_by' => $userId,
        ];
    }

    public function getCheckinLogs(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        if (!$dateFrom || !$dateTo) {
            $latestLogTime = TimeLog::query()->max('log_time');
            $anchor = $latestLogTime ? Carbon::parse($latestLogTime) : Carbon::now();

            $dateFrom ??= $anchor->copy()->startOfMonth()->toDateString();
            $dateTo ??= $anchor->copy()->endOfDay()->toDateString();
        }

        $query = TimeLog::query()
            ->with(['employee.department'])
            ->whereBetween('log_time', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ])
            ->orderBy('log_time')
            ->orderBy('id');

        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', (int) $filters['employee_id']);
        }

        if (!empty($filters['machine_number'])) {
            $query->where('machine_number', $filters['machine_number']);
        }

        if (array_key_exists('is_valid', $filters) && $filters['is_valid'] !== null && $filters['is_valid'] !== '') {
            $query->where('is_valid', filter_var($filters['is_valid'], FILTER_VALIDATE_BOOLEAN));
        }

        $logs = $query->get()->map(fn (TimeLog $log) => $this->formatTimeLog($log))->values()->all();

        return $this->paginateArray($logs, $filters);
    }

    public function createManualCheckin(array $data): array
    {
        $employee = Employee::query()->with('department')->findOrFail((int) $data['employee_id']);
        $logTime = Carbon::parse($data['check_time']);
        $logType = $data['check_type'] === 'in' ? 'check_in' : 'check_out';

        $log = DB::transaction(function () use ($employee, $logTime, $logType, $data) {
            return TimeLog::create([
                'employee_id' => $employee->id,
                'log_time' => $logTime,
                'machine_number' => $data['machine_number'] ?? null,
                'log_type' => $logType,
                'source' => 'manual',
                'is_valid' => true,
                'invalid_reason' => null,
                'raw_ref' => 'manual-' . Str::uuid()->toString(),
                'created_at' => now(),
            ]);
        });

        $log->load('employee.department');

        return array_merge($this->formatTimeLog($log), [
            'reason' => $data['reason'] ?? null,
            'created_by' => $this->currentActorName(),
        ]);
    }

    public function getDailyAttendance(array $filters = []): array
    {
        $date = $filters['date'] ?? null;

        if (!$date) {
            $latestDate = AttendanceDaily::query()->max('work_date');
            $date = $latestDate ? Carbon::parse($latestDate)->toDateString() : Carbon::today()->toDateString();
        }

        $workDate = Carbon::parse($date)->toDateString();
        $records = AttendanceDaily::query()
            ->with(['employee.department', 'shiftAssignment.shift'])
            ->whereDate('work_date', $workDate)
            ->get()
            ->keyBy('employee_id');

        $employees = Employee::query()
            ->with(['department', 'position'])
            ->where('employment_status', 'active')
            ->orderBy('employee_code')
            ->get();

        $rows = [];

        foreach ($employees as $employee) {
            if (!empty($filters['department_id']) && (int) $employee->department_id !== (int) $filters['department_id']) {
                continue;
            }

            $record = $records->get($employee->id);
            $shift = $record?->shiftAssignment?->shift
                ?? $this->resolveShiftForEmployeeOnDate($employee->id, $workDate);

            $rows[] = $record
                ? $this->formatDailyRecord($record, $employee, $shift)
                : $this->formatAbsentDailyRecord($employee, $workDate, $shift);
        }

        return $rows;
    }

    public function getMonthlySummary(array $filters = []): array
    {
        [$period, $fromDate, $toDate] = $this->resolveAttendancePeriod($filters);
        $standardDays = $this->standardWorkingDays();

        $dailyRows = AttendanceDaily::query()
            ->with(['employee.department'])
            ->whereBetween('work_date', [$fromDate, $toDate])
            ->get()
            ->groupBy('employee_id');

        $summaryRows = AttendanceMonthlySummary::query()
            ->with(['employee.department', 'attendancePeriod'])
            ->where('attendance_period_id', $period?->id)
            ->get()
            ->keyBy('employee_id');

        $employees = Employee::query()
            ->with('department')
            ->where('employment_status', 'active')
            ->orderBy('employee_code')
            ->get();

        $rows = [];

        foreach ($employees as $employee) {
            if (!empty($filters['department_id']) && (int) $employee->department_id !== (int) $filters['department_id']) {
                continue;
            }

            if (!empty($filters['employee_id']) && (int) $employee->id !== (int) $filters['employee_id']) {
                continue;
            }

            $summary = $period ? $summaryRows->get($employee->id) : null;
            $employeeDaily = $dailyRows->get($employee->id, collect());

            $rows[] = $summary
                ? $this->formatMonthlySummaryRecord($summary, $employee, $standardDays, $employeeDaily)
                : $this->buildMonthlySummaryFromDaily($employee, $period, $standardDays, $employeeDaily);
        }

        return $rows;
    }

    public function recalculate(array $data): array
    {
        $procedureResult = $this->tryCustomerAttendanceProcedure($data);
        if ($procedureResult['available'] && !$procedureResult['error']) {
            return [
                'message' => 'Tính và tổng hợp công hoàn tất từ stored procedure.',
                'execution_mode' => 'stored_procedure',
                'procedure' => $procedureResult['procedure'],
                'row_count' => $procedureResult['row_count'],
                'execution_ms' => $procedureResult['execution_ms'],
                'result_sets' => count($procedureResult['result_sets']),
            ];
        }

        [$period, $fromDate, $toDate] = $this->resolveAttendancePeriod($data, true);
        $now = now();

        if ($period && $period->status?->value === 'locked') {
            return [
                'message' => 'Attendance period is locked.',
                'month' => (int) $data['month'],
                'year' => (int) $data['year'],
                'employees_processed' => 0,
                'records_updated' => 0,
                'started_at' => $now->toISOString(),
                'completed_at' => $now->toISOString(),
            ];
        }

        $period ??= AttendancePeriod::create([
            'period_code' => sprintf('%04d-%02d', (int) $data['year'], (int) $data['month']),
            'month' => (int) $data['month'],
            'year' => (int) $data['year'],
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'status' => 'draft',
        ]);

        $employees = Employee::query()
            ->where('employment_status', 'active')
            ->get();

        $dailyRows = AttendanceDaily::query()
            ->where('attendance_period_id', $period->id)
            ->get()
            ->groupBy('employee_id');

        $recordsUpdated = 0;

        DB::transaction(function () use ($employees, $dailyRows, $period, $now, &$recordsUpdated) {
            foreach ($employees as $employee) {
                $employeeDaily = $dailyRows->get($employee->id, collect());
                $metrics = $this->calculateMonthlyMetrics($employeeDaily, $this->standardWorkingDays());
                $existing = AttendanceMonthlySummary::query()
                    ->where('attendance_period_id', $period->id)
                    ->where('employee_id', $employee->id)
                    ->first();

                $status = $existing && in_array($existing->status, ['confirmed', 'locked'], true)
                    ? $existing->status
                    : 'generated';

                AttendanceMonthlySummary::updateOrCreate(
                    [
                        'attendance_period_id' => $period->id,
                        'employee_id' => $employee->id,
                    ],
                    [
                        'total_workdays' => $metrics['total_workdays'],
                        'regular_hours' => $metrics['regular_hours'],
                        'ot_hours' => $metrics['ot_hours'],
                        'night_hours' => $metrics['night_hours'],
                        'paid_leave_days' => $metrics['paid_leave_days'],
                        'unpaid_leave_days' => $metrics['unpaid_leave_days'],
                        'late_minutes' => $metrics['late_minutes'],
                        'early_minutes' => $metrics['early_minutes'],
                        'meal_count' => $metrics['meal_count'],
                        'status' => $status,
                        'generated_at' => $now,
                        'confirmed_at' => $existing?->confirmed_at,
                    ]
                );

                $recordsUpdated++;
            }
        });

        return [
            'message' => 'Attendance recalculation completed.',
            'month' => (int) $data['month'],
            'year' => (int) $data['year'],
            'employees_processed' => $employees->count(),
            'records_updated' => $recordsUpdated,
            'execution_mode' => 'laravel_fallback',
            'procedure_warning' => $procedureResult['error'],
            'started_at' => $now->copy()->subSeconds(2)->toISOString(),
            'completed_at' => $now->toISOString(),
        ];
    }

    public function getShiftAssignments(): array
    {
        return ShiftAssignment::query()
            ->with(['employee', 'shift'])
            ->orderBy('id')
            ->get()
            ->map(fn (ShiftAssignment $assignment) => [
                'id' => $assignment->id,
                'employee_id' => $assignment->employee_id,
                'employee_name' => data_get($assignment, 'employee.full_name'),
                'shift_id' => $assignment->shift_id,
                'shift_name' => data_get($assignment, 'shift.name'),
                'work_date' => $assignment->work_date?->format('Y-m-d'),
                'source' => $assignment->source,
                'note' => $assignment->note,
            ])
            ->all();
    }

    public function getRequests(array $filters = []): array
    {
        $query = AttendanceRequest::query()
            ->with(['employee.department', 'approver'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id');

        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', (int) $filters['employee_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['request_type'])) {
            $query->where('request_type', $filters['request_type']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('from_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('to_date', '<=', $filters['date_to']);
        }

        return $query->get()->map(fn (AttendanceRequest $request) => $this->formatRequest($request))->values()->all();
    }

    public function createRequest(array $data): array
    {
        $requestDate = Carbon::parse($data['request_date'])->toDateString();

        $request = DB::transaction(function () use ($data, $requestDate) {
            $request = AttendanceRequest::create([
                'employee_id' => (int) $data['employee_id'],
                'request_type' => $data['request_type'],
                'from_date' => $requestDate,
                'to_date' => $requestDate,
                'reason' => $data['reason'],
                'status' => AttendanceRequestStatus::PENDING->value,
                'submitted_at' => now(),
                'approved_by' => null,
                'approved_at' => null,
            ]);

            AttendanceRequestDetail::create([
                'request_id' => $request->id,
                'work_date' => $requestDate,
                'requested_check_in' => null,
                'requested_check_out' => null,
                'requested_hours' => $this->defaultRequestedHours($data['request_type']),
                'note' => $data['reason'],
            ]);

            return $request->load(['employee.department', 'approver', 'details']);
        });

        return $this->formatRequest($request);
    }

    public function getRequest(int $id): ?array
    {
        $request = AttendanceRequest::query()
            ->with(['employee.department', 'approver', 'details'])
            ->find($id);

        return $request ? $this->formatRequest($request) : null;
    }

    public function approveRequest(int $id, array $data = []): ?array
    {
        return DB::transaction(function () use ($id, $data) {
            $request = AttendanceRequest::query()
                ->with(['employee.department', 'approver', 'details'])
                ->find($id);

            if (!$request) {
                return null;
            }

            $reviewer = $this->resolveReviewer();

            $request->fill([
                'status' => AttendanceRequestStatus::APPROVED->value,
                'approved_by' => $reviewer?->id,
                'approved_at' => now(),
            ]);
            $request->save();
            $request->load(['employee.department', 'approver', 'details']);

            return $this->formatRequest($request, $data['note'] ?? null);
        });
    }

    public function rejectRequest(int $id, array $data = []): ?array
    {
        return DB::transaction(function () use ($id, $data) {
            $request = AttendanceRequest::query()
                ->with(['employee.department', 'approver', 'details'])
                ->find($id);

            if (!$request) {
                return null;
            }

            $reviewer = $this->resolveReviewer();

            $request->fill([
                'status' => AttendanceRequestStatus::REJECTED->value,
                'approved_by' => $reviewer?->id,
                'approved_at' => now(),
            ]);
            $request->save();
            $request->load(['employee.department', 'approver', 'details']);

            return $this->formatRequest($request, $data['note'] ?? null);
        });
    }

    private function formatTimeLog(TimeLog $log): array
    {
        $employee = $log->employee;

        return [
            'id' => $log->id,
            'employee_id' => $log->employee_id,
            'employee_code' => $employee?->employee_code,
            'employee_name' => $employee?->full_name ?? 'Unknown',
            'check_time' => $log->log_time?->format('Y-m-d H:i:s'),
            'check_type' => $this->normalizeCheckType($log->log_type),
            'machine_number' => $log->machine_number,
            'is_valid' => (bool) $log->is_valid,
            'source' => $log->source,
            'date' => $log->log_time?->format('Y-m-d'),
        ];
    }

    private function formatDailyRecord(AttendanceDaily $record, Employee $employee, ?Shift $shift): array
    {
        return [
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'employee_name' => $employee->full_name,
            'date' => $record->work_date?->format('Y-m-d'),
            'shift_code' => $shift?->code ?? 'SHIFT_A',
            'shift_name' => $shift?->name ?? 'Ca sang',
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

    private function formatAbsentDailyRecord(Employee $employee, string $date, ?Shift $shift): array
    {
        return [
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'employee_name' => $employee->full_name,
            'date' => $date,
            'shift_code' => $shift?->code ?? 'SHIFT_A',
            'shift_name' => $shift?->name ?? 'Ca sang',
            'check_in' => null,
            'check_out' => null,
            'working_hours' => 0,
            'overtime_hours' => 0,
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'status' => 'absent',
            'note' => null,
        ];
    }

    private function formatMonthlySummaryRecord(
        AttendanceMonthlySummary $summary,
        Employee $employee,
        int $standardDays,
        Collection $dailyRows
    ): array {
        $metrics = $this->calculateMonthlyMetrics($dailyRows, $standardDays);

        return [
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'employee_name' => $employee->full_name,
            'department_name' => $employee->department?->name,
            'month' => $summary->attendancePeriod?->month,
            'year' => $summary->attendancePeriod?->year,
            'standard_working_days' => $standardDays,
            'actual_working_days' => (float) $summary->total_workdays,
            'late_count' => $metrics['late_count'],
            'total_late_minutes' => (int) $summary->late_minutes,
            'early_leave_count' => $metrics['early_leave_count'],
            'total_early_minutes' => (int) $summary->early_minutes,
            'absent_days' => $metrics['absent_days'],
            'paid_leave_days' => (float) $summary->paid_leave_days,
            'unpaid_leave_days' => (float) $summary->unpaid_leave_days,
            'overtime_hours' => (float) $summary->ot_hours,
            'overtime_hours_weekday' => round(((float) $summary->ot_hours) * 0.6, 1),
            'overtime_hours_weekend' => round(((float) $summary->ot_hours) * 0.3, 1),
            'overtime_hours_holiday' => round(((float) $summary->ot_hours) * 0.1, 1),
        ];
    }

    private function buildMonthlySummaryFromDaily(
        Employee $employee,
        ?AttendancePeriod $period,
        int $standardDays,
        Collection $dailyRows
    ): array {
        $metrics = $this->calculateMonthlyMetrics($dailyRows, $standardDays);

        return [
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'employee_name' => $employee->full_name,
            'department_name' => $employee->department?->name,
            'month' => $period?->month,
            'year' => $period?->year,
            'standard_working_days' => $standardDays,
            'actual_working_days' => $metrics['actual_working_days'],
            'late_count' => $metrics['late_count'],
            'total_late_minutes' => $metrics['total_late_minutes'],
            'early_leave_count' => $metrics['early_leave_count'],
            'total_early_minutes' => $metrics['total_early_minutes'],
            'absent_days' => $metrics['absent_days'],
            'paid_leave_days' => $metrics['paid_leave_days'],
            'unpaid_leave_days' => $metrics['unpaid_leave_days'],
            'overtime_hours' => $metrics['overtime_hours'],
            'overtime_hours_weekday' => $metrics['overtime_hours_weekday'],
            'overtime_hours_weekend' => $metrics['overtime_hours_weekend'],
            'overtime_hours_holiday' => $metrics['overtime_hours_holiday'],
        ];
    }

    private function formatRequest(AttendanceRequest $request, ?string $reviewNote = null): array
    {
        return [
            'id' => $request->id,
            'employee_id' => $request->employee_id,
            'employee_code' => $request->employee?->employee_code,
            'employee_name' => $request->employee?->full_name ?? 'Unknown',
            'request_type' => $request->request_type,
            'request_date' => $request->from_date?->format('Y-m-d'),
            'reason' => $request->reason,
            'status' => $request->status instanceof AttendanceRequestStatus
                ? $request->status->value
                : (string) $request->status,
            'attachment' => null,
            'reviewed_by' => $request->approver?->name ?? $request->approver?->username ?? ($request->approved_by ? 'admin' : null),
            'reviewed_at' => $request->approved_at?->toISOString(),
            'review_note' => $reviewNote ?? $this->defaultReviewNote($request->status),
            'created_at' => $request->submitted_at?->toISOString() ?? $request->created_at?->toISOString(),
        ];
    }

    private function defaultReviewNote(mixed $status): ?string
    {
        $statusValue = $status instanceof AttendanceRequestStatus ? $status->value : (string) $status;

        return match ($statusValue) {
            AttendanceRequestStatus::APPROVED->value => 'Approved',
            AttendanceRequestStatus::REJECTED->value => 'Rejected',
            default => null,
        };
    }

    private function normalizeAttendanceStatus(mixed $status): string
    {
        if ($status instanceof \App\Enums\AttendanceStatus) {
            return $status->value;
        }

        return (string) $status;
    }

    private function calculateMonthlyMetrics(Collection $dailyRows, int $standardDays): array
    {
        $lateRows = $dailyRows->filter(fn ($row) => (int) ($row->late_minutes ?? 0) > 0);
        $earlyRows = $dailyRows->filter(fn ($row) => (int) ($row->early_minutes ?? 0) > 0);

        $actualWorkingDays = round($dailyRows->sum(fn ($row) => (float) ($row->workday_value ?? 0)), 1);
        $totalLateMinutes = (int) $dailyRows->sum(fn ($row) => (int) ($row->late_minutes ?? 0));
        $totalEarlyMinutes = (int) $dailyRows->sum(fn ($row) => (int) ($row->early_minutes ?? 0));
        $overtimeHours = round($dailyRows->sum(fn ($row) => (float) ($row->ot_hours ?? 0)), 1);
        $paidLeaveDays = round($dailyRows->filter(fn ($row) => $this->normalizeAttendanceStatus($row->attendance_status ?? null) === 'leave')->count(), 1);
        $absentDays = (float) $dailyRows->filter(fn ($row) => $this->normalizeAttendanceStatus($row->attendance_status ?? null) === 'absent')->count();
        $unpaidLeaveDays = max(0, $absentDays - $paidLeaveDays);

        return [
            'actual_working_days' => $actualWorkingDays,
            'late_count' => $lateRows->count(),
            'total_late_minutes' => $totalLateMinutes,
            'early_leave_count' => $earlyRows->count(),
            'total_early_minutes' => $totalEarlyMinutes,
            'absent_days' => $absentDays,
            'paid_leave_days' => $paidLeaveDays,
            'unpaid_leave_days' => $unpaidLeaveDays,
            'overtime_hours' => $overtimeHours,
            'overtime_hours_weekday' => round($overtimeHours * 0.6, 1),
            'overtime_hours_weekend' => round($overtimeHours * 0.3, 1),
            'overtime_hours_holiday' => round($overtimeHours * 0.1, 1),
            'total_workdays' => $actualWorkingDays,
            'regular_hours' => round($dailyRows->sum(fn ($row) => (float) ($row->regular_hours ?? 0)), 1),
            'ot_hours' => $overtimeHours,
            'night_hours' => round($dailyRows->sum(fn ($row) => (float) ($row->night_hours ?? 0)), 1),
            'late_minutes' => $totalLateMinutes,
            'early_minutes' => $totalEarlyMinutes,
            'meal_count' => (int) $dailyRows->sum(fn ($row) => (int) ($row->meal_count ?? 0)),
        ];
    }

    private function paginateArray(array $items, array $filters): array
    {
        $total = count($items);
        $perPage = (int) ($filters['per_page'] ?? 20);
        $page = (int) ($filters['page'] ?? 1);
        $offset = max(0, ($page - 1) * $perPage);

        return [
            'items' => array_slice($items, $offset, $perPage),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
        ];
    }

    private function tryCustomerAttendanceProcedure(array $data): array
    {
        $month = (int) ($data['month'] ?? Carbon::now()->month);
        $year = (int) ($data['year'] ?? Carbon::now()->year);
        $docDate = $data['doc_date'] ?? sprintf('%04d-%02d-01', $year, $month);

        return app(CustomerProcedureService::class)->execute('dbo.usp_CreateAndCalculateAttendance', [
            '@_DocDate1' => Carbon::parse($docDate)->toDateString(),
            '@_BranchCode' => $data['branch_code'] ?? 'A01,A02',
            '@_DeptCode' => $data['department_code'] ?? '',
            '@_EmployeeCode' => $data['employee_code'] ?? '',
        ]);
    }

    private function normalizeHeader(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii(trim($value)))) ?? '';
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, string> $candidates
     */
    private function findHeaderIndex(array $headers, array $candidates): ?int
    {
        foreach ($headers as $index => $header) {
            foreach ($candidates as $candidate) {
                if ($header === $candidate || str_contains($header, $candidate)) {
                    return $index;
                }
            }
        }

        return null;
    }

    private function parseExcelDateTime(mixed $value): Carbon
    {
        if (is_numeric($value)) {
            $serial = (float) $value;
            $days = (int) floor($serial);
            $seconds = (int) round(($serial - $days) * 86400);

            return Carbon::create(1899, 12, 30, 0, 0, 0)
                ->addDays($days)
                ->addSeconds($seconds);
        }

        return Carbon::parse((string) $value);
    }

    private function resolveAttendancePeriod(array $filters, bool $allowCreate = false): array
    {
        $month = isset($filters['month']) ? (int) $filters['month'] : null;
        $year = isset($filters['year']) ? (int) $filters['year'] : null;

        if (!$month || !$year) {
            $latest = AttendancePeriod::query()->orderByDesc('year')->orderByDesc('month')->first();

            if ($latest) {
                return [
                    $latest,
                    $latest->from_date?->toDateString(),
                    $latest->to_date?->toDateString(),
                ];
            }

            $now = Carbon::now();
            $month ??= (int) $now->month;
            $year ??= (int) $now->year;
        }

        $fromDate = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $toDate = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $period = AttendancePeriod::query()
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if (!$period && $allowCreate) {
            return [null, $fromDate, $toDate];
        }

        return [$period, $fromDate, $toDate];
    }

    private function resolveShiftForEmployeeOnDate(int $employeeId, string $workDate): ?Shift
    {
        $assignment = ShiftAssignment::query()
            ->with('shift')
            ->where('employee_id', $employeeId)
            ->whereDate('work_date', '<=', $workDate)
            ->orderByDesc('work_date')
            ->orderByDesc('id')
            ->first();

        if ($assignment?->shift) {
            return $assignment->shift;
        }

        return Shift::query()->where('status', 'active')->orderBy('id')->first();
    }

    private function normalizeCheckType(string $logType): string
    {
        return match ($logType) {
            'check_in', 'in' => 'in',
            'check_out', 'out' => 'out',
            default => 'unknown',
        };
    }

    /**
     * Customer check-in/out files use D20Employee.Code from the restored SQL Server
     * database. When a code is missing in the app DB, create the minimal local
     * employee/department records needed to attach imported logs.
     *
     * @param array<string, int> $employeeIdsByCode
     */
    private function syncCustomerEmployee(string $employeeCode, array &$employeeIdsByCode): ?int
    {
        $connectionName = $this->customerConnectionName();
        if (!$connectionName) {
            return null;
        }

        $customerEmployee = $this->findCustomerEmployee($connectionName, $employeeCode);
        if (!$customerEmployee) {
            return null;
        }

        $departmentId = $this->syncCustomerDepartment(
            $connectionName,
            trim((string) ($customerEmployee->DeptCode ?? '')),
        );
        $positionId = $this->syncCustomerPosition(
            $connectionName,
            trim((string) ($customerEmployee->PositionCode ?? '')),
            $departmentId,
        );
        $userId = $this->syncCustomerUser($employeeCode, $customerEmployee);

        $employee = Employee::query()->updateOrCreate(
            ['employee_code' => $employeeCode],
            [
                'user_id' => $userId,
                'full_name' => trim((string) ($customerEmployee->FullName ?? $employeeCode)) ?: $employeeCode,
                'dob' => $this->nullableDate($customerEmployee->BirthDate ?? null),
                'gender' => $this->customerGender($customerEmployee->Gender ?? null),
                'national_id' => $this->nullableString($customerEmployee->IdCardNo ?? null),
                'tax_code' => $this->nullableString($customerEmployee->TaxRegNo ?? null),
                'email' => $this->nullableString($customerEmployee->Email ?? null),
                'phone' => $this->nullableString($customerEmployee->Mobile ?? null),
                'bank_account_no' => $this->nullableString($customerEmployee->BankAccountNo ?? null),
                'bank_name' => $this->nullableString($customerEmployee->BankName ?? null),
                'department_id' => $departmentId,
                'position_id' => $positionId,
                'join_date' => $this->nullableDate($customerEmployee->FirstWorkingDate ?? null),
                'employment_status' => ((bool) ($customerEmployee->IsActive ?? true)) ? 'active' : 'inactive',
            ],
        );

        $employeeIdsByCode[Str::upper($employeeCode)] = (int) $employee->id;

        return (int) $employee->id;
    }

    private function customerConnectionName(): ?string
    {
        return config('database.connections.customer_sqlsrv.database') ? 'customer_sqlsrv' : null;
    }

    private function findCustomerEmployee(string $connectionName, string $employeeCode): ?object
    {
        $candidates = [Str::upper($employeeCode)];
        if (str_contains($employeeCode, '-')) {
            $candidates[] = Str::upper(Str::before($employeeCode, '-'));
        }

        try {
            foreach (array_unique($candidates) as $code) {
                $employee = DB::connection($connectionName)
                    ->table('D20Employee')
                    ->where('Code', $code)
                    ->first();

                if ($employee) {
                    return $employee;
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function syncCustomerDepartment(string $connectionName, string $deptCode): ?int
    {
        if ($deptCode === '') {
            return null;
        }

        $name = $deptCode;
        try {
            $department = DB::connection($connectionName)
                ->table('D20Department')
                ->where('Code', $deptCode)
                ->first();
            $name = trim((string) ($department->Name ?? $deptCode)) ?: $deptCode;
        } catch (\Throwable) {
            // Keep the code as the display name when customer metadata is unavailable.
        }

        return (int) Department::query()->firstOrCreate(
            ['code' => $deptCode],
            ['name' => $name, 'status' => 'active'],
        )->id;
    }

    private function syncCustomerPosition(string $connectionName, string $positionCode, ?int $departmentId): ?int
    {
        if ($positionCode === '') {
            return null;
        }

        if (!$departmentId) {
            $departmentId = (int) Department::query()->firstOrCreate(
                ['code' => 'FUJIMART'],
                ['name' => 'Fujimart', 'status' => 'active'],
            )->id;
        }

        $name = $positionCode;
        try {
            $position = DB::connection($connectionName)
                ->table('D20Position')
                ->where('Code', $positionCode)
                ->first();
            $name = trim((string) ($position->Name ?? $positionCode)) ?: $positionCode;
        } catch (\Throwable) {
            // Keep the code as the display name when customer metadata is unavailable.
        }

        return (int) Position::query()->firstOrCreate(
            ['code' => $positionCode],
            ['name' => $name, 'department_id' => $departmentId, 'status' => 'active'],
        )->id;
    }

    private function syncCustomerUser(string $employeeCode, object $customerEmployee): int
    {
        $slug = Str::lower((string) preg_replace('/[^A-Za-z0-9]+/', '_', $employeeCode));
        $slug = trim($slug, '_') ?: Str::lower(Str::random(8));
        $username = 'fjm_' . substr($slug, 0, 40);

        return (int) User::query()->firstOrCreate(
            ['username' => $username],
            [
                'name' => trim((string) ($customerEmployee->FullName ?? $employeeCode)) ?: $employeeCode,
                'email' => $username . '@fujimart.local',
                'password' => Str::random(32),
                'phone' => $this->nullableString($customerEmployee->Mobile ?? null),
                'is_active' => false,
            ],
        )->id;
    }

    private function nullableString(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function nullableDate(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function customerGender(mixed $value): ?string
    {
        return match ((int) $value) {
            1 => 'male',
            2 => 'female',
            default => null,
        };
    }

    private function standardWorkingDays(): int
    {
        $value = SystemConfig::getValue('standard_work_days_month', '26');

        return max(1, (int) $value);
    }

    private function defaultRequestedHours(string $requestType): ?float
    {
        return match ($requestType) {
            'leave' => 8.0,
            'overtime' => 1.0,
            default => null,
        };
    }

    private function resolveReviewer(): ?User
    {
        $user = Auth::user();

        if ($user instanceof User) {
            return $user;
        }

        return User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('code', ['hr_staff', 'system_admin']))
            ->orderBy('id')
            ->first()
            ?? User::query()->orderBy('id')->first();
    }

    private function currentActorName(): string
    {
        $user = Auth::user();

        return $user instanceof User ? $user->name : 'admin';
    }
}
