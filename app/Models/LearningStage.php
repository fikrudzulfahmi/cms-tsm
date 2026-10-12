<?php

namespace App\Models;

use Database\Factories\LearningStageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningStage extends Model
{
    /** @use HasFactory<LearningStageFactory> */
    use HasFactory;

    protected $table = 'cms_learning_stages';

    protected $fillable = ['class_label', 'title', 'description', 'sort'];

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
}
