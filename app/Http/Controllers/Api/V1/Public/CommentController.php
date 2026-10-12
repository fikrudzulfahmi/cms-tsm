<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\CommentStatus;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Resources\Public\CommentResource;
use App\Jobs\SendCommentNotification;
use App\Models\Comment;
use App\Models\Post;
use App\Services\VisitorHasher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CommentController extends Controller
{
    public function __construct(private readonly VisitorHasher $hasher) {}

    /**
     * Komentar disetujui, bersarang, terpaginasi (spec 9.3).
     */
    public function index(Request $request, string $slug): JsonResponse
    {
        $request->validate(['page' => ['nullable', 'integer', 'min:1']]);

        $post = Post::where('slug', $slug)->published()->firstOrFail();

        $comments = $post->comments()
            ->approved()
            ->whereNull('parent_id')
            ->with(['childrenRecursive' => fn ($q) => $q->approved()])
            ->orderBy('created_at')
            ->paginate(10);

        return CommentResource::collection($comments)->response();
    }

    /**
     * Kirim komentar/balasan (spec 7.3).
     * Anti-spam: honeypot + rate limit + batas tautan + daftar kata terlarang.
     */
    public function store(Request $request, string $slug): JsonResponse
    {
        $post = Post::where('slug', $slug)->published()->firstOrFail();

        if (! $post->allow_comments) {
            return response()->json([
                'message' => 'Komentar untuk berita ini ditutup.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:60'],
            'email' => ['required', 'email', 'max:120'],
            'body' => ['required', 'string', 'min:3', 'max:2000'],
            'parent_id' => ['nullable', 'integer', 'exists:cms_comments,id'],
            'website' => ['nullable', 'string'], // honeypot
        ]);

        // honeypot: isi = bot
        if (! empty($validated['website'])) {
            return response()->json(['message' => 'Komentar berhasil dikirim.'], 202);
        }

        $body = $validated['body'];

        // batas tautan
        $linkCount = preg_match_all('/https?:\/\//i', $body);
        $maxLinks = (int) setting('comment_max_links', 2);
        if ($linkCount > $maxLinks) {
            return response()->json([
                'message' => 'Komentar mengandung terlalu banyak tautan.',
                'errors' => ['body' => ["Maksimal $maxLinks tautan per komentar."]],
            ], 422);
        }

        // kata terlarang
        $banned = (array) setting('banned_words', []);
        $low = Str::lower($body);
        foreach ($banned as $word) {
            if (str_contains($low, Str::lower((string) $word))) {
                return response()->json([
                    'message' => 'Komentar berhasil dikirim dan menunggu moderasi.',
                ], 202);
            }
        }

        $visitorHash = $this->hasher->hash($request->cookie('vid'), $request->userAgent());

        // auto-approve untuk pengomentar yang sudah pernah disetujui
        $autoApprove = (bool) setting('comment_auto_approve', true);
        $status = CommentStatus::Pending;
        if ($autoApprove) {
            $approvedBefore = Comment::where('email', $validated['email'])
                ->where('status', CommentStatus::Approved->value)
                ->exists();
            if ($approvedBefore) {
                $status = CommentStatus::Approved;
            }
        }

        $comment = DB::transaction(function () use ($validated, $post, $visitorHash, $status, $body) {
            $comment = Comment::create([
                'post_id' => $post->id,
                'parent_id' => $validated['parent_id'] ?? null,
                'name' => e($validated['name']),
                'email' => $validated['email'],
                'body' => e($body), // tanpa HTML
                'status' => $status,
                'visitor_hash' => $visitorHash,
                'is_admin_reply' => false,
            ]);

            if ($status === CommentStatus::Approved) {
                $post->increment('comments_count');
            }

            return $comment;
        });

        SendCommentNotification::dispatch($comment->id);

        $message = $status === CommentStatus::Approved
            ? 'Komentar kamu berhasil dikirim.'
            : 'Komentar kamu berhasil dikirim dan menunggu moderasi admin.';

        return response()->json([
            'message' => $message,
            'data' => new CommentResource($comment->fresh()),
        ], 201);
    }

    /**
     * Like komentar (toggle idempoten).
     */
    public function like(Request $request, int $id): JsonResponse
    {
        $comment = Comment::approved()->findOrFail($id);
        $visitorHash = $this->hasher->hash($request->cookie('vid'), $request->userAgent());

        $existing = $comment->likes()->where('visitor_hash', $visitorHash)->first();

        if ($existing !== null) {
            $existing->delete();
            $comment->decrement('likes_count');
            $liked = false;
        } else {
            $comment->likes()->create(['visitor_hash' => $visitorHash]);
            $comment->increment('likes_count');
            $liked = true;
        }

        return response()->json([
            'data' => ['likes' => $comment->likes_count, 'liked' => $liked],
        ]);
    }

    /**
     * Laporkan komentar.
     */
    public function report(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:200'],
        ]);

        $comment = Comment::approved()->findOrFail($id);
        $visitorHash = $this->hasher->hash($request->cookie('vid'), $request->userAgent());

        $comment->reports()->firstOrCreate(
            ['visitor_hash' => $visitorHash],
            ['reason' => $validated['reason']]
        );

        return response()->json([
            'message' => 'Laporan kamu sudah diterima. Terima kasih.',
        ]);
    }
}
