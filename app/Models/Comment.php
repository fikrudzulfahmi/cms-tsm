<?php

namespace App\Models;

use App\Enums\CommentStatus;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    protected $table = 'cms_comments';

    protected $fillable = [
        'post_id', 'parent_id', 'name', 'email', 'body',
        'status', 'visitor_hash', 'likes_count', 'is_admin_reply',
    ];

    protected function casts(): array
    {
        return [
            'status' => CommentStatus::class,
            'likes_count' => 'integer',
            'is_admin_reply' => 'boolean',
        ];
    }

    // ---------- relationships ----------

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CommentLike::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(CommentReport::class);
    }

    // ---------- scopes ----------

    /**
     * Komentar yang sudah disetujui moderasi (status disetujui).
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', CommentStatus::Approved->value);
    }
}
