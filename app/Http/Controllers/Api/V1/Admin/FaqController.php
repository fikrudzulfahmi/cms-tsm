<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD + urutan FAQ CMS TSM.
 * (spec 6.1 — kelola konten landing dari panel admin)
 */
final class FaqController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * Daftar FAQ.
     */
    public function index(Request $request): JsonResponse
    {
        $items = \App\Models\Faq::query()
            ->when($request->input('q'), fn ($q, $s) => $q->where('title', 'like', '$s%'))
            ->orderBy('sort')
            ->orderByDesc('id')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return response()->json($items);
    }

    /**
     * Simpan FAQ baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['question' => ['required','string','max:200'], 'answer' => ['required','string'], 'sort' => ['nullable','integer']]);

        $item = \App\Models\Faq::create($validated);
        $this->logger->log($request->user()?->id, 'admin.faq.create', \App\Models\Faq::class, $item->id);

        return response()->json(['data' => $item], 201);
    }

    /**
     * Perbarui FAQ.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $item = \App\Models\Faq::findOrFail($id);

        $validated = $request->validate(['question' => ['required','string','max:200'], 'answer' => ['required','string'], 'sort' => ['nullable','integer']]);

        $item->update($validated);
        $this->logger->log($request->user()?->id, 'admin.faq.update', \App\Models\Faq::class, $item->id);

        return response()->json(['data' => $item]);
    }

    /**
     * Hapus FAQ.
     */
    public function destroy(int $id): JsonResponse
    {
        $item = \App\Models\Faq::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'FAQ berhasil dihapus.']);
    }

    /**
     * Ubah urutan FAQ (drag & drop di panel).
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        foreach ($validated['ids'] as $i => $id) {
            \App\Models\Faq::where('id', $id)->update(['sort' => $i + 1]);
        }

        return response()->json(['message' => 'Urutan disimpan.']);
    }
}
