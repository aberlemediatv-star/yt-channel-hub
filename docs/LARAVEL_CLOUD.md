# Laravel Cloud und dieses Repository

## Meldung: „Unsupported framework“ (trotz Branch `laravel-cloud`)

[Laravel Cloud](https://cloud.laravel.com) prüft das **Repository-Root** (oft der **Standard-Branch `main`**). Dort müssen u. a. **`composer.json` mit `laravel/framework`** und eine passende **`composer.lock`** erkennbar sein.

Im Monorepo liegt der Anwendungscode unter **`laravel/`**; im Root gibt es zusätzlich ein **`composer.json`**, das dieselben Pakete wie `laravel/composer.json` deklariert (Pfade mit Prefix `laravel/…`), und **`composer.lock`** (Laravel inkl. Lock). Zusätzlich liegt **`artisan`** im Root. Damit sollte die Erkennung auf **`main`** funktionieren.

Wenn die Meldung **trotzdem** kommt: Branch **`laravel-cloud`** wählen oder **Lösung A** (Default-Branch kurz umstellen).

### Sofort-Lösung A: Standard-Branch temporär wechseln

1. **GitHub:** Repository **Settings → General → Default branch**  
   → auf **`laravel-cloud`** stellen (bestätigen).
2. **Laravel Cloud:** Application neu anlegen oder Repository erneut verbinden — Auswahl sollte jetzt als Laravel erkannt werden.
3. **GitHub:** Default branch wieder auf **`main`** stellen (nach erfolgreicher Einrichtung).

In der Cloud-Umgebung weiterhin den Deploy-Branch **`laravel-cloud`** wählen, falls die UI das getrennt vom „Repository-Scan“ anbietet.

### Sofort-Lösung B: Nur Branch `laravel-cloud` in der UI

Falls Laravel Cloud **explizit nach Branch fragt**, unbedingt **`laravel-cloud`** wählen, **nicht** `main`.

### Root-Dateien für Cloud / lokale DX

- **`artisan`** → leitet nach **`laravel/artisan`**.
- **`composer.json` / `composer.lock`** im Root → Laravel-Erkennung + gleiche Versionen wie unter `laravel/` (lokal meist nur `cd laravel && composer install` nötig).

---

## Hintergrund

Auf **`main`** ist ein **Monorepo** (`laravel/`, `bin/`, `deploy/`, …), kein klassisches Laravel-Root mit `app/` direkt unter `/`.

## Branch nur mit `laravel/` (empfohlen für Cloud-Builds)

Git erzeugt aus `laravel/` einen Branch, dessen Root = Laravel-App:

```bash
cd "/Users/christianaberle/YT Channel carousel"
git fetch origin
git subtree split -P laravel -b laravel-cloud
git push -u origin laravel-cloud:laravel-cloud --force
```

Nach Änderungen **unter `laravel/`** auf `main` den Befehl wiederholen, damit Cloud den aktuellen Stand bekommt.

## Offizieller Monorepo-Workaround (Laravel-Doku)

Mit **Root-`composer.lock`-Kopie** und **Build-Skript**, das `laravel/` nach Checkout nach oben kopiert:  
[Monorepo Support](https://cloud.laravel.com/docs/knowledge-base/monorepo-support.md)
