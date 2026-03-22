<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeLog extends Model
{
    /**
     * The time_logs table only has created_at (no updated_at).
     */
    const UPDATED_AT = null;

    protected $table = 'time_logs';

    protected $fillable = [
        'employee_id',
        'log_time',
        'machine_number',
        'log_type',
        'source',
        'is_valid',
        'invalid_reason',
        'raw_ref',
    ];

    protected $casts = [
        'log_time' => 'datetime',
        'is_valid' => 'boolean',
    ];

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /* ------------------------------------------------------------------ */
    /*  Scopes                                                             */
    /* ------------------------------------------------------------------ */

    public function scopeValid(Builder $query): Builder
    {
        return $query->where('is_valid', true);
    }

    public function scopeByEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByDateRange(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('log_time', [$from, $to]);
    }
}
