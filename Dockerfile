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
FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    nginx \
    git \
    curl \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install \
        gd \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
        mbstring \
        bcmath \
        intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*


# ============================================
# PHP configuration
# ============================================
RUN printf '%s\n' \
    'upload_max_filesize=20M' \
    'post_max_size=25M' \
    'memory_limit=256M' \
    'max_execution_time=300' \
    'max_input_time=300' \
    > /usr/local/etc/php/conf.d/laravel.ini


# ============================================
# Composer
# ============================================
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html


# ============================================
# Install PHP dependencies
# ============================================
COPY composer.json composer.lock ./

RUN php -v \
    && php -m \
    && php -r "var_export(gd_info());" \
    && composer --version

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
RUN mkdir -p \
    storage/framework/cache \
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