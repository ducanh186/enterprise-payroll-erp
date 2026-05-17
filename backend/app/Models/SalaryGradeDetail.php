<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryGradeDetail extends Model
{
    protected $table = 'salary_grade_details';

    protected $fillable = ['row_id', 'salary_grade_id', 'salary_type', 'amount', 'description'];

    protected $casts = [
        'salary_type' => 'integer',
        'amount'      => 'decimal:2',
    ];

    public function grade(): BelongsTo
    {
        return $this->belongsTo(SalaryGrade::class);
    }
}
