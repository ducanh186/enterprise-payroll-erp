<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $label
 * @property string $procedure_name
 * @property string $module
 * @property string|null $description
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static Builder|ProcedureCatalog active()
 */
class ProcedureCatalog extends Model
{
    protected $table = 'procedure_catalog';

    protected $fillable = [
        'code',
        'label',
        'procedure_name',
        'module',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function parameters(): HasMany
    {
        return $this->hasMany(ProcedureParameter::class, 'procedure_id')->orderBy('sort_order');
    }

    public function columns(): HasMany
    {
        return $this->hasMany(ProcedureColumn::class, 'procedure_id')->orderBy('sort_order');
    }

    public function executionLogs(): HasMany
    {
        return $this->hasMany(ProcedureExecutionLog::class, 'procedure_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
