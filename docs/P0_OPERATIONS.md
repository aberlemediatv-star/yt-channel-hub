# P0 — Betrieb, Sicherheit, Qualität

Dieses Dokument fasst die umgesetzten **P0**-Bausteine zusammen und ergänzt Checklisten für Backups und Geheimnisse.

Internationalisierung und Token-Admin: siehe **[P1_I18N.md](P1_I18N.md)**. Carousel, OG-Bild, JSON-Export: **[P2_P3_FRONTEND_AND_PRODUCT.md](P2_P3_FRONTEND_AND_PRODUCT.md)**. Tests, Cache, Header: **[P4_P5_QA_AND_PERF.md](P4_P5_QA_AND_PERF.md)**. Security/Compliance: **[P6_SECURITY_AND_COMPLIANCE.md](P6_SECURITY_AND_COMPLIANCE.md)**. README, Architektur, Releases: **[P7_DEVELOPER_EXPERIENCE.md](P7_DEVELOPER_EXPERIENCE.md)**.

## Kontinuierliche Qualität (CI)

Bei **GitHub** läuft [`.github/workflows/ci.yml`](../.github/workflows/ci.yml) auf `push`/`pull_request`:

1. **YtHub-Root**: `composer install` → `composer run stan` (PHPStan auf `src/`, ohne große `messages_*.php`) → `composer run test` (PHPUnit).
2. **Laravel**: `composer install` → `composer run lint` (Pint `--test`) → `composer run test` (`php artisan test`).

Lokal:

```bash
composer install && composer run ci
cd laravel && composer install && composer run ci
```

## Login-Härtung

- **Rate limiting** (pro IP): Admin- und Staff-**Login**-Route — bis zu **90 GET/HEAD** pro Minute (Seite laden), **8 POST** pro Minute (Passwort-Versuche). Überschreitung → HTTP **429**.
- **Logging** bei fehlgeschlagenem Passwort: Laravel-Log-Einträge `admin_login_failed` und `staff_login_failed` mit **IP** und gekürztem **User-Agent** (kein Klartext-Passwort, kein Benutzername beim Staff-Login).

Logs: typischerweise `laravel/storage/logs/laravel.log` bzw. nach eurer Log-Konfiguration.

## Backups

Mindestens sichern:

- **Datenbank** (SQLite-Datei oder Dump der produktiven DB).
- **`storage/`** inkl. Uploads/Caches, falls genutzt.
- **Konfiguration**: `.env` **nicht** im Klartext ins Backup-Repo; getrennt im Secret-Store / Passwortmanager dokumentieren.

### Restore-Übung (mindestens jährlich)

1. Testumgebung mit gleicher PHP-Version wie Produktion.
2. Frisches Checkout, `composer install` (Root + `laravel/`), `laravel` `.env` aus Vorlage.
3. DB aus Backup einspielen, Berechtigungen prüfen.
4. `php artisan migrate --force` nur falls Schema neuer ist als das Backup (sonst Schema aus Backup führend).
5. Smoke-Test: `/`, Admin-Login (ohne echtes Produktionspasswort in Doku speichern), ein geschützter Endpunkt.

Dokumentiert Abweichungen und Dauer der Übung für das Team.

## Geheimnisse & Rotation

- **YouTube / Google**: API-Keys, OAuth-Client-Secret, Refresh-Tokens — Rotationsplan (z. B. bei Mitarbeiterwechsel oder Leak).
- **ADMIN_PASSWORD_HASH**, **Staff-Passwörter**, **INTERNAL_TOKEN**, **APP_KEY** / Verschlüsselungsschlüssel: nur über sichere Kanäle verteilen; nach Rotation alte Sessions invalidieren (Neu-Login).

## Monitoring (manuell / später automatisieren)

- Worker/Cron-Exit-Codes und Laravel-Logs auf wiederkehrende Fehler prüfen.
- Nach Deploy: Healthcheck (`/up` bei Laravel) und eine öffentliche Seite.

Weitere Ideen (nicht P0-pflicht): zentrales APM, synthetische Checks, Alarm bei 5xx-Rate.
