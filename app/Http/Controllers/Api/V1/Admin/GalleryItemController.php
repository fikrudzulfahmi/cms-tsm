<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD + urutan foto galeri CMS TSM.
 * (spec 6.1 — kelola konten landing dari panel admin)
 */
final class GalleryItemController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * Daftar foto galeri.
     */
    public function index(Request $request): JsonResponse
    {
        $items = \App\Models\GalleryItem::query()
            ->when($request->input('q'), fn ($q, $s) => $q->where('title', 'like', '$s%'))
            ->orderBy('sort')
            ->orderByDesc('id')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return response()->json($items);
    }

    /**
     * Simpan foto galeri baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['caption' => ['nullable','string','max:160'], 'sort' => ['nullable','integer']]);

        $item = \App\Models\GalleryItem::create($validated);
        $this->logger->log($request->user()?->id, 'admin.gallery.create', \App\Models\GalleryItem::class, $item->id);

        return response()->json(['data' => $item], 201);
    }

    /**
     * Perbarui foto galeri.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $item = \App\Models\GalleryItem::findOrFail($id);

        $validated = $request->validate(['caption' => ['nullable','string','max:160'], 'sort' => ['nullable','integer']]);

        $item->update($validated);
        $this->logger->log($request->user()?->id, 'admin.gallery.update', \App\Models\GalleryItem::class, $item->id);

        return response()->json(['data' => $item]);
    }

    /**
     * Hapus foto galeri.
     */
    public function destroy(int $id): JsonResponse
    {
        $item = \App\Models\GalleryItem::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'foto galeri berhasil dihapus.']);
    }

    /**
     * Ubah urutan foto galeri (drag & drop di panel).
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        foreach ($validated['ids'] as $i => $id) {
            \App\Models\GalleryItem::where('id', $id)->update(['sort' => $i + 1]);
        }

        return response()->json(['message' => 'Urutan disimpan.']);
    }
}
