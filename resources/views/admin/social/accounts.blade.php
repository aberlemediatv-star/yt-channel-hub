<!doctype html>
<html lang="{{ \YtHub\Lang::code() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Social Accounts</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
</head>
<body class="adm-body">
<header class="adm-head" role="banner">
    <a href="/admin/index.php" style="text-decoration:none;display:inline-flex;align-items:center;gap:.65rem;">
        <img src="{{ asset('assets/imh-group-logo.svg') }}" alt="IMH Group" class="adm-logo">
        <span class="adm-title">Social Accounts</span>
    </a>
    <nav class="adm-nav">
        <a href="/admin/index.php">Dashboard</a>
        <a href="/admin/social/settings?token={{ urlencode($token) }}">Settings</a>
        <a href="/admin/social/activate?token={{ urlencode($token) }}">Aktivierung</a>
        <a href="/admin/social/posts?token={{ urlencode($token) }}">Posts</a>
        @if(!empty($xOAuthReady))
            <a class="adm-btn" href="{{ route('oauth.x.start', ['token' => $token]) }}">X verbinden</a>
        @endif
        @if(!empty($tiktokOAuthReady))
            <a class="adm-btn" href="{{ route('oauth.tiktok.start', ['token' => $token]) }}">TikTok verbinden</a>
        @endif
        @include('site.partials.lang-switcher-inline')
    </nav>
</header>
<main class="adm-main" role="main">

    @if(session('status'))
        <div class="adm-flash adm-flash-ok">{{ session('status') }}</div>
    @endif

    <table class="adm-table">
        <thead>
        <tr>
            <th>ID</th>
            <th>Plattform</th>
            <th>Label</th>
            <th>External User</th>
            <th>Scopes</th>
            <th>Expires</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($accounts as $a)
            <tr>
                <td>{{ $a->id }}</td>
                <td>{{ $a->platform }}</td>
                <td>{{ $a->label }}</td>
                <td>{{ $a->external_user_id }}</td>
                <td style="max-width:320px;word-break:break-word;">{{ $a->scopes }}</td>
                <td>{{ $a->token_expires_at }}</td>
                <td>
                    <form method="post" action="{{ route('admin.social.accounts.disconnect', $a) }}?token={{ urlencode($token) }}" style="margin:0;" onsubmit="return confirm('Diesen Account wirklich entfernen?');">
                        @csrf
                        <button type="submit" class="adm-btn adm-btn-secondary" style="color:var(--adm-err);">Trennen</button>
                    </form>
                </td>
            </tr>
        @endforeach
        @if($accounts->count() === 0)
            <tr><td colspan="7" style="color:var(--adm-muted);">Noch keine Accounts verbunden. X/TikTok: Client-Daten in Settings hinterlegen, dann oben „verbinden".</td></tr>
        @endif
        </tbody>
    </table>

    <p class="adm-note">
        OAuth Connect für X und TikTok ist aktiv, sobald Keys gesetzt sind. Video-Posting in die Netze ist weiterhin vorbereitet (API/Scopes/App Review).
    </p>
</main>
</body>
</html>
