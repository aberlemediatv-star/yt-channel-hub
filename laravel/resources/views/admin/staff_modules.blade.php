@php
    use YtHub\Csrf;
    use YtHub\Lang;
    use YtHub\StaffModule;
    $uname = (string) $staff['username'];
@endphp
<!DOCTYPE html>
<html lang="{{ Lang::code() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ sprintf(Lang::t('admin.staff_doc_modules'), $uname) }}</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
</head>
<body class="adm-body">
@include('admin.partials.header')
<main class="adm-main" role="main">
    @include('admin.partials.flash')

    <p class="adm-actions">
        <a class="adm-btn adm-btn-secondary" href="{{ url('/admin/staff_manage.php') }}">{{ Lang::t('admin.staff_back') }}</a>
        <a class="adm-btn adm-btn-secondary" href="{{ url('/admin/staff_channels.php?id='.$id) }}">{{ Lang::t('admin.staff_btn_channels') }}</a>
    </p>

    <h2 class="adm-h2">{{ sprintf(Lang::t('admin.staff_heading_modules'), $uname) }}</h2>
    <p class="adm-note">{{ Lang::t('admin.staff_modules_note') }}</p>

    <form method="post" action="{{ url('/admin/staff_modules.php?id='.$id) }}">
        {!! Csrf::hiddenField() !!}
        <table class="adm-table">
            <thead>
                <tr>
                    <th scope="col">{{ Lang::t('admin.staff_th_module') }}</th>
                    <th scope="col">{{ Lang::t('admin.staff_th_allowed') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ Lang::t('admin.staff_mod_label_upload') }}</td>
                    <td><label><input type="checkbox" name="mod[{{ StaffModule::UPLOAD }}]" value="1" @if(!empty($mods[StaffModule::UPLOAD])) checked @endif> {{ Lang::t('admin.staff_mod_active') }}</label></td>
                </tr>
                <tr>
                    <td>{{ Lang::t('admin.staff_mod_label_edit') }}</td>
                    <td><label><input type="checkbox" name="mod[{{ StaffModule::EDIT_VIDEO }}]" value="1" @if(!empty($mods[StaffModule::EDIT_VIDEO])) checked @endif> {{ Lang::t('admin.staff_mod_active') }}</label></td>
                </tr>
                <tr>
                    <td>{{ Lang::t('admin.staff_mod_label_revenue') }}</td>
                    <td><label><input type="checkbox" name="mod[{{ StaffModule::VIEW_REVENUE }}]" value="1" @if(!empty($mods[StaffModule::VIEW_REVENUE])) checked @endif> {{ Lang::t('admin.staff_mod_active') }}</label></td>
                </tr>
            </tbody>
        </table>
        <p class="adm-form-row"><button type="submit" class="adm-btn">{{ Lang::t('admin.save') }}</button></p>
    </form>
</main>
</body>
</html>
