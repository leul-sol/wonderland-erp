set -e

cd /var/www/html

if [ ! -f vendor/autoload.php ]; then
    echo "Installing Composer dependencies (vendor/ missing — bind mount overwrote image)..."
    composer install --no-interaction --prefer-dist
fi

if [ -f .env ] && grep -q '^APP_KEY=$' .env 2>/dev/null; then
    php artisan key:generate --force --no-interaction || true
fi

exec php artisan serve --host=0.0.0.0 --port=9001
