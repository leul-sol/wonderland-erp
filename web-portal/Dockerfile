FROM node:22-bookworm AS frontend

WORKDIR /build

COPY package.json package-lock.json* ./
RUN npm install --legacy-peer-deps

COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

FROM php:8.4-cli-bookworm

RUN apt-get update && apt-get install -y \
    git curl unzip libzip-dev \
    && docker-php-ext-install zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --no-interaction

COPY . .
COPY --from=frontend /build/public/build ./public/build

RUN composer dump-autoload --optimize \
    && mkdir -p bootstrap/cache storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && printf '%s\n' \
        '#!/bin/sh' \
        'set -e' \
        'cd /var/www/html' \
        'if [ ! -f vendor/autoload.php ]; then' \
        '  composer install --no-interaction --prefer-dist' \
        'fi' \
        'if [ -f .env ] && grep -q "^APP_KEY=$" .env 2>/dev/null; then' \
        '  php artisan key:generate --force --no-interaction || true' \
        'fi' \
        'if [ "$#" -gt 0 ]; then' \
        '  exec "$@"' \
        'fi' \
        'exec php artisan serve --host=0.0.0.0 --port=9000' \
        > /usr/local/bin/wonderland-entrypoint.sh \
    && chmod +x /usr/local/bin/wonderland-entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/wonderland-entrypoint.sh"]
