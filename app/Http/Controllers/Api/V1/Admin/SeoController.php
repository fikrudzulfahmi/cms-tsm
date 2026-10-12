<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Services\Seo\SeoAnalyzer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SeoController extends Controller
{
    public function __construct(private readonly SeoAnalyzer $analyzer) {}

    /**
     * Analisis SEO real-time (spec 7.2). Debounce 600ms di sisi klien.
     */
    public function analyze(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['nullable', 'integer'],
            'title' => ['nullable', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:75'],
            'body' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string'],
            'focus_keyword' => ['nullable', 'string', 'max:100'],
            'seo_title' => ['nullable', 'string', 'max:200'],
            'seo_description' => ['nullable', 'string', 'max:300'],
            'featured_media_id' => ['nullable', 'integer'],
            'featured_alt' => ['nullable', 'string', 'max:200'],
            'og_media_id' => ['nullable', 'integer'],
        ]);

        $result = $this->analyzer->analyze($validated);

        return response()->json(['data' => $result]);
    }
}
