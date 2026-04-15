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
    <title>{{ Lang::t('admin.analytics') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
</head>
<body class="adm-body">
@include('admin.partials.header')
<main class="adm-main" role="main">
    <form method="get" action="{{ url('/admin/analytics.php') }}" class="adm-filters">
        <label>{{ Lang::t('backend.form_from') }} <input type="date" name="start" value="{{ $start }}"></label>
        <label>{{ Lang::t('backend.form_to') }} <input type="date" name="end" value="{{ $end }}"></label>
        <button type="submit" class="adm-btn">{{ Lang::t('admin.apply') }}</button>
    </form>

    <div class="adm-kpis">
        <div class="adm-kpi"><span>{{ Lang::t('analytics.kpi_views') }}</span><strong>{{ number_format((float) ($totals['total_views'] ?? 0), 0, ',', '.') }}</strong></div>
        <div class="adm-kpi"><span>{{ Lang::t('analytics.kpi_watch') }}</span><strong>{{ number_format((float) ($totals['total_watch_minutes'] ?? 0), 0, ',', '.') }}</strong></div>
        <div class="adm-kpi"><span>{{ Lang::t('analytics.kpi_subs') }}</span><strong>{{ number_format((float) ($totals['total_subs_gained'] ?? 0), 0, ',', '.') }}</strong></div>
        <div class="adm-kpi"><span>{{ Lang::t('analytics.kpi_revenue') }}</span><strong>{{ number_format((float) ($totals['total_revenue'] ?? 0), 2, ',', '.') }} €</strong></div>
        <div class="adm-kpi"><span>{{ Lang::t('analytics.kpi_ad') }}</span><strong>{{ number_format((float) ($totals['total_ad_revenue'] ?? 0), 2, ',', '.') }} €</strong></div>
    </div>

    <table class="adm-table">
        <thead>
            <tr>
                <th scope="col">{{ Lang::t('analytics.th_channel') }}</th>
                <th scope="col">{{ Lang::t('analytics.th_views') }}</th>
                <th scope="col">{{ Lang::t('analytics.th_watch') }}</th>
                <th scope="col">{{ Lang::t('analytics.th_subs') }}</th>
                <th scope="col">{{ Lang::t('analytics.th_revenue') }}</th>
                <th scope="col">{{ Lang::t('analytics.th_ad') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach($perChannel as $row)
            <tr>
                <td>{{ $row['title'] }}</td>
                <td>{{ number_format((float) ($row['views'] ?? 0), 0, ',', '.') }}</td>
                <td>{{ number_format((float) ($row['watch_minutes'] ?? 0), 0, ',', '.') }}</td>
                <td>{{ number_format((float) ($row['subs_gained'] ?? 0), 0, ',', '.') }}</td>
                <td>{{ number_format((float) ($row['revenue'] ?? 0), 2, ',', '.') }}</td>
                <td>{{ number_format((float) ($row['ad_revenue'] ?? 0), 2, ',', '.') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <section class="adm-export-block">
        <h2 class="adm-h2" style="margin-top:2rem;">{{ Lang::t('admin.export_title') }}</h2>
        <p class="adm-note">{{ Lang::t('admin.export_desc') }}</p>
        <form method="post" action="{{ url('/admin/analytics_export.php') }}" class="adm-form" style="max-width:640px;">
            {!! Csrf::hiddenField() !!}
            <input type="hidden" name="start" value="{{ $start }}">
            <input type="hidden" name="end" value="{{ $end }}">
            <label>{{ Lang::t('admin.export_channel') }}
                <select name="channel_id" style="margin-top:0.35rem;display:block;max-width:100%;">
                    <option value="0">{{ Lang::t('admin.export_channel_all') }}</option>
                    @foreach($channelsAll as $ch)
                        <option value="{{ (int) $ch['id'] }}">{{ $ch['title'] }} ({{ $ch['slug'] }})</option>
                    @endforeach
                </select>
            </label>
            <label>{{ Lang::t('admin.export_format') }}
                <select name="format" style="margin-top:0.35rem;display:block;">
                    <option value="excel">{{ Lang::t('admin.export_format_excel') }}</option>
                    <option value="sap">{{ Lang::t('admin.export_format_sap') }}</option>
                    <option value="json">{{ Lang::t('admin.export_format_json') }}</option>
                </select>
            </label>
            <p class="adm-note">{{ Lang::t('admin.export_json_desc') }}</p>
            <p class="adm-note">{{ Lang::t('admin.export_note_sap') }}</p>
            <button type="submit" class="adm-btn">{{ Lang::t('admin.export_submit') }}</button>
        </form>
    </section>

    <p class="adm-note">{{ Lang::t('analytics.note') }}</p>
</main>
</body>
</html>
