<?php

namespace App\Services;

use App\Enums\AttendancePeriodStatus;
use App\Enums\ContractStatus;
use App\Enums\PayrollRunStatus;
use App\Models\AllowanceType;
use App\Models\AttendanceDaily;
use App\Models\AttendanceMonthlySummary;
use App\Models\AttendancePeriod;
use App\Models\BonusDeduction;
use App\Models\BonusDeductionType;
use App\Models\ContractAllowance;
use App\Models\Dependent;
use App\Models\Employee;
use App\Models\LabourContract;
use App\Models\PayrollParameter;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\PayslipItem;
use App\Models\SystemConfig;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayrollService
{
    private const ALLOWANCE_ID_OFFSET = 1000000000;

    public function getPeriods(): array
    {
        $periods = AttendancePeriod::query()
            ->with([
                'payrollRuns' => fn ($query) => $query->with(['payslips'])->orderByDesc('id'),
            ])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderByDesc('id')
            ->get();

        return [
            'items' => $periods->map(fn (AttendancePeriod $period) => $this->formatPeriod($period))->all(),
            'current' => $periods->first() ? $this->formatPeriod($periods->first()) : null,
        ];
    }

    public function getBonusDeductions(): array
    {
        return BonusDeduction::query()
            ->with(['employee', 'type'])
            ->orderBy('id')
            ->get()
            ->map(fn (BonusDeduction $item) => [
                'id' => $item->id,
                'employee_name' => data_get($item, 'employee.full_name'),
                'type_name' => data_get($item, 'type.name'),
                'type_kind' => data_get($item, 'type.kind'),
                'amount' => (float) $item->amount,
                'description' => $item->description,
                'status' => $item->status,
                'attendance_period_id' => $item->attendance_period_id,
            ])
            ->all();
    }

    public function openPeriod(array $data): array
    {
        $month = (int) $data['month'];
        $year = (int) $data['year'];

        return DB::transaction(function () use ($month, $year, $data) {
            $period = $this->resolveAttendancePeriod($month, $year, true);
            $run = $this->resolvePayrollRun($period, 'all', null, true);

            if (!empty($data['standard_working_days'])) {
                SystemConfig::query()->updateOrCreate(
                    ['config_key' => 'standard_work_days_month'],
                    [
                        'config_value' => (string) (int) $data['standard_working_days'],
                        'description' => 'Auto-updated from payroll period open',
                    ]
                );
            }

            return $this->formatPeriod($period, $run);
        });
    }

    public function getPreviewParameters(): array
    {
        $date = Carbon::today();
        $parameters = PayrollParameter::query()
            ->active()
            ->effective($date)
            ->with('details')
            ->get()
            ->keyBy('code');

        $systemConfigs = SystemConfig::query()
            ->whereIn('config_key', [
                'payroll_cutoff_day',
                'payroll_payment_day',
                'overtime_rate_weekday',
                'overtime_rate_weekend',
                'overtime_rate_holiday',
                'standard_work_hours_day',
                'standard_work_days_month',
            ])
            ->get()
            ->mapWithKeys(fn (SystemConfig $config) => [
                $config->config_key => $this->scalarConfigValue($config->config_value),
            ])
            ->all();
        $items = $parameters
            ->flatMap(function (PayrollParameter $parameter) {
                return $parameter->details
                    ->sortBy('display_order')
                    ->map(fn ($detail) => [
                        'id' => $detail->id,
                        'group' => $parameter->code,
                        'name' => Str::headline(str_replace('_', ' ', (string) $detail->param_key)),
                        'type' => $detail->param_type ?: 'string',
                        'required' => Str::contains((string) $detail->validation_rule, 'required'),
                        'default_value' => $this->scalarConfigValue($detail->default_value),
                    ]);
            })
            ->values()
            ->all();

        return [
            'effective_date' => $date->toDateString(),
            'parameters' => $parameters->map(fn (PayrollParameter $parameter) => $this->formatPayrollParameter($parameter))->all(),
            'items' => $items,
            'system_configs' => $systemConfigs,
            'defaults' => [
                'overtime_rate_weekday' => (float) ($systemConfigs['overtime_rate_weekday'] ?? 1.5),
                'overtime_rate_weekend' => (float) ($systemConfigs['overtime_rate_weekend'] ?? 2.0),
                'overtime_rate_holiday' => (float) ($systemConfigs['overtime_rate_holiday'] ?? 3.0),
                'standard_work_hours_day' => (float) ($systemConfigs['standard_work_hours_day'] ?? 8),
                'standard_work_days_month' => (float) ($systemConfigs['standard_work_days_month'] ?? 22),
            ],
        ];
    }

    public function previewRun(array $data): array
    {
        $month = (int) $data['month'];
        $year = (int) $data['year'];
        $scopeType = $data['scope'] ?? 'all';
        $scopeValue = $scopeType === 'department' ? (string) (int) ($data['department_id'] ?? 0) : null;

        return DB::transaction(function () use ($month, $year, $scopeType, $scopeValue, $data) {
            $period = $this->resolveAttendancePeriod($month, $year, true);
            $run = $this->resolvePayrollRun($period, $scopeType, $scopeValue, false);

            if ($run->status === PayrollRunStatus::LOCKED || $period->status === AttendancePeriodStatus::LOCKED) {
                return $this->formatRun($run->fresh(['attendancePeriod', 'payslips.employee.department', 'payslips.items', 'payslips.contract.allowances.allowanceType']));
            }

            $calculation = $this->calculateRunData($period, $data, $run);
            $this->persistRunCalculation($run, $period, $calculation, PayrollRunStatus::PREVIEWED);

            return $this->formatRun($run->fresh(['attendancePeriod', 'payslips.employee.department', 'payslips.items', 'payslips.contract.allowances.allowanceType']));
        });
    }

    public function getRun(string $runId): ?array
    {
        $run = PayrollRun::query()
            ->with([
                'attendancePeriod',
                'payslips.employee.department',
                'payslips.contract.allowances.allowanceType',
                'payslips.items',
            ])
            ->find((int) $runId);

        return $run ? $this->formatRun($run) : null;
    }

    public function finalizeRun(string $runId): ?array
    {
        return DB::transaction(function () use ($runId) {
            $run = PayrollRun::query()
                ->with([
                    'attendancePeriod',
                    'payslips.employee.department',
                    'payslips.contract.allowances.allowanceType',
                    'payslips.items',
                ])
                ->lockForUpdate()
                ->find((int) $runId);

            if (!$run) {
                return null;
            }

            if ($run->status !== PayrollRunStatus::LOCKED) {
                $run->status = PayrollRunStatus::FINALIZED;
                $run->finalized_at = now();
                $run->finalized_by = Auth::id();
                $run->save();
            }

            Payslip::query()
                ->where('payroll_run_id', $run->id)
                ->update([
                    'status' => PayrollRunStatus::FINALIZED->value,
                    'updated_at' => now(),
                ]);

            return $this->formatRun($run->fresh(['attendancePeriod', 'payslips.employee.department', 'payslips.items', 'payslips.contract.allowances.allowanceType']));
        });
    }

    public function lockRun(string $runId): ?array
    {
        return DB::transaction(function () use ($runId) {
            $run = PayrollRun::query()
                ->with([
                    'attendancePeriod',
                    'payslips.employee.department',
                    'payslips.contract.allowances.allowanceType',
                    'payslips.items',
                ])
                ->lockForUpdate()
                ->find((int) $runId);

            if (!$run) {
                return null;
            }

            $run->status = PayrollRunStatus::LOCKED;
            $run->locked_at = now();
            $run->locked_by = Auth::id();
            $run->save();

            if ($run->attendancePeriod && $run->attendancePeriod->status !== AttendancePeriodStatus::LOCKED) {
                $run->attendancePeriod->status = AttendancePeriodStatus::LOCKED;
                $run->attendancePeriod->save();
            }

            Payslip::query()
                ->where('payroll_run_id', $run->id)
                ->update([
                    'status' => PayrollRunStatus::LOCKED->value,
                    'locked_at' => now(),
                    'updated_at' => now(),
                ]);

            return $this->formatRun($run->fresh(['attendancePeriod', 'payslips.employee.department', 'payslips.items', 'payslips.contract.allowances.allowanceType']));
        });
    }

    public function getPayslips(array $filters): array
    {
        $query = Payslip::query()
            ->with([
                'employee.department',
                'contract.allowances.allowanceType',
                'payrollRun.attendancePeriod',
                'items',
            ])
            ->orderByDesc('id');

        if (!empty($filters['month']) && !empty($filters['year'])) {
            $period = AttendancePeriod::query()
                ->where('month', (int) $filters['month'])
                ->where('year', (int) $filters['year'])
                ->first();

            if ($period) {
                $query->where('attendance_period_id', $period->id);
            }
        }

        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', (int) $filters['employee_id']);
        }

        if (!empty($filters['department_id'])) {
            $query->whereHas('employee', fn ($employeeQuery) => $employeeQuery->where('department_id', (int) $filters['department_id']));
        }

        if (!empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 15)));
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()->map(fn (Payslip $payslip) => $this->formatPayslipSummary($payslip))->all(),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
        ];
    }

    public function getPayslip(int $id): ?array
    {
        $payslip = Payslip::query()
            ->with([
                'employee.department',
                'contract.allowances.allowanceType',
                'payrollRun.attendancePeriod',
                'items',
            ])
            ->find($id);

        return $payslip ? $this->formatPayslipDetail($payslip) : null;
    }

    public function getPayslipDetails(int $id): ?array
    {
        return $this->getPayslip($id);
    }

    public function createAdjustment(array $data): array
    {
        return DB::transaction(function () use ($data) {
            return match ($data['type']) {
                'allowance' => $this->createAllowanceAdjustment($data),
                'bonus', 'deduction' => $this->createBonusDeductionAdjustment($data),
                default => throw new \InvalidArgumentException('Unsupported adjustment type.'),
            };
        });
    }

    public function updateAdjustment(int $id, array $data): ?array
    {
        return DB::transaction(function () use ($id, $data) {
            return match ($data['type']) {
                'allowance' => $this->updateAllowanceAdjustment($id, $data),
                'bonus', 'deduction' => $this->updateBonusDeductionAdjustment($id, $data),
                default => throw new \InvalidArgumentException('Unsupported adjustment type.'),
            };
        });
    }

    public function deleteAdjustment(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            if ($this->isEncodedAllowanceId($id)) {
                $allowance = ContractAllowance::query()->find($this->decodeAllowanceId($id));

                if ($allowance) {
                    $allowance->delete();
                    return true;
                }
            }

            return BonusDeduction::query()->whereKey($id)->delete() > 0;
        });
    }

    protected function resolveAttendancePeriod(int $month, int $year, bool $createIfMissing = false): AttendancePeriod
    {
        $period = AttendancePeriod::query()
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if ($period || !$createIfMissing) {
            return $period ?? AttendancePeriod::query()->whereRaw('1 = 0')->firstOrFail();
        }

        return AttendancePeriod::create([
            'period_code' => sprintf('%04d-%02d', $year, $month),
            'month' => $month,
            'year' => $year,
            'from_date' => Carbon::create($year, $month, 1)->startOfMonth()->toDateString(),
            'to_date' => Carbon::create($year, $month, 1)->endOfMonth()->toDateString(),
            'status' => AttendancePeriodStatus::DRAFT->value,
        ]);
    }

    protected function resolvePayrollRun(AttendancePeriod $period, string $scopeType = 'all', ?string $scopeValue = null, bool $draftOnly = false): PayrollRun
    {
        $query = PayrollRun::query()
            ->where('attendance_period_id', $period->id)
            ->where('scope_type', $scopeType)
            ->when($scopeValue !== null, fn ($q) => $q->where('scope_value', $scopeValue), fn ($q) => $q->whereNull('scope_value'))
            ->orderByDesc('id');

        if ($draftOnly) {
            $existing = $query->whereIn('status', [
                PayrollRunStatus::DRAFT->value,
                PayrollRunStatus::PREVIEWED->value,
            ])->first();

            if ($existing) {
                return $existing;
            }
        } else {
            $existing = $query->whereIn('status', [
                PayrollRunStatus::DRAFT->value,
                PayrollRunStatus::PREVIEWED->value,
                PayrollRunStatus::FINALIZED->value,
                PayrollRunStatus::LOCKED->value,
            ])->first();

            if ($existing && in_array($existing->status?->value ?? $existing->status, [PayrollRunStatus::DRAFT->value, PayrollRunStatus::PREVIEWED->value], true)) {
                return $existing;
            }
        }

        $nextRunNo = (int) (PayrollRun::query()
            ->where('attendance_period_id', $period->id)
            ->max('run_no') ?? 0) + 1;

        return PayrollRun::create([
            'attendance_period_id' => $period->id,
            'run_no' => $nextRunNo,
            'scope_type' => $scopeType,
            'scope_value' => $scopeValue,
            'status' => PayrollRunStatus::DRAFT->value,
            'requested_by' => Auth::id(),
        ]);
    }

    protected function scalarConfigValue(?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    protected function floatValue(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return round((float) $value, 2);
    }

    protected function dateValue(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->toISOString();
        }

        return Carbon::parse($value)->toISOString();
    }

    protected function enumValue(mixed $value): ?string
    {
        if ($value instanceof PayrollRunStatus || $value instanceof AttendancePeriodStatus || $value instanceof ContractStatus) {
            return $value->value;
        }

        return $value !== null ? (string) $value : null;
    }

    protected function isEncodedAllowanceId(int $id): bool
    {
        return $id >= self::ALLOWANCE_ID_OFFSET;
    }

    protected function encodeAllowanceId(int $id): int
    {
        return self::ALLOWANCE_ID_OFFSET + $id;
    }

    protected function decodeAllowanceId(int $id): int
    {
        return $id - self::ALLOWANCE_ID_OFFSET;
    }

    protected function createBonusDeductionAdjustment(array $data): array
    {
        $period = $this->resolveAttendancePeriod((int) $data['month'], (int) $data['year'], true);
        $type = $this->resolveBonusDeductionType($data['type'], $data['code'] ?? null);

        $adjustment = BonusDeduction::create([
            'employee_id' => (int) $data['employee_id'],
            'attendance_period_id' => $period->id,
            'type_id' => $type->id,
            'amount' => $this->floatValue($data['amount']),
            'description' => $data['description'],
            'status' => 'active',
            'created_by' => Auth::id(),
        ]);

        return $this->formatBonusDeductionAdjustment($adjustment->load('type', 'employee.department', 'attendancePeriod'));
    }

    protected function updateBonusDeductionAdjustment(int $id, array $data): ?array
    {
        $adjustment = BonusDeduction::query()->with('type', 'employee.department', 'attendancePeriod')->find($id);

        if (!$adjustment) {
            return null;
        }

        $adjustment->update([
            'employee_id' => (int) $data['employee_id'],
            'attendance_period_id' => $this->resolveAttendancePeriod((int) $data['month'], (int) $data['year'], true)->id,
            'type_id' => $this->resolveBonusDeductionType($data['type'], $data['code'] ?? null)->id,
            'amount' => $this->floatValue($data['amount']),
            'description' => $data['description'],
            'status' => 'active',
        ]);

        return $this->formatBonusDeductionAdjustment($adjustment->fresh()->load('type', 'employee.department', 'attendancePeriod'));
    }

    protected function createAllowanceAdjustment(array $data): array
    {
        $period = $this->resolveAttendancePeriod((int) $data['month'], (int) $data['year'], true);
        $allowanceType = $this->resolveAllowanceType($data['code'] ?? $data['description'] ?? null);
        $anchorDate = $period->to_date instanceof Carbon ? $period->to_date : Carbon::parse($period->to_date);
        $contract = $this->resolveActiveContractForEmployee((int) $data['employee_id'], $anchorDate);

        if (!$contract) {
            throw new \RuntimeException('Active contract not found for allowance adjustment.');
        }

        $adjustment = ContractAllowance::query()->updateOrCreate(
            [
                'contract_id' => $contract->id,
                'allowance_type_id' => $allowanceType->id,
            ],
            [
                'amount' => $this->floatValue($data['amount']),
                'effective_from' => $period->from_date,
                'effective_to' => $period->to_date,
            ]
        );

        return $this->formatAllowanceAdjustment($adjustment->load('allowanceType', 'contract.employee.department'));
    }

    protected function updateAllowanceAdjustment(int $id, array $data): ?array
    {
        $adjustment = ContractAllowance::query()->with('allowanceType', 'contract.employee.department')->find($this->decodeAllowanceId($id));

        if (!$adjustment) {
            return null;
        }

        $period = $this->resolveAttendancePeriod((int) $data['month'], (int) $data['year'], true);
        $anchorDate = $period->to_date instanceof Carbon ? $period->to_date : Carbon::parse($period->to_date);
        $contract = $this->resolveActiveContractForEmployee((int) $data['employee_id'], $anchorDate);

        $adjustment->update([
            'contract_id' => $contract?->id ?? $adjustment->contract_id,
            'allowance_type_id' => $this->resolveAllowanceType($data['code'] ?? $data['description'] ?? $adjustment->allowanceType?->code)->id,
            'amount' => $this->floatValue($data['amount']),
            'effective_from' => $period->from_date,
            'effective_to' => $period->to_date,
        ]);

        return $this->formatAllowanceAdjustment($adjustment->fresh()->load('allowanceType', 'contract.employee.department'));
    }

    protected function resolveActiveContractForEmployee(int $employeeId, Carbon $anchorDate): ?LabourContract
    {
        return LabourContract::query()
            ->with(['allowances.allowanceType', 'employee.department'])
            ->active()
            ->where('employee_id', $employeeId)
            ->where(function ($query) use ($anchorDate) {
                $query->whereNull('start_date')->orWhereDate('start_date', '<=', $anchorDate);
            })
            ->where(function ($query) use ($anchorDate) {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', $anchorDate);
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();
    }

    protected function loadParameterContext(Carbon $date): array
    {
        return PayrollParameter::query()
            ->active()
            ->effective($date)
            ->with('details')
            ->get()
            ->keyBy('code')
            ->all();
    }

    protected function calculateRunData(AttendancePeriod $period, array $data, PayrollRun $run): array
    {
        $anchorDate = $period->to_date instanceof Carbon ? $period->to_date : Carbon::parse($period->to_date);
        $parameters = $this->loadParameterContext($anchorDate);
        $scopeType = $data['scope'] ?? $run->scope_type ?? 'all';
        $departmentId = $scopeType === 'department' ? (int) ($data['department_id'] ?? $run->scope_value ?? 0) : null;

        $employees = Employee::query()
            ->with(['department', 'dependents'])
            ->active()
            ->orderBy('employee_code')
            ->get()
            ->filter(fn (Employee $employee) => !$departmentId || (int) $employee->department_id === $departmentId)
            ->values();

        $summaries = AttendanceMonthlySummary::query()
            ->where('attendance_period_id', $period->id)
            ->get()
            ->keyBy('employee_id');

        $dailyRows = AttendanceDaily::query()
            ->where('attendance_period_id', $period->id)
            ->get()
            ->groupBy('employee_id');

        $bonusDeductionRows = BonusDeduction::query()
            ->with('type')
            ->where('attendance_period_id', $period->id)
            ->active()
            ->get()
            ->groupBy('employee_id');

        $items = [];
        $totals = [
            'gross_salary' => 0.0,
            'taxable_income' => 0.0,
            'insurance_base' => 0.0,
            'insurance_employee' => 0.0,
            'insurance_company' => 0.0,
            'pit_amount' => 0.0,
            'bonus_total' => 0.0,
            'deduction_total' => 0.0,
            'net_salary' => 0.0,
        ];

        foreach ($employees as $employee) {
            $contract = $this->resolveActiveContractForEmployee($employee->id, $anchorDate);

            if (!$contract) {
                continue;
            }

            $summary = $summaries->get($employee->id);
            $daily = $dailyRows->get($employee->id, collect());
            $attendance = $summary ? $this->formatAttendanceSummary($summary) : $this->aggregateAttendanceFromDaily($period, $daily);
            $bonusDeductionItems = $bonusDeductionRows->get($employee->id, collect());
            $salary = $this->calculatePayslipForEmployee($period, $employee, $contract, $attendance, $bonusDeductionItems, $parameters, $anchorDate);

            $items[] = $salary;

            foreach ($totals as $key => $value) {
                $totals[$key] += (float) $salary[$key];
            }
        }

        return [
            'items' => $items,
            'totals' => $totals,
            'employees_count' => count($items),
        ];
    }

    protected function persistRunCalculation(PayrollRun $run, AttendancePeriod $period, array $calculation, PayrollRunStatus $status): void
    {
        $run->status = $status;
        if ($status === PayrollRunStatus::PREVIEWED) {
            $run->previewed_at = now();
        }
        $run->save();

        $existingPayslipIds = Payslip::query()->where('payroll_run_id', $run->id)->pluck('id');
        if ($existingPayslipIds->isNotEmpty()) {
            PayslipItem::query()->whereIn('payslip_id', $existingPayslipIds)->delete();
            Payslip::query()->whereIn('id', $existingPayslipIds)->delete();
        }

        foreach ($calculation['items'] as $row) {
            $payslip = Payslip::create([
                'attendance_period_id' => $period->id,
                'employee_id' => $row['employee_id'],
                'payroll_run_id' => $run->id,
                'contract_id' => $row['contract_id'],
                'base_salary_snapshot' => $row['base_salary_snapshot'],
                'gross_salary' => $row['gross_salary'],
                'taxable_income' => $row['taxable_income'],
                'insurance_base' => $row['insurance_base'],
                'insurance_employee' => $row['insurance_employee'],
                'insurance_company' => $row['insurance_company'],
                'pit_amount' => $row['pit_amount'],
                'bonus_total' => $row['bonus_total'],
                'deduction_total' => $row['deduction_total'],
                'net_salary' => $row['net_salary'],
                'status' => $status->value,
                'generated_at' => now(),
                'locked_at' => $status === PayrollRunStatus::LOCKED ? now() : null,
            ]);

            $this->persistPayslipItems($payslip, $row['items']);
        }
    }

    protected function calculatePayslipForEmployee(
        AttendancePeriod $period,
        Employee $employee,
        LabourContract $contract,
        array $attendance,
        Collection $bonusDeductionItems,
        array $parameters,
        Carbon $anchorDate
    ): array {
        $baseSalary = $this->floatValue($contract->base_salary);
        $probationRate = $this->floatValue($contract->probation_rate ?: 100);
        $baseSalarySnapshot = round($baseSalary * ($probationRate > 0 ? ($probationRate / 100) : 1), 2);

        $contractAllowances = $contract->allowances()
            ->with('allowanceType')
            ->where(function ($query) use ($anchorDate) {
                $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $anchorDate);
            })
            ->where(function ($query) use ($anchorDate) {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $anchorDate);
            })
            ->get()
            ->filter(fn (ContractAllowance $allowance) => $allowance->allowanceType && $allowance->allowanceType->status === 'active')
            ->values();

        $allowanceRows = [];
        $allowanceTotal = 0.0;
        $insuranceBaseFromAllowances = 0.0;
        $taxableAllowanceTotal = 0.0;

        foreach ($contractAllowances as $allowance) {
            $type = $allowance->allowanceType;
            $amount = $this->floatValue($allowance->amount);
            $allowanceTotal += $amount;

            if ($type->is_taxable) {
                $taxableAllowanceTotal += $amount;
            }

            if ($type->is_insurance_base) {
                $insuranceBaseFromAllowances += $amount;
            }

            $allowanceRows[] = [
                'item_code' => 'ALW_' . Str::upper(Str::slug($type->code, '_')),
                'item_name' => $type->name,
                'item_group' => 'earning',
                'qty' => 1,
                'rate' => $amount,
                'amount' => $amount,
                'sort_order' => 2 + count($allowanceRows),
                'source_ref' => 'allowance:' . $type->code . ':' . $allowance->id,
            ];
        }

        $bonusTotal = 0.0;
        $deductionTotal = 0.0;
        $insuranceBaseFromAdjustments = 0.0;

        foreach ($bonusDeductionItems as $row) {
            $type = $row->type;
            $amount = $this->floatValue($row->amount);

            if ($type?->kind === 'bonus') {
                $bonusTotal += $amount;
                if ($type->is_insurance_base) {
                    $insuranceBaseFromAdjustments += $amount;
                }
            } else {
                $deductionTotal += $amount;
                if ($type?->is_insurance_base) {
                    $insuranceBaseFromAdjustments -= $amount;
                }
            }
        }

        $insuranceRates = $this->insuranceRates($parameters);
        $taxDeduction = $this->taxDeduction($parameters);
        $insuranceCap = $this->insuranceCap($parameters);
        $pitBrackets = $this->pitBrackets($parameters);

        $insuranceBase = max(0.0, $baseSalarySnapshot + $insuranceBaseFromAllowances + $insuranceBaseFromAdjustments);
        if ($insuranceCap > 0) {
            $insuranceBase = min($insuranceBase, $insuranceCap);
        }

        $insuranceEmployee = round($insuranceBase * ($insuranceRates['employee_bhxh'] + $insuranceRates['employee_bhyt'] + $insuranceRates['employee_bhtn']) / 100, 2);
        $insuranceCompany = round($insuranceBase * ($insuranceRates['employer_bhxh'] + $insuranceRates['employer_bhyt'] + $insuranceRates['employer_bhtn']) / 100, 2);

        $grossSalary = round($baseSalarySnapshot + $allowanceTotal + $bonusTotal, 2);
        $dependentCount = $this->countEligibleDependents($employee->dependents, $anchorDate);
        $taxableIncome = max(0.0, $grossSalary - $insuranceEmployee - $taxDeduction['self'] - ($dependentCount * $taxDeduction['dependent']));
        $pitAmount = $this->calculatePit($taxableIncome, $pitBrackets);
        $netSalary = round($grossSalary - $insuranceEmployee - $pitAmount - $deductionTotal, 2);

        $items = array_merge(
            [
                [
                    'item_code' => 'BASE_SALARY',
                    'item_name' => 'Luong Co Ban',
                    'item_group' => 'earning',
                    'qty' => 1,
                    'rate' => $baseSalarySnapshot,
                    'amount' => $baseSalarySnapshot,
                    'sort_order' => 1,
                    'source_ref' => 'contract:' . $contract->id,
                ],
            ],
            $allowanceRows,
            $bonusTotal > 0 ? [[
                'item_code' => 'BONUS',
                'item_name' => 'Thuong',
                'item_group' => 'earning',
                'qty' => 1,
                'rate' => $bonusTotal,
                'amount' => $bonusTotal,
                'sort_order' => 10,
                'source_ref' => 'bonus_deduction:' . $period->id,
            ]] : [],
            [
                [
                    'item_code' => 'INS_BHXH',
                    'item_name' => 'BHXH (8%)',
                    'item_group' => 'deduction',
                    'qty' => 1,
                    'rate' => $baseSalarySnapshot,
                    'amount' => round($insuranceBase * ($insuranceRates['employee_bhxh'] / 100), 2),
                    'sort_order' => 20,
                    'source_ref' => 'insurance:bhxh',
                ],
                [
                    'item_code' => 'INS_BHYT',
                    'item_name' => 'BHYT (1.5%)',
                    'item_group' => 'deduction',
                    'qty' => 1,
                    'rate' => $baseSalarySnapshot,
                    'amount' => round($insuranceBase * ($insuranceRates['employee_bhyt'] / 100), 2),
                    'sort_order' => 21,
                    'source_ref' => 'insurance:bhyt',
                ],
                [
                    'item_code' => 'INS_BHTN',
                    'item_name' => 'BHTN (1%)',
                    'item_group' => 'deduction',
                    'qty' => 1,
                    'rate' => $baseSalarySnapshot,
                    'amount' => round($insuranceBase * ($insuranceRates['employee_bhtn'] / 100), 2),
                    'sort_order' => 22,
                    'source_ref' => 'insurance:bhtn',
                ],
            ],
            $pitAmount > 0 ? [[
                'item_code' => 'PIT',
                'item_name' => 'Thue TNCN',
                'item_group' => 'deduction',
                'qty' => 1,
                'rate' => null,
                'amount' => $pitAmount,
                'sort_order' => 23,
                'source_ref' => 'pit_calculation',
            ]] : [],
            $deductionTotal > 0 ? [[
                'item_code' => 'OTHER_DED',
                'item_name' => 'Khau Tru Khac',
                'item_group' => 'deduction',
                'qty' => 1,
                'rate' => null,
                'amount' => $deductionTotal,
                'sort_order' => 24,
                'source_ref' => 'bonus_deduction:' . $period->id,
            ]] : [],
            [
                [
                    'item_code' => 'INS_ER_BHXH',
                    'item_name' => 'BHXH Cong Ty (17.5%)',
                    'item_group' => 'employer',
                    'qty' => 1,
                    'rate' => $baseSalarySnapshot,
                    'amount' => round($insuranceBase * ($insuranceRates['employer_bhxh'] / 100), 2),
                    'sort_order' => 30,
                    'source_ref' => 'insurance:bhxh_er',
                ],
                [
                    'item_code' => 'INS_ER_BHYT',
                    'item_name' => 'BHYT Cong Ty (3%)',
                    'item_group' => 'employer',
                    'qty' => 1,
                    'rate' => $baseSalarySnapshot,
                    'amount' => round($insuranceBase * ($insuranceRates['employer_bhyt'] / 100), 2),
                    'sort_order' => 31,
                    'source_ref' => 'insurance:bhyt_er',
                ],
                [
                    'item_code' => 'INS_ER_BHTN',
                    'item_name' => 'BHTN Cong Ty (1%)',
                    'item_group' => 'employer',
                    'qty' => 1,
                    'rate' => $baseSalarySnapshot,
                    'amount' => round($insuranceBase * ($insuranceRates['employer_bhtn'] / 100), 2),
                    'sort_order' => 32,
                    'source_ref' => 'insurance:bhtn_er',
                ],
            ]
        );

        return [
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'employee_name' => $employee->full_name,
            'department_id' => $employee->department_id,
            'department_name' => $employee->department?->name,
            'contract_id' => $contract->id,
            'contract_no' => $contract->contract_no,
            'attendance_period_id' => $period->id,
            'attendance' => $attendance,
            'base_salary_snapshot' => $baseSalarySnapshot,
            'gross_salary' => $grossSalary,
            'taxable_income' => round($taxableIncome, 2),
            'insurance_base' => round($insuranceBase, 2),
            'insurance_employee' => $insuranceEmployee,
            'insurance_company' => $insuranceCompany,
            'pit_amount' => $pitAmount,
            'bonus_total' => round($bonusTotal, 2),
            'deduction_total' => round($deductionTotal, 2),
            'net_salary' => $netSalary,
            'items' => $items,
        ];
    }

    protected function persistPayslipItems(Payslip $payslip, array $items): void
    {
        foreach ($items as $item) {
            PayslipItem::create([
                'payslip_id' => $payslip->id,
                'item_code' => $item['item_code'],
                'item_name' => $item['item_name'],
                'item_group' => $item['item_group'],
                'qty' => $item['qty'],
                'rate' => $item['rate'],
                'amount' => $item['amount'],
                'sort_order' => $item['sort_order'],
                'source_ref' => $item['source_ref'],
            ]);
        }
    }

    protected function insuranceRates(array $parameters): array
    {
        $formula = $this->parameterFormula($parameters['INSURANCE_RATE'] ?? null);

        return [
            'employee_bhxh' => (float) data_get($formula, 'employee.bhxh', 8),
            'employee_bhyt' => (float) data_get($formula, 'employee.bhyt', 1.5),
            'employee_bhtn' => (float) data_get($formula, 'employee.bhtn', 1),
            'employer_bhxh' => (float) data_get($formula, 'employer.bhxh', 17.5),
            'employer_bhyt' => (float) data_get($formula, 'employer.bhyt', 3),
            'employer_bhtn' => (float) data_get($formula, 'employer.bhtn', 1),
        ];
    }

    protected function taxDeduction(array $parameters): array
    {
        $formula = $this->parameterFormula($parameters['TAX_DEDUCTION'] ?? null);

        return [
            'self' => $this->floatValue(data_get($formula, 'self', 11000000)),
            'dependent' => $this->floatValue(data_get($formula, 'dependent', 4400000)),
        ];
    }

    protected function insuranceCap(array $parameters): float
    {
        $formula = $this->parameterFormula($parameters['INSURANCE_CAP'] ?? null);

        return $this->floatValue(data_get($formula, 'cap_amount', data_get($formula, 'cap', 0)));
    }

    protected function pitBrackets(array $parameters): array
    {
        $formula = $this->parameterFormula($parameters['PIT_BRACKET'] ?? null);

        return is_array($formula) ? $formula : [];
    }

    protected function parameterFormula(mixed $parameter): array
    {
        return $parameter?->formula_json ?? [];
    }

    protected function calculatePit(float $taxableIncome, array $brackets): float
    {
        $tax = 0.0;
        $remaining = max(0.0, $taxableIncome);
        $lowerBound = 0.0;

        foreach ($brackets as $bracket) {
            $upperBound = Arr::get($bracket, 'to');
            $rate = ((float) Arr::get($bracket, 'rate', 0)) / 100;

            if ($remaining <= 0) {
                break;
            }

            if ($upperBound === null) {
                $tax += $remaining * $rate;
                break;
            }

            $upperBound = (float) $upperBound;
            if ($taxableIncome > $lowerBound) {
                $taxablePart = min($remaining, max(0.0, $upperBound - $lowerBound));
                $tax += $taxablePart * $rate;
                $remaining -= $taxablePart;
            }

            $lowerBound = $upperBound;
        }

        return round($tax, 2);
    }

    protected function countEligibleDependents(iterable $dependents, Carbon $anchorDate): int
    {
        $count = 0;

        foreach ($dependents as $dependent) {
            if (!$dependent instanceof Dependent) {
                continue;
            }

            $from = $dependent->tax_reduction_from;
            $to = $dependent->tax_reduction_to;

            if ((!$from || $from->startOfDay()->lessThanOrEqualTo($anchorDate)) && (!$to || $to->endOfDay()->greaterThanOrEqualTo($anchorDate))) {
                $count++;
            }
        }

        return $count;
    }

    protected function resolveBonusDeductionType(string $kind, ?string $code = null): BonusDeductionType
    {
        $query = BonusDeductionType::query()->where('kind', $kind);

        if ($code) {
            $match = (clone $query)
                ->where(function ($subQuery) use ($code) {
                    $subQuery->where('code', $code)->orWhere('name', 'like', '%' . $code . '%');
                })
                ->orderBy('id')
                ->first();

            if ($match) {
                return $match;
            }
        }

        return $query->orderBy('id')->firstOrFail();
    }

    protected function resolveAllowanceType(?string $codeOrName): AllowanceType
    {
        $query = AllowanceType::query()->active();

        if ($codeOrName) {
            $match = (clone $query)
                ->where(function ($subQuery) use ($codeOrName) {
                    $subQuery->where('code', $codeOrName)->orWhere('name', 'like', '%' . $codeOrName . '%');
                })
                ->orderBy('id')
                ->first();

            if ($match) {
                return $match;
            }
        }

        return $query->orderBy('id')->firstOrFail();
    }

    protected function formatBonusDeductionAdjustment(BonusDeduction $adjustment): array
    {
        return [
            'id' => $adjustment->id,
            'employee_id' => $adjustment->employee_id,
            'employee_name' => $adjustment->employee?->full_name,
            'department_id' => $adjustment->employee?->department_id,
            'department_name' => $adjustment->employee?->department?->name,
            'attendance_period_id' => $adjustment->attendance_period_id,
            'month' => $adjustment->attendancePeriod?->month,
            'year' => $adjustment->attendancePeriod?->year,
            'type' => $adjustment->type?->kind,
            'code' => $adjustment->type?->code,
            'type_name' => $adjustment->type?->name,
            'description' => $adjustment->description,
            'amount' => $this->floatValue($adjustment->amount),
            'status' => $adjustment->status,
        ];
    }

    protected function formatAllowanceAdjustment(ContractAllowance $adjustment): array
    {
        return [
            'id' => $this->encodeAllowanceId($adjustment->id),
            'employee_id' => $adjustment->contract?->employee_id,
            'employee_name' => $adjustment->contract?->employee?->full_name,
            'department_id' => $adjustment->contract?->employee?->department_id,
            'department_name' => $adjustment->contract?->employee?->department?->name,
            'attendance_period_id' => null,
            'month' => $adjustment->effective_from?->month,
            'year' => $adjustment->effective_from?->year,
            'type' => 'allowance',
            'code' => $adjustment->allowanceType?->code,
            'type_name' => $adjustment->allowanceType?->name,
            'description' => $adjustment->allowanceType?->name,
            'amount' => $this->floatValue($adjustment->amount),
            'status' => 'active',
        ];
    }

    protected function formatPeriodSummary(AttendancePeriod $period): array
    {
        $runs = $period->payrollRuns;

        return [
            'id' => $period->id,
            'period_code' => $period->period_code,
            'month' => (int) $period->month,
            'year' => (int) $period->year,
            'from_date' => $this->dateValue($period->from_date),
            'to_date' => $this->dateValue($period->to_date),
            'status' => $this->enumValue($period->status),
            'status_label' => $period->status instanceof AttendancePeriodStatus ? $period->status->label() : Str::headline((string) $period->status),
            'runs_count' => $runs->count(),
            'payslip_count' => $runs->sum(fn (PayrollRun $run) => $run->payslips->count()),
            'total_gross_salary' => round($runs->sum(fn (PayrollRun $run) => $run->payslips->sum(fn (Payslip $payslip) => $this->floatValue($payslip->gross_salary))), 2),
            'total_net_salary' => round($runs->sum(fn (PayrollRun $run) => $run->payslips->sum(fn (Payslip $payslip) => $this->floatValue($payslip->net_salary))), 2),
        ];
    }

    protected function formatPeriod(AttendancePeriod $period, ?PayrollRun $currentRun = null): array
    {
        $latestRun = $currentRun ?: $period->payrollRuns->sortByDesc('id')->first();

        return array_merge($this->formatPeriodSummary($period), [
            'label' => sprintf('Ky %02d/%d', $period->month, $period->year),
            'payroll_run' => $latestRun ? $this->formatRunSummary($latestRun) : null,
        ]);
    }

    protected function formatRun(PayrollRun $run): array
    {
        $payslips = $run->payslips;
        $totalEmployees = $payslips->count();
        $grossSalaryTotal = round($payslips->sum(fn (Payslip $payslip) => $this->floatValue($payslip->gross_salary)), 2);
        $netSalaryTotal = round($payslips->sum(fn (Payslip $payslip) => $this->floatValue($payslip->net_salary)), 2);
        $insuranceEmployeeTotal = round($payslips->sum(fn (Payslip $payslip) => $this->floatValue($payslip->insurance_employee)), 2);
        $pitTotal = round($payslips->sum(fn (Payslip $payslip) => $this->floatValue($payslip->pit_amount)), 2);
        $deductionTotal = round($payslips->sum(fn (Payslip $payslip) => $this->floatValue($payslip->deduction_total)), 2);

        return [
            'id' => $run->id,
            'run_id' => $run->id,
            'attendance_period_id' => $run->attendance_period_id,
            'period' => $run->attendancePeriod ? $this->formatPeriodSummary($run->attendancePeriod) : null,
            'month' => $run->attendancePeriod?->month,
            'year' => $run->attendancePeriod?->year,
            'run_no' => (int) $run->run_no,
            'scope_type' => $run->scope_type,
            'scope_value' => $run->scope_value,
            'status' => $this->enumValue($run->status),
            'status_label' => $run->status instanceof PayrollRunStatus ? $run->status->label() : Str::headline((string) $run->status),
            'requested_by' => $run->requested_by,
            'previewed_at' => $this->dateValue($run->previewed_at),
            'finalized_at' => $this->dateValue($run->finalized_at),
            'finalized_by' => $run->finalized_by,
            'locked_at' => $this->dateValue($run->locked_at),
            'locked_by' => $run->locked_by,
            'total_employees' => $totalEmployees,
            'gross_salary' => $grossSalaryTotal,
            'net_salary' => $netSalaryTotal,
            'insurance_employee' => $insuranceEmployeeTotal,
            'pit_amount' => $pitTotal,
            'summary' => [
                'payslip_count' => $totalEmployees,
                'total_employees' => $totalEmployees,
                'gross_salary_total' => $grossSalaryTotal,
                'total_gross_salary' => $grossSalaryTotal,
                'net_salary_total' => $netSalaryTotal,
                'total_net_salary' => $netSalaryTotal,
                'total_insurance_employee' => $insuranceEmployeeTotal,
                'total_pit' => $pitTotal,
                'deduction_total' => $deductionTotal,
            ],
            'payslips' => $payslips->map(fn (Payslip $payslip) => $this->formatPayslipSummary($payslip))->values()->all(),
        ];
    }

    protected function formatRunSummary(PayrollRun $run): array
    {
        return [
            'id' => $run->id,
            'run_no' => (int) $run->run_no,
            'scope_type' => $run->scope_type,
            'scope_value' => $run->scope_value,
            'status' => $this->enumValue($run->status),
            'payslip_count' => $run->payslips->count(),
            'gross_salary_total' => round($run->payslips->sum(fn (Payslip $payslip) => $this->floatValue($payslip->gross_salary)), 2),
            'net_salary_total' => round($run->payslips->sum(fn (Payslip $payslip) => $this->floatValue($payslip->net_salary)), 2),
        ];
    }

    protected function formatPayslipSummary(Payslip $payslip): array
    {
        return [
            'id' => $payslip->id,
            'attendance_period_id' => $payslip->attendance_period_id,
            'payroll_run_id' => $payslip->payroll_run_id,
            'employee_id' => $payslip->employee_id,
            'employee_code' => $payslip->employee?->employee_code,
            'employee_name' => $payslip->employee?->full_name,
            'employee' => [
                'id' => $payslip->employee?->id,
                'employee_code' => $payslip->employee?->employee_code,
                'full_name' => $payslip->employee?->full_name,
                'department_name' => $payslip->employee?->department?->name,
            ],
            'department_id' => $payslip->employee?->department_id,
            'department_name' => $payslip->employee?->department?->name,
            'contract_id' => $payslip->contract_id,
            'contract_no' => $payslip->contract?->contract_no,
            'month' => $payslip->payrollRun?->attendancePeriod?->month,
            'year' => $payslip->payrollRun?->attendancePeriod?->year,
            'base_salary_snapshot' => $this->floatValue($payslip->base_salary_snapshot),
            'gross_salary' => $this->floatValue($payslip->gross_salary),
            'taxable_income' => $this->floatValue($payslip->taxable_income),
            'insurance_base' => $this->floatValue($payslip->insurance_base),
            'insurance_employee' => $this->floatValue($payslip->insurance_employee),
            'insurance_company' => $this->floatValue($payslip->insurance_company),
            'pit_amount' => $this->floatValue($payslip->pit_amount),
            'bonus_total' => $this->floatValue($payslip->bonus_total),
            'deduction_total' => $this->floatValue($payslip->deduction_total),
            'net_salary' => $this->floatValue($payslip->net_salary),
            'status' => $this->enumValue($payslip->status),
            'generated_at' => $this->dateValue($payslip->generated_at),
            'locked_at' => $this->dateValue($payslip->locked_at),
            'payroll_run' => [
                'id' => $payslip->payrollRun?->id,
                'status' => $this->enumValue($payslip->payrollRun?->status),
                'attendance_period' => $payslip->payrollRun?->attendancePeriod ? [
                    'id' => $payslip->payrollRun->attendancePeriod->id,
                    'month' => (int) $payslip->payrollRun->attendancePeriod->month,
                    'year' => (int) $payslip->payrollRun->attendancePeriod->year,
                    'period_code' => $payslip->payrollRun->attendancePeriod->period_code,
                ] : null,
            ],
        ];
    }

    protected function formatPayslipDetail(Payslip $payslip): array
    {
        $items = $payslip->items->sortBy('sort_order')->values();

        return array_merge($this->formatPayslipSummary($payslip), [
            'period' => $payslip->payrollRun?->attendancePeriod ? $this->formatPeriod($payslip->payrollRun->attendancePeriod, $payslip->payrollRun) : null,
            'attendance' => $this->attendanceBreakdownFromPayslip($payslip),
            'contract' => [
                'id' => $payslip->contract?->id,
                'contract_no' => $payslip->contract?->contract_no,
                'base_salary' => $this->floatValue($payslip->contract?->base_salary),
                'probation_rate' => $this->floatValue($payslip->contract?->probation_rate),
                'allowances' => $payslip->contract?->allowances?->map(fn (ContractAllowance $allowance) => [
                    'id' => $allowance->id,
                    'code' => $allowance->allowanceType?->code,
                    'name' => $allowance->allowanceType?->name,
                    'amount' => $this->floatValue($allowance->amount),
                    'effective_from' => $this->dateValue($allowance->effective_from),
                    'effective_to' => $this->dateValue($allowance->effective_to),
                ])->values()->all() ?? [],
            ],
            'items' => $items->map(fn (PayslipItem $item) => $this->formatPayslipItem($item))->all(),
            'items_by_group' => $items->groupBy('item_group')->map(fn (Collection $group) => $group->map(fn (PayslipItem $item) => $this->formatPayslipItem($item))->values()->all())->all(),
        ]);
    }

    protected function formatPayslipItem(PayslipItem $item): array
    {
        return [
            'id' => $item->id,
            'code' => $item->item_code,
            'item_code' => $item->item_code,
            'name' => $item->item_name,
            'item_name' => $item->item_name,
            'group' => $item->item_group,
            'item_group' => $item->item_group,
            'qty' => $this->floatValue($item->qty),
            'rate' => $this->floatValue($item->rate),
            'amount' => $this->floatValue($item->amount),
            'sort_order' => (int) $item->sort_order,
            'source_ref' => $item->source_ref,
        ];
    }

    protected function formatPayrollParameter(PayrollParameter $parameter): array
    {
        return [
            'id' => $parameter->id,
            'code' => $parameter->code,
            'name' => $parameter->name,
            'description' => $parameter->description,
            'effective_from' => $this->dateValue($parameter->effective_from),
            'effective_to' => $this->dateValue($parameter->effective_to),
            'formula_json' => $parameter->formula_json,
            'details' => $parameter->details->map(fn ($detail) => [
                'id' => $detail->id,
                'param_key' => $detail->param_key,
                'param_type' => $detail->param_type,
                'default_value' => $this->scalarConfigValue($detail->default_value),
                'display_order' => (int) $detail->display_order,
            ])->values()->all(),
        ];
    }

    protected function formatAttendanceSummary(AttendanceMonthlySummary $summary): array
    {
        return [
            'id' => $summary->id,
            'attendance_period_id' => $summary->attendance_period_id,
            'employee_id' => $summary->employee_id,
            'total_workdays' => $this->floatValue($summary->total_workdays),
            'regular_hours' => $this->floatValue($summary->regular_hours),
            'ot_hours' => $this->floatValue($summary->ot_hours),
            'night_hours' => $this->floatValue($summary->night_hours),
            'paid_leave_days' => $this->floatValue($summary->paid_leave_days),
            'unpaid_leave_days' => $this->floatValue($summary->unpaid_leave_days),
            'late_minutes' => (int) $summary->late_minutes,
            'early_minutes' => (int) $summary->early_minutes,
            'meal_count' => (int) $summary->meal_count,
            'status' => $summary->status,
            'generated_at' => $this->dateValue($summary->generated_at),
            'confirmed_at' => $this->dateValue($summary->confirmed_at),
        ];
    }

    protected function aggregateAttendanceFromDaily(AttendancePeriod $period, EloquentCollection $dailyRows): array
    {
        return [
            'period_id' => $period->id,
            'total_workdays' => $this->floatValue($dailyRows->sum('workday_value')),
            'regular_hours' => $this->floatValue($dailyRows->sum('regular_hours')),
            'ot_hours' => $this->floatValue($dailyRows->sum('ot_hours')),
            'night_hours' => $this->floatValue($dailyRows->sum('night_hours')),
            'paid_leave_days' => 0.0,
            'unpaid_leave_days' => 0.0,
            'late_minutes' => (int) $dailyRows->sum('late_minutes'),
            'early_minutes' => (int) $dailyRows->sum('early_minutes'),
            'meal_count' => (int) $dailyRows->sum('meal_count'),
            'source' => 'attendance_daily',
        ];
    }

    protected function attendanceBreakdownFromPayslip(Payslip $payslip): array
    {
        $period = $payslip->payrollRun?->attendancePeriod;
        $summary = $period ? AttendanceMonthlySummary::query()
            ->where('attendance_period_id', $period->id)
            ->where('employee_id', $payslip->employee_id)
            ->first() : null;

        if ($summary) {
            return $this->formatAttendanceSummary($summary);
        }

        $daily = AttendancePeriod::query()->whereKey($period?->id)->exists()
            ? $payslip->employee->attendanceDaily()->where('attendance_period_id', $period->id)->get()
            : collect();

        return $this->aggregateAttendanceFromDaily($period ?? new AttendancePeriod(), $daily);
    }

}
