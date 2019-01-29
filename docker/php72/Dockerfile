FROM php:7.2-cli-alpine

# Needed for running Scrutinizer's ocular.phar
RUN apk add --no-cache git

# Install the Blackfire probe
RUN version=$(php -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;") \
    && curl -A "Docker" -o /tmp/blackfire-probe.tar.gz -D - -L -s https://blackfire.io/api/v1/releases/probe/php/alpine/amd64/$version \
    && mkdir -p /tmp/blackfire \
    && tar zxpf /tmp/blackfire-probe.tar.gz -C /tmp/blackfire \
    && mv /tmp/blackfire/blackfire-*.so $(php -r "echo ini_get('extension_dir');")/blackfire.so \
    && printf "extension=blackfire.so\nblackfire.agent_socket=tcp://blackfire:8707\n" > $PHP_INI_DIR/conf.d/blackfire.ini \
    && rm -rf /tmp/blackfire /tmp/blackfire-probe.tar.gz

# Install the Blackfire client (agent)
RUN mkdir -p /tmp/blackfire \
    && curl -A "Docker" -L https://blackfire.io/api/v1/releases/client/linux_static/amd64 | tar zxp -C /tmp/blackfire \
    && mv /tmp/blackfire/blackfire /usr/bin/blackfire \
    && rm -Rf /tmp/blackfire

# Instal Composer
RUN apk --no-cache add zlib-dev \
    && docker-php-ext-install zip \
    && php -m | grep zip
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Install PDO with MySQL and Sqlite support
RUN php -m | grep sqlite
RUN docker-php-ext-install pdo pdo_mysql \
    && php -m | grep mysql

# Install Xdebug (for code coverage)
RUN apk add --no-cache --virtual .build-dependencies $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk --no-cache del .build-dependencies \
    && php -m | grep xdebug

WORKDIR /app
