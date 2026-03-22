<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Payroll parameter detail — individual key/value rows belonging to a parameter set.
 *
 * @property int $id
 * @property int $payroll_parameter_id
 * @property string $param_key
 * @property string $param_type
 * @property string|null $default_value
 * @property string|null $validation_rule
 * @property int $display_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\PayrollParameter $payrollParameter
 */
class PayrollParameterDetail extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payroll_parameter_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'payroll_parameter_id',
        'param_key',
        'param_type',
        'default_value',
        'validation_rule',
        'display_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
        ];
    }

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    /**
     * The payroll parameter set this detail belongs to.
     */
    public function payrollParameter(): BelongsTo
    {
        return $this->belongsTo(PayrollParameter::class);
    }
}
