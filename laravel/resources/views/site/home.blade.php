@php
    use Illuminate\Support\Str;
    use YtHub\Lang;
@endphp
<!DOCTYPE html>
<html lang="{{ Lang::code() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $seoTitle }}</title>
    @include('site.partials.seo-head')
    @include('site.partials.hreflang')
    @include('site.partials.legal-styles')
</head>
<body>
<a class="skip-link" href="#main-content">{{ Lang::t('public.skip_to_content') }}</a>
<header class="site-header">
    <a href="{{ url('/') }}" style="text-decoration:none;display:inline-flex;align-items:center;gap:.6rem;">
        <img src="{{ asset('assets/imh-group-logo.svg') }}" alt="IMH Group" class="imh-logo">
    </a>
    <nav class="site-nav" aria-label="{{ Lang::t('public.main_nav_aria') }}">
        <a href="{{ url('/admin/') }}">{{ Lang::t('public.nav_admin') }}</a>
        <a href="{{ url('/admin/analytics.php') }}">{{ Lang::t('public.nav_analytics') }}</a>
        <a href="{{ Lang::relUrl('datenschutz.php') }}">{{ Lang::t('public.nav_privacy') }}</a>
        <a href="{{ Lang::relUrl('impressum.php') }}">{{ Lang::t('public.nav_imprint') }}</a>
    </nav>
    @include('site.partials.lang-switcher')
</header>
<main id="main-content" tabindex="-1">
@if ($byChannel === [])
    <p class="empty">{{ Lang::t('public.empty_no_channels') }}</p>
@endif
@php $thumbIndex = 0; @endphp
@foreach ($byChannel as $block)
    <section class="site-section">
        <h2 class="site-section-title">{{ $block['channel']['title'] }}</h2>
        @if ($block['videos'] === [])
            <p class="empty">{{ Lang::t('public.empty_no_videos') }}</p>
        @else
            <p id="carousel-hint-{{ $block['channel']['id'] }}" class="visually-hidden">{{ Lang::t('public.carousel_kb_hint') }}</p>
            <div
                class="carousel"
                role="region"
                aria-label="{{ Lang::t('public.aria_carousel') }}"
                aria-describedby="carousel-hint-{{ $block['channel']['id'] }}"
                aria-roledescription="carousel"
                data-live-tmpl="{{ Lang::t('public.carousel_live_template') }}"
            >
                <p id="carousel-live-{{ $block['channel']['id'] }}" class="carousel-live" aria-live="polite" aria-atomic="true"></p>
                @foreach ($block['videos'] as $v)
                    @php
                        $vidTitle = (string) $v['title'];
                        $isLcp = $thumbIndex === 0;
                        $thumbIndex++;
                    @endphp
                    <a class="card" href="https://www.youtube.com/watch?v={{ $v['video_id'] }}" target="_blank" rel="noopener noreferrer">
                        <div class="thumb">
                            <img
                                class="thumb-img"
                                src="{{ $v['thumbnail_url'] }}"
                                alt="{{ Str::limit($vidTitle, 120) }}"
                                width="480"
                                height="270"
                                loading="{{ $isLcp ? 'eager' : 'lazy' }}"
                                decoding="async"
                                fetchpriority="{{ $isLcp ? 'high' : 'low' }}"
                                sizes="(max-width: 480px) 85vw, 232px"
                            >
                        </div>
                        <div class="meta">
                            <div class="title">{{ $vidTitle }}</div>
                            <div class="sub">{{ $v['published_at'] ?? '' }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
@endforeach
</main>
@include('site.partials.site-footer')
<script src="{{ asset('assets/site-carousel.js') }}" defer></script>
</body>
</html>
