<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

@php
    $seoPayload = $seo ?? app(App\Support\Seo\SeoManager::class)->meta(
        title: $title ?? null,
        robots: $robots ?? 'index, follow',
    );
    $structuredData = is_array($seoPayload['structured_data'] ?? null) ? $seoPayload['structured_data'] : [];
@endphp

<title>{{ $seoPayload['title'] }}</title>
<meta name="description" content="{{ $seoPayload['description'] }}">
<meta name="robots" content="{{ $seoPayload['robots'] }}">
<link rel="canonical" href="{{ $seoPayload['canonical'] }}">

<meta property="og:site_name" content="{{ config('app.name', 'Amarvero') }}">
<meta property="og:title" content="{{ $seoPayload['title'] }}">
<meta property="og:description" content="{{ $seoPayload['description'] }}">
<meta property="og:type" content="{{ $seoPayload['type'] }}">
<meta property="og:url" content="{{ $seoPayload['canonical'] }}">

@if ($seoPayload['image'])
    <meta property="og:image" content="{{ $seoPayload['image'] }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ $seoPayload['image'] }}">
@else
    <meta name="twitter:card" content="summary">
@endif

<meta name="twitter:title" content="{{ $seoPayload['title'] }}">
<meta name="twitter:description" content="{{ $seoPayload['description'] }}">

<link rel="icon" href="{{ $seoPayload['favicon'] ?: '/favicon.ico' }}" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@foreach ($structuredData as $schema)
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endforeach

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
