@php
    use YtHub\Lang;
    use YtHub\PublicHttp;
    $seoIncludeJsonLd = $seoIncludeJsonLd ?? false;
    $seoOgImage = $seoOgImage ?? null;
    $canonical = Lang::absoluteUrl($seoCanonicalPath . '?' . http_build_query(['lang' => Lang::code()]));
    $lc = Lang::code();
    $ogLocale = match ($lc) {
        'de' => 'de_DE',
        'en' => 'en_US',
        'es' => 'es_ES',
        'fr' => 'fr_FR',
        'th' => 'th_TH',
        default => 'de_DE',
    };
    $nonce = PublicHttp::cspNonce();
    $legalCfg = require config_path('legal.php');
    $orgName = (string) ($legalCfg['company'] ?? '');
@endphp
<link rel="canonical" href="{{ $canonical }}">
<meta name="description" content="{{ $seoDescription }}">
<meta name="theme-color" content="#090c10">
<meta name="color-scheme" content="dark">
<meta name="format-detection" content="telephone=no">
<meta name="robots" content="index, follow, max-image-preview:large">
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:locale" content="{{ $ogLocale }}">
<meta name="twitter:card" content="summary_large_image">
@if ($seoOgImage !== null && $seoOgImage !== '')
<meta property="og:image" content="{{ $seoOgImage }}">
<meta name="twitter:image" content="{{ $seoOgImage }}">
@endif
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<link rel="dns-prefetch" href="https://i.ytimg.com">
<link rel="preconnect" href="https://i.ytimg.com" crossorigin>
@if ($seoIncludeJsonLd && $orgName !== '')
<script type="application/ld+json" nonce="{{ $nonce }}">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => Lang::t('public.brand'),
    'url' => $canonical,
    'description' => $seoDescription,
    'inLanguage' => Lang::SUPPORTED,
    'publisher' => [
        '@type' => 'Organization',
        'name' => $orgName,
        'url' => (string) ($legalCfg['company_site'] ?? '') ?: $canonical,
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) !!}</script>
@endif
