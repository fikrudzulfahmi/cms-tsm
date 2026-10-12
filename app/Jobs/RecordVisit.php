<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\Visit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Mencatat kunjungan ke anrean (spec 8.1). Tanpa IP mentah.
 */
final class RecordVisit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $path,
        private readonly ?string $postSlug,
        private readonly string $visitorHash,
        private readonly ?string $referrer,
        private readonly string $userAgent,
    ) {}

    public function handle(): void
    {
        $post = $this->postSlug !== null
            ? Post::where('slug', $this->postSlug)->first()
            : null;

        Visit::create([
            'visited_at' => now(),
            'path' => mb_substr($this->path, 0, 255),
            'post_id' => $post?->id,
            'visitor_hash' => $this->visitorHash,
            'referrer_source' => $this->parseSource($this->referrer),
            'referrer_url' => mb_substr((string) $this->referrer, 0, 500),
            'device' => $this->parseDevice($this->userAgent),
            'browser' => $this->parseBrowser($this->userAgent),
            'country' => null,
            'is_bot' => $this->isBot($this->userAgent),
        ]);
    }

    private function parseSource(?string $referrer): ?string
    {
        if ($referrer === null || $referrer === '') {
            return 'direct';
        }
        $host = parse_url($referrer, PHP_URL_HOST);
        if ($host === null || $host === '') {
            return 'direct';
        }
        $host = strtolower($host);

        return match (true) {
            str_contains($host, 'google') || str_contains($host, 'bing') || str_contains($host, 'yahoo') => 'search',
            str_contains($host, 'facebook') || str_contains($host, 'instagram') || str_contains($host, 'x.com')
                || str_contains($host, 'twitter') || str_contains($host, 'tiktok') || str_contains($host, 'whatsapp') => 'social',
            str_contains($host, request()->getHost()) => 'direct',
            default => 'referral',
        };
    }

    private function parseDevice(string $ua): string
    {
        $ua = strtolower($ua);
        if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
            return 'tablet';
        }
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function parseBrowser(string $ua): string
    {
        $ua = strtolower($ua);
        foreach (['edg' => 'Edge', 'opr' => 'Opera', 'chrome' => 'Chrome', 'firefox' => 'Firefox', 'safari' => 'Safari'] as $needle => $name) {
            if (str_contains($ua, $needle)) {
                return $name;
            }
        }

        return 'other';
    }

    private function isBot(string $ua): bool
    {
        $bots = ['googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider', 'yandexbot', 'facebookexternalhit', 'twitterbot', 'whatsapp', 'telegrambot', 'linkedinbot', 'applebot', 'ahrefsbot', 'semrush'];

        $ua = strtolower($ua);
        foreach ($bots as $bot) {
            if (str_contains($ua, $bot)) {
                return true;
            }
        }

        return false;
    }
}
