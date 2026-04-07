<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $procedure_id
 * @property string $name
 * @property string $sp_param_name
 * @property string $type
 * @property string|null $label
 * @property bool $required
 * @property string|null $default_value
 * @property int $sort_order
 */
class ProcedureParameter extends Model
{
    protected $table = 'procedure_parameters';

    protected $fillable = [
        'procedure_id',
        'name',
        'sp_param_name',
        'type',
        'label',
        'required',
        'default_value',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(ProcedureCatalog::class, 'procedure_id');
    }
}
