<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\MessageType;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ContactController extends Controller
{
    /**
     * Pesan/pendaftaran dari publik (spec 9.3).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'message' => ['required', 'string', 'min:5', 'max:2000'],
            'type' => ['nullable', 'string', 'in:pendaftaran,pertanyaan'],
            'website' => ['nullable', 'string'], // honeypot
        ]);

        if (! empty($validated['website'])) {
            return response()->json(['message' => 'Pesan kamu terkirim.'], 202);
        }

        $message = ContactMessage::create([
            'name' => e($validated['name']),
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'message' => e($validated['message']),
            'type' => $validated['type'] ?? MessageType::Pertanyaan->value,
            'status' => 'baru',
        ]);

        return response()->json([
            'message' => 'Pesan kamu terkirim. Tim kami akan membalas secepatnya.',
            'data' => ['id' => $message->id],
        ], 201);
    }
}
