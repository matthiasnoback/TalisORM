FROM php:7.2-cli-alpine
RUN docker-php-ext-install pdo pdo_mysql

# Needed for running Scrutinizer's ocular.phar
RUN apk add --no-cache git

WORKDIR /app
