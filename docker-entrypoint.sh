#!/bin/sh
set -e

# Clear existing caches to prevent malformed URI parsing during build
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run database migrations
php artisan migrate --force

# Start application server
exec php artisan serve --host=0.0.0.0 --port=10000
