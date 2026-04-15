<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Installation — MariaDB / Plesk</title>
    <style>
        :root { --bg: #0f0f12; --card: #1a1a20; --text: #eee; --muted: #888; --accent: #3d9eff; --err: #f66; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, sans-serif; background: var(--bg); color: var(--text); line-height: 1.5; padding: 1.5rem; }
        main { max-width: 520px; margin: 0 auto; }
        h1 { font-size: 1.35rem; margin-top: 0; }
        p.note { color: var(--muted); font-size: 0.9rem; }
        label { display: block; margin-top: 1rem; font-size: 0.85rem; color: var(--muted); }
        input[type="text"], input[type="password"], input[type="number"] {
            width: 100%; margin-top: 0.35rem; padding: 0.5rem 0.65rem; border-radius: 8px; border: 1px solid #333;
            background: var(--card); color: var(--text);
        }
        button { margin-top: 1.5rem; background: var(--accent); color: #000; border: none; padding: 0.55rem 1.1rem; border-radius: 8px; font-weight: 600; cursor: pointer; }
        button:hover { filter: brightness(1.08); }
        .err { background: #2a1515; border: 1px solid var(--err); color: #fcc; padding: 0.75rem 1rem; border-radius: 8px; margin: 1rem 0; font-size: 0.9rem; }
        .ok { background: #152a1a; border: 1px solid #3c6; color: #cfc; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
        code { font-size: 0.85em; background: #222; padding: 0.1em 0.35em; border-radius: 4px; }
        fieldset { border: 1px solid #333; border-radius: 10px; padding: 1rem 1.1rem; margin-top: 1.25rem; }
        legend { padding: 0 0.5rem; color: var(--muted); font-size: 0.9rem; }
    </style>
</head>
<body>
<main>
    <h1>Installation (MariaDB / Plesk)</h1>
    <p class="note">In Plesk unter <strong>Datenbanken</strong> eine leere MariaDB-Datenbank und einen Benutzer anlegen, der Rechte auf diese Datenbank hat (inkl. CREATE TABLE). Host meist <code>localhost</code>.</p>
    @if($configWillOverwrite)
        <div class="err" style="border-color:#fa0;color:#fda;">Hinweis: <code>config/hub.php</code> existiert bereits ohne Installationskennzeichnung — ein erneutes Absenden <strong>überschreibt</strong> die Datei.</div>
    @endif

    @if($done)
        <div class="ok">
            <strong>Installation abgeschlossen.</strong><br>
            Aus Sicherheitsgründen diese Datei <code>public/install.php</code> per FTP oder Plesk-Dateimanager <strong>löschen</strong> oder umbenennen.<br><br>
            @if($savedInternalToken !== null)
                <p><strong>Interner Token</strong> (für Cron/Sync und einmalig <code>backend.php?token=…</code>) — <strong>jetzt kopieren</strong>, wird nicht erneut angezeigt:</p>
                <p style="word-break:break-all;background:#111;padding:0.75rem;border-radius:6px;"><code>{{ $savedInternalToken }}</code></p>
                <p class="note">Optional: in <code>.env</code> als <code>INTERNAL_TOKEN=…</code> ablegen (siehe .env.example).</p>
            @endif
            <a href="{{ url('/index.php') }}" style="color:#8cf;">Zur Anwendung</a>
        </div>
    @else
        @foreach($errors as $e)
            <div class="err">{{ $e }}</div>
        @endforeach

        <form method="post" action="{{ url('/install.php') }}">
            @csrf
            <fieldset>
                <legend>MariaDB (Plesk)</legend>
                <label>Host
                    <input type="text" name="db_host" value="{{ old('db_host', $post['db_host'] ?? 'localhost') }}" required autocomplete="off">
                </label>
                <label>Port
                    <input type="number" name="db_port" value="{{ old('db_port', $post['db_port'] ?? 3306) }}" min="1" max="65535">
                </label>
                <label>Datenbankname
                    <input type="text" name="db_name" value="{{ old('db_name', $post['db_name'] ?? '') }}" required placeholder="z. B. yt_hub" autocomplete="off">
                </label>
                <label>Benutzer
                    <input type="text" name="db_user" value="{{ old('db_user', $post['db_user'] ?? '') }}" required autocomplete="off">
                </label>
                <label>Passwort
                    <input type="password" name="db_pass" value="" autocomplete="new-password">
                </label>
                <label>Optional: Unix-Socket (statt Host/Port, selten nötig)
                    <input type="text" name="db_socket" value="{{ old('db_socket', $post['db_socket'] ?? '') }}" placeholder="/var/lib/mysql/mysql.sock" autocomplete="off">
                </label>
            </fieldset>

            <fieldset>
                <legend>Google APIs (optional, später in config/hub.php ergänzbar)</legend>
                <label>API-Key (YouTube Data API)
                    <input type="text" name="google_api_key" value="{{ old('google_api_key', $post['google_api_key'] ?? '') }}" autocomplete="off">
                </label>
                <label>OAuth Client-ID
                    <input type="text" name="google_client_id" value="{{ old('google_client_id', $post['google_client_id'] ?? '') }}" autocomplete="off">
                </label>
                <label>OAuth Client-Secret
                    <input type="password" name="google_client_secret" value="" autocomplete="new-password">
                </label>
                <label>OAuth Redirect-URI (leer = automatisch)
                    <input type="text" name="google_redirect_uri" value="{{ old('google_redirect_uri', $post['google_redirect_uri'] ?? '') }}" placeholder="{{ $suggestedRedirect }}">
                </label>
                <p class="note">Vorschlag Redirect-URI: <code>{{ $suggestedRedirect }}</code></p>
            </fieldset>

            <button type="submit">Installieren</button>
        </form>
    @endif
</main>
</body>
</html>
