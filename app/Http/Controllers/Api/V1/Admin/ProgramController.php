<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD + urutan program CMS TSM.
 * (spec 6.1 — kelola konten landing dari panel admin)
 */
final class ProgramController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * Daftar program.
     */
    public function index(Request $request): JsonResponse
    {
        $items = \App\Models\Program::query()
            ->when($request->input('q'), fn ($q, $s) => $q->where('title', 'like', '$s%'))
            ->orderBy('sort')
            ->orderByDesc('id')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return response()->json($items);
    }

    /**
     * Simpan program baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['title' => ['required','string','max:120'], 'description' => ['nullable','string'], 'sort' => ['nullable','integer']]);

        $item = \App\Models\Program::create($validated);
        $this->logger->log($request->user()?->id, 'admin.program.create', \App\Models\Program::class, $item->id);

        return response()->json(['data' => $item], 201);
    }

    /**
     * Perbarui program.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $item = \App\Models\Program::findOrFail($id);

        $validated = $request->validate(['title' => ['required','string','max:120'], 'description' => ['nullable','string'], 'sort' => ['nullable','integer']]);

        $item->update($validated);
        $this->logger->log($request->user()?->id, 'admin.program.update', \App\Models\Program::class, $item->id);

        return response()->json(['data' => $item]);
    }

    /**
     * Hapus program.
     */
    public function destroy(int $id): JsonResponse
    {
        $item = \App\Models\Program::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'program berhasil dihapus.']);
    }

    /**
     * Ubah urutan program (drag & drop di panel).
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        foreach ($validated['ids'] as $i => $id) {
            \App\Models\Program::where('id', $id)->update(['sort' => $i + 1]);
        }

        return response()->json(['message' => 'Urutan disimpan.']);
    }
}
