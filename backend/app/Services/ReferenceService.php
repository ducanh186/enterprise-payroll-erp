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
        $connection = $this->customerConnection();
        if ($this->customerDatabaseConfigured() || $this->sourceExists($connection, 'D20Shift')) {
            try {
                return collect($connection->table('D20Shift')
                    ->where('IsActive', 1)
                    ->orderBy('Code')
                    ->get())
                    ->map(fn (object $shift) => $this->formatCustomerShift($shift))
                    ->all();
            } catch (\Throwable) {
                // Fall back to the Laravel migrated table when the customer source DB is unavailable.
            }
        }

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
            ->map(fn (ContractType $type) => $this->formatContractType($type))
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
                'is_active' => $this->isActiveModel($type),
            ])
            ->all();
    }

    public function getPayrollParameters(): array
    {
        $connection = $this->customerConnection();
        if (
            $this->customerDatabaseConfigured()
            || (
                $this->sourceExists($connection, 'vD20PayrollPara_ValuePara')
                && $this->sourceExists($connection, 'vD20PayrollPara_SalaryType')
            )
        ) {
            try {
                return [
                    'value_parameters' => collect($connection->table('vD20PayrollPara_ValuePara')
                        ->where('IsActive', 1)
                        ->orderBy('Parameter')
                        ->get())
                        ->map(fn (object $parameter) => $this->formatCustomerPayrollParameter($parameter, 'vD20PayrollPara_ValuePara'))
                        ->all(),
                    'salary_types' => collect($connection->table('vD20PayrollPara_SalaryType')
                        ->where('IsActive', 1)
                        ->orderBy('Parameter')
                        ->get())
                        ->map(fn (object $parameter) => $this->formatCustomerPayrollParameter($parameter, 'vD20PayrollPara_SalaryType'))
                        ->all(),
                ];
            } catch (\Throwable) {
                // Fall back to the Laravel migrated table when the customer source views are unavailable.
            }
        }

        return PayrollParameter::query()
            ->with(['details' => fn ($query) => $query->orderBy('display_order')->orderBy('id')])
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
            ->map(fn (AllowanceType $type) => $this->formatAllowance($type))
            ->all();
    }

    public function createContractType(array $data): array
    {
        $type = ContractType::query()->create($this->contractTypePayload($data));

        return $this->formatContractType($type);
    }

    public function updateContractType(int $id, array $data): ?array
    {
        $type = ContractType::query()->find($id);
        if (!$type) {
            return null;
        }

        $type->fill($this->contractTypePayload($data, true))->save();

        return $this->formatContractType($type->fresh());
    }

    public function suspendContractType(int $id): ?array
    {
        return $this->updateContractType($id, ['status' => 'inactive']);
    }

    public function createAllowance(array $data): array
    {
        $allowance = AllowanceType::query()->create($this->allowancePayload($data));

        return $this->formatAllowance($allowance);
    }

    public function updateAllowance(int $id, array $data): ?array
    {
        $allowance = AllowanceType::query()->find($id);
        if (!$allowance) {
            return null;
        }

        $allowance->fill($this->allowancePayload($data, true))->save();

        return $this->formatAllowance($allowance->fresh());
    }

    public function suspendAllowance(int $id): ?array
    {
        return $this->updateAllowance($id, ['status' => 'inactive']);
    }

    public function createPayrollParameter(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $parameter = PayrollParameter::query()->create([
                'code' => Str::upper((string) $data['code']),
                'name' => (string) $data['name'],
                'description' => $data['description'] ?? null,
                'effective_from' => $this->dateValue($data['effective_from'] ?? now()->toDateString()),
                'effective_to' => $this->dateValue($data['effective_to'] ?? null),
                'status' => $data['status'] ?? 'active',
            ]);

            $detail = $parameter->details()->create([
                'param_key' => Str::upper((string) $data['code']),
                'param_type' => (string) ($data['type'] ?? $data['unit'] ?? 'value'),
                'default_value' => isset($data['value']) ? (string) $data['value'] : null,
                'display_order' => 0,
            ]);

            return $this->formatPayrollParameter($parameter->fresh(), $detail);
        });
    }

    public function updatePayrollParameter(int $id, array $data): ?array
    {
        return DB::transaction(function () use ($id, $data): ?array {
            $detail = PayrollParameterDetail::query()->with('payrollParameter')->find($id);
            $parameter = $detail?->payrollParameter ?? PayrollParameter::query()->find($id);

            if (!$parameter) {
                return null;
            }

            $detail ??= $parameter->details()->orderBy('display_order')->orderBy('id')->first();
            $parameter->fill(array_filter([
                'code' => array_key_exists('code', $data) ? Str::upper((string) $data['code']) : null,
                'name' => $data['name'] ?? null,
                'description' => array_key_exists('description', $data) ? $data['description'] : null,
                'effective_from' => array_key_exists('effective_from', $data) ? $this->dateValue($data['effective_from']) : null,
                'effective_to' => array_key_exists('effective_to', $data) ? $this->dateValue($data['effective_to']) : null,
                'status' => $data['status'] ?? null,
            ], fn ($value) => $value !== null));
            $parameter->save();

            if ($detail) {
                $detail->fill(array_filter([
                    'param_key' => array_key_exists('code', $data) ? Str::upper((string) $data['code']) : null,
                    'param_type' => $data['type'] ?? $data['unit'] ?? null,
                    'default_value' => array_key_exists('value', $data) ? (string) $data['value'] : null,
                ], fn ($value) => $value !== null));
                $detail->save();
            }

            $detail ??= $parameter->details()->create([
                'param_key' => Str::upper((string) $parameter->code),
                'param_type' => 'value',
                'default_value' => null,
                'display_order' => 0,
            ]);

            return $this->formatPayrollParameter($parameter->fresh(), $detail->fresh());
        });
    }

    public function suspendPayrollParameter(int $id): ?array
    {
        return $this->updatePayrollParameter($id, ['status' => 'inactive']);
    }

    public function createSalaryScale(array $data): array
    {
        $type = PayrollType::query()->create([
            'code' => Str::upper((string) $data['code']),
            'name' => (string) $data['name'],
            'is_probationary' => false,
            'status' => $data['status'] ?? 'active',
        ]);

        return $this->formatLocalSalaryScale($type, collect());
    }

    public function updateSalaryScale(int $id, array $data): ?array
    {
        $type = PayrollType::query()->with('salaryLevels')->find($id);
        if (!$type) {
            return null;
        }

        $type->fill(array_filter([
            'code' => array_key_exists('code', $data) ? Str::upper((string) $data['code']) : null,
            'name' => $data['name'] ?? null,
            'status' => $data['status'] ?? null,
        ], fn ($value) => $value !== null))->save();

        return $this->formatLocalSalaryScale($type->fresh(['salaryLevels']), $type->salaryLevels);
    }

    public function suspendSalaryScale(int $id): ?array
    {
        return $this->updateSalaryScale($id, ['status' => 'inactive']);
    }

    public function createSalaryGrade(int $scaleId, array $data): ?array
    {
        $type = PayrollType::query()->find($scaleId);
        if (!$type) {
            return null;
        }

        $grade = SalaryLevel::query()->create([
            'payroll_type_id' => $type->id,
            'code' => Str::upper((string) $data['code']),
            'level_no' => (int) $data['level_no'],
            'amount' => (float) $data['amount'],
            'effective_from' => $this->dateValue($data['effective_from']),
            'effective_to' => $this->dateValue($data['effective_to'] ?? null),
            'status' => $data['status'] ?? 'active',
        ]);

        return $this->formatLocalSalaryGrade($grade->fresh(['payrollType']));
    }

    public function updateSalaryGrade(int $id, array $data): ?array
    {
        $grade = SalaryLevel::query()->with('payrollType')->find($id);
        if (!$grade) {
            return null;
        }

        $grade->fill(array_filter([
            'code' => array_key_exists('code', $data) ? Str::upper((string) $data['code']) : null,
            'level_no' => $data['level_no'] ?? null,
            'amount' => $data['amount'] ?? null,
            'effective_from' => array_key_exists('effective_from', $data) ? $this->dateValue($data['effective_from']) : null,
            'effective_to' => array_key_exists('effective_to', $data) ? $this->dateValue($data['effective_to']) : null,
            'status' => $data['status'] ?? null,
        ], fn ($value) => $value !== null))->save();

        return $this->formatLocalSalaryGrade($grade->fresh(['payrollType']));
    }

    public function suspendSalaryGrade(int $id): ?array
    {
        return $this->updateSalaryGrade($id, ['status' => 'inactive']);
    }

    public function getSalaryGradeDetails(int $gradeId): array
    {
        $connection = $this->customerConnection();
        if ($this->tableExists($connection, 'D20SalaryGradeDetail')) {
            return collect($connection->table('D20SalaryGradeDetail')
                ->where('ParentId', $gradeId)
                ->where('IsActive', 1)
                ->orderBy('Id')
                ->get())
                ->map(fn (object $detail) => $this->formatCustomerSalaryGradeDetail($detail))
                ->values()
                ->all();
        }

        return [];
    }

    public function createSalaryGradeDetail(int $gradeId, array $data): ?array
    {
        $connection = $this->customerConnection();
        if (!$this->tableExists($connection, 'D20SalaryGrade') || !$this->tableExists($connection, 'D20SalaryGradeDetail')) {
            return null;
        }

        $grade = $connection->table('D20SalaryGrade')->where('Id', $gradeId)->first();
        if (!$grade) {
            return null;
        }

        $now = Carbon::now();
        $id = $connection->table('D20SalaryGradeDetail')->insertGetId([
            'ParentId' => $gradeId,
            'IsGroup' => 0,
            'IsActive' => ($data['status'] ?? 'active') !== 'inactive',
            'CreatedBy' => -1,
            'CreatedAt' => $now,
            'ModifiedBy' => -1,
            'ModifiedAt' => $now,
            'SalaryType' => (string) $data['salary_type'],
            'Amount' => (float) $data['amount'],
            'Description' => $data['description'] ?? null,
        ]);

        $detail = $connection->table('D20SalaryGradeDetail')->where('Id', $id)->first();

        return $detail ? $this->formatCustomerSalaryGradeDetail($detail) : null;
    }

    public function updateSalaryGradeDetail(int $id, array $data): ?array
    {
        $connection = $this->customerConnection();
        if (!$this->tableExists($connection, 'D20SalaryGradeDetail')) {
            return null;
        }

        $detail = $connection->table('D20SalaryGradeDetail')->where('Id', $id)->first();
        if (!$detail) {
            return null;
        }

        $payload = ['ModifiedAt' => Carbon::now()];
        if (array_key_exists('salary_type', $data)) {
            $payload['SalaryType'] = (string) $data['salary_type'];
        }
        if (array_key_exists('amount', $data)) {
            $payload['Amount'] = (float) $data['amount'];
        }
        if (array_key_exists('description', $data)) {
            $payload['Description'] = $data['description'];
        }
        if (array_key_exists('status', $data)) {
            $payload['IsActive'] = $data['status'] !== 'inactive';
        }

        $connection->table('D20SalaryGradeDetail')->where('Id', $id)->update($payload);
        $fresh = $connection->table('D20SalaryGradeDetail')->where('Id', $id)->first();

        return $fresh ? $this->formatCustomerSalaryGradeDetail($fresh) : null;
    }

    protected function formatContractType(ContractType $type): array
    {
        return [
            'id' => $type->id,
            'code' => $type->code,
            'name' => $type->name,
            'duration_months' => $type->duration_months,
            'max_probation_days' => $this->resolveMaxProbationDays($type),
            'is_probationary' => (bool) $type->is_probationary,
            'status' => data_get($type, 'status', 'active'),
            'is_active' => $this->isActiveModel($type),
        ];
    }

    protected function contractTypePayload(array $data, bool $partial = false): array
    {
        $payload = [];
        foreach (['code', 'name', 'duration_months', 'is_probationary', 'status'] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $field === 'code' ? Str::upper((string) $data[$field]) : $data[$field];
            }
        }

        if (!$partial && !array_key_exists('status', $payload)) {
            $payload['status'] = 'active';
        }

        return $payload;
    }

    protected function formatAllowance(AllowanceType $type): array
    {
        return [
            'id' => $type->id,
            'code' => $type->code,
            'name' => $type->name,
            'default_amount' => $this->numericValue($type->default_amount),
            'is_taxable' => (bool) $type->is_taxable,
            'is_insurance_base' => (bool) $type->is_insurance_base,
            'is_insurable' => (bool) $type->is_insurance_base,
            'status' => $type->status,
            'is_active' => $type->status === 'active',
        ];
    }

    protected function allowancePayload(array $data, bool $partial = false): array
    {
        $payload = [];
        foreach (['code', 'name', 'default_amount', 'is_taxable', 'is_insurance_base', 'status'] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $field === 'code' ? Str::upper((string) $data[$field]) : $data[$field];
            }
        }

        if (!$partial && !array_key_exists('status', $payload)) {
            $payload['status'] = 'active';
        }

        return $payload;
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

    protected function formatCustomerShift(object $shift): array
    {
        $code = (string) data_get($shift, 'Code');

        return [
            'id' => $code,
            'code' => $code,
            'name' => (string) data_get($shift, 'Name'),
            'description' => data_get($shift, 'Description'),
            'start_time' => $this->formatTime(data_get($shift, 'StartTime')),
            'end_time' => $this->formatTime(data_get($shift, 'EndTime')),
            'start_time_valid_from' => $this->formatTime(data_get($shift, 'StartTimeValid1')),
            'start_time_valid_to' => $this->formatTime(data_get($shift, 'StartTimeValid2')),
            'end_time_valid_from' => $this->formatTime(data_get($shift, 'EndTimeValid1')),
            'end_time_valid_to' => $this->formatTime(data_get($shift, 'EndTimeValid2')),
            'break_minutes' => (int) (data_get($shift, 'ShiftBreakMins') ?? 0),
            'working_hours' => $this->numericValue(data_get($shift, 'WorkingHours')),
            'workday_value' => $this->numericValue(data_get($shift, 'WorkDay')),
            'is_checkin' => (bool) data_get($shift, 'IsCheckIn'),
            'is_checkout' => (bool) data_get($shift, 'IsCheckOut'),
            'is_night_shift' => data_get($shift, 'StartWorkingNightTime') !== null
                || data_get($shift, 'EndWorkingNightTime') !== null,
            'is_active' => (bool) data_get($shift, 'IsActive', true),
            'source_table' => 'D20Shift',
        ];
    }

    protected function formatPayrollParameter(PayrollParameter $parameter, PayrollParameterDetail $detail): array
    {
        return [
            'id' => $detail->id,
            'parameter_id' => $parameter->id,
            'code' => Str::upper($detail->param_key),
            'name' => Str::headline(Str::replace('_', ' ', $detail->param_key)),
            'value' => $this->parseParameterValue($detail->default_value),
            'unit' => $this->resolveParameterUnit($detail),
            'effective_from' => $this->dateValue($parameter->effective_from),
            'description' => $parameter->description,
            'type' => $detail->param_type,
            'status' => $parameter->status,
            'is_active' => $parameter->status === 'active',
        ];
    }

    protected function formatCustomerPayrollParameter(object $parameter, string $sourceView): array
    {
        $code = (string) data_get($parameter, 'Parameter');

        return [
            'id' => data_get($parameter, 'Id') ?? $code,
            'parameter_id' => data_get($parameter, 'Id') ?? $code,
            'code' => $code,
            'param_code' => $code,
            'name' => (string) data_get($parameter, 'Name'),
            'param_name' => (string) data_get($parameter, 'Name'),
            'value' => $this->parseParameterValue(data_get($parameter, 'Amount')),
            'unit' => $this->resolveCustomerParameterUnit($parameter),
            'effective_from' => $this->dateValue(data_get($parameter, 'EffectiveDate')),
            'description' => data_get($parameter, 'Description'),
            'type' => data_get($parameter, 'Type'),
            'status' => (bool) data_get($parameter, 'IsActive', true) ? 'active' : 'inactive',
            'is_active' => (bool) data_get($parameter, 'IsActive', true),
            'source_view' => $sourceView,
        ];
    }

    protected function resolveCustomerParameterUnit(object $parameter): string
    {
        $code = Str::lower((string) data_get($parameter, 'Parameter'));
        $type = Str::lower((string) data_get($parameter, 'Type'));

        if (Str::contains($code, ['rate', 'percent', 'tyle'])) {
            return 'percent';
        }

        if (Str::contains($code, ['day', 'days', 'ngay'])) {
            return 'days';
        }

        if (Str::contains($code, ['salary', 'amount', 'luong', 'phu_cap', 'bonus', 'deduction'])) {
            return 'VND';
        }

        return $type === 'salary' ? 'VND' : 'value';
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
                    'id' => data_get($scale, 'Id') ?? data_get($scale, 'id') ?? $code,
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
                return $this->formatLocalSalaryScale($first->payrollType, $levels);
            })
            ->values()
            ->all();
    }

    protected function formatLocalSalaryScale(?PayrollType $type, Collection $levels): array
    {
        $scaleCode = data_get($type, 'code') ?? 'PAYROLL_TYPE_' . (string) data_get($levels->first(), 'payroll_type_id', 'NEW');
        $scaleName = data_get($type, 'name') ?? 'Thang lương ' . $scaleCode;

        return [
            'id' => data_get($type, 'id'),
            'code' => $scaleCode,
            'name' => $scaleName,
            'description' => 'Fallback từ bảng salary_levels nội bộ.',
            'status' => data_get($type, 'status', 'active'),
            'is_active' => data_get($type, 'status', 'active') === 'active',
            'grades' => $levels
                ->map(fn (SalaryLevel $level) => $this->formatLocalSalaryGrade($level))
                ->values()
                ->all(),
            'source' => 'laravel_fallback',
        ];
    }

    protected function formatLocalSalaryGrade(SalaryLevel $level): array
    {
        $scaleCode = data_get($level, 'payrollType.code') ?? 'PAYROLL_TYPE_' . $level->payroll_type_id;

        return [
            'id' => $level->id,
            'scale_code' => $scaleCode,
            'code' => $level->code,
            'effective_date' => $this->dateValue($level->effective_from),
            'effective_from' => $this->dateValue($level->effective_from),
            'effective_to' => $this->dateValue($level->effective_to),
            'salary_level' => (int) $level->level_no,
            'level_no' => (int) $level->level_no,
            'amount' => $this->numericValue($level->amount),
            'description' => $level->code,
            'status' => data_get($level, 'status', 'active'),
            'is_active' => data_get($level, 'status', 'active') === 'active',
            'details' => [
                [
                    'row_id' => 'SL-' . $level->id . '-BASE',
                    'parent_id' => (string) $level->id,
                    'salary_type' => data_get($level, 'payrollType.code') ?? 'BASE',
                    'amount' => $this->numericValue($level->amount),
                    'description' => data_get($level, 'payrollType.name') ?? 'Lương cơ bản',
                    'status' => data_get($level, 'status', 'active'),
                ],
            ],
        ];
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
            ->map(fn (object $detail) => $this->formatCustomerSalaryGradeDetail($detail))
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

    protected function formatCustomerSalaryGradeDetail(object $detail): array
    {
        $isActive = (bool) (data_get($detail, 'IsActive') ?? data_get($detail, 'is_active') ?? true);

        return [
            'id' => (int) (data_get($detail, 'Id') ?? data_get($detail, 'id') ?? 0),
            'row_id' => (string) (data_get($detail, 'RowId') ?? data_get($detail, 'row_id') ?? ''),
            'parent_id' => (string) (data_get($detail, 'ParentId') ?? data_get($detail, 'parent_id') ?? ''),
            'salary_type' => data_get($detail, 'SalaryType') ?? data_get($detail, 'salary_type'),
            'amount' => $this->numericValue(data_get($detail, 'Amount') ?? data_get($detail, 'amount')),
            'description' => data_get($detail, 'Description') ?? data_get($detail, 'description'),
            'status' => $isActive ? 'active' : 'inactive',
            'is_active' => $isActive,
        ];
    }

    protected function customerConnection(): ConnectionInterface
    {
        if ($this->customerDatabaseConfigured()) {
            return DB::connection('customer_sqlsrv');
        }

        return DB::connection();
    }

    protected function customerDatabaseConfigured(): bool
    {
        return (bool) config('database.connections.customer_sqlsrv.database');
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

    protected function sourceExists(ConnectionInterface $connection, string $source): bool
    {
        try {
            if ($connection->getDriverName() === 'sqlsrv') {
                $row = $connection->selectOne('SELECT OBJECT_ID(?) AS object_id', [$source]);
                return !empty($row?->object_id);
            }

            if ($connection->getDriverName() === 'sqlite') {
                $row = $connection->selectOne(
                    "SELECT name FROM sqlite_master WHERE type IN ('table', 'view') AND name = ?",
                    [$source]
                );

                return !empty($row?->name);
            }

            return $connection->getSchemaBuilder()->hasTable($source);
        } catch (\Throwable) {
            return false;
        }
    }
}
