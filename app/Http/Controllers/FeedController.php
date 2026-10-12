<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Setting;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * `feed.xml` — RSS 2.0 berisi 20 berita terbaru (spec 5.4 poin 6).
 * Dipakai agregator dan pratinjau tautan; dirender server lalu di-cache.
 */
final class FeedController extends Controller
{
    private const CACHE_TTL = 900;

    private const ITEMS = 20;

    public function index(): Response
    {
        $xml = Cache::remember('seo:feed:xml', self::CACHE_TTL, function (): string {
            $siteName = $this->siteName();

            $posts = Post::with(['category', 'author', 'featuredMedia'])
                ->published()
                ->orderByDesc('published_at')
                ->limit(self::ITEMS)
                ->get();

            return view('feeds.rss', [
                'siteName' => $siteName,
                'siteUrl' => url('/'),
                'siteDescription' => $this->description($siteName),
                'language' => 'id-ID',
                'updatedAt' => $posts->first()?->published_at?->toRfc2822() ?? now()->toRfc2822(),
                'items' => $posts->map(fn (Post $post): array => [
                    'title' => $post->title,
                    'link' => url("/berita/{$post->slug}"),
                    'guid' => url("/berita/{$post->slug}"),
                    'description' => $this->excerpt($post->seo_description ?: ($post->excerpt ?: $post->body), 400),
                    'author' => $post->author?->name,
                    'category' => $post->category?->name,
                    'pubDate' => $post->published_at?->toRfc2822(),
                    'image' => $post->featuredMedia?->url(),
                ]),
            ])->render();
        });

        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=900',
        ]);
    }

    private function siteName(): string
    {
        $name = Setting::get('site_name', 'SMKS Islam 1 Kota Blitar');

        return is_string($name) && $name !== '' ? $name : 'SMKS Islam 1 Kota Blitar';
    }

    private function description(string $siteName): string
    {
        $description = Setting::get('seo_description');

        if (is_string($description) && $description !== '') {
            return $description;
        }

        return 'Berita, kegiatan, dan prestasi jurusan Teknik Sepeda Motor '.$siteName.'.';
    }

    private function excerpt(?string $text, int $limit): string
    {
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text)) ?? '');

        return mb_strlen($plain) > $limit ? mb_substr($plain, 0, $limit - 1).'…' : $plain;
    }
}
