<!doctype html>
<html lang="{{ \YtHub\Lang::code() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Social Settings</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
</head>
<body class="adm-body">
@include('admin.partials.header')
<main class="adm-main" role="main">
    <h1 class="adm-h1">{{ \YtHub\Lang::t('admin.nav.social_settings') }}</h1>

    @if (session('status'))
        <div class="adm-flash adm-flash-ok">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="adm-flash adm-flash-err">
            <strong>Fehler:</strong>
            <ul style="margin:0.5rem 0 0 1rem;">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="/admin/social/settings?token={{ urlencode(request()->query('token', '')) }}" class="adm-form">
        @csrf

        <h2 class="adm-h2" style="margin-top:0.5rem;">Instagram (Meta)</h2>
        <label for="meta_app_id">Meta App ID</label>
        <input type="text" id="meta_app_id" name="meta_app_id" value="{{ old('meta_app_id', $metaAppId) }}" autocomplete="off">
        <p class="adm-note" style="margin-top:0.35rem;">Aus der Meta Developer App (Instagram Graph API).</p>

        <label for="meta_app_secret">Meta App Secret</label>
        <input type="password" id="meta_app_secret" name="meta_app_secret" value="{{ old('meta_app_secret', $metaAppSecret) }}" autocomplete="off">
        <p class="adm-note" style="margin-top:0.35rem;">Wird verschlüsselt in der Datenbank gespeichert.</p>

        <h2 class="adm-h2">X (Twitter)</h2>
        <label for="x_client_id">X Client ID</label>
        <input type="text" id="x_client_id" name="x_client_id" value="{{ old('x_client_id', $xClientId) }}" autocomplete="off">
        <p class="adm-note" style="margin-top:0.35rem;">OAuth2 Client ID (X Developer Portal).</p>

        <label for="x_client_secret">X Client Secret</label>
        <input type="password" id="x_client_secret" name="x_client_secret" value="{{ old('x_client_secret', $xClientSecret) }}" autocomplete="off">

        <h2 class="adm-h2">TikTok</h2>
        <label for="tiktok_client_key">TikTok Client Key</label>
        <input type="text" id="tiktok_client_key" name="tiktok_client_key" value="{{ old('tiktok_client_key', $tiktokClientKey) }}" autocomplete="off">

        <label for="tiktok_client_secret">TikTok Client Secret</label>
        <input type="password" id="tiktok_client_secret" name="tiktok_client_secret" value="{{ old('tiktok_client_secret', $tiktokClientSecret) }}" autocomplete="off">

        <h2 class="adm-h2">Facebook Pages</h2>
        <label style="display:inline-flex;align-items:center;gap:0.5rem;font-weight:600;">
            <input type="checkbox" name="facebook_enabled" value="1" {{ ($errors->any() ? old('facebook_enabled') === '1' : $facebookEnabled) ? 'checked' : '' }}>
            Aktivieren (nur Vorbereitung)
        </label>
        <label for="facebook_app_id">Facebook App ID</label>
        <input type="text" id="facebook_app_id" name="facebook_app_id" value="{{ old('facebook_app_id', $facebookAppId) }}" autocomplete="off">
        <label for="facebook_app_secret">Facebook App Secret</label>
        <input type="password" id="facebook_app_secret" name="facebook_app_secret" value="{{ old('facebook_app_secret', $facebookAppSecret) }}" autocomplete="off">

        <h2 class="adm-h2">LinkedIn</h2>
        <label style="display:inline-flex;align-items:center;gap:0.5rem;font-weight:600;">
            <input type="checkbox" name="linkedin_enabled" value="1" {{ ($errors->any() ? old('linkedin_enabled') === '1' : $linkedinEnabled) ? 'checked' : '' }}>
            Aktivieren (nur Vorbereitung)
        </label>
        <label for="linkedin_client_id">LinkedIn Client ID</label>
        <input type="text" id="linkedin_client_id" name="linkedin_client_id" value="{{ old('linkedin_client_id', $linkedinClientId) }}" autocomplete="off">
        <label for="linkedin_client_secret">LinkedIn Client Secret</label>
        <input type="password" id="linkedin_client_secret" name="linkedin_client_secret" value="{{ old('linkedin_client_secret', $linkedinClientSecret) }}" autocomplete="off">

        <div class="adm-form-row">
            <button type="submit" class="adm-btn">Speichern</button>
        </div>

        <p class="adm-note" style="margin-top:1rem;">
            Zugriff ist per <code>X-Internal-Token</code> Header oder <code>?token=…</code> geschützt (Wert aus <code>INTERNAL_TOKEN</code>).
        </p>
    </form>
</main>
</body>
</html>
