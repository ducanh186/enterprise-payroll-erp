<?php

namespace App\Services;

use App\Models\AllowanceType;
use App\Models\ContractType;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\LateEarlyRule;
use App\Models\PayrollParameter;
use App\Models\PayrollParameterDetail;
use App\Models\PayrollType;
use App\Models\SalaryLevel;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferenceService
{
    public function getShifts(): array
    {
        return Shift::query()
            ->active()
            ->orderBy('id')
            ->get()
            ->map(fn (Shift $shift) => [
                'id' => $shift->id,
                'code' => $shift->code,
                'name' => $shift->name,
                'start_time' => $this->formatTime($shift->start_time),
                'end_time' => $this->formatTime($shift->end_time),
                'break_minutes' => $this->calculateBreakMinutes($shift),
                'working_hours' => $this->calculateWorkingHours($shift),
                'is_night_shift' => (bool) $shift->is_overnight,
                'is_active' => $shift->status === 'active',
            ])
            ->all();
    }

    public function getHolidays(): array
    {
        return Holiday::query()
            ->orderBy('holiday_date')
            ->get()
            ->map(fn (Holiday $holiday) => [
                'id' => $holiday->id,
                'name' => $holiday->name,
                'date' => $this->dateValue($holiday->holiday_date),
                'days' => 1,
                'multiplier' => $this->numericValue($holiday->multiplier),
                'is_paid' => (bool) $holiday->is_paid,
            ])
            ->all();
    }

    public function getContractTypes(): array
    {
        return ContractType::query()
            ->orderBy('id')
            ->get()
            ->map(fn (ContractType $type) => [
                'id' => $type->id,
                'code' => $type->code,
                'name' => $type->name,
                'max_probation_days' => $this->resolveMaxProbationDays($type),
                'is_active' => $this->isActiveModel($type),
            ])
            ->all();
    }

    public function getPayrollTypes(): array
    {
        return PayrollType::query()
            ->orderBy('id')
            ->get()
            ->map(fn (PayrollType $type) => [
                'id' => $type->id,
                'code' => $type->code,
                'name' => $type->name,
                'description' => $this->resolvePayrollTypeDescription($type),
                'is_active' => true,
            ])
            ->all();
    }

    public function getPayrollParameters(): array
    {
        return PayrollParameter::query()
            ->with(['details' => fn ($query) => $query->orderBy('display_order')->orderBy('id')])
            ->active()
            ->effective()
            ->orderBy('id')
            ->get()
            ->flatMap(fn (PayrollParameter $parameter) => $parameter->details->map(
                fn (PayrollParameterDetail $detail) => $this->formatPayrollParameter($parameter, $detail)
            ))
            ->values()
            ->all();
    }

    public function getLateEarlyRules(): array
    {
        return LateEarlyRule::query()
            ->orderBy('id')
            ->get()
            ->map(fn (LateEarlyRule $rule) => [
                'id' => $rule->id,
                'name' => $rule->name,
                'min_minutes' => $rule->from_minute,
                'max_minutes' => $rule->to_minute,
                'deduction_type' => $rule->deduction_type,
                'deduction_value' => $this->numericValue($rule->deduction_value),
                'applies_to' => $this->resolveAppliesTo($rule->code),
            ])
            ->all();
    }

    public function getDepartments(): array
    {
        return Department::query()
            ->with(['manager'])
            ->withCount('employees')
            ->active()
            ->orderBy('id')
            ->get()
            ->map(fn (Department $department) => [
                'id' => $department->id,
                'code' => $department->code,
                'name' => $department->name,
                'manager_name' => data_get($department, 'manager.full_name'),
                'employee_count' => (int) $department->employees_count,
                'is_active' => $department->status === 'active',
            ])
            ->all();
    }

    public function getSalaryLevels(): array
    {
        return SalaryLevel::query()
            ->with(['payrollType'])
            ->orderBy('id')
            ->get()
            ->map(fn (SalaryLevel $level) => [
                'id' => $level->id,
                'code' => $level->code,
                'level_no' => $level->level_no,
                'amount' => $this->numericValue($level->amount),
                'payroll_type_name' => data_get($level, 'payrollType.name'),
                'effective_from' => $this->dateValue($level->effective_from),
                'effective_to' => $this->dateValue($level->effective_to),
            ])
            ->all();
    }

    public function getSalaryScales(): array
    {
        $customerScales = $this->customerSalaryScales();

        return $customerScales !== null ? $customerScales : $this->localSalaryScales();
    }

    public function getAllowances(): array
    {
        return AllowanceType::query()
            ->orderBy('id')
            ->get()
            ->map(fn (AllowanceType $type) => [
                'id' => $type->id,
                'code' => $type->code,
                'name' => $type->name,
                'default_amount' => $this->numericValue($type->default_amount),
                'is_taxable' => (bool) $type->is_taxable,
                'is_insurance_base' => (bool) $type->is_insurance_base,
                'is_active' => $type->status === 'active',
            ])
            ->all();
    }

    protected function formatTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse((string) $value)->format('H:i');
    }

    protected function calculateBreakMinutes(Shift $shift): int
    {
        if ($shift->break_start_time && $shift->break_end_time) {
            return $this->timeIntervalMinutes($shift->break_start_time, $shift->break_end_time);
        }

        if ($shift->min_meal_hours !== null) {
            return (int) round(((float) $shift->min_meal_hours) * 60);
        }

        return 0;
    }

    protected function calculateWorkingHours(Shift $shift): float
    {
        $minutes = $this->timeIntervalMinutes($shift->start_time, $shift->end_time) - $this->calculateBreakMinutes($shift);

        return round(max(0, $minutes) / 60, 1);
    }

    protected function timeIntervalMinutes(mixed $start, mixed $end): int
    {
        if (!$start || !$end) {
            return 0;
        }

        $startAt = Carbon::parse((string) $start);
        $endAt = Carbon::parse((string) $end);

        if ($endAt->lessThanOrEqualTo($startAt)) {
            $endAt = $endAt->copy()->addDay();
        }

        return (int) $startAt->diffInMinutes($endAt);
    }

    protected function formatPayrollParameter(PayrollParameter $parameter, PayrollParameterDetail $detail): array
    {
        return [
            'id' => $detail->id,
            'code' => Str::upper($detail->param_key),
            'name' => Str::headline(Str::replace('_', ' ', $detail->param_key)),
            'value' => $this->parseParameterValue($detail->default_value),
            'unit' => $this->resolveParameterUnit($detail),
            'effective_from' => $this->dateValue($parameter->effective_from),
            'description' => $parameter->description,
        ];
    }

    protected function parseParameterValue(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? $this->numericValue($value) : $value;
    }

    protected function resolveParameterUnit(PayrollParameterDetail $detail): string
    {
        $key = Str::lower($detail->param_key);
        $type = Str::lower((string) $detail->param_type);

        if (Str::contains($key, ['rate', 'percent'])) {
            return 'percent';
        }

        if (Str::contains($key, ['day', 'days'])) {
            return 'days';
        }

        if (Str::contains($key, ['wage', 'salary', 'amount', 'deduction', 'cap'])) {
            return 'VND';
        }

        return match ($type) {
            'percent', 'decimal_rate' => 'percent',
            'money', 'currency' => 'VND',
            'int', 'integer' => 'value',
            'bool', 'boolean' => 'value',
            default => 'value',
        };
    }

    protected function resolveAppliesTo(?string $code): string
    {
        return Str::startsWith(Str::upper((string) $code), 'EARLY_') ? 'early' : 'late';
    }

    protected function resolvePayrollTypeDescription(PayrollType $type): ?string
    {
        return match (Str::upper($type->code)) {
            'MONTHLY' => 'Tinh luong theo thang',
            'HOURLY' => 'Tinh luong theo gio lam viec',
            'PIECEWORK' => 'Tinh luong theo san pham',
            'LUONG_CO_BAN' => 'Tinh luong co ban theo thang',
            'LUONG_THU_VIEC' => 'Tinh luong ap dung cho nhan vien thu viec',
            'LUONG_KHOAN' => 'Tinh luong khoan theo muc giao viec',
            default => data_get($type, 'is_probationary') ? 'Applies to probationary employees' : null,
        };
    }

    protected function resolveMaxProbationDays(ContractType $type): ?int
    {
        if (!$type->is_probationary) {
            return null;
        }

        $durationMonths = (int) ($type->duration_months ?? 0);

        return $durationMonths > 0 ? $durationMonths * 30 : null;
    }

    protected function isActiveModel(object $model): bool
    {
        return data_get($model, 'status', 'active') === 'active';
    }

    protected function dateValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse((string) $value)->toDateString();
    }

    protected function customerSalaryScales(): ?array
    {
        $connection = $this->customerConnection();

        if (!$this->tableExists($connection, 'D20SalaryScale') || !$this->tableExists($connection, 'D20SalaryGrade')) {
            return null;
        }

        $scales = collect($connection->table('D20SalaryScale')->get());
        $grades = collect($connection->table('D20SalaryGrade')->get());
        $details = $this->tableExists($connection, 'D20SalaryGradeDetail')
            ? collect($connection->table('D20SalaryGradeDetail')->get())
            : collect();

        return $scales
            ->map(function (object $scale) use ($grades, $details): array {
                $code = (string) (data_get($scale, 'Code') ?? data_get($scale, 'code') ?? '');
                $scaleGrades = $grades
                    ->filter(fn (object $grade) => (string) (data_get($grade, 'ScaleCode') ?? data_get($grade, 'scale_code') ?? '') === $code)
                    ->sortBy(fn (object $grade) => (int) (data_get($grade, 'SalaryLevel') ?? data_get($grade, 'salary_level') ?? data_get($grade, 'Id') ?? 0))
                    ->values()
                    ->map(fn (object $grade) => $this->formatCustomerSalaryGrade($grade, $details, $code))
                    ->all();

                return [
                    'code' => $code,
                    'name' => (string) (data_get($scale, 'Name') ?? data_get($scale, 'name') ?? $code),
                    'description' => data_get($scale, 'Description') ?? data_get($scale, 'description'),
                    'is_active' => (bool) (data_get($scale, 'IsActive') ?? data_get($scale, 'is_active') ?? true),
                    'grades' => $scaleGrades,
                    'source' => 'customer_sqlsrv',
                ];
            })
            ->values()
            ->all();
    }

    protected function localSalaryScales(): array
    {
        return SalaryLevel::query()
            ->with(['payrollType'])
            ->orderBy('payroll_type_id')
            ->orderBy('level_no')
            ->get()
            ->groupBy('payroll_type_id')
            ->map(function (Collection $levels, int|string $payrollTypeId): array {
                /** @var SalaryLevel $first */
                $first = $levels->first();
                $scaleCode = data_get($first, 'payrollType.code') ?? 'PAYROLL_TYPE_' . $payrollTypeId;
                $scaleName = data_get($first, 'payrollType.name') ?? 'Thang lương ' . $scaleCode;

                return [
                    'code' => $scaleCode,
                    'name' => $scaleName,
                    'description' => 'Fallback từ bảng salary_levels nội bộ.',
                    'is_active' => true,
                    'grades' => $levels
                        ->map(fn (SalaryLevel $level) => [
                            'id' => $level->id,
                            'scale_code' => $scaleCode,
                            'effective_date' => $this->dateValue($level->effective_from),
                            'salary_level' => (int) $level->level_no,
                            'description' => $level->code,
                            'details' => [
                                [
                                    'row_id' => 'SL-' . $level->id . '-BASE',
                                    'parent_id' => (string) $level->id,
                                    'salary_type' => data_get($level, 'payrollType.code') ?? 'BASE',
                                    'amount' => $this->numericValue($level->amount),
                                    'description' => data_get($level, 'payrollType.name') ?? 'Lương cơ bản',
                                ],
                            ],
                        ])
                        ->values()
                        ->all(),
                    'source' => 'laravel_fallback',
                ];
            })
            ->values()
            ->all();
    }

    protected function formatCustomerSalaryGrade(object $grade, Collection $details, string $scaleCode): array
    {
        $id = (string) (data_get($grade, 'Id') ?? data_get($grade, 'id') ?? '');
        $gradeDetails = $details
            ->filter(function (object $detail) use ($id, $scaleCode): bool {
                $parentId = (string) (data_get($detail, 'ParentId') ?? data_get($detail, 'parent_id') ?? '');

                return $parentId === $id || $parentId === $scaleCode;
            })
            ->values()
            ->map(fn (object $detail) => [
                'row_id' => (string) (data_get($detail, 'RowId') ?? data_get($detail, 'row_id') ?? ''),
                'parent_id' => (string) (data_get($detail, 'ParentId') ?? data_get($detail, 'parent_id') ?? ''),
                'salary_type' => data_get($detail, 'SalaryType') ?? data_get($detail, 'salary_type'),
                'amount' => $this->numericValue(data_get($detail, 'Amount') ?? data_get($detail, 'amount')),
                'description' => data_get($detail, 'Description') ?? data_get($detail, 'description'),
            ])
            ->all();

        return [
            'id' => $id !== '' && is_numeric($id) ? (int) $id : $id,
            'scale_code' => (string) (data_get($grade, 'ScaleCode') ?? data_get($grade, 'scale_code') ?? $scaleCode),
            'effective_date' => $this->dateValue(data_get($grade, 'EffectiveDate') ?? data_get($grade, 'effective_date')),
            'salary_level' => (int) (data_get($grade, 'SalaryLevel') ?? data_get($grade, 'salary_level') ?? 0),
            'description' => data_get($grade, 'Description') ?? data_get($grade, 'description'),
            'details' => $gradeDetails,
        ];
    }

    protected function customerConnection(): ConnectionInterface
    {
        $customerDatabase = config('database.connections.customer_sqlsrv.database');

        if ($customerDatabase && DB::connection()->getDriverName() === 'sqlsrv') {
            return DB::connection('customer_sqlsrv');
        }

        return DB::connection();
    }

    protected function tableExists(ConnectionInterface $connection, string $table): bool
    {
        try {
            if ($connection->getDriverName() === 'sqlsrv') {
                $row = $connection->selectOne('SELECT OBJECT_ID(?) AS object_id', [$table]);
                return !empty($row?->object_id);
            }

            return $connection->getSchemaBuilder()->hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

    protected function numericValue(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) $value;
    }
}
