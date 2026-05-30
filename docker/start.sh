#!/usr/bin/env bash
set -e

cd /var/www/html

if [ -n "${PORT:-}" ] && [ "$PORT" != "10000" ]; then
    sed -i "s/listen 10000;/listen ${PORT};/" /etc/nginx/nginx.conf
fi

if [ -n "${DATABASE_URL:-}" ]; then
    export DB_URL="$DATABASE_URL"
fi

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

php artisan config:clear

attempt=1
until php artisan migrate --force; do
    if [ "$attempt" -ge 10 ]; then
        echo "Database migration failed after ${attempt} attempts."
        exit 1
    fi

    echo "Database is not ready yet. Retrying migration in 5 seconds... attempt ${attempt}/10"
    attempt=$((attempt + 1))
    sleep 5
done

php artisan db:seed --force
php artisan config:cache
php artisan view:cache

exec supervisord -c /etc/supervisord.conf
