<?php

namespace App\Models;

use App\Enums\PayrollRunStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Payroll run — a batch payroll calculation for a given attendance period.
 *
 * @property int $id
 * @property int $attendance_period_id
 * @property int $run_no
 * @property string $scope_type
 * @property string|null $scope_value
 * @property PayrollRunStatus $status
 * @property int|null $requested_by
 * @property \Illuminate\Support\Carbon|null $previewed_at
 * @property \Illuminate\Support\Carbon|null $finalized_at
 * @property int|null $finalized_by
 * @property \Illuminate\Support\Carbon|null $locked_at
 * @property int|null $locked_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\AttendancePeriod $attendancePeriod
 * @property-read \App\Models\User|null $requester
 * @property-read \App\Models\User|null $finalizer
 * @property-read \App\Models\User|null $locker
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payslip> $payslips
 *
 * @method static Builder|PayrollRun byStatus(PayrollRunStatus $status)
 * @method static Builder|PayrollRun byPeriod(int $periodId)
 */
class PayrollRun extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payroll_runs';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'attendance_period_id',
        'run_no',
        'scope_type',
        'scope_value',
        'status',
        'requested_by',
        'previewed_at',
        'finalized_at',
        'finalized_by',
        'locked_at',
        'locked_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status'       => PayrollRunStatus::class,
            'previewed_at' => 'datetime',
            'finalized_at' => 'datetime',
            'locked_at'    => 'datetime',
        ];
    }

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    /**
     * The attendance period this run belongs to.
     */
    public function attendancePeriod(): BelongsTo
    {
        return $this->belongsTo(AttendancePeriod::class);
    }

    /**
     * The user who requested this payroll run.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * The user who finalized this payroll run.
     */
    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    /**
     * The user who locked this payroll run.
     */
    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /**
     * Payslips generated in this run.
     */
    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------

    /**
     * Filter by payroll run status.
     */
    public function scopeByStatus(Builder $query, PayrollRunStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Filter by attendance period.
     */
    public function scopeByPeriod(Builder $query, int $periodId): Builder
    {
        return $query->where('attendance_period_id', $periodId);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /**
     * Check whether this run can transition to the given status.
     */
    public function canTransitionTo(PayrollRunStatus $status): bool
    {
        return $this->status->canTransitionTo($status);
    }
}
