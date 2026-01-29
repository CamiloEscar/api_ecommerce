#!/bin/bash
set -e

echo "=== Container started ==="
echo "Waiting 30 seconds for MySQL..."
sleep 30

echo "Running migrations..."
php artisan migrate --force || echo "Migration failed but continuing..."

echo "Creating storage link..."
php artisan storage:link || echo "Storage link failed but continuing..."

echo "Starting Laravel server on 0.0.0.0:8080..."
exec php artisan serve --host=0.0.0.0 --port=8080
