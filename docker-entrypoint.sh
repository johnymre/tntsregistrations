#!/bin/sh
set -e

# Clear all cached states instead of compiling them on startup
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run migrations
php artisan migrate --force

# Serve application
exec php artisan serve --host=0.0.0.0 --port=10000
