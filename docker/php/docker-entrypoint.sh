#!/bin/sh
set -e
if [ ! -f laravel/config/hub.php ]; then
  cp -n laravel/config/hub.example.php laravel/config/hub.php || true
fi
mkdir -p laravel/storage/logs laravel/storage/backups/analytics storage/logs storage/backups/analytics
chown -R www-data:www-data laravel/storage storage 2>/dev/null || true
chmod -R ug+rwX laravel/storage storage 2>/dev/null || true
exec docker-php-entrypoint "$@"
