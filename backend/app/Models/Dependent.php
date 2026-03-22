<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dependent model — tax-deductible dependent of an employee.
 *
 * Used for Vietnamese PIT (Personal Income Tax) dependent deductions.
 *
 * @property int $id
 * @property int $employee_id
 * @property string $full_name
 * @property \Illuminate\Support\Carbon|null $dob
 * @property string $relationship
 * @property string|null $national_id
 * @property \Illuminate\Support\Carbon|null $tax_reduction_from
 * @property \Illuminate\Support\Carbon|null $tax_reduction_to
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Employee $employee
 */
class Dependent extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'dependents';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'full_name',
        'dob',
        'relationship',
        'national_id',
        'tax_reduction_from',
        'tax_reduction_to',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'tax_reduction_from' => 'date',
            'tax_reduction_to' => 'date',
        ];
    }

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    /**
     * The employee this dependent belongs to.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
