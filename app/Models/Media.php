<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    /** @use HasFactory<\Database\Factories\MediaFactory> */
    use HasFactory;

    protected $table = 'cms_media';

    protected $fillable = [
        'path', 'disk', 'mime', 'size', 'width', 'height', 'alt', 'caption',
        'variants', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'variants' => 'array',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * URL publik untuk media di disk default.
     */
    public function url(?string $variant = null): string
    {
        $path = $this->path;

        if ($variant !== null && isset($this->variants[$variant])) {
            $path = $this->variants[$variant];
        }

        return '/storage/' . ltrim($path, '/');
    }
}
