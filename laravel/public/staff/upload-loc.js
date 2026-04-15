/**
 * Zusätzliche Zeilen für Übersetzungs-Felder (staff/upload.php)
 */
(function () {
    'use strict';
    var btn = document.getElementById('st-loc-add');
    var rows = document.getElementById('st-loc-rows');
    if (!btn || !rows) return;
    btn.addEventListener('click', function () {
        var first = rows.querySelector('.yt-loc-row');
        if (!first) return;
        var clone = first.cloneNode(true);
        clone.querySelectorAll('input, textarea').forEach(function (el) {
            el.value = '';
        });
        rows.appendChild(clone);
    });
})();
