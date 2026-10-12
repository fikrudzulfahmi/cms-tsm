<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD + urutan kompetensi CMS TSM.
 * (spec 6.1 — kelola konten landing dari panel admin)
 */
final class CompetencyController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * Daftar kompetensi.
     */
    public function index(Request $request): JsonResponse
    {
        $items = \App\Models\Competency::query()
            ->when($request->input('q'), fn ($q, $s) => $q->where('title', 'like', '$s%'))
            ->orderBy('sort')
            ->orderByDesc('id')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return response()->json($items);
    }

    /**
     * Simpan kompetensi baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['title' => ['required','string','max:120'], 'description' => ['nullable','string'], 'icon' => ['nullable','string','max:60'], 'sort' => ['nullable','integer']]);

        $item = \App\Models\Competency::create($validated);
        $this->logger->log($request->user()?->id, 'admin.competency.create', \App\Models\Competency::class, $item->id);

        return response()->json(['data' => $item], 201);
    }

    /**
     * Perbarui kompetensi.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $item = \App\Models\Competency::findOrFail($id);

        $validated = $request->validate(['title' => ['required','string','max:120'], 'description' => ['nullable','string'], 'icon' => ['nullable','string','max:60'], 'sort' => ['nullable','integer']]);

        $item->update($validated);
        $this->logger->log($request->user()?->id, 'admin.competency.update', \App\Models\Competency::class, $item->id);

        return response()->json(['data' => $item]);
    }

    /**
     * Hapus kompetensi.
     */
    public function destroy(int $id): JsonResponse
    {
        $item = \App\Models\Competency::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'kompetensi berhasil dihapus.']);
    }

    /**
     * Ubah urutan kompetensi (drag & drop di panel).
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        foreach ($validated['ids'] as $i => $id) {
            \App\Models\Competency::where('id', $id)->update(['sort' => $i + 1]);
        }

        return response()->json(['message' => 'Urutan disimpan.']);
    }
}
