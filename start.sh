#!/bin/sh
set -e

echo "=== Container started ==="
echo "Waiting for MySQL..."
sleep 10

echo "Running migrations..."
php artisan migrate --force

echo "Creating storage link..."
php artisan storage:link

echo "Starting Laravel server on 0.0.0.0:8080..."
exec php artisan serve --host=0.0.0.0 --port=8080
