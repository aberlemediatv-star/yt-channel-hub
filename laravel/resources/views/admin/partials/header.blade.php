@php
    use YtHub\AdminAuth;
    use YtHub\Csrf;
    use YtHub\Lang;
    $cur = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '';
    $adminSession = class_exists(AdminAuth::class) && AdminAuth::isLoggedIn();
    // Lightweight group detection so we can visually mark the active section.
    $inSection = static function (array $needles) use ($cur, $path): bool {
        foreach ($needles as $n) {
            if ($cur === $n) return true;
            if ($n !== '' && str_contains($path, $n)) return true;
        }
        return false;
    };
    $sec = [
        'dashboard' => $cur === 'index.php' && str_starts_with($path, '/admin/'),
        'channels' => $inSection(['channel_edit.php', 'channel_save.php']),
        'feeds' => $inSection(['advanced-feeds', 'mrss-feeds']),
        'analytics' => $inSection(['analytics.php', 'analytics_export.php']),
        'social' => $inSection(['/admin/social/']),
        'staff' => $inSection(['staff_manage.php', 'staff_channels.php', 'staff_modules.php']),
        'system' => $inSection(['logs.php', 'api-keys', 'audit']),
    ];
@endphp
<header class="adm-head" role="banner">
    <a href="{{ url('/admin/index.php') }}" style="text-decoration:none;display:inline-flex;align-items:center;gap:.65rem;">
        <img src="{{ asset('assets/imh-group-logo.svg') }}" alt="IMH Group" class="adm-logo">
        <span class="adm-title">{{ Lang::t('admin.title') }}</span>
    </a>
    <nav class="adm-nav" aria-label="Admin">
        <a href="{{ url('/admin/index.php') }}"
           @class(['adm-nav-link', 'is-active' => $sec['dashboard']])
           @if($sec['dashboard']) aria-current="page" @endif>
            {{ Lang::t('admin.nav.dashboard') }}
        </a>

        <details class="adm-nav-group" @if($sec['channels'] || $sec['feeds']) open @endif>
            <summary @class(['adm-nav-summary', 'is-active' => $sec['channels'] || $sec['feeds']])>{{ Lang::t('admin.nav.content') }}</summary>
            <div class="adm-nav-sub">
                <a href="{{ url('/admin/index.php') }}" @if($sec['channels']) aria-current="page" @endif>{{ Lang::t('admin.channels') }}</a>
                <a href="{{ url('/admin/channel_edit.php') }}">{{ Lang::t('admin.add_channel') }}</a>
                <a href="{{ url('/admin/mrss-feeds') }}">{{ Lang::t('admin.nav.mrss') }}</a>
                <a href="{{ url('/admin/advanced-feeds') }}">{{ Lang::t('admin.nav.advanced_feeds') }}</a>
            </div>
        </details>

        <a href="{{ url('/admin/analytics.php') }}"
           @class(['adm-nav-link', 'is-active' => $sec['analytics']])
           @if($sec['analytics']) aria-current="page" @endif>
            {{ Lang::t('admin.analytics') }}
        </a>

        <details class="adm-nav-group" @if($sec['social']) open @endif>
            <summary @class(['adm-nav-summary', 'is-active' => $sec['social']])>{{ Lang::t('admin.nav.social') }}</summary>
            <div class="adm-nav-sub">
                <a href="{{ url('/admin/social/posts') }}">{{ Lang::t('admin.nav.social_posts') }}</a>
                <a href="{{ url('/admin/social/accounts') }}">{{ Lang::t('admin.nav.social_accounts') }}</a>
                <a href="{{ url('/admin/social/activate') }}">{{ Lang::t('admin.nav.social_activate') }}</a>
                <a href="{{ url('/admin/social/settings') }}">{{ Lang::t('admin.nav.social_settings') }}</a>
            </div>
        </details>

        <details class="adm-nav-group" @if($sec['staff']) open @endif>
            <summary @class(['adm-nav-summary', 'is-active' => $sec['staff']])>{{ Lang::t('admin.staff') }}</summary>
            <div class="adm-nav-sub">
                <a href="{{ url('/admin/staff_manage.php') }}">{{ Lang::t('admin.nav.staff_manage') }}</a>
            </div>
        </details>

        <details class="adm-nav-group" @if($sec['system']) open @endif>
            <summary @class(['adm-nav-summary', 'is-active' => $sec['system']])>{{ Lang::t('admin.nav.system') }}</summary>
            <div class="adm-nav-sub">
                <a href="{{ url('/admin/logs.php') }}">{{ Lang::t('admin.nav_logs') }}</a>
                <a href="{{ url('/admin/audit') }}">{{ Lang::t('admin.nav.audit') }}</a>
                <a href="{{ url('/admin/api-keys') }}">{{ Lang::t('admin.nav.api_keys') }}</a>
                <a href="{{ url('/health.php') }}" target="_blank" rel="noopener">{{ Lang::t('admin.nav.health') }}</a>
            </div>
        </details>

        <a class="adm-nav-link" href="{{ url('/index.php') }}">{{ Lang::t('admin.frontend') }}</a>

        @if($adminSession)
            <form method="post" action="{{ url('/admin/logout.php') }}" class="adm-inline">
                {!! Csrf::hiddenField() !!}
                <button type="submit" class="adm-logout-btn">{{ Lang::t('admin.logout') }}</button>
            </form>
        @endif
        @include('site.partials.lang-switcher-inline')
    </nav>
</header>
<style>
.adm-nav { display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; }
.adm-nav-link.is-active,
.adm-nav-summary.is-active { color:var(--adm-accent, #3d9eff); font-weight:600; }
.adm-nav-group { position:relative; }
.adm-nav-group > summary {
    list-style:none; cursor:pointer; padding:.25rem .1rem;
    display:inline-flex; align-items:center; gap:.25rem;
}
.adm-nav-group > summary::-webkit-details-marker { display:none; }
.adm-nav-group > summary::after { content:"▾"; font-size:.7em; opacity:.6; }
.adm-nav-group[open] > summary::after { content:"▴"; }
.adm-nav-sub {
    position:absolute; top:100%; left:0; min-width:220px; z-index:20;
    background:var(--adm-card, #1a1a20); border:1px solid var(--adm-border, #333);
    border-radius:8px; padding:.35rem; display:flex; flex-direction:column;
    box-shadow:0 8px 24px rgba(0,0,0,.35);
}
.adm-nav-sub a {
    padding:.45rem .6rem; border-radius:6px; text-decoration:none;
    color:inherit; display:block;
}
.adm-nav-sub a:hover { background:var(--adm-card-hover, #242430); }
@media (max-width: 780px) {
    .adm-nav-sub { position:static; box-shadow:none; border:none; padding:.25rem 0 .25rem 1rem; }
}
</style>
