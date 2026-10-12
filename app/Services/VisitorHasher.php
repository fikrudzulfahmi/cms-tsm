<?php

namespace App\Services;

/**
 * Meng-hash identitas pengunjung tanpa menyimpan IP mentah (spec 7.3 & 12).
 *
 * visitor_hash = HMAC-SHA256(vid + userAgent, secret)
 * - vid adalah cookie acak HttpOnly sisi klien.
 * - IP mentah TIDAK PERNAH disimpan; hanya dipakai transient untuk
 *   deteksi serangan (SecurityMonitor) dan dibuang.
 */
final class VisitorHasher
{
    public function __construct(?string $appKey = null)
    {
        $this->appKey = $appKey ?? (string) config('app.key');
    }

    public function hash(?string $visitorId, ?string $userAgent): string
    {
        $vid = (string) ($visitorId ?? '');
        $ua = (string) ($userAgent ?? '');

        return hash_hmac('sha256', $vid . "\0" . $ua, $this->appKey);
    }

    /**
     * Buat visitor id baru (48 byte random, base64url) untuk cookie `vid`.
     */
    public function generateId(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
    }
}
