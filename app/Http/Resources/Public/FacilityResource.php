<?php

namespace App\Http\Resources\Public;

use App\Http\Resources\Concerns\FormatsMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Sarana/bengkel: tabel spesifikasi dan daftar alat (blok "Sarana").
 */
class FacilityResource extends JsonResource
{
    use FormatsMedia;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'spec' => $this->spec,
            'quantity' => (int) $this->quantity,
            'image' => $this->whenLoaded('image', fn ($media) => $this->imageShape($media)),
            'sort' => (int) $this->sort,
        ];
    }
}
