<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    protected $table = 'shifts';

    protected $fillable = [
        'code', 'name', 'description',
        'is_checkin', 'is_checkout',
        'start_time', 'start_time_valid1', 'start_time_valid2',
        'end_time', 'end_time_valid1', 'end_time_valid2',
        'break_start_time', 'start_break_time_valid1', 'start_break_time_valid2',
        'break_end_time', 'end_break_time_valid1', 'end_break_time_valid2',
        'shift_break', 'shift_break_mins',
        'workday_value', 'working_hours', 'min_meal_hours', 'shift_meal',
        'start_working_night_time', 'end_working_night_time',
        'timesheet_type', 'is_overnight',
        'grace_late_minutes', 'grace_early_minutes',
        'status', 'is_active',
    ];

    protected $casts = [
        'workday_value'  => 'decimal:2',
        'working_hours'  => 'decimal:2',
        'is_overnight'   => 'boolean',
        'is_checkin'     => 'boolean',
        'is_checkout'    => 'boolean',
        'is_active'      => 'boolean',
        'min_meal_hours' => 'decimal:2',
        'shift_break'    => 'integer',
        'shift_break_mins' => 'integer',
        'shift_meal'     => 'integer',
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
