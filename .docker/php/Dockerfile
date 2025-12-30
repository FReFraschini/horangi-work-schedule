
FROM php:8.3-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    curl \
    libzip-dev \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    nodejs \
    npm \
    icu-dev \
    gmp-dev \
    poppler-utils \
    oniguruma-dev \
    autoconf \
    build-base \
    linux-headers

# Install PHP extensions
RUN docker-php-ext-configure gd --with-jpeg
RUN docker-php-ext-configure intl
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip
# Install Xdebug
# Copy configuration files
COPY .docker/php/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN pecl install xdebug-3.5.0 && docker-php-ext-enable xdebug

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www


# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
