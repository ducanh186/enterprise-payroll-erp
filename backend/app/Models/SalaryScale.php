<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryScale extends Model
{
    protected $table = 'salary_scales';

    protected $fillable = ['code', 'name', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function grades(): HasMany
    {
        return $this->hasMany(SalaryGrade::class, 'scale_code', 'code');
    }
}
