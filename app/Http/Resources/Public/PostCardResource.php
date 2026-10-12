<?php

namespace App\Http\Resources\Public;

use App\Http\Resources\Concerns\FormatsMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Versi ringkas berita untuk daftar/kartu (`GET /api/v1/posts`) dan
 * blok "berita terkini" di landing page.
 *
 * Tanpa `body_html` dan tanpa data khusus admin.
 */
class PostCardResource extends JsonResource
{
    use FormatsMedia;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,

            'featured_image' => $this->whenLoaded(
                'featuredMedia',
                fn ($media) => $this->imageCard($media)
            ),

            'category' => $this->whenLoaded('category', fn ($category) => [
                'name' => $category->name,
                'slug' => $category->slug,
            ]),

            'author' => $this->whenLoaded('author', fn ($author) => [
                'name' => $author->name,
            ]),

            'published_at' => $this->published_at?->toISOString(),
            'views_count' => (int) $this->views_count,
            'comments_count' => (int) $this->comments_count,
        ];
    }
}
