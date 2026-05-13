#!/usr/bin/env bash
set -e

cd /var/www/html

if [ -n "${PORT:-}" ] && [ "$PORT" != "10000" ]; then
    sed -i "s/listen 10000;/listen ${PORT};/" /etc/nginx/nginx.conf
fi

if [ -n "${DATABASE_URL:-}" ] && [ -z "${DB_URL:-}" ]; then
    export DB_URL="$DATABASE_URL"
fi

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

php artisan config:clear
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan view:cache

exec supervisord -c /etc/supervisord.conf
