<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AllowanceType extends Model
{
    protected $table = 'allowance_types';

    protected $fillable = [
        'code',
        'name',
        'is_taxable',
        'is_insurance_base',
        'default_amount',
        'status',
    ];

    protected $casts = [
        'is_taxable'       => 'boolean',
        'is_insurance_base' => 'boolean',
        'default_amount'   => 'decimal:2',
    ];

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    public function contractAllowances(): HasMany
    {
        return $this->hasMany(ContractAllowance::class);
    }

    /* ------------------------------------------------------------------ */
    /*  Scopes                                                             */
    /* ------------------------------------------------------------------ */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
