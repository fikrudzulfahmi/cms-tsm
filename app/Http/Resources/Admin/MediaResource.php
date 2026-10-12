<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\Concerns\FormatsMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Pustaka media (`GET /admin/media`, spec bagian 9.4).
 *
 * Dipakai juga sebagai bentuk gambar tersarang di resource admin lain
 * (gambar unggulan/OG berita). Path mentah dan nama disk tidak dikirim;
 * SPA bekerja dengan URL.
 */
class MediaResource extends JsonResource
{
    use FormatsMedia;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url(),
            'alt' => $this->alt,
            'caption' => $this->caption,
            'width' => $this->width === null ? null : (int) $this->width,
            'height' => $this->height === null ? null : (int) $this->height,
            'mime' => $this->mime,
            'size' => (int) $this->size,
            'variants' => $this->imageVariants($this->resource),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
