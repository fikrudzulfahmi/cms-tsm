<?php

namespace App\Models;

use Database\Factories\PostReactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostReaction extends Model
{
    /** @use HasFactory<PostReactionFactory> */
    use HasFactory;

    protected $table = 'cms_post_reactions';

    protected $fillable = ['post_id', 'visitor_hash', 'value'];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
        ];
    }

    // ---------- relationships ----------

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
