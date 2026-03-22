<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Payslip item — individual line item (earning, deduction, tax) on a payslip.
 *
 * @property int $id
 * @property int $payslip_id
 * @property string $item_code
 * @property string $item_name
 * @property string $item_group
 * @property float|null $qty
 * @property float|null $rate
 * @property float $amount
 * @property int $sort_order
 * @property string|null $source_ref
 *
 * @property-read \App\Models\Payslip $payslip
 */
class PayslipItem extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payslip_items';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'payslip_id',
        'item_code',
        'item_name',
        'item_group',
        'qty',
        'rate',
        'amount',
        'sort_order',
        'source_ref',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty'        => 'decimal:2',
            'rate'       => 'decimal:2',
            'amount'     => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    /**
     * The payslip this item belongs to.
     */
    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }
}
