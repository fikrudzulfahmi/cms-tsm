<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Section;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Konten section landing (spec 6.2). Satu section per key.
 */
final class SectionController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function index(): JsonResponse
    {
        $sections = Section::orderBy('sort')->get();

        return response()->json(['data' => $sections]);
    }

    public function show(string $key): JsonResponse
    {
        $section = Section::where('key', $key)->firstOrFail();

        return response()->json(['data' => $section]);
    }

    public function update(Request $request, string $key): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:160'],
            'subtitle' => ['nullable', 'string', 'max:300'],
            'body' => ['nullable', 'string', 'max:20000'],
            'cta_label' => ['nullable', 'string', 'max:60'],
            'cta_url' => ['nullable', 'string', 'max:255'],
            'extra' => ['nullable', 'array'],
            'is_visible' => ['boolean'],
        ]);

        $section = Section::where('key', $key)->firstOrFail();
        $section->update($validated);

        $this->logger->log($request->user()?->id, 'admin.section.update', Section::class, $section->id, ['key' => $key]);

        return response()->json(['data' => $section]);
    }

    public function order(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'keys' => ['required', 'array', 'min:1'],
            'keys.*' => ['string'],
        ]);

        foreach ($validated['keys'] as $i => $key) {
            Section::where('key', $key)->update(['sort' => $i + 1]);
        }

        return response()->json(['message' => 'Urutan section disimpan.']);
    }
}
