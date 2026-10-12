<?php

namespace App\Models;

use Database\Factories\CommentLikeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentLike extends Model
{
    /** @use HasFactory<CommentLikeFactory> */
    use HasFactory;

    protected $table = 'cms_comment_likes';

    protected $fillable = ['comment_id', 'visitor_hash'];

    // ---------- relationships ----------

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }
}
