{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
{{-- sitemap.xml: halaman statis + berita terbit + kategori berisi berita (spec 5.4 poin 6). --}}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($staticPages as $page)
    <url>
        <loc>{{ $page['loc'] }}</loc>
        <lastmod>{{ $page['lastmod'] }}</lastmod>
        <changefreq>{{ $page['changefreq'] }}</changefreq>
        <priority>{{ $page['priority'] }}</priority>
    </url>
@endforeach
@foreach ($categories as $category)
    <url>
        <loc>{{ $category['loc'] }}</loc>
@if (! empty($category['lastmod']))
        <lastmod>{{ $category['lastmod'] }}</lastmod>
@endif
        <changefreq>{{ $category['changefreq'] }}</changefreq>
        <priority>{{ $category['priority'] }}</priority>
    </url>
@endforeach
@foreach ($posts as $post)
    <url>
        <loc>{{ $post['loc'] }}</loc>
@if (! empty($post['lastmod']))
        <lastmod>{{ $post['lastmod'] }}</lastmod>
@endif
        <changefreq>{{ $post['changefreq'] }}</changefreq>
        <priority>{{ $post['priority'] }}</priority>
    </url>
@endforeach
</urlset>
