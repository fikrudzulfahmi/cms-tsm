<?php

namespace App\Http\Resources\Public;

use App\Http\Resources\Concerns\FormatsMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Prestasi (`GET /api/v1/achievements`, blok prestasi di landing page).
 */
class AchievementResource extends JsonResource
{
    use FormatsMedia;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'level' => $this->level,
            'rank' => $this->rank,
            'year' => $this->year === null ? null : (int) $this->year,
            'student_name' => $this->student_name,
            'description' => $this->description,
            'image' => $this->whenLoaded('image', fn ($media) => $this->imageShape($media)),
            'sort' => (int) $this->sort,
        ];
    }
}
