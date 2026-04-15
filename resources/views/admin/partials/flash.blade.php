@php
    use YtHub\AdminFlash;
    $flash = AdminFlash::pull();
@endphp
@if($flash !== null)
@php
    $class = $flash['type'] === 'ok' ? 'adm-flash adm-flash-ok' : 'adm-flash adm-flash-err';
@endphp
<div class="{{ $class }}" role="status">{{ $flash['message'] }}</div>
@endif
