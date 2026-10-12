<?php

namespace App\Models;

use Database\Factories\PartnerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Partner extends Model
{
    /** @use HasFactory<PartnerFactory> */
    use HasFactory;

    protected $table = 'cms_partners';

    protected $fillable = ['name', 'logo_id', 'url', 'sort'];

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

    public function logo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'logo_id');
    }
}
