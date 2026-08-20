#!/bin/sh
set -e

# Force environment clear
php artisan config:clear
php artisan route:clear

# Execute database migrations
php artisan migrate --force

# Launch application
exec php artisan serve --host=0.0.0.0 --port=10000
