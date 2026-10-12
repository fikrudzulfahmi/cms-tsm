{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
{{-- feed.xml: RSS 2.0, 20 berita terbit terbaru (spec 5.4 poin 6). --}}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ $siteName }}</title>
        <link>{{ $siteUrl }}</link>
        <description>{{ $siteDescription }}</description>
        <language>{{ $language }}</language>
        <lastBuildDate>{{ $updatedAt }}</lastBuildDate>
        <generator>CMS TSM (Laravel)</generator>
        <atom:link href="{{ url('/feed.xml') }}" rel="self" type="application/rss+xml"/>
@foreach ($items as $item)
        <item>
            <title>{{ $item['title'] }}</title>
            <link>{{ $item['link'] }}</link>
            <guid isPermaLink="true">{{ $item['guid'] }}</guid>
            <description>{{ $item['description'] }}</description>
@if (! empty($item['author']))
            <author>{{ $item['author'] }}</author>
@endif
@if (! empty($item['category']))
            <category>{{ $item['category'] }}</category>
@endif
@if (! empty($item['image']))
            <enclosure url="{{ $item['image'] }}" type="image/jpeg"/>
@endif
@if (! empty($item['pubDate']))
            <pubDate>{{ $item['pubDate'] }}</pubDate>
@endif
        </item>
@endforeach
    </channel>
</rss>
