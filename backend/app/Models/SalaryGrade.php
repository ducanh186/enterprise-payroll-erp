<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryGrade extends Model
{
    protected $table = 'salary_grades';

    protected $fillable = ['scale_code', 'effective_date', 'salary_level', 'description'];

    protected $casts = [
        'effective_date' => 'date',
        'salary_level'   => 'integer',
    ];

    public function scale(): BelongsTo
    {
        return $this->belongsTo(SalaryScale::class, 'scale_code', 'code');
    }

    public function details(): HasMany
    {
        return $this->hasMany(SalaryGradeDetail::class);
    }
}
