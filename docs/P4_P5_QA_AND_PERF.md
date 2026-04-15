# P4 & P5 — Qualitätssicherung, Performance, Skalierung

## P4 — Tests & Verträge

### Automatisierte Tests (PHPUnit)

- **Root** (`composer test`): u. a. `tests/Unit/AnalyticsExportServiceFormatTest.php` — Export-Formate (`json` / `excel` / `sap`), UTF-8-BOM für Excel-CSV.
- **Laravel** (`cd laravel && composer test`): `tests/Feature/SiteHomeTest.php` prüft `/`, `/index.php` und `?lang=en` **sofern die YtHub-Datenbank** laut `config.php` / `.env` **erreichbar** ist; sonst wird der Test **übersprungen** (`markTestSkipped`). So bleibt CI ohne separate MySQL-Instanz grün.

### E2E (Playwright) — optional

1. YtHub-DB und `.env` wie lokal üblich konfigurieren, im Verzeichnis `laravel/` ausführen: `php artisan serve --host=127.0.0.1 --port=8000`.
2. `npm init playwright@latest` in einem eigenen Ordner (z. B. `e2e/`) oder Playwright zum Monorepo hinzufügen.
3. Smoke: `page.goto('http://127.0.0.1:8000/')`, sichtbar `main`, optional Sprach-Umschalter und Carousel.

**GitHub Actions:** eigenen Job nur mit **MySQL-Service** + **Playwright-Browser-Cache** ergänzen, wenn E2E verbindlich werden soll.

### Last / Performance-Messung

- Kurzlast: z. B. **k6** oder `ab`/`hey` gegen `/` in Staging.
- Tiefer: Chrome DevTools Performance, **Lighthouse** (siehe ggf. Web-Perf-Skill).

### „Contract“-Tests zu Google/YouTube

- Echte API-Aufrufe in CI vermeiden (Quota, Flaky). Stattdessen: **Responses als Fixtures** (JSON-Dateien) und Services mit injiziertem HTTP-Client / Mock testen — schrittweise dort, wo HTTP-Schicht gekapselt ist.

---

## P5 — Caching, HTTP-Header, Datenbank

### Öffentliche Startseite (Laravel-Cache)

- Konfiguration: **`laravel/config/ythub.php`**, Umgebungsvariable **`PUBLIC_HOME_CACHE_TTL`** (Sekunden). **`0`** = kein Cache (Standard).
- Bei Wert **> 0**: HTML-Datenblock (`byChannel` + erstes OG-Bild) wird unter einem Schlüssel aus **Sprache** und **Kanal-IDs + sort_order** gecacht. Nach Video-Sync maximal bis TTL veraltet; bei Bedarf TTL niedrig halten (z. B. 60–120) oder nach Deploy `php artisan cache:clear`.

### Statische Assets (Apache)

- **`laravel/public/.htaccess`**: für Dateien mit Endungen **`.css`**, **`.js`**, **`.svg`**, **`.ico`**, **`.woff2`** wird **`Cache-Control: public, max-age=2592000, immutable`** gesetzt (30 Tage), sofern **mod_headers** aktiv ist.

### Nginx (Beispiel)

```nginx
location ~* \.(?:css|js|svg|ico|woff2)$ {
    add_header Cache-Control "public, max-age=2592000, immutable";
    try_files $uri =404;
}
```

Passe Pfade an, wenn statische Assets **nicht** unter `public/` liegen.

### Datenbank-Indizes

- **`database/schema.sql`**: bereits **`idx_channel_published`** auf `videos (channel_id, published_at)`, **`uq_channel_day`** / impliziter Index auf `analytics_daily (channel_id, report_date)`, **`idx_report_date`**, **`idx_status_created`** auf `jobs`.
- Bei langsamen Reports: mit **`EXPLAIN`** prüfen; ggf. zusätzliche Indizes nur nach Messung (zusätzliche Indizes kosten Schreibperformance).

---

Siehe auch **[P2_P3_FRONTEND_AND_PRODUCT.md](P2_P3_FRONTEND_AND_PRODUCT.md)** und **[P0_OPERATIONS.md](P0_OPERATIONS.md)**.
