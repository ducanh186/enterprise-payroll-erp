<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attendance monthly summary — aggregated attendance data for an employee within a period.
 *
 * @property int $id
 * @property int $attendance_period_id
 * @property int $employee_id
 * @property float $total_workdays
 * @property float $regular_hours
 * @property float $ot_hours
 * @property float $night_hours
 * @property float $paid_leave_days
 * @property float $unpaid_leave_days
 * @property int $late_minutes
 * @property int $early_minutes
 * @property int $meal_count
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $generated_at
 * @property \Illuminate\Support\Carbon|null $confirmed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\AttendancePeriod $attendancePeriod
 * @property-read \App\Models\Employee $employee
 *
 * @method static Builder|AttendanceMonthlySummary byPeriod(int $periodId)
 * @method static Builder|AttendanceMonthlySummary byEmployee(int $employeeId)
 */
class AttendanceMonthlySummary extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'attendance_monthly_summary';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'attendance_period_id',
        'employee_id',
        'total_workdays',
        'regular_hours',
        'ot_hours',
        'night_hours',
        'paid_leave_days',
        'unpaid_leave_days',
        'late_minutes',
        'early_minutes',
        'meal_count',
        'status',
        'generated_at',
        'confirmed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_workdays'    => 'decimal:1',
            'regular_hours'     => 'decimal:1',
            'ot_hours'          => 'decimal:1',
            'night_hours'       => 'decimal:1',
            'paid_leave_days'   => 'decimal:1',
            'unpaid_leave_days' => 'decimal:1',
            'generated_at'      => 'datetime',
            'confirmed_at'      => 'datetime',
        ];
    }

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    /**
     * The attendance period this summary belongs to.
     */
    public function attendancePeriod(): BelongsTo
    {
        return $this->belongsTo(AttendancePeriod::class);
    }

    /**
     * The employee this summary belongs to.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------

    /**
     * Filter summaries by attendance period.
     */
    public function scopeByPeriod(Builder $query, int $periodId): Builder
    {
        return $query->where('attendance_period_id', $periodId);
    }

    /**
     * Filter summaries by employee.
     */
    public function scopeByEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }
}
