@php
    use YtHub\Lang;
@endphp
<!doctype html>
<html lang="{{ Lang::code() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ Lang::t('admin.api_keys_title') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
</head>
<body class="adm-body">
<header class="adm-head" role="banner">
    <a href="/admin/index.php" style="text-decoration:none;display:inline-flex;align-items:center;gap:.65rem;">
        <img src="{{ asset('assets/imh-group-logo.svg') }}" alt="{{ Lang::t('public.brand') }}" class="adm-logo">
        <span class="adm-title">{{ Lang::t('admin.api_keys_title') }}</span>
    </a>
    <nav class="adm-nav">
        <a href="/admin/index.php">Dashboard</a>
        <a href="/admin/social/settings?token={{ urlencode(request()->query('token', '')) }}">{{ Lang::t('admin.api_keys_nav_social') }}</a>
        <a href="/admin/mrss-feeds?token={{ urlencode(request()->query('token', '')) }}">{{ Lang::t('admin.api_keys_nav_mrss') }}</a>
        <a href="/admin/advanced-feeds?token={{ urlencode(request()->query('token', '')) }}">{{ Lang::t('admin.api_keys_nav_advanced') }}</a>
        @include('site.partials.lang-switcher-inline')
    </nav>
</header>
<main class="adm-main" role="main">
    <p class="adm-note">
        {!! Lang::tRich('admin.api_keys_intro') !!}
    </p>

    @if (session('status'))
        <div class="adm-flash adm-flash-ok">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="adm-flash adm-flash-err">
            <strong>{{ Lang::t('admin.api_keys_errors_heading') }}</strong>
            <ul style="margin:0.5rem 0 0 1rem;">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="/admin/api-keys?token={{ urlencode(request()->query('token', '')) }}" class="adm-form">
        @csrf

        <h2 class="adm-h2">{{ Lang::t('admin.api_keys_tmdb_h2') }}</h2>
        <p class="adm-note" style="margin-top:0;">{!! Lang::tRich('admin.api_keys_tmdb_blurb_html') !!}</p>

        <label for="tmdb_api_key">{{ Lang::t('admin.api_keys_label_tmdb') }}</label>
        <input type="text" id="tmdb_api_key" name="tmdb_api_key" value="{{ old('tmdb_api_key', $tmdbApiKey) }}" autocomplete="off" placeholder="{{ Lang::t('admin.api_keys_placeholder_tmdb') }}">
        @if ($tmdbEnvSet)
            <p class="adm-note" style="margin-top:0.35rem;font-size:0.78rem;font-style:italic;">{!! Lang::tRich('admin.api_keys_tmdb_fallback') !!}</p>
        @endif

        <h2 class="adm-h2">{{ Lang::t('admin.api_keys_google_h2') }}</h2>
        <p class="adm-note" style="margin-top:0;">{!! Lang::tRich('admin.api_keys_google_blurb_html') !!}</p>

        <label for="google_api_key">{{ Lang::t('admin.api_keys_label_google_api_key') }}</label>
        <input type="text" id="google_api_key" name="google_api_key" value="{{ old('google_api_key', $googleApiKey) }}" autocomplete="off">

        <label for="google_client_id">{{ Lang::t('admin.api_keys_label_google_client_id') }}</label>
        <input type="text" id="google_client_id" name="google_client_id" value="{{ old('google_client_id', $googleClientId) }}" autocomplete="off">
        @if ($googleEnvSet)
            <p class="adm-note" style="margin-top:0.35rem;font-size:0.78rem;font-style:italic;">{!! Lang::tRich('admin.api_keys_google_client_fallback') !!}</p>
        @endif

        <label for="google_client_secret">{{ Lang::t('admin.api_keys_label_google_client_secret') }}</label>
        <input type="password" id="google_client_secret" name="google_client_secret" value="{{ old('google_client_secret', $googleClientSecret) }}" autocomplete="off">

        <label for="google_redirect_uri">{{ Lang::t('admin.api_keys_label_google_redirect') }}</label>
        <input type="text" id="google_redirect_uri" name="google_redirect_uri" value="{{ old('google_redirect_uri', $googleRedirectUri) }}" autocomplete="off" placeholder="{{ Lang::t('admin.api_keys_redirect_placeholder') }}">
        <p class="adm-note" style="margin-top:0.35rem;">{{ Lang::t('admin.api_keys_redirect_note') }}</p>

        <div class="adm-form-row">
            <button type="submit" class="adm-btn">{{ Lang::t('admin.save') }}</button>
        </div>
    </form>
</main>
</body>
</html>
