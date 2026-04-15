@php
    use YtHub\Lang;
@endphp
<!DOCTYPE html>
<html lang="{{ Lang::code() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ Lang::t('admin.logs_title') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
    <style>.adm-log-view{font-family:ui-monospace,monospace;font-size:0.78rem;background:#0c0d12;color:#c8ccd8;padding:1rem;border-radius:10px;border:1px solid rgba(255,255,255,0.08);overflow-x:auto;white-space:pre-wrap;word-break:break-word;max-height:70vh;overflow-y:auto}</style>
</head>
<body class="adm-body">
@include('admin.partials.header')
<main class="adm-main" role="main">
    <p class="adm-note">{{ Lang::t('admin.logs_desc') }}</p>
    @if($err !== '')
        <p class="adm-flash adm-flash-err">{{ $err }}</p>
    @elseif(empty($lines))
        <p class="adm-note">{{ Lang::t('admin.logs_empty') }}</p>
    @else
        <pre class="adm-log-view" role="log">@foreach($lines as $ln){{ $ln }}
@endforeach</pre>
    @endif
</main>
</body>
</html>
