<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Api\V1\Controller;
use App\Jobs\RecordVisit;
use App\Services\VisitorHasher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TrackController extends Controller
{
    public function __construct(private readonly VisitorHasher $hasher) {}

    /**
     * Pencatatan kunjungan (spec 8.1 + 9.3). Selalu 204.
     * IP mentah TIDAK disimpan; hanya visitor_hash.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:255'],
            'post_slug' => ['nullable', 'string', 'max:255'],
            'referrer' => ['nullable', 'string', 'max:500'],
        ]);

        $visitorHash = $this->hasher->hash($request->cookie('vid'), $request->userAgent());
        $ua = (string) $request->userAgent();

        RecordVisit::dispatch(
            path: $validated['path'],
            postSlug: $validated['post_slug'] ?? null,
            visitorHash: $visitorHash,
            referrer: $validated['referrer'] ?? null,
            userAgent: $ua,
        );

        return response()->json(null, 204);
    }
}
