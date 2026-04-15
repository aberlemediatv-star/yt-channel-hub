@php
    use YtHub\Lang;
    use YtHub\StaffCsrf;
    $cid = (int) ($channelId ?? 0);
    $src = old('video_source', 'local');
@endphp
<!DOCTYPE html>
<html lang="{{ Lang::code() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ Lang::t('staff.upload') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
</head>
<body class="adm-body">
@include('staff.partials.nav', ['mods' => $mods, 'staffPageTitle' => $channelTitle])
<main class="adm-main" role="main">
    <p class="adm-note">{{ sprintf(Lang::t('staff.upload_note'), $maxUpload) }}</p>
    @if($error !== '')<div class="adm-flash adm-flash-err">{{ $error }}</div>@endif
    @if($ok !== '')<div class="adm-flash adm-flash-ok">{{ $ok }}</div>@endif
    @if(!empty($cloudError))<div class="adm-flash adm-flash-err">{{ $cloudError }}</div>@endif
    @if(!empty($cloudOk))<div class="adm-flash adm-flash-ok">{{ $cloudOk }}</div>@endif

    <form method="post" action="{{ url('/staff/upload.php?channel_id='.$cid) }}" enctype="multipart/form-data" class="adm-form" id="staff-upload-form">
        {!! StaffCsrf::hiddenField() !!}
        <input type="hidden" name="channel_id" value="{{ $cid }}">

        <fieldset class="adm-fieldset" style="border:1px solid var(--adm-border);border-radius:var(--adm-radius);padding:1rem;margin-bottom:1.25rem;">
            <legend class="adm-h2" style="padding:0 0.5rem;font-size:1rem;">{{ Lang::t('staff.cloud_section') }}</legend>
            <div class="adm-form-row" style="flex-direction:column;align-items:flex-start;gap:0.45rem;">
                <label><input type="radio" name="video_source" value="local" @checked($src === 'local')> {{ Lang::t('staff.cloud_source_local') }}</label>
                @if($gdriveOAuthOk)
                <label><input type="radio" name="video_source" value="gdrive" @checked($src === 'gdrive')> {{ Lang::t('staff.cloud_source_gdrive') }}</label>
                @endif
                @if($dropboxOAuthOk)
                <label><input type="radio" name="video_source" value="dropbox" @checked($src === 'dropbox')> {{ Lang::t('staff.cloud_source_dropbox') }}</label>
                @endif
                @if($s3Ready)
                <label><input type="radio" name="video_source" value="s3" @checked($src === 's3')> {{ Lang::t('staff.cloud_source_s3') }}</label>
                @endif
            </div>

            @if($gdriveOAuthOk)
            <div class="cloud-panel" data-panel="gdrive" style="margin-top:0.75rem;padding-top:0.75rem;border-top:1px solid var(--adm-border);">
                <p class="adm-note" style="margin:0 0 0.5rem;">
                    @if($gdriveConnected)
                        <a href="{{ url('/staff/oauth/gdrive/disconnect?channel_id='.$cid) }}">{{ Lang::t('staff.cloud_disconnect_gdrive') }}</a>
                    @else
                        <a href="{{ url('/staff/oauth/gdrive/start?channel_id='.$cid) }}">{{ Lang::t('staff.cloud_connect_gdrive') }}</a>
                    @endif
                </p>
                @if($gdriveConnected)
                <div id="gdrive-browse-ui" style="margin-top:0.5rem;">
                    <button type="button" id="gdrive-up" class="adm-btn adm-btn-secondary" style="display:none;margin-right:.5rem;">{{ Lang::t('staff.cloud_up') }}</button>
                    <span class="adm-note" id="gdrive-here" style="display:inline;"></span>
                    <div id="gdrive-folders" style="margin:.5rem 0;display:flex;flex-wrap:wrap;gap:.35rem;"></div>
                    <p class="adm-note" style="margin:0 0 0.35rem;font-size:0.85rem;">{{ Lang::t('staff.cloud_folders_hint') }}</p>
                </div>
                <button type="button" class="adm-btn adm-btn-secondary" data-cloud-refresh="gdrive">{{ Lang::t('staff.cloud_refresh_list') }}</button>
                <label style="margin-top:0.65rem;display:block;">{{ Lang::t('staff.cloud_pick_gdrive') }}
                    <select name="gdrive_file_id" id="gdrive_file_id" style="margin-top:0.35rem;max-width:100%;width:min(520px,100%);">
                        <option value="">—</option>
                    </select>
                </label>
                @endif
            </div>
            @endif

            @if($dropboxOAuthOk)
            <div class="cloud-panel" data-panel="dropbox" style="margin-top:0.75rem;padding-top:0.75rem;border-top:1px solid var(--adm-border);">
                <p class="adm-note" style="margin:0 0 0.5rem;">
                    @if($dropboxConnected)
                        <a href="{{ url('/staff/oauth/dropbox/disconnect?channel_id='.$cid) }}">{{ Lang::t('staff.cloud_disconnect_dropbox') }}</a>
                    @else
                        <a href="{{ url('/staff/oauth/dropbox/start?channel_id='.$cid) }}">{{ Lang::t('staff.cloud_connect_dropbox') }}</a>
                    @endif
                </p>
                @if($dropboxConnected)
                <div id="dropbox-browse-ui" style="margin-top:0.5rem;">
                    <button type="button" id="dropbox-up" class="adm-btn adm-btn-secondary" style="display:none;margin-right:.5rem;">{{ Lang::t('staff.cloud_up') }}</button>
                    <span class="adm-note" id="dropbox-here" style="display:inline;"></span>
                    <div id="dropbox-folders" style="margin:.5rem 0;display:flex;flex-wrap:wrap;gap:.35rem;"></div>
                    <p class="adm-note" style="margin:0 0 0.35rem;font-size:0.85rem;">{{ Lang::t('staff.cloud_folders_hint') }}</p>
                </div>
                <button type="button" class="adm-btn adm-btn-secondary" data-cloud-refresh="dropbox">{{ Lang::t('staff.cloud_refresh_list') }}</button>
                <label style="margin-top:0.65rem;display:block;">{{ Lang::t('staff.cloud_pick_dropbox') }}
                    <select name="dropbox_path" id="dropbox_path" style="margin-top:0.35rem;max-width:100%;width:min(520px,100%);">
                        <option value="">—</option>
                    </select>
                </label>
                @endif
            </div>
            @endif

            @if($s3Ready)
            <div class="cloud-panel" data-panel="s3" style="margin-top:0.75rem;padding-top:0.75rem;border-top:1px solid var(--adm-border);">
                <button type="button" class="adm-btn adm-btn-secondary" data-cloud-refresh="s3">{{ Lang::t('staff.cloud_refresh_list') }}</button>
                <label style="margin-top:0.65rem;display:block;">{{ Lang::t('staff.cloud_pick_s3') }}
                    <select name="s3_key" id="s3_key" style="margin-top:0.35rem;max-width:100%;width:min(520px,100%);">
                        <option value="">—</option>
                    </select>
                </label>
            </div>
            @endif
        </fieldset>

        <label for="title">{{ Lang::t('staff.label_title') }}</label>
        <input type="text" id="title" name="title" required maxlength="500" value="{{ $post['title'] ?? '' }}">

        <label for="description">{{ Lang::t('staff.label_description') }}</label>
        <textarea id="description" name="description" rows="5" style="width:100%;max-width:520px;padding:0.5rem;border-radius:8px;border:1px solid #333;background:#111;color:#fff;">{{ $post['description'] ?? '' }}</textarea>

        <label for="privacy">{{ Lang::t('staff.label_privacy') }}</label>
        <select id="privacy" name="privacy" style="margin-top:0.35rem;font:inherit;padding:0.45rem;">
            <option value="private" @if(($post['privacy'] ?? 'private') === 'private') selected @endif>{{ Lang::t('staff.privacy_private') }}</option>
            <option value="unlisted" @if(($post['privacy'] ?? '') === 'unlisted') selected @endif>{{ Lang::t('staff.privacy_unlisted') }}</option>
            <option value="public" @if(($post['privacy'] ?? '') === 'public') selected @endif>{{ Lang::t('staff.privacy_public') }}</option>
        </select>

        <div class="adm-form-row">
            <label><input type="checkbox" name="notify_subscribers" value="1" @if(!empty($post['notify_subscribers'])) checked @endif> {{ Lang::t('staff.notify_subscribers') }}</label>
        </div>

        @include('site.partials.youtube-upload-metadata', ['idPrefix' => $idPrefix ?? 'st'])

        <label for="video">{{ Lang::t('staff.label_video_file') }}</label>
        <input type="file" id="video" name="video" accept="video/*">

        <div class="adm-form-row">
            <button type="submit" class="adm-btn">{{ Lang::t('staff.upload_submit') }}</button>
        </div>
    </form>
</main>
<script src="{{ url('/staff/upload-loc.js') }}" defer></script>
<script>
(function () {
    const channelId = {{ (int) $cid }};
    const listUrl = @json(url('/staff/cloud/files'));
    const oldGdrive = @json(old('gdrive_file_id', ''));
    const oldDropbox = @json(old('dropbox_path', ''));
    const oldS3 = @json(old('s3_key', ''));
    const lblHere = @json(Lang::t('staff.cloud_here'));

    let gdriveFolder = 'root';
    let dropboxPath = '';
    let gdriveSeq = 0;
    let dropboxSeq = 0;

    function currentSource() {
        const r = document.querySelector('input[name="video_source"]:checked');
        return r ? r.value : 'local';
    }

    function syncVideoRequired() {
        const v = document.getElementById('video');
        if (v) v.required = (currentSource() === 'local');
    }

    document.querySelectorAll('input[name="video_source"]').forEach(function (el) {
        el.addEventListener('change', syncVideoRequired);
    });
    syncVideoRequired();

    function fillSelect(sel, items, valueKey, labelFn, preserve) {
        if (!sel) return;
        const keep = preserve || '';
        sel.innerHTML = '';
        const opt0 = document.createElement('option');
        opt0.value = '';
        opt0.textContent = '—';
        sel.appendChild(opt0);
        items.forEach(function (it) {
            const o = document.createElement('option');
            o.value = String(it[valueKey]);
            o.textContent = labelFn(it);
            if (keep && o.value === keep) o.selected = true;
            sel.appendChild(o);
        });
    }

    async function refreshS3() {
        const sel = document.getElementById('s3_key');
        const res = await fetch(listUrl + '?channel_id=' + encodeURIComponent(channelId) + '&provider=s3', { credentials: 'same-origin' });
        const j = await res.json().catch(function () { return {}; });
        if (!res.ok) {
            alert(j.error || ('HTTP ' + res.status));
            return;
        }
        fillSelect(sel, j.files || [], 'key', function (f) { return f.key + (f.size != null ? ' (' + f.size + ' B)' : ''); }, oldS3);
    }

    async function refreshGdrive() {
        const mySeq = ++gdriveSeq;
        const sel = document.getElementById('gdrive_file_id');
        const res = await fetch(listUrl + '?channel_id=' + encodeURIComponent(channelId) + '&provider=gdrive&folder=' + encodeURIComponent(gdriveFolder), { credentials: 'same-origin' });
        if (mySeq !== gdriveSeq) return;
        const j = await res.json().catch(function () { return {}; });
        if (mySeq !== gdriveSeq) return;
        if (!res.ok) {
            alert(j.error || ('HTTP ' + res.status));
            return;
        }
        const up = document.getElementById('gdrive-up');
        const here = document.getElementById('gdrive-here');
        const folders = document.getElementById('gdrive-folders');
        if (up) {
            if (j.parent_id !== null && j.parent_id !== undefined) {
                up.style.display = 'inline-block';
                const target = String(j.parent_id);
                up.onclick = function () {
                    gdriveFolder = target;
                    refreshGdrive();
                };
            } else {
                up.style.display = 'none';
                up.onclick = null;
            }
        }
        if (here) {
            here.textContent = lblHere + ': ' + (j.folder_id === 'root' ? 'root' : String(j.folder_id || ''));
        }
        if (folders) {
            folders.innerHTML = '';
            (j.entries || []).filter(function (e) { return e.kind === 'folder'; }).forEach(function (e) {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'adm-btn adm-btn-secondary';
                b.style.fontSize = '0.82rem';
                b.textContent = '📁 ' + (e.name || e.id);
                b.addEventListener('click', function () {
                    gdriveFolder = String(e.id);
                    refreshGdrive();
                });
                folders.appendChild(b);
            });
        }
        const videos = (j.entries || []).filter(function (e) { return e.kind === 'file'; });
        fillSelect(sel, videos, 'id', function (f) { return f.name + (f.size ? ' (' + f.size + ' B)' : ''); }, oldGdrive);
    }

    async function refreshDropbox() {
        const mySeq = ++dropboxSeq;
        const sel = document.getElementById('dropbox_path');
        const res = await fetch(listUrl + '?channel_id=' + encodeURIComponent(channelId) + '&provider=dropbox&path=' + encodeURIComponent(dropboxPath), { credentials: 'same-origin' });
        if (mySeq !== dropboxSeq) return;
        const j = await res.json().catch(function () { return {}; });
        if (mySeq !== dropboxSeq) return;
        if (!res.ok) {
            alert(j.error || ('HTTP ' + res.status));
            return;
        }
        const up = document.getElementById('dropbox-up');
        const here = document.getElementById('dropbox-here');
        const folders = document.getElementById('dropbox-folders');
        if (up) {
            if (j.can_up) {
                up.style.display = 'inline-block';
                const target = j.parent_path === null || j.parent_path === undefined ? '' : String(j.parent_path);
                up.onclick = function () {
                    dropboxPath = target;
                    refreshDropbox();
                };
            } else {
                up.style.display = 'none';
                up.onclick = null;
            }
        }
        if (here) {
            here.textContent = lblHere + ': ' + (j.path === '' || j.path === undefined ? '/' : String(j.path));
        }
        if (folders) {
            folders.innerHTML = '';
            (j.entries || []).filter(function (e) { return e.kind === 'folder'; }).forEach(function (e) {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'adm-btn adm-btn-secondary';
                b.style.fontSize = '0.82rem';
                b.textContent = '📁 ' + (e.name || '');
                b.addEventListener('click', function () {
                    dropboxPath = String(e.path || '');
                    refreshDropbox();
                });
                folders.appendChild(b);
            });
        }
        const videos = (j.entries || []).filter(function (e) { return e.kind === 'file'; });
        fillSelect(sel, videos, 'path', function (f) { return f.name + (f.size != null ? ' (' + f.size + ' B)' : ''); }, oldDropbox);
    }

    document.querySelectorAll('[data-cloud-refresh]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const p = btn.getAttribute('data-cloud-refresh');
            if (p === 'gdrive') refreshGdrive();
            else if (p === 'dropbox') refreshDropbox();
            else if (p === 's3') refreshS3();
        });
    });

    if (oldGdrive) refreshGdrive();
    if (oldDropbox) refreshDropbox();
    if (oldS3) refreshS3();
})();
</script>
</body>
</html>
