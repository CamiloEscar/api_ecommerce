#!/bin/sh
set -e

echo "=== Container started ==="
echo "Waiting for MySQL..."
sleep 10

echo "Running migrations..."
php artisan migrate --force

echo "Creating storage link..."
php artisan storage:link || true

echo "Creating log file..."
touch storage/logs/laravel.log
chmod 666 storage/logs/laravel.log

echo "Starting Laravel server on 0.0.0.0:8080..."

# Seguir los logs en background
tail -f storage/logs/laravel.log &

# Iniciar el servidor (este debe ser el último comando)
exec php artisan serve --host=0.0.0.0 --port=8080
