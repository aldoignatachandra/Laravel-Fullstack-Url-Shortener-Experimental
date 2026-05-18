<?php

namespace App\Models;

use Database\Factories\LinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** @use HasFactory<LinkFactory> */
#[Fillable(['user_id', 'title', 'original_url', 'short_code', 'status'])]
class Link extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Link status constants.
     */
    public const STATUS_ACTIVE = 1;

    public const STATUS_ARCHIVED = 2;

    protected function casts(): array
    {
        return [
            'status' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(LinkLog::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * Get click count. Uses eager-loaded count if available, otherwise queries.
     */
    public function clickCount(): int
    {
        if ($this->relationLoaded('logs')) {
            return $this->logs->count();
        }

        if (isset($this->attributes['logs_count'])) {
            return (int) $this->attributes['logs_count'];
        }

        return $this->logs()->count();
    }

    /**
     * Get unique visitor count. Uses eager-loaded data if available.
     */
    public function uniqueVisitorCount(): int
    {
        if ($this->relationLoaded('logs')) {
            return $this->logs->unique('ip_address')->count();
        }

        if (isset($this->attributes['unique_visitors_count'])) {
            return (int) $this->attributes['unique_visitors_count'];
        }

        return $this->logs()->distinct('ip_address')->count('ip_address');
    }
}
