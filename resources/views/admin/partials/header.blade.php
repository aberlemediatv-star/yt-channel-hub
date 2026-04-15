@php
    use YtHub\Csrf;
    use YtHub\Lang;
    $cur = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
@endphp
<header class="adm-head" role="banner">
    <a href="{{ url('/admin/index.php') }}" style="text-decoration:none;display:inline-flex;align-items:center;gap:.65rem;">
        <img src="{{ asset('assets/imh-group-logo.svg') }}" alt="IMH Group" class="adm-logo">
        <span class="adm-title">{{ Lang::t('admin.title') }}</span>
    </a>
    <nav class="adm-nav" aria-label="Admin">
        <a href="{{ url('/admin/index.php') }}" @if($cur === 'index.php') aria-current="page" @endif>{{ Lang::t('admin.channels') }}</a>
        <a href="{{ url('/admin/analytics.php') }}" @if($cur === 'analytics.php') aria-current="page" @endif>{{ Lang::t('admin.analytics') }}</a>
        <a href="{{ url('/admin/logs.php') }}" @if($cur === 'logs.php') aria-current="page" @endif>{{ Lang::t('admin.nav_logs') }}</a>
        <a href="{{ url('/admin/staff_manage.php') }}" @if(in_array($cur, ['staff_manage.php', 'staff_channels.php', 'staff_modules.php'], true)) aria-current="page" @endif>{{ Lang::t('admin.staff') }}</a>
        <a href="{{ url('/index.php') }}">{{ Lang::t('admin.frontend') }}</a>
        <form method="post" action="{{ url('/admin/logout.php') }}" class="adm-inline">
            {!! Csrf::hiddenField() !!}
            <button type="submit" class="adm-logout-btn">{{ Lang::t('admin.logout') }}</button>
        </form>
        @include('site.partials.lang-switcher-inline')
    </nav>
</header>
