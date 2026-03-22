<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    protected $table = 'shifts';

    protected $fillable = [
        'code',
        'name',
        'start_time',
        'end_time',
        'break_start_time',
        'break_end_time',
        'workday_value',
        'timesheet_type',
        'is_overnight',
        'min_meal_hours',
        'grace_late_minutes',
        'grace_early_minutes',
        'status',
    ];

    protected $casts = [
        'workday_value'  => 'decimal:1',
        'is_overnight'   => 'boolean',
        'min_meal_hours' => 'decimal:1',
    ];

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    public function shiftAssignments(): HasMany
    {
        return $this->hasMany(ShiftAssignment::class);
    }

    /* ------------------------------------------------------------------ */
    /*  Scopes                                                             */
    /* ------------------------------------------------------------------ */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
