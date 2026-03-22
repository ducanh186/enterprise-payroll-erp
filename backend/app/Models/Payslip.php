<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Payslip — individual salary slip for one employee within a payroll run.
 *
 * @property int $id
 * @property int $attendance_period_id
 * @property int $employee_id
 * @property int $payroll_run_id
 * @property int|null $contract_id
 * @property float $base_salary_snapshot
 * @property float $gross_salary
 * @property float $taxable_income
 * @property float $insurance_base
 * @property float $insurance_employee
 * @property float $insurance_company
 * @property float $pit_amount
 * @property float $bonus_total
 * @property float $deduction_total
 * @property float $net_salary
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $generated_at
 * @property \Illuminate\Support\Carbon|null $locked_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\AttendancePeriod $attendancePeriod
 * @property-read \App\Models\Employee $employee
 * @property-read \App\Models\PayrollRun $payrollRun
 * @property-read \App\Models\LabourContract|null $contract
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PayslipItem> $items
 *
 * @method static Builder|Payslip byPeriod(int $periodId)
 * @method static Builder|Payslip byEmployee(int $employeeId)
 */
class Payslip extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payslips';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'attendance_period_id',
        'employee_id',
        'payroll_run_id',
        'contract_id',
        'base_salary_snapshot',
        'gross_salary',
        'taxable_income',
        'insurance_base',
        'insurance_employee',
        'insurance_company',
        'pit_amount',
        'bonus_total',
        'deduction_total',
        'net_salary',
        'status',
        'generated_at',
        'locked_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_salary_snapshot' => 'decimal:2',
            'gross_salary'        => 'decimal:2',
            'taxable_income'      => 'decimal:2',
            'insurance_base'      => 'decimal:2',
            'insurance_employee'  => 'decimal:2',
            'insurance_company'   => 'decimal:2',
            'pit_amount'          => 'decimal:2',
            'bonus_total'         => 'decimal:2',
            'deduction_total'     => 'decimal:2',
            'net_salary'          => 'decimal:2',
            'generated_at'        => 'datetime',
            'locked_at'           => 'datetime',
        ];
    }

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    /**
     * The attendance period this payslip belongs to.
     */
    public function attendancePeriod(): BelongsTo
    {
        return $this->belongsTo(AttendancePeriod::class);
    }

    /**
     * The employee this payslip was generated for.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * The payroll run that generated this payslip.
     */
    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    /**
     * The labour contract active at the time of payroll calculation.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(LabourContract::class, 'contract_id');
    }

    /**
     * Line items (earnings, deductions, taxes) on this payslip.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PayslipItem::class);
    }

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------

    /**
     * Filter by attendance period.
     */
    public function scopeByPeriod(Builder $query, int $periodId): Builder
    {
        return $query->where('attendance_period_id', $periodId);
    }

    /**
     * Filter by employee.
     */
    public function scopeByEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }
}
