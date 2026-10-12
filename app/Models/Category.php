<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $table = 'cms_categories';

    protected $fillable = [
        'name', 'slug', 'description', 'seo_title', 'seo_description', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
        ];
    }

    // Urut default berdasar kolom sort.
    protected static function booted(): void
    {
        static::addGlobalScope('sort', fn (Builder $q) => $q->orderBy('sort'));
    }

    // ---------- relationships ----------

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
