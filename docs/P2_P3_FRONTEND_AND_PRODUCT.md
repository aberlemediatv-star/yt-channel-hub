# P2 & P3 — Frontend, Performance, Publisher

## P2 (umgesetzt)

### Carousel (Startseite)

- **`public/assets/site-carousel.js`**: Fokus auf der Leiste, **Pfeil links/rechts** scrollt um eine Kartenbreite, **Pos1 / Ende** zum Anfang/Ende; **`prefers-reduced-motion`**: in JS nur noch `behavior: 'auto'` statt `smooth` (ergänzend zu CSS).
- **CSS** (`theme.css`): `scroll-behavior: smooth` auf `.carousel`, bei **reduced motion** aus; **`:focus-visible`** für die Leiste; **`.visually-hidden`** für Screenreader-Hinweise und Live-Region.
- **`home.blade.php`**: `aria-describedby` mit Tastaturkurztext, **`aria-live="polite"`** mit Positionsvorlage `{current}` / `{total}`, sinnvolle **`alt`‑Texte** für Thumbnails (Titel, gekürzt), **`defer`**-Skript.
- **`document.visibilitychange`**: im Skript vorbereitet — aktuell kein Autoplay; bei späterem Autoplay dort pausieren.

### Social / Vorschau

- **`HomeController`**: erstes verfügbares **Thumbnail** als **`seoOgImage`**.
- **`seo-head.blade.php`**: **`og:image`** und **`twitter:image`**, wenn gesetzt (YouTube-CDN bereits in CSP `img-src`).

## P3 (teilweise umgesetzt)

### Analytics-Export

- Zusätzliches Format **`json`**: gleiche Tageszeilen wie CSV, als **JSON-Array** (`Content-Type: application/json`, Download `.json`).
- UI: Admin **Analytics** → Export-Format **JSON**; Texte `admin.export_format_json`, `admin.export_json_desc` in allen Locales.

### Noch nicht gebaut (Backlog)

- **Webhooks** bei neuem Video oder Sync-Status (HTTP POST, Signatur, Retries) — braucht Konfiguration, Outbox oder Queue, und klare Hook-Punkte im Worker.
- **Manuelle „Playlist“ / Pinning** pro Kanal — eigenes Datenmodell oder Erweiterung von `sort_order` / Metadaten; ggf. UI in der Kanalverwaltung.
- **Embargo / geplante Veröffentlichung** — Felder in DB + Upload-Flow.
- **Kuratierte Reihenfolge** der Kanäle auf der Startseite ist bereits über **`sort_order`** in der Kanalverwaltung steuerbar (kein extra Feature nötig, wenn das reicht).

Siehe auch **[P1_I18N.md](P1_I18N.md)**, **[P0_OPERATIONS.md](P0_OPERATIONS.md)** und **[P4_P5_QA_AND_PERF.md](P4_P5_QA_AND_PERF.md)**.
