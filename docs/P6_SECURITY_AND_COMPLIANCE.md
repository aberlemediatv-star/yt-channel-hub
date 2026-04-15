# P6 — Sicherheit & Compliance

Dieses Dokument ergänzt **[P0_OPERATIONS.md](P0_OPERATIONS.md)** (CI, Login-Rate-Limits, Backups) um **Security-Review**, **DSGVO-Orientierung** und optionale **2FA**.

## Bereits umgesetzt (Kurzüberblick)

- **Security-Header** in `YtHub\PublicHttp::sendSecurityHeaders()`: u. a. `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, **CSP** (inkl. Nonce für Skripte), **HSTS** unter HTTPS, verschärfte **Permissions-Policy** (u. a. Kamera/Mikro/Geo/Payment aus).
- **CSRF** für Laravel-Formulare und YtHub-Admin/Staff (`Csrf` / `StaffCsrf`).
- **Login-Throttling** für Admin- und Staff-Login (Laravel `RateLimiter`).
- **Logging** fehlgeschlagener Admin-/Staff-Logins (ohne Passwort im Klartext).

## Nach jedem größeren Frontend-/Header-Change

1. Öffentliche Startseite und **ein Admin-Screen** im Browser öffnen, **Konsole** auf CSP-Verstöße prüfen.
2. Neue **Inline-Skripte** nur mit **`nonce="{{ PublicHttp::cspNonce() }}"`** (bzw. über Blade-Partial `seo-head` / gleicher Nonce-Quelle).
3. Neue **externe Domains** (Bilder, APIs, Fonts) in **CSP** (`img-src`, `connect-src`, …) eintragen, sonst blockiert der Browser die Ressource.

## Sessions & Cookies (Laravel)

- Konfiguration: `laravel/config/session.php`, Werte aus `.env` (`SESSION_*`). Für Produktion typisch: **`SESSION_SECURE_COOKIE=true`**, **`SESSION_SAME_SITE`** passend setzen (z. B. `lax`), Lebensdauer bewusst wählen.
- **Session-Fixation**: Laravel regeneriert die Session bei Login, wo der Standard-Flow genutzt wird — eigene Auth-Pfade bei Änderungen erneut prüfen.

## Optional: Zwei-Faktor-Authentisierung (2FA) für Admin

- Noch **nicht** im Produktcode; sinnvolle Varianten: **TOTP** (Authenticator-App) oder **WebAuthn** (Passkeys).
- Umsetzung würde u. a. erfordern: sichere Speicherung von Geheimnissen/Recovery-Codes, UI in `admin/login`, Migration, klare Notfall-Strategie (Lockout, Support).

## DSGVO / Datenschutz (organisatorisch)

- **Verzeichnis von Verarbeitungstätigkeiten (VVT)** und **Löschkonzepte** für Logs, Analytics-Exporte, Backups mit Recht/Legal abstimmen.
- **Auftragsverarbeitung (AVV)** mit Hosting-Provider, ggf. Google (siehe bestehende Datenschutzhinweise in den Locale-Dateien `legal.*`).
- **Privacy by Design**: nur notwendige personenbezogene Daten loggen (aktuell: u. a. IP/User-Agent bei fehlgeschlagenem Login — Speicherdauer und Zweck dokumentieren).

## Weiterführend

- **[P4_P5_QA_AND_PERF.md](P4_P5_QA_AND_PERF.md)** (Tests, Cache, statische Header)  
- **[P7_DEVELOPER_EXPERIENCE.md](P7_DEVELOPER_EXPERIENCE.md)** (Setup, Architektur, Releases)
