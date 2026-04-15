# Laravel Cloud und dieses Repository

## Meldung: „Unsupported framework“

[Laravel Cloud](https://cloud.laravel.com) erkennt beim Anlegen der App nur Repositories, in denen **im Root** eine Laravel- (oder Symfony-)Anwendung liegt: typisch `artisan`, `composer.json` mit `laravel/framework` usw.

In **yt-channel-hub** liegt die Laravel-App unter **`laravel/`**, daneben `bin/`, `deploy/`, `docs/` — das Root ist ein **Monorepo**, kein klassisches Laravel-Root. Deshalb erscheint **Unsupported framework**.

## Empfohlene Lösung: Branch nur mit `laravel/`

Git kann aus dem Ordner `laravel/` einen **eigenen Branch** erzeugen, dessen Root genau der Laravel-Code ist. Diesen Branch verknüpfst du in Laravel Cloud (oder ein kleines Deploy-Repo, das nur diesen Branch spiegelt).

Lokal (einmalig; ersetzt ggf. einen bestehenden Branch `laravel-cloud`):

```bash
cd "/Users/christianaberle/YT Channel carousel"
git fetch origin
git subtree split -P laravel -b laravel-cloud
git push -u origin laravel-cloud:laravel-cloud --force
```

In **Laravel Cloud** beim Anlegen der Application: dasselbe Repository wählen, Branch **`laravel-cloud`** (nicht `main`).

Hinweise:

- Auf diesem Branch fehlen **`bin/`** und **`deploy/`** am Root — für Laravel Cloud meist unkritisch (Queues/Cron über Cloud, nicht `bin/worker.php`).
- **Plesk** und **ZIP-Deploy** bleiben unverändert auf **`main`** mit Ordner `laravel/`.

## Alternative (offizieller Workaround)

Laravel dokumentiert einen Monorepo-Workaround mit **Root-`composer.lock`-Kopie** und **Build-Skript**, das `laravel/` nach Checkout „hochzieht“:  
[Konsole: Monorepo Support](https://cloud.laravel.com/docs/knowledge-base/monorepo-support.md)

Das ist fehleranfälliger und muss in der Cloud-UI pro Environment gepflegt werden; der **Subtree-Branch** ist meist klarer.
