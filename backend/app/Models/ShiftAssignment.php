<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftAssignment extends Model
{
    protected $table = 'shift_assignments';

    protected $fillable = [
        'employee_id', 'work_date', 'shift_id', 'source', 'note',
        'start_date', 'end_date',
        'include_mon', 'include_tue', 'include_wed', 'include_thu',
        'include_fri', 'include_sat', 'include_sun',
    ];

    protected $casts = [
        'work_date'   => 'date',
        'start_date'  => 'date',
        'end_date'    => 'date',
        'include_mon' => 'boolean',
        'include_tue' => 'boolean',
        'include_wed' => 'boolean',
        'include_thu' => 'boolean',
        'include_fri' => 'boolean',
        'include_sat' => 'boolean',
        'include_sun' => 'boolean',
    ];

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }
}
