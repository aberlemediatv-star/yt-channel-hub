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
    <title>{{ Lang::t('admin.title') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
</head>
<body class="adm-body">
@include('admin.partials.header')
<main class="adm-main" role="main">
    @include('admin.partials.flash')

    <p class="adm-note">{{ $workerNote }}</p>
    <p class="adm-note">{!! Lang::tRich('admin.oauth_scopes_note') !!}</p>

    <h2 class="adm-h2">{{ Lang::t('admin.channels') }}</h2>
    <p class="adm-actions">
        <a class="adm-btn" href="{{ url('/admin/channel_edit.php') }}">{{ Lang::t('admin.add_channel') }}</a>
        <form class="adm-inline" method="post" action="{{ url('/admin/enqueue.php') }}">
            {!! Csrf::hiddenField() !!}
            <input type="hidden" name="job_type" value="video_sync_all">
            <button type="submit" class="adm-btn">{{ Lang::t('admin.job_video_all') }}</button>
        </form>
        <form class="adm-inline" method="post" action="{{ url('/admin/enqueue.php') }}">
            {!! Csrf::hiddenField() !!}
            <input type="hidden" name="job_type" value="analytics_sync_all">
            <input type="hidden" name="days" value="28">
            <button type="submit" class="adm-btn">{{ Lang::t('admin.job_analytics_all') }}</button>
        </form>
    </p>

    <table class="adm-table">
        <thead>
            <tr>
                <th scope="col">{{ Lang::t('admin.channel_id') }}</th>
                <th scope="col">{{ Lang::t('admin.title_label') }}</th>
                <th scope="col">{{ Lang::t('admin.slug_col') }}</th>
                <th scope="col">{{ Lang::t('admin.yt_col') }}</th>
                <th scope="col">{{ Lang::t('admin.active_col') }}</th>
                <th scope="col">{{ Lang::t('admin.video_sync') }}</th>
                <th scope="col">{{ Lang::t('admin.analytics_col') }}</th>
                <th scope="col">{{ Lang::t('admin.actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach($channels as $c)
            <tr>
                <td>{{ (int) $c['id'] }}</td>
                <td>{{ $c['title'] }}</td>
                <td><code>{{ $c['slug'] }}</code></td>
                <td><code>{{ $c['youtube_channel_id'] }}</code></td>
                <td>{{ (int) $c['is_active'] ? Lang::t('admin.yes') : Lang::t('admin.no') }}</td>
                <td>{{ $c['last_video_sync_at'] ? $c['last_video_sync_at'] : '—' }}</td>
                <td>{{ $c['last_analytics_sync_at'] ? $c['last_analytics_sync_at'] : '—' }}</td>
                <td class="adm-actions">
                    <a class="adm-btn adm-btn-secondary" href="{{ url('/admin/channel_edit.php?id='.(int) $c['id']) }}">{{ Lang::t('admin.edit') }}</a>
                    <a class="adm-btn adm-btn-secondary" href="{{ url('/oauth_start.php?channel_id='.(int) $c['id']) }}">{{ Lang::t('admin.oauth') }}</a>
                    <form class="adm-inline" method="post" action="{{ url('/admin/enqueue.php') }}">
                        {!! Csrf::hiddenField() !!}
                        <input type="hidden" name="job_type" value="video_sync_channel">
                        <input type="hidden" name="channel_id" value="{{ (int) $c['id'] }}">
                        <button type="submit" class="adm-btn">{{ Lang::t('admin.job_video') }}</button>
                    </form>
                    <form class="adm-inline" method="post" action="{{ url('/admin/enqueue.php') }}">
                        {!! Csrf::hiddenField() !!}
                        <input type="hidden" name="job_type" value="analytics_sync_channel">
                        <input type="hidden" name="channel_id" value="{{ (int) $c['id'] }}">
                        <input type="hidden" name="days" value="28">
                        <button type="submit" class="adm-btn">{{ Lang::t('admin.job_analytics') }}</button>
                    </form>
                    <form class="adm-inline" method="post" action="{{ url('/admin/channel_delete.php') }}" onsubmit="return confirm(@json(Lang::t('admin.confirm_delete_channel')));">
                        {!! Csrf::hiddenField() !!}
                        <input type="hidden" name="id" value="{{ (int) $c['id'] }}">
                        <button type="submit" class="adm-btn adm-btn-secondary">{{ Lang::t('admin.delete') }}</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h2 class="adm-h2">{{ Lang::t('admin.jobs') }}</h2>
    <table class="adm-table">
        <thead>
            <tr>
                <th scope="col">{{ Lang::t('admin.jobs_col_id') }}</th>
                <th scope="col">{{ Lang::t('admin.jobs_col_type') }}</th>
                <th scope="col">{{ Lang::t('admin.jobs_col_channel') }}</th>
                <th scope="col">{{ Lang::t('admin.jobs_col_status') }}</th>
                <th scope="col">{{ Lang::t('admin.jobs_col_created') }}</th>
                <th scope="col">{{ Lang::t('admin.jobs_col_error') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach($jobs as $j)
            <tr>
                <td>{{ (int) $j['id'] }}</td>
                <td><code>{{ $j['job_type'] }}</code></td>
                <td>{{ $j['channel_id'] !== null ? (int) $j['channel_id'] : '—' }}</td>
                <td>{{ $j['status'] }}</td>
                <td>{{ $j['created_at'] }}</td>
                <td>{{ $j['error_message'] ? mb_substr((string) $j['error_message'], 0, 120) : '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</main>
</body>
</html>
