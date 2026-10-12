<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * `sitemap.xml` dan `robots.txt` dirender server dan di-cache (spec 5.4 poin 6).
 */
final class SitemapController extends Controller
{
    private const CACHE_TTL = 3600;

    /**
     * Halaman statis SPA publik beserta prioritasnya.
     *
     * @var array<string, array{priority: string, changefreq: string}>
     */
    private const STATIC_PAGES = [
        '/' => ['priority' => '1.0', 'changefreq' => 'daily'],
        '/berita' => ['priority' => '0.9', 'changefreq' => 'daily'],
        '/prestasi' => ['priority' => '0.7', 'changefreq' => 'weekly'],
        '/tentang' => ['priority' => '0.7', 'changefreq' => 'monthly'],
        '/galeri' => ['priority' => '0.6', 'changefreq' => 'weekly'],
        '/kontak' => ['priority' => '0.6', 'changefreq' => 'monthly'],
    ];

    public function index(): Response
    {
        $xml = Cache::remember('seo:sitemap:xml', self::CACHE_TTL, function (): string {
            return view('seo.sitemap', [
                'staticPages' => collect(self::STATIC_PAGES)->map(fn (array $meta, string $path): array => [
                    'loc' => url($path),
                    'lastmod' => now()->toDateString(),
                    'changefreq' => $meta['changefreq'],
                    'priority' => $meta['priority'],
                ])->values(),

                'posts' => Post::query()
                    ->published()
                    ->orderByDesc('published_at')
                    ->limit(5000)
                    ->get(['slug', 'published_at', 'updated_at'])
                    ->map(fn (Post $post): array => [
                        'loc' => url("/berita/{$post->slug}"),
                        'lastmod' => ($post->updated_at ?? $post->published_at)?->toDateString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.8',
                    ]),

                'categories' => Category::query()
                    ->withCount('posts')
                    ->having('posts_count', '>', 0)
                    ->get(['id', 'slug', 'updated_at'])
                    ->map(fn (Category $category): array => [
                        'loc' => url("/berita/kategori/{$category->slug}"),
                        'lastmod' => $category->updated_at?->toDateString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.6',
                    ]),
            ])->render();
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /admin/',
            'Disallow: /api/',
            '',
            'Sitemap: '.url('/sitemap.xml'),
            'Sitemap: '.url('/feed.xml'),
            '',
        ];

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
