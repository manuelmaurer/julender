FROM composer:latest AS builder

# Copy composer files and install dependencies
COPY composer.json composer.lock /app/
RUN composer install --no-dev --optimize-autoloader -d /app

FROM php:8.3-apache AS target

# Enable container caching by default
ENV CONTAINER_CACHE=1

# Copy vendor folder from builder stage
COPY --from=builder /app/vendor /var/www/html/vendor

# Copy source code
COPY config /var/www/html/config/
COPY public /var/www/html/public/
COPY src /var/www/html/src/
COPY translations /var/www/html/translations/
COPY views /var/www/html/views/

# Create tmp folder and set permissions
RUN mkdir -p /var/www/html/tmp && \
    chown -R www-data:www-data /var/www/html/tmp

# Configure apache
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf && \
    sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf && \
    a2enmod rewrite

# Configure PHP
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN apt-get update && apt-get install -y --no-install-recommends \
      libmagickwand-dev \
	&& pecl install imagick \
	&& docker-php-ext-enable imagick \
    && rm -rf /var/lib/apt/lists/*

# Volume mount the media folder
VOLUME /var/www/html/media
