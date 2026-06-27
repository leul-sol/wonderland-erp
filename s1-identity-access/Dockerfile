FROM php:8.3-cli-bookworm

RUN apt-get update && apt-get install -y \
    git curl unzip libzip-dev libpng-dev libonig-dev libxml2-dev default-mysql-client \
    && docker-php-ext-install pdo_mysql zip bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/* \
    && printf '%s\n' \
        'opcache.enable=1' \
        'opcache.enable_cli=1' \
        'opcache.memory_consumption=256' \
        'opcache.interned_strings_buffer=16' \
        'opcache.max_accelerated_files=20000' \
        'opcache.validate_timestamps=1' \
        'opcache.revalidate_freq=0' \
        > /usr/local/etc/php/conf.d/99-opcache.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_DISABLE_HTTP2=1

COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --prefer-dist --no-interaction

COPY . .
RUN composer dump-autoload --optimize \
    && mkdir -p bootstrap/cache storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && printf '%s\n' \
        '#!/bin/sh' \
        'set -e' \
        'cd /var/www/html' \
        'if [ ! -f vendor/autoload.php ]; then' \
        '  echo "Installing Composer dependencies..."' \
        '  composer install --no-interaction --prefer-dist' \
        'fi' \
        'if [ -f .env ] && grep -q "^APP_KEY=$" .env 2>/dev/null; then' \
        '  php artisan key:generate --force --no-interaction || true' \
        'fi' \
        'php artisan app:ensure-seeded --no-interaction || true' \
        'if [ "$#" -gt 0 ]; then' \
        '  exec "$@"' \
        'fi' \
        'exec php artisan serve --host=0.0.0.0 --port=9001' \
        > /usr/local/bin/wonderland-entrypoint.sh \
    && chmod +x /usr/local/bin/wonderland-entrypoint.sh

EXPOSE 9001

ENTRYPOINT ["/usr/local/bin/wonderland-entrypoint.sh"]
