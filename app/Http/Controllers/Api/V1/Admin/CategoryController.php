<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD + urutan kategori CMS TSM.
 * (spec 6.1 — kelola konten landing dari panel admin)
 */
final class CategoryController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * Daftar kategori.
     */
    public function index(Request $request): JsonResponse
    {
        $items = \App\Models\Category::query()
            ->when($request->input('q'), fn ($q, $s) => $q->where('title', 'like', '$s%'))
            ->orderBy('sort')
            ->orderByDesc('id')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return response()->json($items);
    }

    /**
     * Simpan kategori baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['name' => ['required','string','max:120'], 'slug' => ['nullable','string','max:140'], 'description' => ['nullable','string'], 'sort' => ['nullable','integer']]);

        $item = \App\Models\Category::create($validated);
        $this->logger->log($request->user()?->id, 'admin.category.create', \App\Models\Category::class, $item->id);

        return response()->json(['data' => $item], 201);
    }

    /**
     * Perbarui kategori.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $item = \App\Models\Category::findOrFail($id);

        $validated = $request->validate(['name' => ['required','string','max:120'], 'slug' => ['nullable','string','max:140'], 'description' => ['nullable','string'], 'sort' => ['nullable','integer']]);

        $item->update($validated);
        $this->logger->log($request->user()?->id, 'admin.category.update', \App\Models\Category::class, $item->id);

        return response()->json(['data' => $item]);
    }

    /**
     * Hapus kategori.
     */
    public function destroy(int $id): JsonResponse
    {
        $item = \App\Models\Category::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'kategori berhasil dihapus.']);
    }

    /**
     * Ubah urutan kategori (drag & drop di panel).
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        foreach ($validated['ids'] as $i => $id) {
            \App\Models\Category::where('id', $id)->update(['sort' => $i + 1]);
        }

        return response()->json(['message' => 'Urutan disimpan.']);
    }
}
