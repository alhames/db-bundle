FROM php:8.0-cli

RUN apt-get update \
    && apt-get install --no-install-recommends -y zlib1g-dev libzip-dev \
    && docker-php-ext-install zip mysqli \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

WORKDIR /app

COPY . /app
COPY --from=composer /usr/bin/composer /usr/local/bin/composer

RUN composer install

CMD ['/app/vendor/bin/phpunit']
