#!/bin/sh
set -e

echo "=== Container started ==="

echo "Clearing Laravel caches..."
php artisan config:clear || true
php artisan cache:clear || true

echo "Generating app & JWT keys..."
php artisan key:generate --force || true
php artisan jwt:secret --force || true

echo "Optimizing Laravel..."
php artisan optimize || true

echo "Waiting for MySQL..."
sleep 10

echo "Running migrations..."
php artisan migrate --force || true

echo "Creating storage link..."
php artisan storage:link || true

echo "Creating log file..."
touch storage/logs/laravel.log
chmod 666 storage/logs/laravel.log

echo "Starting Laravel server on 0.0.0.0:8080..."

tail -f storage/logs/laravel.log &

exec php artisan serve --host=0.0.0.0 --port=8080
