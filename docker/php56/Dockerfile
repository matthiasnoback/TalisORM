FROM php:5.6-cli-alpine
RUN docker-php-ext-install pdo pdo_mysql
RUN apk add --no-cache --virtual .build-dependencies $PHPIZE_DEPS \
    && pecl install xdebug-2.5.0 \
    && docker-php-ext-enable xdebug \
    && apk --no-cache del .build-dependencies \
    && php -m | grep xdebug
RUN apk --no-cache add zlib-dev \
    && docker-php-ext-install zip \
    && php -m | grep zip
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
WORKDIR /app
