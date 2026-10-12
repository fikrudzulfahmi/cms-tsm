<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\Concerns\FormatsMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
{
    use FormatsMedia;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'body' => $this->body,
            'image_id' => $this->image_id,
            'image' => $this->whenLoaded('image', fn ($media) => $this->imageShape($media)),
            'sort' => (int) $this->sort,
            'is_visible' => (bool) $this->is_visible,
            'extra' => $this->extra ?? (object) [],
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
