<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\AlumniFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FeedbackController extends Controller
{
    /**
     * Kirim umpan balik lulusan (status awal pending, dimoderasi admin).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'graduation_year' => ['nullable', 'integer', 'min:1990', 'max:' . (int) date('Y')],
            'occupation' => ['nullable', 'string', 'max:120'],
            'message' => ['required', 'string', 'min:10', 'max:1000'],
            'website' => ['nullable', 'string'], // honeypot
        ]);

        if (! empty($validated['website'])) {
            return response()->json(['message' => 'Terima kasih atas umpan balikmu.'], 202);
        }

        $feedback = AlumniFeedback::create([
            'name' => e($validated['name']),
            'graduation_year' => $validated['graduation_year'] ?? null,
            'occupation' => e($validated['occupation'] ?? ''),
            'message' => e($validated['message']),
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Terima kasih! Umpan balikmu akan ditampilkan setelah dimoderasi.',
            'data' => ['id' => $feedback->id],
        ], 201);
    }
}
