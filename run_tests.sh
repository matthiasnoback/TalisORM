#!/usr/bin/env bash

set -e

HOST_UID=$(id -u)
HOST_GID=$(id -g)

mkdir -p build/logs/
docker-compose up -d mysql

docker-compose run --user="${HOST_UID}:${HOST_GID}" php72 \
    composer install --prefer-dist

docker-compose run --user="${HOST_UID}:${HOST_GID}" php72 \
    vendor/bin/phpunit -v \
    --coverage-text \
    --coverage-clover=build/logs/clover.xml

docker-compose run phpstan analyze

docker-compose run --user="${HOST_UID}:${HOST_GID}" php72 ./migrate.sh
