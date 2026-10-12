<?php

namespace App\Http\Resources\Admin;

use App\Enums\CommentStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Moderasi komentar (`GET /admin/comments`, spec bagian 9.4).
 *
 * Admin melihat data penuh termasuk `email` (untuk verifikasi) — tetapi
 * `visitor_hash` tetap TIDAK dikirim: hash itu adalah identitas turunan
 * pengunjung, dan tidak ada kebutuhan moderasi yang memerlukannya.
 */
class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status;

        return [
            'id' => $this->id,

            'post' => $this->whenLoaded('post', fn ($post) => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
            ]),
            'parent_id' => $this->parent_id === null ? null : (int) $this->parent_id,

            'name' => $this->name,
            'email' => $this->email,
            'body' => $this->body,

            'status' => $status instanceof CommentStatus ? $status->value : $status,
            'status_label' => $status instanceof CommentStatus ? $status->label() : null,

            'is_admin_reply' => (bool) $this->is_admin_reply,
            'likes_count' => (int) $this->likes_count,
            'reports_count' => $this->whenCounted('reports'),

            'replies' => $this->whenLoaded('replies', fn ($replies) => self::collection($replies)),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
