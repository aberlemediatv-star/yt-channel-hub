<!doctype html>
<html lang="{{ \YtHub\Lang::code() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Social Posts</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
</head>
<body class="adm-body">
<header class="adm-head" role="banner">
    <a href="/admin/index.php" style="text-decoration:none;display:inline-flex;align-items:center;gap:.65rem;">
        <img src="{{ asset('assets/imh-group-logo.svg') }}" alt="IMH Group" class="adm-logo">
        <span class="adm-title">Social Posts</span>
    </a>
    <nav class="adm-nav">
        <a href="/admin/index.php">Dashboard</a>
        <a href="/admin/social/accounts?token={{ urlencode($token) }}">Accounts</a>
        <a href="/admin/social/activate?token={{ urlencode($token) }}">Aktivierung</a>
        <a href="/admin/social/settings?token={{ urlencode($token) }}">Settings</a>
        @include('site.partials.lang-switcher-inline')
    </nav>
</header>
<main class="adm-main" role="main">

    @if(session('status'))
        <div class="adm-flash adm-flash-ok">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="adm-flash adm-flash-err">
            <ul style="margin:0;padding-left:1.25rem;">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="padding:1rem;border:1px solid var(--adm-border);border-radius:var(--adm-radius);background:var(--adm-card);margin-bottom:1.5rem;">
        <h2 style="margin:0 0 0.5rem;font-size:0.95rem;">Test: X-Post (Text / YouTube-Link)</h2>
        <p class="adm-note" style="margin-top:0;">
            Benötigt verbundenen X-Account. Es wird ein normaler Tweet erstellt (kein Video-Upload aus lokaler Datei).
        </p>
        <form method="post" action="/admin/social/posts/x?token={{ urlencode($token) }}" class="adm-form" style="display:flex;gap:0.75rem;align-items:flex-end;flex-wrap:wrap;">
            @csrf
            <div>
                <label for="x_text" style="font-size:0.8rem;margin-bottom:4px;">Text (optional)</label>
                <input id="x_text" name="text" type="text" maxlength="250" style="width:min(520px,100%);" value="{{ old('text') }}">
            </div>
            <div>
                <label for="x_yt" style="font-size:0.8rem;margin-bottom:4px;">YouTube Video-ID (optional)</label>
                <input id="x_yt" name="youtube_video_id" type="text" maxlength="64" style="width:220px;" value="{{ old('youtube_video_id') }}">
            </div>
            <button class="adm-btn" type="submit">An X senden</button>
        </form>
    </div>

    <p class="adm-note" style="margin-bottom:1rem;">
        TikTok-Video und X-Media-Upload aus Datei folgen separat (API/Scopes).
    </p>

    <table class="adm-table">
        <thead>
        <tr>
            <th>ID</th>
            <th>Plattform</th>
            <th>Status</th>
            <th>Channel</th>
            <th>YouTube</th>
            <th>External</th>
            <th>Error</th>
            <th>Created</th>
        </tr>
        </thead>
        <tbody>
        @foreach($posts as $p)
            <tr>
                <td>{{ $p->id }}</td>
                <td>{{ $p->platform }}</td>
                <td>{{ $p->status }}</td>
                <td>{{ $p->channel_id }}</td>
                <td>{{ $p->youtube_video_id }}</td>
                <td>{{ $p->external_id }}</td>
                <td style="max-width:360px;word-break:break-word;">{{ $p->error_message }}</td>
                <td>{{ $p->created_at }}</td>
            </tr>
        @endforeach
        @if($posts->count() === 0)
            <tr><td colspan="8" style="color:var(--adm-muted);">Noch keine Posts.</td></tr>
        @endif
        </tbody>
    </table>
</main>
</body>
</html>
