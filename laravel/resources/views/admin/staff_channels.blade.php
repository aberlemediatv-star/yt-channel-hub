@php
    use YtHub\Csrf;
    use YtHub\Lang;
    $uname = (string) $staff['username'];
@endphp
<!DOCTYPE html>
<html lang="{{ Lang::code() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ sprintf(Lang::t('admin.staff_doc_channels'), $uname) }}</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
</head>
<body class="adm-body">
@include('admin.partials.header')
<main class="adm-main" role="main">
    @include('admin.partials.flash')

    <p class="adm-actions">
        <a class="adm-btn adm-btn-secondary" href="{{ url('/admin/staff_manage.php') }}">{{ Lang::t('admin.staff_back') }}</a>
        <a class="adm-btn adm-btn-secondary" href="{{ url('/admin/staff_modules.php?id='.$id) }}">{{ Lang::t('admin.staff_btn_modules') }}</a>
    </p>

    <h2 class="adm-h2">{{ sprintf(Lang::t('admin.staff_heading_channels'), $uname) }}</h2>
    <p class="adm-note">{!! Lang::tRich('admin.staff_channels_note_html') !!}</p>
    <form method="post" action="{{ url('/admin/staff_channels.php?id='.$id) }}">
        {!! Csrf::hiddenField() !!}
        <table class="adm-table">
            <thead>
                <tr>
                    <th scope="col">{{ Lang::t('admin.staff_th_status') }}</th>
                    <th scope="col">{{ Lang::t('admin.staff_th_id') }}</th>
                    <th scope="col">{{ Lang::t('admin.staff_th_title') }}</th>
                    <th scope="col">{{ Lang::t('admin.staff_th_active') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($allChannels as $c)
                @php
                    $cid = (int) $c['id'];
                    $cur = $states[$cid] ?? 'none';
                @endphp
                <tr>
                    <td>
                        <select name="ch[{{ $cid }}]" aria-label="{{ sprintf(Lang::t('admin.staff_aria_channel_status'), $cid) }}">
                            <option value="none" @if($cur === 'none') selected @endif>{{ Lang::t('admin.staff_opt_none') }}</option>
                            <option value="allow" @if($cur === 'allow') selected @endif>{{ Lang::t('admin.staff_opt_allow') }}</option>
                            <option value="block" @if($cur === 'block') selected @endif>{{ Lang::t('admin.staff_opt_block') }}</option>
                        </select>
                    </td>
                    <td>{{ $cid }}</td>
                    <td>{{ $c['title'] }}</td>
                    <td>{{ (int) $c['is_active'] ? Lang::t('admin.yes') : Lang::t('admin.no') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <p class="adm-form-row"><button type="submit" class="adm-btn">{{ Lang::t('admin.save') }}</button></p>
    </form>
</main>
</body>
</html>
