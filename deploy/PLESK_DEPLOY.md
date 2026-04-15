# Plesk Deployment — YT Channel Hub

Vollstaendige Anleitung fuer die Inbetriebnahme auf einem Plesk-Server mit MariaDB und PHP 8.4 (oder 8.5).

---

## Voraussetzungen

| Komponente | Minimum |
|------------|---------|
| **PHP** | 8.4 (8.5 kompatibel) |
| **PHP-Erweiterungen** | pdo_mysql, zip, mbstring, openssl, curl, json, fileinfo |
| **MariaDB** | 10.5+ (Plesk-Standard) |
| **Composer** | 2.x (global oder lokal) |
| **Plesk** | Obsidian 18.0+ |

---

## 1. Datenbank anlegen

In Plesk unter **Websites & Domains > Datenbanken > Datenbank hinzufuegen**:

- **Name:** `yt_hub`
- **Benutzer:** `yt_hub` (sicheres Passwort vergeben)
- **Zeichensatz:** `utf8mb4`

Initiales Schema importieren:

```bash
mysql -u yt_hub -p yt_hub < laravel/database/legacy_sql/schema.sql
php bin/migrate.php
```

(Flaches Hosting, Laravel = Webroot: `mysql ... < database/legacy_sql/schema.sql` und `php bin/migrate.php` — gleiche Pfade relativ zum Webroot.)

---

## 2. Dateien hochladen

**Empfohlen:** das gesamte Repository (mit `laravel/`, `bin/`, `deploy/`, …) nach `httpdocs/`.

**Flaches Hosting:** Wenn euer Webroot **schon** das Laravel-Verzeichnis ist (`app/`, `public/`, … direkt unter `httpdocs/`, kein weiterer Ordner `laravel/`), nutzt die ZIP mit **`bash deploy/build-plesk-zip.sh --flat`** (siehe 2a) und setzt den Document Root auf **`public`** (siehe Abschnitt 3).

Beispielpfad:

```
/var/www/vhosts/example.com/httpdocs/
```

### 2a. ZIP statt Ordner fuer Ordner (empfohlen fuer Plesk)

Lokal im geklonten Repository:

```bash
cd laravel && composer install --no-dev --optimize-autoloader && cd ..
# Standard: Unterordner laravel/ in der ZIP (Document Root …/laravel/public)
bash deploy/build-plesk-zip.sh

# Flaches Webroot (app/, public/ schon auf oberster Ebene — typisch Plesk-Subdomain):
bash deploy/build-plesk-zip.sh --flat
```

- **`dist/yt-channel-hub-plesk-*.zip`**: nach Entpacken liegt **`laravel/`** unter `httpdocs/` → Document Root **`httpdocs/laravel/public`**.
- **`dist/yt-channel-hub-plesk-flat-*.zip`**: Inhalt von Laravel liegt **direkt** im Archiv-Root (`app/`, `public/`, `src/`, `vendor/`, dazu `bin/`, `deploy/`). Nach Entpacken ins Webroot → Document Root nur **`public`** (kein `laravel/` dazwischen).

Ohne vorheriges lokales `composer install --no-dev` in `laravel/` waere die ZIP **ohne** `vendor/` (auf dem Server dann per SSH `composer install` nachholen). Alternative: `bash deploy/build-plesk-zip.sh --rebuild-vendor` (ggf. mit `--flat`) — laedt Abhaengigkeiten beim Packen neu, braucht Netz.

---

## 3. Document Root setzen

In Plesk unter **Websites & Domains > Hosting & DNS > Hosting-Einstellungen**:

**Normales ZIP** (Unterordner `laravel/`):

```
Document Root: /httpdocs/laravel/public
```

**Flaches ZIP** (`--flat`, `app/` und `public/` liegen schon direkt unter `httpdocs/`):

```
Document Root: /httpdocs/public
```

Damit zeigt der Webserver auf Laravels `public/`-Verzeichnis (mit oder ohne `laravel/` davor, je nach Layout).

---

## 4. PHP-Version zuweisen

In Plesk unter **Websites & Domains > PHP-Einstellungen**:

- **PHP-Version:** 8.4 (oder 8.5, sobald verfuegbar)
- **PHP-Handler:** FPM (FastCGI Process Manager)
- **Wichtige php.ini-Einstellungen:**
  - `memory_limit = 256M`
  - `max_execution_time = 300`
  - `upload_max_filesize = 32M`
  - `post_max_size = 32M`
  - `date.timezone = UTC`

---

## 5. .env-Datei einrichten

```bash
cd /var/www/vhosts/example.com/httpdocs
# Mit Unterordner laravel/:
cp deploy/.env.plesk.example laravel/.env && nano laravel/.env
# Flaches Webroot (ZIP --flat):
cp deploy/.env.plesk.example .env && nano .env
```

Mindestens diese Werte setzen:

| Variable | Beispiel |
|----------|----------|
| `APP_URL` | `https://example.com` |
| `APP_KEY` | (wird im naechsten Schritt generiert) |
| `DB_HOST` | `localhost` |
| `DB_DATABASE` | `yt_hub` |
| `DB_USERNAME` | `yt_hub` |
| `DB_PASSWORD` | (Passwort aus Schritt 1) |
| `INTERNAL_TOKEN` | `php -r "echo bin2hex(random_bytes(32));"` |

---

## 6. Deploy-Skript ausfuehren

```bash
cd /var/www/vhosts/example.com/httpdocs
bash deploy/plesk-deploy.sh
```

Das Skript:
- Installiert Composer-Abhaengigkeiten (ohne Dev)
- Generiert `APP_KEY` falls noetig
- Fuehrt alle Migrationen aus (Legacy + Laravel)
- Erstellt Config-, Route- und View-Cache
- Setzt Storage-Symlink und Berechtigungen

---

## 7. Nginx-Konfiguration

### Option A: Plesk "Zusaetzliche nginx-Direktiven"

In Plesk unter **Websites & Domains > Apache & nginx-Einstellungen > Zusaetzliche nginx-Direktiven**:

Inhalt aus diesen Dateien einfuegen:
1. `deploy/nginx-security-headers.conf` (Security-Header)
2. `deploy/nginx-server-hardening.conf` (Absicherung)

### Option B: Eigene nginx-Konfiguration

Falls der Server-Admin Zugriff auf die Nginx-Hauptkonfiguration hat:
1. `deploy/nginx-http-context.conf` in den `http { }`-Block einbinden (Rate-Limits)
2. `deploy/nginx.example.conf` als Referenz fuer den `server { }`-Block

---

## 8. SSL / HTTPS

In Plesk unter **Websites & Domains > SSL/TLS-Zertifikate**:

- **Let's Encrypt** aktivieren (empfohlen)
- Automatische Verlaengerung einschalten
- HTTP-zu-HTTPS-Weiterleitung aktivieren

Danach in `laravel/.env` pruefen:
```
APP_URL=https://example.com
SESSION_SECURE_COOKIE=true
```

---

## 9. Cron-Jobs

In Plesk unter **Websites & Domains > Geplante Aufgaben (Cron)**:

### Laravel Scheduler (Pflicht)

```
* * * * * cd /var/www/vhosts/example.com/httpdocs && php laravel/artisan schedule:run >> /dev/null 2>&1
```

### Legacy Queue Worker (Legacy-Jobs: Video-Sync, Analytics)

```
*/5 * * * * cd /var/www/vhosts/example.com/httpdocs && php bin/worker.php >> /dev/null 2>&1
```

### Laravel Queue Worker (Social-Posts etc.)

**Option A** — Ueber den Laravel Scheduler (empfohlen, bereits konfiguriert):

Der Scheduler startet automatisch `queue:work` mit `--max-time=300` und `--stop-when-empty`. Kein extra Cron noetig.

**Option B** — Eigener Cron (Alternative):

```
* * * * * cd /var/www/vhosts/example.com/httpdocs/laravel && php artisan queue:work database --sleep=3 --tries=3 --max-time=55 --stop-when-empty >> /dev/null 2>&1
```

---

## 10. API-Keys konfigurieren

### Im Admin-Panel (empfohlen)

Nach dem Login unter `/admin/api-keys?token=DEIN_TOKEN`:
- **TMDB API Key** (fuer Advanced Feeds)
- **Google API Key, Client ID, Client Secret, Redirect URI** (fuer YouTube OAuth)

DB-Werte haben Vorrang vor `.env`-Werten.

### Alternativ in .env

```
TMDB_API_KEY=...
GOOGLE_API_KEY=...
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=https://example.com/oauth_callback.php
```

### Social-Plattformen

Unter `/admin/social/settings?token=DEIN_TOKEN`:
- X (Twitter): Client ID + Secret
- TikTok: Client Key + Secret
- Meta/Instagram, Facebook, LinkedIn: App-Credentials

---

## 11. Pruefung nach dem Deployment

```bash
# Health-Check
curl -s https://example.com/up

# Erweiterter Health-Check (mit Token)
curl -s "https://example.com/health.php?token=DEIN_TOKEN"

# Laravel-Status
cd /var/www/vhosts/example.com/httpdocs/laravel
php artisan about
```

---

## 12. Updates deployen

Bei Code-Aenderungen:

```bash
cd /var/www/vhosts/example.com/httpdocs
git pull origin main       # oder Dateien per SFTP aktualisieren
bash deploy/plesk-deploy.sh
```

Das Deploy-Skript ist idempotent und kann beliebig oft ausgefuehrt werden.

---

## Troubleshooting

| Problem | Loesung |
|---------|---------|
| 500 Error | `laravel/storage/logs/laravel-*.log` pruefen; Berechtigungen auf `storage/` und `bootstrap/cache/` |
| "INTERNAL_TOKEN ist nicht gesetzt" | `INTERNAL_TOKEN` in `laravel/.env` setzen, dann `php artisan config:cache` |
| Queue-Jobs laufen nicht | Cron-Eintrag pruefen; `php artisan queue:work --once` manuell testen |
| CSS/JS fehlen | `php artisan storage:link` ausfuehren; Document Root pruefen |
| Migrationen fehlgeschlagen | DB-Berechtigungen pruefen; `php artisan migrate:status` |
