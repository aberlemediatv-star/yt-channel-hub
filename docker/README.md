# Docker (lokale Entwicklung)

## Start

```bash
docker compose up --build
```

Die App ist unter **http://localhost:8080** erreichbar (Port über Umgebungsvariable `HTTP_PORT` oder eine `.env` im Projektroot mit `HTTP_PORT=…` anpassen).

- **Admin:** `http://localhost:8080/admin/login.php` (Laravel) — Standardpasswort `changeme` (über `ADMIN_PASSWORD_HASH_B64` in `docker-compose.yml`). Eigenes Passwort: `php -r 'echo base64_encode(password_hash("IhrPasswort", PASSWORD_DEFAULT));'` und den Wert als `ADMIN_PASSWORD_HASH_B64` eintragen.
- **Datenbank:** MariaDB, Hostname im PHP-Container `db`, Zugangsdaten wie in `docker-compose.yml` (`yt_hub` / `yt_hub_pass`).
- **OAuth:** In der Google Cloud Console die Redirect-URI eintragen, z. B. `http://localhost:8080/oauth_callback.php` (Port anpassen). Zusätzlich `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` usw. setzen — z. B. in einer Override-Datei oder per Umgebungsvariablen am `php`-Service.

## Technik

- **nginx** verwendet als Document Root **`laravel/public`** (siehe `docker/nginx/default.conf`); PHP geht über FastCGI an **php** (Port 9000) und läuft dabei durch Laravels `public/index.php`.
- **vendor** kommt aus dem Image; durch das Volume `./:/var/www/html` bleibt `vendor` ein anonymer Volume-Eintrag, damit `composer install` aus dem Build nicht überschrieben wird.
- Beim ersten Start legt der PHP-Entrypoint fehlende `laravel/config/hub.php` aus `laravel/config/hub.example.php` an.
- Die Datenbank wird beim ersten Anlegen des DB-Volumes mit `laravel/database/legacy_sql/schema.sql` sowie den Staff-SQL-Dateien `003`/`004` initialisiert.

## Hintergrund-Jobs

```bash
docker compose up --build
```

Der `worker`-Service ist im Compose bereits enthalten und verarbeitet die Queue automatisch.

Manuell einmalig ausführen (z. B. zum Testen):

```bash
docker compose exec php php bin/worker.php
```

## Frische Datenbank

Volume entfernen und neu starten:

```bash
docker compose down -v
docker compose up --build
```
