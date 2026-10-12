<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Resources\Public\AchievementResource;
use App\Models\Achievement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AchievementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer'],
            'level' => ['nullable', 'string', 'max:50'],
        ]);

        $query = Achievement::with('image')
            ->where('is_visible', true);

        if (! empty($validated['year'])) {
            $query->where('year', $validated['year']);
        }

        if (! empty($validated['level'])) {
            $query->where('level', $validated['level']);
        }

        $achievements = $query->orderByDesc('year')->orderBy('sort')->get();

        return AchievementResource::collection($achievements)
            ->response()
            ->withHeaders(['Cache-Control' => 'public, max-age=300']);
    }
}
