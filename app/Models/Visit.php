<?php

namespace App\Models;

use Database\Factories\VisitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visit extends Model
{
    /** @use HasFactory<VisitFactory> */
    use HasFactory;

    protected $table = 'cms_visits';

    protected $fillable = [
        'visited_at', 'path', 'post_id', 'visitor_hash', 'referrer_source',
        'referrer_url', 'device', 'browser', 'country', 'is_bot',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
            'is_bot' => 'boolean',
        ];
    }

    // ---------- relationships ----------

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
