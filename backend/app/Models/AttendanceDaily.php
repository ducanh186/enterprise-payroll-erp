<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceDaily extends Model
{
    protected $table = 'attendance_daily';

    protected $fillable = [
        'employee_id',
        'work_date',
        'attendance_period_id',
        'shift_assignment_id',
        'first_in',
        'last_out',
        'late_minutes',
        'early_minutes',
        'regular_hours',
        'ot_hours',
        'night_hours',
        'workday_value',
        'meal_count',
        'attendance_status',
        'source_status',
        'is_confirmed_by_employee',
        'confirmed_at',
        'confirmed_by',
        'calculation_version',
    ];

    protected $casts = [
        'work_date'                => 'date',
        'first_in'                 => 'datetime',
        'last_out'                 => 'datetime',
        'regular_hours'            => 'decimal:1',
        'ot_hours'                 => 'decimal:1',
        'night_hours'              => 'decimal:1',
        'workday_value'            => 'decimal:1',
        'attendance_status'        => AttendanceStatus::class,
        'is_confirmed_by_employee' => 'boolean',
        'confirmed_at'             => 'datetime',
    ];

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendancePeriod(): BelongsTo
    {
        return $this->belongsTo(AttendancePeriod::class);
    }

    public function shiftAssignment(): BelongsTo
    {
        return $this->belongsTo(ShiftAssignment::class);
    }

    /* ------------------------------------------------------------------ */
    /*  Scopes                                                             */
    /* ------------------------------------------------------------------ */

    public function scopeByPeriod(Builder $query, int $periodId): Builder
    {
        return $query->where('attendance_period_id', $periodId);
    }

    public function scopeByEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }
}
