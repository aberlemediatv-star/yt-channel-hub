@php
    use YtHub\Lang;
@endphp
<div class="lang-switcher lang-switcher--picker lang-switcher--compact" role="navigation" aria-label="{{ Lang::t('public.lang_switcher_aria') }}">
    <span class="lang-switcher__icon" aria-hidden="true">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
        </svg>
    </span>
    <select
        class="lang-switcher__select"
        aria-label="{{ Lang::t('public.lang_switcher_aria') }}"
        data-lang-switcher
        onchange="if (this.value) { window.location.href = this.value; }"
    >
        @foreach (Lang::SUPPORTED as $lc)
            <option value="{{ Lang::urlWithLang($lc) }}" @selected(Lang::code() === $lc)>
                {{ Lang::NATIVE_LABELS[$lc] ?? strtoupper($lc) }}
            </option>
        @endforeach
    </select>
</div>
