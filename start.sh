#!/bin/sh
set -e

echo "=== Container started ==="

echo "Clearing config cache..."
php artisan config:clear || true

echo "Waiting for MySQL..."

until php artisan migrate:status >/dev/null 2>&1; do
  echo "MySQL not ready..."
  sleep 5
done

echo "MySQL is ready!"

php artisan migrate --force

php artisan storage:link || true

touch storage/logs/laravel.log
chmod 666 storage/logs/laravel.log

tail -f storage/logs/laravel.log &

exec php artisan serve --host=0.0.0.0 --port=8080
