<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LateEarlyRule extends Model
{
    protected $table = 'late_early_rules';

    protected $fillable = [
        'code',
        'name',
        'from_minute',
        'to_minute',
        'deduction_type',
        'deduction_value',
        'exclude_meal',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'deduction_value' => 'decimal:2',
        'exclude_meal'    => 'boolean',
        'effective_from'  => 'date',
        'effective_to'    => 'date',
    ];
}
