<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bonus/deduction — individual bonus or deduction entry for an employee in a period.
 *
 * @property int $id
 * @property int $employee_id
 * @property int $attendance_period_id
 * @property int $type_id
 * @property float $amount
 * @property string|null $description
 * @property string $status
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Employee $employee
 * @property-read \App\Models\AttendancePeriod $attendancePeriod
 * @property-read \App\Models\BonusDeductionType $type
 * @property-read \App\Models\User|null $creator
 *
 * @method static Builder|BonusDeduction byPeriod(int $periodId)
 * @method static Builder|BonusDeduction active()
 */
class BonusDeduction extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bonus_deductions';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'attendance_period_id',
        'type_id',
        'amount',
        'description',
        'status',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    /**
     * The employee this bonus/deduction belongs to.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * The attendance period this entry belongs to.
     */
    public function attendancePeriod(): BelongsTo
    {
        return $this->belongsTo(AttendancePeriod::class);
    }

    /**
     * The bonus/deduction type.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(BonusDeductionType::class, 'type_id');
    }

    /**
     * The user who created this entry.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
     * Filter to active entries only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
