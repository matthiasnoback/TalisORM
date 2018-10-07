#!/usr/bin/env bash

set -e

docker-compose up -d mysql
docker-compose run composer install --prefer-dist
docker-compose run php72 vendor/bin/phpunit -v
docker-compose run phpstan analyze
