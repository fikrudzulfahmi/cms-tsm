<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pengaturan site (spec 6.2). Dikelompokkan: umum, seo, komentar.
 */
final class SettingsController extends Controller
{
    private const GROUPS = [
        'umum' => ['site_name', 'tagline', 'school_address', 'school_whatsapp', 'school_email', 'service_hours', 'google_maps_url', 'footer_text'],
        'seo' => ['seo_title', 'seo_description', 'seo_keywords', 'seo_publish_min_score', 'google_analytics_id'],
        'komentar' => ['comment_auto_approve', 'comment_max_links', 'banned_words'],
    ];

    public function __construct(private readonly ActivityLogger $logger) {}

    public function show(string $group): JsonResponse
    {
        $keys = self::GROUPS[$group] ?? null;

        if ($keys === null) {
            abort(404, 'Grup pengaturan tidak ditemukan.');
        }

        $settings = Setting::whereIn('key', $keys)->pluck('value', 'key');

        // isi default kosong supaya form lengkap
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = $settings->has($k) ? $settings->get($k) : '';
        }

        return response()->json(['data' => $out]);
    }

    public function update(Request $request, string $group): JsonResponse
    {
        $keys = self::GROUPS[$group] ?? null;

        if ($keys === null) {
            abort(404, 'Grup pengaturan tidak ditemukan.');
        }

        $validated = $request->validate(
            collect($keys)->mapWithKeys(fn ($k) => [$k => ['nullable']])->toArray()
        );

        foreach ($validated as $key => $value) {
            if ($value === null) {
                continue;
            }

            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => is_array($value) ? json_encode($value) : (string) $value]
            );
        }

        $this->logger->log($request->user()?->id, 'admin.settings.update', null, null, ['group' => $group]);

        return response()->json(['message' => 'Pengaturan disimpan.']);
    }
}
