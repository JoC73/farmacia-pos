FROM node:24-alpine AS assets

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY resources ./resources
COPY vite.config.js postcss.config.js tailwind.config.js ./
RUN npm run build


FROM php:8.4-cli-alpine AS vendor

RUN apk add --no-cache git icu-dev libxml2-dev libzip-dev oniguruma-dev postgresql-dev sqlite-dev unzip \
    && docker-php-ext-install bcmath dom intl mbstring pdo_pgsql pdo_sqlite pgsql xmlreader zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1 \
    COMPOSER_MAX_PARALLEL_HTTP=4

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts

COPY . .
RUN composer dump-autoload --optimize \
    && php artisan package:discover --ansi


FROM php:8.4-fpm-alpine

RUN apk add --no-cache bash icu-dev libxml2-dev libzip-dev nginx oniguruma-dev postgresql-dev sqlite-dev supervisor \
    && docker-php-ext-install bcmath dom intl mbstring opcache pdo_pgsql pdo_sqlite pgsql xmlreader zip \
    && mkdir -p /run/nginx /var/log/supervisor

WORKDIR /var/www/html

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/start.sh /usr/local/bin/start.sh

RUN chmod +x /usr/local/bin/start.sh \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=pgsql

EXPOSE 10000

CMD ["/usr/local/bin/start.sh"]
