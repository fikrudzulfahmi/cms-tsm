<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD + urutan prestasi CMS TSM.
 * (spec 6.1 — kelola konten landing dari panel admin)
 */
final class AchievementController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * Daftar prestasi.
     */
    public function index(Request $request): JsonResponse
    {
        $items = \App\Models\Achievement::query()
            ->when($request->input('q'), fn ($q, $s) => $q->where('title', 'like', '$s%'))
            ->orderBy('sort')
            ->orderByDesc('id')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return response()->json($items);
    }

    /**
     * Simpan prestasi baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['title' => ['required','string','max:160'], 'student_name' => ['nullable','string','max:120'], 'level' => ['nullable','string','max:60'], 'year' => ['nullable','integer'], 'sort' => ['nullable','integer']]);

        $item = \App\Models\Achievement::create($validated);
        $this->logger->log($request->user()?->id, 'admin.achievement.create', \App\Models\Achievement::class, $item->id);

        return response()->json(['data' => $item], 201);
    }

    /**
     * Perbarui prestasi.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $item = \App\Models\Achievement::findOrFail($id);

        $validated = $request->validate(['title' => ['required','string','max:160'], 'student_name' => ['nullable','string','max:120'], 'level' => ['nullable','string','max:60'], 'year' => ['nullable','integer'], 'sort' => ['nullable','integer']]);

        $item->update($validated);
        $this->logger->log($request->user()?->id, 'admin.achievement.update', \App\Models\Achievement::class, $item->id);

        return response()->json(['data' => $item]);
    }

    /**
     * Hapus prestasi.
     */
    public function destroy(int $id): JsonResponse
    {
        $item = \App\Models\Achievement::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'prestasi berhasil dihapus.']);
    }

    /**
     * Ubah urutan prestasi (drag & drop di panel).
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        foreach ($validated['ids'] as $i => $id) {
            \App\Models\Achievement::where('id', $id)->update(['sort' => $i + 1]);
        }

        return response()->json(['message' => 'Urutan disimpan.']);
    }
}
