# Laravel Cloud und dieses Repository

## Meldung: „Unsupported framework“ (trotz Branch `laravel-cloud`)

[Laravel Cloud](https://cloud.laravel.com) prüft beim **Auswählen des Repositories** oft nur den **Standard-Branch** (bei euch meist **`main`**). Auf `main` liegt Laravel unter **`laravel/`**, nicht im Root — dann erscheint **Unsupported framework**, **auch wenn** der Branch `laravel-cloud` korrekt wäre.

### Sofort-Lösung A: Standard-Branch temporär wechseln

1. **GitHub:** Repository **Settings → General → Default branch**  
   → auf **`laravel-cloud`** stellen (bestätigen).
2. **Laravel Cloud:** Application neu anlegen oder Repository erneut verbinden — Auswahl sollte jetzt als Laravel erkannt werden.
3. **GitHub:** Default branch wieder auf **`main`** stellen (nach erfolgreicher Einrichtung).

In der Cloud-Umgebung weiterhin den Deploy-Branch **`laravel-cloud`** wählen, falls die UI das getrennt vom „Repository-Scan“ anbietet.

### Sofort-Lösung B: Nur Branch `laravel-cloud` in der UI

Falls Laravel Cloud **explizit nach Branch fragt**, unbedingt **`laravel-cloud`** wählen, **nicht** `main`.

### Was im Root von `main` jetzt zusätzlich liegt

- **`artisan`** im Repository-Root leitet nach **`laravel/artisan`** weiter (`php artisan …` funktioniert vom Monorepo-Root).  
  Ob Laravel Cloud damit die Erkennung auf `main` schon akzeptiert, ist **nicht garantiert** — die zuverlässige Variante bleibt **`laravel-cloud`** bzw. **Lösung A**.

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
