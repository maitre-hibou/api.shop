#!/bin/sh

set -eu

envsubst '${APP_DOMAIN} ${COMPOSE_PROJECT_NAME}' < /.docker-config/app.conf.template > /etc/nginx/conf.d/app.conf

/docker-entrypoint.sh "$@"
