#!/bin/sh
set -e

# Run package discovery now that the container environment is loaded
php artisan package:discover --ansi

# Clear old cached files
php artisan config:clear
php artisan route:clear

# Execute database migrations
php artisan migrate --force

# Launch application server
exec php artisan serve --host=0.0.0.0 --port=10000
