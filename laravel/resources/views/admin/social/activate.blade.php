@php use YtHub\Lang; @endphp
<!doctype html>
<html lang="{{ Lang::code() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Social Aktivierung</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
    <style>
        .check-card { border:1px solid var(--adm-border); border-radius:var(--adm-radius); padding:1rem; margin-top:1rem; background:var(--adm-card); }
        .check-card h2 { margin:0 0 0.5rem; font-size:0.95rem; }
        .ok { color:#8fe0b0; font-weight:600; }
        .bad { color:#ffa8a8; font-weight:600; }
    </style>
</head>
<body class="adm-body">
@include('admin.partials.header')
<main class="adm-main" role="main">
    <h1 class="adm-h1">{{ Lang::t('admin.nav.social_activate') }}</h1>

    <div class="check-card">
        <h2>Grundchecks</h2>
        <ul style="margin:0;padding-left:1.25rem;color:var(--adm-muted);font-size:0.86rem;line-height:1.8;">
            <li>
                APP_URL gesetzt:
                @if($checks['app_url_set']) <span class="ok">OK</span> @else <span class="bad">FEHLT</span> @endif
                <div style="margin-top:4px;">Aktuell: <code>{{ $appUrl !== '' ? $appUrl : '(leer)' }}</code></div>
            </li>
            <li style="margin-top:0.5rem;">
                HTTPS (für echte OAuth-Redirects nötig):
                @if($checks['app_url_https']) <span class="ok">OK</span> @else <span class="bad">NICHT HTTPS</span> @endif
                <div style="margin-top:4px;">Lokal ist das normal. Später in Produktion muss <code>APP_URL</code> auf <code>https://…/app</code> zeigen.</div>
            </li>
        </ul>
    </div>

    <div class="check-card">
        <h2>Instagram (Meta)</h2>
        <div style="font-size:0.86rem;">Keys in DB: @if($checks['meta_keys']) <span class="ok">OK</span> @else <span class="bad">FEHLT</span> @endif</div>
        <p class="adm-note" style="margin-top:0.5rem;">
            Redirect URI (im Meta Developer Portal eintragen): <code>{{ $redirects['meta'] !== '' ? $redirects['meta'] : '(APP_URL fehlt)' }}</code>
        </p>
        @if(!empty($checks['meta_keys']))
            <div style="margin-top:0.65rem;">
                <a class="adm-btn" href="{{ route('oauth.meta.start', ['token' => $token]) }}">Mit Instagram verbinden</a>
            </div>
        @endif
        <ul style="color:var(--adm-muted);font-size:0.82rem;margin-top:0.5rem;padding-left:1.25rem;">
            <li>OAuth via Facebook Login (Instagram-Business-Account + verknüpfte Facebook-Seite erforderlich).</li>
            <li>Video-Publishing: öffentliche <code>video_url</code> im Payload erforderlich (IG akzeptiert keinen Direkt-Upload).</li>
        </ul>
    </div>

    <div class="check-card">
        <h2>X (Twitter)</h2>
        <div style="font-size:0.86rem;">Keys in DB: @if($checks['x_keys']) <span class="ok">OK</span> @else <span class="bad">FEHLT</span> @endif</div>
        <p class="adm-note" style="margin-top:0.5rem;">
            Redirect URI (im X Developer Portal eintragen): <code>{{ $redirects['x'] !== '' ? $redirects['x'] : '(APP_URL fehlt)' }}</code>
        </p>
        @if(!empty($checks['x_keys']))
            <div style="margin-top:0.65rem;">
                <a class="adm-btn" href="{{ route('oauth.x.start', ['token' => $token]) }}">Mit X verbinden</a>
            </div>
        @endif
        <ul style="color:var(--adm-muted);font-size:0.82rem;margin-top:0.5rem;padding-left:1.25rem;">
            <li>OAuth Connect: Button oben (nach Keys in Settings).</li>
            <li>Video-Post: Media Upload + Tweet — noch nicht final verdrahtet (API/Scopes).</li>
        </ul>
    </div>

    <div class="check-card">
        <h2>TikTok</h2>
        <div style="font-size:0.86rem;">Keys in DB: @if($checks['tiktok_keys']) <span class="ok">OK</span> @else <span class="bad">FEHLT</span> @endif</div>
        <p class="adm-note" style="margin-top:0.5rem;">
            Redirect URI (im TikTok Developer Portal eintragen): <code>{{ $redirects['tiktok'] !== '' ? $redirects['tiktok'] : '(APP_URL fehlt)' }}</code>
        </p>
        @if(!empty($checks['tiktok_keys']))
            <div style="margin-top:0.65rem;">
                <a class="adm-btn" href="{{ route('oauth.tiktok.start', ['token' => $token]) }}">Mit TikTok verbinden</a>
            </div>
        @endif
        <ul style="color:var(--adm-muted);font-size:0.82rem;margin-top:0.5rem;padding-left:1.25rem;">
            <li>OAuth (Login Kit): Button oben; aktuell Scope <code>user.info.basic</code> — für Upload später <code>video.upload</code>/<code>video.publish</code> + App Review.</li>
        </ul>
    </div>

    <div class="check-card">
        <h2>Facebook Pages</h2>
        @if(!empty($checks['facebook_incomplete']))
            <p class="bad" style="margin:0 0 0.5rem;">Hinweis: „Aktiviert" ist an, aber App-ID/Secret fehlen noch.</p>
        @endif
        <div style="font-size:0.86rem;">
            Aktiviert: @if($checks['facebook_enabled']) <span class="ok">JA</span> @else <span class="bad">NEIN</span> @endif
            — Keys: @if($checks['facebook_keys']) <span class="ok">OK</span> @else <span class="bad">FEHLT</span> @endif
        </div>
        <p class="adm-note" style="margin-top:0.5rem;">
            Redirect URI: <code>{{ $redirects['facebook'] !== '' ? $redirects['facebook'] : '(APP_URL fehlt)' }}</code>
        </p>
        @if(!empty($checks['facebook_enabled']) && !empty($checks['facebook_keys']))
            <div style="margin-top:0.65rem;">
                <a class="adm-btn" href="{{ route('oauth.facebook.start', ['token' => $token]) }}">Mit Facebook verbinden</a>
            </div>
        @endif
        <ul style="color:var(--adm-muted);font-size:0.82rem;margin-top:0.5rem;padding-left:1.25rem;">
            <li>Speichert pro Page ein SocialAccount (<code>platform=facebook</code>) + bei verknüpftem IG-Business auch ein <code>platform=instagram</code>-Konto.</li>
            <li>Video-Publish: Graph v19 <code>/{page-id}/videos</code> mit <code>source</code>-Multipart.</li>
        </ul>
    </div>

    <div class="check-card">
        <h2>LinkedIn</h2>
        @if(!empty($checks['linkedin_incomplete']))
            <p class="bad" style="margin:0 0 0.5rem;">Hinweis: „Aktiviert" ist an, aber Client-ID/Secret fehlen noch.</p>
        @endif
        <div style="font-size:0.86rem;">
            Aktiviert: @if($checks['linkedin_enabled']) <span class="ok">JA</span> @else <span class="bad">NEIN</span> @endif
            — Keys: @if($checks['linkedin_keys']) <span class="ok">OK</span> @else <span class="bad">FEHLT</span> @endif
        </div>
        <p class="adm-note" style="margin-top:0.5rem;">
            Redirect URI: <code>{{ $redirects['linkedin'] !== '' ? $redirects['linkedin'] : '(APP_URL fehlt)' }}</code>
        </p>
        @if(!empty($checks['linkedin_enabled']) && !empty($checks['linkedin_keys']))
            <div style="margin-top:0.65rem;">
                <a class="adm-btn" href="{{ route('oauth.linkedin.start', ['token' => $token]) }}">Mit LinkedIn verbinden</a>
            </div>
        @endif
        <ul style="color:var(--adm-muted);font-size:0.82rem;margin-top:0.5rem;padding-left:1.25rem;">
            <li>Scopes: <code>openid profile email w_member_social</code>.</li>
            <li>Video-Publish: Assets API v2 (registerUpload → PUT → ugcPosts).</li>
        </ul>
    </div>
</main>
</body>
</html>
