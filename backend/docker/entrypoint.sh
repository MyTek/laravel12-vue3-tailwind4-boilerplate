#!/usr/bin/env sh
set -e

# Ensure required writable dirs exist
mkdir -p /var/www/html/storage/framework/views \
         /var/www/html/storage/framework/cache \
         /var/www/html/storage/framework/sessions \
         /var/www/html/bootstrap/cache

# Fix permissions on bind mounts (WSL/Windows often needs this)
# Don't fail container if filesystem doesn't support chown.
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R ug+rwX /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Run Reverb in background
php artisan reverb:start --host=0.0.0.0 --port=8081 > /proc/1/fd/1 2>/proc/1/fd/2 &

exec "$@"
