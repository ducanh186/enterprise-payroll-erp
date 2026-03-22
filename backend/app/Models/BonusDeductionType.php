<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bonus/deduction type — master data defining categories of bonuses and deductions.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $kind
 * @property bool $is_taxable
 * @property bool $is_insurance_base
 * @property bool $is_recurring
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BonusDeduction> $bonusDeductions
 *
 * @method static Builder|BonusDeductionType byKind(string $kind)
 * @method static Builder|BonusDeductionType taxable()
 */
class BonusDeductionType extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bonus_deduction_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'kind',
        'is_taxable',
        'is_insurance_base',
        'is_recurring',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_taxable'       => 'boolean',
            'is_insurance_base' => 'boolean',
            'is_recurring'     => 'boolean',
        ];
    }

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    /**
     * Bonus/deduction entries using this type.
     */
    public function bonusDeductions(): HasMany
    {
        return $this->hasMany(BonusDeduction::class, 'type_id');
    }

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------

    /**
     * Filter by kind (e.g. 'bonus' or 'deduction').
     */
    public function scopeByKind(Builder $query, string $kind): Builder
    {
        return $query->where('kind', $kind);
    }

    /**
     * Filter to taxable types only.
     */
    public function scopeTaxable(Builder $query): Builder
    {
        return $query->where('is_taxable', true);
    }
}
