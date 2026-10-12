<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\Concerns\FormatsMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedbackResource extends JsonResource
{
    use FormatsMedia;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'graduation_year' => $this->graduation_year === null ? null : (int) $this->graduation_year,
            'occupation' => $this->occupation,
            'message' => $this->message,
            'photo_id' => $this->photo_id,
            'photo' => $this->whenLoaded('photo', fn ($media) => $this->imageShape($media)),
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
