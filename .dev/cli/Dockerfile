FROM composer:latest AS composer

FROM php:8.3-cli AS app

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
RUN echo "memory_limit = -1" >> "$PHP_INI_DIR/conf.d/memory_limit.ini"

RUN apt update && apt install -y libzip-dev unzip git libmagickwand-dev

RUN pecl install xdebug imagick
RUN docker-php-ext-install zip
RUN docker-php-ext-enable xdebug zip imagick


RUN groupmod -g 1000 www-data && usermod -u 1000 www-data
USER www-data
