<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRequestDetail extends Model
{
    /**
     * This table has no timestamps columns.
     */
    public $timestamps = false;

    protected $table = 'attendance_request_details';

    protected $fillable = [
        'request_id',
        'work_date',
        'requested_check_in',
        'requested_check_out',
        'requested_hours',
        'note',
    ];

    protected $casts = [
        'work_date'           => 'date',
        'requested_check_in'  => 'datetime',
        'requested_check_out' => 'datetime',
        'requested_hours'     => 'decimal:1',
    ];

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    public function request(): BelongsTo
    {
        return $this->belongsTo(AttendanceRequest::class, 'request_id');
    }
}
