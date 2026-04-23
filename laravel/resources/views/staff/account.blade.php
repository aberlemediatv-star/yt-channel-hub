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
    <title>{{ Lang::t('staff.account') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
</head>
<body class="adm-body">
@include('staff.partials.nav', ['mods' => $mods, 'staffPageTitle' => Lang::t('staff.account')])
<main class="adm-main" role="main">
    <h1 class="adm-h1">{{ Lang::t('staff.account') }}</h1>

    @if(session('ok'))
        <div class="adm-flash adm-flash-ok">{{ session('ok') }}</div>
    @endif
    @if(session('err'))
        <div class="adm-flash adm-flash-err">{{ session('err') }}</div>
    @endif

    <form method="post" action="{{ url('/staff/account/password') }}" class="adm-form" style="max-width:420px;">
        {!! StaffCsrf::hiddenField() !!}
        <label>{{ Lang::t('staff.account_label_current') }}
            <input type="password" name="current_password" autocomplete="current-password" required>
        </label>
        <label>{{ Lang::t('staff.account_label_new') }}
            <input type="password" name="new_password" autocomplete="new-password" required minlength="12">
        </label>
        <label>{{ Lang::t('staff.account_label_new_confirm') }}
            <input type="password" name="new_password_confirm" autocomplete="new-password" required minlength="12">
        </label>
        <div class="adm-form-row"><button type="submit" class="adm-btn">{{ Lang::t('staff.account_btn_change') }}</button></div>
    </form>
</main>
</body>
</html>
