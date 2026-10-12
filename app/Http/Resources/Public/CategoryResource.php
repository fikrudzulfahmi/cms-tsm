<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Kategori berita publik (`GET /api/v1/categories`).
 *
 * `posts_count` hanya terisi bila controller memakai `withCount('posts')`.
 * Kolom SEO kategori (seo_title, seo_description) tidak dipublikasikan di sini;
 * dipakai server-side oleh SeoShellController.
 */
class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'posts_count' => (int) ($this->posts_count ?? 0),
        ];
    }
}
