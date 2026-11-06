#!/bin/sh
set -e
cd /var/www/html

# The media volume is mounted from the host (usually root-owned) - make sure PHP can write to it.
mkdir -p storage/app/public/posters storage/app/public/trailers
chown www-data:www-data storage/app/public storage/app/public/posters storage/app/public/trailers
chown -R www-data:www-data storage/framework storage/logs bootstrap/cache

[ -e public/storage ] || php artisan storage:link

# Build the framework caches from the REAL runtime env (env vars are only known here, not at image build).
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

php-fpm -D
exec nginx -g 'daemon off;'
