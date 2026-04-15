@php
    use YtHub\Csrf;
    use YtHub\Lang;
    $s = $row ?? [];
@endphp
<!DOCTYPE html>
<html lang="{{ Lang::code() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ Lang::t('admin.channels') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
</head>
<body class="adm-body">
@include('admin.partials.header')
<main class="adm-main" role="main">
    <h2 class="adm-h2" style="margin-top:0;">{{ $id ? Lang::t('admin.channel_edit') : Lang::t('admin.channel_new') }}</h2>
    @if($id > 0)
    <p class="adm-note">{!! Lang::tRich('admin.oauth_scopes_note') !!}</p>
    <p class="adm-actions" style="margin-bottom:1.25rem;">
        <a class="adm-btn adm-btn-secondary" href="{{ url('/oauth_start.php?channel_id='.$id) }}">{{ Lang::t('admin.oauth') }}</a>
    </p>
    @endif
    <form method="post" action="{{ url('/admin/channel_save.php') }}" class="adm-form">
        {!! Csrf::hiddenField() !!}
        @if($id > 0)<input type="hidden" name="id" value="{{ $id }}">@endif
        <label>{{ Lang::t('admin.slug') }}
            <input type="text" name="slug" required value="{{ old('slug', $s['slug'] ?? '') }}" pattern="[a-z0-9\-]+" maxlength="64" title="a-z 0-9 -">
        </label>
        <label>{{ Lang::t('admin.title_label') }}
            <input type="text" name="title" required value="{{ old('title', $s['title'] ?? '') }}">
        </label>
        <label>{{ Lang::t('admin.yt_id') }}
            <input type="text" name="youtube_channel_id" required value="{{ old('youtube_channel_id', $s['youtube_channel_id'] ?? '') }}" minlength="10">
        </label>
        <label>{{ Lang::t('admin.sort') }}
            <input type="number" name="sort_order" value="{{ old('sort_order', (int) ($s['sort_order'] ?? 0)) }}">
        </label>
        <label class="adm-form-row"><input type="checkbox" name="is_active" value="1" @if(old('is_active', !$id || !empty($s['is_active']))) checked @endif> {{ Lang::t('admin.active') }}</label>
        <button type="submit" class="adm-btn">{{ Lang::t('admin.save') }}</button>
        <a href="{{ url('/admin/index.php') }}" style="margin-left:1rem;color:var(--adm-accent);">{{ Lang::t('admin.cancel') }}</a>
    </form>
</main>
</body>
</html>
