<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * FAQ jurusan (`GET /api/v1/faqs`, JSON-LD FAQPage memakai data yang sama).
 */
class FaqResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'answer' => $this->answer,
            'sort' => (int) $this->sort,
        ];
    }
}
