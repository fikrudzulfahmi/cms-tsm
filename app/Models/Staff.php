<?php

namespace App\Models;

use Database\Factories\StaffFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Staff extends Model
{
    /** @use HasFactory<StaffFactory> */
    use HasFactory;

    protected $table = 'cms_staff';

    protected $fillable = ['name', 'role', 'photo_id', 'bio', 'sort'];

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

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'photo_id');
    }
}
