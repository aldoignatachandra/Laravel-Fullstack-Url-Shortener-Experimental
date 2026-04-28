<?php

namespace App\Models;

use Database\Factories\LinkLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @use HasFactory<LinkLogFactory> */
#[Fillable(['link_id', 'clicked_at', 'ip_address', 'user_agent', 'referrer'])]
class LinkLog extends Model
{
    use HasFactory;

    protected $table = 'link_logs';

    protected function casts(): array
    {
        return [
            'clicked_at' => 'datetime',
        ];
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}
