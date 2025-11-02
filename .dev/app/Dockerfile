FROM composer:latest AS composer

FROM php:8.4-apache AS target

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN a2enmod rewrite

RUN apt-get -y update && apt-get install -y libicu-dev libpng-dev zlib1g-dev git libzip-dev

RUN pecl install xdebug
RUN docker-php-ext-configure intl
RUN docker-php-ext-install gd intl zip
RUN docker-php-ext-enable gd intl xdebug zip

# Update document root to public folder
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf


RUN groupmod -g 1000 www-data && usermod -u 1000 www-data
USER www-data
