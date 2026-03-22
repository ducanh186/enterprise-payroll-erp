<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractAllowance extends Model
{
    protected $table = 'contract_allowances';

    protected $fillable = [
        'contract_id',
        'allowance_type_id',
        'amount',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'effective_from' => 'date',
        'effective_to'   => 'date',
    ];

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    public function contract(): BelongsTo
    {
        return $this->belongsTo(LabourContract::class, 'contract_id');
    }

    public function allowanceType(): BelongsTo
    {
        return $this->belongsTo(AllowanceType::class);
    }
}
