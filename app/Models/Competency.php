<?php

namespace App\Models;

use Database\Factories\CompetencyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Competency extends Model
{
    /** @use HasFactory<CompetencyFactory> */
    use HasFactory;

    protected $table = 'cms_competencies';

    protected $fillable = [
        'title', 'description', 'icon', 'sort', 'is_highlight', 'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'is_highlight' => 'boolean',
            'is_visible' => 'boolean',
        ];
    }

    // Urut default berdasar kolom sort.
    protected static function booted(): void
    {
        static::addGlobalScope('sort', fn (Builder $q) => $q->orderBy('sort'));
    }
}
