# syntax=docker/dockerfile:1

############################################
# Stage 1 — Dépendances PHP (Composer)
############################################
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
COPY . .
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --optimize-autoloader

############################################
# Stage 2 — Build des assets front (Vite)
############################################
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

############################################
# Stage 3 — Image d'exécution (PHP-FPM + Nginx)
############################################
FROM php:8.2-fpm-bookworm AS runtime

# --- Paquets système + librairies pour les extensions PHP ---
RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
        libicu-dev \
        unzip \
        ca-certificates \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        bcmath \
        gd \
        zip \
        exif \
        pcntl \
        intl \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# --- Code applicatif + dépendances déjà construites ---
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

# --- Configuration container (php, nginx, supervisor, entrypoint) ---
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint \
    && mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && rm -f /etc/nginx/sites-enabled/default \
    && ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

EXPOSE 80

ENTRYPOINT ["entrypoint"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
