<?php

namespace App\Models;

use App\Enums\ContractStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabourContract extends Model
{
    protected $table = 'labour_contracts';

    protected $fillable = [
        'employee_id',
        'contract_no',
        'contract_type_id',
        'position_title_snapshot',
        'department_snapshot',
        'start_date',
        'end_date',
        'sign_date',
        'status',
        'base_salary',
        'salary_level_id',
        'payroll_type_id',
        'probation_rate',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'start_date'     => 'date',
        'end_date'       => 'date',
        'sign_date'      => 'date',
        'status'         => ContractStatus::class,
        'base_salary'    => 'decimal:2',
        'probation_rate' => 'decimal:2',
    ];

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    public function payrollType(): BelongsTo
    {
        return $this->belongsTo(PayrollType::class);
    }

    public function salaryLevel(): BelongsTo
    {
        return $this->belongsTo(SalaryLevel::class);
    }

    public function allowances(): HasMany
    {
        return $this->hasMany(ContractAllowance::class, 'contract_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /* ------------------------------------------------------------------ */
    /*  Scopes                                                             */
    /* ------------------------------------------------------------------ */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ContractStatus::ACTIVE);
    }

    public function scopeByEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }
}
