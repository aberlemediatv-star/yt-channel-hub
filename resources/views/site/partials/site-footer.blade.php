@php
    use YtHub\Lang;
    Lang::init();
    /** @var array<string, mixed> $legal */
    $legal = require config_path('legal.php');
@endphp
<footer class="site-footer" role="contentinfo">
    <div class="site-footer-inner">
        <p class="site-footer-brand">
            <a href="{{ $legal['company_site'] }}" target="_blank" rel="noopener">
                <img src="{{ asset('assets/imh-group-logo.svg') }}" alt="{{ $legal['company'] }}" style="height:18px;width:auto;vertical-align:middle;opacity:.85;">
            </a>
        </p>
        <nav class="site-footer-nav" aria-label="{{ Lang::t('footer.legal_nav') }}">
            <a href="{{ Lang::relUrl('impressum.php') }}">{{ Lang::t('footer.imprint') }}</a>
            <a href="{{ Lang::relUrl('datenschutz.php') }}">{{ Lang::t('footer.privacy') }}</a>
            <a href="{{ $legal['company_site'] }}" target="_blank" rel="noopener">{{ $legal['company'] }}</a>
        </nav>
        <p class="site-footer-note">&copy; {{ date('Y') }} {{ $legal['company'] }} — {{ Lang::t('footer.note') }}</p>
    </div>
</footer>
