# Stage 1: Build Vue/JS frontend assets
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: PHP/Laravel Application
FROM php:8.2-fpm-alpine

# Install Nginx and PostgreSQL driver
RUN apk add --no-cache nginx postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql

WORKDIR /var/www

# Copy application files
COPY . .
COPY --from=frontend /app/public/build ./public/build

# Install PHP dependencies
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-dev --optimize-autoloader

# Copy Nginx configuration
COPY nginx.conf /etc/nginx/http.d/default.conf

EXPOSE 80

# Cache Laravel configuration, run migrations, start PHP-FPM & Nginx
CMD php artisan config:cache && php artisan route:cache && php artisan migrate --force && php-fpm -D && nginx -g "daemon off;"
