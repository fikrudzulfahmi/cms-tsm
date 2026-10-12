<?php

namespace App\Http\Resources\Public;

use App\Http\Resources\Concerns\FormatsMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Blok landing page (hero, statistik, kontak, CTA, dst.).
 *
 * `extra` memuat data khusus per section, mis. angka statistik atau koordinat
 * peta. Urutan ditentukan kolom `sort` (controller mengurutkan sebelum
 * dikirim; LandingResource juga mengurutkan ulang secara defensif).
 */
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
            'body_html' => $this->body,
            'image' => $this->whenLoaded('image', fn ($media) => $this->imageShape($media)),
            'sort' => (int) $this->sort,
            'is_visible' => (bool) $this->is_visible,
            'extra' => $this->extra ?? (object) [],
        ];
    }
}
