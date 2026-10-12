<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD + urutan staff CMS TSM.
 * (spec 6.1 — kelola konten landing dari panel admin)
 */
final class StaffController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * Daftar staff.
     */
    public function index(Request $request): JsonResponse
    {
        $items = \App\Models\Staff::query()
            ->when($request->input('q'), fn ($q, $s) => $q->where('title', 'like', '$s%'))
            ->orderBy('sort')
            ->orderByDesc('id')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return response()->json($items);
    }

    /**
     * Simpan staff baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['name' => ['required','string','max:120'], 'role' => ['nullable','string','max:120'], 'bio' => ['nullable','string'], 'sort' => ['nullable','integer']]);

        $item = \App\Models\Staff::create($validated);
        $this->logger->log($request->user()?->id, 'admin.staff.create', \App\Models\Staff::class, $item->id);

        return response()->json(['data' => $item], 201);
    }

    /**
     * Perbarui staff.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $item = \App\Models\Staff::findOrFail($id);

        $validated = $request->validate(['name' => ['required','string','max:120'], 'role' => ['nullable','string','max:120'], 'bio' => ['nullable','string'], 'sort' => ['nullable','integer']]);

        $item->update($validated);
        $this->logger->log($request->user()?->id, 'admin.staff.update', \App\Models\Staff::class, $item->id);

        return response()->json(['data' => $item]);
    }

    /**
     * Hapus staff.
     */
    public function destroy(int $id): JsonResponse
    {
        $item = \App\Models\Staff::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'staff berhasil dihapus.']);
    }

    /**
     * Ubah urutan staff (drag & drop di panel).
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        foreach ($validated['ids'] as $i => $id) {
            \App\Models\Staff::where('id', $id)->update(['sort' => $i + 1]);
        }

        return response()->json(['message' => 'Urutan disimpan.']);
    }
}
