<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Umpan balik alumni (spec 6.2). Approve/reject sebelum tampil publik.
 */
final class FeedbackController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:pending,approved,rejected'],
        ]);

        $items = \App\Models\AlumniFeedback::query()
            ->when($validated['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('id')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return response()->json($items);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,approved,rejected'],
        ]);

        $item = \App\Models\AlumniFeedback::findOrFail($id);
        $item->update($validated);

        $this->logger->log($request->user()?->id, 'admin.feedback.update', \App\Models\AlumniFeedback::class, $id, ['status' => $validated['status']]);

        return response()->json(['data' => $item]);
    }
}
