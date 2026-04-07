
FROM php:8.3-fpm-alpine AS base

# Install system dependencies (runtime only)
RUN apk add --no-cache \
    curl \
    libzip-dev \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    gmp-dev \
    oniguruma-dev

# Install PHP extensions (build deps added then removed to keep image lean)
RUN apk add --no-cache --virtual .build-deps \
        autoconf \
        build-base \
        linux-headers \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl gmp \
    && apk del .build-deps

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

EXPOSE 9000
CMD ["php-fpm"]

# ─── Development stage (adds Xdebug) ────────────────────────────────────────
FROM base AS dev

RUN apk add --no-cache --virtual .build-deps \
        autoconf \
        build-base \
        linux-headers \
    && pecl install xdebug-3.5.0 \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps

COPY .docker/php/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# ─── Production stage (no Xdebug, OPcache enabled) ──────────────────────────
FROM base AS prod

RUN apk add --no-cache --virtual .build-deps \
        autoconf \
        build-base \
        linux-headers \
    && docker-php-ext-install opcache \
    && apk del .build-deps

COPY .docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
