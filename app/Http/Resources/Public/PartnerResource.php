<?php

namespace App\Http\Resources\Public;

use App\Http\Resources\Concerns\FormatsMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Mitra industri/DUDI (`GET /api/v1/partners`).
 */
class PartnerResource extends JsonResource
{
    use FormatsMedia;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url,
            'logo' => $this->whenLoaded('logo', fn ($media) => $this->imageCard($media)),
            'sort' => (int) $this->sort,
        ];
    }
}
