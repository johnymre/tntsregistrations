# ============================================
# Stage 1: Build Node.js / Vite assets
# ============================================
FROM node:20-alpine AS frontend-builder

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build


# ============================================
# Stage 2: PHP / Laravel application
# ============================================
FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
        mbstring \
        bcmath \
        intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html


# ============================================
# Install PHP dependencies
# ============================================
COPY composer.json composer.lock ./

# Helpful diagnostics for Render logs
RUN php -v && php -m && composer --version

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction \
    -vvv


# ============================================
# Application files
# ============================================
COPY . .


# ============================================
# Copy Vite production build
# ============================================
COPY --from=frontend-builder \
    /app/public/build \
    /var/www/html/public/build


# ============================================
# Optimize Composer autoloader
# ============================================
RUN composer dump-autoload \
    --optimize \
    --no-dev \
    --no-interaction


# ============================================
# Laravel permissions
# ============================================
RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data \
        storage \
        bootstrap/cache \
    && chmod -R 775 \
        storage \
        bootstrap/cache


# ============================================
# Entrypoint
# ============================================
RUN chmod +x /var/www/html/docker-entrypoint.sh

EXPOSE 10000

CMD ["sh", "/var/www/html/docker-entrypoint.sh"]