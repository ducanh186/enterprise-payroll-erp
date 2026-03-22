<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attachment — file attachment linked to any module record via module + ref_id.
 *
 * @property int $id
 * @property string $module
 * @property int $ref_id
 * @property string $file_name
 * @property string $file_path
 * @property string|null $mime_type
 * @property int|null $uploaded_by
 * @property \Illuminate\Support\Carbon $created_at
 *
 * @property-read \App\Models\User|null $uploader
 *
 * @method static Builder|Attachment byRef(string $module, int $refId)
 */
class Attachment extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'attachments';

    /**
     * Indicates if the model should be timestamped.
     * Attachments only have created_at, no updated_at.
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
        'module',
        'ref_id',
        'file_name',
        'file_path',
        'mime_type',
        'uploaded_by',
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
            'created_at' => 'datetime',
        ];
    }

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    /**
     * The user who uploaded this attachment.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------

    /**
     * Filter by module and reference ID.
     */
    public function scopeByRef(Builder $query, string $module, int $refId): Builder
    {
        return $query->where('module', $module)->where('ref_id', $refId);
    }
}
