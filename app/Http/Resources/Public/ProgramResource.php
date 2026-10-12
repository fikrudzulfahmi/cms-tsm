<?php

namespace App\Http\Resources\Public;

use App\Http\Resources\Concerns\FormatsMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Program unggulan (praktik bengkel, teaching factory, sertifikasi).
 */
class ProgramResource extends JsonResource
{
    use FormatsMedia;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'image' => $this->whenLoaded('image', fn ($media) => $this->imageShape($media)),
            'sort' => (int) $this->sort,
        ];
    }
}
