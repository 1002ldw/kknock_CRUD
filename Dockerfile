ARG PHP_VERSION=8.3
FROM php:${PHP_VERSION}-apache

RUN docker-php-ext-install mysqli

WORKDIR /var/www/html

COPY --chown=www-data:www-data *.php ./
COPY docker/init-db.php /usr/local/bin/init-db.php
COPY sql/schema.sql /opt/kknock/schema.sql

EXPOSE 80
