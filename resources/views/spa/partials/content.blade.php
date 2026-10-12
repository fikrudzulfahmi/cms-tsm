{{--
    Konten semantik ringkas di dalam <div id="app"> sebagai fallback crawler dan
    pengguna tanpa JavaScript (spec 5.4 poin 3). Vue menggantikan isi ini saat dimuat.

    @var array<string, mixed> $semantic
--}}
@php
    $meta = $semantic['meta'] ?? [];
    $links = $semantic['links'] ?? [];
    $list = $semantic['list'] ?? [];
    $blocks = $semantic['blocks'] ?? [];
    $faqs = $semantic['faqs'] ?? [];
@endphp
    <header>
        <h1>{{ $semantic['h1'] ?? '' }}</h1>
        @if (! empty($meta))
            <p class="meta">@foreach ($meta as $item) <span>{{ $item }}</span>@endforeach</p>
        @endif
        @if (! empty($semantic['lead']))
            <p class="lead">{{ $semantic['lead'] }}</p>
        @endif
    </header>

    <main>
        @if (! empty($semantic['html']))
            <article>{!! $semantic['html'] !!}</article>
        @endif

        @foreach ($blocks as $block)
            <section>
                @if (! empty($block['heading']))
                    <h2>{{ $block['heading'] }}</h2>
                @endif
                @if (! empty($block['html']))
                    <p>{{ $block['html'] }}</p>
                @endif
            </section>
        @endforeach

        @if (! empty($list))
            <section>
                <ul>
                    @foreach ($list as $item)
                        <li>
                            <strong>{{ $item['label'] ?? '' }}</strong>
                            @if (! empty($item['note']))
                                <span class="note"> {{ $item['note'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if (! empty($links))
            <nav aria-label="Daftar tautan">
                <h2>{{ $semantic['links_heading'] ?? 'Berita terkait' }}</h2>
                <ul>
                    @foreach ($links as $link)
                        <li>
                            <a href="{{ $link['href'] }}">{{ $link['label'] }}</a>
                            @if (! empty($link['date']))
                                <span class="meta"> {{ $link['date'] }}</span>
                            @endif
                            @if (! empty($link['note']))
                                <p class="note">{{ $link['note'] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif

        @if (! empty($faqs))
            <section>
                <h2>Pertanyaan yang sering diajukan</h2>
                <dl>
                    @foreach ($faqs as $faq)
                        <dt>{{ $faq['question'] }}</dt>
                        <dd>{{ $faq['answer'] }}</dd>
                    @endforeach
                </dl>
            </section>
        @endif
    </main>
