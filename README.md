# YT Channel Hub

Mehrkanal-**YouTube**-Übersicht (Carousel), **Analytics**, Admin- und Staff-Bereich, optional Social/MRSS/Advanced Feeds — **Laravel** unter `laravel/` inkl. **YtHub**-PHP (`laravel/src/`) und **MariaDB/MySQL**.

## Schnellstart

1. **PHP 8.4** und **Composer** installieren.  
2. **Laravel:**  
   ```bash
   cd laravel && composer install && cp .env.example .env && php artisan key:generate
   ```  
   `.env` ausfüllen und **`php artisan migrate`** ausführen.  
3. **YtHub-Konfiguration:** [`laravel/config/hub.example.php`](laravel/config/hub.example.php) nach `laravel/config/hub.php` kopieren (Datenbank, Admin-Hash, `internal_token`, Google) — oder den Web-Installer nutzen.  
4. **Kern-Schema:** [`laravel/database/legacy_sql/schema.sql`](laravel/database/legacy_sql/schema.sql) in dieselbe Datenbank einspielen, danach **`php bin/migrate.php`** im Projektroot (führt `laravel/database/legacy_sql/migrations/*.sql` aus) bzw. auf dem Server nur Laravel: **`php bin/legacy-migrate.php`**.  
   **Plesk mit flachem Webroot** (`app/`/`public/` ohne `laravel/`): Deploy-ZIP mit **`bash deploy/build-plesk-zip.sh --flat`**, Document Root **`public`**, siehe [deploy/PLESK_DEPLOY.md](deploy/PLESK_DEPLOY.md).  
5. *(Optional)* Im Projektroot **`composer install`** für PHPUnit/PHPStan der Root-Tests.

Statische Assets liegen unter **`laravel/public/`** (Document Root für Plesk: `laravel/public`).

Ausführlicher: **[docs/P7_DEVELOPER_EXPERIENCE.md](docs/P7_DEVELOPER_EXPERIENCE.md)** (Hinweise in älteren Abschnitten zu `config/config.php` / `database/schema.sql` bitte gegen `laravel/config/hub.php` bzw. `laravel/database/legacy_sql/` abgleichen.)

## Qualität & CI

```bash
composer run ci          # Root: PHPUnit + PHPStan (YtHub unter laravel/src)
cd laravel && composer run ci
```

GitHub: [`.github/workflows/ci.yml`](.github/workflows/ci.yml)

## Dokumentation (Themen P0–P7)

| Thema | Datei |
|--------|--------|
| Betrieb, Backups, CI | [docs/P0_OPERATIONS.md](docs/P0_OPERATIONS.md) |
| i18n | [docs/P1_I18N.md](docs/P1_I18N.md) |
| Frontend / Export | [docs/P2_P3_FRONTEND_AND_PRODUCT.md](docs/P2_P3_FRONTEND_AND_PRODUCT.md) |
| QA, Performance | [docs/P4_P5_QA_AND_PERF.md](docs/P4_P5_QA_AND_PERF.md) |
| Security / Compliance | [docs/P6_SECURITY_AND_COMPLIANCE.md](docs/P6_SECURITY_AND_COMPLIANCE.md) |
| Developer Experience | [docs/P7_DEVELOPER_EXPERIENCE.md](docs/P7_DEVELOPER_EXPERIENCE.md) |
| Plesk / Deploy | [deploy/PLESK_DEPLOY.md](deploy/PLESK_DEPLOY.md) |
| Laravel Cloud | [docs/LARAVEL_CLOUD.md](docs/LARAVEL_CLOUD.md) |
