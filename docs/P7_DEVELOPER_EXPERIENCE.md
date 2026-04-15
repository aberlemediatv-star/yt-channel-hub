# P7 — Developer Experience

## Schnellstart (lokal)

1. **Voraussetzungen:** PHP **8.4**, **Composer**, MariaDB/MySQL (oder kompatible Socket-Konfiguration für YtHub), optional **Node** für Laravel-Vite (`laravel/`).
2. **YtHub-Root:** `composer install`
3. **Konfiguration:** `config/config.example.php` → **`config/config.php`** anlegen und DB/Admin-Hash/`security.internal_token` setzen (siehe Kommentare in der Example-Datei).
4. **Laravel:**  
   `cd laravel && composer install && cp .env.example .env && php artisan key:generate`  
   `.env` mit DB, `APP_URL`, `INTERNAL_TOKEN` usw. füllen; **`php artisan migrate`**
5. **Datenbankschema (YtHub-Kern):** Tabellen aus **`database/schema.sql`** in dieselbe Datenbank einspielen (siehe Installationshinweise / Plesk-Doku im Projekt).
6. **Öffentliche Assets:** CSS/JS/SVG unter **`public/assets/`** (Projektroot) müssen für den Webserver unter **`laravel/public/`** erreichbar sein (Kopie oder Symlink — je nach Deploy).

## Tests

```bash
composer install && composer run ci     # Root: PHPUnit + PHPStan
cd laravel && composer install && composer run ci
```

Siehe **[P4_P5_QA_AND_PERF.md](P4_P5_QA_AND_PERF.md)** für Site-Smoke-Tests (DB-abhängig).

## Worker / Cron

- Video- und Job-Verarbeitung: siehe Admin-Hinweis mit **`bin/worker.php`** (Cron minütlich).
- Analytics: **`bin/sync_analytics.php`** nach Bedarf / Cron.
- Exakte Zeilen: in den Übersetzungen `admin.worker_note` / `analytics.note` oder im Admin-UI.

## Typische Fehler

| Symptom | Prüfen |
|--------|--------|
| `SQLSTATE[HY000] [2002] No such file or directory` (MySQL) | `DB_HOST` / **`DB_SOCKET`** in Laravel `.env` bzw. DSN in `config/config.php` (Unix-Socket vs. TCP). |
| CSP blockiert Skript/Styles | Nonce / `unsafe-inline` nur wo nötig; neue Domains in `PublicHttp`. |
| 403 auf token-geschützten URLs | `INTERNAL_TOKEN` in `.env`, Aufruf mit `?token=` oder Header `X-Internal-Token`. |
| OAuth „redirect_uri_mismatch“ (Drive/Dropbox) | `APP_URL` in `laravel/.env` exakt wie öffentliche URL; Redirect-URIs in Google/Dropbox wie in **[MANUAL_CLOUD_IMPORT.md](MANUAL_CLOUD_IMPORT.md)**. |

## Cloud-Import (Staff)

Google Drive, Dropbox und optional S3 für den Video-Upload: Konfiguration, **Redirect-URIs** und Bedienung → **[MANUAL_CLOUD_IMPORT.md](MANUAL_CLOUD_IMPORT.md)**.

## Architektur (Kurz)

Ausführlicher: **[ARCHITECTURE.md](ARCHITECTURE.md)**.

## Release

Checkliste: **[RELEASE_CHECKLIST.md](RELEASE_CHECKLIST.md)**.
