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

# Iniciar el servidor y seguir los logs
php artisan serve --host=0.0.0.0 --port=8080 &

# Seguir el archivo de log de Laravel
tail -f storage/logs/laravel.log
