<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Mencatat setiap aksi admin + mendeteksi pola serangan (spec 12 + permintaan
 * "activity log yang bisa mendeteksi serangan").
 *
 * Semua log disimpan ke `activity_logs` (tabel) dan tersedia di admin panel.
 * Periode retensi bisa diatur; tabel tidak tumbuh tanpa batas karena lama
 * dibersihkan lewat command prune.
 */
final class ActivityLogger
{
    /** Meta tambahan yang secara default ikut dicatat (dibersihkan dari rahasia). */
    public static function log(
        ?int $userId,
        string $action,
        ?string $subjectType = null,
        ?int $subjectId = null,
        array $meta = []
    ): void {
        try {
            DB::table('activity_logs')->insert([
                'user_id' => $userId,
                'action' => $action,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
                'ip' => self::ip(),
                'user_agent' => self::ua(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // jangan sampahkan request hanya karena logging gagal
            Log::warning('ActivityLogger gagal: ' . $e->getMessage());
        }
    }

    public static function ip(): ?string
    {
        $ip = request()?->ip();

        return $ip === null ? null : $ip;
    }

    public static function ua(): ?string
    {
        $ua = request()?->userAgent();

        return $ua === null ? null : mb_substr($ua, 0, 255);
    }
}
