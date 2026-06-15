# 빌드 시 PHP 버전을 교체할 수 있도록 기본 버전을 인자로 선언합니다.
ARG PHP_VERSION=8.3
FROM php:${PHP_VERSION}-apache

# 애플리케이션의 MySQL 연결에 필요한 mysqli 확장을 설치합니다.
RUN docker-php-ext-install mysqli

WORKDIR /var/www/html

# 웹 실행 계정이 애플리케이션 파일을 소유하도록 복사합니다.
COPY --chown=www-data:www-data *.php ./
COPY docker/init-db.php /usr/local/bin/init-db.php
COPY sql/schema.sql /opt/kknock/schema.sql
COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini
# Apache 경고를 제거하고 첨부파일 저장 디렉터리 권한을 준비합니다.
RUN echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername \
    && mkdir -p /var/www/uploads \
    && chown www-data:www-data /var/www/uploads

EXPOSE 80
