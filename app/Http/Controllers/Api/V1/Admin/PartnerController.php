<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD + urutan mitra CMS TSM.
 * (spec 6.1 — kelola konten landing dari panel admin)
 */
final class PartnerController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * Daftar mitra.
     */
    public function index(Request $request): JsonResponse
    {
        $items = \App\Models\Partner::query()
            ->when($request->input('q'), fn ($q, $s) => $q->where('title', 'like', '$s%'))
            ->orderBy('sort')
            ->orderByDesc('id')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return response()->json($items);
    }

    /**
     * Simpan mitra baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['name' => ['required','string','max:120'], 'sort' => ['nullable','integer']]);

        $item = \App\Models\Partner::create($validated);
        $this->logger->log($request->user()?->id, 'admin.partner.create', \App\Models\Partner::class, $item->id);

        return response()->json(['data' => $item], 201);
    }

    /**
     * Perbarui mitra.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $item = \App\Models\Partner::findOrFail($id);

        $validated = $request->validate(['name' => ['required','string','max:120'], 'sort' => ['nullable','integer']]);

        $item->update($validated);
        $this->logger->log($request->user()?->id, 'admin.partner.update', \App\Models\Partner::class, $item->id);

        return response()->json(['data' => $item]);
    }

    /**
     * Hapus mitra.
     */
    public function destroy(int $id): JsonResponse
    {
        $item = \App\Models\Partner::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'mitra berhasil dihapus.']);
    }

    /**
     * Ubah urutan mitra (drag & drop di panel).
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        foreach ($validated['ids'] as $i => $id) {
            \App\Models\Partner::where('id', $id)->update(['sort' => $i + 1]);
        }

        return response()->json(['message' => 'Urutan disimpan.']);
    }
}
