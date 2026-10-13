<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Resources\Public\PostCardResource;
use App\Http\Resources\Public\PostResource;
use App\Models\Post;
use App\Services\VisitorHasher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PostController extends Controller
{
    public function __construct(private readonly VisitorHasher $hasher) {}

    /**
     * Daftar berita terbit (spec 9.3).
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => ['nullable', 'string'],
            'tag' => ['nullable', 'string'],
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'], // maks 50 (spec 9.1)
        ]);

        $posts = Post::with(['category', 'author', 'featuredMedia'])
            ->published()
            ->orderByDesc('published_at');

        if (! empty($validated['category'])) {
            $posts->whereHas('category', fn ($q) => $q->where('slug', $validated['category']));
        }
        if (! empty($validated['tag'])) {
            $posts->whereHas('tags', fn ($q) => $q->where('slug', $validated['tag']));
        }
        if (! empty($validated['q'])) {
            $search = $validated['q'];
            $posts->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('excerpt', 'like', "%{$search}%"));
        }

        $perPage = (int) ($validated['per_page'] ?? 9);
        $result = $posts->paginate($perPage);

        return PostCardResource::collection($result)
            ->response()
            ->withHeaders(['Cache-Control' => 'public, max-age=60']);
    }

    /**
     * Daftar kategori (untuk filter di halaman berita publik).
     */
    public function categories(): JsonResponse
    {
        $categories = \App\Models\Category::query()
            ->orderBy('sort')
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->get();

        return response()->json([
            'data' => \App\Http\Resources\Public\CategoryResource::collection($categories),
        ])->withHeaders(['Cache-Control' => 'public, max-age=300']);
    }

    /**
     * Detail berita + reaksi pengunjung saat ini + berita terkait.
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $post = Post::with(['category', 'author', 'featuredMedia', 'ogMedia', 'tags', 'comments' => fn ($q) => $q->approved()->orderBy('id')])
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();

        // catat kunjungan (async nanti; di sini increment view counter)
        $post->incrementView();

        // reaksi pengunjung saat ini
        $visitorHash = $this->hasher->hash($request->cookie('vid'), $request->userAgent());
        $reaction = $post->reactions()->where('visitor_hash', $visitorHash)->value('value');

        // berita terkait (kategori sama, 3 berita lain)
        $related = Post::with(['category', 'featuredMedia'])
            ->published()
            ->where('id', '!=', $post->id)
            ->when($post->category_id, fn ($q) => $q->where('category_id', $post->category_id))
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        $resource = new PostResource($post);
        $resource->setVisitorData([
            'reaction' => $reaction !== null ? (int) $reaction : null,
            'related' => PostCardResource::collection($related),
        ]);

        return $resource->response();
    }
}
