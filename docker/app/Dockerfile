FROM php:8.2-fpm-alpine

# System deps
RUN apk add --no-cache \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    bash \
    curl \
    git \
    unzip \
    && docker-php-ext-install \
    intl \
    pdo_mysql \
    zip \
    opcache

# PHP configuration
COPY docker/app/php.ini /usr/local/etc/php/conf.d/99-app.ini

WORKDIR /var/www/html
