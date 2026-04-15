# Architektur (Kurzüberblick)

## Schichten

```text
Browser
   → Webserver (Document Root typisch: laravel/public/)
        → Laravel (Routing, Middleware, Blade, Session, Cache)
        → YtHub PHP (src/) — Domain-Logik per Composer-Autoload „YtHub\“
        → MariaDB/MySQL — Kernschema database/schema.sql (+ Laravel-Migrationen für Social/Advanced Feeds)
```

- **`laravel/`**: HTTP-Einstieg, Admin-/Staff-/Site-Controller, interne Token-Routen, Eloquent-Modelle für Laravel-Tabellen.
- **`src/`**: PDO-Repositories, Google/YouTube-Services, Export, Auth-Helfer, **`Lang`**, **`PublicHttp`** (Security-Header + CSP-Nonce), Worker/Jobs-Helfer.
- **`config/config.php`** (nicht immer in VCS): DB und YtHub-**Admin-Passwort-Hash**, **internal_token**, Google-Defaults; wird von `src/bootstrap.php` geladen.
- **`public/`** (Repo-Root): statische Assets für das öffentliche Theme; Deploy so wählen, dass sie unter dem gleichen Host wie die App ausgeliefert werden.

## Wichtige Datenflüsse

1. **Öffentliche Startseite:** `HomeController` → `ChannelRepository` / `VideoRepository` → optional Laravel-Cache (`config/ythub.php`).
2. **Admin (Passwort):** Session + `AdminAuth`; viele Aktionen gegen YtHub-Tabellen über PDO.
3. **OAuth / YouTube:** `google/apiclient`, Refresh-Tokens in DB, teils verschlüsselt (`APP_ENCRYPTION_KEY` / `security.encryption_key`).
4. **Token-Admin (INTERNAL_TOKEN):** Laravel-Middleware, keine Admin-Passwort-Session.

## OAuth (grob)

- Redirect-Handler und Scopes: siehe Routen unter `laravel/routes/` und Kommentare in `config/config.example.php` / Admin-OAuth-Hilfen.

## Weitere Doku

- **[P0_OPERATIONS.md](P0_OPERATIONS.md)** · **[P6_SECURITY_AND_COMPLIANCE.md](P6_SECURITY_AND_COMPLIANCE.md)** · **[P7_DEVELOPER_EXPERIENCE.md](P7_DEVELOPER_EXPERIENCE.md)**
