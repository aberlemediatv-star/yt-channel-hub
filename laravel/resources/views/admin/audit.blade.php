@php
    use YtHub\Lang;
@endphp
<!DOCTYPE html>
<html lang="{{ Lang::code() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ Lang::t('admin.nav.audit') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
</head>
<body class="adm-body">
@include('admin.partials.header')
<main class="adm-main" role="main">
    @include('admin.partials.flash')

    <h1 class="adm-h1">{{ Lang::t('admin.nav.audit') }}</h1>
    <p class="adm-note">{{ Lang::t('admin.audit_note') }}</p>

    <form method="get" class="adm-form" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;margin-bottom:1rem;">
        <div>
            <label for="flt_actor_type" style="font-size:.8rem;">{{ Lang::t('admin.audit_col_actor_type') }}</label>
            <select id="flt_actor_type" name="actor_type">
                <option value="">—</option>
                @foreach($actorTypes as $a)
                    <option value="{{ $a }}" @selected($filters['actor_type'] === $a)>{{ $a }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="flt_action" style="font-size:.8rem;">{{ Lang::t('admin.audit_col_action') }}</label>
            <select id="flt_action" name="action">
                <option value="">—</option>
                @foreach($actions as $a)
                    <option value="{{ $a }}" @selected($filters['action'] === $a)>{{ $a }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="flt_target_type" style="font-size:.8rem;">{{ Lang::t('admin.audit_col_target') }}</label>
            <select id="flt_target_type" name="target_type">
                <option value="">—</option>
                @foreach($targetTypes as $t)
                    <option value="{{ $t }}" @selected($filters['target_type'] === $t)>{{ $t }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <button type="submit" class="adm-btn">{{ Lang::t('admin.audit_btn_filter') }}</button>
            <a class="adm-btn adm-btn-secondary" href="{{ url('/admin/audit') }}">{{ Lang::t('admin.audit_btn_reset') }}</a>
        </div>
    </form>

    <table class="adm-table">
        <thead>
        <tr>
            <th>{{ Lang::t('admin.audit_col_time') }}</th>
            <th>{{ Lang::t('admin.audit_col_actor') }}</th>
            <th>{{ Lang::t('admin.audit_col_action') }}</th>
            <th>{{ Lang::t('admin.audit_col_target') }}</th>
            <th>{{ Lang::t('admin.audit_col_ip') }}</th>
            <th>{{ Lang::t('admin.audit_col_context') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($rows as $r)
            <tr>
                <td style="white-space:nowrap;">{{ $r->created_at }}</td>
                <td><code>{{ $r->actor_type }}</code> {{ $r->actor_label }}</td>
                <td><code>{{ $r->action }}</code></td>
                <td>{{ $r->target_type ? ($r->target_type.'#'.$r->target_id) : '—' }}</td>
                <td>{{ $r->ip }}</td>
                <td style="max-width:420px;word-break:break-word;font-size:.78rem;color:var(--adm-muted);">
                    <code>{{ $r->context }}</code>
                </td>
            </tr>
        @endforeach
        @if($rows->isEmpty())
            <tr><td colspan="6" style="color:var(--adm-muted);">{{ Lang::t('admin.audit_empty') }}</td></tr>
        @endif
        </tbody>
    </table>
</main>
</body>
</html>
