<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD + urutan tahap belajar CMS TSM.
 * (spec 6.1 — kelola konten landing dari panel admin)
 */
final class LearningStageController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * Daftar tahap belajar.
     */
    public function index(Request $request): JsonResponse
    {
        $items = \App\Models\LearningStage::query()
            ->when($request->input('q'), fn ($q, $s) => $q->where('title', 'like', '$s%'))
            ->orderBy('sort')
            ->orderByDesc('id')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return response()->json($items);
    }

    /**
     * Simpan tahap belajar baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['class_label' => ['required','string','max:30'], 'title' => ['required','string','max:120'], 'description' => ['nullable','string'], 'sort' => ['nullable','integer']]);

        $item = \App\Models\LearningStage::create($validated);
        $this->logger->log($request->user()?->id, 'admin.stage.create', \App\Models\LearningStage::class, $item->id);

        return response()->json(['data' => $item], 201);
    }

    /**
     * Perbarui tahap belajar.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $item = \App\Models\LearningStage::findOrFail($id);

        $validated = $request->validate(['class_label' => ['required','string','max:30'], 'title' => ['required','string','max:120'], 'description' => ['nullable','string'], 'sort' => ['nullable','integer']]);

        $item->update($validated);
        $this->logger->log($request->user()?->id, 'admin.stage.update', \App\Models\LearningStage::class, $item->id);

        return response()->json(['data' => $item]);
    }

    /**
     * Hapus tahap belajar.
     */
    public function destroy(int $id): JsonResponse
    {
        $item = \App\Models\LearningStage::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'tahap belajar berhasil dihapus.']);
    }

    /**
     * Ubah urutan tahap belajar (drag & drop di panel).
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        foreach ($validated['ids'] as $i => $id) {
            \App\Models\LearningStage::where('id', $id)->update(['sort' => $i + 1]);
        }

        return response()->json(['message' => 'Urutan disimpan.']);
    }
}
