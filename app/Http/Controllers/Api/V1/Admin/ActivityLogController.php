<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Services\SecurityMonitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ActivityLogController extends Controller
{
    public function __construct(private readonly SecurityMonitor $monitor) {}

    /**
     * Semua log aktivitas admin (spec 6.1 + 9.4).
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['nullable', 'string'],
            'user_id' => ['nullable', 'integer'],
            'security' => ['nullable', 'boolean'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = \App\Models\User::query()->getQuery();
        $logTable = (new \App\Models\User)->getConnection()->getTablePrefix() . 'activity_logs';

        $logs = \Illuminate\Support\Facades\DB::table('activity_logs')
            ->leftJoin('users', 'activity_logs.user_id', '=', 'users.id')
            ->select('activity_logs.*', 'users.name as user_name', 'users.email as user_email');

        if (! empty($validated['action'])) {
            $logs->where('activity_logs.action', $validated['action']);
        }
        if (! empty($validated['user_id'])) {
            $logs->where('activity_logs.user_id', $validated['user_id']);
        }
        if (! empty($validated['security'])) {
            $logs->where('activity_logs.action', 'like', 'security.%');
        }
        if (! empty($validated['from'])) {
            $logs->where('activity_logs.created_at', '>=', $validated['from']);
        }
        if (! empty($validated['to'])) {
            $logs->where('activity_logs.created_at', '<=', $validated['to'] . ' 23:59:59');
        }

        $logs->orderByDesc('activity_logs.created_at');
        $result = $logs->paginate((int) ($validated['per_page'] ?? 25));

        return response()->json($result);
    }

    /**
     * Khusus deteksi serangan: log security.* + IP yang diblokir + ringkasan.
     */
    public function security(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $logs = \Illuminate\Support\Facades\DB::table('activity_logs')
            ->leftJoin('users', 'activity_logs.user_id', '=', 'users.id')
            ->select('activity_logs.*', 'users.name as user_name', 'users.email as user_email')
            ->where('activity_logs.action', 'like', 'security.%')
            ->orderByDesc('activity_logs.created_at');

        $result = $logs->paginate((int) ($validated['per_page'] ?? 25));

        // ringkasan 24 jam terakhir
        $since = now()->subDay();
        $threats = \Illuminate\Support\Facades\DB::table('activity_logs')
            ->where('action', 'like', 'security.%')
            ->where('created_at', '>=', $since)
            ->selectRaw('subject_type as threat_type, COUNT(*) as total')
            ->groupBy('subject_type')
            ->get();
        $banned = \Illuminate\Support\Facades\DB::table('activity_logs')
            ->where('action', 'security.ip_banned')
            ->where('created_at', '>=', $since)
            ->count();
        $failedLogins = \Illuminate\Support\Facades\DB::table('cms_login_attempts')
            ->where('created_at', '>=', $since)
            ->where('success', false)
            ->count();

        // daftar IP diblokir (dari cache — SecurityMonitor)
        $bannedIps = \Illuminate\Support\Facades\Cache::get('sec:banned_ips', []);

        return response()->json([
            'data' => $result->items(),
            'meta' => [
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'total' => $result->total(),
                'per_page' => $result->perPage(),
            ],
            'summary' => [
                'threats_by_type' => $threats,
                'banned_24h' => $banned,
                'failed_logins_24h' => $failedLogins,
                'banned_ips' => $bannedIps,
            ],
        ]);
    }

    public function unban(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate(['ip' => ['required', 'string']]);

        $this->monitor->unban($validated['ip']);
        \App\Services\ActivityLogger::log($request->user()?->id, 'security.ip_unbanned', meta: ['ip' => $validated['ip']]);

        return response()->json(['message' => 'IP berhasil dibebaskan dari blokir.']);
    }
}
