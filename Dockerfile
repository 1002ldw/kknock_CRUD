ARG PHP_VERSION=8.3
FROM php:${PHP_VERSION}-apache

RUN docker-php-ext-install mysqli

WORKDIR /var/www/html

COPY --chown=www-data:www-data *.php ./
COPY docker/init-db.php /usr/local/bin/init-db.php
COPY sql/schema.sql /opt/kknock/schema.sql
COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini
RUN echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername \
    && mkdir -p /var/www/uploads \
    && chown www-data:www-data /var/www/uploads

EXPOSE 80
