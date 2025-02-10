#!/bin/sh

set -e

cleanup() {
    kill -TERM "$command" 2> /dev/null
}

COMPOSER_FLAGS="--no-progress --prefer-dist"
if [[ ${APP_ENV:="dev"} == "prod" ]]; then
    COMPOSER_FLAGS="--no-dev --optimize-autoloader $COMPOSER_FLAGS"
fi

composer install ${COMPOSER_FLAGS}

chown -R www-data:www-data /var/www
chown -R www-data:www-data ${COMPOSER_HOME:="/var/lib/composer"}

trap 'cleanup' INT TERM

"$@" &

command=$!

wait "$command"
