@php
    use YtHub\Lang;
    use YtHub\PublicHttp;
@endphp
<!DOCTYPE html>
<html lang="{{ Lang::code() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ Lang::t('backend.title') }}</title>
    @foreach(\YtHub\Lang::SUPPORTED as $lc)
        <link rel="alternate" hreflang="{{ $lc }}" href="{{ \YtHub\Lang::absoluteUrl(($hreflangPage ?? 'backend.php').'?'.http_build_query(['lang' => $lc])) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ \YtHub\Lang::absoluteUrl(($hreflangPage ?? 'backend.php').'?'.http_build_query(['lang' => 'de'])) }}">
    <link rel="preload" href="{{ asset('assets/theme.css') }}" as="style">
    <link rel="stylesheet" href="{{ asset('assets/theme.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/backend-upload.css') }}">
</head>
<body>
<header class="backend-header">
    <h1>{{ Lang::t('backend.title') }}</h1>
    <nav class="backend-nav" aria-label="{{ Lang::t('public.main_nav_aria') }}">
        <a href="{{ Lang::relUrl('index.php') }}">{{ Lang::t('backend.nav_home') }}</a>
        <a href="{{ Lang::relUrl('datenschutz.php') }}">{{ Lang::t('public.nav_privacy') }}</a>
        <a href="{{ Lang::relUrl('impressum.php') }}">{{ Lang::t('public.nav_imprint') }}</a>
        <span class="bu-subnav"><a href="#upload">{{ Lang::t('backend.nav_upload') }}</a></span>
    </nav>
    @include('site.partials.lang-switcher-inline')
</header>
<main class="backend-main">
    <form method="get" action="{{ url('/backend.php') }}">
        <input type="hidden" name="lang" value="{{ Lang::code() }}">
        <label>{{ Lang::t('backend.form_from') }} <input type="date" name="start" value="{{ $start }}"></label>
        <label>{{ Lang::t('backend.form_to') }} <input type="date" name="end" value="{{ $end }}"></label>
        <button type="submit">{{ Lang::t('backend.apply') }}</button>
    </form>

    <div class="kpis">
        <div class="kpi"><span>{{ Lang::t('backend.kpi_views') }}</span><strong>{{ number_format((float) ($totals['total_views'] ?? 0), 0, ',', '.') }}</strong></div>
        <div class="kpi"><span>{{ Lang::t('backend.kpi_watch') }}</span><strong>{{ number_format((float) ($totals['total_watch_minutes'] ?? 0), 0, ',', '.') }}</strong></div>
        <div class="kpi"><span>{{ Lang::t('backend.kpi_subs') }}</span><strong>{{ number_format((float) ($totals['total_subs_gained'] ?? 0), 0, ',', '.') }}</strong></div>
        <div class="kpi"><span>{{ Lang::t('backend.kpi_revenue') }}</span><strong>{{ number_format((float) ($totals['total_revenue'] ?? 0), 2, ',', '.') }} €</strong></div>
        <div class="kpi"><span>{{ Lang::t('backend.kpi_ad') }}</span><strong>{{ number_format((float) ($totals['total_ad_revenue'] ?? 0), 2, ',', '.') }} €</strong></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ Lang::t('backend.th_channel') }}</th>
                <th>{{ Lang::t('backend.th_views') }}</th>
                <th>{{ Lang::t('backend.th_watch') }}</th>
                <th>{{ Lang::t('backend.th_subs') }}</th>
                <th>{{ Lang::t('backend.th_revenue') }}</th>
                <th>{{ Lang::t('backend.th_ad') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach($perChannel as $row)
            <tr>
                <td>{{ $row['title'] }}</td>
                <td>{{ number_format((float) ($row['views'] ?? 0), 0, ',', '.') }}</td>
                <td>{{ number_format((float) ($row['watch_minutes'] ?? 0), 0, ',', '.') }}</td>
                <td>{{ number_format((float) ($row['subs_gained'] ?? 0), 0, ',', '.') }}</td>
                <td>{{ number_format((float) ($row['revenue'] ?? 0), 2, ',', '.') }}</td>
                <td>{{ number_format((float) ($row['ad_revenue'] ?? 0), 2, ',', '.') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <p class="note">{!! Lang::tRich('backend.note') !!}</p>

    <section id="upload" class="bu-section" aria-labelledby="bu-heading">
        <div id="bu-root">
            <h2 id="bu-heading" class="bu-section-title">{{ Lang::t('backend.upload_heading') }}</h2>
            <p class="bu-lead">{{ Lang::t('backend.upload_lead') }}</p>
            <p class="bu-limits">{{ sprintf(Lang::t('backend.upload_limits'), $uploadMax, $postMax) }}</p>

            <div id="bu-flash" class="bu-flash{{ $buFlash ? ($buFlash['ok'] ? ' bu-flash-ok' : ' bu-flash-err') : '' }}"@if(!$buFlash) style="display:none"@endif role="status">
                @if($buFlash){{ $buFlash['message'] }}@endif
            </div>

            <form id="bu-form" class="bu-card" method="post" action="{{ url('/backend.php') }}" enctype="multipart/form-data">
                <input type="hidden" name="lang" value="{{ Lang::code() }}">
                <input type="hidden" name="backend_action" value="upload">
                <input type="hidden" name="backend_csrf" value="{{ $backendCsrf }}">
                <input type="hidden" name="range_start" value="{{ $start }}">
                <input type="hidden" name="range_end" value="{{ $end }}">

                <div class="bu-field">
                    <label for="bu-channel">{{ Lang::t('backend.upload_channel') }}</label>
                    <select id="bu-channel" name="channel_id" required>
                        <option value="">—</option>
                        @foreach($uploadChannels as $ch)
                            @if(empty($ch['refresh_token'])) @continue @endif
                            <option value="{{ (int) $ch['id'] }}">{{ $ch['title'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="bu-grid">
                    <div>
                        <div id="bu-drop-video" class="bu-drop" tabindex="0" role="button" aria-label="{{ Lang::t('backend.upload_drop_video') }}">
                            <div class="bu-drop-icon" aria-hidden="true">▶</div>
                            <p class="bu-drop-title">{{ Lang::t('backend.upload_drop_video') }}</p>
                            <p class="bu-drop-hint">{{ Lang::t('backend.upload_pick') }}</p>
                            <input id="bu-video" name="video" type="file" accept="video/*">
                        </div>
                        <p id="bu-video-meta" class="bu-file-meta"></p>
                    </div>
                    <div>
                        <div id="bu-drop-thumb" class="bu-drop" tabindex="0" role="button" aria-label="{{ Lang::t('backend.upload_drop_thumb') }}">
                            <div class="bu-drop-icon" aria-hidden="true">🖼</div>
                            <p class="bu-drop-title">{{ Lang::t('backend.upload_drop_thumb') }}</p>
                            <p class="bu-drop-hint">{{ Lang::t('backend.upload_pick') }}</p>
                            <input id="bu-thumb" name="thumbnail" type="file" accept="image/jpeg,image/png">
                        </div>
                        <p id="bu-thumb-meta" class="bu-file-meta"></p>
                        <img id="bu-thumb-preview" class="bu-thumb-preview" alt="">
                    </div>
                </div>

                <div class="bu-field" style="margin-top:1rem;">
                    <label for="bu-yt-id">{{ Lang::t('backend.upload_youtube_id') }} — {{ Lang::t('backend.upload_thumb_only') }}</label>
                    <input id="bu-yt-id" name="youtube_video_id" type="text" placeholder="dQw4w9WgXcQ" maxlength="20" autocomplete="off" pattern="[a-zA-Z0-9_-]{11}">
                </div>

                <div class="bu-field">
                    <label for="bu-title">{{ Lang::t('staff.label_title') }}</label>
                    <input id="bu-title" name="title" type="text" maxlength="500" autocomplete="off">
                </div>

                <div class="bu-field">
                    <label for="bu-desc">{{ Lang::t('staff.label_description') }}</label>
                    <textarea id="bu-desc" name="description" rows="4"></textarea>
                </div>

                <div class="bu-field">
                    <label for="bu-privacy">{{ Lang::t('staff.label_privacy') }}</label>
                    <select id="bu-privacy" name="privacy">
                        <option value="private">{{ Lang::t('staff.privacy_private') }}</option>
                        <option value="unlisted">{{ Lang::t('staff.privacy_unlisted') }}</option>
                        <option value="public">{{ Lang::t('staff.privacy_public') }}</option>
                    </select>
                </div>

                <div class="bu-check">
                    <input id="bu-notify" type="checkbox" name="notify_subscribers" value="1">
                    <label for="bu-notify">{{ Lang::t('staff.notify_subscribers') }}</label>
                </div>

                @include('site.partials.youtube-upload-metadata', ['idPrefix' => $idPrefix ?? 'bu'])

                <div class="bu-row-actions">
                    <button id="bu-submit" type="submit" class="bu-btn bu-btn-primary">{{ Lang::t('staff.upload_submit') }}</button>
                    <button id="bu-clear" type="button" class="bu-btn bu-btn-ghost">{{ Lang::t('backend.upload_clear') }}</button>
                </div>

                <div id="bu-progress" class="bu-progress-wrap">
                    <div id="bu-progress-label" class="bu-progress-label"></div>
                    <div class="bu-progress-track">
                        <div id="bu-progress-bar" class="bu-progress-bar"></div>
                    </div>
                </div>
            </form>
        </div>
    </section>
</main>
@include('site.partials.site-footer')
<script type="application/json" id="bu-i18n-json" nonce="{{ PublicHttp::cspNonce() }}">{{ json_encode([
    'msgProgress' => Lang::t('backend.upload_progress'),
    'msgYoutube' => Lang::t('backend.upload_to_youtube'),
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) }}</script>
<script src="{{ asset('assets/backend-upload.js') }}" defer></script>
</body>
</html>
