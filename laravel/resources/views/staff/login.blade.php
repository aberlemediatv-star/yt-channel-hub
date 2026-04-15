@php
    use YtHub\Lang;
    use YtHub\StaffCsrf;
@endphp
<!DOCTYPE html>
<html lang="{{ Lang::code() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ Lang::t('staff.login_title') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
</head>
<body class="adm-body">
<div class="adm-login-wrap">
<form method="post" action="{{ url('/staff/login.php') }}" class="adm-login-box" autocomplete="off">
    <img src="{{ asset('assets/imh-group-logo.svg') }}" alt="IMH Group" class="adm-logo-lg">
    <h1>{{ Lang::t('staff.login_title') }}</h1>
    <p class="adm-note" style="margin-top:0;">{{ Lang::t('staff.login_blurb') }}</p>
    @if($error !== '')
        <div class="err">{{ $error }}</div>
    @endif
    {!! StaffCsrf::hiddenField() !!}
    <label for="u">{{ Lang::t('staff.username') }}</label>
    <input type="text" id="u" name="username" required autocomplete="username" autofocus value="{{ old('username', '') }}">
    <label for="pw">{{ Lang::t('staff.password') }}</label>
    <input type="password" id="pw" name="password" required autocomplete="current-password">
    <button type="submit">{{ Lang::t('staff.sign_in') }}</button>
    <p class="adm-note" style="margin-top:1rem;">@include('site.partials.lang-switcher-inline')</p>
</form>
</div>
</body>
</html>
