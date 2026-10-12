<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD + urutan karier CMS TSM.
 * (spec 6.1 — kelola konten landing dari panel admin)
 */
final class CareerController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * Daftar karier.
     */
    public function index(Request $request): JsonResponse
    {
        $items = \App\Models\Career::query()
            ->when($request->input('q'), fn ($q, $s) => $q->where('title', 'like', '$s%'))
            ->orderBy('sort')
            ->orderByDesc('id')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return response()->json($items);
    }

    /**
     * Simpan karier baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['label' => ['required','string','max:120'], 'sort' => ['nullable','integer']]);

        $item = \App\Models\Career::create($validated);
        $this->logger->log($request->user()?->id, 'admin.career.create', \App\Models\Career::class, $item->id);

        return response()->json(['data' => $item], 201);
    }

    /**
     * Perbarui karier.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $item = \App\Models\Career::findOrFail($id);

        $validated = $request->validate(['label' => ['required','string','max:120'], 'sort' => ['nullable','integer']]);

        $item->update($validated);
        $this->logger->log($request->user()?->id, 'admin.career.update', \App\Models\Career::class, $item->id);

        return response()->json(['data' => $item]);
    }

    /**
     * Hapus karier.
     */
    public function destroy(int $id): JsonResponse
    {
        $item = \App\Models\Career::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'karier berhasil dihapus.']);
    }

    /**
     * Ubah urutan karier (drag & drop di panel).
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        foreach ($validated['ids'] as $i => $id) {
            \App\Models\Career::where('id', $id)->update(['sort' => $i + 1]);
        }

        return response()->json(['message' => 'Urutan disimpan.']);
    }
}
