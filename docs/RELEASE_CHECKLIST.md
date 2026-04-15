# Release-Checkliste

Vor **Produktions-Deploy** (und nach größeren Änderungen) abarbeiten.

## Vor dem Deploy

- [ ] **`laravel/.env`** / Server-Umgebung: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` mit **https**, gültige **DB**-Zugangsdaten, **`INTERNAL_TOKEN`**, ggf. **`PUBLIC_HOME_CACHE_TTL`** bewusst setzen.
- [ ] **`config/config.php`** (YtHub): DB, **ADMIN_PASSWORD_HASH**, **`security.internal_token`**, Google-Keys/Redirect — mit Staging abgeglichen.
- [ ] **Schema:** `database/schema.sql` + alle relevanten **Laravel-Migrationen** angewendet (`php artisan migrate --force`).
- [ ] **Assets:** `public/assets/*` unter **`laravel/public/`** verfügbar (Kopie/Symlink/Build-Pipeline).
- [ ] **Cron:** `bin/worker.php` minütlich; weitere Skripte (Analytics-Sync, Backups) wie in Betriebsdoku.

## Nach dem Deploy

- [ ] **Smoke:** `/`, `/admin/login.php`, ein tokengeschützter Pfad (falls genutzt), optional Staff-Login.
- [ ] **`php artisan config:clear`** / **`cache:clear`** auf dem Server, falls Config geändert wurde.
- [ ] **Logs:** erste Minuten `storage/logs/laravel.log` und `storage/logs/app.log` (YtHub) prüfen.
- [ ] **CSP / Konsole:** öffentliche Seite im Browser — keine roten CSP-Fehler.

## Rollback

- Vorherige **Release-Version** + bekannte DB-Migration rückgängig nur mit **getestetem Down**-Pfad (Laravel `migrate:rollback` nur wenn Migrationen das hergeben).

Siehe **[P0_OPERATIONS.md](P0_OPERATIONS.md)** (Backups, Monitoring).
