<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class D20PayrollParameter extends Model
{
    public const TYPES_VALUE_TAB  = ['VALUE', 'RATE', 'COEFF'];
    public const TYPES_SALARY_TAB = ['INCOME', 'BONUS', 'DEDUCTION'];

    protected $table = 'd20_payroll_parameters';

    protected $fillable = [
        'parameter', 'name', 'description',
        'effective_date', 'type', 'amount', 'is_active',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'amount'         => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    public function scopeValueTab(Builder $q): Builder
    {
        return $q->whereIn('type', self::TYPES_VALUE_TAB);
    }

    public function scopeSalaryTab(Builder $q): Builder
    {
        return $q->whereIn('type', self::TYPES_SALARY_TAB);
    }
}
