<?php

namespace App\Http\Resources\Public;

use App\Http\Resources\Concerns\FormatsMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Profil guru/staf jurusan (`GET /api/v1/staff`, halaman Tentang).
 */
class StaffResource extends JsonResource
{
    use FormatsMedia;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role,
            'bio' => $this->bio,
            'photo' => $this->whenLoaded('photo', fn ($media) => $this->imageShape($media)),
            'sort' => (int) $this->sort,
        ];
    }
}
