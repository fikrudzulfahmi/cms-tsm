<?php

namespace App\Models;

use App\Enums\PostStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    /** @use HasFactory<\Database\Factories\PostFactory> */
    use HasFactory;

    protected $table = 'cms_posts';

    protected $fillable = [
        'title', 'slug', 'excerpt', 'body', 'featured_media_id', 'category_id',
        'author_id', 'status', 'published_at', 'scheduled_at', 'allow_comments',
        'focus_keyword', 'seo_title', 'seo_description', 'og_media_id',
        'seo_score', 'views_count', 'likes_count', 'unlikes_count',
        'comments_count', 'shares_count', 'like_percent', 'engagement_percent',
        'stars',
    ];

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'allow_comments' => 'boolean',
            'seo_score' => 'integer',
            'views_count' => 'integer',
            'likes_count' => 'integer',
            'unlikes_count' => 'integer',
            'comments_count' => 'integer',
            'shares_count' => 'integer',
            'like_percent' => 'float',
            'engagement_percent' => 'float',
            'stars' => 'float',
        ];
    }

    // ---------- scopes ----------

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', PostStatus::Published->value)
            ->where('published_at', '<=', now());
    }

    public function scopeVisiblePublic(Builder $q): Builder
    {
        return $q->published()
            ->orderByDesc('published_at');
    }

    // ---------- relationships ----------

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    public function ogMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'og_media_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'cms_post_tag', 'post_id', 'tag_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PostRevision::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(PostReaction::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(PostShare::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function approvedComments(): HasMany
    {
        return $this->hasMany(Comment::class)->where('status', \App\Enums\CommentStatus::Approved->value);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    // ---------- helpers ----------

    public function isPublished(): bool
    {
        return $this->status === PostStatus::Published;
    }

    public function incrementView(): void
    {
        $this->increment('views_count');
    }
}
