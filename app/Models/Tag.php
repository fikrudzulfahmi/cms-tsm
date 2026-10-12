<?php

namespace App\Models;

use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    protected $table = 'cms_tags';

    protected $fillable = ['name', 'slug'];

    // ---------- relationships ----------

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'cms_post_tag', 'tag_id', 'post_id');
    }
}
