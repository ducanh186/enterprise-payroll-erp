<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * System config — key-value configuration store for application settings.
 *
 * @property int $id
 * @property string $config_key
 * @property string|null $config_value
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static Builder|SystemConfig byKey(string $key)
 */
class SystemConfig extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'system_configs';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'config_key',
        'config_value',
        'description',
    ];

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------

    /**
     * Filter by config key.
     */
    public function scopeByKey(Builder $query, string $key): Builder
    {
        return $query->where('config_key', $key);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /**
     * Get a config value by key, with an optional default.
     */
    public static function getValue(string $key, ?string $default = null): ?string
    {
        $config = static::byKey($key)->first();

        return $config?->config_value ?? $default;
    }
}
