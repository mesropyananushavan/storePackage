FROM php:5.6-cli

RUN sed -ri 's/deb.debian.org|security.debian.org/archive.debian.org/g' /etc/apt/sources.list \
    && sed -ri 's|^deb .*stretch-updates.*$|# &|g' /etc/apt/sources.list \
    && printf 'Acquire::Check-Valid-Until "false";\nAcquire::AllowInsecureRepositories "true";\nAPT::Get::AllowUnauthenticated "true";\n' > /etc/apt/apt.conf.d/99archive \
    && apt-get -o Acquire::AllowInsecureRepositories=true -o APT::Get::AllowUnauthenticated=true update \
    && apt-get install -y --allow-unauthenticated --no-install-recommends git unzip libxml2-dev zlib1g-dev \
    && docker-php-ext-install dom mbstring xmlwriter \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.2 /usr/bin/composer /usr/local/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HOME=/tmp/composer
ENV COMPOSER_CACHE_DIR=/tmp/composer-cache

WORKDIR /app
