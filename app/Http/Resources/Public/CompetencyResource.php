<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Kompetensi keahlian (grid 3 kolom di landing page).
 */
class CompetencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'icon' => $this->icon,
            'is_highlight' => (bool) $this->is_highlight,
            'sort' => (int) $this->sort,
        ];
    }
}
