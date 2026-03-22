<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractType extends Model
{
    protected $table = 'contract_types';

    protected $fillable = [
        'code',
        'name',
        'duration_months',
        'is_probationary',
    ];

    protected $casts = [
        'is_probationary' => 'boolean',
    ];

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    public function contracts(): HasMany
    {
        return $this->hasMany(LabourContract::class, 'contract_type_id');
    }
}
