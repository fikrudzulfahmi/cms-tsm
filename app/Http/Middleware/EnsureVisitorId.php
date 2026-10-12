<?php

namespace App\Http\Middleware;

use App\Services\VisitorHasher;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menerbitkan cookie `vid` (visitor id) untuk pengunjung anonim (spec 7.3):
 * acak, HttpOnly, Secure, SameSite=Lax, berlaku 1 tahun.
 * IP mentah tidak pernah disimpan — hanya HMAC(vid + UA, secret).
 */
final class EnsureVisitorId
{
    public const COOKIE = 'vid';
    public const ONE_YEAR = 60 * 24 * 365;

    public function __construct(private readonly VisitorHasher $hasher) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasCookie(self::COOKIE)) {
            $response = $next($request);
            $response->withCookie(cookie(
                name: self::COOKIE,
                value: $this->hasher->generateId(),
                minutes: self::ONE_YEAR,
                httpOnly: true,
                secure: false, // lokal http; produksi pakai HTTPS (env cookie_secure)
                sameSite: 'lax'
            ));

            return $response;
        }

        return $next($request);
    }
}
