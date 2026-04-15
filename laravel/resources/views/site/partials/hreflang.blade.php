@php
    use YtHub\Lang;
    $page = $hreflangPage ?? basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
@endphp
@foreach (Lang::SUPPORTED as $lc)
<link rel="alternate" hreflang="{{ $lc }}" href="{{ Lang::absoluteUrl($page . '?' . http_build_query(['lang' => $lc])) }}">
@endforeach
@php
    $defaultHref = Lang::absoluteUrl($page . '?' . http_build_query(['lang' => 'de']));
@endphp
<link rel="alternate" hreflang="x-default" href="{{ $defaultHref }}">
