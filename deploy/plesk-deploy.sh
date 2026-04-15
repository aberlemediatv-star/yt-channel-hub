#!/usr/bin/env bash
# =============================================================================
# Plesk / Bare-Metal Deploy-Skript (idempotent)
#
# Verwendung:
#   cd /var/www/vhosts/example.com/httpdocs
#   bash deploy/plesk-deploy.sh
#
# Voraussetzungen:
#   - PHP 8.4+ CLI im PATH
#   - Composer global oder unter vendor/bin
#   - laravel/.env ist korrekt ausgefuellt (inkl. APP_KEY)
#   - MariaDB-Datenbank existiert und ist erreichbar
# =============================================================================
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

if [ -f "$PROJECT_ROOT/laravel/composer.json" ]; then
    LARAVEL_DIR="$PROJECT_ROOT/laravel"
else
    LARAVEL_DIR="$PROJECT_ROOT"
fi

cd "$PROJECT_ROOT"

echo "=== YT Channel Hub — Deploy ==="
echo "Projektverzeichnis: $PROJECT_ROOT"
echo "Laravel-Verzeichnis: $LARAVEL_DIR"
echo "PHP: $(php -v | head -1)"
echo ""

# --- 1. Root-Paket (nur Monorepo: PHPUnit/PHPStan fuer YtHub) -----------------
if [ -f "$PROJECT_ROOT/composer.json" ] && [ -d "$PROJECT_ROOT/laravel" ]; then
    echo ">>> composer install (Root, optional) ..."
    composer install --no-dev --no-interaction --optimize-autoloader --quiet
fi

# --- 2. Laravel -------------------------------------------------------------
echo ">>> composer install (Laravel) ..."
cd "$LARAVEL_DIR"
composer install --no-dev --no-interaction --optimize-autoloader --quiet

# --- 3. APP_KEY pruefen -----------------------------------------------------
if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    echo ">>> APP_KEY fehlt — generiere ..."
    php artisan key:generate --force
fi

# --- 4. Migrationen ---------------------------------------------------------
echo ">>> Legacy-SQL-Migrationen (database/legacy_sql/migrations/) ..."
if [ -f "$PROJECT_ROOT/bin/migrate.php" ]; then
    php "$PROJECT_ROOT/bin/migrate.php"
else
    php "$LARAVEL_DIR/bin/legacy-migrate.php"
fi

echo ">>> Laravel-Migrationen ..."
php artisan migrate --force

# --- 5. Caches --------------------------------------------------------------
echo ">>> Config-Cache ..."
php artisan config:cache

echo ">>> Route-Cache ..."
php artisan route:cache

echo ">>> View-Cache ..."
php artisan view:cache

# --- 6. Storage-Symlink -----------------------------------------------------
if [ ! -L public/storage ]; then
    echo ">>> Storage-Link ..."
    php artisan storage:link
fi

# --- 7. Berechtigungen ------------------------------------------------------
echo ">>> Berechtigungen ..."
cd "$PROJECT_ROOT"

DIRS_WRITABLE=(
    "$LARAVEL_DIR/storage"
    "$LARAVEL_DIR/bootstrap/cache"
    "$LARAVEL_DIR/storage/logs"
    "$LARAVEL_DIR/storage/backups"
)
if [ "$LARAVEL_DIR" != "$PROJECT_ROOT" ]; then
    DIRS_WRITABLE+=("storage/logs" "storage/backups")
fi

for d in "${DIRS_WRITABLE[@]}"; do
    if [ -d "$d" ]; then
        chmod -R ug+rwX "$d" 2>/dev/null || true
    else
        mkdir -p "$d"
        chmod -R ug+rwX "$d" 2>/dev/null || true
    fi
done

# Plesk: www-data oder der Plesk-Systemuser
WEB_USER="${WEB_USER:-www-data}"
for d in "${DIRS_WRITABLE[@]}"; do
    if [ -d "$d" ]; then
        chown -R "$WEB_USER":"$WEB_USER" "$d" 2>/dev/null || true
    fi
done

echo ""
echo "=== Deploy abgeschlossen ==="
echo ""
echo "Naechste Schritte (einmalig):"
echo "  1. Document Root in Plesk auf ${LARAVEL_DIR}/public setzen"
echo "  2. PHP 8.4 (oder 8.5) als FPM-Version zuweisen"
echo "  3. Nginx-Snippets aus deploy/ einfuegen"
echo "  4. Cron-Jobs anlegen (siehe deploy/PLESK_DEPLOY.md)"
echo "  5. SSL/Let's Encrypt aktivieren"
