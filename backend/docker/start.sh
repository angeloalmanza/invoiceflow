#!/bin/sh
set -e

export PORT="${PORT:-8080}"

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

php artisan config:cache
php artisan route:cache
php artisan migrate --force

touch /var/www/html/database/database.sqlite 2>/dev/null || true

envsubst '${PORT}' < /etc/nginx/nginx.conf > /tmp/nginx.conf
cp /tmp/nginx.conf /etc/nginx/nginx.conf

exec /usr/bin/supervisord -c /etc/supervisord.conf
