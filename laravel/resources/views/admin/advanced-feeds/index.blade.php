<!doctype html>
<html lang="{{ \YtHub\Lang::code() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Advanced Feeds</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
    <style>
        .badge { display:inline-block; padding:2px 8px; border-radius:6px; font-size:0.75rem; font-weight:600; }
        .badge.on { background:rgba(61,159,106,0.15); color:#8fe0b0; }
        .badge.off { background:rgba(224,112,112,0.12); color:#ffa8a8; }
    </style>
</head>
<body class="adm-body">
@include('admin.partials.header')
<main class="adm-main" role="main">
    <h1 class="adm-h1">Advanced Feeds</h1>
    <p class="adm-actions">
        <a class="adm-btn" href="{{ url('/admin/advanced-feeds/create') }}">Neuer Feed</a>
    </p>

    @if(session('status'))
        <div class="adm-flash adm-flash-ok">{{ session('status') }}</div>
    @endif

    <table class="adm-table">
        <thead>
        <tr>
            <th>Feed</th>
            <th>Kanal</th>
            <th>Sprache</th>
            <th>TMDB</th>
            <th>Items</th>
            <th>Aktiv</th>
            <th>Feed-URL</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($feeds as $f)
            <tr>
                <td><a href="/admin/advanced-feeds/{{ $f->id }}/edit?token={{ urlencode($token) }}" style="color:var(--adm-accent);">{{ $f->title }}</a></td>
                <td>{{ $channelMap[$f->channel_id] ?? 'Kanal #' . $f->channel_id }}</td>
                <td>{{ $f->language }}</td>
                <td>@if($f->tmdb_enabled) <span class="badge on">Ja</span> @else <span class="badge off">Nein</span> @endif</td>
                <td>{{ $f->items->count() }}</td>
                <td>@if($f->is_active) <span class="badge on">Aktiv</span> @else <span class="badge off">Inaktiv</span> @endif</td>
                <td><code style="font-size:0.78rem;word-break:break-all;">{{ $appUrl }}/feed/advanced/{{ rawurlencode($f->slug) }}</code></td>
                <td><a href="{{ $appUrl }}/feed/advanced/{{ rawurlencode($f->slug) }}" target="_blank" style="color:var(--adm-accent);font-size:0.82rem;">XML</a></td>
            </tr>
        @endforeach
        @if($feeds->isEmpty())
            <tr><td colspan="8" style="color:var(--adm-muted);">Noch keine Advanced Feeds. Oben „Neuer Feed" klicken.</td></tr>
        @endif
        </tbody>
    </table>

    <p class="adm-note">
        Pro Kanal können <strong>beliebig viele</strong> Advanced Feeds angelegt werden — z.B. unterschiedliche
        Sprachen (DE, EN, FR), thematische Zusammenstellungen oder separate Feeds für verschiedene FAST-Plattformen.
    </p>
</main>
</body>
</html>
