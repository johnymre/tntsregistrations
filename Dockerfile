# Stage 1: Build Vue/JS frontend assets
FROM node:20-alpine AS frontend
WORKDIR /app
RUN apt-get update && apt-get install -y nodejs npm
COPY package*.json ./
RUN npm install
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: PHP/Laravel Application
FROM php:8.4-fpm-alpine

# 1. Install system dependencies & build tools
# Use --no-cache to always pull fresh package list indexes
RUN apk update && apk add --no-cache \
    nginx \
    postgresql-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    git \
    unzip \
    curl


# 2. Install required PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql zip gd bcmath

WORKDIR /var/www/html

# 3. Copy files and install Composer dependencies
COPY . .
ENV COMPOSER_HTTP_TIMEOUT=600

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-dev --optimize-autoloader --no-interaction

# Copy Nginx configuration
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

# Cache Laravel configuration, run migrations, start PHP-FPM & Nginx
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]


