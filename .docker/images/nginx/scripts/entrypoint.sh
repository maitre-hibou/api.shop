#!/bin/sh

set -eu

envsubst '${APP_DOMAIN} ${COMPOSE_PROJECT_NAME}' < /.docker-config/symfony.conf.template > /etc/nginx/conf.d/symfony.conf

/docker-entrypoint.sh "$@"
