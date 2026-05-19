<?php

namespace App\Services;

use App\Enums\EmploymentStatus;
use App\Models\ContractAllowance;
use App\Models\ContractType;
use App\Models\Dependent;
use App\Models\Employee;
use App\Models\LabourContract;
use App\Models\PayrollType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmployeeService
{
    public function getEmployees(array $filters = []): array
    {
        $query = Employee::query()->with(['department', 'position']);

        $this->applyEmployeeFilters($query, $filters);

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, (int) ($filters['per_page'] ?? 15));

        $paginator = $query->orderBy('id')->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()
                ->map(fn (Employee $employee) => $this->formatEmployee($employee))
                ->all(),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
        ];
    }

    public function getEmployee(int $id): ?array
    {
        $employee = Employee::query()
            ->with(['department', 'position'])
            ->find($id);

        if (!$employee) {
            return null;
        }

        return $this->formatEmployee($employee, true);
    }

    public function createEmployee(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $employee = Employee::query()->create($this->employeePayload($data));

            return $this->formatEmployee($employee->fresh(['department', 'position']), true);
        });
    }

    public function updateEmployee(int $id, array $data): ?array
    {
        return DB::transaction(function () use ($id, $data) {
            $employee = Employee::query()->with(['department', 'position'])->find($id);

            if (!$employee) {
                return null;
            }

            $employee->fill($this->employeePayload($data, true));
            $employee->save();

            return $this->formatEmployee($employee->fresh(['department', 'position']), true);
        });
    }

    public function suspendEmployee(int $id): ?array
    {
        return DB::transaction(function () use ($id) {
            $employee = Employee::query()->with(['department', 'position'])->find($id);

            if (!$employee) {
                return null;
            }

            $employee->employment_status = EmploymentStatus::INACTIVE;
            $employee->save();

            return $this->formatEmployee($employee->fresh(['department', 'position']), true);
        });
    }

    public function getActiveContract(int $employeeId): ?array
    {
        $today = Carbon::today()->toDateString();

        $contract = LabourContract::query()
            ->with([
                'employee.department',
                'contractType',
                'payrollType',
                'salaryLevel',
                'allowances.allowanceType',
            ])
            ->active()
            ->where('employee_id', $employeeId)
            ->whereDate('start_date', '<=', $today)
            ->where(function (Builder $query) use ($today) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        return $contract ? $this->formatContract($contract) : null;
    }

    public function getContracts(array $filters = []): array
    {
        $query = LabourContract::query()
            ->with([
                'employee.department',
                'contractType',
                'payrollType',
                'salaryLevel',
                'allowances.allowanceType',
            ])
            ->active();

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, (int) ($filters['per_page'] ?? 15));

        $paginator = $query
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()
                ->map(fn (LabourContract $contract) => $this->formatContract($contract))
                ->all(),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
        ];
    }

    public function createContract(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $contract = LabourContract::query()->create($this->contractPayload($data));

            return $this->formatContract($contract->fresh([
                'employee.department',
                'contractType',
                'payrollType',
                'salaryLevel',
                'allowances.allowanceType',
            ]));
        });
    }

    public function updateContract(int $id, array $data): ?array
    {
        return DB::transaction(function () use ($id, $data) {
            $contract = LabourContract::query()->find($id);

            if (!$contract) {
                return null;
            }

            $contract->fill($this->contractPayload($data, true));
            $contract->save();

            return $this->formatContract($contract->fresh([
                'employee.department',
                'contractType',
                'payrollType',
                'salaryLevel',
                'allowances.allowanceType',
            ]));
        });
    }

    public function suspendContract(int $id): ?array
    {
        return $this->updateContract($id, ['status' => 'terminated']);
    }

    public function getContract(int $id): ?array
    {
        $contract = LabourContract::query()
            ->with([
                'employee.department',
                'contractType',
                'payrollType',
                'salaryLevel',
                'allowances.allowanceType',
            ])
            ->find($id);

        return $contract ? $this->formatContract($contract) : null;
    }

    public function getDependents(int $employeeId): array
    {
        return Dependent::query()
            ->where('employee_id', $employeeId)
            ->orderBy('full_name')
            ->get()
            ->map(fn (Dependent $dependent) => $this->formatDependent($dependent))
            ->all();
    }

    public function createDependent(int $employeeId, array $data): ?array
    {
        $employee = Employee::query()->find($employeeId);

        if (!$employee) {
            return null;
        }

        $dependent = $employee->dependents()->create($this->dependentPayload($data));

        return $this->formatDependent($dependent);
    }

    public function updateDependent(int $employeeId, int $dependentId, array $data): ?array
    {
        $dependent = Dependent::query()
            ->where('employee_id', $employeeId)
            ->find($dependentId);

        if (!$dependent) {
            return null;
        }

        $dependent->fill($this->dependentPayload($data, true));
        $dependent->save();

        return $this->formatDependent($dependent->fresh());
    }

    protected function applyEmployeeFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['keyword'])) {
            $keyword = trim((string) $filters['keyword']);

            $query->where(function (Builder $subQuery) use ($keyword) {
                $subQuery->where('employee_code', 'like', "%{$keyword}%")
                    ->orWhere('full_name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('national_id', 'like', "%{$keyword}%");
            });
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', (int) $filters['department_id']);
        }

        if (array_key_exists('active_status', $filters) && $filters['active_status'] !== '' && $filters['active_status'] !== null) {
            $status = $this->normalizeEmploymentStatusFilter($filters['active_status']);

            if ($status === EmploymentStatus::ACTIVE->value) {
                $query->where('employment_status', EmploymentStatus::ACTIVE->value);
            } elseif ($status === 'inactive_or_terminated') {
                $query->whereIn('employment_status', [
                    EmploymentStatus::INACTIVE->value,
                    EmploymentStatus::TERMINATED->value,
                ]);
            } elseif ($status !== null) {
                $query->where('employment_status', $status);
            }
        }
    }

    protected function normalizeEmploymentStatusFilter(mixed $value): ?string
    {
        if ($value instanceof EmploymentStatus) {
            return $value->value;
        }

        if (is_bool($value)) {
            return $value ? EmploymentStatus::ACTIVE->value : 'inactive_or_terminated';
        }

        if (is_numeric($value)) {
            return ((int) $value) === 1 ? EmploymentStatus::ACTIVE->value : 'inactive_or_terminated';
        }

        $value = Str::lower(trim((string) $value));

        return match ($value) {
            '1', 'true', 'active' => EmploymentStatus::ACTIVE->value,
            '0', 'false', 'inactive', 'terminated' => $value,
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function employeePayload(array $data, bool $partial = false): array
    {
        $fields = [
            'employee_code' => ['employee_code', 'code'],
            'full_name' => ['full_name', 'name'],
            'gender' => ['gender'],
            'dob' => ['birth_date', 'date_of_birth', 'dob'],
            'national_id' => ['id_card_no', 'identity_number', 'national_id'],
            'tax_code' => ['tax_code'],
            'email' => ['email'],
            'phone' => ['mobile', 'phone'],
            'bank_account_no' => ['bank_account_no', 'bank_account'],
            'bank_name' => ['bank_name'],
            'department_id' => ['department_id'],
            'position_id' => ['position_id'],
            'join_date' => ['hire_date', 'join_date', 'start_date'],
            'resign_date' => ['resign_date'],
            'employment_status' => ['status', 'employment_status'],
        ];

        $payload = [];
        foreach ($fields as $column => $aliases) {
            [$found, $value] = $this->firstPresent($data, $aliases);
            if (!$found) {
                continue;
            }

            if (in_array($column, ['dob', 'join_date', 'resign_date'], true)) {
                $payload[$column] = $this->nullableDate($value);
            } elseif (in_array($column, ['department_id', 'position_id'], true)) {
                $payload[$column] = $value === null || $value === '' ? null : (int) $value;
            } elseif ($column === 'employment_status') {
                $payload[$column] = $this->normalizeEmploymentStatusValue($value);
            } else {
                $payload[$column] = $value === null ? null : trim((string) $value);
            }
        }

        if (!$partial && !array_key_exists('employment_status', $payload)) {
            $payload['employment_status'] = EmploymentStatus::ACTIVE->value;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    protected function dependentPayload(array $data, bool $partial = false): array
    {
        $fields = [
            'full_name' => ['full_name', 'name'],
            'relationship' => ['relationship'],
            'dob' => ['date_of_birth', 'birth_date', 'dob'],
            'national_id' => ['identity_number', 'id_card_no', 'national_id'],
            'tax_reduction_from' => ['tax_deduction_from', 'tax_reduction_from'],
            'tax_reduction_to' => ['tax_deduction_to', 'tax_reduction_to'],
        ];

        $payload = [];
        foreach ($fields as $column => $aliases) {
            [$found, $value] = $this->firstPresent($data, $aliases);
            if (!$found) {
                continue;
            }

            $payload[$column] = in_array($column, ['dob', 'tax_reduction_from', 'tax_reduction_to'], true)
                ? $this->nullableDate($value)
                : ($value === null ? null : trim((string) $value));
        }

        if (!$partial && !array_key_exists('relationship', $payload)) {
            $payload['relationship'] = '';
        }

        return $payload;
    }

    protected function contractPayload(array $data, bool $partial = false): array
    {
        $payload = [];
        $employeeId = $data['employee_id'] ?? null;
        if (!$employeeId && !empty($data['employee_code'])) {
            $employeeId = Employee::query()
                ->where('employee_code', (string) $data['employee_code'])
                ->value('id');
        }

        if ($employeeId) {
            $payload['employee_id'] = (int) $employeeId;
        }

        if (array_key_exists('contract_no', $data)) {
            $payload['contract_no'] = trim((string) $data['contract_no']);
        }

        if (array_key_exists('contract_type_id', $data) && $data['contract_type_id']) {
            $payload['contract_type_id'] = (int) $data['contract_type_id'];
        } elseif (!empty($data['contract_type_code'])) {
            $payload['contract_type_id'] = (int) ContractType::query()
                ->where('code', (string) $data['contract_type_code'])
                ->value('id');
        } elseif (!$partial) {
            $payload['contract_type_id'] = (int) ContractType::query()->orderBy('id')->value('id');
        }

        if (array_key_exists('payroll_type_id', $data) && $data['payroll_type_id']) {
            $payload['payroll_type_id'] = (int) $data['payroll_type_id'];
        } elseif (!$partial) {
            $payload['payroll_type_id'] = (int) PayrollType::query()->orderBy('id')->value('id');
        }

        foreach (['start_date', 'end_date', 'sign_date'] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $this->nullableDate($data[$field]);
            }
        }

        foreach (['base_salary', 'probation_rate'] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = (float) $data[$field];
            }
        }

        if (array_key_exists('salary_level_id', $data)) {
            $payload['salary_level_id'] = $data['salary_level_id'] ? (int) $data['salary_level_id'] : null;
        }

        if (array_key_exists('status', $data)) {
            $payload['status'] = $data['status'] ?: 'draft';
        } elseif (!$partial) {
            $payload['status'] = 'active';
        }

        return $payload;
    }

    protected function firstPresent(array $data, array $keys): array
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                return [true, $data[$key]];
            }
        }

        return [false, null];
    }

    protected function normalizeEmploymentStatusValue(mixed $value): string
    {
        if ($value instanceof EmploymentStatus) {
            return $value->value;
        }

        $normalized = Str::lower(trim((string) $value));

        return match ($normalized) {
            'inactive', '0', 'false', 'suspended' => EmploymentStatus::INACTIVE->value,
            'terminated', 'resigned', 'nghi viec' => EmploymentStatus::TERMINATED->value,
            default => EmploymentStatus::ACTIVE->value,
        };
    }

    protected function formatEmployee(Employee $employee, bool $detailed = false): array
    {
        $data = [
            'id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'full_name' => $employee->full_name,
            'gender' => $employee->gender,
            'date_of_birth' => $this->dateValue($employee->dob),
            'birth_date' => $this->dateValue($employee->dob),
            'phone' => $employee->phone,
            'mobile' => $employee->phone,
            'email' => $employee->email,
            'identity_number' => $employee->national_id,
            'id_card_no' => $employee->national_id,
            'department_id' => $employee->department_id,
            'department_name' => data_get($employee, 'department.name'),
            'position' => data_get($employee, 'position.name'),
            'hire_date' => $this->dateValue($employee->join_date),
            'resign_date' => $this->dateValue($employee->resign_date),
            'status' => $this->enumValue($employee->employment_status),
            'active_status' => $this->enumValue($employee->employment_status) === EmploymentStatus::ACTIVE->value,
            'avatar' => null,
        ];

        if ($detailed) {
            $data += [
                'tax_code' => $employee->tax_code,
                'bank_account' => $employee->bank_account_no,
                'bank_name' => $employee->bank_name,
                'bank_branch' => null,
                'permanent_address' => null,
                'current_address' => null,
                'emergency_contact_name' => null,
                'emergency_contact_phone' => null,
            ];
        }

        return $data;
    }

    protected function formatContract(LabourContract $contract): array
    {
        $allowances = $contract->allowances
            ->map(function (ContractAllowance $allowance): array {
                return [
                    'name' => data_get($allowance, 'allowanceType.name') ?? data_get($allowance, 'allowanceType.code') ?? 'Allowance',
                    'amount' => $this->numericValue($allowance->amount),
                ];
            })
            ->values();

        return [
            'id' => $contract->id,
            'employee_id' => $contract->employee_id,
            'employee_code' => data_get($contract, 'employee.employee_code'),
            'employee_name' => data_get($contract, 'employee.full_name'),
            'contract_no' => $contract->contract_no,
            'contract_number' => $contract->contract_no,
            'number' => $contract->contract_no,
            'contract_type_id' => $contract->contract_type_id,
            'contract_type_name' => data_get($contract, 'contractType.name'),
            'salary_level_id' => $contract->salary_level_id,
            'salary_level_code' => data_get($contract, 'salaryLevel.code'),
            'salary_level_name' => data_get($contract, 'salaryLevel.name') ?? data_get($contract, 'salaryLevel.code'),
            'start_date' => $this->dateValue($contract->start_date),
            'end_date' => $this->dateValue($contract->end_date),
            'base_salary' => $this->numericValue($contract->base_salary),
            'basic_salary' => $this->numericValue($contract->base_salary),
            'allowances' => $allowances->all(),
            'insurance_salary' => $this->calculateInsuranceSalary($contract),
            'is_probation' => $this->isProbationContract($contract),
            'status' => $this->enumValue($contract->status),
            'signed_date' => $this->dateValue($contract->sign_date),
        ];
    }

    protected function formatDependent(Dependent $dependent): array
    {
        return [
            'id' => $dependent->id,
            'employee_id' => $dependent->employee_id,
            'full_name' => $dependent->full_name,
            'relationship' => $dependent->relationship,
            'date_of_birth' => $this->dateValue($dependent->dob),
            'identity_number' => $dependent->national_id,
            'tax_deduction_from' => $this->dateValue($dependent->tax_reduction_from),
            'tax_deduction_to' => $this->dateValue($dependent->tax_reduction_to),
            'is_active' => $this->isDependentActive($dependent),
        ];
    }

    protected function calculateInsuranceSalary(LabourContract $contract): float
    {
        $salary = $this->numericValue($contract->base_salary);

        foreach ($contract->allowances as $allowance) {
            if (data_get($allowance, 'allowanceType.is_insurance_base')) {
                $salary += $this->numericValue($allowance->amount);
            }
        }

        return round($salary, 2);
    }

    protected function isProbationContract(LabourContract $contract): bool
    {
        $contractTypeCode = Str::upper((string) data_get($contract, 'contractType.code'));
        $probationRate = $this->numericValue($contract->probation_rate);

        return $contractTypeCode === 'PROBATION'
            || (bool) data_get($contract, 'contractType.is_probationary')
            || (bool) data_get($contract, 'payrollType.is_probationary')
            || ($probationRate > 0 && $probationRate < 100);
    }

    protected function isDependentActive(Dependent $dependent): bool
    {
        $today = Carbon::today();
        $from = $dependent->tax_reduction_from ? Carbon::parse($dependent->tax_reduction_from) : null;
        $to = $dependent->tax_reduction_to ? Carbon::parse($dependent->tax_reduction_to) : null;

        if ($from && $from->greaterThan($today)) {
            return false;
        }

        if ($to && $to->lessThan($today)) {
            return false;
        }

        return true;
    }

    protected function enumValue(mixed $value): mixed
    {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        return $value;
    }

    protected function dateValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse((string) $value)->toDateString();
    }

    protected function nullableDate(mixed $value): ?string
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
