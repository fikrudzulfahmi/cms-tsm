<?php

namespace App\Http\Resources\Public;

use App\Http\Resources\Concerns\FormatsMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Item galeri (`GET /api/v1/gallery`, blok sarana/galeri di landing page).
 */
class GalleryResource extends JsonResource
{
    use FormatsMedia;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'caption' => $this->caption,
            'album' => $this->album,
            'image' => $this->whenLoaded('media', fn ($media) => $this->imageShape($media)),
            'variants' => $this->whenLoaded('media', fn ($media) => $this->imageVariants($media)),
            'sort' => (int) $this->sort,
        ];
    }
}
