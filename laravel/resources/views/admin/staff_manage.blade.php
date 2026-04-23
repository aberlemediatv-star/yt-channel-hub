@php
    use YtHub\Csrf;
    use YtHub\Lang;
    /** @var \YtHub\StaffRepository $repo */
@endphp
<!DOCTYPE html>
<html lang="{{ Lang::code() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ Lang::t('admin.staff') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
</head>
<body class="adm-body">
@include('admin.partials.header')
<main class="adm-main" role="main">
    @include('admin.partials.flash')

    <p class="adm-note">{!! Lang::tRich('admin.staff_note_blurb') !!}</p>

    <h2 class="adm-h2">{{ Lang::t('admin.staff_section_new') }}</h2>
    <form method="post" action="{{ url('/admin/staff_manage.php') }}" class="adm-form">
        {!! Csrf::hiddenField() !!}
        <input type="hidden" name="action" value="create">
        <label>{{ Lang::t('admin.staff_label_username') }} <input type="text" name="username" required autocomplete="off" maxlength="64" value="{{ old('username', '') }}"></label>
        <label>{{ Lang::t('admin.staff_label_password') }} <input type="password" name="password" required autocomplete="new-password"></label>
        <div class="adm-form-row"><button type="submit" class="adm-btn">{{ Lang::t('admin.staff_btn_create') }}</button></div>
    </form>

    <h2 class="adm-h2">{{ Lang::t('admin.staff_section_list') }}</h2>
    <table class="adm-table">
        <thead>
            <tr>
                <th scope="col">{{ Lang::t('admin.staff_th_id') }}</th>
                <th scope="col">{{ Lang::t('admin.staff_th_username') }}</th>
                <th scope="col">{{ Lang::t('admin.staff_th_channels') }}</th>
                <th scope="col">{{ Lang::t('admin.staff_th_actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach($staffList as $s)
            @php
                $sid = (int) $s['id'];
                $stats = $repo->channelAccessStats($sid);
                $chLabel = $stats['allowed'] === 0 && $stats['blocked'] === 0
                    ? Lang::t('admin.staff_channel_none')
                    : sprintf(Lang::t('admin.staff_channel_access'), $stats['allowed'])
                        . ($stats['blocked'] > 0 ? sprintf(Lang::t('admin.staff_channel_blocked'), $stats['blocked']) : '');
            @endphp
            <tr>
                <td>{{ $sid }}</td>
                <td>{{ $s['username'] }}</td>
                <td>{{ $chLabel }}</td>
                <td class="adm-actions">
                    <a class="adm-btn adm-btn-secondary" href="{{ url('/admin/staff_modules.php?id='.$sid) }}">{{ Lang::t('admin.staff_btn_modules') }}</a>
                    <a class="adm-btn adm-btn-secondary" href="{{ url('/admin/staff_channels.php?id='.$sid) }}">{{ Lang::t('admin.staff_btn_channels') }}</a>
                    <form class="adm-inline" method="post" action="{{ url('/admin/staff_manage.php') }}" style="display:inline-flex;gap:.25rem;align-items:center;">
                        {!! Csrf::hiddenField() !!}
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="id" value="{{ $sid }}">
                        <input type="password" name="password" required minlength="12" autocomplete="new-password" placeholder="{{ Lang::t('admin.staff_label_password') }}" style="max-width:140px;">
                        <button type="submit" class="adm-btn adm-btn-secondary">{{ Lang::t('admin.staff_btn_reset_password') }}</button>
                    </form>
                    <form class="adm-inline" method="post" action="{{ url('/admin/staff_manage.php') }}" onsubmit="return confirm(@json(Lang::t('admin.staff_confirm_delete')));">
                        {!! Csrf::hiddenField() !!}
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="{{ $sid }}">
                        <button type="submit" class="adm-btn adm-btn-secondary">{{ Lang::t('admin.staff_btn_delete') }}</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</main>
</body>
</html>
