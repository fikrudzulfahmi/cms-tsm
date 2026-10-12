<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pesan masuk dari form kontak/pendaftaran (spec 6.2).
 */
final class MessageController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:baru,baca,selesai'],
            'type' => ['nullable', 'string', 'in:pendaftaran,pertanyaan'],
        ]);

        $items = \App\Models\ContactMessage::query()
            ->when($validated['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($validated['type'] ?? null, fn ($q, $s) => $q->where('type', $s))
            ->orderByDesc('id')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return response()->json($items);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:baru,baca,selesai'],
        ]);

        $item = \App\Models\ContactMessage::findOrFail($id);
        $item->update($validated);

        $this->logger->log($request->user()?->id, 'admin.message.update', \App\Models\ContactMessage::class, $id, ['status' => $validated['status']]);

        return response()->json(['data' => $item]);
    }
}
