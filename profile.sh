#!/usr/bin/env bash

set -e

docker-compose run php72 blackfire run php profile.php
