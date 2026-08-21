#!/bin/sh
set -e

# Clear config and route caches
php artisan config:clear
php artisan route:clear

# Run database migrations
php artisan migrate --force

# Start application server
exec php artisan serve --host=0.0.0.0 --port=10000
