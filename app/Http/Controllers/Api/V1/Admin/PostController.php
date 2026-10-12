<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\PostStatus;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Admin\PostRequest;
use App\Http\Resources\Admin\PostResource;
use App\Jobs\RecomputeRating;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostRevision;
use App\Services\ActivityLogger;
use App\Services\HtmlSanitizer;
use App\Services\Seo\SeoAnalyzer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PostController extends Controller
{
    public function __construct(
        private readonly HtmlSanitizer $sanitizer,
        private readonly SeoAnalyzer $seo,
    ) {
        $this->authorizeResource(Post::class, 'post');
    }

    /**
     * Daftar berita admin (sort, filter, search).
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string'],
            'category' => ['nullable', 'string'],
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'string', 'in:created_at,published_at,title,seo_score,views_count'],
            'order' => ['nullable', 'string', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = Post::with(['category', 'author', 'featuredMedia']);

        // penulis hanya lihat beritanya sendiri
        if (! $request->user()?->hasAbility('posts.*')) {
            $query->where('author_id', $request->user()?->id);
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (! empty($validated['category'])) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $validated['category']));
        }
        if (! empty($validated['q'])) {
            $search = $validated['q'];
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('focus_keyword', 'like', "%{$search}%"));
        }

        $sort = $validated['sort'] ?? 'created_at';
        $order = $validated['order'] ?? 'desc';
        $query->orderBy($sort, $order);

        $result = $query->paginate((int) ($validated['per_page'] ?? 15));

        return PostResource::collection($result)->response();
    }

    public function store(PostRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['body'] = $this->sanitizer->purify($validated['body']);
        $validated['slug'] = $this->uniqueSlug($validated['title']);
        $validated['author_id'] = $request->user()?->id;
        $validated['status'] = $this->resolveStatus($validated['status'] ?? 'draf', $request);

        $post = DB::transaction(function () use ($validated, $request) {
            $post = Post::create($validated);
            $this->syncTags($post, $validated['tags'] ?? []);
            $this->createRevision($post, $request->user()?->id);

            return $post;
        });

        $post->load(['category', 'author', 'featuredMedia', 'tags']);
        ActivityLogger::log($request->user()?->id, 'admin.posts.create', 'Post', $post->id);

        return (new PostResource($post))->response()->setStatusCode(201);
    }

    public function show(Request $request, Post $post): JsonResponse
    {
        $post->load(['category', 'author', 'featuredMedia', 'ogMedia', 'tags']);

        return (new PostResource($post))->response();
    }

    public function update(PostRequest $request, Post $post): JsonResponse
    {
        $validated = $request->validated();
        $validated['body'] = $this->sanitizer->purify($validated['body']);

        // penulis hanya ubah miliknya
        if (! $request->user()?->hasAbility('posts.*') && $post->author_id !== $request->user()?->id) {
            return response()->json(['message' => 'Kamu tidak bisa mengubah berita ini.'], 403);
        }

        DB::transaction(function () use ($validated, $post, $request) {
            $post->update($validated);
            $this->syncTags($post, $validated['tags'] ?? []);
            $this->createRevision($post, $request->user()?->id);
        });

        $post->load(['category', 'author', 'featuredMedia', 'ogMedia', 'tags']);
        ActivityLogger::log($request->user()?->id, 'admin.posts.update', 'Post', $post->id);

        return (new PostResource($post))->response();
    }

    public function destroy(Request $request, Post $post): JsonResponse
    {
        $post->delete();
        ActivityLogger::log($request->user()?->id, 'admin.posts.delete', 'Post', $post->id);

        return response()->json(['message' => 'Berita berhasil dihapus.']);
    }

    /**
     * Terbitkan berita — API menolak bila skor SEO di bawah ambang (spec 7.2).
     */
    public function publish(Request $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);

        if ($post->seo_score < (int) setting('seo_publish_min_score', 50)) {
            return response()->json([
                'message' => 'Berita belum bisa diterbitkan.',
                'errors' => ['seo_score' => ['Skor SEO (' . $post->seo_score . ') masih di bawah ambang minimum (' . setting('seo_publish_min_score', 50) . ').']],
            ], 422);
        }

        $post->update([
            'status' => PostStatus::Published,
            'published_at' => now(),
            'scheduled_at' => null,
        ]);
        ActivityLogger::log($request->user()?->id, 'admin.posts.publish', 'Post', $post->id);

        return response()->json(['message' => 'Berita diterbitkan.']);
    }

    public function unpublish(Request $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);
        $post->update(['status' => PostStatus::Draft, 'published_at' => null]);
        ActivityLogger::log($request->user()?->id, 'admin.posts.unpublish', 'Post', $post->id);

        return response()->json(['message' => 'Berita dijadikan draf.']);
    }

    public function archive(Request $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);
        $post->update(['status' => PostStatus::Archived]);
        ActivityLogger::log($request->user()?->id, 'admin.posts.archive', 'Post', $post->id);

        return response()->json(['message' => 'Berita diarsipkan.']);
    }

    public function revisions(Request $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);
        $revisions = $post->revisions()->with('user')->latest()->limit(30)->get();

        return response()->json(['data' => $revisions]);
    }

    public function restoreRevision(Request $request, int $id, int $rev): JsonResponse
    {
        $post = Post::findOrFail($id);
        $revision = $post->revisions()->findOrFail($rev);

        $post->update([
            'title' => $revision->title,
            'body' => $revision->body,
        ]);
        ActivityLogger::log($request->user()?->id, 'admin.posts.restore_revision', 'Post', $post->id);

        return response()->json(['message' => 'Revisi berhasil dipulihkan.']);
    }

    // ---------- helpers ----------

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;
        while (Post::where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }

        return mb_substr($slug, 0, 75);
    }

    private function resolveStatus(string $status, Request $request): PostStatus
    {
        $status = PostStatus::tryFrom($status) ?? PostStatus::Draft;

        // penulis tidak bisa publikasi langsung
        if ($status === PostStatus::Published && ! $request->user()?->hasAbility('posts.*')) {
            return PostStatus::PendingReview;
        }

        return $status;
    }

    private function syncTags(Post $post, array $tags): void
    {
        $ids = [];
        foreach ($tags as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $tag = \App\Models\Tag::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
            $ids[] = $tag->id;
        }
        $post->tags()->sync($ids);
    }

    private function createRevision(Post $post, ?int $userId): void
    {
        PostRevision::create([
            'post_id' => $post->id,
            'title' => $post->title,
            'body' => $post->body,
            'user_id' => $userId,
        ]);
    }
}
