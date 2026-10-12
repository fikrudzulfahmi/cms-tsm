<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Resources\Public\GalleryResource;
use App\Models\GalleryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GalleryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'album' => ['nullable', 'string', 'max:100'],
        ]);

        $query = GalleryItem::with('media');

        if (! empty($validated['album'])) {
            $query->where('album', $validated['album']);
        }

        $items = $query->orderBy('sort')->get();

        return GalleryResource::collection($items)
            ->response()
            ->withHeaders(['Cache-Control' => 'public, max-age=300']);
    }
}
