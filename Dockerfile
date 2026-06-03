FROM php:8.2-cli-alpine

RUN apk add --no-cache \
    git \
    unzip \
    icu-dev \
    libzip-dev

RUN docker-php-ext-install \
    intl \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction
