<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Section;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Cangkang SEO untuk SPA (spec 5.4).
 *
 * Merender blade tipis dengan <head> terisi meta SEO + JSON-LD, dan
 * konten semantik ringkas di dalam #app sebagai fallback crawler.
 * Vue menggantikan isi ini tanpa hydration.
 */
class SeoShellController extends Controller
{
    public function public(Request $request, ?string $any = null)
    {
        $path = $any ?? '/';
        $seo = $this->resolvePublicSeo($path);

        // slug tidak ditemukan → 404 sungguhan (spec 5.4 poin 4)
        if ($seo === null) {
            abort(404);
        }

        $initial = json_encode($seo['initial'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return view('spa.public', [
            'seo' => $seo,
            'initial' => $initial,
        ]);
    }

    public function admin(?string $any = null)
    {
        return view('spa.admin');
    }

    private function resolvePublicSeo(string $path): ?array
    {
        $siteName = (string) Setting::get('site_name', 'Jurusan Teknik Sepeda Motor');
        $siteDesc = (string) Setting::get('seo_description', '');
        $canonical = url($path);

        // berita: /berita/{slug}
        if (preg_match('#^berita/([a-z0-9-]+)$#', $path, $m)) {
            $post = Post::with(['category', 'featuredMedia', 'author'])
                ->where('slug', $m[1])
                ->published()
                ->first();

            if ($post === null) {
                return null;
            }

            $title = $post->seo_title ?: $post->title;
            $description = $post->seo_description ?: $post->excerpt;
            $ogImage = $post->ogMedia?->url() ?: $post->featuredMedia?->url();

            $jsonLd = json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'NewsArticle',
                'headline' => $title,
                'description' => $description,
                'datePublished' => $post->published_at?->toIso8601String(),
                'author' => ['@type' => 'Person', 'name' => $post->author?->name],
                'publisher' => ['@type' => 'Organization', 'name' => $siteName],
                'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonical],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return [
                'title' => $title . ' — ' . $siteName,
                'description' => $description,
                'canonical' => $canonical,
                'og_type' => 'article',
                'og_image' => $ogImage,
                'json_ld' => $jsonLd,
                'h1' => $post->title,
                'fallback_text' => $description,
                'fallback_body' => Str::limit(strip_tags((string) $post->body), 800),
                'initial' => ['post' => $post->only(['id', 'title', 'slug', 'excerpt'])],
            ];
        }

        // kategori: /berita/kategori/{slug}
        if (preg_match('#^berita/kategori/([a-z0-9-]+)$#', $path, $m)) {
            $category = Category::where('slug', $m[1])->first();
            if ($category === null) {
                return null;
            }

            return [
                'title' => ($category->seo_title ?: 'Berita ' . $category->name) . ' — ' . $siteName,
                'description' => $category->seo_description ?: $category->description,
                'canonical' => $canonical,
                'og_type' => 'website',
                'h1' => 'Berita ' . $category->name,
                'fallback_text' => $category->description,
            ];
        }

        // halaman statis SPA
        $pageSeo = [
            '' => ['title' => (string) Setting::get('seo_title', $siteName), 'h1' => $siteName],
            'berita' => ['title' => 'Berita — ' . $siteName, 'h1' => 'Berita'],
            'prestasi' => ['title' => 'Prestasi — ' . $siteName, 'h1' => 'Prestasi siswa'],
            'galeri' => ['title' => 'Galeri — ' . $siteName, 'h1' => 'Galeri kegiatan'],
            'tentang' => ['title' => 'Tentang — ' . $siteName, 'h1' => 'Tentang jurusan'],
            'kontak' => ['title' => 'Kontak — ' . $siteName, 'h1' => 'Kontak dan lokasi'],
        ];

        $page = $pageSeo[$path] ?? null;
        if ($page === null) {
            // section kustom / 404 lembut untuk SPA route valid lainnya
            return [
                'title' => $siteName,
                'description' => $siteDesc,
                'canonical' => $canonical,
                'og_type' => 'website',
                'h1' => $siteName,
                'fallback_text' => $siteDesc,
            ];
        }

        return [
            'title' => $page['title'],
            'description' => $siteDesc,
            'canonical' => $canonical,
            'og_type' => 'website',
            'h1' => $page['h1'],
            'fallback_text' => $siteDesc,
        ];
    }
}
