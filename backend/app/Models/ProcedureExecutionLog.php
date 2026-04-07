<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $procedure_id
 * @property int|null $user_id
 * @property array|null $parameters
 * @property int $row_count
 * @property int $execution_ms
 * @property string $status
 * @property string|null $error_message
 * @property string|null $ip_address
 * @property \Illuminate\Support\Carbon $executed_at
 */
class ProcedureExecutionLog extends Model
{
    public $timestamps = false;

    protected $table = 'procedure_execution_logs';

    protected $fillable = [
        'procedure_id',
        'user_id',
        'parameters',
        'row_count',
        'execution_ms',
        'status',
        'error_message',
        'ip_address',
        'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'row_count' => 'integer',
            'execution_ms' => 'integer',
            'executed_at' => 'datetime',
        ];
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(ProcedureCatalog::class, 'procedure_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
