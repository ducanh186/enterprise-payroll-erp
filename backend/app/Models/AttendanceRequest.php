<?php

namespace App\Models;

use App\Enums\AttendanceRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRequest extends Model
{
    protected $table = 'attendance_requests';

    protected $fillable = [
        'employee_id',
        'request_type',
        'from_date',
        'to_date',
        'reason',
        'status',
        'submitted_at',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'from_date'    => 'date',
        'to_date'      => 'date',
        'status'       => AttendanceRequestStatus::class,
        'submitted_at' => 'datetime',
        'approved_at'  => 'datetime',
    ];

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(AttendanceRequestDetail::class, 'request_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /* ------------------------------------------------------------------ */
    /*  Scopes                                                             */
    /* ------------------------------------------------------------------ */

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', AttendanceRequestStatus::PENDING);
    }

    public function scopeByEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }
}
