{{--
    Data awal SPA (spec 5.4 poin 5) agar render pertama tidak menunggu API.
    JSON_HEX_TAG mencegah `</script>` di dalam data menutup tag lebih awal.
--}}
@php
    /** @var array<string, mixed> $initial */
    $json = json_encode($initial, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
@endphp
<script>window.__INITIAL__ = {!! $json === false ? '{}' : $json !!};</script>
