<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollType extends Model
{
    protected $table = 'payroll_types';

    protected $fillable = [
        'code',
        'name',
        'is_probationary',
    ];

    protected $casts = [
        'is_probationary' => 'boolean',
    ];

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    public function salaryLevels(): HasMany
    {
        return $this->hasMany(SalaryLevel::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(LabourContract::class);
    }
}
