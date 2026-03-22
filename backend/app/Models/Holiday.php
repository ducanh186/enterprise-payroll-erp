<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $table = 'holidays';

    protected $fillable = [
        'holiday_date',
        'name',
        'multiplier',
        'is_paid',
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'multiplier'   => 'decimal:1',
        'is_paid'      => 'boolean',
    ];
}
