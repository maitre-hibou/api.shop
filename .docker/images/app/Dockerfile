#
# We will use PHP-FPM as base for our image. PHP version is configurable at build using an argument.
#
ARG         PHP_IMAGE_VERSION=8.4
FROM        php:${PHP_IMAGE_VERSION}-fpm AS base

#
# Here we define some useful arguments and environments variable used throughout our image
#
ARG         USER_ID=1000
ARG         GROUP_ID=1000
ENV         COMPOSER_HOME=/usr/local/lib/composer

#
# When working with local volumes, it is usefull to synchronize the UID of the user that will create/update files on the container
# with the one on the host machine to avoir issues with file permissions.
#
RUN         usermod -u $USER_ID www-data && groupmod -g $GROUP_ID www-data

#
# Here we install dependencies used by core components of our image such as Composer.
#
RUN         apt update && \
            apt install -y --no-install-recommends curl libcurl4-openssl-dev libxml2-dev libzip-dev unzip wget zip

#
# Now we can install and enable core php extensions.
#
RUN         docker-php-ext-install curl simplexml zip

#
# Now we can proceed by installing composer inside our image using the official Composer Docker image (to avoid managing the
# whole installation process by ourselves.
#
RUN         mkdir -p $COMPOSER_HOME
RUN         chown -R www-data:www-data $COMPOSER_HOME
COPY        --from=composer/composer:2.8 /usr/bin/composer /usr/local/bin/composer

#
# Next, we copy our own PHP configuration file to override core PHP configurations.
#
COPY        .docker/images/app/config/docker.php.ini /usr/local/ect/php/conf.d/

#
# We can now define the working directory of our image where source files will live, and enable this directory as a volume
#
RUN         rm -rf /var/www && mkdir -p /var/www
RUN         chown -R www-data:www-data /var/www

WORKDIR     /var/www
VOLUME      /var/www

#
# Installation of healthcheck script and dependencies
#

RUN         apt update && apt install -y libfcgi0ldbl
RUN         wget -O /usr/local/bin/php-fpm-healthcheck \
            https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/master/php-fpm-healthcheck && \
            chmod +x /usr/local/bin/php-fpm-healthcheck
RUN         set -xe && echo "pm.status_path = /status" >> /usr/local/etc/php-fpm.d/zz-docker.conf

HEALTHCHECK --interval=5s --timeout=1s --retries=3 \
    CMD     php-fpm-healthcheck || exit 1

#
# Image cleanup
#
RUN         apt autoremove --purge && apt clean && rm -rf /var/lib/apt/lists/* /tmp/*
