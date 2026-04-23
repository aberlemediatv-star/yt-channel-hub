<!doctype html>
<html lang="{{ \YtHub\Lang::code() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $feed ? 'Feed bearbeiten' : 'Neuer Feed' }}</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
    <style>
        .tmdb-badge { display:inline-block; padding:2px 8px; border-radius:6px; font-size:0.7rem; background:rgba(253,90,55,0.15); color:var(--adm-accent); }
        img.thumb { width:80px; border-radius:4px; }
    </style>
</head>
<body class="adm-body">
@include('admin.partials.header')
<main class="adm-main" role="main">
    <h1 class="adm-h1">{{ $feed ? 'Feed bearbeiten: '.$feed->title : 'Neuer Feed' }}</h1>
    <p class="adm-actions">
        <a class="adm-btn adm-btn-secondary" href="{{ url('/admin/advanced-feeds') }}">← Zur Übersicht</a>
    </p>

    @if(session('status'))
        <div class="adm-flash adm-flash-ok">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="adm-flash adm-flash-err">
            <ul style="margin:0;padding-left:1.25rem;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="post" action="{{ $feed ? '/admin/advanced-feeds/' . $feed->id . '?token=' . urlencode($token) : '/admin/advanced-feeds?token=' . urlencode($token) }}" class="adm-form">
        @csrf
        @if($feed) @method('PUT') @endif

        <label for="af_title">Titel</label>
        <input type="text" id="af_title" name="title" value="{{ old('title', $feed?->title ?? '') }}" required>

        <label for="af_channel">Kanal</label>
        <select id="af_channel" name="channel_id" required style="width:100%;max-width:520px;padding:0.55rem 0.65rem;border-radius:9px;border:1px solid var(--adm-border);background:var(--adm-bg-mid);color:#fff;margin-top:0.35rem;font-family:inherit;font-size:0.9rem;">
            <option value="">— wählen —</option>
            @foreach($channels as $ch)
                <option value="{{ $ch['id'] }}" {{ (int) old('channel_id', $feed?->channel_id ?? '') === (int) $ch['id'] ? 'selected' : '' }}>
                    {{ $ch['title'] }} ({{ $ch['youtube_channel_id'] }})
                </option>
            @endforeach
        </select>

        <label for="af_lang">Sprache (ISO, z.B. de, en, fr)</label>
        <input type="text" id="af_lang" name="language" maxlength="8" value="{{ old('language', $feed?->language ?? 'de') }}" style="max-width:120px;" required>

        <div style="margin-top:1rem;">
            <label style="display:inline-flex;align-items:center;gap:0.5rem;font-weight:600;">
                <input type="checkbox" name="tmdb_enabled" value="1" {{ ($errors->any() ? old('tmdb_enabled') === '1' : ($feed?->tmdb_enabled ?? false)) ? 'checked' : '' }}>
                TMDB-Anreicherung aktivieren
            </label>
            @if(!$tmdbConfigured)
                <span style="color:var(--adm-err);font-size:0.82rem;margin-left:0.5rem;">(<code>TMDB_API_KEY</code> fehlt)</span>
            @endif
        </div>

        <div style="margin-top:0.65rem;">
            <label style="display:inline-flex;align-items:center;gap:0.5rem;font-weight:600;">
                <input type="checkbox" name="is_active" value="1" {{ ($errors->any() ? old('is_active') === '1' : ($feed === null || $feed->is_active)) ? 'checked' : '' }}>
                Aktiv
            </label>
        </div>

        <div class="adm-actions" style="margin-top:1rem;">
            <button class="adm-btn" type="submit">{{ $feed ? 'Speichern' : 'Feed erstellen' }}</button>
        </div>
    </form>

    @if($feed)
        <form method="post" action="/admin/advanced-feeds/{{ $feed->id }}" style="margin:0;display:inline;margin-top:-0.5rem;" onsubmit="return confirm('Feed wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.');">
            @csrf @method('DELETE')
            <button type="submit" class="adm-btn adm-btn-secondary" style="color:var(--adm-err);">Löschen</button>
        </form>
    @endif

    @if($feed)
    <div style="border:1px solid var(--adm-border);border-radius:var(--adm-radius);padding:1rem;margin-top:1.5rem;background:var(--adm-card);">
        <h2 style="margin:0 0 0.5rem;font-size:0.95rem;">Videos im Feed ({{ isset($items) ? $items->count() : 0 }})</h2>

        @if(isset($items) && $items->count() > 0)
        <table class="adm-table">
            <thead>
            <tr>
                <th>#</th>
                <th></th>
                <th>YouTube</th>
                <th>Feed-Titel</th>
                <th>TMDB</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($items as $item)
                @php
                    $yt = $ytVideos[$item->youtube_video_id] ?? null;
                    $effTitle = $item->effectiveTitle() ?? ($yt['title'] ?? $item->youtube_video_id);
                @endphp
                <tr>
                    <td>{{ $item->sort_order }}</td>
                    <td>
                        @if($yt && $yt['thumbnail_url'])
                            <img class="thumb" src="{{ $yt['thumbnail_url'] }}" alt="{{ $yt['title'] ?? $item->youtube_video_id }}">
                        @endif
                    </td>
                    <td>
                        <a href="https://www.youtube.com/watch?v={{ rawurlencode($item->youtube_video_id) }}" target="_blank" style="color:var(--adm-accent);font-size:0.78rem;">
                            {{ $yt['title'] ?? $item->youtube_video_id }}
                        </a>
                    </td>
                    <td>
                        <strong>{{ $effTitle }}</strong>
                        @if($item->tmdb_title)
                            <span class="tmdb-badge">TMDB</span>
                        @endif
                    </td>
                    <td>
                        @if($item->tmdb_id)
                            <span style="font-size:0.78rem;">{{ $item->tmdb_type }}/{{ $item->tmdb_id }}</span>
                            <form method="post" action="/admin/advanced-feeds/{{ $feed->id }}/items/{{ $item->id }}/tmdb-clear?token={{ urlencode($token) }}" style="display:inline;margin:0;">
                                @csrf
                                <button type="submit" style="background:none;border:none;color:var(--adm-err);cursor:pointer;font-size:0.78rem;">&times; entfernen</button>
                            </form>
                        @elseif($feed->tmdb_enabled && $tmdbConfigured)
                            <span style="font-size:0.78rem;color:var(--adm-muted);">—</span>
                        @endif
                    </td>
                    <td>
                        <form method="post" action="/admin/advanced-feeds/{{ $feed->id }}/items/{{ $item->id }}/remove?token={{ urlencode($token) }}" style="margin:0;" onsubmit="return confirm('Entfernen?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="adm-btn adm-btn-secondary" style="padding:4px 8px;font-size:0.75rem;color:var(--adm-err);">Entfernen</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @else
            <p class="adm-note">Noch keine Videos. Unten hinzufügen.</p>
        @endif
    </div>

    <div style="border:1px solid var(--adm-border);border-radius:var(--adm-radius);padding:1rem;margin-top:1rem;background:var(--adm-card);">
        <h2 style="margin:0 0 0.5rem;font-size:0.95rem;">Video hinzufügen</h2>
        <p class="adm-note" style="margin-top:0;">YouTube Video-ID des Kanals eingeben.</p>
        <form method="post" action="/admin/advanced-feeds/{{ $feed->id }}/items?token={{ urlencode($token) }}" class="adm-form" style="display:flex;gap:0.75rem;align-items:flex-end;flex-wrap:wrap;">
            @csrf
            <div>
                <label for="add_vid" style="font-size:0.8rem;">YouTube Video-ID</label>
                <input type="text" id="add_vid" name="youtube_video_id" maxlength="32" style="width:240px;" required>
            </div>
            <button class="adm-btn" type="submit">Hinzufügen</button>
        </form>
        <details style="margin-top:0.75rem;">
            <summary style="cursor:pointer;font-size:0.82rem;color:var(--adm-muted);">Verfügbare Videos laden (API)</summary>
            <div id="available-videos" style="margin-top:0.5rem;">
                <button type="button" class="adm-btn adm-btn-secondary" style="padding:4px 10px;font-size:0.78rem;" onclick="loadAvailable()">Laden</button>
                <div id="av-list" style="margin-top:0.5rem;max-height:300px;overflow-y:auto;"></div>
            </div>
        </details>
    </div>

    @if($feed->tmdb_enabled && $tmdbConfigured)
    <div style="border:1px solid var(--adm-border);border-radius:var(--adm-radius);padding:1rem;margin-top:1rem;background:var(--adm-card);">
        <h2 style="margin:0 0 0.5rem;font-size:0.95rem;">TMDB-Suche (Sprache: {{ $feed->language }})</h2>
        <div style="display:flex;gap:0.75rem;align-items:flex-end;flex-wrap:wrap;">
            <div>
                <label for="tmdb_q" style="font-size:0.8rem;color:var(--adm-muted);display:block;">Suchbegriff</label>
                <input id="tmdb_q" type="text" style="width:340px;padding:0.55rem 0.65rem;border-radius:9px;border:1px solid var(--adm-border);background:var(--adm-bg-mid);color:#fff;font-family:inherit;font-size:0.9rem;">
            </div>
            <button type="button" class="adm-btn adm-btn-secondary" onclick="tmdbSearch()">Suchen</button>
        </div>
        <div id="tmdb-results" style="margin-top:0.75rem;"></div>
        <p class="adm-note" style="margin-top:0.5rem;font-size:0.78rem;">
            Ergebnis wählen → auf ein Feed-Video per „Zuweisen" anwenden.
        </p>
    </div>
    @endif
    @endif
</main>

<script>
const feedId = {{ $feed?->id ?? 0 }};
const tkn = '{{ urlencode($token) }}';

function esc(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}
function escJs(s) {
    return String(s).replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"');
}

function loadAvailable() {
    if (!feedId) return;
    fetch('/admin/advanced-feeds/' + feedId + '/available-videos?token=' + tkn)
        .then(r => r.json())
        .then(vids => {
            const el = document.getElementById('av-list');
            if (!vids.length) { el.innerHTML = '<em style="color:var(--adm-muted);">Keine Videos.</em>'; return; }
            el.innerHTML = '<table class="adm-table"><thead><tr><th></th><th>Titel</th><th>ID</th><th></th></tr></thead><tbody>' +
                vids.map(v => '<tr><td>' + (v.thumbnail_url ? '<img src="'+esc(v.thumbnail_url)+'" style="width:60px;border-radius:4px;">' : '') +
                    '</td><td style="font-size:0.78rem;">' + esc(v.title) + '</td><td><code style="font-size:0.72rem;">' + esc(v.video_id) +
                    '</code></td><td><button onclick="document.getElementById(\'add_vid\').value=\'' + escJs(v.video_id) +
                    '\'" style="font-size:0.78rem;cursor:pointer;background:var(--adm-card-hover);border:1px solid var(--adm-border);color:var(--adm-text);border-radius:6px;padding:4px 8px;">Wählen</button></td></tr>').join('') +
                '</tbody></table>';
        }).catch(() => {});
}

@if($feed && ($feed->tmdb_enabled ?? false))
let tmdbResults = [];
function tmdbSearch() {
    const q = document.getElementById('tmdb_q').value.trim();
    if (!q) return;
    fetch('/admin/advanced-feeds/' + feedId + '/tmdb-search?token=' + tkn + '&q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(res => {
            tmdbResults = res;
            const el = document.getElementById('tmdb-results');
            if (!res.length) { el.innerHTML = '<em style="color:var(--adm-muted);">Keine Ergebnisse.</em>'; return; }
            el.innerHTML = '<table class="adm-table"><thead><tr><th></th><th>Titel</th><th>Jahr</th><th>Typ</th><th>Zuweisen an</th></tr></thead><tbody>' +
                res.map((r,i) => '<tr><td>' + (r.poster ? '<img src="'+esc(r.poster)+'" style="width:50px;border-radius:4px;">' : '') +
                    '</td><td style="font-size:0.78rem;">' + esc(r.title) + '</td><td>' + esc(String(r.year)) + '</td><td>' + esc(r.type) +
                    '</td><td><select id="tmdb-assign-'+i+'" style="font-size:0.78rem;padding:4px;background:var(--adm-bg-mid);border:1px solid var(--adm-border);color:var(--adm-text);border-radius:6px;">' +
                    @if(isset($items))
                    {!! json_encode($items->map(fn($it) => ['id' => $it->id, 'vid' => $it->youtube_video_id])->values()->toArray()) !!}
                    @else [] @endif
                    .map(it => '<option value="'+esc(String(it.id))+'">'+esc(it.vid)+'</option>').join('') +
                    '</select> <button onclick="applyTmdb('+i+')" style="font-size:0.78rem;cursor:pointer;background:var(--adm-card-hover);border:1px solid var(--adm-border);color:var(--adm-text);border-radius:6px;padding:4px 8px;">Zuweisen</button></td></tr>').join('') +
                '</tbody></table>';
        }).catch(() => {});
}

function applyTmdb(idx) {
    const r = tmdbResults[idx];
    const sel = document.getElementById('tmdb-assign-' + idx);
    const itemId = sel.value;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/admin/advanced-feeds/' + feedId + '/items/' + itemId + '/tmdb-apply?token=' + tkn;
    ['_token','tmdb_id','tmdb_type'].forEach((k,i) => {
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = k;
        inp.value = [document.querySelector('input[name=_token]').value, r.id, r.type][i];
        form.appendChild(inp);
    });
    document.body.appendChild(form);
    form.submit();
}
@endif
</script>
</body>
</html>
