@php
    use YtHub\Lang;
@endphp
<!DOCTYPE html>
<html lang="{{ Lang::code() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ Lang::t('legal.ds_html_title') }} — {{ $legal['company'] }}</title>
    @include('site.partials.seo-head')
    @include('site.partials.hreflang')
    @include('site.partials.legal-styles')
</head>
<body>
<article class="legal-page">
    <p class="legal-toolbar"><a class="legal-back" href="{{ Lang::relUrl('index.php') }}">{{ Lang::t('legal.back') }}</a>@include('site.partials.lang-switcher')</p>
    <h1>{{ Lang::t('legal.ds_h1') }}</h1>
    <p class="legal-meta">{{ Lang::t('legal.ds_meta') }}</p>

    <h2>{{ Lang::t('legal.ds_s1_title') }}</h2>
    <p>
        {{ Lang::t('legal.ds_s1_intro') }}<br><br>
        <strong>{{ $legal['company'] }}</strong><br>
        @foreach ($legal['address_lines'] as $line)
            {{ $line }}<br>
        @endforeach
        {{ Lang::t('legal.ds_s1_phone_label') }}: {{ $legal['phone'] }}<br>
        {{ Lang::t('legal.ds_s1_more') }} <a href="{{ $imprintPageUrl }}" target="_blank" rel="noopener">{{ Lang::t('legal.ds_s1_link') }}</a>
    </p>

    <h2>{{ Lang::t('legal.ds_s2_title') }}</h2>
    <p>{{ Lang::t('legal.ds_s2_p1') }}</p>

    <h2>{{ Lang::t('legal.ds_s3_title') }}</h2>
    <p>{{ Lang::t('legal.ds_s3_p1') }}</p>

    <h2>{{ Lang::t('legal.ds_s4_title') }}</h2>
    <p>{!! Lang::tRich('legal.ds_s4_p1_html') !!}</p>
    <p>{{ Lang::t('legal.ds_s4_p2') }}</p>

    <h2>{{ Lang::t('legal.ds_s5_title') }}</h2>
    <p>{!! Lang::tRich('legal.ds_s5_p1_html') !!}</p>

    <h2>{{ Lang::t('legal.ds_s6_title') }}</h2>
    <p>{!! Lang::tRich('legal.ds_s6_p1_html') !!}</p>

    <h2>{{ Lang::t('legal.ds_s7_title') }}</h2>
    <p>{{ Lang::t('legal.ds_s7_p1') }}</p>

    <h2>{{ Lang::t('legal.ds_s8_title') }}</h2>
    <p>{{ Lang::t('legal.ds_s8_p1') }}</p>
    <p>{!! Lang::tRich('legal.ds_s8_p2_html') !!}</p>

    <h2>{{ Lang::t('legal.ds_s9_title') }}</h2>
    <p>{{ Lang::t('legal.ds_s9_p1') }}</p>

</article>
@include('site.partials.site-footer')
</body>
</html>
