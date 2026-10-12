@php
    /**
     * Blok <head> SEO untuk shell SPA publik (spec 5.4 poin 2).
     * Semua nilai sudah dibersihkan controller; di sini hanya di-escape sekali lagi.
     *
     * @var array<string, mixed> $seo
     */
    $ogImage = $seo['og_image'] ?? null;
@endphp
    <title>{{ $seo['title'] }}</title>
@if (($seo['description'] ?? '') !== '')
    <meta name="description" content="{{ $seo['description'] }}">
@endif
    <meta name="robots" content="{{ $seo['robots'] }}">
    <link rel="canonical" href="{{ $seo['canonical'] }}">
@if (! empty($seo['root']))
    <link rel="alternate" type="application/rss+xml" title="Berita RSS" href="{{ url('/feed.xml') }}">
@endif

    {{-- Open Graph --}}
    <meta property="og:locale" content="id_ID">
    <meta property="og:type" content="{{ $seo['og_type'] }}">
    <meta property="og:site_name" content="{{ $seo['og_site_name'] }}">
    <meta property="og:title" content="{{ $seo['title'] }}">
@if (($seo['description'] ?? '') !== '')
    <meta property="og:description" content="{{ $seo['description'] }}">
@endif
    <meta property="og:url" content="{{ $seo['canonical'] }}">
@if ($ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:alt" content="{{ $seo['title'] }}">
@endif
@if (! empty($seo['published_time']))
    <meta property="article:published_time" content="{{ $seo['published_time'] }}">
@endif
@if (! empty($seo['modified_time']))
    <meta property="article:modified_time" content="{{ $seo['modified_time'] }}">
@endif
@if (! empty($seo['author']))
    <meta property="article:author" content="{{ $seo['author'] }}">
@endif
@if (! empty($seo['section']))
    <meta property="article:section" content="{{ $seo['section'] }}">
@endif
@foreach (($seo['tags'] ?? []) as $tag)
    <meta property="article:tag" content="{{ $tag }}">
@endforeach

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $seo['title'] }}">
@if (($seo['description'] ?? '') !== '')
    <meta name="twitter:description" content="{{ $seo['description'] }}">
@endif
@if ($ogImage)
    <meta name="twitter:image" content="{{ $ogImage }}">
@endif

    {{-- JSON-LD: NewsArticle, BreadcrumbList, Organization, FAQPage, CollectionPage --}}
@foreach (($seo['jsonld'] ?? []) as $block)
    <script type="application/ld+json">{!! json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endforeach
