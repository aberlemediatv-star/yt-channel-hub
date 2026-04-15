@php
    use YtHub\Lang;
    use YtHub\StaffModule;
@endphp
<!DOCTYPE html>
<html lang="{{ Lang::code() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ Lang::t('staff.title') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
</head>
<body class="adm-body">
@include('staff.partials.nav', ['mods' => $mods])
<main class="adm-main" role="main">
    <p class="adm-note">{{ Lang::t('staff.index_note') }}</p>

    @if(empty($channels))
        <p class="adm-note">{{ Lang::t('staff.no_channels') }}</p>
    @else
        <table class="adm-table">
            <thead>
                <tr>
                    <th scope="col">{{ Lang::t('staff.col_channel') }}</th>
                    <th scope="col">{{ Lang::t('staff.col_action') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($channels as $c)
                <tr>
                    <td>{{ $c['title'] }}</td>
                    <td>
                        @if(!empty($mods[StaffModule::UPLOAD]))
                            <a class="adm-btn" href="{{ url('/staff/upload.php?channel_id='.(int) $c['id']) }}">{{ Lang::t('staff.upload') }}</a>
                        @else
                            <span class="adm-note">{{ Lang::t('staff.module_upload_off') }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</main>
</body>
</html>
