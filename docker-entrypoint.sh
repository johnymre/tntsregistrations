#!/bin/sh
set -e

# Clear configuration cache
php artisan config:clear

# Run database migrations
php artisan migrate --force

# Start the Laravel application server
exec php artisan serve --host=0.0.0.0 --port=10000
