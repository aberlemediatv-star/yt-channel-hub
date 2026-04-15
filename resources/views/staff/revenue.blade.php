@php
    use YtHub\Lang;
@endphp
<!DOCTYPE html>
<html lang="{{ Lang::code() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ Lang::t('staff.nav_revenue') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
</head>
<body class="adm-body">
@include('staff.partials.nav', ['mods' => $mods])
<main class="adm-main" role="main">
    <form method="get" action="{{ url('/staff/revenue.php') }}" class="adm-filters">
        <label>{{ Lang::t('backend.form_from') }} <input type="date" name="start" value="{{ $start }}"></label>
        <label>{{ Lang::t('backend.form_to') }} <input type="date" name="end" value="{{ $end }}"></label>
        <button type="submit" class="adm-btn">{{ Lang::t('admin.apply') }}</button>
    </form>

    @if(empty($ids))
        <p class="adm-note">{{ Lang::t('staff.no_channels') }}</p>
    @else
        <table class="adm-table">
            <thead>
                <tr>
                    <th scope="col">{{ Lang::t('staff.revenue_th_channel') }}</th>
                    <th scope="col">{{ Lang::t('staff.revenue_th_views') }}</th>
                    <th scope="col">{{ Lang::t('staff.revenue_th_rev') }}</th>
                    <th scope="col">{{ Lang::t('staff.revenue_th_ad') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($rows as $r)
                <tr>
                    <td>{{ $r['title'] }}</td>
                    <td>{{ number_format((float) ($r['views'] ?? 0), 0, ',', '.') }}</td>
                    <td>{{ number_format((float) ($r['rev'] ?? 0), 2, ',', '.') }} €</td>
                    <td>{{ number_format((float) ($r['adrev'] ?? 0), 2, ',', '.') }} €</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <p class="adm-note">{{ Lang::t('staff.revenue_note') }}</p>
    @endif
</main>
</body>
</html>
