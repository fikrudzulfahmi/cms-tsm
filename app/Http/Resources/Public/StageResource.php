<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Alur belajar per tingkat kelas (X, XI, XII).
 */
class StageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'class_label' => $this->class_label,
            'title' => $this->title,
            'description' => $this->description,
            'sort' => (int) $this->sort,
        ];
    }
}
