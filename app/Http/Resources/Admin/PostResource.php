<?php

namespace App\Http\Resources\Admin;

use App\Enums\PostStatus;
use App\Http\Resources\Concerns\FormatsMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Berita untuk panel admin (`apiResource /admin/posts`, spec bagian 9.4).
 *
 * Berbeda dari `App\Http\Resources\Public\PostResource`: di sini SEMUA field
 * editor ikut dikirim, termasuk `seo_score`, `focus_keyword`, status draf/
 * jadwal, jumlah revisi, dan token pratinjau. Jangan pernah memakai resource
 * ini untuk endpoint publik.
 */
class PostResource extends JsonResource
{
    use FormatsMedia;

    public function toArray(Request $request): array
    {
        $status = $this->status;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'body_html' => $this->body,

            // ---------- status & penjadwalan ----------
            'status' => $status instanceof PostStatus ? $status->value : $status,
            'status_label' => $status instanceof PostStatus ? $status->label() : null,
            'status_color' => $status instanceof PostStatus ? $status->color() : null,
            'is_published' => $status === PostStatus::Published,
            'allow_comments' => (bool) $this->allow_comments,

            'published_at' => $this->published_at?->toISOString(),
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // ---------- SEO (khusus admin) ----------
            'focus_keyword' => $this->focus_keyword,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'seo_score' => (int) $this->seo_score,

            // ---------- media & relasi ----------
            'featured_image' => $this->whenLoaded('featuredMedia', fn ($media) => MediaResource::make($media)),
            'og_image' => $this->whenLoaded('ogMedia', fn ($media) => MediaResource::make($media)),

            'category' => $this->whenLoaded('category', fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ]),

            'tags' => $this->whenLoaded('tags', fn ($tags) => $tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])->values()->all()),
            'tag_ids' => $this->whenLoaded('tags', fn ($tags) => $tags->pluck('id')->values()->all()),

            'author' => $this->whenLoaded('author', fn ($author) => [
                'id' => $author->id,
                'name' => $author->name,
                'email' => $author->email,
                'avatar' => $this->storageUrl($author->avatar),
            ]),

            // ---------- statistik interaksi ----------
            'counts' => [
                'views' => (int) $this->views_count,
                'likes' => (int) $this->likes_count,
                'unlikes' => (int) $this->unlikes_count,
                'comments' => (int) $this->comments_count,
                'shares' => (int) $this->shares_count,
            ],
            'stats' => [
                'views' => (int) $this->views_count,
                'likes' => (int) $this->likes_count,
                'unlikes' => (int) $this->unlikes_count,
                'comments' => (int) $this->comments_count,
                'shares' => (int) $this->shares_count,
                'like_percent' => round((float) $this->like_percent, 1),
                'engagement_percent' => round((float) $this->engagement_percent, 1),
                'stars' => round((float) $this->stars, 1),
            ],
            'revisions_count' => $this->whenCounted('revisions'),

            // ---------- pratinjau (POST /admin/posts/{id}/preview-token) ----------
            'preview' => [
                'url' => url("/berita/{$this->slug}"),
                'token' => data_get($this->resource, 'preview_token'),
            ],
        ];
    }
}
