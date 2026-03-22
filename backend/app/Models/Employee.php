<?php

namespace App\Models;

use App\Enums\ContractStatus;
use App\Enums\EmploymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Employee model — core HR record for each staff member.
 *
 * @property int $id
 * @property string $employee_code
 * @property int|null $user_id
 * @property string $full_name
 * @property \Illuminate\Support\Carbon|null $dob
 * @property string|null $gender
 * @property string|null $national_id
 * @property string|null $tax_code
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $bank_account_no
 * @property string|null $bank_name
 * @property int|null $department_id
 * @property int|null $position_id
 * @property \Illuminate\Support\Carbon|null $join_date
 * @property EmploymentStatus $employment_status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\Department|null $department
 * @property-read \App\Models\Position|null $position
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Dependent> $dependents
 * @property-read \Illuminate\Database\Eloquent\Collection<int, LabourContract> $contracts
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ShiftAssignment> $shiftAssignments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TimeLog> $timeLogs
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AttendanceRequest> $attendanceRequests
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AttendanceDaily> $attendanceDaily
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AttendanceMonthlySummary> $monthlySummaries
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Payslip> $payslips
 * @property-read \Illuminate\Database\Eloquent\Collection<int, BonusDeduction> $bonusDeductions
 *
 * @method static Builder|Employee active()
 * @method static Builder|Employee byDepartment(int $departmentId)
 */
class Employee extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'employees';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_code',
        'user_id',
        'full_name',
        'dob',
        'gender',
        'national_id',
        'tax_code',
        'email',
        'phone',
        'bank_account_no',
        'bank_name',
        'department_id',
        'position_id',
        'join_date',
        'employment_status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'join_date' => 'date',
            'employment_status' => EmploymentStatus::class,
        ];
    }

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    /**
     * The user account linked to this employee.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The department this employee belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * The position this employee holds.
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Tax dependents registered for this employee.
     */
    public function dependents(): HasMany
    {
        return $this->hasMany(Dependent::class);
    }

    /**
     * Labour contracts for this employee.
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(LabourContract::class);
    }

    /**
     * Shift assignments for this employee.
     */
    public function shiftAssignments(): HasMany
    {
        return $this->hasMany(ShiftAssignment::class);
    }

    /**
     * Raw time log entries (clock in/out) for this employee.
     */
    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }

    /**
     * Attendance requests (leave, OT, correction) submitted by this employee.
     */
    public function attendanceRequests(): HasMany
    {
        return $this->hasMany(AttendanceRequest::class);
    }

    /**
     * Daily attendance calculation records for this employee.
     */
    public function attendanceDaily(): HasMany
    {
        return $this->hasMany(AttendanceDaily::class);
    }

    /**
     * Monthly attendance summary records for this employee.
     */
    public function monthlySummaries(): HasMany
    {
        return $this->hasMany(AttendanceMonthlySummary::class);
    }

    /**
     * Payslips generated for this employee.
     */
    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    /**
     * Bonus and deduction entries for this employee.
     */
    public function bonusDeductions(): HasMany
    {
        return $this->hasMany(BonusDeduction::class);
    }

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------

    /**
     * Filter to employees with active employment status.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('employment_status', EmploymentStatus::ACTIVE);
    }

    /**
     * Filter employees by department ID.
     */
    public function scopeByDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /**
     * Get the latest active labour contract for this employee.
     *
     * Returns null if no active contract exists.
     */
    public function getActiveContract(): ?LabourContract
    {
        return $this->contracts()
            ->where('status', ContractStatus::ACTIVE)
            ->orderByDesc('start_date')
            ->first();
    }
}
