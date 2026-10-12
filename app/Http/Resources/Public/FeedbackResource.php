<?php

namespace App\Http\Resources\Public;

use App\Http\Resources\Concerns\FormatsMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Umpan balik lulusan yang sudah disetujui (blok "Umpan balik lulusan").
 *
 * Hanya baris dengan status disetujui yang boleh dikirim controller ke resource
 * ini. Tidak ada kolom kontak/email pada tabelnya, jadi tidak ada data pribadi
 * tambahan yang perlu disembunyikan.
 */
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
            'photo' => $this->whenLoaded('photo', fn ($media) => $this->imageCard($media)),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
