#!/usr/bin/env bash

set -e

mkdir -p build/logs/
docker-compose up -d mysql
docker-compose run php56 composer install --prefer-dist
docker-compose run php56 vendor/bin/phpunit -v --coverage-text --coverage-clover=build/logs/clover.xml
docker-compose run php72 vendor/bin/phpunit -v
docker-compose run phpstan analyze
