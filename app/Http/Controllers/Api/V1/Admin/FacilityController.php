<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD + urutan sarana CMS TSM.
 * (spec 6.1 — kelola konten landing dari panel admin)
 */
final class FacilityController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * Daftar sarana.
     */
    public function index(Request $request): JsonResponse
    {
        $items = \App\Models\Facility::query()
            ->when($request->input('q'), fn ($q, $s) => $q->where('title', 'like', '$s%'))
            ->orderBy('sort')
            ->orderByDesc('id')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return response()->json($items);
    }

    /**
     * Simpan sarana baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['name' => ['required','string','max:120'], 'spec' => ['nullable','string'], 'quantity' => ['nullable','integer'], 'sort' => ['nullable','integer']]);

        $item = \App\Models\Facility::create($validated);
        $this->logger->log($request->user()?->id, 'admin.facility.create', \App\Models\Facility::class, $item->id);

        return response()->json(['data' => $item], 201);
    }

    /**
     * Perbarui sarana.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $item = \App\Models\Facility::findOrFail($id);

        $validated = $request->validate(['name' => ['required','string','max:120'], 'spec' => ['nullable','string'], 'quantity' => ['nullable','integer'], 'sort' => ['nullable','integer']]);

        $item->update($validated);
        $this->logger->log($request->user()?->id, 'admin.facility.update', \App\Models\Facility::class, $item->id);

        return response()->json(['data' => $item]);
    }

    /**
     * Hapus sarana.
     */
    public function destroy(int $id): JsonResponse
    {
        $item = \App\Models\Facility::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'sarana berhasil dihapus.']);
    }

    /**
     * Ubah urutan sarana (drag & drop di panel).
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        foreach ($validated['ids'] as $i => $id) {
            \App\Models\Facility::where('id', $id)->update(['sort' => $i + 1]);
        }

        return response()->json(['message' => 'Urutan disimpan.']);
    }
}
