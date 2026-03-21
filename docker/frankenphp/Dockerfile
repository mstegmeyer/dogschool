FROM dunglas/frankenphp:1-php8.4-bookworm

RUN install-php-extensions \
    pdo_mysql \
    intl \
    zip \
    opcache

WORKDIR /app
