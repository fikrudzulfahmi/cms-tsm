<?php

namespace App\Http\Resources\Public;

use App\Enums\CommentStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Komentar publik (`GET /api/v1/posts/{slug}/comments`).
 *
 * Hanya komentar berstatus `disetujui` yang boleh tampil. Karena itu:
 * - `email`, `visitor_hash`, dan `status` mentah TIDAK PERNAH dipublikasikan;
 * - anak komentar difilter ulang ke status disetujui walaupun relasi `children`
 *   memuat semua balasan.
 *
 * `children` bersarang rekursif (maksimal sebatas relasi yang di-eager-load,
 * mis. `children.children`).
 */
class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'body' => $this->body,
            'created_at' => $this->created_at?->toISOString(),
            'likes_count' => (int) $this->likes_count,
            'is_admin_reply' => (bool) $this->is_admin_reply,

            'children' => $this->whenLoaded('children', fn ($children) => self::collection(
                $children->filter(fn ($child) => $this->isApproved($child))->values()
            )),
        ];
    }

    /**
     * Cocokkan status komentar baik berupa enum maupun string mentah.
     */
    protected function isApproved(mixed $comment): bool
    {
        $status = data_get($comment, 'status');

        return $status instanceof CommentStatus
            ? $status === CommentStatus::Approved
            : $status === CommentStatus::Approved->value;
    }
}
