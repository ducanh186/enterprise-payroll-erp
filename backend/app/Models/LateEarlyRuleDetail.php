<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LateEarlyRuleDetail extends Model
{
    protected $table = 'late_early_rule_details';

    protected $fillable = [
        'row_id', 'late_early_rule_id', 'description',
        'start_minute', 'end_minute',
        'exclude_time', 'exclude_workday',
    ];

    protected $casts = [
        'start_minute'    => 'integer',
        'end_minute'      => 'integer',
        'exclude_time'    => 'decimal:2',
        'exclude_workday' => 'decimal:2',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(LateEarlyRule::class);
    }
}
