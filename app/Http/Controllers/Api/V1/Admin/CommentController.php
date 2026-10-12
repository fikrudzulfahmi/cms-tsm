<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\CommentStatus;
use App\Http\Controllers\Api\V1\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Moderasi komentar (spec 7.4).
 */
final class CommentController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:menunggu,disetujui,ditolak,spam'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $comments = \App\Models\Comment::query()
            ->with(['post:id,title,slug'])
            ->when($validated['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('q'), fn ($q, $s) => $q->where('body', 'like', '%' . $s . '%'))
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 25);

        return response()->json($comments);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:menunggu,disetujui,ditolak,spam'],
        ]);

        $comment = \App\Models\Comment::findOrFail($id);
        $comment->update($validated);

        $this->logger->log($request->user()?->id, 'admin.comment.moderate', \App\Models\Comment::class, $id, ['status' => $validated['status']]);

        return response()->json(['data' => $comment]);
    }

    public function reply(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $parent = \App\Models\Comment::findOrFail($id);

        $reply = \App\Models\Comment::create([
            'post_id' => $parent->post_id,
            'parent_id' => $parent->id,
            'name' => $request->user()?->name ?? 'Admin',
            'body' => $validated['body'],
            'status' => CommentStatus::Approved->value,
            'is_admin_reply' => true,
            'email' => $request->user()?->email,
        ]);

        $this->logger->log($request->user()?->id, 'admin.comment.reply', \App\Models\Comment::class, $reply->id);

        return response()->json(['data' => $reply], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $comment = \App\Models\Comment::findOrFail($id);
        $comment->delete();

        return response()->json(['message' => 'Komentar dihapus.']);
    }

    public function bulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'action' => ['required', 'string', 'in:disetujui,ditolak,spam,hapus'],
        ]);

        $action = $validated['action'];

        if ($action === 'hapus') {
            \App\Models\Comment::whereIn('id', $validated['ids'])->delete();
        } else {
            \App\Models\Comment::whereIn('id', $validated['ids'])->update(['status' => $action]);
        }

        $this->logger->log($request->user()?->id, 'admin.comment.bulk', null, null, [
            'count' => count($validated['ids']),
            'action' => $action,
        ]);

        return response()->json(['message' => count($validated['ids']) . ' komentar diproses.']);
    }
}
