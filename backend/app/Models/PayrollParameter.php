<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Payroll parameter — configurable parameters used in payroll calculations.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $effective_from
 * @property \Illuminate\Support\Carbon|null $effective_to
 * @property array|null $formula_json
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PayrollParameterDetail> $details
 *
 * @method static Builder|PayrollParameter active()
 * @method static Builder|PayrollParameter effective(?Carbon $date = null)
 */
class PayrollParameter extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payroll_parameters';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'effective_from',
        'effective_to',
        'formula_json',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to'   => 'date',
            'formula_json'   => 'array',
        ];
    }

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    /**
     * Detail rows (keys/values) belonging to this parameter set.
     */
    public function details(): HasMany
    {
        return $this->hasMany(PayrollParameterDetail::class);
    }

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------

    /**
     * Filter to parameters with active status.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Filter to parameters effective on a given date (defaults to today).
     */
    public function scopeEffective(Builder $query, CarbonInterface|string|null $date = null): Builder
    {
        $date = match (true) {
            $date instanceof CarbonInterface => Carbon::instance($date),
            is_string($date) && $date !== '' => Carbon::parse($date),
            default => Carbon::today(),
        };

        return $query
            ->where('effective_from', '<=', $date)
            ->where(function (Builder $q) use ($date) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $date);
            });
    }
}
