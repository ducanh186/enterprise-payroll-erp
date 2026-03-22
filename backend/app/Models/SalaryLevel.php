<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryLevel extends Model
{
    protected $table = 'salary_levels';

    protected $fillable = [
        'payroll_type_id',
        'code',
        'level_no',
        'amount',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'effective_from' => 'date',
        'effective_to'   => 'date',
    ];

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    public function payrollType(): BelongsTo
    {
        return $this->belongsTo(PayrollType::class);
    }
}
