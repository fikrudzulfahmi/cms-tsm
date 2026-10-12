<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Deteksi serangan otomatis pada setiap request (permintaan khusus: domain
 * sekolah sering kena serangan hacker).
 *
 * Menganalisis: path, query, header, dan body mentah terhadap pola:
 *  - SQL injection (union select, sleep(, information_schema, comment tricks)
 *  - XSS (<script>, onerror=, javascript:, document.cookie)
 *  - Path traversal (../, %2e%2e, /etc/passwd, web.config)
 *  - Command injection / RCE (system(, eval(, php://, base64_decode, wget)
 *  - Laravel debug exposure (.env, _ignition, telescope)
 *  - Local file inclusion
 *
 * Setiap kecocokan dicatat ke activity_logs (severity tinggi) + counter per IP.
 * Bila melewati ambang dalam jendela waktu, IP diblokir otomatis lewat cache
 * (dipakai middleware BlockBannedIp).
 */
final class SecurityMonitor
{
    public const BAN_THRESHOLD = 5;      // hit mencurigakan sebelum diblokir
    public const BAN_WINDOW_MINUTES = 10; // jendela penghitung
    public const BAN_DURATION_HOURS = 24; // lama blokir

    private const PATTERNS = [
        'sql_injection' => [
            '/\bunion[\s\/\*]+(?:all[\s\/\*]+)?select\b/i',
            '/\bselect\b[\s\S]{1,200}\bfrom\b[\s\S]{1,120}\b(information_schema|mysql\.|pg_)/i',
            '/\b(?:sleep|benchmark|pg_sleep|waitfor\s+delay)\s*\(/i',
            '/\b(?:or|and)\s+["\']?1["\']?\s*=\s*["\']?1["\']?/i',
            '/\/\*[\s\S]*\*\//',
            "/'\\s*(?:or|and|union|;|--|#)/i",
            '/\bdrop\s+table\b/i',
            '/\binsert\s+into\b[\s\S]{0,80}\bvalues\b/i',
        ],
        'xss' => [
            '/<script[\s\S]*?>/i',
            '/javascript:\s*/i',
            '/\bon(?:error|load|click|mouseover|focus|submit)\s*=\s*["\']?/i',
            '/<iframe[\s\S]*?>/i',
            '/document\.cookie/i',
            '/<img[\s\S]*?src[\s\S]*?onerror/i',
            '/alert\s*\(/i',
            '/eval\s*\(\s*(?:atob|String\.fromCharCode)/i',
        ],
        'path_traversal' => [
            '/\.\.[\/\\\\]/',
            '#%2e%2e[%/\\\\]#i',
            '/\/etc\/(?:passwd|shadow|hosts)/i',
            '/\/(?:proc\/self|var\/www)/i',
            '/web\.config/i',
            '/\.\.%2f/i',
            '/%252e%252e/i',
        ],
        'command_injection' => [
            '/\b(?:system|exec|passthru|shell_exec|popen|proc_open)\s*\(/i',
            '/php:\/\/(?:input|filter|memory)/i',
            '/base64_decode\s*\(/i',
            '/\b(?:wget|curl|nc|bash|sh)\s+-/i',
            '/;\s*(?:cat|ls|id|whoami|uname)\s/i',
        ],
        'laravel_exploit' => [
            '/\.env\b/i',
            '/_ignition\/(?:execute-solution|health-check)/i',
            '/telescope/i',
            '/horizon\/api/i',
            '/log-viewer/i',
        ],
        'lfi' => [
            '/filter\/resource=/i',
            '/php:\/\/filter/i',
            '/\bfile:\/\/\//i',
        ],
    ];

    public function analyze(Request $request): ?array
    {
        $targets = [
            $request->path(),
            $request->getQueryString() ?? '',
            $request->userAgent() ?? '',
        ];

        // body mentah hanya bila kecil (hindari beban pada unggah besar)
        $raw = $this->rawBody($request);
        if ($raw !== null && strlen($raw) < 64_000) {
            $targets[] = $raw;
        }

        foreach ($targets as $target) {
            foreach (self::PATTERNS as $type => $patterns) {
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, (string) $target)) {
                        return [
                            'type' => $type,
                            'pattern' => $pattern,
                            'target' => mb_substr((string) $target, 0, 300),
                        ];
                    }
                }
            }
        }

        return null;
    }

    public function recordHit(Request $request, array $match): bool
    {
        $ip = $request->ip();
        if ($ip === null) {
            return false;
        }

        $key = "sec:hits:{$ip}";
        $hits = Cache::increment($key);
        if ($hits === 1) {
            Cache::put($key, 1, now()->addMinutes(self::BAN_WINDOW_MINUTES));
        }

        ActivityLogger::log(
            userId: null,
            action: 'security.threat_detected',
            subjectType: $match['type'],
            subjectId: null,
            meta: [
                'ip' => $ip,
                'path' => '/' . $request->path(),
                'pattern' => $match['pattern'],
                'match' => mb_substr($match['target'], 0, 200),
                'method' => $request->method(),
                'hits' => $hits,
            ]
        );

        Log::warning('SecurityMonitor: ancaman terdeteksi', [
            'ip' => $ip,
            'type' => $match['type'],
            'path' => '/' . $request->path(),
        ]);

        if ($hits >= self::BAN_THRESHOLD) {
            $this->ban($ip, $match['type']);

            return true;
        }

        return false;
    }

    public function ban(string $ip, string $reason): void
    {
        Cache::put("sec:ban:{$ip}", $reason, now()->addHours(self::BAN_DURATION_HOURS));
        ActivityLogger::log(
            userId: null,
            action: 'security.ip_banned',
            subjectType: $reason,
            subjectId: null,
            meta: ['ip' => $ip, 'duration_hours' => self::BAN_DURATION_HOURS]
        );
    }

    public function isBanned(?string $ip): ?string
    {
        if ($ip === null) {
            return null;
        }

        /** @var string|null $reason */
        $reason = Cache::get("sec:ban:{$ip}");

        return $reason;
    }

    public function unban(string $ip): void
    {
        Cache::forget("sec:ban:{$ip}");
        Cache::forget("sec:hits:{$ip}");
    }

    private function rawBody(Request $request): ?string
    {
        try {
            $content = $request->getContent();

            return $content === '' ? null : $content;
        } catch (\Throwable) {
            return null;
        }
    }
}
