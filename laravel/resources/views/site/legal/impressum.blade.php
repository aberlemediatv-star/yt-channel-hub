@php
    use YtHub\Lang;
@endphp
<!DOCTYPE html>
<html lang="{{ Lang::code() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ Lang::t('legal.im_html_title') }} — {{ $legal['company'] }}</title>
    @include('site.partials.seo-head')
    @include('site.partials.hreflang')
    @include('site.partials.legal-styles')
</head>
<body>
<article class="legal-page">
    <p class="legal-toolbar"><a class="legal-back" href="{{ Lang::relUrl('index.php') }}">{{ Lang::t('legal.back') }}</a>@include('site.partials.lang-switcher')</p>
    <h1>{{ Lang::t('legal.im_h1') }}</h1>
    <p class="legal-meta">{!! $metaHtml !!}</p>

    <h2>{{ Lang::t('legal.im_s1_title') }}</h2>
    <p>
        {{ $legal['company'] }}<br>
        @foreach ($legal['address_lines'] as $line)
            {{ $line }}<br>
        @endforeach
        {{ Lang::t('legal.im_phone_label') }}: {{ $legal['phone'] }}
    </p>

    <h2>{{ Lang::t('legal.im_s2_title') }}</h2>
    <p>{{ $legal['managing_director'] }}</p>

    <h2>{{ Lang::t('legal.im_s3_title') }}</h2>
    <p>
        {{ $legal['register_court'] }}, {{ $legal['register_number'] }}<br>
        {{ Lang::t('legal.im_s3_vat_label') }}: {{ $legal['vat_id'] }}
    </p>

    <h2>{{ Lang::t('legal.im_s4_title') }}</h2>
    <p>{{ $legal['share_capital'] }}</p>

    <h2>{{ Lang::t('legal.im_s5_title') }}</h2>
    <p>
        {{ $legal['content_responsible'] }} ({{ $legal['content_responsible_legal'] }})
    </p>

    <h2>{{ Lang::t('legal.im_s6_title') }}</h2>
    <p>{{ $legal['professional_liability'] }}</p>

    <h2>{{ Lang::t('legal.im_s7_title') }}</h2>
    <p>{{ Lang::t('legal.im_s7_p1') }}</p>

    <h2>{{ Lang::t('legal.im_s8_title') }}</h2>
    <p>{!! $s8Html !!}</p>

</article>
@include('site.partials.site-footer')
</body>
</html>
