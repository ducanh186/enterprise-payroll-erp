<?php

namespace App\Models;

use App\Enums\AttendancePeriodStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendancePeriod extends Model
{
    protected $table = 'attendance_periods';

    protected $fillable = [
        'period_code',
        'month',
        'year',
        'from_date',
        'to_date',
        'status',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date'   => 'date',
        'status'    => AttendancePeriodStatus::class,
    ];

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    public function attendanceDaily(): HasMany
    {
        return $this->hasMany(AttendanceDaily::class);
    }

    public function monthlySummaries(): HasMany
    {
        return $this->hasMany(AttendanceMonthlySummary::class);
    }

    public function payrollRuns(): HasMany
    {
        return $this->hasMany(PayrollRun::class);
    }

    public function bonusDeductions(): HasMany
    {
        return $this->hasMany(BonusDeduction::class);
    }

    /* ------------------------------------------------------------------ */
    /*  Scopes                                                             */
    /* ------------------------------------------------------------------ */

    public function scopeByYear(Builder $query, int $year): Builder
    {
        return $query->where('year', $year);
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * Check whether this period's status can transition to the given target status.
     */
    public function canTransitionTo(AttendancePeriodStatus $status): bool
    {
        return $this->status->canTransitionTo($status);
    }
}
