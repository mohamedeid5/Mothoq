FROM php:8.4-fpm-bookworm

ARG WWWGROUP=1000
ARG WWWUSER=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        libcurl4-openssl-dev \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libsqlite3-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        curl \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        pdo_sqlite \
        zip \
    && pecl install redis-6.3.0 \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2.9 /usr/bin/composer /usr/local/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-mothoq.ini
COPY docker/php/entrypoint.sh /usr/local/bin/mothoq-entrypoint

RUN groupmod --non-unique --gid "${WWWGROUP}" www-data \
    && usermod --non-unique --uid "${WWWUSER}" --gid "${WWWGROUP}" www-data \
    && chmod +x /usr/local/bin/mothoq-entrypoint

WORKDIR /var/www/html

ENV COMPOSER_HOME=/tmp/composer

COPY --chown=www-data:www-data composer.json composer.lock ./

USER www-data

RUN composer install --no-interaction --prefer-dist --no-autoloader --no-scripts

COPY --chown=www-data:www-data . .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader

EXPOSE 9000

ENTRYPOINT ["mothoq-entrypoint"]
CMD ["php-fpm"]
