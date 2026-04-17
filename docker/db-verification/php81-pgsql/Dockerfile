FROM composer:2 AS composer-bin

FROM php:8.1-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libonig-dev libxml2-dev unzip git \
    && docker-php-ext-install pdo_pgsql mbstring dom \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer-bin /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
