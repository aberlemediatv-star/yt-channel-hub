@php
    use YtHub\Lang;
@endphp
<!DOCTYPE html>
<html lang="{{ Lang::code() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ Lang::t('staff.nav_videos') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
</head>
<body class="adm-body">
@include('staff.partials.nav', ['mods' => $mods])
<main class="adm-main" role="main">
    <p class="adm-note">{{ Lang::t('staff.videos_note') }}</p>

    @if(session('bulk_error'))
        <div class="adm-flash adm-flash-err" role="alert">{{ session('bulk_error') }}</div>
    @endif
    @if(session('bulk_result'))
        @php
            /** @var array{ok: int, fail: int, errors: list<string>} $br */
            $br = session('bulk_result');
        @endphp
        <div class="adm-flash adm-flash-ok" role="status">
            {{ sprintf(Lang::t('staff.bulk_summary_ok'), (int) ($br['ok'] ?? 0), (int) ($br['fail'] ?? 0)) }}
            @if(!empty($br['errors']))
                <ul style="margin:0.5rem 0 0 1.1rem;font-size:0.88rem;">
                    @foreach(array_slice($br['errors'], 0, 10) as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    @if(empty($videos))
        <p class="adm-note">{{ Lang::t('staff.no_videos_cache') }}</p>
    @else
        <form method="post" action="{{ url('/staff/videos.php') }}" id="staff-videos-bulk-form" class="adm-form">
            {!! \YtHub\StaffCsrf::hiddenField() !!}

            <fieldset class="adm-fieldset" style="border:1px solid var(--adm-border);border-radius:var(--adm-radius);padding:1rem;margin-bottom:1.25rem;">
                <legend class="adm-h2" style="padding:0 0.5rem;font-size:1rem;">{{ Lang::t('staff.bulk_section_title') }}</legend>
                <p class="adm-note" style="margin-top:0;">{{ Lang::t('staff.bulk_section_intro') }}</p>

                <div class="adm-form-row" style="gap:0.5rem;margin-bottom:0.75rem;flex-wrap:wrap;">
                    <button type="button" class="adm-btn adm-btn-secondary" id="bulk-sel-all">{{ Lang::t('staff.bulk_select_all') }}</button>
                    <button type="button" class="adm-btn adm-btn-secondary" id="bulk-sel-none">{{ Lang::t('staff.bulk_select_none') }}</button>
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:0.65rem 1rem;">
                    <label style="display:block;">
                        <span class="adm-note" style="display:block;margin-bottom:0.2rem;">{{ Lang::t('staff.bulk_privacy') }}</span>
                        <select name="privacy" style="width:100%;max-width:280px;padding:0.45rem;border-radius:8px;border:1px solid var(--adm-border);background:var(--adm-bg-mid);color:#fff;font:inherit;">
                            <option value="">{{ Lang::t('staff.bulk_privacy_keep') }}</option>
                            <option value="private">{{ Lang::t('staff.privacy_private') }}</option>
                            <option value="unlisted">{{ Lang::t('staff.privacy_unlisted') }}</option>
                            <option value="public">{{ Lang::t('staff.privacy_public') }}</option>
                        </select>
                    </label>
                    <label style="display:block;">
                        <span class="adm-note" style="display:block;margin-bottom:0.2rem;">{{ Lang::t('staff.bulk_category') }}</span>
                        <input type="text" name="category_id" value="" maxlength="8" pattern="[0-9]*" inputmode="numeric" placeholder="{{ Lang::t('staff.bulk_category_ph') }}" style="width:100%;max-width:280px;padding:0.45rem;border-radius:8px;border:1px solid var(--adm-border);background:var(--adm-bg-mid);color:#fff;font:inherit;">
                    </label>
                    <label style="display:block;">
                        <span class="adm-note" style="display:block;margin-bottom:0.2rem;">{{ Lang::t('staff.bulk_tags') }}</span>
                        <select name="tags_mode" style="width:100%;max-width:280px;padding:0.45rem;border-radius:8px;border:1px solid var(--adm-border);background:var(--adm-bg-mid);color:#fff;font:inherit;">
                            <option value="">{{ Lang::t('staff.bulk_tags_keep') }}</option>
                            <option value="clear">{{ Lang::t('staff.bulk_tags_clear') }}</option>
                            <option value="append">{{ Lang::t('staff.bulk_tags_append') }}</option>
                            <option value="replace">{{ Lang::t('staff.bulk_tags_replace') }}</option>
                        </select>
                    </label>
                    <label style="display:block;grid-column:1/-1;">
                        <span class="adm-note" style="display:block;margin-bottom:0.2rem;">{{ Lang::t('staff.bulk_tags_text_label') }}</span>
                        <input type="text" name="tags_text" value="" autocomplete="off" style="width:100%;max-width:640px;padding:0.45rem;border-radius:8px;border:1px solid var(--adm-border);background:var(--adm-bg-mid);color:#fff;font:inherit;">
                    </label>
                    <label style="display:block;">
                        <span class="adm-note" style="display:block;margin-bottom:0.2rem;">{{ Lang::t('staff.bulk_title') }}</span>
                        <select name="title_mode" style="width:100%;max-width:280px;padding:0.45rem;border-radius:8px;border:1px solid var(--adm-border);background:var(--adm-bg-mid);color:#fff;font:inherit;">
                            <option value="">{{ Lang::t('staff.bulk_title_keep') }}</option>
                            <option value="prefix">{{ Lang::t('staff.bulk_title_prefix') }}</option>
                            <option value="suffix">{{ Lang::t('staff.bulk_title_suffix') }}</option>
                            <option value="find_replace">{{ Lang::t('staff.bulk_title_replace') }}</option>
                        </select>
                    </label>
                    <label style="display:block;">
                        <span class="adm-note" style="display:block;margin-bottom:0.2rem;">{{ Lang::t('staff.bulk_lab_title_prefix') }}</span>
                        <input type="text" name="title_prefix" autocomplete="off" style="width:100%;max-width:280px;padding:0.45rem;border-radius:8px;border:1px solid var(--adm-border);background:var(--adm-bg-mid);color:#fff;font:inherit;">
                    </label>
                    <label style="display:block;">
                        <span class="adm-note" style="display:block;margin-bottom:0.2rem;">{{ Lang::t('staff.bulk_lab_title_suffix') }}</span>
                        <input type="text" name="title_suffix" autocomplete="off" style="width:100%;max-width:280px;padding:0.45rem;border-radius:8px;border:1px solid var(--adm-border);background:var(--adm-bg-mid);color:#fff;font:inherit;">
                    </label>
                    <label style="display:block;">
                        <span class="adm-note" style="display:block;margin-bottom:0.2rem;">{{ Lang::t('staff.bulk_lab_title_find') }}</span>
                        <input type="text" name="title_find" autocomplete="off" style="width:100%;max-width:280px;padding:0.45rem;border-radius:8px;border:1px solid var(--adm-border);background:var(--adm-bg-mid);color:#fff;font:inherit;">
                    </label>
                    <label style="display:block;">
                        <span class="adm-note" style="display:block;margin-bottom:0.2rem;">{{ Lang::t('staff.bulk_lab_title_replace') }}</span>
                        <input type="text" name="title_replace" autocomplete="off" style="width:100%;max-width:280px;padding:0.45rem;border-radius:8px;border:1px solid var(--adm-border);background:var(--adm-bg-mid);color:#fff;font:inherit;">
                    </label>
                    <label style="display:block;">
                        <span class="adm-note" style="display:block;margin-bottom:0.2rem;">{{ Lang::t('staff.bulk_desc') }}</span>
                        <select name="desc_mode" style="width:100%;max-width:280px;padding:0.45rem;border-radius:8px;border:1px solid var(--adm-border);background:var(--adm-bg-mid);color:#fff;font:inherit;">
                            <option value="">{{ Lang::t('staff.bulk_desc_keep') }}</option>
                            <option value="prepend">{{ Lang::t('staff.bulk_desc_prepend') }}</option>
                            <option value="append">{{ Lang::t('staff.bulk_desc_append') }}</option>
                        </select>
                    </label>
                    <label style="display:block;grid-column:1/-1;">
                        <span class="adm-note" style="display:block;margin-bottom:0.2rem;">{{ Lang::t('staff.bulk_desc_ph') }}</span>
                        <textarea name="desc_text" rows="3" style="width:100%;max-width:640px;padding:0.45rem;border-radius:8px;border:1px solid var(--adm-border);background:var(--adm-bg-mid);color:#fff;font:inherit;"></textarea>
                    </label>
                    <label style="display:block;">
                        <span class="adm-note" style="display:block;margin-bottom:0.2rem;">{{ Lang::t('staff.bulk_license') }}</span>
                        <select name="license" style="width:100%;max-width:280px;padding:0.45rem;border-radius:8px;border:1px solid var(--adm-border);background:var(--adm-bg-mid);color:#fff;font:inherit;">
                            <option value="">{{ Lang::t('staff.bulk_license_keep') }}</option>
                            <option value="youtube">{{ Lang::t('staff.bulk_license_youtube') }}</option>
                            <option value="creativeCommon">{{ Lang::t('staff.bulk_license_cc') }}</option>
                        </select>
                    </label>
                    <label style="display:block;">
                        <span class="adm-note" style="display:block;margin-bottom:0.2rem;">{{ Lang::t('staff.bulk_embed') }}</span>
                        <select name="embeddable" style="width:100%;max-width:280px;padding:0.45rem;border-radius:8px;border:1px solid var(--adm-border);background:var(--adm-bg-mid);color:#fff;font:inherit;">
                            <option value="">{{ Lang::t('staff.bulk_embed_keep') }}</option>
                            <option value="1">{{ Lang::t('staff.bulk_embed_on') }}</option>
                            <option value="0">{{ Lang::t('staff.bulk_embed_off') }}</option>
                        </select>
                    </label>
                    <label style="display:block;">
                        <span class="adm-note" style="display:block;margin-bottom:0.2rem;">{{ Lang::t('staff.bulk_mfk') }}</span>
                        <select name="made_for_kids" style="width:100%;max-width:280px;padding:0.45rem;border-radius:8px;border:1px solid var(--adm-border);background:var(--adm-bg-mid);color:#fff;font:inherit;">
                            <option value="">{{ Lang::t('staff.bulk_mfk_keep') }}</option>
                            <option value="1">{{ Lang::t('staff.bulk_mfk_yes') }}</option>
                            <option value="0">{{ Lang::t('staff.bulk_mfk_no') }}</option>
                        </select>
                    </label>
                    <label style="display:block;">
                        <span class="adm-note" style="display:block;margin-bottom:0.2rem;">{{ Lang::t('staff.bulk_public_stats') }}</span>
                        <select name="public_stats" style="width:100%;max-width:280px;padding:0.45rem;border-radius:8px;border:1px solid var(--adm-border);background:var(--adm-bg-mid);color:#fff;font:inherit;">
                            <option value="">{{ Lang::t('staff.bulk_public_stats_keep') }}</option>
                            <option value="1">{{ Lang::t('staff.bulk_public_stats_on') }}</option>
                            <option value="0">{{ Lang::t('staff.bulk_public_stats_off') }}</option>
                        </select>
                    </label>
                </div>

                <div class="adm-form-row" style="margin-top:1rem;">
                    <button type="submit" class="adm-btn" onclick="return confirm(@json(Lang::t('staff.bulk_confirm')));">{{ Lang::t('staff.bulk_apply') }}</button>
                </div>
            </fieldset>

            <table class="adm-table">
            <thead>
                <tr>
                    <th scope="col" style="width:2.5rem;"><span class="sr-only">{{ Lang::t('staff.bulk_th_select') }}</span><input type="checkbox" id="bulk-master" aria-label="{{ Lang::t('staff.bulk_select_all') }}"></th>
                    <th scope="col">{{ Lang::t('staff.videos_th_channel') }}</th>
                    <th scope="col">{{ Lang::t('staff.videos_th_title') }}</th>
                    <th scope="col">{{ Lang::t('staff.videos_th_pub') }}</th>
                    <th scope="col">{{ Lang::t('staff.videos_th_yt') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($videos as $v)
                <tr>
                    <td><input type="checkbox" name="video_ids[]" value="{{ $v['video_id'] }}" class="bulk-vid-cb"></td>
                    <td>{{ $v['channel_title'] ?? '' }}</td>
                    <td>{{ $v['title'] ?? '' }}</td>
                    <td>{{ $v['published_at'] ?? '' }}</td>
                    <td><a href="https://www.youtube.com/watch?v={{ $v['video_id'] }}" target="_blank" rel="noopener">{{ Lang::t('staff.videos_open') }}</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </form>
        <style>.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;}</style>
        <script>
        (function () {
            var master = document.getElementById('bulk-master');
            var cbs = document.querySelectorAll('.bulk-vid-cb');
            function allChecked(v) { cbs.forEach(function (c) { c.checked = v; }); }
            if (master) master.addEventListener('change', function () { allChecked(master.checked); });
            var sa = document.getElementById('bulk-sel-all');
            var sn = document.getElementById('bulk-sel-none');
            if (sa) sa.addEventListener('click', function () { allChecked(true); if (master) master.checked = true; });
            if (sn) sn.addEventListener('click', function () { allChecked(false); if (master) master.checked = false; });
        })();
        </script>
    @endif
</main>
</body>
</html>
