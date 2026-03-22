<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit log — immutable record of user actions for compliance and traceability.
 *
 * @property int $id
 * @property int|null $actor_user_id
 * @property string $module
 * @property string $action
 * @property string|null $ref_table
 * @property int|null $ref_id
 * @property array|null $before_json
 * @property array|null $after_json
 * @property string|null $ip_address
 * @property \Illuminate\Support\Carbon $created_at
 *
 * @property-read \App\Models\User|null $actor
 *
 * @method static Builder|AuditLog byModule(string $module)
 * @method static Builder|AuditLog byActor(int $userId)
 */
class AuditLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'audit_logs';

    /**
     * Indicates if the model should be timestamped.
     * Audit logs only have created_at, no updated_at.
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
        'actor_user_id',
        'module',
        'action',
        'ref_table',
        'ref_id',
        'before_json',
        'after_json',
        'ip_address',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'before_json' => 'array',
            'after_json'  => 'array',
            'created_at'  => 'datetime',
        ];
    }

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    /**
     * The user who performed the action.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------

    /**
     * Filter by module name.
     */
    public function scopeByModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    /**
     * Filter by actor user ID.
     */
    public function scopeByActor(Builder $query, int $userId): Builder
    {
        return $query->where('actor_user_id', $userId);
    }
}
