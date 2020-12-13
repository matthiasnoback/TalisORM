#!/usr/bin/env bash


# Use this script to build new versions of the Docker images that can be pulled by anyone who wants to run the tests.

set -e

docker-compose build
docker-compose push
