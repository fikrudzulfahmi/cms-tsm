<?php

namespace App\Models;

use Database\Factories\AlumniFeedbackFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlumniFeedback extends Model
{
    /** @use HasFactory<AlumniFeedbackFactory> */
    use HasFactory;

    protected $table = 'cms_alumni_feedback';

    protected $fillable = [
        'name', 'graduation_year', 'occupation', 'message', 'photo_id', 'status',
    ];

    protected function casts(): array
    {
        return [
            'graduation_year' => 'integer',
            'status' => 'string',
        ];
    }

    // ---------- relationships ----------

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'photo_id');
    }

    /**
     * Umpan balik yang sudah disetujui untuk tampil di publik.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }
}
