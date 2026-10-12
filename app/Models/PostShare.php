<?php

namespace App\Models;

use App\Enums\ShareChannel;
use Database\Factories\PostShareFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostShare extends Model
{
    /** @use HasFactory<PostShareFactory> */
    use HasFactory;

    protected $table = 'cms_post_shares';

    protected $fillable = ['post_id', 'channel'];

    protected function casts(): array
    {
        return [
            'channel' => ShareChannel::class,
        ];
    }

    // ---------- relationships ----------

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
