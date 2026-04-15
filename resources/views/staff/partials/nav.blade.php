@php
    use YtHub\Lang;
    use YtHub\StaffCsrf;
    use YtHub\StaffModule;
    $mods = $mods ?? [];
    $staffPageTitle = $staffPageTitle ?? null;
@endphp
<header class="adm-head" role="banner">
    <a href="{{ url('/staff/index.php') }}" style="text-decoration:none;display:inline-flex;align-items:center;gap:.65rem;">
        <img src="{{ asset('assets/imh-group-logo.svg') }}" alt="IMH Group" class="adm-logo">
        <span class="adm-title">{{ $staffPageTitle ?? Lang::t('staff.title') }}</span>
    </a>
    <nav class="adm-nav" aria-label="Staff">
        <a href="{{ url('/staff/index.php') }}">{{ Lang::t('staff.nav_home') }}</a>
        @if(!empty($mods[StaffModule::VIEW_REVENUE]))
            <a href="{{ url('/staff/revenue.php') }}">{{ Lang::t('staff.nav_revenue') }}</a>
        @endif
        @if(!empty($mods[StaffModule::EDIT_VIDEO]))
            <a href="{{ url('/staff/videos.php') }}">{{ Lang::t('staff.nav_videos') }}</a>
        @endif
        <form method="post" action="{{ url('/staff/logout.php') }}" class="adm-inline">
            {!! StaffCsrf::hiddenField() !!}
            <button type="submit" class="adm-logout-btn">{{ Lang::t('staff.logout') }}</button>
        </form>
        @include('site.partials.lang-switcher-inline')
    </nav>
</header>
