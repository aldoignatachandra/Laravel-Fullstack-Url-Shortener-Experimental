<?php

namespace App\Models;

use Database\Factories\LinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @use HasFactory<LinkFactory> */
#[Fillable(['user_id', 'title', 'original_url', 'short_code', 'status'])]
class Link extends Model
{
    use HasFactory, SoftDeletes;

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
        return $this->status === 1;
    }

    public function clickCount(): int
    {
        return $this->logs()->count();
    }

    public function uniqueVisitorCount(): int
    {
        return $this->logs()->distinct('ip_address')->count('ip_address');
    }
}
