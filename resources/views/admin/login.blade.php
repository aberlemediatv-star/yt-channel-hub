@php
    use YtHub\Csrf;
    use YtHub\Lang;
@endphp
<!DOCTYPE html>
<html lang="{{ Lang::code() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ Lang::t('admin.login_title') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
</head>
<body class="adm-body">
<div class="adm-login-wrap">
<form method="post" action="{{ url('/admin/login.php') }}" class="adm-login-box">
    <img src="{{ asset('assets/imh-group-logo.svg') }}" alt="IMH Group" class="adm-logo-lg">
    <h1>{{ Lang::t('admin.login_title') }}</h1>
    @if($error !== '')
        <div class="err">{{ $error }}</div>
    @endif
    {!! Csrf::hiddenField() !!}
    <label for="pw">{{ Lang::t('admin.password') }}</label>
    <input type="password" id="pw" name="password" required autocomplete="current-password" autofocus>
    <button type="submit">{{ Lang::t('admin.sign_in') }}</button>
    <p class="note">{!! Lang::tRich('admin.hash_note') !!}</p>
    <p class="adm-note" style="margin-top:1rem;">@include('site.partials.lang-switcher-inline')</p>
</form>
</div>
</body>
</html>
