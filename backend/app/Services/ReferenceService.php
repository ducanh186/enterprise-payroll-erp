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

    protected function numericValue(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) $value;
    }
}
