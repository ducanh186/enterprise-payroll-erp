<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $procedure_id
 * @property string $key
 * @property string $label
 * @property string $type
 * @property bool $visible
 * @property bool $exportable
 * @property int $sort_order
 */
class ProcedureColumn extends Model
{
    protected $table = 'procedure_columns';

    protected $fillable = [
        'procedure_id',
        'key',
        'label',
        'type',
        'visible',
        'exportable',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'visible' => 'boolean',
            'exportable' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(ProcedureCatalog::class, 'procedure_id');
    }
}
