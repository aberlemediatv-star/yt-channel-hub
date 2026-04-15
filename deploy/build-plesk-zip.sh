#!/usr/bin/env bash
# =============================================================================
# Baut eine ZIP-Datei fuer Plesk: einmal hochladen, im Dateimanager entpacken.
#
# Voraussetzung (empfohlen): lokal einmal
#   cd laravel && composer install --no-dev --optimize-autoloader
# damit vendor/ in die ZIP kopiert wird (offline-faehig).
#
# Nutzung:
#   bash deploy/build-plesk-zip.sh
#       → ZIP mit Ordner laravel/ darin (klassisches Repo-Layout)
#
#   bash deploy/build-plesk-zip.sh --flat
#       → ZIP fuer flaches Hosting: app/, public/, src/, vendor/ liegen
#         direkt im Archiv-Root (kein Unterordner laravel/).
#
#   bash deploy/build-plesk-zip.sh --rebuild-vendor   # optional, kombinierbar mit --flat
#       → composer install --no-dev im Staging (braucht Netz)
#
# Ausgabe: dist/yt-channel-hub-plesk[-flat]-YYYYMMDD-HHMM.zip
# =============================================================================
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
REBUILD_VENDOR=0
FLAT=0
for arg in "$@"; do
  case "$arg" in
    --rebuild-vendor) REBUILD_VENDOR=1 ;;
    --flat) FLAT=1 ;;
  esac
done

STAMP="$(date +%Y%m%d-%H%M)"
OUT_DIR="$ROOT/dist"
SUFFIX="plesk-${STAMP}"
if [[ "$FLAT" -eq 1 ]]; then
  ZIP_NAME="yt-channel-hub-plesk-flat-${STAMP}.zip"
else
  ZIP_NAME="yt-channel-hub-plesk-${STAMP}.zip"
fi
ZIP_PATH="$OUT_DIR/$ZIP_NAME"
BUILD="$(mktemp -d "${TMPDIR:-/tmp}/yt-hub-plesk-zip.XXXXXX")"

cleanup() {
  rm -rf "$BUILD"
}
trap cleanup EXIT

mkdir -p "$OUT_DIR"

if [[ "$FLAT" -eq 1 ]]; then
  echo ">>> Staging FLAT (Laravel = Archiv-Root) nach $BUILD ..."
  rsync -a "$ROOT/laravel/" "$BUILD/" \
    --exclude 'node_modules' \
    --exclude '.env' \
    --exclude '.phpunit.cache' \
    --exclude 'public/hot' \
    --exclude 'storage/framework/cache/data' \
    --exclude 'storage/framework/sessions' \
    --exclude 'storage/framework/views' \
    --exclude 'storage/logs' \
    --exclude '.DS_Store'

  rsync -a "$ROOT/bin/" "$BUILD/bin/"
  rsync -a "$ROOT/deploy/" "$BUILD/deploy/"

  mkdir -p "$BUILD/storage/framework/cache/data" \
    "$BUILD/storage/framework/sessions" \
    "$BUILD/storage/framework/views" \
    "$BUILD/storage/logs" \
    "$BUILD/storage/backups"

  LARAVEL_VENDOR_SRC="$ROOT/laravel/vendor"
  COMPOSER_DIR="$BUILD"
else
  echo ">>> Staging MIT laravel/-Unterordner nach $BUILD ..."
  rsync -a "$ROOT/" "$BUILD/" \
    --exclude '.git' \
    --exclude '.cursor' \
    --exclude 'dist/' \
    --exclude 'node_modules' \
    --exclude 'laravel/node_modules' \
    --exclude '.env' \
    --exclude 'laravel/.env' \
    --exclude '.phpunit.cache' \
    --exclude 'laravel/.phpunit.cache' \
    --exclude 'laravel/public/hot' \
    --exclude 'laravel/storage/framework/cache/data' \
    --exclude 'laravel/storage/framework/sessions' \
    --exclude 'laravel/storage/framework/views' \
    --exclude 'laravel/storage/logs' \
    --exclude 'storage/logs' \
    --exclude '.DS_Store'

  mkdir -p "$BUILD/laravel/storage/framework/cache/data" \
    "$BUILD/laravel/storage/framework/sessions" \
    "$BUILD/laravel/storage/framework/views" \
    "$BUILD/laravel/storage/logs" \
    "$BUILD/storage/logs" \
    "$BUILD/storage/backups"

  LARAVEL_VENDOR_SRC="$ROOT/laravel/vendor"
  COMPOSER_DIR="$BUILD/laravel"
fi

if [[ "$REBUILD_VENDOR" -eq 1 ]]; then
  echo ">>> composer install --no-dev (braucht Netz) in $COMPOSER_DIR ..."
  rm -rf "$COMPOSER_DIR/vendor"
  (cd "$COMPOSER_DIR" && composer install --no-dev --no-interaction --optimize-autoloader)
  if [[ "$FLAT" -eq 0 ]] && [[ -f "$BUILD/composer.json" ]]; then
    echo ">>> composer install --no-dev (Root, optional) ..."
    rm -rf "$BUILD/vendor"
    (cd "$BUILD" && composer install --no-dev --no-interaction --optimize-autoloader)
  fi
else
  if [[ ! -d "$LARAVEL_VENDOR_SRC" ]]; then
    echo "WARNUNG: laravel/vendor fehlt lokal. ZIP ohne Vendor ist auf dem Server nicht lauffaehig."
    echo "         Ausfuehren: cd laravel && composer install --no-dev"
    echo "         Oder: bash deploy/build-plesk-zip.sh --rebuild-vendor  (ggf. mit --flat)"
  fi
fi

echo ">>> ZIP schreiben: $ZIP_PATH ..."
( cd "$BUILD" && zip -r -q "$ZIP_PATH" . -x '*.git*' )

echo ""
echo "Fertig: $ZIP_PATH ($(du -h "$ZIP_PATH" | cut -f1))"
echo ""
if [[ "$FLAT" -eq 1 ]]; then
  echo "Plesk (flach): ZIP ins Webroot (httpdocs) entpacken — dort liegen app/, public/, bin/, …"
  echo "               Document Root auf **public** setzen (nicht laravel/public)."
  echo "               .env im Webroot anlegen (z. B. aus deploy/.env.plesk.example)."
else
  echo "Plesk: ZIP nach httpdocs hochladen, dort \"Archiv entpacken\"."
  echo "       Document Root auf httpdocs/laravel/public setzen."
  echo "       laravel/.env anlegen (z. B. aus deploy/.env.plesk.example)."
fi
