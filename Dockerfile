# Stage 1: Build Node.js / Vite Assets
FROM node:20-alpine AS frontend-builder
WORKDIR /app

# Copy package configs and install JS dependencies
COPY package*.json ./
RUN npm ci

# Copy source code and build Vite assets
COPY . .
RUN npm run build

# Stage 2: PHP Application Container
FROM php:8.3-fpm

# Install system dependencies & PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    nginx \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application source code
COPY . .

# Copy built Vite assets from Stage 1 into public/build
COPY --from=frontend-builder /app/public/build /var/www/html/public/build

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set directory permissions for Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port and start script
EXPOSE 10000
CMD ["sh", "docker-entrypoint.sh"]
