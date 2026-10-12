<?php

namespace App\Http\Middleware;

use App\Services\SecurityMonitor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mendeteksi pola serangan pada setiap request masuk dan memblokir IP yang
 * sudah melewati ambang (SecurityMonitor).
 */
final class DetectIntrusions
{
    public function __construct(private readonly SecurityMonitor $monitor) {}

    public function handle(Request $request, Closure $next): Response
    {
        // 1. IP sudah diblokir? tolak cepat 403.
        $reason = $this->monitor->isBanned($request->ip());
        if ($reason !== null) {
            return response()->json([
                'message' => 'Akses ditolak.',
                'reason' => $reason,
            ], 403, ['Retry-After' => 3600]);
        }

        // 2. Analisis pola serangan pada request ini.
        $match = $this->monitor->analyze($request);
        if ($match !== null) {
            $banned = $this->monitor->recordHit($request, $match);

            if ($banned) {
                return response()->json([
                    'message' => 'Akses diblokir karena aktivitas mencurigakan berulang.',
                ], 403);
            }

            // hit pertama: tolak request ini saja, tapi belum ban
            return response()->json([
                'message' => 'Request ditolak.',
            ], 400);
        }

        return $next($request);
    }
}
