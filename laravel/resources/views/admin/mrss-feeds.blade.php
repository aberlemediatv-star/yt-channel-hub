<!doctype html>
<html lang="{{ \YtHub\Lang::code() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>MRSS Feeds (FAST)</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
    <style>
        .badge-active { display:inline-block; padding:2px 8px; border-radius:6px; font-size:0.75rem; font-weight:600; }
        .badge-active.yes { background:rgba(61,159,106,0.15); color:#8fe0b0; }
        .badge-active.no { background:rgba(224,112,112,0.12); color:#ffa8a8; }
        .copy-btn { cursor:pointer; padding:4px 10px; border-radius:6px; border:1px solid var(--adm-border); background:var(--adm-card); color:var(--adm-text); font-size:0.75rem; }
        .copy-btn:active { background:var(--adm-card-hover); }
    </style>
</head>
<body class="adm-body">
@include('admin.partials.header')
<main class="adm-main" role="main">
    <h1 class="adm-h1">MRSS Feeds (FAST)</h1>
    <p class="adm-actions">
        <a class="adm-btn adm-btn-secondary" href="{{ $appUrl }}/feed/mrss" target="_blank" rel="noopener">Feed-Index (XML)</a>
    </p>
    <p class="adm-note">
        Jeder aktive Kanal hat eine eigene MRSS-Feed-URL. Diese URL kann an FAST-Provider
        (Pluto TV, Samsung TV Plus, Rakuten TV, etc.) übermittelt werden.
        Der Feed enthält Titel, Beschreibung, Thumbnail, Dauer und YouTube-Link für jedes Video.
    </p>

    <table class="adm-table">
        <thead>
        <tr>
            <th>Kanal</th>
            <th>Aktiv</th>
            <th>MRSS-Feed-URL</th>
            <th></th>
            <th>Vorschau</th>
        </tr>
        </thead>
        <tbody>
        @foreach($channels as $ch)
            @php
                $feedUrl = $appUrl . '/feed/mrss/' . rawurlencode((string) $ch['slug']);
                $isActive = !empty($ch['is_active']);
            @endphp
            <tr>
                <td>
                    <strong>{{ $ch['title'] }}</strong><br>
                    <span style="color:var(--adm-muted);font-size:0.75rem;">{{ $ch['youtube_channel_id'] }}</span>
                </td>
                <td>
                    @if($isActive)
                        <span class="badge-active yes">Aktiv</span>
                    @else
                        <span class="badge-active no">Inaktiv</span>
                    @endif
                </td>
                <td><code style="font-size:0.78rem;word-break:break-all;" id="url-{{ $ch['id'] }}">{{ $feedUrl }}</code></td>
                <td>
                    @if($isActive)
                        <button class="copy-btn" onclick="navigator.clipboard.writeText(document.getElementById('url-{{ $ch['id'] }}').textContent).then(()=>this.textContent='Kopiert!').catch(()=>{})">Kopieren</button>
                    @endif
                </td>
                <td>
                    @if($isActive)
                        <a href="{{ $feedUrl }}" target="_blank" style="color:var(--adm-accent);font-size:0.82rem;">XML anzeigen</a>
                    @else
                        <span style="color:var(--adm-muted);font-size:0.82rem;">—</span>
                    @endif
                </td>
            </tr>
        @endforeach
        @if(count($channels) === 0)
            <tr><td colspan="5" style="color:var(--adm-muted);">Keine Kanäle vorhanden.</td></tr>
        @endif
        </tbody>
    </table>

    <div style="margin-top:1.5rem;padding:1rem;border:1px solid var(--adm-border);border-radius:var(--adm-radius);background:var(--adm-card);">
        <h2 style="margin:0 0 0.5rem;font-size:0.95rem;color:var(--adm-text);">FAST-Provider-Übergabe</h2>
        <ol style="margin:0;padding-left:1.25rem;font-size:0.86rem;color:var(--adm-muted);line-height:1.7;">
            <li>Kanal unter <em>Kanäle verwalten</em> aktivieren.</li>
            <li>Die MRSS-URL oben kopieren und an den FAST-Provider übermitteln (z.B. per E-Mail oder Portal-Upload).</li>
            <li>Der Provider pollt die URL regelmäßig. Neue Videos erscheinen automatisch nach dem nächsten Video-Sync.</li>
            <li>Optional: <code>?limit=50</code> an die URL anhängen, um die Anzahl der Videos zu begrenzen (Standard: 200, Max: 500).</li>
        </ol>
    </div>
</main>
</body>
</html>
