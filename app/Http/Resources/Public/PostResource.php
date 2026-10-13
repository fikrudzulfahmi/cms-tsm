<?php

namespace App\Http\Resources\Public;

use App\Http\Resources\Concerns\FormatsMedia;
use App\Http\Resources\Concerns\HasVisitorData;
use App\Models\Media;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Berita untuk konsumen publik (`GET /api/v1/posts`, `GET /api/v1/posts/{slug}`).
 *
 * Bentuk data mengikuti spec bagian 9.5. Data khusus admin — `seo_score`,
 * `focus_keyword`, status draf/jadwal, `allow_comments`, email pengguna, dan
 * hash pengunjung — TIDAK PERNAH disertakan di resource ini. Untuk panel admin
 * pakai `App\Http\Resources\Admin\PostResource`.
 *
 * Relasi opsional cukup dimuat lewat eager loading (`with()`); bila tidak
 * dimuat, key-nya otomatis hilang dari JSON (tanpa N+1 query).
 */
class PostResource extends JsonResource
{
    use FormatsMedia;
    use HasVisitorData;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'body_html' => $this->body,

            'featured_image' => $this->whenLoaded(
                'featuredMedia',
                fn ($media) => $this->imageShape($media)
            ),

            'category' => $this->whenLoaded('category', fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ]),

            // Publik hanya menerima nama tag (array string), sesuai contoh spec.
            'tags' => $this->whenLoaded(
                'tags',
                fn ($tags) => $tags->pluck('name')->values()->all()
            ),

            'author' => $this->whenLoaded('author', fn ($author) => [
                'name' => $author->name,
                'avatar' => $this->storageUrl($author->avatar),
            ]),

            'published_at' => $this->published_at?->toISOString(),

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

            // Suara pengunjung saat ini; controller mengisi atribut
            // `viewer_reaction` (+1/-1/0) hasil lookup visitor_hash.
            'viewer' => [
                'reaction' => (int) (data_get($this->resource, 'viewer_reaction') ?? 0),
            ],

            'seo' => [
                'title' => $this->seo_title ?: $this->title,
                'description' => $this->seo_description ?: $this->excerpt,
                'canonical' => $this->canonicalUrl(),
                'og_image' => $this->ogImageUrl(),
            ],

            'visitor' => $this->visitorData(),

            // Komentar yang disetujui (dipakai halaman detail publik)
            'comments' => CommentResource::collection(
                $this->whenLoaded('comments', fn () => $this->resource->comments)
            ),
            // Berita terkait (dipakai halaman detail publik) — selalu sediakan
            'related' => PostCardResource::collection($this->relatedPosts()),
        ];
    }

    /**
     * URL kanonik halaman berita publik.
     */
    protected function canonicalUrl(): string
    {
        return url("/berita/{$this->slug}");
    }

    /**
     * Gambar Open Graph: `og_media` diutamakan, fallback ke gambar unggulan.
     */
    protected function ogImageUrl(): ?string
    {
        $og = $this->relationLoaded('ogMedia') ? $this->ogMedia : null;

        if ($og instanceof Media) {
            return $og->url();
        }

        $featured = $this->relationLoaded('featuredMedia') ? $this->featuredMedia : null;

        return $featured instanceof Media ? $featured->url() : null;
    }

    /**
     * 3 berita terkait (kategori sama lebih diutamakan).
     */
    protected function relatedPosts()
    {
        return Post::query()
            ->published()
            ->where('id', '!=', $this->id)
            ->when($this->category_id, fn ($q) => $q->where('category_id', $this->category_id))
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();
    }
}
