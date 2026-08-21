# Stage 1: Build Node.js / Vite Assets
FROM node:20-alpine AS frontend-builder
WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build

# Stage 2: PHP Application Container
FROM php:8.3-cli

# Install system libraries and required PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip mbstring bcmath \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

# 1. Copy ONLY composer files first to isolate dependency installation
COPY composer.json composer.lock ./

# 2. Run composer install WITHOUT --no-plugins (which crashes Pest/Laravel plugins)
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction

# 3. Copy full application source files
COPY . .

# 4. Copy compiled Vite assets from Stage 1
COPY --from=frontend-builder /app/public/build /var/www/html/public/build

# 5. Generate production autoloader after all PHP classes exist
RUN composer dump-autoload --optimize --no-dev

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 10000
CMD ["sh", "docker-entrypoint.sh"]
