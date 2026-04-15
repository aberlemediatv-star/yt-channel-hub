/**
 * Backend upload: Drag & Drop, Vorschau, XHR-Fortschritt
 */
(function () {
    'use strict';

    var root = document.getElementById('bu-root');
    if (!root) return;

    var cfg = window.BU_I18N || {};
    var jsonEl = document.getElementById('bu-i18n-json');
    if (jsonEl && jsonEl.textContent) {
        try {
            cfg = JSON.parse(jsonEl.textContent);
        } catch (e) {
            /* ignore */
        }
    }
    var form = document.getElementById('bu-form');
    var videoInput = document.getElementById('bu-video');
    var thumbInput = document.getElementById('bu-thumb');
    var videoDrop = document.getElementById('bu-drop-video');
    var thumbDrop = document.getElementById('bu-drop-thumb');
    var videoMeta = document.getElementById('bu-video-meta');
    var thumbMeta = document.getElementById('bu-thumb-meta');
    var thumbPreview = document.getElementById('bu-thumb-preview');
    var progressWrap = document.getElementById('bu-progress');
    var progressBar = document.getElementById('bu-progress-bar');
    var progressLabel = document.getElementById('bu-progress-label');
    var btnClear = document.getElementById('bu-clear');
    var btnSubmit = document.getElementById('bu-submit');
    var locAdd = document.getElementById('bu-loc-add');
    var locRows = document.getElementById('bu-loc-rows');

    function fmtSize(n) {
        if (n < 1024) return n + ' B';
        if (n < 1024 * 1024) return (n / 1024).toFixed(1) + ' KB';
        return (n / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function bindDrop(zone, input) {
        if (!zone || !input) return;
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function (ev) {
            zone.addEventListener(ev, function (e) {
                e.preventDefault();
                e.stopPropagation();
            });
        });
        zone.addEventListener('dragover', function () {
            zone.classList.add('is-dragover');
        });
        zone.addEventListener('dragleave', function () {
            zone.classList.remove('is-dragover');
        });
        zone.addEventListener('drop', function (e) {
            zone.classList.remove('is-dragover');
            var files = e.dataTransfer && e.dataTransfer.files;
            if (!files || !files.length) return;
            input.files = files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
        zone.addEventListener('click', function () {
            input.click();
        });
    }

    bindDrop(videoDrop, videoInput);
    bindDrop(thumbDrop, thumbInput);

    if (videoInput && videoMeta) {
        videoInput.addEventListener('change', function () {
            var f = videoInput.files && videoInput.files[0];
            videoMeta.textContent = f
                ? f.name + ' · ' + fmtSize(f.size)
                : '';
        });
    }

    if (thumbInput && thumbMeta && thumbPreview) {
        thumbInput.addEventListener('change', function () {
            var f = thumbInput.files && thumbInput.files[0];
            thumbMeta.textContent = f
                ? f.name + ' · ' + fmtSize(f.size)
                : '';
            thumbPreview.classList.remove('is-visible');
            thumbPreview.removeAttribute('src');
            if (f && /^image\/(jpeg|png)/.test(f.type)) {
                var url = URL.createObjectURL(f);
                thumbPreview.onload = function () {
                    URL.revokeObjectURL(url);
                };
                thumbPreview.src = url;
                thumbPreview.classList.add('is-visible');
            }
        });
    }

    function cloneLocRow() {
        if (!locRows) return;
        var first = locRows.querySelector('.yt-loc-row');
        if (!first) return;
        var clone = first.cloneNode(true);
        clone.querySelectorAll('input, textarea').forEach(function (el) {
            el.value = '';
        });
        locRows.appendChild(clone);
    }

    if (locAdd) {
        locAdd.addEventListener('click', cloneLocRow);
    }

    if (btnClear) {
        btnClear.addEventListener('click', function () {
            if (videoInput) videoInput.value = '';
            if (thumbInput) thumbInput.value = '';
            if (videoMeta) videoMeta.textContent = '';
            if (thumbMeta) thumbMeta.textContent = '';
            if (thumbPreview) {
                thumbPreview.classList.remove('is-visible');
                thumbPreview.removeAttribute('src');
            }
            var cap = document.getElementById('bu-caption');
            if (cap) cap.value = '';
            if (locRows) {
                var rows = locRows.querySelectorAll('.yt-loc-row');
                for (var i = 1; i < rows.length; i++) {
                    rows[i].remove();
                }
                var r0 = locRows.querySelector('.yt-loc-row');
                if (r0) {
                    r0.querySelectorAll('input, textarea').forEach(function (el) {
                        el.value = '';
                    });
                }
            }
        });
    }

    if (form && btnSubmit) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var fd = new FormData(form);
            fd.set('ajax', '1');
            btnSubmit.disabled = true;
            if (progressWrap)
                progressWrap.classList.add('is-visible');
            if (progressBar) progressBar.style.width = '0%';
            if (progressLabel)
                progressLabel.textContent =
                    cfg.msgProgress || '…';

            var xhr = new XMLHttpRequest();
            xhr.open('POST', form.action || window.location.pathname, true);
            xhr.upload.onprogress = function (ev) {
                if (!ev.lengthComputable || !progressBar) return;
                var pct = Math.round((ev.loaded / ev.total) * 100);
                progressBar.style.width = pct + '%';
                if (progressLabel && pct >= 100)
                    progressLabel.textContent =
                        cfg.msgYoutube || '…';
            };
            xhr.onload = function () {
                btnSubmit.disabled = false;
                try {
                    var j = JSON.parse(xhr.responseText || '{}');
                    var box = document.getElementById('bu-flash');
                    if (box) {
                        box.className =
                            'bu-flash ' +
                            (j.ok ? 'bu-flash-ok' : 'bu-flash-err');
                        box.textContent = j.message || xhr.statusText;
                        box.style.display = 'block';
                    }
                    if (j.csrf) {
                        var csrfEl = form.querySelector('[name="backend_csrf"]');
                        if (csrfEl) csrfEl.value = j.csrf;
                    }
                    if (j.ok && form) {
                        if (videoInput) videoInput.value = '';
                        if (thumbInput) thumbInput.value = '';
                        if (videoMeta) videoMeta.textContent = '';
                        if (thumbMeta) thumbMeta.textContent = '';
                        if (thumbPreview) {
                            thumbPreview.classList.remove('is-visible');
                            thumbPreview.removeAttribute('src');
                        }
                        var cap2 = document.getElementById('bu-caption');
                        if (cap2) cap2.value = '';
                        var lr = document.getElementById('bu-loc-rows');
                        if (lr) {
                            var rows2 = lr.querySelectorAll('.yt-loc-row');
                            for (var k = 1; k < rows2.length; k++) {
                                rows2[k].remove();
                            }
                            var r1 = lr.querySelector('.yt-loc-row');
                            if (r1) {
                                r1.querySelectorAll('input, textarea').forEach(function (el) {
                                    el.value = '';
                                });
                            }
                        }
                    }
                } catch (err) {
                    var box2 = document.getElementById('bu-flash');
                    if (box2) {
                        box2.className = 'bu-flash bu-flash-err';
                        box2.textContent =
                            xhr.status >= 400
                                ? 'HTTP ' + xhr.status
                                : 'Parse error';
                        box2.style.display = 'block';
                    }
                }
                if (progressWrap)
                    progressWrap.classList.remove('is-visible');
                if (progressBar) progressBar.style.width = '0%';
            };
            xhr.onerror = function () {
                btnSubmit.disabled = false;
                if (progressWrap)
                    progressWrap.classList.remove('is-visible');
            };
            xhr.send(fd);
        });
    }
})();
