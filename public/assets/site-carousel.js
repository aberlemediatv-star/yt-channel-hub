/**
 * Horizontale Carousels: Tastatur (Pfeile, Pos1, Ende), scrollend → aria-live.
 * Respektiert prefers-reduced-motion (nur auto statt smooth in JS).
 */
(function () {
    "use strict";

    function prefersReducedMotion() {
        return window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    }

    function scrollBehavior() {
        return prefersReducedMotion() ? "auto" : "smooth";
    }

    function cardStepWidth(root) {
        const card = root.querySelector(".card");
        if (!card) {
            return 0;
        }
        const gapRaw = getComputedStyle(root).gap || getComputedStyle(root).columnGap;
        const gap = parseFloat(gapRaw) || 16;
        return card.getBoundingClientRect().width + gap;
    }

    function announce(root, current, total) {
        const live = root.querySelector(".carousel-live");
        const tmpl = root.getAttribute("data-live-tmpl") || "";
        if (!live || !tmpl || total < 1) {
            return;
        }
        const text = tmpl.replace("{current}", String(current)).replace("{total}", String(total));
        live.textContent = text;
    }

    function updateLiveFromScroll(root) {
        const cards = root.querySelectorAll(".card");
        const n = cards.length;
        if (n === 0) {
            return;
        }
        const mid = root.scrollLeft + root.clientWidth * 0.35;
        let idx = 1;
        cards.forEach(function (c, i) {
            const left = c.offsetLeft;
            const right = left + c.offsetWidth;
            if (mid >= left && mid < right) {
                idx = i + 1;
            }
        });
        announce(root, idx, n);
    }

    function onKeydown(e) {
        const root = e.target.closest(".carousel");
        if (!root) {
            return;
        }
        const k = e.key;
        if (k !== "ArrowLeft" && k !== "ArrowRight" && k !== "Home" && k !== "End") {
            return;
        }
        if (e.target !== root && !root.contains(e.target)) {
            return;
        }
        const step = cardStepWidth(root);
        if (step <= 0) {
            return;
        }
        const beh = scrollBehavior();
        if (k === "ArrowLeft") {
            e.preventDefault();
            root.scrollBy({ left: -step, behavior: beh });
        } else if (k === "ArrowRight") {
            e.preventDefault();
            root.scrollBy({ left: step, behavior: beh });
        } else if (k === "Home") {
            e.preventDefault();
            root.scrollTo({ left: 0, behavior: beh });
        } else if (k === "End") {
            e.preventDefault();
            root.scrollTo({ left: root.scrollWidth, behavior: beh });
        }
    }

    function wire(root) {
        if (root.getAttribute("data-carousel-wired") === "1") {
            return;
        }
        root.setAttribute("data-carousel-wired", "1");
        root.setAttribute("tabindex", "0");
        root.addEventListener("scroll", function () {
            window.clearTimeout(root._carouselScrollT);
            root._carouselScrollT = window.setTimeout(function () {
                updateLiveFromScroll(root);
            }, 120);
        });
        if ("onscrollend" in window) {
            root.addEventListener("scrollend", function () {
                updateLiveFromScroll(root);
            });
        }
        root.addEventListener("keydown", onKeydown);
    }

    function init() {
        document.querySelectorAll(".carousel").forEach(wire);
        document.querySelectorAll(".carousel").forEach(updateLiveFromScroll);
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    document.addEventListener("visibilitychange", function () {
        /* Platzhalter: aktuell kein Autoplay; bei späterem Autoplay hier pausieren. */
    });
})();
